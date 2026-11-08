<?php
/**
 * کنترلر ایمپورت دسته‌جمعی (BulkImportController)
 * ---------------------------------------------------
 * ایمپورت کاربران از فایل CSV با انتخاب نوع خدمت + رشته مجوز.
 *
 * جریان:
 *   ۱) آپلود فایل CSV + انتخاب نوع خدمت + رشته
 *   ۲) پیش‌نمایش + شکستن نام کامل + نگاشت ستون‌ها + تیک
 *   ۳) اجرای ایمپورت (تراکنش)
 *   ۴) گزارش نتیجه
 *   ۵) بارگذاری دستی مدارک
 */
class BulkImportController extends Controller
{
    /* ==================== ۱) آپلود ==================== */

    public function index(): void
    {
        $this->requireRole(['admin']);
        $services = (new ServiceType())->getActive();
        $licenseGroups = (new LicenseTitle())->groupedActive();

        $this->render('admin/bulk_import', [
            'title' => 'ایمپورت دسته‌جمعی',
            'services' => $services,
            'licenseGroups' => $licenseGroups,
            'currentPage' => 'bulk-import',
            'error' => Session::get('bulk_error'),
        ], 'admin_layout');
        Session::remove('bulk_error');
    }

    public function upload(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/bulk-import');
        $this->verifyCsrf();

        $serviceTypeId = (int)$this->input('service_type_id', 0);
        $licenseTitle = (string)$this->input('license_title', '');

        if ($serviceTypeId <= 0 || $licenseTitle === '') {
            Session::set('bulk_error', 'انتخاب نوع خدمت و رشته الزامی است.');
            $this->redirect('/admin/bulk-import');
        }

        $file = $_FILES['csv_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::set('bulk_error', 'لطفاً یک فایل CSV انتخاب کنید.');
            $this->redirect('/admin/bulk-import');
        }

        // اعتبارسنجی فایل
        // فایل CSV فقط خوانده می‌شود — از validate_uploaded_file استفاده نمی‌کنیم
        // چون آن تابع فقط jpg/jpeg/png/pdf را مجاز می‌داند
        $ext = safe_file_extension((string)$file['name']);
        if ($ext !== 'csv') {
            Session::set('bulk_error', 'فقط فایل CSV پشتیبانی می‌شود. پسوند فایل شما: ' . $ext);
            $this->redirect('/admin/bulk-import');
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            Session::set('bulk_error', 'حداکثر حجم فایل ۵ مگابایت است.');
            $this->redirect('/admin/bulk-import');
        }

        // خواندن فایل CSV
        $rows = [];
        $headers = [];
        $handle = fopen($file['tmp_name'], 'r');

        // BOM حذف
        $firstLine = fgets($handle);
        if ($firstLine !== false) {
            $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
            $headers = str_getcsv($firstLine, ',', '"', '\\');
            if ($headers === false) $headers = [];
        }

        $lineCount = 0;
        while (($line = fgets($handle)) !== false && $lineCount < 1000) {
            $row = str_getcsv($line, ',', '"', '\\');
            if ($row === false || (count($row) === 1 && $row[0] === '')) continue;
            $rows[] = $row;
            $lineCount++;
        }
        fclose($handle);

        if (empty($rows)) {
            Session::set('bulk_error', 'فایل خالی است یا هیچ ردیفی خوانده نشد.');
            $this->redirect('/admin/bulk-import');
        }

        if (count($rows) > 1000) {
            Session::set('bulk_error', 'حداکثر ۱۰۰۰ ردیف مجاز است.');
            $this->redirect('/admin/bulk-import');
        }

        // ذخیره در نشست
        Session::set('bulk_rows', $rows);
        Session::set('bulk_headers', $headers);
        Session::set('bulk_service_type_id', $serviceTypeId);
        Session::set('bulk_license_title', $licenseTitle);
        Session::set('bulk_default_password', (string)$this->input('default_password', 'national_code'));
        Session::set('bulk_fixed_password', (string)$this->input('fixed_password', ''));

        $this->redirect('/admin/bulk-import/preview');
    }

    /* ==================== ۲) پیش‌نمایش ==================== */

