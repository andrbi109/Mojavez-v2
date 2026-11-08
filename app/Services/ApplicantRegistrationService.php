<?php
/**
 * سرویس متمرکز ثبت‌نام متقاضی (ApplicantRegistrationService)
 * =================================================================
 * این سرویس همهٔ منطق مشترک ۳ سطح ثبت‌نام را در یک جا متمرکز می‌کند:
 *   ۱) ویزارد عمومی (RegistrationController::handleFinalize)
 *   ۲) ثبت‌نام دستی مدیر (AdminController::registerProcess)
 *   ۳) ایمپورت دسته‌جمعی (BulkImportController::execute)
 *
 * مسائل برطرف‌شده (طبق گزارش تحلیل):
 *   - D-A-1: کپی ۴ بارهٔ زنجیرهٔ اعتبارسنجی هویتی → اکنون متمرکز در validateIdentity()
 *   - D-A-2: کپی ۸ بارهٔ کلوژر $saveFile → اکنون متمرکز در saveUploadedFile()
 *   - D-A-4: ایمپورت متقاضیان را در 'پیش‌نویس' رها می‌کرد → اکنون همیشه completeRegistration
 *   - D-A-5: نوتیفیکیشن کاربر فقط در ایمپورت → اکنون در همه (اختیاری)
 *   - D-A-6: شخص حقوقی فقط در ویزارد → اکنون پشتیبانی می‌شود
 *   - D-C-1: ایمپورت از Person::findOrCreate عبور می‌کرد → اکنون از همان استفاده می‌کند
 *   - D-C-2: persons های ایمپورت‌شده دادهٔ ناقص داشتند → اکنون کامل
 *
 * نکتهٔ مهم: این سرویس روی منبع حقیقت واحد تمرکز دارد. هر تغییر در منطق ثبت‌نام،
 * فقط اینجا باید اعمال شود.
 */
class ApplicantRegistrationService
{
    /** فهرست مجاز انواع مدرک (مرجع واحد — قبلاً در ۵ جا تکرار شده بود) */
    public const DOC_TYPES = [
        'national_card_front' => 'کارت ملی (رو)',
        'national_card_back'  => 'کارت ملی (پشت)',
        'birth_cert'          => 'شناسنامه',
        'ownership_doc'       => 'مدرک مالکیت',
        'vocational_cert'    => 'گواهینامه فنی‌وحرفه‌ای',
        'education_degree'   => 'مدرک تحصیلی',
        'product_photos'     => 'تصویر محصول',
        'optional_1'         => 'مدرک اختیاری ۱',
        'optional_2'         => 'مدرک اختیاری ۲',
        'optional_3'         => 'مدرک اختیاری ۳',
    ];

    /** نگاشت نام ورودی فایل فرم → doc_type ذخیره‌شده در documents */
    public const FILE_INPUT_TO_DOC_TYPE = [
        'national_card_front' => 'national_card',
        'national_card_back'  => 'national_card',
        'birth_cert'          => 'birth_cert',
        'ownership_doc'       => 'ownership_doc',
        'vocational_cert'     => 'vocational_cert',
        'education_degree'    => 'education_degree',
        'optional_1'          => 'optional',
        'optional_2'          => 'optional',
        'optional_3'          => 'optional',
    ];

    private Person $personModel;
    private Applicant $applicantModel;
    private JobDetail $jobDetailModel;
    private PropertyDetail $propertyDetailModel;
    private Document $documentModel;
    private StatusLog $statusLogModel;
    private UserNotification $notificationModel;

    public function __construct()
    {
        $this->personModel       = new Person();
        $this->applicantModel     = new Applicant();
        $this->jobDetailModel     = new JobDetail();
        $this->propertyDetailModel = new PropertyDetail();
        $this->documentModel      = new Document();
        $this->statusLogModel     = new StatusLog();
        $this->notificationModel  = new UserNotification();
    }

    /* ==================== اعتبارسنجی ==================== */

