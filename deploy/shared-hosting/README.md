# راهنمای استقرار روی هاست اشتراکی (نت افراز / ایران استاندارد)

این راهنما برای استقرار سامانهٔ Mojavec روی هاست اشتراکی با DirectAdmin
سازگار است (مثل سرویس برنزی نت افراز).

## مشخصات سرویس برنزی نت افراز

| منبع | مقدار | وضعیت پروژه |
|---|---|---|
| فضای هاست | ۱۵ گیگابایت | ✓ کافی (پروژه ۲ مگ + uploads) |
| پهنای باند ماهیانه | ۱۵۰ گیگابایت | ✓ برای ۱۰۰۰ کاربر/روز کافی |
| تعداد دامنه اصلی | ۲ | ✓ |
| تعداد پایگاه داده | ۵ | ✓ (نیاز به ۱) |
| **حداکثر حجم دیتابیس** | **۳۰۰ مگابایت** | ⚠️ برای داده‌های کم OK، در صورت رشد نیاز به ارتقا |
| تعداد ایمیل | ۲۰ | ✓ |
| پنل مدیریت | DirectAdmin | ✓ |
| سرور وب | **Nginx + Apache** | ✓ (mod_rewrite پشتیبانی می‌شود) |
| SSL رایگان | بله (Let's Encrypt) | ✓ |
| نسخه PHP | 5/7/8 قابل انتخاب | ✓ (انتخاب 8.x) |
| بک‌آپ | روزانه + هفتگی | ✓ |
| آپتایم | ۹۹٪ | ✓ |
| **SSH دسترسی** | ❌ محدود | ⚠️ باید از فایل‌منیجر DirectAdmin استفاده کنید |

---

## پیش‌نیازها

### ۱) ساخت دیتابیس در DirectAdmin

1. وارد پنل DirectAdmin شوید: `https://your-domain.com:2222`
2. به **MySQL Management** بروید
3. **Create New Database** را بزنید:
   - نام دیتابیس: `mojavez` (در نهایت چیزی مثل `user_mojavez` می‌شود)
   - نام کاربری: `mojavez_user` (یا همان نام دیتابیس)
   - رمز عبور: یک رمز قوی (در جای امن ذخیره کنید)
4. **Character Set** را روی `utf8mb4_unicode_ci` تنظیم کنید (اگر این گزینه هست)
5. دیتابیس ساخته شد ✓

### ۲) فعال‌سازی PHP 8.x

1. در DirectAdmin به **Select PHP Version** بروید
2. نسخهٔ **8.2** یا **8.3** را انتخاب کنید (8.4/8.5 هم OK)
3. روی **Save** بزنید

### ۳) فعال‌سازی SSL رایگان

1. در DirectAdmin به **SSL Certificates** بروید
2. **Free & automatic Let's Encrypt certificate** را انتخاب کنید
3. دامنه و ساب‌دامنهٔ www را تیک بزنید
4. **Save** بزنید — گواهی در چند دقیقه نصب می‌شود

### ۴) فعال‌سازی cron job (برای لغو خودکار)

1. در DirectAdmin به **Cron Jobs** بروید
2. یک cron جدید بسازید:
   - **Minute**: `0`
   - **Hour**: `*/6` (هر ۶ ساعت)
   - **Day**: `*`
   - **Month**: `*`
   - **Weekday**: `*`
   - **Command**: `wget -q -O /dev/null "https://your-domain.com/check-cancel?key=YOUR_CRON_SECRET" > /dev/null 2>&1`

   مقدار `YOUR_CRON_SECRET` بعد از نصب به شما داده می‌شود.

---

## مراحل استقرار

### گام ۱: آپلود فایل‌ها

۱) فایل‌های پروژه را (به‌جز `.git/`، `tests/`، `dev-server.sh`) با FTP یا فایل‌منیجر DirectAdmin آپلود کنید:

```
public_html/
├── public/        ← محتوای این پوشه را در public_html/ بریزید
│   ├── .htaccess
│   ├── index.php
│   ├── check.php (موقت — بعداً حذف)
│   ├── install.php (موقت — بعداً حذف)
│   ├── assets/
│   └── ...
├── app/
├── core/
├── config/
├── database/
├── deploy/
├── uploads/
└── .env (ساخته می‌شود)
```

**ساختار نهایی هاست:**
```
/home/youruser/
├── public_html/          ← DocumentRoot (محتوای public/ اینجا)
│   ├── .htaccess
│   ├── index.php
│   ├── check.php
│   ├── install.php
│   └── assets/
├── domains/
│   └── your-domain.com/
│       └── public_html/  (یا این مسیر)
└── (سایر فایل‌های پروژه)
    ├── app/
    ├── core/
    ├── config/
    ├── database/
    ├── uploads/
    └── .env
```

**نکتهٔ مهم:** اگر فایل‌های app/core/config در دسترس عمومی نیستند (یعنی DocumentRoot فقط `public/` است)، امنیت بهتر است. اگر کل پروژه در `public_html/` است، `.htaccess` باید دسترسی به این پوشه‌ها را مسدود کند (به‌صورت پیش‌فرض این کار را می‌کند).