    public function preview(): void
    {
        $this->requireRole(['admin']);

        $rows = Session::get('bulk_rows', []);
        $headers = Session::get('bulk_headers', []);
        $serviceTypeId = (int)Session::get('bulk_service_type_id', 0);
        $licenseTitle = (string)Session::get('bulk_license_title', '');

        if (empty($rows)) {
            Session::set('bulk_error', 'داده‌ای برای پیش‌نمایش وجود ندارد. فایل را مجدداً آپلود کنید.');
            $this->redirect('/admin/bulk-import');
        }

        // تشخیص خودکار هدرها
        $autoDetect = [
            'نام کامل' => 'full_name', 'نام و نام خانوادگی' => 'full_name',
            'نام' => 'first_name', 'نام کوچک' => 'first_name',
            'نام خانوادگی' => 'last_name', 'فامیل' => 'last_name',
            'کد ملی' => 'national_code', 'شناسنامه' => 'national_code',
            'موبایل' => 'mobile', 'تلفن همراه' => 'mobile', 'شماره تماس' => 'mobile',
            'کد پستی' => 'postal_code',
            'استان' => 'province',
            'شهر' => 'city',
            'روستا' => 'village',
            'نام پدر' => 'father_name',
            'تاریخ تولد' => 'birth_date',
            'دهک درآمدی' => 'income_decile',
        ];

        $autoMapping = [];
        foreach ($headers as $i => $header) {
            $header = trim($header);
            $autoMapping[$i] = $autoDetect[$header] ?? '';
        }

        // اعتبارسنجی ردیف‌ها
        $validatedRows = [];
        foreach ($rows as $idx => $row) {
            $rowData = [];
            foreach ($autoMapping as $colIdx => $field) {
                if ($field && isset($row[$colIdx])) {
                    $rowData[$field] = trim($row[$colIdx]);
                }
            }

            // شکستن نام کامل
            if (!empty($rowData['full_name']) && empty($rowData['first_name'])) {
                $split = split_full_name($rowData['full_name']);
                $rowData['first_name'] = $split['first_name'];
                $rowData['last_name'] = $split['last_name'];
                unset($rowData['full_name']);
            }

            // اعتبارسنجی
            $status = 'valid'; // valid, duplicate, invalid
            $errors = [];

            // کد ملی: فقط ۱۰ رقم کافی است (بدون بررسی رقم کنترل برای ایمپورت دسته‌جمعی)
            $nc = trim($rowData['national_code'] ?? '');
            // حذف صفرهای اضافی از ابتدا (اگر بیشتر از ۱۰ رقم باشد)
            $nc = preg_replace('/[^0-9]/', '', $nc);
            if (strlen($nc) > 10) {
                $nc = substr($nc, -10); // ۱۰ رقم آخر
            } elseif (strlen($nc) < 10 && strlen($nc) > 0) {
                $nc = str_pad($nc, 10, '0', STR_PAD_LEFT); // صفرپَد از ابتدا
            }
            $rowData['national_code'] = $nc;

            if (!preg_match('/^\d{10}$/', $nc)) {
                $status = 'invalid';
                $errors[] = 'کد ملی نامعتبر (باید ۱۰ رقم باشد)';
            }

            // موبایل: خودکار اصلاح شود
            $mb = trim($rowData['mobile'] ?? '');
            $mb = preg_replace('/[^0-9]/', '', $mb); // فقط اعداد
            // اگر با 9 شروع شده و ۱۰ رقم است → 0 اضافه کن
            if (strlen($mb) === 10 && str_starts_with($mb, '9')) {
                $mb = '0' . $mb;
            }
            // اگر ۱۱ رقم و با 09 شروع شده → OK
            // اگر ۱۲ رقم و با 09 شروع شده → ۰ اضافی حذف
            if (strlen($mb) === 12 && str_starts_with($mb, '09')) {
                $mb = substr($mb, 0, 11);
            }
            $rowData['mobile'] = $mb;

            if (!preg_match('/^09\d{9}$/', $mb)) {
                $status = 'invalid';
                $errors[] = 'موبایل نامعتبر (' . $mb . ')';
            }

            if (empty($rowData['first_name'] ?? '')) {
                $status = 'invalid';
                $errors[] = 'نام خالی';
            }

            // بررسی تکراری (فقط اگر معتبر باشد)
            if ($status === 'valid') {
                try {
                    $personModel = new Person();
                    $existingPerson = $personModel->findByNationalCode($nc);
                    if ($existingPerson) {
                        $status = 'duplicate';
                        $errors[] = 'کد ملی موجود';
                    }
                    $userModel = new UserAccount();
                    $existingUser = $userModel->findByMobile($mb);
                    if ($existingUser) {
                        $status = 'duplicate';
                        $errors[] = 'موبایل موجود';
                    }
                } catch (\Throwable $e) {}
            }

            $validatedRows[] = [
                'idx' => $idx,
                'data' => $rowData,
                'status' => $status,
                'errors' => $errors,
                'original' => $row,
            ];
        }

        $services = (new ServiceType())->getActive();
        $licenseGroups = (new LicenseTitle())->groupedActive();

        $this->render('admin/bulk_preview', [
            'title' => 'پیش‌نمایش ایمپورت',
            'rows' => $validatedRows,
            'headers' => $headers,
            'autoMapping' => $autoMapping,
            'serviceTypeId' => $serviceTypeId,
            'licenseTitle' => $licenseTitle,
            'services' => $services,
            'licenseGroups' => $licenseGroups,
            'currentPage' => 'bulk-import',
        ], 'admin_layout');
    }