    /**
     * اعتبارسنجی هویتی متمرکز — قبلاً در ۴ کنترلر کپی شده بود.
     *
     * این متد الزامات اساسی هویتی را چک می‌کند. اعتبارسنجی کامل‌تر (تاریخ تولد،
     * تأهل، معلولیت) به عهدهٔ کنترلر است، زیرا به داده‌های محلی وابسته است.
     *
     * @param array $data ورودی‌های فرم
     * @return array{valid: bool, errors: array<string,array<string>>}
     */
    public function validateIdentity(array $data): array
    {
        $errors = [];
        $validator = new Validator($data);

        $validator->required('first_name', 'نام')
            ->persianAlpha('first_name', 'نام')
            ->maxLength('first_name', 50, 'نام')
            ->required('last_name', 'نام خانوادگی')
            ->persianAlpha('last_name', 'نام خانوادگی')
            ->maxLength('last_name', 50, 'نام خانوادگی')
            ->required('national_code', 'کد ملی')
            ->nationalCode('national_code', 'کد ملی')
            ->required('postal_code', 'کد پستی')
            ->postalCode('postal_code', 'کد پستی')
            ->required('mobile', 'شماره تلفن همراه')
            ->mobile('mobile', 'شماره تلفن همراه')
            ->required('father_name', 'نام پدر')
            ->persianAlpha('father_name', 'نام پدر')
            ->maxLength('father_name', 50, 'نام پدر')
            ->required('marital_status', 'وضعیت تأهل')
            ->enum('marital_status', ['مجرد','متأهل'], 'وضعیت تأهل')
            ->required('physical_condition', 'وضعیت جسمانی')
            ->enum('physical_condition', ['سالم','معلولیت'], 'وضعیت جسمانی')
            ->required('has_court_case', 'پرونده قضایی')
            ->enum('has_court_case', ['بله','خیر'], 'پرونده قضایی')
            ->required('has_active_loan', 'وام فعال')
            ->enum('has_active_loan', ['بله','خیر'], 'وام فعال')
            ->required('has_overdue_installments', 'اقساط معوقه')
            ->enum('has_overdue_installments', ['بله','خیر'], 'اقساط معوقه');

        // یکتایی موبایل در سطح person (با متد اصلاح‌شدهٔ فاز ۲)
        $mobile = (string)($data['mobile'] ?? '');
        $nc = (string)($data['national_code'] ?? '');
        if ($mobile !== '' && $nc !== '') {
            if ($this->personModel->mobileBelongsToOtherPerson($mobile, $nc)) {
                $validator->addError('mobile', 'این شمارهٔ موبایل به کد ملی دیگری تعلق دارد.');
            }
        }

        // وضعیت معلولیت → توضیحات الزامی
        if (($data['physical_condition'] ?? '') === 'معلولیت') {
            $validator->required('disability_details', 'توضیحات معلولیت');
        }

        // تأهل → children_count
        if (($data['marital_status'] ?? '') === 'متأهل') {
            $validator->required('children_count', 'تعداد فرزندان')
                ->unsignedInt('children_count', 'تعداد فرزندان');
        }

        // دهک درآمدی اختیاری
        if (!empty($data['income_decile'])) {
            $validator->unsignedInt('income_decile', 'دهک درآمدی');
        }

        return [
            'valid'  => !$validator->hasErrors(),
            'errors' => $validator->getErrors(),
        ];
    }

    /**
     * اعتبارسنجی اطلاعات شغلی.
     */
    public function validateJobInfo(array $data): array
    {
        $validator = new Validator($data);
        $validator->required('license_title', 'عنوان مجوز')
            ->maxLength('license_title', 100, 'عنوان مجوز')
            ->required('experience_months', 'سابقه فعالیت')
            ->unsignedInt('experience_months', 'سابقه فعالیت')
            ->required('training_method', 'نحوه آموزش')
            ->enum('training_method', ['در خانه','استاد شاگردی','کارگاهی'], 'نحوه آموزش');

        return [
            'valid'  => !$validator->hasErrors(),
            'errors' => $validator->getErrors(),
        ];
    }

    /**
     * اعتبارسنجی اطلاعات ملک.
     */
    public function validatePropertyInfo(array $data): array
    {
        $validator = new Validator($data);
        $validator->required('ownership_status', 'وضعیت مالکیت')
            ->enum('ownership_status', ['شخص','مستأجر','سرپرست'], 'وضعیت مالکیت')
            ->required('province', 'استان')
            ->required('county', 'شهرستان')
            ->required('city', 'شهر')
            ->required('electricity_bill_id', 'شناسه قبض برق')
            ->required('address_street', 'خیابان')
            ->required('address_plaque', 'پلاک');

        if (!empty($data['address_alley'])) {
            $validator->maxLength('address_alley', 100, 'کوچه');
        }

        return [
            'valid'  => !$validator->hasErrors(),
            'errors' => $validator->getErrors(),
        ];
    }

