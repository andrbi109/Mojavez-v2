# راهنمای استقرار — Mojavez

> این پوشه شامل تمام اسکریپت‌ها و پیکربندی‌های لازم برای استقرار سامانه در
> محیط‌های مختلف است.

## ساختار

```
deploy/
├── apache/         # پیکربندی Apache (vhost)
│   └── mojavez.conf
├── nginx/          # پیکربندی Nginx (server block)
│   └── mojavez.conf
├── docker/         # پیکربندی Docker (Dockerfile, Compose)
│   ├── Dockerfile
│   ├── docker-compose.yml
│   ├── entrypoint.sh
│   ├── nginx-proxy.conf
│   ├── .env.example
│   └── README.md
├── windows/        # اسکریپت‌های ویندوز (XAMPP, Laragon)
│   ├── install-and-run.bat
│   ├── install-xampp.bat
│   └── README.md
├── linux/          # اسکریپت‌های لینوکس (systemd, Apache)
│   ├── install.sh
│   ├── install-service.sh
│   └── README.md
└── README.md       # این فایل
```

## شروع سریع (انتخاب پلتفرم)

| پلتفرم | روش توصیه‌شده | دستور |
|---|---|---|
| **ویندوز (توسعه)** | سرور داخلی PHP | `deploy\windows\install-and-run.bat` |
| **ویندوز + XAMPP** | Apache XAMPP | `deploy\windows\install-xampp.bat` |
| **لینوکس (تولید)** | Apache + MariaDB | `sudo bash deploy/linux/install.sh` |
| **لینوکس (توسعه)** | سرور داخلی + systemd | `sudo bash deploy/linux/install-service.sh` |
| **لینوکس + Nginx** | Nginx + PHP-FPM | [راهنما](linux/README.md#۳-نصب-دستی-با-nginx--php-fpm) |
| **داکر** | Docker Compose | [راهنما](docker/README.md) |

## فایل‌های پیکربندی در ریشه

علاوه بر پوشهٔ `deploy/`، در ریشهٔ پروژه نیز فایل‌های هدایت خودکار وجود دارند:

| فایل | مسیر | کاربرد |
|---|---|---|
| `.htaccess` | ریشه | هدایت همهٔ درخواست‌ها به `public/` (وقتی DocumentRoot روی ریشه است) |
| `.htaccess` | `public/` | مسیریابی به `index.php` + هدرهای امنیتی + کش + فشرده‌سازی |
| `.htaccess` | `uploads/` | مسدودسازی کامل دسترسی مستقیم به فایل‌های کاربران |
| `.dockerignore` | ریشه | فایل‌های نادیده‌گرفته‌شده هنگام build داکر |

## نصب پایگاه داده

**در همهٔ پلتفرم‌ها**، نصب با یک فایل SQL انجام می‌شود:

```bash
mysql -u root -p < database/schema.sql
```

این فایل شامل تمام جداول، روابط، ایندکس‌ها و دادهٔ بذر اولیه است.

## متغیرهای محیطی قابل تنظیم

| متغیر | پیش‌فرض | توضیح |
|---|---|---|
| `APP_DEBUG` | `true` | حالت اشکال‌زدایی (در تولید `false` شود) |
| `DB_HOST` | `localhost` | میزبان پایگاه داده |
| `DB_NAME` | `handicraft_license` | نام پایگاه داده |
| `DB_USER` | `root` | نام کاربری |
| `DB_PASS` | (خالی) | رمز عبور |
| `BASE_URL` | `/` | مسیر پایهٔ سامانه (در زیرپوشه: `/mojavez/`) |
| `USE_CDN` | `false` | استفاده از CDN برای Bootstrap |

این متغیرها در `config/config.php` تعریف شده‌اند. برای داکر، در `deploy/docker/.env` قابل تنظیم‌اند.

## اطلاعات ورود پیش‌فرض

| مورد | مقدار |
|---|---|
| آدرس پنل مدیر | `/admin/login` |
| نام کاربری | `admin` |
| رمز عبور | `admin123` |

⚠️ **پس از اولین ورود، رمز عبور را از بخش «مدیریت کاربران» تغییر دهید.**

## بررسی سلامت بعد از نصب

```bash
# ۱) صفحهٔ اصلی
curl -I http://localhost/
# انتظار: HTTP/1.1 200 OK

# ۲) صفحهٔ لاگین مدیر
curl -I http://localhost/admin/login
# انتظار: HTTP/1.1 200 OK

# ۳) مسیر نامعتبر
curl -I http://localhost/page-not-exist
# انتظار: HTTP/1.1 404 Not Found

# ۴) تست پایتون (در صورت نصب)
php -r "new PDO('mysql:host=localhost;dbname=handicraft_license', 'root', '');"
# انتظار: بدون خروجی (موفق) یا PDOException با جزئیات خطا
```

## امنیت در تولید

چک‌لیست قبل از استقرار در محیط تولید:

- [ ] `APP_DEBUG = false` در `config/config.php`
- [ ] تغییر رمز عبور پیش‌فرض `admin`
- [ ] ایجاد کاربر MySQL غیر-root با امتیاز محدود به `handicraft_license`
- [ ] فعال‌سازی HTTPS (با Let's Encrypt یا گواهی سازمانی)
- [ ] فعال‌سازی فایروال (ufw/firewalld) — باز کردن فقط ۸۰ و ۴۴۳
- [ ] تنظیم `fail2ban` برای محافظت از SSH و ورود مدیر
- [ ] پشتیبان‌گیری خودکار روزانه از `database/` و `uploads/`
- [ ] محدودسازی `client_max_body_size` در Nginx یا `LimitRequestBody` در Apache
- [ ] غیرفعال‌سازی نمایش نسخهٔ سرور (`ServerTokens Prod` در Apache، `server_tokens off` در Nginx)

## پشتیبان‌گیری و بازگردانی

```bash
# پشتیبان‌گیری از دیتابیس
mysqldump -u root -p --single-transaction --routines handicraft_license > \
  backup_$(date +%Y%m%d_%H%M%S).sql

# پشتیبان‌گیری از فایل‌های بارگذاری‌شده
tar -czf uploads_$(date +%Y%m%d).tar.gz uploads/

# بازگردانی
mysql -u root -p handicraft_license < backup_YYYYMMDD_HHMMSS.sql
tar -xzf uploads_YYYYMMDD.tar.gz
```

## مشکل‌یابی

### صفحهٔ سفید یا خطای ۵۰۰
۱) `APP_DEBUG` را موقتاً `true` کنید.
۲) لاگ Apache/Nginx را ببینید:
   ```bash
   tail -f /var/log/apache2/mojavez_error.log
   tail -f /var/log/nginx/mojavez_error.log
   ```

### خطای اتصال به دیتابیس
۱) سرویس MySQL را بررسی کنید: `systemctl status mariadb`
۲) اعتبارنامه‌ها در `config/config.php` را بررسی کنید.
۳) اگر `localhost` کار نکرد، `127.0.0.1` را امتحان کنید.

### آپلود فایل کار نمی‌کند
۱) مجوز `uploads/` را بررسی کنید (باید ۷۷۵ و متعلق به www-data/apache).
۲) `upload_max_filesize` و `post_max_size` در `php.ini`.
۳) `MAX_UPLOAD_SIZE` در `config/config.php` (پیش‌فرض ۲MB).

## ارتقا به نسخه‌های جدید

```bash
cd /var/www/Mojavez
git pull origin main
# اجرای schema.sql (با IF NOT EXISTS امن است)
mysql -u root -p handicraft_license < database/schema.sql
sudo systemctl reload apache2  # یا nginx
```

نیازی به حذف داده‌های قبلی نیست — `schema.sql` با `IF NOT EXISTS` و `ON DUPLICATE KEY UPDATE` طراحی شده است.
