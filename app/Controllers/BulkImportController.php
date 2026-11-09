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
        // در ابتدای ایمپورت هنوز سرویسی انتخاب نشده است؛ فهرست خالی است
        // و پس از انتخاب سرویس توسط کاربر، توسط JS پر می‌شود.
        $licenseGroups = (new LicenseTitle())->groupedActiveByService(
            !empty($services) ? (int)$services[0]['id'] : null
        );

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

        if ($file['size'] > MAX_CSV_SIZE) {
            Session::set('bulk_error', 'حداکثر حجم فایل ' . (MAX_CSV_SIZE / 1024 / 1024) . ' مگابایت است.');
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

        // تشخیص خودکار هدرها — با تطبیق جزیی (partial match)
        $autoMapping = [];
        foreach ($headers as $i => $header) {
            $header = trim($header);
            $mapped = '';

            // تطبیق جزیی: اگر هدر شامل کلمات کلیدی باشد
            if ($header === '') { $mapped = ''; }
            elseif (mb_strpos($header, 'نام و نام خانوادگی') !== false || mb_strpos($header, 'نام کامل') !== false) { $mapped = 'full_name'; }
            elseif (mb_strpos($header, 'نام خانوادگی') !== false || $header === 'فامیل') { $mapped = 'last_name'; }
            elseif (mb_strpos($header, 'نام') !== false && mb_strpos($header, 'خانوادگی') === false && mb_strpos($header, 'کامل') === false) { $mapped = 'first_name'; }
            elseif (mb_strpos($header, 'ملی') !== false || mb_strpos($header, 'شناسنامه') !== false) { $mapped = 'national_code'; }
            elseif (mb_strpos($header, 'تماس') !== false || mb_strpos($header, 'موبایل') !== false || mb_strpos($header, 'تلفن') !== false || mb_strpos($header, 'همراه') !== false) { $mapped = 'mobile'; }
            elseif (mb_strpos($header, 'پستی') !== false) { $mapped = 'postal_code'; }
            elseif (mb_strpos($header, 'استان') !== false) { $mapped = 'province'; }
            elseif (mb_strpos($header, 'روستا') !== false) { $mapped = 'village'; }
            elseif (mb_strpos($header, 'شهر') !== false || mb_strpos($header, 'سکونت') !== false) { $mapped = 'city'; }
            elseif (mb_strpos($header, 'پدر') !== false) { $mapped = 'father_name'; }
            elseif (mb_strpos($header, 'تولد') !== false) { $mapped = 'birth_date'; }
            elseif (mb_strpos($header, 'دهک') !== false) { $mapped = 'income_decile'; }

            $autoMapping[$i] = $mapped;
        }

        // اعتبارسنجی ردیف‌ها
        $validatedRows = [];
        // پیگیری کد ملی‌های دیده‌شده در همین فایل (برای تشخیص تکرار درون‌فایلی)
        $seenInFile = []; // national_code => [first_idx, first_mobile, ...]

        // کش کردن نتایج بررسی DB برای جلوگیری از کوئری تکراری روی کد ملی‌های تکراری
        $dbPersonCache = [];   // nc => ?array
        $dbUserCache  = [];   // mobile => ?array

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

            // نرمال‌سازی کد ملی و موبایل — با توابع مشترک (همان نرمال‌سازی که در execute استفاده می‌شود)
            $nc = normalize_national_code((string)($rowData['national_code'] ?? ''));
            $mb = normalize_mobile((string)($rowData['mobile'] ?? ''));
            $rowData['national_code'] = $nc;
            $rowData['mobile'] = $mb;

            $status = 'valid';
            $errors = [];

            // اعتبارسنجی کد ملی
            if (!is_valid_national_code($nc)) {
                $status = 'invalid';
                $errors[] = 'کد ملی نامعتبر (باید ۱۰ رقم باشد)';
            }

            // اعتبارسنجی موبایل — این ردیف‌ها طبق درخواست skip می‌شوند
            if (!is_valid_mobile($mb)) {
                $status = 'invalid';
                $errors[] = 'موبایل نامعتبر';
            }

            if (empty($rowData['first_name'] ?? '')) {
                $status = 'invalid';
                $errors[] = 'نام خالی';
            }

            // بررسی تکراری در پایگاه داده (فقط اگر کد ملی/موبایل معتبر باشد)
            $duplicateInDb = false;
            if ($status === 'valid') {
                try {
                    $personModel = new Person();
                    if (!array_key_exists($nc, $dbPersonCache)) {
                        $dbPersonCache[$nc] = $personModel->findByNationalCode($nc);
                    }
                    if ($dbPersonCache[$nc]) {
                        $duplicateInDb = true;
                        $errors[] = 'کد ملی موجود';
                    }
                    $userModel = new UserAccount();
                    if (!array_key_exists($mb, $dbUserCache)) {
                        $dbUserCache[$mb] = $userModel->findByMobile($mb);
                    }
                    if ($dbUserCache[$mb]) {
                        $duplicateInDb = true;
                        $errors[] = 'موبایل موجود';
                    }
                } catch (\Throwable $e) {}
            }

            if ($duplicateInDb) {
                $status = 'duplicate';
            }

            // تشخیص تکرار درون‌فایلی: همان کد ملی قبلاً در همین CSV دیده شده و در DB موجود نیست
            // این ردیف‌ها در وضعیت «merge» قرار می‌گیرند — یعنی موبایلشان به شخص قبلی اضافه می‌شود.
            $mergeTargetIdx = null;
            if ($status === 'valid' && isset($seenInFile[$nc])) {
                $status = 'merge';
                $mergeTargetIdx = $seenInFile[$nc]['idx'];
                $errors[] = 'ادغام با ردیف ' . ($mergeTargetIdx + 1);
            } elseif ($status === 'valid') {
                $seenInFile[$nc] = ['idx' => $idx, 'mobile' => $mb];
            }

            $validatedRows[] = [
                'idx' => $idx,
                'data' => $rowData,
                'status' => $status,
                'errors' => $errors,
                'merge_target_idx' => $mergeTargetIdx,
                'original' => $row,
            ];
        }

        $services = (new ServiceType())->getActive();
        // در پیش‌نمایش، رشته‌ها بر اساس سرویس انتخاب‌شده فیلتر می‌شوند
        $licenseGroups = (new LicenseTitle())->groupedActiveByService(
            $serviceTypeId > 0 ? $serviceTypeId : null
        );

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

        // افزایش محدودیت زمان و حافظه برای ایمپورت حجیم (۳۰۰+ ردیف)
        // bcrypt روی هر رمز عبور ~۱۰۰ms طول می‌کشد؛ ۳۰۰ ردیف ≈ ۳۰ ثانیه فقط برای هش
        @set_time_limit(0);            // بدون محدودیت زمان اجرا
        @ini_set('memory_limit', '512M');
        // از قطع نشست در حین اجرای طولانی جلوگیری کن
        @ini_set('session.gc_maxlifetime', (string)(60 * 60));
        // بافر خروجی را فلش کن تا کاربر در صورت تایم‌اوت مرورگر، داده‌ها از دست نرود
        @ob_end_clean();

        $personModel = new Person();
        $userModel = new UserAccount();
        $applicantModel = new Applicant();
        $registrationService = new ApplicantRegistrationService();

        $db = Database::getInstance()->getConnection();
        $results = [];

        // پیگیری کد ملی‌های پردازش‌شده در همین اجرا (برای ادغام موبایل دوم/سوم)
        // nc => ['person_id' => int, 'mobile' => string, 'mobile_2' => ?, 'mobile_3' => ?, 'first_idx' => int]
        $processedInBatch = [];

        // اندازهٔ هر دسته برای commit میان‌راه (تا پیشرفت با Commیت شود و کل ایمپورت لغو نگردد)
        $batchSize = 50;
        $processedInThisTransaction = 0;
        $inTransaction = false;

        // تابع کمکی برای commit یک دسته
        $commitBatch = function () use (&$db, &$inTransaction, &$processedInThisTransaction) {
            if ($inTransaction) {
                try { $db->commit(); } catch (\Throwable $e) {}
                $inTransaction = false;
                $processedInThisTransaction = 0;
            }
        };
        // تابع کمکی برای شروع تراکنش جدید
        $beginBatch = function () use (&$db, &$inTransaction) {
            if (!$inTransaction) {
                try { $db->beginTransaction(); $inTransaction = true; } catch (\Throwable $e) {}
            }
        };

        // پردازش ردیف‌های انتخابی به‌ترتیب اندیس
        $sortedIndices = $selectedIndices;
        usort($sortedIndices, fn($a, $b) => (int)$a <=> (int)$b);

        foreach ($sortedIndices as $idx) {
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

            // نرمال‌سازی کد ملی و موبایل — همان نرمال‌سازی preview
            $nc = normalize_national_code((string)($data['national_code'] ?? ''));
            $mb = normalize_mobile((string)($data['mobile'] ?? ''));
            $fn = $data['first_name'] ?? '';
            $ln = $data['last_name'] ?? '';

            // ردیف‌های نامعتبر skip می‌شوند (موبایل نامعتبر، کد ملی نامعتبر، نام خالی)
            if (!is_valid_national_code($nc) || !is_valid_mobile($mb) || $fn === '') {
                $results[] = [
                    'idx' => $idx, 'name' => $fn . ' ' . $ln,
                    'national_code' => $nc, 'mobile' => $mb,
                    'status' => 'skipped',
                    'message' => 'نامعتبر — skip شد',
                ];
                continue;
            }

            // سناریوی «merge»: کد ملی قبلاً در همین اجرا پردازش شده → موبایل اضافه کن
            if (isset($processedInBatch[$nc])) {
                $info = &$processedInBatch[$nc];
                // اگر موبایل ورودی همان موبایل اصلی است، چیزی اضافه نکن
                if ($info['mobile'] === $mb || ($info['mobile_2'] ?? null) === $mb || ($info['mobile_3'] ?? null) === $mb) {
                    $results[] = [
                        'idx' => $idx, 'name' => $fn . ' ' . $ln,
                        'national_code' => $nc, 'mobile' => $mb,
                        'status' => 'skipped',
                        'message' => 'موبایل تکراری با ردیف ' . ((int)$info['first_idx'] + 1),
                    ];
                    continue;
                }

                // شروع تراکنش در صورت نیاز
                $beginBatch();

                try {
                    $slot = $personModel->addSecondaryMobile((int)$info['person_id'], $mb);
                    if ($slot === 'mobile_2') {
                        $info['mobile_2'] = $mb;
                        $msg = 'ادغام شد — موبایل دوم ثبت شد';
                    } elseif ($slot === 'mobile_3') {
                        $info['mobile_3'] = $mb;
                        $msg = 'ادغام شد — موبایل سوم ثبت شد';
                    } else {
                        $msg = 'skip شد — هر سه شماره موبایل قبلاً پر شده‌اند';
                    }
                    $results[] = [
                        'idx' => $idx, 'name' => $fn . ' ' . $ln,
                        'national_code' => $nc, 'mobile' => $mb,
                        'status' => $slot !== '' ? 'merged' : 'skipped',
                        'message' => $msg,
                    ];
                    $processedInThisTransaction++;
                } catch (\Throwable $e) {
                    $results[] = [
                        'idx' => $idx, 'name' => $fn . ' ' . $ln,
                        'national_code' => $nc, 'mobile' => $mb,
                        'status' => 'error',
                        'message' => 'خطا در ادغام: ' . $e->getMessage(),
                    ];
                }
                unset($info);

                // commit دسته‌ای
                if ($processedInThisTransaction >= $batchSize) {
                    $commitBatch();
                }
                continue;
            }

            // سناریوی جدید: بررسی یکتایی در DB
            $existingPerson = $personModel->findByNationalCode($nc);
            if ($existingPerson) {
                $results[] = [
                    'idx' => $idx, 'name' => $fn . ' ' . $ln,
                    'national_code' => $nc, 'mobile' => $mb,
                    'status' => 'skipped',
                    'message' => 'کد ملی موجود در دیتابیس — skip شد',
                ];
                continue;
            }
            $existingUser = $userModel->findByMobile($mb);
            if ($existingUser) {
                $results[] = [
                    'idx' => $idx, 'name' => $fn . ' ' . $ln,
                    'national_code' => $nc, 'mobile' => $mb,
                    'status' => 'skipped',
                    'message' => 'موبایل موجود در دیتابیس — skip شد',
                ];
                continue;
            }

            // رمز عبور
            $password = $passwordStrategy === 'fixed' ? $fixedPassword : $nc;

            // شروع تراکنش دسته‌ای (اگر از قبل فعال نیست)
            // createApplicant در صورت وجود تراکنش فعال، تراکنش جدید شروع نمی‌کند
            $beginBatch();

            // استفاده از سرویس متمرکز ثبت‌نام (یکپارچه با ویزارد و ثبت‌نام دستی)
            $result = $registrationService->createApplicant(
                [
                    'national_code' => $nc,
                    'first_name' => $fn,
                    'last_name' => $ln,
                    'mobile' => $mb,
                    'postal_code' => $data['postal_code'] ?? null,
                    'father_name' => $data['father_name'] ?? null,
                    'income_decile' => $data['income_decile'] ?? null,
                    'license_title' => $licenseTitle,
                    'experience_months' => 0,
                    'training_method' => 'کارگاهی',
                    'province' => $data['province'] ?? null,
                    'city' => $data['city'] ?? null,
                    'village' => $data['village'] ?? null,
                    'ownership_status' => !empty($data['province']) || !empty($data['city']) ? 'شخص' : null,
                    'electricity_bill_id' => !empty($data['province']) || !empty($data['city']) ? '0000000000' : null,
                ],
                [
                    'source' => 'bulk',
                    'service_type_id' => $serviceTypeId,
                    'create_user_account' => true,
                    'default_password' => $password,
                    'complete_status' => true,
                    'notify_user' => true,
                ]
            );

            if (!$result['success']) {
                // چون createApplicant تراکنش خودش را rollback نکرده (چون مالک ماست)،
                // کل تراکنش دسته‌ای را rollback می‌کنیم تا تغییرات ناقص ثبت نشود.
                // سپس تراکنش جدید برای ردیف‌های بعدی شروع می‌شود.
                if ($inTransaction) {
                    try { $db->rollBack(); } catch (\Throwable $e) {}
                    $inTransaction = false;
                    $processedInThisTransaction = 0;
                }
                $results[] = [
                    'idx' => $idx, 'name' => $fn . ' ' . $ln,
                    'national_code' => $nc, 'mobile' => $mb,
                    'status' => 'error',
                    'message' => implode(' | ', $result['errors']),
                ];
                continue;
            }

            $applicantId = $result['applicant_id'];
            $trackingCode = $result['tracking_code'];
            $personId = $result['person_id'];
            $accountId = $result['account_id'];

            // ثبت در نقشهٔ batch برای ادغام ردیف‌های بعدی
            $processedInBatch[$nc] = [
                'person_id' => $personId,
                'mobile' => $mb,
                'mobile_2' => null,
                'mobile_3' => null,
                'first_idx' => $idx,
            ];

            $results[] = [
                'idx' => $idx,
                'name' => $fn . ' ' . $ln,
                'national_code' => $nc,
                'mobile' => $mb,
                'tracking_code' => $trackingCode,
                'password' => $password,
                'status' => 'success',
            ];

            $processedInThisTransaction++;

            // commit دسته‌ای
            if ($processedInThisTransaction >= $batchSize) {
                $commitBatch();
            }
        }

        // commit تراکنش باقی‌مانده
        $commitBatch();

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

        $successCount  = count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'success'));
        $mergedCount   = count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'merged'));
        $skippedCount  = count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'skipped'));
        $duplicateCount= count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'duplicate'));
        $errorCount    = count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'error'));

        $this->render('admin/bulk_result', [
            'title' => 'گزارش ایمپورت',
            'results' => $results,
            'successCount' => $successCount,
            'mergedCount' => $mergedCount,
            'skippedCount' => $skippedCount,
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

        // استفاده از سرویس متمرکز برای ذخیرهٔ مدارک (یک الگوی واحد — رفع D-F-2)
        $registrationService = new ApplicantRegistrationService();
        $uploaded = 0;
        $errors = [];

        // فایل‌های تکی (مطابق DocTypes::USER_UPLOAD)
        foreach (DocTypes::allowedForUserUpload() as $docInputName) {
            $fileInput = $_FILES['doc_' . $docInputName] ?? null;
            if (!$fileInput || $fileInput['error'] !== UPLOAD_ERR_OK) continue;
            // نگاشت نام ورودی → doc_type ذخیره‌شده در documents
            $docType = ApplicantRegistrationService::FILE_INPUT_TO_DOC_TYPE[$docInputName] ?? $docInputName;
            $result = $registrationService->saveUploadedFile($fileInput, $applicantId, $docType);
            if ($result['success']) {
                $uploaded++;
            } elseif ($result['error']) {
                $errors[] = $result['error'];
            }
        }

        // تصاویر محصولات (چندفایلی)
        if (!empty($_FILES['product_photos']) && is_array($_FILES['product_photos']['name'] ?? null)) {
            $ppFiles = $_FILES['product_photos'];
            $ppCount = is_array($ppFiles['name']) ? count($ppFiles['name']) : 0;
            for ($i = 0; $i < $ppCount; $i++) {
                if (($ppFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
                $tmpFile = [
                    'name'     => $ppFiles['name'][$i] ?? '',
                    'tmp_name' => $ppFiles['tmp_name'][$i] ?? '',
                    'size'     => $ppFiles['size'][$i] ?? 0,
                    'error'    => $ppFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                ];
                $result = $registrationService->saveUploadedFile($tmpFile, $applicantId, 'product_photos');
                if ($result['success']) {
                    $uploaded++;
                } elseif ($result['error']) {
                    $errors[] = $result['error'];
                }
            }
        }

        $msg = $uploaded . ' مدرک بارگذاری شد.';
        if (!empty($errors)) {
            $msg .= ' خطاها: ' . implode(' | ', $errors);
        }
        Session::set('bulk_success', $msg);
        $this->redirect('/admin/bulk-import/documents/' . $code);
    }
}
