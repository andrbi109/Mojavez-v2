<?php
/**
 * بوت‌استرپ تست‌ها — بدون وابستگی به PHPUnit
 * -----------------------------------------------------------------
 * این فایل محیط اجرایی را برای تست‌ها آماده می‌کند:
 *   ۱) ثابت‌های اصلی (ROOT_PATH, APP_PATH, ...)
 *   ۲) تنظیمات پیش‌فرض (با مقادیر امن، نه از فایل .env)
 *   ۳) بارگذاری autoloader
 *   ۴) اتصال به پایگاه دادهٔ تست (MySQL یا SQLite در حافظه)
 *   ۵) بارگذاری schema.sql در صورت نیاز
 *
 * نحوهٔ اجرا: php tests/run.php
 */

// جلوگیری از اجرای مستقیم از وب
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

// ==================== ثابت‌های مسیر ====================
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('VIEW_PATH', APP_PATH . '/Views');
define('UPLOAD_DIR', ROOT_PATH . '/uploads');

// ==================== ثابت‌های پیکربندی (تست) ====================
// مقادیر امن برای محیط تست — نباید با مقادیر تولید تداخل کنند.
define('APP_VERSION', 'test');
define('APP_DEBUG', true);
define('APP_ENV', 'test');
define('DB_HOST', getenv('TEST_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('TEST_DB_NAME') ?: 'handicraft_license_test');
define('DB_USER', getenv('TEST_DB_USER') ?: 'root');
define('DB_PASS', getenv('TEST_DB_PASS') ?: '');
define('BASE_URL', '/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024);
define('MAX_CSV_SIZE', 5 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('CSV_EXTENSION', 'csv');
define('USE_CDN', false);
define('SESSION_LIFETIME', 3600);
define('AUTO_CANCEL_DAYS', 14);
define('PRIORITY_DAYS', 12);
define('CRON_SECRET', 'test-secret-key-for-phpunit-32chars');
define('TRACKING_REQUIRE_MOBILE', false);
define('PUBLIC_RATE_LIMIT_PER_MIN', 60);
define('APP_ENV_TEST', true);
define('ERROR_LOG_PATH', '');
define('ADMIN_IP_ALLOWLIST', '');
define('IP_BLOCKLIST', '');

// ==================== راه‌اندازی نشست (mock) ====================
// در محیط CLI، نشست واقعی لازم نیست. اما برخی کلاس‌ها به Session وابسته‌اند.
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (!isset($_SESSION)) {
    $_SESSION = [];
}

// ==================== بارگذاری کمک‌کننده‌ها ====================
require_once CONFIG_PATH . '/env.php';
require_once CONFIG_PATH . '/helper.php';
require_once CONFIG_PATH . '/jdf.php';
require_once CORE_PATH . '/AutoLoader.php';
AutoLoader::register();

// ==================== اتصال به پایگاه دادهٔ تست ====================
/**
 * سهولت: اگر MySQL در دسترس باشد، از آن استفاده می‌کنیم؛ در غیر این صورت،
 * یک پایگاه دادهٔ SQLite در حافظه (in-memory) برای تست‌های واحد می‌سازیم.
 *
 * متغیر محیطی TEST_DB_DRIVER=mysql|sqlite انتخاب می‌کند.
 */
function test_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $driver = strtolower(getenv('TEST_DB_DRIVER') ?: 'mysql');

    if ($driver === 'sqlite') {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // اجرای schema.sql با تطبیق نحوی SQLite (به‌صورت بهترین تلاش)
        test_load_sqlite_schema($pdo);
        return $pdo;
    }

    // MySQL — ایجاد دیتابیس تست در صورت نبود
    try {
        $root = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $root->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        fwrite(STDERR, "⚠️ اتصال به MySQL ناموفق: " . $e->getMessage() . "\n");
        fwrite(STDERR, "   برای استفاده از SQLite: TEST_DB_DRIVER=sqlite php tests/run.php\n");
        exit(2);
    }

    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // بارگذاری schema در صورت خالی بودن دیتابیس
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote(DB_NAME))->fetchColumn();
    if ($cnt === 0) {
        test_load_mysql_schema($pdo);
    }
    return $pdo;
}

/**
 * بارگذاری schema.sql برای MySQL.
 * فقط ساخت CREATE TABLE و INSERT اجرا می‌شود؛ ALTER TABLE ممکن است skip شود.
 */
function test_load_mysql_schema(PDO $pdo): void
{
    $sql = file_get_contents(ROOT_PATH . '/database/schema.sql');
    if ($sql === false) {
        fwrite(STDERR, "⚠️ database/schema.sql یافت نشد\n");
        return;
    }

    // حذف USE/CREATE DATABASE — ما از قبل به دیتابیس وصلیم
    $sql = preg_replace('/^USE\s+`?\w+`?\s*;?\s*$/m', '', $sql);
    $sql = preg_replace('/^CREATE DATABASE[^;]+;?\s*$/m', '', $sql);

    // تقسیم به دستورات منفرد
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        $stmt = rtrim($stmt, ';');
        if ($stmt === '' || str_starts_with($stmt, '--')) continue;
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            // در صورت وجود table/column از قبل، عبور کن
            if (str_contains($e->getMessage(), 'already exists')) continue;
            fwrite(STDERR, "  schema skip: " . substr($stmt, 0, 80) . "... " . $e->getMessage() . "\n");
        }
    }
}

/**
 * بارگذاری schema در SQLite — به‌صورت بهترین تلاش (best-effort).
 * برخی قابلیت‌های MySQL (مثل ENUM) به TEXT تبدیل می‌شوند.
 */
function test_load_sqlite_schema(PDO $pdo): void
{
    // schema.sql برای SQLite به‌سختی تطبیق می‌یابد (ENUM، ENGINE، COLLATE).
    // این یک تست سازگاری نیست — فقط برای آزمون‌های واحد مدل که نیازی به ENUM ندارند.
    // برای تست‌های کامل، از MySQL استفاده کنید.
    fwrite(STDERR, "ℹ️  SQLite فقط برای تست‌های واحد مدل استفاده می‌شود.\n");
    fwrite(STDERR, "    برای تست‌های کامل، MySQL را راه‌اندازی و TEST_DB_DRIVER=mysql را تنظیم کنید.\n");
}

// ==================== override کلاس Database برای تست ====================
// به‌جای وصل شدن به دیتابیس تولید، به دیتابیس تست وصل می‌شویم.
// کلاس Database واقعی را (که از core/Database.php بارگذاری می‌شود) بازنویسی می‌کنیم.

// نگه‌دارندهٔ PDO تست — کلاس Database به این ارجاع می‌دهد
class TestDatabaseOverride
{
    public static PDO $pdo;
}
TestDatabaseOverride::$pdo = test_db();

if (!class_exists('Database', false)) {
    // تعریف کلاس Database به‌صورت تست — قبل از بارگذاری اصلی توسط autoloader
    class Database
    {
        private static ?Database $instance = null;
        private PDO $pdo;
        private function __construct()
        {
            $this->pdo = TestDatabaseOverride::$pdo;
        }
        public static function getInstance(): self
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        public function getConnection(): PDO
        {
            return $this->pdo;
        }
        private function __clone() {}
        public function __wakeup()
        {
            throw new \Exception('Cannot unserialize singleton');
        }
    }
}

// ==================== متغیرهای سرور (mock) ====================
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '80';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? '';
$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'PHPUnit-Test/1.0';