    /* ==================== ۳) اجرای ایمپورت ==================== */

    public function execute(): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/bulk-import');
        $this->verifyCsrf();

        $selectedIndices = $_POST['selected_rows'] ?? [];
        if (!is_array($selectedIndices)) $selectedIndices = [];

        $rows = Session::get('bulk_rows', []);
        $mapping = $_POST['mapping'] ?? [];
        $serviceTypeId = (int)$this->input('service_type_id', 0);
        $licenseTitle = (string)$this->input('license_title', '');
        $passwordStrategy = (string)$this->input('default_password', 'national_code');
        $fixedPassword = (string)$this->input('fixed_password', '');

        if (empty($selectedIndices)) {
            Session::set('bulk_error', 'هیچ ردیفی انتخاب نشده است.');
            $this->redirect('/admin/bulk-import/preview');
        }

        $personModel = new Person();
        $userModel = new UserAccount();
        $applicantModel = new Applicant();
        $jobModel = new JobDetail();
        $notifModel = new UserNotification();

        $db = Database::getInstance()->getConnection();
        $results = [];

        try {
            $db->beginTransaction();

            foreach ($selectedIndices as $idx) {
                $idx = (int)$idx;
                $row = $rows[$idx] ?? null;
                if (!$row) continue;

                // نگاشت ستون‌ها
                $data = [];
                foreach ($mapping as $colIdx => $field) {
                    if ($field && isset($row[$colIdx])) {
                        $data[$field] = trim($row[$colIdx]);
                    }
                }

                // شکستن نام کامل
                if (!empty($data['full_name']) && empty($data['first_name'])) {
                    $split = split_full_name($data['full_name']);
                    $data['first_name'] = $split['first_name'];
                    $data['last_name'] = $split['last_name'];
                }

                $nc = $data['national_code'] ?? '';
                $mb = $data['mobile'] ?? '';
                $fn = $data['first_name'] ?? '';
                $ln = $data['last_name'] ?? '';

                // بررسی مجدد یکتایی
                $existingPerson = $personModel->findByNationalCode($nc);
                if ($existingPerson) {
                    $results[] = ['idx' => $idx, 'name' => $fn . ' ' . $ln, 'national_code' => $nc, 'mobile' => $mb, 'status' => 'duplicate', 'message' => 'کد ملی موجود است'];
                    continue;
                }
                $existingUser = $userModel->findByMobile($mb);
                if ($existingUser) {
                    $results[] = ['idx' => $idx, 'name' => $fn . ' ' . $ln, 'national_code' => $nc, 'mobile' => $mb, 'status' => 'duplicate', 'message' => 'موبایل موجود است'];
                    continue;
                }

                // رمز عبور
                $password = $passwordStrategy === 'fixed' ? $fixedPassword : $nc;

                // ۱. Person
                $personId = $personModel->create([
                    'national_code' => $nc,
                    'first_name' => $fn,
                    'last_name' => $ln,
                    'mobile' => $mb,
                    'postal_code' => $data['postal_code'] ?? null,
                ]);

                // ۲. UserAccount
                $accountId = $userModel->create([
                    'national_code' => $nc,
                    'first_name' => $fn,
                    'last_name' => $ln,
                    'mobile' => $mb,
                    'password' => $password,
                ]);

                // ۳. Applicant
                $trackingCode = Applicant::generateTrackingCode();
                $applicantId = $applicantModel->create([
                    'first_name' => $fn,
                    'last_name' => $ln,
                    'national_code' => $nc,
                    'postal_code' => $data['postal_code'] ?? null,
                    'mobile' => $mb,
                    'tracking_code' => $trackingCode,
                    'service_type_id' => $serviceTypeId,
                    'user_account_id' => $accountId,
                    'income_decile' => $data['income_decile'] ?? null,
                    'father_name' => $data['father_name'] ?? null,
                ]);

                // ۴. JobDetail
                $jobModel->create([
                    'applicant_id' => $applicantId,
                    'license_title' => $licenseTitle,
                    'experience_months' => 0,
                    'training_method' => 'کارگاهی',
                ]);

                // ۵. PropertyDetail (اگر آدرس موجود)
                if (!empty($data['province']) || !empty($data['city'])) {
                    (new PropertyDetail())->create([
                        'applicant_id' => $applicantId,
                        'ownership_status' => 'شخص',
                        'province' => $data['province'] ?? null,
                        'county' => $data['city'] ?? null,
                        'city' => $data['city'] ?? null,
                        'electricity_bill_id' => '0000000000',
                        'village' => $data['village'] ?? null,
                    ]);
                }

                // ۶. linkApplication
                $personModel->linkApplication($applicantId, $personId);

                // ۷. اعلان
                try {
                    $notifModel->create($accountId, 'registration',
                        'ثبت‌نام شما تکمیل شد',
                        'کد رهگیری شما: ' . $trackingCode . ' — رشته: ' . $licenseTitle,
                        $applicantId
                    );
                } catch (\Throwable $e) {}

                $results[] = [
                    'idx' => $idx,
                    'name' => $fn . ' ' . $ln,
                    'national_code' => $nc,
                    'mobile' => $mb,
                    'tracking_code' => $trackingCode,
                    'password' => $password,
                    'status' => 'success',
                ];
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::set('bulk_error', 'خطا در اجرای ایمپورت: ' . $e->getMessage());
            $this->redirect('/admin/bulk-import/preview');
        }

        // پاکسازی نشست
        Session::remove('bulk_rows');
        Session::remove('bulk_headers');

        Session::set('bulk_results', $results);
        $this->redirect('/admin/bulk-import/result');
    }