۲) **فایل `deploy/shared-hosting/.htaccess`** را به‌جای `public/.htaccess` کپی کنید (این نسخه برای هاست اشتراکی بهینه‌تر است):

```bash
# روی هاست، در فایل‌منیجر:
cp deploy/shared-hosting/.htaccess public_html/.htaccess
```

### گام ۲: بررسی پیش‌نیازها

۱) فایل `deploy/shared-hosting/check.php` را در `public_html/` کپی کنید

۲) با مرورگر باز کنید: `https://your-domain.com/check.php`

۳) بررسی کنید همه‌چیز سبز است. اگر چیزی قرمز است:
   - **mod_rewrite غیرفعال**: با پشتیبانی تماس بگیرید
   - **PHP version**: در DirectAdmin → Select PHP Version → 8.x
   - **Upload limit**: در `.htaccess` اضافه کنید:
     ```apache
     php_value upload_max_filesize 5M
     php_value post_max_size 10M
     ```

### گام ۳: نصب با install.php

۱) فایل `deploy/shared-hosting/install.php` را در `public_html/` کپی کنید

۲) با مرورگر باز کنید: `https://your-domain.com/install.php`

۳) در فرم، اطلاعات دیتابیس را وارد کنید:
   - **Host**: `localhost`
   - **Database name**: نامی که در DirectAdmin ساختید (مثلاً `user_mojavez`)
   - **Username**: نام کاربری دیتابیس
   - **Password**: رمز عبور دیتابیس
   - **Base URL**: `/` (اگر در ریشهٔ دامنه است)

۴) روی **تست اتصال** بزنید. اگر موفق بود، گام ۲ را بزنید تا schema نصب شود.

۵) پس از نصب موفق:
   - فایل `.env` در ریشهٔ پروژه ساخته می‌شود
   - `CRON_SECRET` تولید و نمایش داده می‌شود (در جای امن ذخیره کنید)
   - همهٔ جداول دیتابیس ساخته می‌شوند

### گام ۴: حذف فایل‌های نصب

**مهم:** دو فایل زیر را از `public_html/` حذف کنید:

```
rm public_html/check.php
rm public_html/install.php
```

اگر این کار را نکنید، هر کسی می‌تواند دیتابیس شما را ریست کند!

### گام ۵: ساخت حساب مدیر

۱) با مرورگر به آدرس بروید: `https://your-domain.com/admin/login`

۲) چون هنوز حساب مدیر وجود ندارد، باید از طریق دیتابیس یکی بسازید. در DirectAdmin → **phpMyAdmin**:

```sql
INSERT INTO users (username, password, display_name) VALUES (
    'admin',
    '$2y$10$YOUR_BCRYPT_HASH_HERE',
    'مدیر کل'
);
```

برای تولید bcrypt hash، از این ابزار استفاده کنید:
- https://bcrypt-generator.com/
- یا اسکریپت PHP: `php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_BCRYPT);"`

۳) با نام کاربری `admin` و رمز انتخاب‌شده وارد شوید.

### گام ۶: تنظیم cron job

۱) در DirectAdmin → **Cron Jobs**:

| فیلد | مقدار |
|---|---|
| Minute | `0` |
| Hour | `*/6` |
| Day | `*` |
| Month | `*` |
| Weekday | `*` |
| Command | `wget -q -O /dev/null "https://your-domain.com/check-cancel?key=YOUR_CRON_SECRET" > /dev/null 2>&1` |

این کار هر ۶ ساعت اجرا می‌شود و پرونده‌های بیش از ۱۴ روز را لغو می‌کند.

---

## تنظیمات امنیتی

### ۱) غیرفعال‌کردن APP_DEBUG

فایل `.env` را ویرایش کنید:

```ini
APP_DEBUG=false
APP_ENV=production
```

### ۲) محدود کردن IP مدیر (اختیاری)

اگر مدیر فقط از IP خاصی وارد می‌شود، در `.env`:

```ini
ADMIN_IP_ALLOWLIST=192.168.1.10,203.0.113.5
```

### ۳) مسدود کردن IP‌های مشکوک

اگر حملاتی دریافت می‌کنید:

```ini
IP_BLOCKLIST=1.2.3.4,5.6.7.8
```

---

## تست نهایی

### تست ۱: صفحهٔ اصلی

```
https://your-domain.com/
```

باید صفحهٔ اصلی سامانه نمایش داده شود.

### تست ۲: صفحهٔ ورود مدیر

```
https://your-domain.com/admin/login
```

### تست ۳: صفحهٔ سلامت

```
https://your-domain.com/health
```

باید برگرداند: `{"status":"healthy","checks":{"db":"ok",...}}`

### تست ۴: مسدودسازی موتورهای جستجو

```bash
curl -I -A "Googlebot/2.1" https://your-domain.com/
# باید برگرداند: HTTP/1.1 410 Gone
```

### تست ۵: robots.txt

```
https://your-domain.com/robots.txt
```

