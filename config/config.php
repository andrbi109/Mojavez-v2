<?php
/**
 * فایل پیکربندی اصلی سامانه
 * سامانه مدیریت ثبت‌نام و پیگیری صدور مجوز صنایع دستی
 */

// ==================== نسخهٔ سامانه ====================
// نسخهٔ فعلی پروژه — در README.md، فوتر پنل و پاسخ‌های JSON بازگردانده می‌شود.
// الگوی SemVer: MAJOR.MINOR.PATCH
// این اولین نسخهٔ رسمی است. هر اصلاح یک واحد به PATCH اضافه می‌شود.
define('APP_VERSION', '0.1.2');
define('APP_VERSION_NAME', 'اصلاح خطای JavaScript و تغییر نوع خدمت');

// ==================== حالت اشکال‌زدایی ====================
// در محیط توسعه true و روی سرور واقعی حتماً false باشد.
// وقتی false است: خطاها در لاگ سرور ثبت می‌شوند و کاربر صفحهٔ عمومی ۵۰۰ می‌بیند.
define('APP_DEBUG', true);

// تنظیمات پایگاه داده
define('DB_HOST', 'localhost');
define('DB_NAME', 'handicraft_license');
define('DB_USER', 'root');
define('DB_PASS', '');

// مسیر پایه - در لوکال معمولاً خالی یا '/' است
define('BASE_URL', '/');

// مسیرهای اصلی
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('VIEW_PATH', APP_PATH . '/Views');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('SERVICES_PATH', APP_PATH . '/Services');

// تنظیمات آپلود
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // ۲ مگابایت
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_DIR', ROOT_PATH . '/uploads');

// استفاده از CDN برای Bootstrap
define('USE_CDN', false);

// تنظیمات نشست
define('SESSION_LIFETIME', 86400); // ۲۴ ساعت

// تنظیمات محدودیت زمانی
define('AUTO_CANCEL_DAYS', 14); // لغو خودکار پس از ۱۴ روز
define('PRIORITY_DAYS', 12); // اولویت‌بندی برای ثبت‌نام بیش از ۱۲ روز