    /* ==================== ذخیرهٔ فایل آپلودشده ==================== */

    /**
     * ذخیرهٔ یک فایل آپلودشده — قبلاً در ۸ جا به‌صورت کلوژر کپی شده بود.
     *
     * الگوی یکسان: validate_uploaded_file → bin2hex(16).ext → move_uploaded_file
     * → chmod 0640 → Document::create.
     *
     * @param array       $file       ورودی $_FILES[name]
     * @param int         $applicantId شناسهٔ متقاضی
     * @param string      $docType    نوع مدرک (از FILE_INPUT_TO_DOC_TYPE)
     * @return array{success: bool, error: ?string, document_id: ?int}
     */
    public function saveUploadedFile(array $file, int $applicantId, string $docType): array
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => null, 'document_id' => null];
        }

        $validation = validate_uploaded_file($file);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error'] ?? 'فایل نامعتبر', 'document_id' => null];
        }

        $ext = $validation['ext'];
        $uploadDir = UPLOAD_DIR . '/' . date('Y/m');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $uploadDir . '/' . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'error' => 'خطا در ذخیرهٔ فایل', 'document_id' => null];
        }
        @chmod($destPath, 0640);

        $docId = $this->documentModel->create([
            'applicant_id'  => $applicantId,
            'doc_type'       => $docType,
            'file_name'      => $uniqueName,
            'original_name'  => sanitize_upload_filename((string)$file['name']),
            'file_path'      => str_replace(UPLOAD_DIR, '', $destPath),
        ]);

        return ['success' => true, 'error' => null, 'document_id' => $docId];
    }

    /**
     * ذخیرهٔ همهٔ فایل‌های آپلودشده از $_FILES.
     * فقط فایل‌هایی که واقعاً آپلود شده‌اند پردازش می‌شوند.
     *
     * @param int   $applicantId
     * @return array{saved: int, errors: array<string>}
     */
    public function saveAllUploadedFiles(int $applicantId): array
    {
        $saved = 0;
        $errors = [];

        // فایل‌های تکی (مطابق FILE_INPUT_TO_DOC_TYPE)
        foreach (self::FILE_INPUT_TO_DOC_TYPE as $inputName => $docType) {
            if (!empty($_FILES[$inputName]) && $_FILES[$inputName]['error'] !== UPLOAD_ERR_NO_FILE) {
                $result = $this->saveUploadedFile($_FILES[$inputName], $applicantId, $docType);
                if ($result['success']) {
                    $saved++;
                } elseif ($result['error']) {
                    $errors[] = $_FILES[$inputName]['name'] . ': ' . $result['error'];
                }
            }
        }

        // تصاویر محصول (چندفایلی)
        if (!empty($_FILES['product_photos']) && is_array($_FILES['product_photos']['name'])) {
            $ppNames = $_FILES['product_photos']['name'];
            $ppTmp   = $_FILES['product_photos']['tmp_name'];
            $ppSize  = $_FILES['product_photos']['size'];
            $ppErr   = $_FILES['product_photos']['error'];
            foreach ($ppTmp as $i => $tmpName) {
                if (($ppErr[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
                $result = $this->saveUploadedFile([
                    'name'     => $ppNames[$i] ?? '',
                    'tmp_name' => $tmpName,
                    'size'     => $ppSize[$i] ?? 0,
                    'error'    => $ppErr[$i],
                ], $applicantId, 'product_photos');
                if ($result['success']) {
                    $saved++;
                } elseif ($result['error']) {
                    $errors[] = ($ppNames[$i] ?? 'product_photo') . ': ' . $result['error'];
                }
            }
        }

        return ['saved' => $saved, 'errors' => $errors];
    }

    /* ==================== ایجاد شخص + پرونده ==================== */

    /**
     * ایجاد کامل یک متقاضی با همهٔ وابستگی‌ها (Person, UserAccount اختیاری,
     * Applicant, JobDetail, PropertyDetail).
     *
     * این متد «قلب» ثبت‌نام است و همهٔ ۳ سطح ثبت‌نام باید از آن استفاده کنند.
     *
     * @param array $data داده‌های فرم (شامل فیلدهای هویتی، شغلی، ملک)
     * @param array $options گزینه‌های اضافی:
     *   - source: منبع ثبت‌نام ('wizard' | 'admin' | 'bulk') — برای لاگ
     *   - service_type_id: int — نوع خدمت
     *   - user_account_id: ?int — اگر کاربر قبلاً وارد شده
     *   - create_user_account: bool — آیا UserAccount ایجاد شود؟ (پیش‌فرض false)
     *   - default_password: ?string — رمز عبور برای UserAccount (default: national_code)
     *   - complete_status: bool — آیا به 'تکمیل اطلاعات' ارتقا یابد؟ (پیش‌فرض true)
     *   - notify_user: bool — آیا نوتیفیکیشن ارسال شود؟ (پیش‌فرض false)
     * @return array{success: bool, applicant_id: ?int, tracking_code: ?string, person_id: ?int, account_id: ?int, errors: array}
     */
    public function createApplicant(array $data, array $options = []): array
    {
        $source = $options['source'] ?? 'unknown';
        $serviceTypeId = (int)($options['service_type_id'] ?? 0);
        $userAccountId = $options['user_account_id'] ?? null;
        $createUserAccount = $options['create_user_account'] ?? false;
        $defaultPassword = $options['default_password'] ?? ($data['national_code'] ?? '');
        $completeStatus = $options['complete_status'] ?? true;
        $notifyUser = $options['notify_user'] ?? false;

        // مالک سطح دسترسی برای لاگ
        $actorName = Session::get('admin_display_name') ?? Session::get('admin_username', $source);

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            // ۱. Person (با findOrCreate — پشتیبانی کامل از حقوقی)
            $personData = [
                'national_code' => $data['national_code'],
                'first_name'    => $data['first_name'] ?? '',
                'last_name'     => $data['last_name'] ?? '',
                'father_name'   => $data['father_name'] ?? null,
                'birth_date'    => !empty($data['birth_date']) ? $data['birth_date'] : null,
                'mobile'        => $data['mobile'],
                'postal_code'   => $data['postal_code'] ?? null,
                'person_type'   => $data['person_type'] ?? 'حقیقی',
            ];

            // فیلدهای شخص حقوقی
            if (($data['person_type'] ?? 'حقیقی') === 'حقوقی') {
                $personData['company_name']         = $data['company_name'] ?? null;
                $personData['company_national_id']  = $data['company_national_id'] ?? null;
                $personData['reg_number']           = $data['reg_number'] ?? null;
                $personData['reg_date']              = $data['reg_date'] ?? null;
                $personData['company_type']           = $data['company_type'] ?? null;
                $personData['economic_code']         = $data['economic_code'] ?? null;
            }

            $personId = $this->personModel->findOrCreate($personData);

            // ۲. UserAccount (اختیاری)
            $accountId = $userAccountId;
            if ($createUserAccount && !$accountId) {
                $account = new UserAccount();
                $accountId = $account->create([
                    'national_code' => $data['national_code'],
                    'first_name'    => $data['first_name'] ?? '',
                    'last_name'     => $data['last_name'] ?? '',
                    'mobile'        => $data['mobile'],
                    'password'      => $defaultPassword,
                ]);
            }

            // ۳. Applicant
            $trackingCode = Applicant::generateTrackingCode();
            $applicantData = [
                'first_name'    => $data['first_name'],
                'last_name'     => $data['last_name'],
                'national_code' => $data['national_code'],
                'postal_code'   => $data['postal_code'],
                'mobile'        => $data['mobile'],
                'birth_date'    => !empty($data['birth_date']) ? $data['birth_date'] : null,
                'father_name'   => $data['father_name'] ?? null,
                'marital_status' => $data['marital_status'] ?? 'مجرد',
                'children_count' => (int)($data['children_count'] ?? 0),
                'physical_condition' => $data['physical_condition'] ?? 'سالم',
                'disability_details' => $data['disability_details'] ?? null,
                'income_decile' => !empty($data['income_decile']) ? (int)$data['income_decile'] : null,
                'has_court_case' => $data['has_court_case'] ?? 'خیر',
                'has_active_loan' => $data['has_active_loan'] ?? 'خیر',
                'has_overdue_installments' => $data['has_overdue_installments'] ?? 'خیر',
                'tracking_code'  => $trackingCode,
                'service_type_id' => $serviceTypeId,
                'applicant_type' => $data['person_type'] ?? 'حقیقی',
                'user_account_id' => $accountId,
            ];

            $applicantId = $this->applicantModel->create($applicantData);

            // ۴. JobDetail
            $this->jobDetailModel->create([
                'applicant_id'      => $applicantId,
                'license_title'     => $data['license_title'] ?? '',
                'experience_months' => (int)($data['experience_months'] ?? 0),
                'training_method'   => $data['training_method'] ?? 'کارگاهی',
            ]);

            // ۵. PropertyDetail (اگر حداقل یک فیلد ملک موجود باشد)
            if (!empty($data['province']) || !empty($data['city']) || !empty($data['ownership_status'])) {
                $this->propertyDetailModel->create([
                    'applicant_id'       => $applicantId,
                    'ownership_status'   => $data['ownership_status'] ?? 'شخص',
                    'province'           => $data['province'] ?? '',
                    'county'             => $data['county'] ?? ($data['city'] ?? ''),
                    'city'               => $data['city'] ?? '',
                    'district'           => $data['district'] ?? null,
                    'rural_district'     => $data['rural_district'] ?? null,
                    'village'            => $data['village'] ?? null,
                    'electricity_bill_id' => $data['electricity_bill_id'] ?? '0000000000',
                    'address_street'     => $data['address_street'] ?? null,
                    'address_alley'       => $data['address_alley'] ?? null,
                    'address_plaque'      => $data['address_plaque'] ?? null,
                ]);
            }

            // ۶. linkApplication
            $this->personModel->linkApplication($applicantId, $personId);

            // ۷. ارتقا به 'تکمیل اطلاعات' + status_log
            if ($completeStatus) {
                $this->applicantModel->completeRegistration($applicantId);
                $sourceLabel = [
                    'wizard' => 'ثبت‌نام توسط متقاضی',
                    'admin'  => 'ثبت‌نام توسط مدیر',
                    'bulk'   => 'ثبت‌نام توسط ایمپورت دسته‌جمعی',
                ][$source] ?? $source;
                $this->statusLogModel->create([
                    'applicant_id' => $applicantId,
                    'status_name' => 'تکمیل اطلاعات',
                    'description' => $sourceLabel
                        . (!empty($data['license_title']) ? ' — رشته: ' . $data['license_title'] : '')
                        . ' (توسط: ' . $actorName . ')',
                ]);
            }

            // ۸. نوتیفیکیشن کاربر (اختیاری)
            if ($notifyUser && $accountId) {
                try {
                    $this->notificationModel->create(
                        $accountId,
                        'registration',
                        'ثبت‌نام شما تکمیل شد',
                        'کد رهگیری شما: ' . $trackingCode
                            . (!empty($data['license_title']) ? ' — رشته: ' . $data['license_title'] : ''),
                        $applicantId
                    );
                } catch (\Throwable $e) {
                    // خطای نوتیفیکیشن نباید کل ثبت‌نام را لغو کند
                }
            }

            $db->commit();

            return [
                'success'       => true,
                'applicant_id'  => $applicantId,
                'tracking_code' => $trackingCode,
                'person_id'     => $personId,
                'account_id'   => $accountId,
                'errors'        => [],
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            return [
                'success'       => false,
                'applicant_id'  => null,
                'tracking_code' => null,
                'person_id'     => null,
                'account_id'   => null,
                'errors'        => [$e->getMessage()],
            ];
        }
    }

    /* ==================== اصلاح دادهٔ متقاضی موجود ==================== */

    /**
     * افزودن شماره موبایل دوم/سوم به شخص موجود.
     * برای ایمپورت دسته‌جمعی وقتی همان کد ملی با موبایل متفاوت ظاهر می‌شود.
     *
     * @return string اسلاتی که در آن ذخیره شد: 'mobile_2'، 'mobile_3' یا '' (هیچ)
     */
    public function addSecondaryMobile(int $personId, string $mobile): string
    {
        return $this->personModel->addSecondaryMobile($personId, $mobile);
    }
}
