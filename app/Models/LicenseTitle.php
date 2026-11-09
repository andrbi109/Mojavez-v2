<?php
/**
 * مدل رشته‌های مجوز
 * -------------------------------------------------
 * فهرست رشته‌ها از پنل مدیریت قابل افزودن/ویرایش/حذف/غیرفعال‌سازی است
 * و در فرم ثبت‌نام (مرحلهٔ ۲) به‌صورت گروه‌بندی‌شده نمایش داده می‌شود.
 *
 * هر رشته به یک سرویس خاص وصل می‌شود (service_type_id) تا هر سرویس
 * فهرست اختصاصی خود را داشته باشد — مشابه وضعیت‌ها در `service_types`.
 *   ۱ = صنایع دستی
 *   ۲ = مشاغل خانگی مستقل
 *   ۳ = پشتیبان
 */
class LicenseTitle
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * رشته‌های فعال یک سرویس، گروه‌بندی‌شده بر اساس دسته (برای optgroup در فرم)
     *
     * @param  int|null $serviceTypeId شناسهٔ سرویس — اگر NULL باشد، همهٔ رشته‌ها (در همهٔ سرویس‌ها) بازگردانده می‌شوند.
     * @return array<string, array<string>> دسته → [عنوان‌ها]
     */
    public function groupedActiveByService(?int $serviceTypeId): array
    {
        if ($serviceTypeId !== null) {
            $stmt = $this->db->prepare(
                "SELECT category, title FROM license_titles
                 WHERE is_active = 1 AND service_type_id = :sid
                 ORDER BY sort_order, category, title"
            );
            $stmt->execute(['sid' => $serviceTypeId]);
            $rows = $stmt->fetchAll();
        } else {
            $rows = $this->db->query(
                "SELECT category, title FROM license_titles
                 WHERE is_active = 1 ORDER BY sort_order, category, title"
            )->fetchAll();
        }

        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['category']][] = $r['title'];
        }
        return $grouped;
    }

    /**
     * @deprecated استفاده از groupedActiveByService(serviceId) توصیه می‌شود.
     *             نگه‌داشته‌شده برای سازگاری با کدهایی که service را مشخص نمی‌کنند.
     *             همهٔ رشته‌های فعال (در همهٔ سرویس‌ها) را بازمی‌گرداند.
     */
    public function groupedActive(): array
    {
        return $this->groupedActiveByService(null);
    }

    /**
     * همهٔ رشته‌ها برای پنل مدیریت.
     * اگر serviceTypeId مشخص باشد، فقط همان سرویس فیلتر می‌شود.
     */
    public function getAll(?int $serviceTypeId = null): array
    {
        if ($serviceTypeId !== null) {
            $stmt = $this->db->prepare(
                "SELECT lt.*, st.title AS service_title
                 FROM license_titles lt
                 LEFT JOIN service_types st ON st.id = lt.service_type_id
                 WHERE lt.service_type_id = :sid
                 ORDER BY lt.sort_order, lt.category, lt.title"
            );
            $stmt->execute(['sid' => $serviceTypeId]);
            return $stmt->fetchAll();
        }
        return $this->db->query(
            "SELECT lt.*, st.title AS service_title
             FROM license_titles lt
             LEFT JOIN service_types st ON st.id = lt.service_type_id
             ORDER BY lt.service_type_id, lt.sort_order, lt.category, lt.title"
        )->fetchAll();
    }

    /**
     * فهرست دسته‌های موجود برای یک سرویس (برای dropdown فرم مدیریت)
     */
    public function getCategories(?int $serviceTypeId = null): array
    {
        if ($serviceTypeId !== null) {
            $stmt = $this->db->prepare(
                "SELECT category FROM license_titles
                 WHERE service_type_id = :sid
                 GROUP BY category
                 ORDER BY MIN(sort_order)"
            );
            $stmt->execute(['sid' => $serviceTypeId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        return $this->db->query(
            "SELECT category FROM license_titles
             GROUP BY category ORDER BY MIN(sort_order)"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT lt.*, st.title AS service_title
             FROM license_titles lt
             LEFT JOIN service_types st ON st.id = lt.service_type_id
             WHERE lt.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * بررسی یکتایی عنوان در دامنهٔ یک سرویس (service_type_id, title)
     */
    public function isTitleUniqueInService(string $title, ?int $serviceTypeId, ?int $exceptId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM license_titles
                WHERE title = :title AND service_type_id <=> :sid";
        $params = ['title' => $title, 'sid' => $serviceTypeId];
        if ($exceptId !== null) {
            $sql .= " AND id != :except_id";
            $params['except_id'] = $exceptId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() === 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO license_titles (service_type_id, category, title, is_active, sort_order)
             VALUES (:service_type_id, :category, :title, :is_active, :sort_order)"
        );
        $stmt->execute([
            'service_type_id' => !empty($data['service_type_id']) ? (int)$data['service_type_id'] : null,
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
            "UPDATE license_titles SET service_type_id = :service_type_id,
                    category = :category, title = :title,
                    is_active = :is_active, sort_order = :sort_order
             WHERE id = :id"
        );
        $stmt->execute([
            'service_type_id' => !empty($data['service_type_id']) ? (int)$data['service_type_id'] : null,
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
