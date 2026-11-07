# راهنمای نصب روی لینوکس — Mojavez

> این پوشه شامل اسکریپت‌های نصب و راه‌اندازی سامانه روی توزیع‌های لینوکس است.

## روش‌های نصب

### ۱) نصب کامل با Apache + MariaDB (توصیه‌شده)

```bash
sudo bash deploy/linux/install.sh
```

این اسکریپت:
- پیش‌نیازها را نصب می‌کند (Apache, PHP, MariaDB)
- پروژه را در `/var/www/Mojavez` کپی می‌کند
- پایگاه داده را می‌سازد و `schema.sql` را اجرا می‌کند
- ماژول‌های Apache را فعال می‌کند (`mod_rewrite`, `mod_headers`, `mod_deflate`, `mod_expires`)
- Apache vhost را نصب و فعال می‌کند

### ۲) نصب به‌عنوان سرویس systemd (بدون Apache)

مناسب برای توسعه یا محیط‌های کوچک که Apache/Nginx نمی‌خواهید:

```bash
sudo bash deploy/linux/install-service.sh
```

این سرویس:
- سرور داخلی PHP را روی پورت 8000 اجرا می‌کند
- با `systemctl restart mojavez` قابل کنترل است
- لاگ‌ها در `journalctl -u mojavez` قابل مشاهده‌اند

### ۳) نصب دستی با Nginx + PHP-FPM

```bash
# ۱) نصب پیش‌نیازها
sudo apt-get install -y nginx mariadb-server php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-gd php8.2-curl

# ۲) کپی پروژه
sudo cp -r . /var/www/Mojavez
sudo chown -R www-data:www-data /var/www/Mojavez
sudo chmod -R 0775 /var/www/Mojavez/uploads

# ۳) ساخت دیتابیس
sudo mysql -u root -p < <(echo "CREATE DATABASE handicraft_license CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")
sudo mysql -u root -p handicraft_license < database/schema.sql

# ۴) نصب پیکربندی Nginx
sudo cp deploy/nginx/mojavez.conf /etc/nginx/sites-available/mojavez
sudo ln -s /etc/nginx/sites-available/mojavez /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default  # اختیاری
sudo nginx -t
sudo systemctl reload nginx

# ۵) راه‌اندازی مجدد PHP-FPM
sudo systemctl restart php8.2-fpm
```

## توزیع‌های پشتیبانی‌شده

| توزیع | پشتیبانی | بسته‌ها |
|---|---|---|
| Debian 11+ | ✅ | apache2, mariadb-server, php, php-mysql, php-mbstring, php-gd |
| Ubuntu 20.04+ | ✅ | همان Debian |
| CentOS 8+ / RHEL 9 | ✅ | httpd, mariadb-server, php, php-mysqlnd, php-mbstring, php-gd |
| Fedora 38+ | ✅ | همان CentOS |
| Arch Linux | ✅ | apache, mariadb, php, php-gd |

## پیش‌نیازها

| نرم‌افزار | حداقل نسخه |
|---|---|
| PHP | 8.0+ با `pdo_mysql`, `mbstring`, `fileinfo`, `gd` |
| MariaDB / MySQL | 10.4+ / 5.7+ (با پشتیبانی JSON) |
| Apache | 2.4+ با `mod_rewrite`, `mod_headers` |

## پیکربندی پس از نصب

۱) `/var/www/Mojavez/config/config.php` را ویرایش کنید:
```php
define('APP_DEBUG', false);          // روی سرور واقعی حتماً false
define('DB_HOST', 'localhost');
define('DB_NAME', 'handicraft_license');
define('DB_USER', 'mojavez');        // کاربر غیر-root توصیه می‌شود
define('DB_PASS', 'رمز‌قوی');
```

۲) کاربر اختصاصی MySQL بسازید (بهتر از استفادهٔ root):
```sql
CREATE USER 'mojavez'@'localhost' IDENTIFIED BY 'رمز‌قوی';
GRANT ALL PRIVILEGES ON handicraft_license.* TO 'mojavez'@'localhost';
FLUSH PRIVILEGES;
```

۳) رمز عبور مدیر را از پنل تغییر دهید.

## دستورات مفید

```bash
# مشاهدهٔ وضعیت سرویس‌ها
systemctl status apache2 mariadb

# مشاهدهٔ لاگ‌ها
sudo tail -f /var/log/apache2/mojavez_error.log
sudo tail -f /var/log/nginx/mojavez_error.log

# راه‌اندازی مجدد
sudo systemctl reload apache2
sudo systemctl reload nginx
sudo systemctl restart php8.2-fpm

# پشتیبان‌گیری از دیتابیس
mysqldump -u root -p handicraft_license > backup_$(date +%F).sql

# بازگردانی
mysql -u root -p handicraft_license < backup_YYYY-MM-DD.sql
```

## فعال‌سازی HTTPS (تولید)

```bash
# نصب Certbot
sudo apt-get install -y certbot python3-certbot-apache
# یا برای Nginx: python3-certbot-nginx

# دریافت گواهی Let's Encrypt
sudo certbot --apache -d mojavez.example.com
# یا: sudo certbot --nginx -d mojavez.example.com
```

گواهی به‌صورت خودکار هر ۹۰ روز تمدید می‌شود.

## رفع اشکال

### خطای «Permission denied» در uploads
```bash
sudo chown -R www-data:www-data /var/www/Mojavez/uploads
sudo chmod -R 0775 /var/www/Mojavez/uploads
```

### خطای «SQLSTATE[HY000] [2002] No such file or directory»
در `config/config.php` مقدار `DB_HOST` را به `127.0.0.1` تغییر دهید (به‌جای `localhost`).

### خطای ۴۰۴ در زیرمسیرها
ماژول `mod_rewrite` را فعال کنید:
```bash
sudo a2enmod rewrite
sudo systemctl reload apache2
```
