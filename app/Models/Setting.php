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
     */
    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE setting_value = :v2"
        );
        $stmt->execute(['k' => $key, 'v' => $value, 'v2' => $value]);
        self::$cache = null; // بی‌اعتبارسازی کش
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
        ];
    }
}
