<?php
/**
 * کنترلر چرخهٔ بازبینی پرونده (فاز ۲ نقشهٔ راه)
 * =====================================================================
 * قاعدهٔ کسب‌وکار: متقاضی پس از ثبت نهایی امکان ویرایش ندارد؛ اما می‌تواند
 * «درخواست بازبینی» بدهد — فرم کامل مراحل ۱ تا ۳ (پیش‌پرشده) را ویرایش و
 * مدارک جدید بارگذاری کند. تا تأیید مدیر، دادهٔ رسمی پرونده تغییر نمی‌کند.
 * محدودیت: هر ۷ روز حداکثر ۲ درخواست و نبودِ درخواست در انتظار.
 */
class RevisionController extends Controller
{
    private ApplicantRevision $revisionModel;
    private Applicant $applicantModel;
    private JobDetail $jobDetailModel;
    private PropertyDetail $propertyDetailModel;
    private Document $documentModel;
    private StatusLog $statusLogModel;
    private FormField $fieldModel;
    private ApplicantFieldValue $fieldValueModel;

    public function __construct()
    {
        $this->revisionModel = new ApplicantRevision();
        $this->applicantModel = new Applicant();
        $this->jobDetailModel = new JobDetail();
        $this->propertyDetailModel = new PropertyDetail();
        $this->documentModel = new Document();
        $this->statusLogModel = new StatusLog();
        $this->fieldModel = new FormField();
        $this->fieldValueModel = new ApplicantFieldValue();
    }

    /* ==================== سمت متقاضی ==================== */

    /**
     * فرم بازبینی — پیش‌پرشده با همهٔ اطلاعات فعلی پرونده
     */
    public function form(string $trackingCode): void
    {
        $applicant = $this->applicantModel->findByTrackingCode(strtoupper(trim($trackingCode)));
        if (!$applicant) {
            Session::set('tracking_error', 'کد رهگیری نامعتبر است.');
            $this->redirect('/tracking');
        }
        $aid = (int)$applicant['id'];

        // شرایط پذیرش درخواست بازبینی
        $blocked = $this->checkBlocked($aid);
        if ($blocked !== null) {
            Session::set('revision_error', $blocked);
            $this->redirect('/tracking/status/' . rawurlencode($applicant['tracking_code']));
        }

        // مقادیر فعلی فیلدهای سفارشی
        $dynCurrent = [];
        foreach ($this->fieldValueModel->findByApplicantId($aid) as $dv) {
            $dynCurrent[$dv['field_key']] = $dv['field_value'];
        }

        $this->render('tracking/revision', [
            'title' => 'درخواست بازبینی پرونده',
            'applicant' => $applicant,
            'jobDetail' => $this->jobDetailModel->findByApplicantId($aid),
            'propertyDetail' => $this->propertyDetailModel->findByApplicantId($aid),
            'dynFields' => $this->dynFieldsForSteps([1, 2, 3]),
            'dynCurrent' => $dynCurrent,
            'licenseGroups' => (new LicenseTitle())->groupedActive(),
            'errors' => Session::get('revision_errors', []),
            'old' => Session::get('revision_form', []),
        ]);
        Session::remove('revision_errors');
        Session::remove('revision_form');
    }

