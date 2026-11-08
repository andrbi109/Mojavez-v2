<?php
/**
 * مدل حساب کاربری متقاضی (UserAccount)
 * ---------------------------------------------------
 * مدل فروشگاهی: کاربران آزادانه ثبت‌نام می‌کنند.
 * شناسهٔ اصلی ورود = موبایل (همراه با رمز عبور bcrypt).
 * هر حساب به یک Person متصل است (لنگر هویتی با کد ملی یکتا).
 */
class UserAccount
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ua.*, p.national_code, p.first_name, p.last_name, p.father_name,
                    p.birth_date, p.postal_code
             FROM user_accounts ua
             JOIN persons p ON p.id = ua.person_id
             WHERE ua.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByMobile(string $mobile): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ua.*, p.national_code, p.first_name, p.last_name
             FROM user_accounts ua
             JOIN persons p ON p.id = ua.person_id
             WHERE ua.mobile = :mobile LIMIT 1"
        );
        $stmt->execute(['mobile' => $mobile]);
        return $stmt->fetch() ?: null;
    }

    public function findByPersonId(int $personId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM user_accounts WHERE person_id = :pid LIMIT 1"
        );
        $stmt->execute(['pid' => $personId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * ایجاد حساب کاربری جدید.
     * اگر Person با این کد ملی وجود داشته باشد، به آن متصل می‌شود.
     * در غیر این صورت، Person جدید ایجاد می‌شود.
     */
    public function create(array $data): int
    {
        $personModel = new Person();
        $nationalCode = (string)$data['national_code'];
        $person = $personModel->findByNationalCode($nationalCode);

        if (!$person) {
            // ایجاد Person جدید
            $personId = $personModel->create([
                'national_code' => $nationalCode,
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? '',
                'mobile' => $data['mobile'],
            ]);
        } else {
            $personId = (int)$person['id'];
            // اگر موبایل Person خالی است، آن را به‌روزرسانی کن
            if (empty($person['mobile'])) {
                $personModel->updateMobile($personId, $data['mobile']);
            }
        }

        $stmt = $this->db->prepare(
            "INSERT INTO user_accounts
                (person_id, username, mobile, password, email, is_active, created_at)
             VALUES
                (:pid, :username, :mobile, :password, :email, 1, NOW())"
        );
        $stmt->execute([
            'pid' => $personId,
            'username' => $data['username'] ?? null,
            'mobile' => $data['mobile'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'email' => $data['email'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * تأیید رمز عبور با مقایسهٔ زمان‌ثابت.
     */
    public function verifyPassword(int $accountId, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT password FROM user_accounts WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $accountId]);
        $hash = $stmt->fetchColumn();
        if (!$hash) return false;
        return password_verify($password, $hash);
    }

    public function updatePassword(int $accountId, string $newPassword): void
    {
        $stmt = $this->db->prepare("UPDATE user_accounts SET password = :pwd, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['pwd' => password_hash($newPassword, PASSWORD_BCRYPT), 'id' => $accountId]);
    }

    public function updateLastLogin(int $accountId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE user_accounts
             SET last_login_at = NOW(), failed_login_count = 0, locked_until = NULL
             WHERE id = :id"
        );
        $stmt->execute(['id' => $accountId]);
    }

    /**
     * ثبت تلاش ناموفق ورود و قفل کردن حساب در صورت لزوم.
     */
    public function recordFailedLogin(int $accountId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE user_accounts
             SET failed_login_count = failed_login_count + 1,
                 locked_until = CASE
                     WHEN failed_login_count + 1 >= 5 THEN DATE_ADD(NOW(), INTERVAL 5 MINUTE)
                     ELSE locked_until
                 END
             WHERE id = :id"
        );
        $stmt->execute(['id' => $accountId]);
    }

    public function isLocked(int $accountId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT locked_until FROM user_accounts WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $accountId]);
        $lockedUntil = $stmt->fetchColumn();
        if (!$lockedUntil) return false;
        return strtotime($lockedUntil) > time();
    }

    /**
     * دریافت تمام درخواست‌های (پرونده‌های) یک کاربر.
     */
    public function getApplications(int $accountId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, st.title AS service_title
             FROM applicants a
             LEFT JOIN service_types st ON st.id = a.service_type_id
             WHERE a.user_account_id = :uid
             ORDER BY a.id DESC"
        );
        $stmt->execute(['uid' => $accountId]);
        return $stmt->fetchAll();
    }

    /**
     * دریافت تمام پرداخت‌های یک کاربر.
     * نکته: از طریق applicants.user_account_id فیلتر می‌کند، نه payments.user_account_id.
     * این کار پرداخت‌های قدیمی (که user_account_id در payments ست نشده) را هم پوشش می‌دهد.
     */
    public function getPayments(int $accountId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, a.tracking_code, c.title AS channel_title
             FROM payments p
             JOIN applicants a ON a.id = p.applicant_id
             LEFT JOIN payment_channels c ON c.id = p.payment_channel_id
             WHERE a.user_account_id = :uid
             ORDER BY p.id DESC"
        );
        $stmt->execute(['uid' => $accountId]);
        return $stmt->fetchAll();
    }

    /**
     * اتصال یک پرونده (applicant) به حساب کاربری.
     */
    public function linkApplication(int $accountId, int $applicantId): void
    {
        $stmt = $this->db->prepare("UPDATE applicants SET user_account_id = :uid WHERE id = :aid");
        $stmt->execute(['uid' => $accountId, 'aid' => $applicantId]);
    }

    /**
     * اتصال یک پرداخت به حساب کاربری.
     */
    public function linkPayment(int $accountId, int $paymentId): void
    {
        $stmt = $this->db->prepare("UPDATE payments SET user_account_id = :uid WHERE id = :pid");
        $stmt->execute(['uid' => $accountId, 'pid' => $paymentId]);
    }
}
