<?php
/**
 * کنترلر ثبت‌نام چندمرحله‌ای — موتور ویزارد پویا (فاز ۱ نقشهٔ راه)
 * ======================================================================
 * - مراحل از جدول wizard_steps خوانده می‌شوند (سیستمی + سفارشی مدیر)
 * - مسیرها موقعیت‌محورند: /register/step/{n} (n = جایگاه ۱-مبنای مرحلهٔ فعال)
 * - مسیرهای قدیمی /register/step1..4 به‌عنوان نام مستعار باقی‌اند
 * - مرحلهٔ «بارگذاری مدارک» همیشه آخرین است و نهایی‌سازی همان‌جا انجام می‌شود
 * - دادهٔ هر مرحله در سشن: کلیدهای step1..step3 (سیستمی) و wiz_{id} (سفارشی)
 */
class RegistrationController extends Controller
{
    private Applicant $applicantModel;
    private JobDetail $jobDetailModel;
    private PropertyDetail $propertyDetailModel;
    private Document $documentModel;
    private StatusLog $statusLogModel;
    private FormField $fieldModel;
    private ApplicantFieldValue $fieldValueModel;
    private WizardStep $stepModel;

    public function __construct()
    {
        $this->applicantModel = new Applicant();
        $this->jobDetailModel = new JobDetail();
        $this->propertyDetailModel = new PropertyDetail();
        $this->documentModel = new Document();
        $this->statusLogModel = new StatusLog();
        $this->fieldModel = new FormField();
        $this->fieldValueModel = new ApplicantFieldValue();
        $this->stepModel = new WizardStep();
    }

    /* ==================== موتور ویزارد ==================== */

    private function orderedSteps(): array
    {
        return $this->stepModel->orderedActive($this->selectedServiceId());
    }

    /** شناسهٔ سرویس انتخاب‌شده در سشن (فاز ۹) */
    private function selectedServiceId(): ?int
    {
        $sid = Session::getStep('service_id');
        return $sid ? (int)$sid : null;
    }

    /** کلید سشن دادهٔ یک مرحله */
    private function sessionKey(array $step): string
    {
        return $step['view_key'] ?: ('wiz_' . (int)$step['id']);
    }

    /** آیا مراحل پیشین کامل شده‌اند؟ */
    private function prerequisitesMet(array $steps, int $position): bool
    {
        for ($i = 1; $i < $position; $i++) {
            if (empty(Session::getStep($this->sessionKey($steps[$i - 1])))) {
                return false;
            }
        }
        return true;
    }

    /** فیلدهای سفارشی فعال یک مرحله (بر پایهٔ شناسهٔ مرحله) */
    private function customFieldsForStepId(int $stepId): array
    {
        return array_values(array_filter(
            $this->fieldModel->activeByStepId($stepId, $this->selectedServiceId()),
            fn($f) => (int)$f['is_system'] === 0
        ));
    }

    /**
     * ورودی ویزارد — هدایت به اولین مرحله
     */
    public function index(): void
    {
        // انتخاب نوع خدمت (فاز ۹) — اگر بیش از یک سرویس فعال باشد
        $services = (new ServiceType())->getActive();

        // درخواست صریح «تغییر نوع خدمت» از کاربر (لینک در step1.php)
        $changeRequested = isset($_GET['change']) && $_GET['change'] === 'service';
        if ($changeRequested && count($services) > 1) {
            // پاک‌سازی انتخاب قبلی و داده‌های ویزارد برای شروع پاک
            $this->clearWizardData();
            unset($_SESSION['registration']['service_id']);
            $this->render('registration/select_service', [
                'title' => 'انتخاب نوع خدمت',
                'services' => $services,
            ]);
            return;
        }

        if (count($services) > 1 && $this->selectedServiceId() === null) {
            $this->render('registration/select_service', [
                'title' => 'انتخاب نوع خدمت',
                'services' => $services,
            ]);
            return;
        }
        // اگر فقط یک سرویس فعال است، خودکار انتخاب شود
        if (count($services) === 1 && $this->selectedServiceId() === null) {
            Session::setStep('service_id', (int)$services[0]['id']);
        }

        $steps = $this->orderedSteps();
        if (empty($steps)) {
            $this->redirect('/');
        }
        $this->renderStep(1);
    }