    /**
     * ثبت درخواست بازبینی
     */
    public function submit(): void
    {
        if (!$this->isPost()) $this->redirect('/tracking');
        $this->verifyCsrf();

        $applicant = $this->findApplicantOrFail();
        $aid = (int)$applicant['id'];
        $back = '/tracking/status/' . rawurlencode($applicant['tracking_code']);

        $blocked = $this->checkBlocked($aid);
        if ($blocked !== null) {
            Session::set('revision_error', $blocked);
            $this->redirect($back);
        }

        $data = $this->allInputs();

        /* ---------- اعتبارسنجی (همان قواعد ثبت‌نام، بدون کد ملی) ---------- */
        $validator = new Validator($data);
        $validator->required('first_name', 'نام')->persianAlpha('first_name', 'نام')->maxLength('first_name', 50, 'نام')
            ->required('last_name', 'نام خانوادگی')->persianAlpha('last_name', 'نام خانوادگی')->maxLength('last_name', 50, 'نام خانوادگی')
            ->required('postal_code', 'کد پستی')->postalCode('postal_code', 'کد پستی')
            ->required('mobile', 'موبایل')->mobile('mobile', 'موبایل')
            ->unique('mobile', 'applicants', 'mobile', 'شمارهٔ موبایل', $aid)
            ->required('father_name', 'نام پدر')->persianAlpha('father_name', 'نام پدر')->maxLength('father_name', 50, 'نام پدر')
            ->required('marital_status', 'وضعیت تأهل')->enum('marital_status', ['مجرد', 'متأهل'], 'وضعیت تأهل')
            ->required('physical_condition', 'وضعیت جسمانی')->enum('physical_condition', ['سالم', 'معلولیت'], 'وضعیت جسمانی')
            ->enum('has_court_case', ['بله', 'خیر'], 'پروندهٔ قضایی')
            ->enum('has_active_loan', ['بله', 'خیر'], 'وام فعال')
            ->enum('has_overdue_installments', ['بله', 'خیر'], 'اقساط معوقه')
            ->required('license_title', 'عنوان مجوز')->maxLength('license_title', 100, 'عنوان مجوز')
            ->required('experience_months', 'سابقهٔ فعالیت')->unsignedInt('experience_months', 'سابقهٔ فعالیت')
            ->required('training_method', 'نحوهٔ آموزش')->enum('training_method', ['در خانه', 'استاد شاگردی', 'کارگاهی'], 'نحوهٔ آموزش')
            ->required('ownership_status', 'وضعیت مالکیت')->enum('ownership_status', ['شخص', 'مستأجر', 'سرپرست'], 'وضعیت مالکیت')
            ->required('province', 'استان')->maxLength('province', 50, 'استان')
            ->required('county', 'شهرستان')->maxLength('county', 50, 'شهرستان')
            ->required('city', 'شهر')->maxLength('city', 50, 'شهر')
            ->required('electricity_bill_id', 'شناسهٔ قبض برق')->maxLength('electricity_bill_id', 20, 'شناسهٔ قبض برق')
            ->required('address_street', 'خیابان')->maxLength('address_street', 100, 'خیابان');

        // تاریخ تولد شمسی
        $birthDate = $applicant['birth_date'];
        if (!empty($data['birth_year']) && !empty($data['birth_month']) && !empty($data['birth_day'])) {
            $jy = (int)$data['birth_year']; $jm = (int)$data['birth_month']; $jd = (int)$data['birth_day'];
            if (is_valid_persian_date($jy, $jm, $jd)) {
                $birthDate = persian_to_gregorian($jy, $jm, $jd);
            } else {
                $validator->addError('birth_date', 'تاریخ تولد معتبر نیست.');
            }
        }
        if (($data['marital_status'] ?? '') === 'متأهل') {
            $validator->unsignedInt('children_count', 'تعداد فرزندان');
        }
        if (($data['physical_condition'] ?? '') === 'معلولیت') {
            $validator->required('disability_details', 'توضیحات معلولیت');
        }

        // فیلدهای سفارشی مراحل ۱ تا ۳
        $dynFields = $this->dynFieldsForSteps([1, 2, 3]);
        foreach ($dynFields as $f) {
            $name = 'dyn_' . (int)$f['id'];
            $value = trim((string)($data[$name] ?? ''));
            $rules = FormField::parseRules($f['validation_json']);
            if ((int)$f['is_required'] === 1 && $value === '') {
                $validator->addError($name, $f['label'] . ' الزامی است.');
            }
            if ($value !== '' && !empty($rules['maxlength']) && mb_strlen($value) > (int)$rules['maxlength']) {
                $validator->addError($name, $f['label'] . ' بیش از حد طولانی است.');
            }
            if ($value !== '' && in_array($f['field_type'], ['select', 'radio'], true)
                && !in_array($value, FormField::parseOptions($f['options']), true)) {
                $validator->addError($name, $f['label'] . ' مقدار معتبری نیست.');
            }
        }

        /* ---------- مدارک جدید (اختیاری — حداکثر ۵ فایل با نوع مشخص) ---------- */
        $allowedDocTypes = ['national_card', 'birth_cert', 'ownership_doc', 'vocational_cert',
            'education_degree', 'product_photos', 'optional'];
        $newDocs = [];
        for ($i = 1; $i <= 5; $i++) {
            $file = $_FILES['rev_doc_' . $i] ?? null;
            if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) continue;
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $validator->addError('rev_doc_' . $i, 'خطا در بارگذاری فایل شمارهٔ ' . $i);
                continue;
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
                $validator->addError('rev_doc_' . $i, 'پسوند فایل شمارهٔ ' . $i . ' مجاز نیست.');
                continue;
            }
            if ($file['size'] > MAX_UPLOAD_SIZE) {
                $validator->addError('rev_doc_' . $i, 'حجم فایل شمارهٔ ' . $i . ' بیش از ۲ مگابایت است.');
                continue;
            }
            if (!in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)) {
                $validator->addError('rev_doc_' . $i, 'محتوای فایل شمارهٔ ' . $i . ' معتبر نیست.');
                continue;
            }
            $type = (string)($data['rev_doc_type_' . $i] ?? 'optional');
            $newDocs[$i] = ['file' => $file, 'type' => in_array($type, $allowedDocTypes, true) ? $type : 'optional'];
        }

        if ($validator->hasErrors()) {
            Session::set('revision_errors', $validator->getErrors());
            Session::set('revision_form', $data);
            $this->redirect('/tracking/revision/' . rawurlencode($applicant['tracking_code']));
        }

        /* ---------- ذخیرهٔ مدارک موقت + ساخت payload ---------- */
        $uploadDir = UPLOAD_DIR . '/' . date('Y/m');
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $payloadDocs = [];
        foreach ($newDocs as $d) {
            $file = $d['file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $uniqueName)) {
                Session::set('revision_error', 'خطا در ذخیرهٔ یکی از فایل‌ها.');
                $this->redirect('/tracking/revision/' . rawurlencode($applicant['tracking_code']));
            }
            @chmod($uploadDir . '/' . $uniqueName, 0640);
            $docId = $this->documentModel->create([
                'applicant_id' => $aid,
                'doc_type' => 'revision_doc',   // نوع موقت تا زمان تأیید مدیر
                'file_name' => $uniqueName,
                'original_name' => basename((string)$file['name']),
                'file_path' => '/' . date('Y/m') . '/' . $uniqueName,
            ]);
            $payloadDocs[] = ['doc_id' => $docId, 'doc_type' => $d['type'], 'name' => basename((string)$file['name'])];
        }

        $dynValues = [];
        foreach ($dynFields as $f) {
            $dynValues[(string)$f['id']] = trim((string)($data['dyn_' . (int)$f['id']] ?? ''));
        }

        $payload = [
            'identity' => [
                'first_name' => trim((string)$data['first_name']),
                'last_name' => trim((string)$data['last_name']),
                'postal_code' => trim((string)$data['postal_code']),
                'mobile' => trim((string)$data['mobile']),
                'birth_date' => $birthDate,
                'father_name' => trim((string)$data['father_name']),
                'marital_status' => $data['marital_status'],
                'children_count' => (int)($data['children_count'] ?? 0),
                'physical_condition' => $data['physical_condition'],
                'disability_details' => trim((string)($data['disability_details'] ?? '')) ?: null,
                'income_decile' => $data['income_decile'] !== '' ? (int)$data['income_decile'] : null,
                'has_court_case' => $data['has_court_case'] ?? 'خیر',
                'has_active_loan' => $data['has_active_loan'] ?? 'خیر',
                'has_overdue_installments' => $data['has_overdue_installments'] ?? 'خیر',
            ],
            'job' => [
                'license_title' => trim((string)$data['license_title']),
                'experience_months' => (int)$data['experience_months'],
                'training_method' => $data['training_method'],
            ],
            'property' => [
                'ownership_status' => $data['ownership_status'],
                'province' => trim((string)$data['province']),
                'county' => trim((string)$data['county']),
                'city' => trim((string)$data['city']),
                'district' => trim((string)($data['district'] ?? '')) ?: null,
                'rural_district' => trim((string)($data['rural_district'] ?? '')) ?: null,
                'village' => trim((string)($data['village'] ?? '')) ?: null,
                'electricity_bill_id' => trim((string)$data['electricity_bill_id']),
                'address_street' => trim((string)($data['address_street'] ?? '')) ?: null,
                'address_alley' => trim((string)($data['address_alley'] ?? '')) ?: null,
                'address_plaque' => trim((string)($data['address_plaque'] ?? '')) ?: null,
            ],
            'dyn' => $dynValues,
            'docs' => $payloadDocs,
        ];

        $this->revisionModel->create($aid, $payload, trim((string)($data['note'] ?? '')) ?: null);

        $this->statusLogModel->create([
            'applicant_id' => $aid,
            'status_name' => 'درخواست بازبینی پرونده',
            'description' => 'متقاضی درخواست ویرایش اطلاعات/افزودن مدرک ثبت کرد'
                . (count($payloadDocs) ? ' — همراه با ' . count($payloadDocs) . ' مدرک جدید' : '')
                . ' — در انتظار بررسی مدیر',
        ]);

        Session::set('revision_success', 'درخواست بازبینی ثبت شد؛ پس از بررسی مدیر، تغییرات اعمال یا با دلیل رد می‌شود.');
        $this->redirect($back);
    }

    /* ==================== سمت مدیر ==================== */

    public function index(): void
    {
        $this->requireRole(['admin']);
        $statusFilter = $_GET['status'] ?? '';
        if (!in_array($statusFilter, ['', 'در انتظار', 'تأیید شده', 'رد شده'], true)) $statusFilter = '';

        $this->render('admin/revisions', [
            'title' => 'درخواست‌های بازبینی پرونده',
            'revisions' => $this->revisionModel->getAllWithApplicant($statusFilter),
            'statusFilter' => $statusFilter,
            'message' => Session::get('revision_admin_message'),
            'currentPage' => 'revisions',
        ], 'admin_layout');
        Session::remove('revision_admin_message');
    }

    /**
     * نمای مقایسهٔ مقادیر فعلی و پیشنهادی
     */
    public function detail(int|string $id): void
    {
        $this->requireRole(['admin']);
        $id = (int)$id;
        $rev = $this->revisionModel->findById($id);
        if (!$rev) $this->redirect('/admin/revisions');

        $aid = (int)$rev['applicant_id'];
        $applicant = $this->applicantModel->findById($aid);
        $job = $this->jobDetailModel->findByApplicantId($aid);
        $prop = $this->propertyDetailModel->findByApplicantId($aid);

        // ردیف‌های مقایسه: [برچسب، مقدار فعلی، مقدار پیشنهادی]
        $p = $rev['payload_data'];
        $cmp = [];
        $cmpRows = [
            ['نام', $applicant['first_name'], $p['identity']['first_name'] ?? ''],
            ['نام خانوادگی', $applicant['last_name'], $p['identity']['last_name'] ?? ''],
            ['کد پستی', $applicant['postal_code'], $p['identity']['postal_code'] ?? ''],
            ['موبایل', $applicant['mobile'], $p['identity']['mobile'] ?? ''],
            ['تاریخ تولد', persianDate((string)$applicant['birth_date']), persianDate((string)($p['identity']['birth_date'] ?? ''))],
            ['نام پدر', $applicant['father_name'], $p['identity']['father_name'] ?? ''],
            ['وضعیت تأهل', $applicant['marital_status'] . ' (' . (int)$applicant['children_count'] . ' فرزند)', ($p['identity']['marital_status'] ?? '') . ' (' . (int)($p['identity']['children_count'] ?? 0) . ' فرزند)'],
            ['وضعیت جسمانی', $applicant['physical_condition'], $p['identity']['physical_condition'] ?? ''],
            ['پروندهٔ قضایی / وام / اقساط', $applicant['has_court_case'] . ' / ' . $applicant['has_active_loan'] . ' / ' . $applicant['has_overdue_installments'],
                ($p['identity']['has_court_case'] ?? '') . ' / ' . ($p['identity']['has_active_loan'] ?? '') . ' / ' . ($p['identity']['has_overdue_installments'] ?? '')],
            ['عنوان مجوز', $job['license_title'] ?? '', $p['job']['license_title'] ?? ''],
            ['سابقه (ماه)', (string)($job['experience_months'] ?? ''), (string)($p['job']['experience_months'] ?? '')],
            ['نحوهٔ آموزش', $job['training_method'] ?? '', $p['job']['training_method'] ?? ''],
            ['وضعیت مالکیت', $prop['ownership_status'] ?? '', $p['property']['ownership_status'] ?? ''],
            ['استان/شهرستان/شهر', implode('/', [$prop['province'] ?? '', $prop['county'] ?? '', $prop['city'] ?? '']),
                implode('/', [$p['property']['province'] ?? '', $p['property']['county'] ?? '', $p['property']['city'] ?? ''])],
            ['شناسهٔ قبض برق', $prop['electricity_bill_id'] ?? '', $p['property']['electricity_bill_id'] ?? ''],
            ['خیابان/کوچه/پلاک', implode('، ', array_filter([$prop['address_street'] ?? '', $prop['address_alley'] ?? '', $prop['address_plaque'] ?? ''])),
                implode('، ', array_filter([$p['property']['address_street'] ?? '', $p['property']['address_alley'] ?? '', $p['property']['address_plaque'] ?? '']))],
        ];
        foreach ($cmpRows as [$label, $cur, $new]) {
            $cmp[] = ['label' => $label, 'current' => (string)$cur, 'proposed' => (string)$new,
                'changed' => trim((string)$cur) !== trim((string)$new)];
        }
        // فیلدهای سفارشی
        $dynCurrent = [];
        foreach ($this->fieldValueModel->findByApplicantId($aid) as $dv) {
            $dynCurrent[(string)$dv['field_key']] = $dv;
        }
        foreach ($this->dynFieldsForSteps([1, 2, 3]) as $f) {
            $cur = $dynCurrent[$f['field_key']]['field_value'] ?? '';
            $new = $p['dyn'][(string)$f['id']] ?? '';
            $cmp[] = ['label' => $f['label'] . ' (سفارشی)', 'current' => (string)$cur, 'proposed' => (string)$new,
                'changed' => trim((string)$cur) !== trim((string)$new)];
        }
        // مدارک جدید
        $newDocs = [];
        foreach ($p['docs'] ?? [] as $d) {
            $doc = $this->documentModel->findById((int)$d['doc_id']);
            if ($doc) $newDocs[] = ['doc' => $doc, 'type' => $d['doc_type']];
        }

        $this->render('admin/revision_detail', [
            'title' => 'بازبینی درخواست #' . $id,
            'rev' => $rev,
            'applicant' => $applicant,
            'cmp' => $cmp,
            'newDocs' => $newDocs,
            'docTypeLabels' => $this->docTypeLabels(),
            'currentPage' => 'revisions',
        ], 'admin_layout');
    }

    /**
     * تأیید بازبینی — اعمال داده‌ها در تراکنش اتمی
     */
    public function approve(int|string $id): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/revisions');
        $this->verifyCsrf();

        $id = (int)$id;
        $rev = $this->revisionModel->findById($id);
        if (!$rev || $rev['status'] !== 'در انتظار') {
            Session::set('revision_admin_message', 'درخواست یافت نشد یا پیش‌تر بررسی شده است.');
            $this->redirect('/admin/revisions');
        }

        $aid = (int)$rev['applicant_id'];
        $p = $rev['payload_data'];
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            // ۱) اطلاعات هویتی (کد ملی تغییر نمی‌کند — لنگر هویت است)
            $i = $p['identity'] ?? [];
            $stmt = $db->prepare(
                "UPDATE applicants SET first_name=:fn, last_name=:ln, postal_code=:pc, mobile=:mob,
                        birth_date=:bd, father_name=:fth, marital_status=:ms, children_count=:cc,
                        physical_condition=:phc, disability_details=:dd, income_decile=:idc,
                        has_court_case=:hcc, has_active_loan=:hal, has_overdue_installments=:hoi, updated_at=NOW()
                 WHERE id=:aid"
            );
            $stmt->execute([
                'fn' => $i['first_name'] ?? '', 'ln' => $i['last_name'] ?? '', 'pc' => $i['postal_code'] ?? '',
                'mob' => $i['mobile'] ?? '', 'bd' => $i['birth_date'] ?? null, 'fth' => $i['father_name'] ?? '',
                'ms' => $i['marital_status'] ?? 'مجرد', 'cc' => (int)($i['children_count'] ?? 0),
                'phc' => $i['physical_condition'] ?? 'سالم', 'dd' => $i['disability_details'] ?? null,
                'idc' => $i['income_decile'] ?? null, 'hcc' => $i['has_court_case'] ?? 'خیر',
                'hal' => $i['has_active_loan'] ?? 'خیر', 'hoi' => $i['has_overdue_installments'] ?? 'خیر',
                'aid' => $aid,
            ]);

            // ۲) اطلاعات شغلی
            $j = $p['job'] ?? [];
            $db->prepare("UPDATE job_details SET license_title=:lt, experience_months=:em, training_method=:tm WHERE applicant_id=:aid")
                ->execute(['lt' => $j['license_title'] ?? '', 'em' => (int)($j['experience_months'] ?? 0), 'tm' => $j['training_method'] ?? 'کارگاهی', 'aid' => $aid]);

            // ۳) اطلاعات ملک
            $pr = $p['property'] ?? [];
            $db->prepare(
                "UPDATE property_details SET ownership_status=:os, province=:prov, county=:cty, city=:ct,
                        district=:dis, rural_district=:rd, village=:vil, electricity_bill_id=:eb,
                        address_street=:st, address_alley=:al, address_plaque=:pl
                 WHERE applicant_id=:aid"
            )->execute([
                'os' => $pr['ownership_status'] ?? 'شخص', 'prov' => $pr['province'] ?? '', 'cty' => $pr['county'] ?? '',
                'ct' => $pr['city'] ?? '', 'dis' => $pr['district'] ?? null, 'rd' => $pr['rural_district'] ?? null,
                'vil' => $pr['village'] ?? null, 'eb' => $pr['electricity_bill_id'] ?? '', 'st' => $pr['address_street'] ?? null,
                'al' => $pr['address_alley'] ?? null, 'pl' => $pr['address_plaque'] ?? null, 'aid' => $aid,
            ]);

            // ۴) مقادیر فیلدهای سفارشی
            $dynValues = [];
            foreach (($p['dyn'] ?? []) as $fid => $val) {
                if ((int)$fid > 0) $dynValues[(int)$fid] = (string)$val;
            }
            if ($dynValues) $this->fieldValueModel->saveMany($aid, $dynValues);

            // ۵) فعال‌سازی مدارک جدید (تغییر نوع از موقت به انتخابی کاربر)
            foreach ($p['docs'] ?? [] as $d) {
                $db->prepare("UPDATE documents SET doc_type = :t WHERE id = :id AND applicant_id = :aid AND doc_type = 'revision_doc'")
                    ->execute(['t' => $d['doc_type'] ?? 'optional', 'id' => (int)$d['doc_id'], 'aid' => $aid]);
            }

            // ۶) همگام‌سازی رکورد شخص (فاز ۷) و ثبت نتیجه
            (new Person())->syncFromApplicant($aid);
            $this->revisionModel->review($id, 'تأیید شده', (int)Session::get('admin_id', 0));
            $this->statusLogModel->create([
                'applicant_id' => $aid,
                'status_name' => 'تأیید بازبینی پرونده',
                'description' => 'تغییرات درخواستی متقاضی اعمال شد'
                    . (count($p['docs'] ?? []) ? ' — ' . count($p['docs']) . ' مدرک جدید به پرونده افزوده شد' : '')
                    . ' — توسط ' . Session::get('admin_display_name', 'مدیر'),
            ]);
            $db->commit();
            Session::set('revision_admin_message', 'بازبینی تأیید و تغییرات اعمال شد. ✔');
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('revision approve failed: ' . $e->getMessage());
            Session::set('revision_admin_message', 'خطا در اعمال تغییرات — عملیات بازگردانی شد.');
        }
        $this->redirect('/admin/revisions');
    }

    /**
     * رد بازبینی — پاک‌سازی مدارک موقت
     */
    public function reject(int|string $id): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/revisions');
        $this->verifyCsrf();

        $id = (int)$id;
        $rev = $this->revisionModel->findById($id);
        if (!$rev || $rev['status'] !== 'در انتظار') {
            Session::set('revision_admin_message', 'درخواست یافت نشد یا پیش‌تر بررسی شده است.');
            $this->redirect('/admin/revisions');
        }
        $reason = trim((string)$this->input('reject_reason', ''));
        $aid = (int)$rev['applicant_id'];

        // حذف فایل‌ها و رکوردهای موقت مدارک
        foreach ($rev['payload_data']['docs'] ?? [] as $d) {
            $doc = $this->documentModel->findById((int)$d['doc_id']);
            if ($doc && $doc['doc_type'] === 'revision_doc') {
                $fp = realpath(UPLOAD_DIR . str_replace(['..', "\0"], '', (string)$doc['file_path']));
                $base = realpath(UPLOAD_DIR);
                if ($fp && $base && str_starts_with($fp, $base) && is_file($fp)) @unlink($fp);
                Database::getInstance()->getConnection()
                    ->prepare("DELETE FROM documents WHERE id = :id AND doc_type = 'revision_doc'")
                    ->execute(['id' => (int)$doc['id']]);
            }
        }

        $this->revisionModel->review($id, 'رد شده', (int)Session::get('admin_id', 0), $reason !== '' ? $reason : null);
        $this->statusLogModel->create([
            'applicant_id' => $aid,
            'status_name' => 'رد درخواست بازبینی',
            'description' => 'درخواست بازبینی پرونده رد شد' . ($reason !== '' ? ' — دلیل: ' . $reason : ''),
        ]);

        Session::set('revision_admin_message', 'درخواست بازبینی رد شد.');
        $this->redirect('/admin/revisions');
    }

    /* ==================== داخلی ==================== */

    private function checkBlocked(int $aid): ?string
    {
        if ($this->revisionModel->findPendingByApplicant($aid)) {
            return 'یک درخواست بازبینی در انتظار بررسی دارید؛ تا زمان بررسی، درخواست جدید ممکن نیست.';
        }
        if ($this->revisionModel->countByApplicantSince($aid, 7) >= 2) {
            return 'در ۷ روز گذشته ۲ درخواست بازبینی ثبت کرده‌اید؛ لطفاً بعداً تلاش کنید.';
        }
        return null;
    }

    private function findApplicantOrFail(): array
    {
        $code = strtoupper(trim((string)$this->input('tracking_code', '')));
        $applicant = $this->applicantModel->findByTrackingCode($code);
        if (!$applicant) {
            Session::set('tracking_error', 'کد رهگیری نامعتبر است.');
            $this->redirect('/tracking');
        }
        return $applicant;
    }

    private function dynFieldsForSteps(array $steps): array
    {
        $out = [];
        foreach ($steps as $st) {
            foreach ($this->fieldModel->activeByStep($st) as $f) {
                if ((int)$f['is_system'] === 0 && $f['field_type'] !== 'file') {
                    $out[] = $f;
                }
            }
        }
        return $out;
    }

    private function docTypeLabels(): array
    {
        return [
            'national_card' => 'کارت ملی', 'birth_cert' => 'شناسنامه', 'ownership_doc' => 'مدرک مالکیت',
            'vocational_cert' => 'گواهی فنی‌وحرفه‌ای', 'education_degree' => 'مدرک تحصیلی',
            'product_photos' => 'تصویر محصولات', 'optional' => 'سایر',
        ];
    }
}
