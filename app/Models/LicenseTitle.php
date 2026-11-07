<?php
/**
 * مدل رشته‌های مجوز صنایع دستی
 * -------------------------------------------------
 * فهرست رشته‌ها از پنل مدیریت قابل افزودن/ویرایش/حذف/غیرفعال‌سازی است
 * و در فرم ثبت‌نام (مرحلهٔ ۲) به‌صورت گروه‌بندی‌شده نمایش داده می‌شود.
 */
class LicenseTitle
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * رشته‌های فعال، گروه‌بندی‌شده بر اساس دسته (برای optgroup در فرم)
     * @return array<string, array<string>> دسته → [عنوان‌ها]
     */
    public function groupedActive(): array
    {
        $rows = $this->db->query(
            "SELECT category, title FROM license_titles
             WHERE is_active = 1 ORDER BY sort_order, category, title"
        )->fetchAll();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['category']][] = $r['title'];
        }
        return $grouped;
    }

    /**
     * همهٔ رشته‌ها برای پنل مدیریت
     */
    public function getAll(): array
    {
        return $this->db->query(
            "SELECT * FROM license_titles ORDER BY sort_order, category, title"
        )->fetchAll();
    }

    /**
     * فهرست دسته‌های موجود (برای dropdown فرم مدیریت)
     */
    public function getCategories(): array
    {
        return $this->db->query(
            "SELECT DISTINCT category FROM license_titles ORDER BY MIN(sort_order)"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM license_titles WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO license_titles (category, title, is_active, sort_order)
             VALUES (:category, :title, :is_active, :sort_order)"
        );
        $stmt->execute([
            'category' => $data['category'],
            'title' => $data['title'],
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => (int)($data['sort_order'] ?? 100),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE license_titles SET category = :category, title = :title,
                    is_active = :is_active, sort_order = :sort_order
             WHERE id = :id"
        );
        $stmt->execute([
            'category' => $data['category'],
            'title' => $data['title'],
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => (int)($data['sort_order'] ?? 100),
            'id' => $id,
        ]);
    }

    /**
     * حذف رشته — اگر در پرونده‌ای استفاده شده باشد، فقط غیرفعال می‌شود
     * @return bool آیا واقعاً حذف شد؟
     */
    public function delete(int $id): bool
    {
        $inUse = $this->db->prepare(
            "SELECT COUNT(*) FROM license_titles lt
             JOIN job_details j ON j.license_title = lt.title
             WHERE lt.id = :id"
        );
        $inUse->execute(['id' => $id]);
        if ((int)$inUse->fetchColumn() > 0) {
            // حفظ یکپارچگی پرونده‌های موجود → غیرفعال‌سازی به‌جای حذف
            $this->db->prepare("UPDATE license_titles SET is_active = 0 WHERE id = :id")->execute(['id' => $id]);
            return false;
        }
        $this->db->prepare("DELETE FROM license_titles WHERE id = :id")->execute(['id' => $id]);
        return true;
    }
}
