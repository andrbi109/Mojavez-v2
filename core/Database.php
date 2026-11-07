<?php
/**
 * کلاس مدیریت پایگاه داده با الگوی Singleton و PDO
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        try {
            // charset=utf8mb4 در DSN، SET NAMES را به‌صورت خودکار اجرا می‌کند.
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // PDO::MYSQL_ATTR_INIT_COMMAND در PHP 8.5+ منسوخ (deprecated) شده است.
            // برای سازگاری با همهٔ نسخه‌ها (PHP 8.0 تا 8.5+) از پیکربندی DSN استفاده می‌کنیم
            // (charset=utf8mb4 در DSN فوق معادل SET NAMES utf8mb4 است).
            // اگر COLLATE خاصی لازم بود، پس از ساخت PDO با exec تنظیم می‌شود.

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // تنظیم collation پیش‌فرض اتصال (معادل MYSQL_ATTR_INIT_COMMAND قدیمی،
            // اما بدون استفاده از constant منسوخ‌شده)
            $this->pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

            // تنظیم timezone نشست MySQL بر اساس timezone سرور (برای TIMESTAMPهای صحیح)
            $this->pdo->exec("SET time_zone = '+00:00'");
        } catch (PDOException $e) {
            die('خطا در اتصال به پایگاه داده: ' . $e->getMessage());
        }
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

    // جلوگیری از کپی شدن نمونه
    private function __clone() {}
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }
}