    /* ==================== ۴) گزارش نتیجه ==================== */

    public function result(): void
    {
        $this->requireRole(['admin']);
        $results = Session::get('bulk_results', []);
        Session::remove('bulk_results');

        $successCount = count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'success'));
        $duplicateCount = count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'duplicate'));
        $errorCount = count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'error'));

        $this->render('admin/bulk_result', [
            'title' => 'گزارش ایمپورت',
            'results' => $results,
            'successCount' => $successCount,
            'duplicateCount' => $duplicateCount,
            'errorCount' => $errorCount,
            'currentPage' => 'bulk-import',
        ], 'admin_layout');
    }

    /* ==================== ۵) لیست نیازمند مدرک ==================== */

    public function missingDocs(): void
    {
        $this->requireRole(['admin']);
        $db = Database::getInstance()->getConnection();

        // یافتن پرونده‌های بدون مدرک
        $stmt = $db->query(
            "SELECT a.id, a.tracking_code, a.first_name, a.last_name, a.national_code,
                    a.service_type_id, st.title AS service_title,
                    (SELECT COUNT(*) FROM documents d WHERE d.applicant_id = a.id) AS doc_count
             FROM applicants a
             LEFT JOIN service_types st ON st.id = a.service_type_id
             ORDER BY a.id DESC
             LIMIT 500"
        );
        $applicants = $stmt->fetchAll();

        $this->render('admin/bulk_missing_docs', [
            'title' => 'کاربران نیازمند مدرک',
            'applicants' => $applicants,
            'currentPage' => 'bulk-import',
        ], 'admin_layout');
    }

    /* ==================== ۶) بارگذاری مدارک ==================== */

    public function uploadDocs(string $code): void
    {
        $this->requireRole(['admin']);
        $applicant = (new Applicant())->findByTrackingCode($code);
        if (!$applicant) {
            Session::set('bulk_error', 'کد رهگیری یافت نشد.');
            $this->redirect('/admin/bulk-import/missing-docs');
        }

        $documentModel = new Document();
        $existingDocs = $documentModel->findByApplicantId((int)$applicant['id']);

        // گروه‌بندی مدارک موجود بر اساس doc_type
        $docsByType = [];
        foreach ($existingDocs as $doc) {
            $docsByType[$doc['doc_type']][] = $doc;
        }

        $this->render('admin/bulk_upload_docs', [
            'title' => 'بارگذاری مدارک: ' . $code,
            'applicant' => $applicant,
            'docsByType' => $docsByType,
            'currentPage' => 'bulk-import',
        ], 'admin_layout');
    }

    public function saveDocs(string $code): void
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) $this->redirect('/admin/bulk-import/missing-docs');
        $this->verifyCsrf();

        $applicant = (new Applicant())->findByTrackingCode($code);
        if (!$applicant) {
            $this->redirect('/admin/bulk-import/missing-docs');
        }

        $applicantId = (int)$applicant['id'];
        $trackingCode = $applicant['tracking_code'];
        $documentModel = new Document();

        // انواع مدارک قابل آپلود
        $docTypes = [
            'national_card_front' => 'تصویر کارت ملی (رو)',
            'national_card_back' => 'تصویر کارت ملی (پشت)',
            'birth_cert' => 'تصویر شناسنامه',
            'ownership_doc' => 'مدرک مالکیت/اجاره',
            'vocational_cert' => 'گواهینامه فنی‌وحرفه‌ای',
            'education_degree' => 'مدرک تحصیلی',
            'product_photos' => 'تصاویر محصولات',
            'optional_1' => 'مدارک اختیاری ۱',
            'optional_2' => 'مدارک اختیاری ۲',
            'optional_3' => 'مدارک اختیاری ۳',
        ];

        $uploaded = 0;
        foreach ($docTypes as $docType => $label) {
            $file = $_FILES['doc_' . $docType] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) continue;

            $validation = validate_uploaded_file($file);
            if (!$validation['valid']) continue;

            $ext = $validation['ext'];
            $index = ($docType === 'product_photos' || $docType === 'optional_1' || $docType === 'optional_2' || $docType === 'optional_3')
                ? (count($_FILES) % 10) : 0;

            $uploadInfo = get_upload_path($trackingCode, $docType, $ext, $index);

            if (!move_uploaded_file($file['tmp_name'], $uploadInfo['path'])) continue;
            @chmod($uploadInfo['path'], 0640);

            $documentModel->create([
                'applicant_id' => $applicantId,
                'doc_type' => $docType,
                'file_name' => $uploadInfo['filename'],
                'original_name' => sanitize_upload_filename((string)$file['name']),
                'file_path' => $uploadInfo['relative_path'],
            ]);
            $uploaded++;
        }

        // تصاویر محصولات (چندفایلی)
        if (!empty($_FILES['product_photos'])) {
            $ppFiles = $_FILES['product_photos'];
            $ppCount = is_array($ppFiles['name']) ? count($ppFiles['name']) : 0;
            for ($i = 0; $i < $ppCount; $i++) {
                if ($ppFiles['error'][$i] !== UPLOAD_ERR_OK) continue;
                $tmpFile = [
                    'name' => $ppFiles['name'][$i],
                    'tmp_name' => $ppFiles['tmp_name'][$i],
                    'size' => $ppFiles['size'][$i],
                    'error' => $ppFiles['error'][$i],
                ];
                $validation = validate_uploaded_file($tmpFile);
                if (!$validation['valid']) continue;
                $ext = $validation['ext'];
                $uploadInfo = get_upload_path($trackingCode, 'product_photos', $ext, $i + 1);
                if (!move_uploaded_file($tmpFile['tmp_name'], $uploadInfo['path'])) continue;
                @chmod($uploadInfo['path'], 0640);
                $documentModel->create([
                    'applicant_id' => $applicantId,
                    'doc_type' => 'product_photos',
                    'file_name' => $uploadInfo['filename'],
                    'original_name' => sanitize_upload_filename((string)$tmpFile['name']),
                    'file_path' => $uploadInfo['relative_path'],
                ]);
                $uploaded++;
            }
        }

        Session::set('bulk_success', $uploaded . ' مدرک بارگذاری شد.');
        $this->redirect('/admin/bulk-import/documents/' . $code);
    }
}