    /**
     * انتخاب سرویس و شروع ویزارد
     */
    public function selectService(int|string $id): void
    {
        $service = (new ServiceType())->findById((int)$id);
        if (!$service || !(int)$service['is_active']) {
            $this->redirect('/register');
        }
        Session::setStep('service_id', (int)$service['id']);
        // پاک‌سازی دادهٔ مراحل پیشین برای شروع پاک با سرویس جدید
        // داده‌ها در $_SESSION['registration'][$key] ذخیره می‌شوند
        // کلیدهای ممکن: step1..step4 (سیستمی) و wiz_{id} (سفارشی)
        $this->clearWizardData();
        $this->redirect('/register');
    }

    /**
     * پاک‌سازی کامل دادهٔ ویزارد (به‌جز service_id اگر نگه داشته شود)
     * هنگام تغییر سرویس یا شروع ثبت‌نام جدید فراخوانی می‌شود.
     */
    private function clearWizardData(bool $keepServiceId = false): void
    {
        if (!isset($_SESSION['registration']) || !is_array($_SESSION['registration'])) {
            return;
        }
        $preserve = $keepServiceId ? ['service_id'] : [];
        foreach (array_keys($_SESSION['registration']) as $key) {
            if (in_array($key, $preserve, true)) continue;
            // پاک‌سازی کلیدهای سیستمی (step1..step4) و سفارشی (wiz_*)
            if (preg_match('/^step[1-4]$/', $key) || str_starts_with($key, 'wiz_')) {
                unset($_SESSION['registration'][$key]);
            }
        }
    }

    /**
     * نام‌های مستعار قدیمی (سازگاری با مسیرهای step1..4)
     */
    public function step1(): void { $this->renderStepByKey('step1'); }
    public function step2(): void { $this->renderStepByKey('step2'); }
    public function step3(): void { $this->renderStepByKey('step3'); }
    public function step4(): void { $this->renderStepByKey('step4'); }
    public function processStep1(): void { $this->processStepByKey('step1'); }
    public function processStep2(): void { $this->processStepByKey('step2'); }
    public function processStep3(): void { $this->processStepByKey('step3'); }
    public function processStep4(): void { $this->processStepByKey('step4'); }

    private function renderStepByKey(string $key): void
    {
        $steps = $this->orderedSteps();
        foreach ($steps as $i => $st) {
            if ($st['view_key'] === $key) {
                $this->renderStep($i + 1);
                return;
            }
        }
        $this->redirect('/register');
    }

    private function processStepByKey(string $key): void
    {
        $steps = $this->orderedSteps();
        foreach ($steps as $i => $st) {
            if ($st['view_key'] === $key) {
                $this->processStep($i + 1);
                return;
            }
        }
        $this->redirect('/register');
    }

    /**
     * GET /register/step/{n}
     */
    public function stepByPosition(int|string $n): void
    {
        $this->renderStep((int)$n);
    }

    /**
     * POST /register/step/{n}
     */
    public function processByPosition(int|string $n): void
    {
        $this->processStep((int)$n);
    }

    private function renderStep(int $position): void
    {
        $steps = $this->orderedSteps();
        if ($position < 1 || $position > count($steps)) {
            $this->redirect('/register');
        }
        if (!$this->prerequisitesMet($steps, $position)) {
            $this->redirect('/register');
        }

        $step = $steps[$position - 1];
        $stepId = (int)$step['id'];
        $common = [
            'wizardSteps' => $steps,
            'stepPosition' => $position,
            'errors' => Session::get('errors', []),
        ];

        switch ($step['view_key']) {
            case 'step1':
                $this->render('registration/step1', $common + [
                    'title' => 'مرحلهٔ ' . $this->faNum($position) . ' — اطلاعات هویتی',
                    'stepData' => Session::getStep('step1', []),
                    'dynFields' => $this->customFieldsForStepId($stepId),
                    'sysActive' => $this->fieldModel->systemActiveMap(),
                    'currentStep' => $position,
                ]);
                break;
            case 'step2':
                $this->render('registration/step2', $common + [
                    'title' => 'مرحلهٔ ' . $this->faNum($position) . ' — اطلاعات شغلی',
                    'stepData' => Session::getStep('step2', []),
                    'dynFields' => $this->customFieldsForStepId($stepId),
                    'sysActive' => $this->fieldModel->systemActiveMap(),
                    'licenseGroups' => (new LicenseTitle())->groupedActive(),
                    'currentStep' => $position,
                ]);
                break;
            case 'step3':
                $this->render('registration/step3', $common + [
                    'title' => 'مرحلهٔ ' . $this->faNum($position) . ' — اطلاعات ملک و مالکیت',
                    'stepData' => Session::getStep('step3', []),
                    'dynFields' => $this->customFieldsForStepId($stepId),
                    'sysActive' => $this->fieldModel->systemActiveMap(),
                    'currentStep' => $position,
                ]);
                break;
            case 'step4':
                $step3Data = Session::getStep('step3', []);
                $this->render('registration/step4', $common + [
                    'title' => 'مرحلهٔ ' . $this->faNum($position) . ' — بارگذاری مدارک',
                    'currentStep' => $position,
                    'ownershipStatus' => $step3Data['ownership_status'] ?? '',
                    'dynFields' => $this->customFieldsForStepId($stepId),
                    'sysActive' => $this->fieldModel->systemActiveMap(),
                ]);
                break;
            default:
                // مرحلهٔ سفارشی — رندر عمومی بر پایهٔ فیلدهای پویا
                $this->render('registration/step_custom', $common + [
                    'title' => 'مرحلهٔ ' . $this->faNum($position) . ' — ' . $step['title'],
                    'wizardStep' => $step,
                    'stepData' => Session::getStep($this->sessionKey($step), []),
                    'dynFields' => $this->customFieldsForStepId($stepId),
                    'currentStep' => $position,
                ]);
        }
        Session::remove('errors');
    }