باید بلیک‌لیست ربات‌ها را نمایش دهد.

---

## محدودیت‌های هاست اشتراکی

### ❌ عدم دسترسی SSH

برای اجرای دستورات، از فایل‌منیجر DirectAdmin یا **Terminal** (اگر DirectAdmin این قابلیت را دارد) استفاده کنید.

### ❌ محدودیت حجم دیتابیس

سرویس برنزی نت افراز: حداکثر **۳۰۰ مگابایت** برای دیتابیس. اگر داده‌ها زیاد شدند:
- پاکسازی لاگ‌های قدیمی
- فشرده‌سازی مدارک قدیمی
- ارتقا به سرویس بالاتر (نقره‌ای: ۵۰۰MB)

### ❌ محدودیت process

هاست اشتراکی محدودیت process دارد. اگر cron بیش از ۳۰ ثانیه طول بکشد، ممکن است kill شود.

### ⚠️ محدودیت file upload

برای آپلود مدارک بزرگ‌تر از ۲MB:
- در `.htaccess` اضافه کنید: `php_value upload_max_filesize 5M`
- در `config.php` مقدار `MAX_UPLOAD_SIZE` را به‌روزرسانی کنید

---

## پشتیبان‌گیری

### بک‌آپ خودکار

نت افراز بک‌آپ روزانه و هفتگی دارد. ولی توصیه می‌شود:
- هر هفته از دیتابیس export بگیرید (phpMyAdmin → Export)
- هر ماه فایل‌های `uploads/` را دانلود کنید

### بک‌آپ دستی

```bash
# از دیتابیس (از طریق phpMyAdmin یا SSH اگر دسترسی دارید):
mysqldump -u USER -p DATABASE > backup_$(date +%Y%m%d).sql

# از فایل‌های uploads:
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

---

## رفع مشکل

### مشکل: خطای ۵۰۰

**علت‌های رایج:**
1. `APP_DEBUG=true` در `.env` → به `false` تغییر دهید
2. خطای syntax در فایل PHP → لاگ خطا را بررسی کنید
3. کمبود حافظه → در `.htaccess`: `php_value memory_limit 256M`

**برای مشاهدهٔ لاگ:**
- DirectAdmin → **Site Summary / Statistics / Logs** → Error Log
- یا در `.env`: `ERROR_LOG_PATH=/home/youruser/logs/php-error.log`

### مشکل: صفحه سفید

1. `APP_DEBUG=true` کنید تا خطا نمایش داده شود
2. خطا را برطرف کنید
3. `APP_DEBUG=false` کنید

### مشکل: آپلود ناموفق

1. حجم فایل را بررسی کنید (`< 2MB` به‌صورت پیش‌فرض)
2. در `.htaccess`: `php_value upload_max_filesize 5M` و `php_value post_max_size 10M`
3. پوشهٔ `uploads/` قابل نوشتن باشد (`chmod 755`)

### مشکل: Session کار نمی‌کند

1. در DirectAdmin → **PHP Settings** → session.save_path را بررسی کنید
2. پوشهٔ `/tmp` قابل نوشتن باشد
3. در `.htaccess`: `php_value session.save_path /home/youruser/tmp`

### مشکل: زمان اجرای طولانی

ایمپورت دسته‌جمعی CSV نیاز به زمان دارد. در `.htaccess`:
```apache
php_value max_execution_time 120
php_value set_time_limit 120
```

---

## ارتقا از محیط لوکال به هاست

اگر از قبل روی XAMPP کار کرده‌اید:

1. دیتابیس محلی را export کنید (phpMyAdmin → Export)
2. روی هاست import کنید (phpMyAdmin → Import)
3. فایل‌های `uploads/` را با FTP آپلود کنید
4. فایل `.env` هاست را با اطلاعات هاست به‌روزرسانی کنید
5. `APP_DEBUG=false` کنید

---

## پشتیبانی

- **پشتیبانی فنی نت افراز**: ۰۲۱-۹۱۰۹-۴۱۴۱ یا تیکت
- **مستندات پروژه**: `README.md` در ریشهٔ پروژه
- **تست‌ها**: `tests/run.php` (با `php tests/run.php` اجرا کنید)

---

## چک‌لیست نهایی

- [ ] دیتابیس ساخته شد (`utf8mb4_unicode_ci`)
- [ ] PHP 8.x فعال شد
- [ ] SSL رایگان نصب شد
- [ ] فایل‌ها آپلود شدند (`public/` → `public_html/`)
- [ ] `.htaccess` هاست اشتراکی کپی شد
- [ ] `check.php` همه‌چیز را سبز نشان داد
- [ ] `install.php` اجرا شد و `.env` ساخته شد
- [ ] `check.php` و `install.php` حذف شدند
- [ ] حساب مدیر ساخته شد
- [ ] `APP_DEBUG=false` شد
- [ ] cron job تنظیم شد
- [ ] صفحهٔ اصلی تست شد
- [ ] صفحهٔ `/health` تست شد
- [ ] ورود مدیر تست شد
- [ ] بک‌آپ دستی از دیتابیس گرفته شد
