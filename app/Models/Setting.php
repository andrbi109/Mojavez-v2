<?php
/**
 * مدل تنظیمات سامانه (کلید/مقدار)
 * ------------------------------------------
 * مقادیر از پنل مدیریت قابل تغییرند: هویت سامانه، آیدی شبکه‌های اجتماعی،
 * قالب پیام اطلاع‌رسانی متقاضی و محتوای صفحهٔ «دربارهٔ ما».
 */
class Setting
{
    private PDO $db;

    /** کش درون‌درخواستی تا برای هر کلید کوئری تکراری نزنیم */
    private static ?array $cache = null;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * دریافت همهٔ تنظیمات (یک کوئری + کش)
     */
    public function all(): array
    {
        if (self::$cache === null) {
            self::$cache = $this->db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        return self::$cache;
    }

    /**
     * دریافت یک تنظیم با مقدار پیش‌فرض
     */
    public function get(string $key, string $default = ''): string
    {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    /**
     * ذخیرهٔ یک تنظیم (درج یا به‌روزرسانی)
     *
     * نکتهٔ فنی: MariaDB با EMULATE_PREPARES=false ممکن است با پارامترهای
     * نام‌گذاری‌شدهٔ تکراری مشکل داشته باشد. برای اطمینان، از دو پارامتر مجزا
     * استفاده می‌کنیم و در صورت خطا، لاگ می‌کنیم.
     */
    public function set(string $key, string $value): void
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
                 ON DUPLICATE KEY UPDATE setting_value = :v2"
            );
            $stmt->execute(['k' => $key, 'v' => $value, 'v2' => $value]);
            self::$cache = null; // بی‌اعتبارسازی کش
        } catch (\PDOException $e) {
            // لاگ خطا برای دیباگ
            error_log("Setting::set({$key}) failed: " . $e->getMessage());
            throw $e; // بازپرتاب برای مدیریت در کنترلر
        }
    }

    /**
     * ذخیرهٔ دسته‌ای چند تنظیم
     */
    public function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            $this->set((string)$key, (string)$value);
        }
    }

    /**
     * کلیدهای مجازی که پنل تنظیمات مدیریت می‌کند (فهرست سفید)
     */
    public static function editableKeys(): array
    {
        return [
            'site_name', 'owner_name', 'owner_username',
            'social_eitaa', 'social_rubika', 'social_bale',
            'success_message_template', 'about_title', 'about_content',
            'registration_fee_amount', 'license_reissue_fee_amount',
            // کلیدهای نرخ‌نامهٔ چندگانه (مدیریت از /admin/fee-tariff)
            'fee_tariff_active', 'fee_tariff_valid_until', 'fee_default_amount',
            'fee_rural_auto_eligible', 'fee_modal_enabled', 'fee_modal_dismissable',
            'fee_fallback_group_code', 'fee_min_amount_percent',
            'fee_max_discount_without_reason', 'fee_max_discount_with_reason',
        ];
    }
}