    private function processStep(int $position): void
    {
        if (!$this->isPost()) {
            $this->redirect('/register');
        }
        $this->verifyCsrf();

        $steps = $this->orderedSteps();
        if ($position < 1 || $position > count($steps)) {
            $this->redirect('/register');
        }
        if (!$this->prerequisitesMet($steps, $position)) {
            $this->redirect('/register');
        }

        $step = $steps[$position - 1];
        $isLast = ($position === count($steps));

        switch ($step['view_key']) {
            case 'step1': $this->handleStep1($steps, $position); break;
            case 'step2': $this->handleStep2($steps, $position); break;
            case 'step3': $this->handleStep3($steps, $position); break;
            case 'step4': $this->handleFinalize($steps, $position); break;
            default:
                if ($isLast) {
                    // دفاعی: مرحلهٔ سفارشی نمی‌تواند آخر باشد (مدارک همیشه آخر است)
                    $this->handleCustomStep($steps, $position, $step);
                } else {
                    $this->handleCustomStep($steps, $position, $step);
                }
        }
    }

    /** پیشروی به مرحلهٔ بعد یا صفحهٔ موفقیت */
    private function advance(array $steps, int $position): void
    {
        if ($position < count($steps)) {
            $this->redirect('/register/step/' . ($position + 1));
        }
        $this->redirect('/register');
    }

    private function fail(array $steps, int $position, array $errors, array $data): void
    {
        Session::set('errors', $errors);
        Session::setStep($this->sessionKey($steps[$position - 1]), $data);
        $this->redirect('/register/step/' . $position);
    }

