<?php
/**
 * فایل پیکربندی اصلی سامانه
 * سامانه مدیریت ثبت‌نام و پیگیری صدور مجوز صنایع دستی
 * ----------------------------------------------------------------
 * مقادیر حساس (DB credentials، APP_DEBUG، CRON_SECRET) از فایل .env خوانده می‌شوند.
 * برای راه‌اندازی: فایل .env.example را به .env کپی کرده و مقادیر واقعی را جایگزین کنید.
 */

// بارگذاری لودر .env (قبل از هر define)
require_once __DIR__ . '/env.php';
load_env();

// ==================== نسخهٔ سامانه ====================
// نسخهٔ فعلی پروژه — در README.md، فوتر پنل و پاسخ‌های JSON بازگردانده می‌شود.
// الگوی SemVer: MAJOR.MINOR.PATCH
// v2.0.0 → v0.6.0: سخت‌سازی امنیتی، محافظت از حریم خصوصی، بهینه‌سازی تولید
define('APP_VERSION', '0.6.0');
define('APP_VERSION_NAME', 'سخت‌سازی امنیتی و حریم خصوصی — مسدودسازی موتورهای جستجو و خزنده‌های هوش مصنوعی');

// ==================== حالت اشکال‌زدایی ====================
// در محیط توسعه true و روی سرور واقعی حتماً false باشد.
// فقط از فایل .env خوانده می‌شود — پیش‌فرض false (امن).
// رفع H-11: auto-debug حذف شد چون در تونل/proxy با Host=localhost فعال می‌شد.
define('APP_DEBUG', (bool)env('APP_DEBUG', false));

// ==================== تنظیمات پایگاه داده ====================
// از فایل .env یا متغیرهای محیطی سرور خوانده می‌شوند.
// پیش‌فرض‌ها فقط برای توسعهٔ محلی هستند — در تولید حتماً از .env استفاده کنید.
define('DB_HOST', (string)env('DB_HOST', 'localhost'));
define('DB_NAME', (string)env('DB_NAME', 'handicraft_license'));
define('DB_USER', (string)env('DB_USER', 'root'));
define('DB_PASS', (string)env('DB_PASS', ''));

// مسیر پایه - در لوکال معمولاً خالی یا '/' است
define('BASE_URL', (string)env('BASE_URL', '/'));

// مسیرهای اصلی
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('VIEW_PATH', APP_PATH . '/Views');
define('CONFIG_PATH', ROOT_PATH . '/config');

// تنظیمات آپلود
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // ۲ مگابایت برای مدارک
define('MAX_CSV_SIZE', 5 * 1024 * 1024);    // ۵ مگابایت برای فایل CSV ایمپورت دسته‌جمعی
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('CSV_EXTENSION', 'csv');
define('UPLOAD_DIR', ROOT_PATH . '/uploads');

// استفاده از CDN برای Bootstrap
define('USE_CDN', (bool)env('USE_CDN', false));

// تنظیمات نشست
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 86400)); // ۲۴ ساعت

// تنظیمات محدودیت زمانی
define('AUTO_CANCEL_DAYS', 14); // لغو خودکار پس از ۱۴ روز
define('PRIORITY_DAYS', 12); // اولویت‌بندی برای ثبت‌نام بیش از ۱۲ روز

// ==================== امنیت endpoint های عمومی ====================
// رمز مشترک برای endpoint هایی که فقط باید از cron فراخوانی شوند (مثل /check-cancel).
// اگر CRON_SECRET در .env تنظیم شده باشد، درخواست باید آن را در کوئری یا هدر بفرستد.
// اگر خالی باشد، endpoint غیرفعال می‌شود.
define('CRON_SECRET', (string)env('CRON_SECRET', ''));

// ==================== احراز هویت اختیاری برای tracking عمومی ====================
// اگر true باشد، کد رهگیری به‌تنهایی کافی نیست؛ کاربر باید موبایل را هم وارد کند.
// فعلاً برای backward-compat پیش‌فرض false (در فاز بعدی به true تغییر کند).
define('TRACKING_REQUIRE_MOBILE', (bool)env('TRACKING_REQUIRE_MOBILE', false));

// ==================== Rate Limit عمومی برای endpoint های عمومی ====================
// حداکثر تعداد درخواست در دقیقه برای endpoint هایی که بدون login هستند.
define('PUBLIC_RATE_LIMIT_PER_MIN', (int)env('PUBLIC_RATE_LIMIT_PER_MIN', 30));

// ==================== نشانهٔ محیط ====================
// production/development — برای انتخاب سطح لاگ‌گذاری و رفتار امنیتی.
define('APP_ENV', (string)env('APP_ENV', 'production'));

// ==================== محل ذخیرهٔ لاگ خطاها (اختیاری) ====================
// اگر خالی باشد، PHP از پیش‌فرض سرور استفاده می‌کند.
// توصیه: مسیری خارج از DocumentRoot، قابل نوشتن توسط کاربر PHP-FPM/Apache.
define('ERROR_LOG_PATH', (string)env('ERROR_LOG_PATH', ''));

// ==================== محدودیت آی‌پی برای پنل مدیریت (allowlist) ====================
// لیست آی‌پی‌های مجاز برای دسترسی به پنل مدیریت، با کاما جدا شوند.
// اگر خالی باشد، همهٔ آی‌پی‌ها مجازند (پیش‌فرض — برای پشتیبانی از شبکه‌های داخلی).
// مثال: '192.168.1.10,192.168.1.20'
define('ADMIN_IP_ALLOWLIST', (string)env('ADMIN_IP_ALLOWLIST', ''));

// ==================== محدودیت آی‌پی (denylist عمومی) ====================
// لیست آی‌پی‌های مسدودشده، با کاما جدا شوند.
define('IP_BLOCKLIST', (string)env('IP_BLOCKLIST', ''));
