<?php
/**
 * مدل اشخاص (فاز ۷ نقشهٔ راه)
 * --------------------------------------------------
 * هر «شخص» (کد ملی یکتا) می‌تواند چندین «پرونده/مجوز» داشته باشد:
 *  - درخواست‌های همزمان در رشته‌های متفاوت
 *  - مجوزهای باطل/منقضی‌شده که دسترسی‌شان حفظ می‌شود
 *  - زنجیرهٔ تمدید/ارتقا از طریق parent_application_id
 */
class Person
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByNationalCode(string $nationalCode): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM persons WHERE national_code = :nc LIMIT 1");
        $stmt->execute(['nc' => $nationalCode]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM persons WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * یافتن یا ساخت شخص بر پایهٔ کد ملی — در پایان ثبت‌نام صدا زده می‌شود
     */
    public function findOrCreate(array $data): int
    {
        $isLegal = ($data['person_type'] ?? 'حقیقی') === 'حقوقی';
        $existing = $this->findByNationalCode((string)$data['national_code']);
        if ($existing) {
            // به‌روزرسانی اطلاعات با تازه‌ترین مقادیر
            $stmt = $this->db->prepare(
                "UPDATE persons SET first_name=:fn, last_name=:ln, father_name=:fa,
                        birth_date=:bd, mobile=:mo, postal_code=:pc,
                        person_type=:pt, company_name=:cn, company_national_id=:cni,
                        reg_number=:rn, reg_date=:rd, company_type=:ct, economic_code=:ec
                 WHERE id=:id"
            );
            $stmt->execute([
                'fn' => $data['first_name'], 'ln' => $data['last_name'],
                'fa' => $data['father_name'] ?? null, 'bd' => $data['birth_date'] ?? null,
                'mo' => $data['mobile'], 'pc' => $data['postal_code'] ?? null,
                'pt' => $isLegal ? 'حقوقی' : ($existing['person_type'] ?? 'حقیقی'),
                'cn' => $data['company_name'] ?? $existing['company_name'],
                'cni' => $data['company_national_id'] ?? $existing['company_national_id'],
                'rn' => $data['reg_number'] ?? $existing['reg_number'],
                'rd' => $data['reg_date'] ?? $existing['reg_date'],
                'ct' => $data['company_type'] ?? $existing['company_type'],
                'ec' => $data['economic_code'] ?? $existing['economic_code'],
                'id' => (int)$existing['id'],
            ]);
            return (int)$existing['id'];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO persons (national_code, first_name, last_name, father_name, birth_date, mobile, postal_code,
                                   person_type, company_name, company_national_id, reg_number, reg_date, company_type, economic_code)
             VALUES (:nc, :fn, :ln, :fa, :bd, :mo, :pc, :pt, :cn, :cni, :rn, :rd, :ct, :ec)"
        );
        $stmt->execute([
            'nc' => $data['national_code'],
            'fn' => $data['first_name'], 'ln' => $data['last_name'],
            'fa' => $data['father_name'] ?? null, 'bd' => $data['birth_date'] ?? null,
            'mo' => $data['mobile'], 'pc' => $data['postal_code'] ?? null,
            'pt' => $isLegal ? 'حقوقی' : 'حقیقی',
            'cn' => $data['company_name'] ?? null,
            'cni' => $data['company_national_id'] ?? null,
            'rn' => $data['reg_number'] ?? null,
            'rd' => $data['reg_date'] ?? null,
            'ct' => $data['company_type'] ?? null,
            'ec' => $data['economic_code'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * ایجاد شخص جدید (نسخهٔ ساده برای ثبت‌نام کاربر).
     * mobile_2 و mobile_3 اختیاری هستند و در صورت عدم ارائه NULL ذخیره می‌شوند.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO persons (national_code, first_name, last_name, mobile, mobile_2, mobile_3, postal_code, person_type)
             VALUES (:nc, :fn, :ln, :mo, :mo2, :mo3, :pc, 'حقیقی')"
        );
        $stmt->execute([
            'nc' => $data['national_code'],
            'fn' => $data['first_name'] ?? '',
            'ln' => $data['last_name'] ?? '',
            'mo' => $data['mobile'] ?? '',
            'mo2' => $data['mobile_2'] ?? null,
            'mo3' => $data['mobile_3'] ?? null,
            'pc' => $data['postal_code'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * افزودن شماره موبایل دوم یا سوم به شخص موجود.
     * اگر آن اسلات پر باشد، یکی دیگر امتحان می‌شود.
     *
     * @param int    $personId شناسهٔ شخص
     * @param string $mobile   موبایل جدید (نرمال‌شده)
     * @return string اسلاتی که در آن ذخیره شد: 'mobile_2'، 'mobile_3' یا '' (هیچ‌کدام در دسترس نبود)
     */
    public function addSecondaryMobile(int $personId, string $mobile): string
    {
        $person = $this->findById($personId);
        if (!$person) return '';

        // اگر موبایل ورودی قبلاً در یکی از فیلدها ثبت شده، چیزی اضافه نکن
        if (!empty($person['mobile'])   && $person['mobile']   === $mobile) return '';
        if (!empty($person['mobile_2']) && $person['mobile_2'] === $mobile) return 'mobile_2';
        if (!empty($person['mobile_3']) && $person['mobile_3'] === $mobile) return 'mobile_3';

        if (empty($person['mobile_2'])) {
            $stmt = $this->db->prepare("UPDATE persons SET mobile_2 = :m WHERE id = :id");
            $stmt->execute(['m' => $mobile, 'id' => $personId]);
            return 'mobile_2';
        }
        if (empty($person['mobile_3'])) {
            $stmt = $this->db->prepare("UPDATE persons SET mobile_3 = :m WHERE id = :id");
            $stmt->execute(['m' => $mobile, 'id' => $personId]);
            return 'mobile_3';
        }
        return '';
    }

    /**
     * به‌روزرسانی موبایل شخص.
     */
    public function updateMobile(int $personId, string $mobile): void
    {
        $stmt = $this->db->prepare("UPDATE persons SET mobile = :mo WHERE id = :id");
        $stmt->execute(['mo' => $mobile, 'id' => $personId]);
    }

    /**
     * اتصال پرونده به شخص
     */
    public function linkApplication(int $applicantId, int $personId): void
    {
        $this->db->prepare("UPDATE applicants SET person_id = :pid WHERE id = :aid")
            ->execute(['pid' => $personId, 'aid' => $applicantId]);
    }

    /**
     * همهٔ پرونده‌های یک شخص (با اطلاعات شغلی) — مرتب از جدید به قدیم
     */
    public function applications(int $personId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, j.license_title, p.province, p.city
             FROM applicants a
             LEFT JOIN job_details j ON j.applicant_id = a.id
             LEFT JOIN property_details p ON p.applicant_id = a.id
             WHERE a.person_id = :pid
             ORDER BY a.id DESC"
        );
        $stmt->execute(['pid' => $personId]);
        return $stmt->fetchAll();
    }

    /**
     * آیا این موبایل متعلق به شخصی «غیر از» کد ملی داده‌شده است؟
     *
     * یکتایی موبایل در سطح شخص بررسی می‌شود (نه applicants). این یعنی:
     *  - persons.mobile
     *  - persons.mobile_2
     *  - persons.mobile_3
     *
     * نکتهٔ مهم: قبلاً این متد روی جدول applicants کوئری می‌زد که باگ داشت —
     * شماره‌های ذخیره‌شده در mobile_2/mobile_3 persons را نمی‌دید.
     *
     * @param string $mobile        موبایل نرمال‌شده (۱۱ رقم، با ۰۹ شروع)
     * @param string $nationalCode کد ملی ۱۰ رقمی شخص فعلی
     * @return bool                 true = موبایل متعلق به شخص دیگری است
     */
    public function mobileBelongsToOtherPerson(string $mobile, string $nationalCode): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM persons
             WHERE (mobile = :m1 OR mobile_2 = :m2 OR mobile_3 = :m3)
               AND national_code != :nc"
        );
        $stmt->execute([
            'm1' => $mobile,
            'm2' => $mobile,
            'm3' => $mobile,
            'nc' => $nationalCode,
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * همگام‌سازی اطلاعات شخص پس از ویرایش/بازبینی پرونده
     */
    public function syncFromApplicant(int $applicantId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE persons p
             JOIN applicants a ON a.person_id = p.id
             SET p.first_name = a.first_name, p.last_name = a.last_name,
                 p.father_name = a.father_name, p.birth_date = a.birth_date,
                 p.mobile = a.mobile, p.postal_code = a.postal_code
             WHERE a.id = :aid"
        );
        $stmt->execute(['aid' => $applicantId]);
    }

    /**
     * به‌روزرسانی وضعیت مجوز (جاری/صادر شده/باطل/منقضی) + تاریخ اعتبار
     */
    public function updateLicenseState(int $applicantId, string $state, ?string $validUntil = null): void
    {
        $stmt = $this->db->prepare(
            "UPDATE applicants SET license_state = :st, license_valid_until = :vu, updated_at = NOW() WHERE id = :id"
        );
        $stmt->execute(['st' => $state, 'vu' => $validUntil, 'id' => $applicantId]);
    }

    /**
     * ثبت تلاش احراز هویت (فاز ۸)
     */
    public function logIdentityCheck(string $nationalCode, bool $success): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->db->prepare(
            "INSERT INTO identity_checks (national_code, ip, success) VALUES (:nc, :ip, :ok)"
        )->execute(['nc' => $nationalCode, 'ip' => $ip, 'ok' => $success ? 1 : 0]);
    }

    /**
     * شمارش تلاش‌های ناموفق اخیر یک کد ملی (قفل تصاعدی)
     */
    public function recentFailedChecks(string $nationalCode, int $minutes = 10): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM identity_checks
             WHERE national_code = :nc AND success = 0
               AND created_at > DATE_SUB(NOW(), INTERVAL :m MINUTE)"
        );
        $stmt->execute(['nc' => $nationalCode, 'm' => $minutes]);
        return (int)$stmt->fetchColumn();
    }
}