    private function faNum(int $n): string
    {
        return strtr((string)$n, ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹']);
    }

    /* ==================== مراحل سیستمی ==================== */

    private function handleStep1(array $steps, int $position): void
    {
        $data = $this->allInputs();

        // محاسبهٔ تاریخ تولد پیش از اعتبارسنجی اصلی
        $birth_date = '';
        $birthError = null;
        if (empty($data['birth_year']) || empty($data['birth_month']) || empty($data['birth_day'])) {
            $birthError = 'required';
        } else {
            $jy = (int)$data['birth_year']; $jm = (int)$data['birth_month']; $jd = (int)$data['birth_day'];
            if (!is_valid_persian_date($jy, $jm, $jd)) {
                $birthError = 'invalid';
            } else {
                $birth_date = persian_to_gregorian($jy, $jm, $jd);
            }
        }
        $data['birth_date'] = $birth_date;

        // نوع متقاضی: حقیقی (پیش‌فرض) یا حقوقی (فاز ۱۰ نقشهٔ راه)
        $applicantType = ($data['applicant_type'] ?? 'حقیقی') === 'حقوقی' ? 'حقوقی' : 'حقیقی';
        $data['applicant_type'] = $applicantType;

        $validator = new Validator($data);
        $validator->required('first_name', $applicantType === 'حقوقی' ? 'نام نمایندهٔ مجاز' : 'نام')
            ->persianAlpha('first_name', 'نام')->maxLength('first_name', 50, 'نام')
            ->required('last_name', $applicantType === 'حقوقی' ? 'نام خانوادگی نماینده' : 'نام خانوادگی')
            ->persianAlpha('last_name', 'نام خانوادگی')->maxLength('last_name', 50, 'نام خانوادگی')
            ->required('national_code', $applicantType === 'حقوقی' ? 'کد ملی نماینده' : 'کد ملی')
            ->nationalCode('national_code', 'کد ملی')
            ->required('postal_code', 'کد پستی')->postalCode('postal_code', 'کد پستی')
            ->required('mobile', 'شماره تلفن همراه')->mobile('mobile', 'شماره تلفن همراه')
            ->required('father_name', 'نام پدر')->persianAlpha('father_name', 'نام پدر')->maxLength('father_name', 50, 'نام پدر')
            ->required('marital_status', 'وضعیت تأهل')->enum('marital_status', ['مجرد', 'متأهل'], 'وضعیت تأهل')
            ->required('physical_condition', 'وضعیت جسمانی')->enum('physical_condition', ['سالم', 'معلولیت'], 'وضعیت جسمانی')
            ->required('has_court_case', 'پرونده قضایی')->enum('has_court_case', ['بله', 'خیر'], 'پرونده قضایی')
            ->required('has_active_loan', 'وام فعال')->enum('has_active_loan', ['بله', 'خیر'], 'وام فعال')
            ->required('has_overdue_installments', 'اقساط معوقه')->enum('has_overdue_installments', ['بله', 'خیر'], 'اقساط معوقه');

        if ($birthError === 'required') {
            $validator->addError('birth_date', 'تاریخ تولد الزامی است.');
        } elseif ($birthError === 'invalid') {
            $validator->addError('birth_date', 'تاریخ تولد معتبر نیست.');
        }
        if (($data['physical_condition'] ?? '') === 'معلولیت') {
            $validator->required('disability_details', 'توضیحات معلولیت');
        }
        if (($data['marital_status'] ?? '') === 'متأهل') {
            $validator->required('children_count', 'تعداد فرزندان')->unsignedInt('children_count', 'تعداد فرزندان');
        } else {
            $data['children_count'] = 0;
        }
        if (!empty($data['income_decile'])) {
            $validator->unsignedInt('income_decile', 'دهک درآمدی');
        }
        // یکتایی موبایل در سطح شخص (چندمجوزی — فاز ۷)
        if ((new Person())->mobileBelongsToOtherPerson((string)($data['mobile'] ?? ''), (string)($data['national_code'] ?? ''))) {
            $validator->addError('mobile', 'این شمارهٔ موبایل به کد ملی دیگری تعلق دارد.');
        }

        /* ---------- اطلاعات شخص حقوقی (فاز ۱۰ نقشهٔ راه) ---------- */
        if ($applicantType === 'حقوقی') {
            $validator->required('company_name', 'نام شرکت/مؤسسه')
                ->maxLength('company_name', 150, 'نام شرکت')
                ->required('company_national_id', 'شناسهٔ ملی شخص حقوقی')
                ->legalNationalId('company_national_id', 'شناسهٔ ملی شخص حقوقی')
                ->required('reg_number', 'شمارهٔ ثبت')
                ->maxLength('reg_number', 30, 'شمارهٔ ثبت')
                ->required('company_type', 'نوع شخصیت حقوقی');

            $regDate = null;
            if (!empty($data['reg_year']) && !empty($data['reg_month']) && !empty($data['reg_day'])) {
                $ry = (int)$data['reg_year']; $rm = (int)$data['reg_month']; $rd = (int)$data['reg_day'];
                if (is_valid_persian_date($ry, $rm, $rd)) {
                    $regDate = persian_to_gregorian($ry, $rm, $rd);
                } else {
                    $validator->addError('reg_date', 'تاریخ ثبت شرکت معتبر نیست.');
                }
            } else {
                $validator->addError('reg_date', 'تاریخ ثبت شرکت الزامی است.');
            }
            $data['reg_date'] = $regDate;
        } else {
            foreach (['company_name', 'company_national_id', 'reg_number', 'company_type', 'economic_code'] as $lk) {
                $data[$lk] = null;
            }
            $data['reg_date'] = null;
        }

        // فیلدهای سفارشی همین مرحله
        $stepId = (int)$steps[$position - 1]['id'];
        $this->validateDynamicFields($validator, $data, $this->customFieldsForStepId($stepId));

        if ($validator->hasErrors()) {
            $this->fail($steps, $position, $validator->getErrors(), $data);
        }
        $data['birth_date'] = $birth_date;
        Session::setStep('step1', $data);
        Session::remove('errors');
        $this->advance($steps, $position);
    }

    private function handleStep2(array $steps, int $position): void
    {
        $data = $this->allInputs();
        $validator = new Validator($data);
        $validator->required('license_title', 'عنوان مجوز')->maxLength('license_title', 100, 'عنوان مجوز')
            ->required('experience_months', 'سابقه فعالیت')->unsignedInt('experience_months', 'سابقه فعالیت')
            ->required('training_method', 'نحوه آموزش')->enum('training_method', ['در خانه', 'استاد شاگردی', 'کارگاهی'], 'نحوه آموزش');

        $stepId = (int)$steps[$position - 1]['id'];
        $this->validateDynamicFields($validator, $data, $this->customFieldsForStepId($stepId));

        if ($validator->hasErrors()) {
            $this->fail($steps, $position, $validator->getErrors(), $data);
        }
        Session::setStep('step2', $data);
        Session::remove('errors');
        $this->advance($steps, $position);
    }

    private function handleStep3(array $steps, int $position): void
    {
        $data = $this->allInputs();
        $validator = new Validator($data);
        $validator->required('ownership_status', 'وضعیت مالکیت')->enum('ownership_status', ['شخص', 'مستأجر', 'سرپرست'], 'وضعیت مالکیت')
            ->required('province', 'استان')->maxLength('province', 50, 'استان')
            ->required('county', 'شهرستان')->maxLength('county', 50, 'شهرستان')
            ->required('city', 'شهر')->maxLength('city', 50, 'شهر')
            ->required('electricity_bill_id', 'شناسه قبض برق')->maxLength('electricity_bill_id', 20, 'شناسه قبض برق')
            ->required('address_street', 'خیابان')->maxLength('address_street', 100, 'خیابان')
            ->required('address_alley', 'کوچه')->maxLength('address_alley', 100, 'کوچه')
            ->required('address_plaque', 'پلاک')->maxLength('address_plaque', 20, 'پلاک');

        foreach (['district' => 'بخش', 'rural_district' => 'دهستان', 'village' => 'روستا'] as $k => $lbl) {
            if (!empty($data[$k])) {
                $validator->maxLength($k, 50, $lbl);
            }
        }

        $stepId = (int)$steps[$position - 1]['id'];
        $this->validateDynamicFields($validator, $data, $this->customFieldsForStepId($stepId));

        if ($validator->hasErrors()) {
            $this->fail($steps, $position, $validator->getErrors(), $data);
        }
        Session::setStep('step3', $data);
        Session::remove('errors');
        $this->advance($steps, $position);
    }

    /**
     * مرحلهٔ سفارشی — فقط فیلدهای پویا
     */
    private function handleCustomStep(array $steps, int $position, array $step): void
    {
        $data = $this->allInputs();
        $stepId = (int)$step['id'];
        $fields = $this->customFieldsForStepId($stepId);

        // فیلدهای فایلی در مرحلهٔ سفارشی پشتیبانی نمی‌شوند (فرم multipart نیست) —
        // فرم‌ساز اجازهٔ ساخت فیلد فایلی خارج از مرحلهٔ مدارک را نمی‌دهد.
        $fields = array_values(array_filter($fields, fn($f) => $f['field_type'] !== 'file'));

        $validator = new Validator($data);
        $this->validateDynamicFields($validator, $data, $fields);

        if ($validator->hasErrors()) {
            $this->fail($steps, $position, $validator->getErrors(), $data);
        }
        Session::setStep($this->sessionKey($step), $data);
        Session::remove('errors');
        $this->advance($steps, $position);
    }

    /* ==================== نهایی‌سازی (مرحلهٔ مدارک) ==================== */

    private function handleFinalize(array $steps, int $position): void
    {
        $step1 = Session::getStep('step1');
        $step2 = Session::getStep('step2');
        $step3 = Session::getStep('step3');
        if (!$step1 || !$step2 || !$step3) {
            $this->redirect('/register');
        }

        $ownershipStatus = $step3['ownership_status'];
        $errors = [];
        $sysActive = $this->fieldModel->systemActiveMap();

        /* ---------- مدارک سیستمی ---------- */
        $nationalCardFront = $_FILES['national_card_front'] ?? null;
        $nationalCardBack = $_FILES['national_card_back'] ?? null;
        if ((!$nationalCardFront || $nationalCardFront['error'] !== UPLOAD_ERR_OK) &&
            (!$nationalCardBack || $nationalCardBack['error'] !== UPLOAD_ERR_OK)) {
            $errors['national_card'][] = 'تصویر کارت ملی (پشت یا رو) الزامی است.';
        }
        $birthCert = $_FILES['birth_cert'] ?? null;
        if (!$birthCert || $birthCert['error'] !== UPLOAD_ERR_OK) {
            $errors['birth_cert'][] = 'تصویر شناسنامه الزامی است.';
        }
        $ownershipDoc = $_FILES['ownership_doc'] ?? null;
        if (!$ownershipDoc || $ownershipDoc['error'] !== UPLOAD_ERR_OK) {
            $errors['ownership_doc'][] = 'بارگذاری مدرک مربوط به وضعیت مالکیت الزامی است.';
        }

        $vocationalCert = ($sysActive['vocational_cert'] ?? true) ? ($_FILES['vocational_cert'] ?? null) : null;
        $educationDegree = ($sysActive['education_degree'] ?? true) ? ($_FILES['education_degree'] ?? null) : null;

        $optionalFiles = [];
        for ($i = 1; $i <= 3; $i++) {
            if (!($sysActive["optional_{$i}"] ?? true)) continue;
            $file = $_FILES["optional_{$i}"] ?? null;
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $optionalFiles[] = $file;
            }
        }

        $productPhotos = [];
        if (($sysActive['product_photos'] ?? true)
            && !empty($_FILES['product_photos']['name']) && is_array($_FILES['product_photos']['name'])) {
            $ppNames = $_FILES['product_photos']['name'] ?? [];
            $ppTmpNames = $_FILES['product_photos']['tmp_name'] ?? [];
            $ppSizes = $_FILES['product_photos']['size'] ?? [];
            $ppErrors = $_FILES['product_photos']['error'] ?? [];
            foreach ($ppNames as $idx => $name) {
                if (($ppErrors[$idx] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $productPhotos[] = [
                        'name' => $name,
                        'tmp_name' => $ppTmpNames[$idx] ?? '',
                        'size' => $ppSizes[$idx] ?? 0,
                        'error' => $ppErrors[$idx] ?? UPLOAD_ERR_NO_FILE,
                    ];
                }
            }
        }

        /* ---------- فیلدهای سفارشی این مرحله (داده + فایل) ---------- */
        $stepId = (int)$steps[$position - 1]['id'];
        $dynFields4 = $this->customFieldsForStepId($stepId);
        $dynFileFields = array_values(array_filter($dynFields4, fn($f) => $f['field_type'] === 'file'));
        $dynDataFields = array_values(array_filter($dynFields4, fn($f) => $f['field_type'] !== 'file'));
        $postAll = $this->allInputs();

        $dynValidator = new Validator($postAll);
        $this->validateDynamicFields($dynValidator, $postAll, $dynDataFields);
        foreach ($dynValidator->getErrors() as $fname => $ferrors) {
            $errors[$fname] = $ferrors;
        }

        $dynFiles = [];
        foreach ($dynFileFields as $f) {
            $file = $_FILES['dyn_' . (int)$f['id']] ?? null;
            if ((int)$f['is_required'] === 1 && (!$file || $file['error'] !== UPLOAD_ERR_OK)) {
                $errors['dyn_' . (int)$f['id']][] = 'بارگذاری «' . $f['label'] . '» الزامی است.';
            } elseif ($file && $file['error'] === UPLOAD_ERR_OK) {
                $dynFiles[] = $file;
            }
        }

        /* ---------- اعتبارسنجی یکپارچهٔ همهٔ فایل‌ها ---------- */
        $allFiles = array_filter([
            $nationalCardFront, $nationalCardBack, $birthCert, $ownershipDoc,
            $vocationalCert, $educationDegree,
            ...$optionalFiles, ...$productPhotos, ...$dynFiles,
        ]);
        $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
        foreach ($allFiles as $file) {
            $safeName = htmlspecialchars(sanitize_upload_filename((string)$file['name']), ENT_QUOTES, 'UTF-8');
            $ext = safe_file_extension((string)$file['name']);
            if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
                $errors['files'][] = "پسوند فایل «{$safeName}» مجاز نیست. فقط " . implode(', ', ALLOWED_EXTENSIONS) . " مجاز است.";
                continue;
            }
            if ($file['size'] > MAX_UPLOAD_SIZE) {
                $errors['files'][] = "حجم فایل «{$safeName}» بیش از ۲ مگابایت است.";
                continue;
            }
            $realMime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if (!in_array($realMime, $allowedMimes, true)) {
                $errors['files'][] = "محتوای فایل «{$safeName}» با پسوندهای مجاز (تصویر JPG/PNG یا PDF) همخوانی ندارد.";
            }
        }

        if (!empty($errors)) {
            Session::set('errors', $errors);
            $this->redirect('/register/step/' . $position);
        }

        /* ---------- تراکنش نهایی‌سازی ---------- */
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            $trackingCode = Applicant::generateTrackingCode();

            $applicantId = $this->applicantModel->create([
                'first_name' => $step1['first_name'],
                'last_name' => $step1['last_name'],
                'national_code' => $step1['national_code'],
                'postal_code' => $step1['postal_code'],
                'mobile' => $step1['mobile'],
                'birth_date' => $step1['birth_date'] ?? null,
                'father_name' => $step1['father_name'] ?? null,
                'marital_status' => $step1['marital_status'] ?? 'مجرد',
                'children_count' => $step1['children_count'] ?? 0,
                'physical_condition' => $step1['physical_condition'] ?? 'سالم',
                'disability_details' => $step1['disability_details'] ?? null,
                'income_decile' => $step1['income_decile'] ?? null,
                'has_court_case' => $step1['has_court_case'] ?? 'خیر',
                'has_active_loan' => $step1['has_active_loan'] ?? 'خیر',
                'has_overdue_installments' => $step1['has_overdue_installments'] ?? 'خیر',
                'tracking_code' => $trackingCode,
                'service_type_id' => $this->selectedServiceId(),
                'applicant_type' => $step1['applicant_type'] ?? 'حقیقی',
            ]);

            // شخص (چندمجوزی — فاز ۷)
            $personModel = new Person();
            $personData = [
                'national_code' => $step1['national_code'],
                'first_name' => $step1['first_name'],
                'last_name' => $step1['last_name'],
                'father_name' => $step1['father_name'] ?? null,
                'birth_date' => $step1['birth_date'] ?? null,
                'mobile' => $step1['mobile'],
                'postal_code' => $step1['postal_code'],
            ];
            if (($step1['applicant_type'] ?? 'حقیقی') === 'حقوقی') {
                $personData += [
                    'person_type' => 'حقوقی',
                    'company_name' => $step1['company_name'] ?? null,
                    'company_national_id' => $step1['company_national_id'] ?? null,
                    'reg_number' => $step1['reg_number'] ?? null,
                    'reg_date' => $step1['reg_date'] ?? null,
                    'company_type' => $step1['company_type'] ?? null,
                    'economic_code' => $step1['economic_code'] ?? null,
                ];
            }
            $personId = $personModel->findOrCreate($personData);
            $personModel->linkApplication($applicantId, $personId);

            // شغلی
            if (str_starts_with($step2['license_title'], 'سایر') && !empty($step2['custom_license_title'])) {
                $step2['license_title'] = mb_substr(trim($step2['custom_license_title']), 0, 100);
            }
            $this->jobDetailModel->create([
                'applicant_id' => $applicantId,
                'license_title' => $step2['license_title'],
                'experience_months' => $step2['experience_months'],
                'training_method' => $step2['training_method'],
            ]);

            // ملک
            $this->propertyDetailModel->create([
                'applicant_id' => $applicantId,
                'ownership_status' => $step3['ownership_status'],
                'province' => $step3['province'],
                'county' => $step3['county'],
                'city' => $step3['city'],
                'district' => $step3['district'] ?? null,
                'rural_district' => $step3['rural_district'] ?? null,
                'village' => $step3['village'] ?? null,
                'electricity_bill_id' => $step3['electricity_bill_id'],
                'address_street' => $step3['address_street'] ?? null,
                'address_alley' => $step3['address_alley'] ?? null,
                'address_plaque' => $step3['address_plaque'] ?? null,
            ]);

            // مدارک
            $uploadDir = UPLOAD_DIR . '/' . date('Y/m');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $saveFile = function ($file, $docType) use ($applicantId, $uploadDir) {
                $ext = safe_file_extension((string)$file['name']);
                $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
                $destPath = $uploadDir . '/' . $uniqueName;
                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    throw new \RuntimeException('خطا در ذخیره فایل ' . sanitize_upload_filename((string)$file['name']));
                }
                @chmod($destPath, 0640);
                return $this->documentModel->create([
                    'applicant_id' => $applicantId,
                    'doc_type' => $docType,
                    'file_name' => $uniqueName,
                    'original_name' => sanitize_upload_filename((string)$file['name']),
                    'file_path' => str_replace(UPLOAD_DIR, '', $destPath),
                ]);
            };

