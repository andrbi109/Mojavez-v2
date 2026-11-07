<?php
/**
 * اسکریپت روتر مخصوص سرور توسعهٔ داخلی PHP (PHP Built-in Web Server)
 * ---------------------------------------------------------------
 * این فایل فقط برای اجرای پروژه در محیط توسعه با دستور زیر است:
 *   php -S localhost:8000 -t public public/router.php
 *
 * نقش آن دقیقاً مشابه mod_rewrite در آپاچی است:
 *  - اگر فایل فیزیکی درخواست‌شده وجود داشت (css/js/تصویر)، همان را برمی‌گرداند.
 *  - در غیر این صورت همهٔ درخواست‌ها را به index.php می‌سپارد (URL تمیز).
 *
 * توجه: در سرور واقعی (Apache/XAMPP/Laragon) این فایل استفاده نمی‌شود
 *       و public/.htaccess وظیفهٔ بازنویسی را بر عهده دارد.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// فایل یا پوشهٔ واقعی وجود دارد؟ (به‌جز فایل‌های php که باید به روتر بروند)
if ($path !== '/' && file_exists($file) && !is_dir($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false; // سرو خود فایل استاتیک توسط سرور داخلی
}

// در غیر این صورت، ورودی یکپارچهٔ برنامه
require __DIR__ . '/index.php';
