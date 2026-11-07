# استقرار داکری سامانهٔ Mojavez

این پوشه شامل تمام فایل‌های لازم برای اجرای سامانهٔ **Mojavez** (مدیریت ثبت‌نام و
پیگیری صدور مجوز صنایع دستی) با **Docker** و **Docker Compose** است.

> نسخهٔ هدف: PHP 8.2 خالص + Apache + MariaDB 10.11

---

## فهرست

- [معماری کلی](#معماری-کلی)
- [پیش‌نیازها](#پیش‌نیازها)
- [ساختار فایل‌ها](#ساختار-فایل‌ها)
- [نصب و راه‌اندازی سریع](#نصب-و-راه‌اندازی-سریع)
- [متغیرهای محیطی](#متغیرهای-محیطی)
- [دستورات مفید](#دستورات-مفید)
- [نکات امنیتی](#نکات-امنیتی)
- [رفع اشکال](#رفع-اشکال)

---

## معماری کلی

```
                ┌──────────────────────────────────────────┐
   کاربر  ──►   │  web (nginx:1.27-alpine)                 │  پورت: ${MOJAVEZ_PORT} (پیش‌فرض 8080)
                │  reverse proxy + هدرهای امنیتی           │
                └─────────────────────┬────────────────────┘
                                      │ (شبکهٔ mojavez-net)
                                      ▼
                ┌──────────────────────────────────────────┐
                │  app (php:8.2-apache — ساخته‌شده)        │  پورت داخلی 80
                │  • DocumentRoot: /var/www/html/public    │
                │  • entrypoint.sh: مقداردهی DB             │
                │  • uploads/ در volume دائمی               │
                └─────────────────────┬────────────────────┘
                                      │
                                      ▼
                ┌──────────────────────────────────────────┐
                │  db (mariadb:10.11)                       │  پورت داخلی 3306
                │  • volume: mojavez-db-data                │
                │  • init-db.sql در /docker-entrypoint…     │
                └──────────────────────────────────────────┘
```

سه کانتینر روی یک شبکهٔ اختصاصی (`mojavez-net`) قرار دارند. کانتینر `web` نقطهٔ
ورود عمومی است و درخواست‌ها را به `app` پروکسی می‌کند. کانتینر `app` از طریق PDO به
`db` متصل می‌شود.

> **اختیاری بودن nginx:** اگر nginx نمی‌خواهید، کل بلوک `web` را در `docker-compose.yml`
> کامنت کنید و بخش `ports` سرویس `app` را فعال نمایید تا Apache مستقیماً روی
> `${MOJAVEZ_PORT}` سرو شود.

---

## پیش‌نیازها

| نرم‌افزار        | حداقل نسخه | توضیح                                  |
|-----------------|------------|----------------------------------------|
| Docker Engine   | 20.10+     | موتور داکر                              |
| Docker Compose  | 2.0+       | به‌صورت پلاگین (`docker compose`)       |
| فضای دیسک       | ~۵۰۰ MB    | برای تصاویر پایه و داده‌ها              |
| RAM             | ۱ GB آزاد   | برای اجرای همزمان سه کانتینر           |

برای بررسی نصب بودن:

```bash
docker --version
docker compose version
```

---

## ساختار فایل‌ها

```
deploy/docker/
├── Dockerfile           # تصویر PHP 8.2 + Apache با افزونه‌های مورد نیاز
├── docker-compose.yml   # تعریف سه سرویس: db، app، web
├── .env.example         # نمونهٔ متغیرهای محیطی (به .env کپی کنید)
├── .dockerignore        # نسخهٔ مرجع (نسخهٔ فعال در ریشهٔ پروژه است)
├── entrypoint.sh        # انتظار برای MySQL + اجرای schema.sql + apache
├── init-db.sql          # wrapper سبک برای مقداردهی MariaDB
├── nginx-proxy.conf     # پیکربندی nginx reverse proxy
└── README.md            # همین فایل
```

---

## نصب و راه‌اندازی سریع

### ۱) آماده‌سازی فایل محیطی

```bash
cd deploy/docker
cp .env.example .env
```

سپس مقادیر `.env` را متناسب با محیط خود ویرایش کنید — **مخصوصاً رمزهای عبور**.

### ۲) ساخت و اجرای کانتینرها

```bash
docker compose up -d --build
```

این دستور:
1. تصویر `app` را از روی `Dockerfile` می‌سازد.
2. کانتینر `db` (MariaDB) را راه‌اندازی و منتظر سلامت آن می‌ماند.
3. کانتینر `app` را اجرا می‌کند؛ `entrypoint.sh` منتظر MySQL می‌ماند و در صورت
   `INIT_DB=1` اسکیمای دیتابیس را می‌سازد.
4. کانتینر `web` (nginx) را روی پورت `${MOJAVEZ_PORT}` راه‌اندازی می‌کند.

### ۳) بررسی وضعیت

```bash
docker compose ps
```

همهٔ سرویس‌ها باید `healthy` یا `running` باشند.

### ۴) دسترسی به سامانه

مرورگر را روی آدرس زیر باز کنید:

```
http://localhost:8080
```

(یا پورتی که در `.env` تنظیم کرده‌اید.)

---

## متغیرهای محیطی

همهٔ این متغیرها در فایل `.env` (کپی‌شده از `.env.example`) قابل تنظیم‌اند:

| متغیر           | پیش‌فرض               | توضیح                                           |
|-----------------|----------------------|-------------------------------------------------|
| `DB_HOST`       | `db`                 | هاست دیتابیس (نام سرویس در compose)             |
| `DB_NAME`       | `handicraft_license` | نام دیتابیس                                     |
| `DB_USER`       | `mojavez`            | کاربر اپلیکیشن                                   |
| `DB_PASS`       | `secret`            | رمز کاربر اپلیکیشن — **حتماً تغییر دهید**       |
| `DB_ROOT_PASS`  | `rootsecret`        | رمز root دیتابیس — **حتماً تغییر دهید**          |
| `DB_PORT`       | `3306`               | پورت دیتابیس (داخلی)                            |
| `APP_DEBUG`     | `false`              | حالت اشکال‌زدایی — در تولید `false` باشد         |
| `INIT_DB`       | `1`                  | اجرای خودکار `schema.sql` در راه‌اندازی         |
| `MOJAVEZ_PORT`  | `8080`               | پورت میزبان برای دسترسی عمومی (nginx)            |
| `TZ`            | `Asia/Tehran`        | منطقهٔ زمانی                                     |

### دربارهٔ `INIT_DB`

- `INIT_DB=1` (پیش‌فرض): `entrypoint.sh` فایل `/var/www/html/database/schema.sql`
  را روی دیتابیس اجرا می‌کند. چون schema از `CREATE TABLE IF NOT EXISTS` استفاده
  می‌کند، اجرای مجدد آن بی‌خطر (idempotent) است.
- `INIT_DB=0`: از مقداردهی اولیه صرف‌نظر می‌شود. اگر دیتابیس از قبل مقداردهی شده
  باشد، این حالت راه‌اندازی سریع‌تری دارد.

> **نکتهٔ فنی:** فایل `schema.sql` با دستور `CREATE DATABASE IF NOT EXISTS` شروع
> می‌شود. کاربر اپلیکیشن (`DB_USER`) امتیاز سراسری `CREATE` ندارد، بنابراین این
> خط به‌طور طبیعی خطای access-denied می‌دهد. اما دیتابیس از قبل توسط متغیر
> `MYSQL_DATABASE` در کانتینر `db` ساخته شده است؛ با پرچم `--force` در `mysql`،
> اجرا ادامه می‌یابد و بقیهٔ دستورات (`USE` و `CREATE TABLE IF NOT EXISTS`) با
> موفقیت اجرا می‌شوند. در پایان، صحت اجرا با بررسی وجود جدول `users` تأیید می‌شود.
> این رویکرد از دادن امتیاز `root` به کانتینر اپلیکیشن جلوگیری می‌کند (least privilege).

---

## دستورات مفید

### مشاهدهٔ لاگ‌ها

```bash
# لاگ همهٔ سرویس‌ها
docker compose logs -f

# لاگ فقط سرویس app
docker compose logs -f app

# ۱۰۰ خط آخر لاگ db
docker compose logs --tail=100 db
```

### ورود به کانتینرها

```bash
# ورود به کانتینر app (با bash)
docker compose exec app bash

# اجرای یک دستور PHP
docker compose exec app php -v

# ورود به MariaDB
docker compose exec db mariadb -u mojavez -p handicraft_license

# ورود به nginx
docker compose exec web sh
```

### توقف و راه‌اندازی مجدد

```bash
# توقف (کانتینرها حفظ می‌شوند)
docker compose stop

# راه‌اندازی مجدد
docker compose start

# توقف و حذف کانتینرها (volumeها حفظ می‌شوند)
docker compose down
```

### بازسازی تصویر پس از تغییر کد

```bash
docker compose up -d --build app
```

### ریست کامل (با حذف داده‌ها)

> ⚠️ این دستور **همهٔ داده‌های دیتابیس و فایل‌های آپلودشده** را حذف می‌کند.

```bash
docker compose down -v
```

سپس برای شروع تازه:

```bash
docker compose up -d --build
```

### اجرای دستی schema (در صورت نیاز)

```bash
docker compose exec app bash -c '\
  mysql --host=$DB_HOST --user=$DB_USER --password=$DB_PASS \
        --default-character-set=utf8mb4 --force \
        < /var/www/html/database/schema.sql'
```

### بک‌آپ از دیتابیس

```bash
docker compose exec db sh -c \
  'mariadb-dump -u root -p"$MYSQL_ROOT_PASSWORD" handicraft_license' \
  > backup-$(date +%F).sql
```

### بازیابی از بک‌آپ

```bash
docker compose exec -T db sh -c \
  'mariadb -u root -p"$MYSQL_ROOT_PASSWORD" handicraft_license' \
  < backup-2024-01-01.sql
```

---

## نکات امنیتی

### ۱) تغییر رمزهای عبور پیش‌فرض

رمزهای `DB_PASS` و `DB_ROOT_PASS` در `.env.example` مقادیر ضعیف دارند. **حتماً**
پیش از استقرار در محیط تولید، آن‌ها را در `.env` به مقادیر قوی تغییر دهید:

```bash
# تولید رمز تصادفی قوی
openssl rand -base64 32
```

### ۲) فایل `.env` را در گیت commit نکنید

فایل `.env` حاوی اسرار است. آن را به `.gitignore` اضافه کنید (در ریشهٔ پروژه
`.dockerignore` و `.gitignore` آن را مستثنی کرده‌اند). هرگز آن را در مخزن عمومی
نگه ندارید.

### ۳) محدود کردن پورت‌های انتشار

در محیط تولید، به‌جای انتشار روی `0.0.0.0:8080`، بهتر است nginx را پشت یک
reverse proxy با TLS (مثل Caddy، Traefik یا nginx میزبان) قرار دهید. می‌توانید
`MOJAVEZ_PORT` را به `127.0.0.1:8080` تغییر دهید تا فقط محلی سرو شود.

### ۴) `APP_DEBUG=false` در تولید

هرگز در محیط تولید `APP_DEBUG=true` نگذارید؛ در این حالت خطاهای دقیق PHP به
کاربر نمایش داده می‌شود که نشت اطلاعات حساس می‌کند.

### ۵) محافظت از پوشهٔ `uploads/`

- این پوشه بیرون از `DocumentRoot` است و توسط `.htaccess` محافظت می‌شود.
- در داکر، این پوشه روی volume دائمی `mojavez-uploads` مونت می‌شود تا فایل‌های
  کاربران با rebuild کانتینر از بین نروند.
- دسترسی مستقیم به فایل‌ها فقط از طریق مسیر امن برنامه (`/document/view` و
  `/document/thumb/{id}`) انجام می‌شود.

### ۶) پشتیبان‌گیری منظم

- دیتابیس: به‌صورت روزانه `mariadb-dump` بگیرید.
- فایل‌های `uploads/`: volume را پشتیبان بگیرید.

### ۷) به‌روزرسانی تصاویر پایه

به‌طور دوره‌ای تصاویر `php:8.2-apache`، `mariadb:10.11` و `nginx:1.27-alpine`
را به‌روز کنید تا از وصله‌های امنیتی بهره‌مند شوید:

```bash
docker compose pull
docker compose up -d --build
```

---

## رفع اشکال

### مشکل: کانتینر `app` در حالت `restart loop` است

بررسی لاگ:

```bash
docker compose logs app
```

شایع‌ترین دلایل:
- MySQL هنوز آماده نیست → صبر کنید یا `DB_HOST`/`DB_USER`/`DB_PASS` را بررسی کنید.
- `INIT_DB=1` ولی schema اجرا نشده → لاگ `entrypoint` را بخوانید.

### مشکل: صفحهٔ ۵۰۲ Bad Gateway از nginx

- کانتینر `app` در حال اجراست؟ `docker compose ps`
- آیا `app` روی پورت ۸۰ سرو می‌کند؟ `docker compose exec app curl -I http://localhost/`

### مشکل: خطای اتصال به دیتابیس در صفحه

- مطمئن شوید `DB_HOST=db` است (نام سرویس در compose).
- `DB_NAME`، `DB_USER`، `DB_PASS` با مقادیر `db` مطابقت داشته باشند.
- سلامت `db` را بررسی کنید: `docker compose ps db`

### مشکل: داده‌ها پس از `down -v` از بین رفتند

این رفتار طبیعی است. `down -v` حجم‌ها را هم حذف می‌کند. برای توقف موقت بدون حذف
داده‌ها از `docker compose stop` یا `docker compose down` (بدون `-v`) استفاده کنید.

### مشکل: پورت ۸۰۸۰ اشغال است

پورت دیگری در `.env` تنظیم کنید:

```bash
MOJAVEZ_PORT=8090
```

سپس `docker compose up -d` را دوباره اجرا کنید.

### مشکل: اسکیمای دیتابیس اجرا نشد

به‌صورت دستی اجرا کنید:

```bash
docker compose exec app bash -c '\
  mysql --host=$DB_HOST --user=$DB_USER --password=$DB_PASS \
        --default-character-set=utf8mb4 --force \
        < /var/www/html/database/schema.sql'
```

سپس وجود جدول‌ها را بررسی کنید:

```bash
docker compose exec db mariadb -u mojavez -p handicraft_license -e "SHOW TABLES;"
```

### مشکل: تغییرات کد اعمال نمی‌شود

تصویر را بازسازی کنید:

```bash
docker compose up -d --build app
```

---

## مجوز و اعتبار

این فایل‌های داکر برای پروژهٔ **Mojavez** (سامانهٔ مدیریت ثبت‌نام و پیگیری صدور
مجوز صنایع دستی) تهیه شده‌اند. برای جزئیات پروژه به `README.md` ریشه مراجعه کنید.