            if ($nationalCardFront && $nationalCardFront['error'] === UPLOAD_ERR_OK) $saveFile($nationalCardFront, 'national_card');
            if ($nationalCardBack && $nationalCardBack['error'] === UPLOAD_ERR_OK) $saveFile($nationalCardBack, 'national_card');
            $saveFile($birthCert, 'birth_cert');
            $saveFile($ownershipDoc, 'ownership_doc');
            if ($vocationalCert && $vocationalCert['error'] === UPLOAD_ERR_OK) $saveFile($vocationalCert, 'vocational_cert');
            if ($educationDegree && $educationDegree['error'] === UPLOAD_ERR_OK) $saveFile($educationDegree, 'education_degree');
            foreach ($productPhotos as $file) $saveFile($file, 'product_photos');
            foreach ($optionalFiles as $file) $saveFile($file, 'optional');

            /* ---------- مقادیر فیلدهای سفارشی همهٔ مراحل ---------- */
            $dynValues = [];
            // فایل‌های سفارشی این مرحله
            foreach ($dynFileFields as $f) {
                $file = $_FILES['dyn_' . (int)$f['id']] ?? null;
                if ($file && $file['error'] === UPLOAD_ERR_OK) {
                    $docId = $saveFile($file, 'dynamic');
                    $dynValues[(int)$f['id']] = 'doc:' . $docId;
                }
            }
            // داده‌های مراحل سیستمی ۱ تا ۳ + مراحل سفارشی میانی + مرحلهٔ جاری
            foreach ($steps as $i => $st) {
                $sid = (int)$st['id'];
                $sessKey = $this->sessionKey($st);
                $stData = ($i === $position - 1) ? $postAll : Session::getStep($sessKey, []);
                foreach ($this->customFieldsForStepId($sid) as $f) {
                    if ($f['field_type'] === 'file') continue;
                    $key = 'dyn_' . (int)$f['id'];
                    if (array_key_exists($key, $stData)) {
                        $dynValues[(int)$f['id']] = (string)$stData[$key];
                    }
                }
            }
            if ($dynValues) {
                $this->fieldValueModel->saveMany($applicantId, $dynValues);
            }

