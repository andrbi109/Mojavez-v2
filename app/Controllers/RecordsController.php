<?php
/**
 * کنترلر مشاهدهٔ سوابق متقاضی (فاز ۸ نقشهٔ راه)
 * =====================================================================
 * متقاضی بدون حساب کاربری و فقط با تطبیق شش عامل هویتی
 * (کد ملی + نام + نام خانوادگی + نام پدر + موبایل + تاریخ تولد)
 * فهرست همهٔ پرونده‌ها/مجوزهای خود (جاری، صادرشده، باطل و منقضی) را می‌بیند.
 *
 * محافظت‌ها:
 *  - محدودسازی نرخ نشست‌محور (۳ تلاش در ۱۰ دقیقه)
 *  - قفل تصاعدی مبتنی بر دیتابیس روی کد ملی (۵ شکست در ۱۰ دقیقه → ۳۰ دقیقه قفل)
 *  - پاسخ خطای یکسان (نشت وجود/عدم وجود رکورد ندارد)
 *  - لاگ امنیتی identity_checks با IP
 */
class RecordsController extends Controller
{
    private Person $personModel;
    private Applicant $applicantModel;

    public function __construct()
    {
        $this->personModel = new Person();
        $this->applicantModel = new Applicant();
    }

    /**
     * فرم احراز هویت
     */
    public function form(): void
    {
        $this->render('records', [
            'title' => 'مشاهدهٔ سوابق',
            'results' => Session::get('records_results'),
            'personName' => Session::get('records_name'),
            'error' => Session::get('records_error'),
            'old' => Session::get('records_form', []),
        ]);
        Session::remove('records_results');
        Session::remove('records_name');
        Session::remove('records_error');
        Session::remove('records_form');
    }

    /**
     * بررسی تطبیق و نمایش سوابق
     */
    public function verify(): void
    {
        if (!$this->isPost()) $this->redirect('/my-records');
        $this->verifyCsrf();

        // محدودسازی نرخ نشست‌محور
        if (Session::isLimited('records_verify', 3, 600)) {
            Session::set('records_error', 'تلاش‌های ناموفق بیش از حد مجاز است. لطفاً ۱۰ دقیقه دیگر دوباره تلاش کنید.');
            $this->redirect('/my-records');
        }

        $data = $this->allInputs();
        $nc = trim((string)($data['national_code'] ?? ''));
        $fn = trim((string)($data['first_name'] ?? ''));
        $ln = trim((string)($data['last_name'] ?? ''));
        $fa = trim((string)($data['father_name'] ?? ''));
        $mo = trim((string)($data['mobile'] ?? ''));

        // تاریخ تولد شمسی → میلادی
        $birth = '';
        if (!empty($data['birth_year']) && !empty($data['birth_month']) && !empty($data['birth_day'])) {
            $jy = (int)$data['birth_year']; $jm = (int)$data['birth_month']; $jd = (int)$data['birth_day'];
            if (is_valid_persian_date($jy, $jm, $jd)) {
                $birth = persian_to_gregorian($jy, $jm, $jd);
            }
        }

        // فرم ناقص → همان پیام یکسان
        if ($nc === '' || $fn === '' || $ln === '' || $fa === '' || $mo === '' || $birth === '') {
            Session::set('records_error', 'همهٔ شش فیلد هویتی را به‌طور کامل وارد کنید.');
            Session::set('records_form', ['national_code' => $nc, 'first_name' => $fn, 'last_name' => $ln, 'father_name' => $fa, 'mobile' => $mo]);
            $this->redirect('/my-records');
        }

        // قفل مبتنی بر دیتابیس روی کد ملی
        if ($this->personModel->recentFailedChecks($nc, 10) >= 5) {
            $this->personModel->logIdentityCheck($nc, false);
            Session::set('records_error', 'به دلیل تلاش‌های ناموفق متعدد، این کد ملی موقتاً قفل است. لطفاً ۳۰ دقیقه دیگر تلاش کنید.');
            $this->redirect('/my-records');
        }

        // تطبیق شش‌عاملی با آخرین دادهٔ رسمی پرونده‌ها
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT person_id FROM applicants
             WHERE national_code = :nc AND first_name = :fn AND last_name = :ln
               AND father_name = :fa AND mobile = :mo AND birth_date = :bd
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['nc' => $nc, 'fn' => $fn, 'ln' => $ln, 'fa' => $fa, 'mo' => $mo, 'bd' => $birth]);
        $row = $stmt->fetch();

        if (!$row || empty($row['person_id'])) {
            Session::hit('records_verify');
            $this->personModel->logIdentityCheck($nc, false);
            // پاسخ یکسان — وجود/عدم وجود رکورد نشت نمی‌کند
            Session::set('records_error', 'اطلاعات واردشده با سوابق سامانه تطبیق نیافت. لطفاً دقت کنید همهٔ موارد دقیقاً مطابق ثبت‌نام وارد شوند.');
            Session::set('records_form', ['national_code' => $nc, 'first_name' => $fn, 'last_name' => $ln, 'father_name' => $fa, 'mobile' => $mo]);
            $this->redirect('/my-records');
        }

        // موفق
        Session::resetRate('records_verify');
        $this->personModel->logIdentityCheck($nc, true);

        $person = $this->personModel->findById((int)$row['person_id']);
        $apps = $this->personModel->applications((int)$row['person_id']);

        Session::set('records_results', $apps);
        Session::set('records_name', trim(($person['first_name'] ?? $fn) . ' ' . ($person['last_name'] ?? $ln)));
        $this->redirect('/my-records');
    }
}
