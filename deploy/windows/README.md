# راهنمای نصب روی ویندوز — Mojavec

> این پوشه شامل اسکریپت‌ها و راهنمای نصب و راه‌اندازی سامانه روی ویندوز است.

## روش‌های موجود

### ۱) راه‌اندازی سریع با سرور داخلی PHP (توصیه‌شده)

اگر PHP را روی ویندوز نصب دارید (با یا بدون XAMPP/Laragon):

```cmd
deploy\windows\install-and-run.bat
```

این اسکریپت:
- وجود PHP و MySQL را بررسی می‌کند
- پایگاه دادهٔ `handicraft_license` را می‌سازد
- `database/schema.sql` را اجرا می‌کند
- سرور توسعه PHP را روی `http://localhost:8000` راه‌اندازی می‌کند

### ۲) نصب با XAMPP

اگر XAMPP را در مسیر پیش‌فرض `C:\xampp` نصب دارید:

```cmd
deploy\windows\install-xampp.bat
```

این اسکریپت:
- پوشهٔ پروژه را در `htdocs` بررسی می‌کند (باید دستی کپی کنید)
- پنل کنترل XAMPP را باز می‌کند
- پایگاه داده را می‌سازد و `schema.sql` را اجرا می‌کند

### ۳) نصب با Laragon

اگر Laragon نصب دارید:

1. پوشهٔ پروژه را در `C:\laragon\www\Mojavez` کپی کنید.
2. پنل Laragon را باز کنید و Apache + MySQL را استارت کنید.
3. خط فرمان Laragon (Cmder) را باز کنید:
   ```cmd
   cd C:\laragon\www\Mojavez
   mysql -u root -e "CREATE DATABASE handicraft_license CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root handicraft_license < database\schema.sql
   ```
4. آدرس `http://localhost/Mojavez/public/` را در مرورگر باز کنید.

## پیش‌نیازها

| نرم‌افزار | حداقل نسخه | توضیح |
|---|---|---|
| PHP | 8.0+ | با افزونه‌های `pdo_mysql`, `mbstring`, `fileinfo`, `gd` |
| MySQL / MariaDB | 5.7+ / 10.4+ | با پشتیبانی از JSON و توابع پنجره‌ای |
| Apache | 2.4+ | با `mod_rewrite`, `mod_headers` (یا سرور داخلی PHP) |

## پیکربندی پس از نصب

۱) فایل `config/config.php` را باز کنید و در صورت نیاز مقادیر زیر را تغییر دهید:

```php
define('APP_DEBUG', false);   // روی سرور واقعی
define('DB_HOST', 'localhost');
define('DB_NAME', 'handicraft_license');
define('DB_USER', 'root');
define('DB_PASS', '');         // رمز عبور MySQL
define('BASE_URL', '/');       // یا '/Mojavez/public/' در زیرپوشه
```

۲) در اولین ورود به پنل مدیر، رمز عبور پیش‌فرض `admin123` را تغییر دهید.

## رفع اشکال

### خطای «Access denied for user 'root'»
رمز عبور MySQL را در `config.php` تنظیم کنید.

### خطای «404 Not Found»
- `mod_rewrite` Apache را فعال کنید.
- در XAMPP: ویرایش `C:\xampp\apache\conf\httpd.conf` و برداشتن `#` از ابتدای خط `LoadModule rewrite_module modules/mod_rewrite.so`.

### خطای «500 Internal Server Error»
- مقدار `APP_DEBUG` را به‌صورت موقت `true` کنید تا پیام خطای دقیق ببینید.
- بررسی کنید که `extension=pdo_mysql` در `php.ini` فعال باشد.

### فونت‌ها و آیکون‌ها نمایش داده نمی‌شوند
- مطمئن شوید پوشهٔ `public/assets/` قابل دسترسی است.