            $this->applicantModel->completeRegistration($applicantId);
            $this->statusLogModel->create([
                'applicant_id' => $applicantId,
                'status_name' => 'تکمیل اطلاعات',
                'description' => 'ثبت‌نام نهایی توسط متقاضی انجام شد.',
            ]);

            $db->commit();
            Session::clearRegistration();
            $this->redirect('/register/success/' . $trackingCode);
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('registration finalize failed: ' . $e->getMessage());
            Session::set('errors', ['system' => ['خطایی در ثبت‌نام رخ داد. لطفاً دوباره تلاش کنید.']]);
            $this->redirect('/register/step/' . $position);
        }
    }

    /* ==================== اعتبارسنجی فیلدهای پویا ==================== */

    private function validateDynamicFields(Validator $validator, array $data, array $fields): void
    {
        foreach ($fields as $f) {
            $name = 'dyn_' . (int)$f['id'];
            $value = trim((string)($data[$name] ?? ''));
            $rules = FormField::parseRules($f['validation_json']);
            $label = $f['label'];

            if ((int)$f['is_required'] === 1 && $value === '') {
                $validator->addError($name, $label . ' الزامی است.');
                continue;
            }
            if ($value === '') continue;

            if (!empty($rules['maxlength']) && mb_strlen($value) > (int)$rules['maxlength']) {
                $validator->addError($name, $label . ' نباید بیش از ' . (int)$rules['maxlength'] . ' کاراکتر باشد.');
            }
            if ($f['field_type'] === 'number') {
                if (!is_numeric($value)) {
                    $validator->addError($name, $label . ' باید عدد باشد.');
                } else {
                    if (isset($rules['min']) && (float)$value < (float)$rules['min']) {
                        $validator->addError($name, $label . ' نباید کمتر از ' . $rules['min'] . ' باشد.');
                    }
                    if (isset($rules['max']) && (float)$value > (float)$rules['max']) {
                        $validator->addError($name, $label . ' نباید بیشتر از ' . $rules['max'] . ' باشد.');
                    }
                }
            }
            if (in_array($f['field_type'], ['select', 'radio'], true)) {
                if (!in_array($value, FormField::parseOptions($f['options']), true)) {
                    $validator->addError($name, $label . ' مقدار معتبری نیست.');
                }
            }
            if (!empty($rules['pattern']) && @preg_match('/' . str_replace('/', '\\/', $rules['pattern']) . '/u', $value) !== 1) {
                $validator->addError($name, $label . ' معتبر نیست.');
            }
        }
    }

    /* ==================== صفحهٔ موفقیت ==================== */

    public function success(string $trackingCode): void
    {
        $applicant = $this->applicantModel->findByTrackingCode($trackingCode);
        if (!$applicant) {
            $this->redirect('/');
        }
        $fullName = trim($applicant['first_name'] . ' ' . $applicant['last_name']);
        $this->render('registration/success', [
            'title' => 'ثبت‌نام با موفقیت انجام شد',
            'trackingCode' => $trackingCode,
            'applicantName' => $fullName,
            'notifyMessage' => build_notify_message($fullName, $trackingCode),
        ]);
    }
}
