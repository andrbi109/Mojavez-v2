<?php
/**
 * کنترلر گزارش‌های CSV و نسخهٔ چاپی/PDF
 * ------------------------------------------------------
 * - خروجی Excel/CSV با BOM برای نمایش صحیح فارسی
 * - خروجی «PDF» به شکل صفحهٔ HTML بهینه‌شده برای چاپ ارائه می‌شود
 *   (بدون کتابخانهٔ خارجی؛ کاربر با Print to PDF مرورگر فایل PDF می‌سازد)
 * - همهٔ گزارش‌ها فقط در دسترس نقش «admin» هستند
 */
class ReportController extends Controller
{
    private Applicant $applicantModel;
    private JobDetail $jobDetailModel;
    private PropertyDetail $propertyDetailModel;
    private Document $documentModel;
    private StatusLog $statusLogModel;
    private PostalCodeChange $postalCodeChangeModel;

    public function __construct()
    {
        $this->applicantModel = new Applicant();
        $this->jobDetailModel = new JobDetail();
        $this->propertyDetailModel = new PropertyDetail();
        $this->documentModel = new Document();
        $this->statusLogModel = new StatusLog();
        $this->postalCodeChangeModel = new PostalCodeChange();
    }

    /**
     * گزارش‌ها فقط برای مدیر کل
     */
    private function checkAdmin(): void
    {
        $this->requireRole(['admin']);
    }

    /**
     * خروجی CSV/Excel — اصلاح باگ نسخهٔ قبل:
     *  - متغیرهای تعریف‌نشدهٔ $job و $prop حذف و از ستون‌های JOIN‌شده استفاده شد
     *  - ترتیب ستون‌های داده دقیقاً منطبق بر سرستون‌ها چیده شد
     *  - تاریخ‌ها به شمسی تبدیل می‌شوند
     */
    public function csvReport(): void
    {
        $this->checkAdmin();
        $filters = $this->getFiltersFromRequest();
        $applicants = $this->applicantModel->getAll($filters);

        $headers = [
            'کد رهگیری', 'نوع خدمت', 'نام', 'نام خانوادگی', 'کد ملی', 'موبایل', 'کد پستی',
            'تاریخ تولد', 'نام پدر', 'وضعیت تأهل', 'تعداد فرزندان', 'وضعیت جسمانی',
            'رشته (عنوان مجوز)', 'سابقه (ماه)', 'نحوه آموزش',
            'وضعیت مالکیت', 'استان', 'شهرستان', 'شهر', 'روستا',
            'وضعیت فعلی', 'وضعیت مجوز', 'اولویت‌دار', 'تاریخ شروع ثبت‌نام', 'تاریخ تکمیل ثبت‌نام',
        ];

        // ستون‌های پویا: فیلدهای سفارشی فعالِ فرم‌ساز
        $fvModel = new ApplicantFieldValue();
        $customFields = $fvModel->activeCustomFields();
        foreach ($customFields as $cf) {
            $headers[] = $cf['label'];
        }

        $rows = [];
        foreach ($applicants as $app) {
            $job = $this->jobDetailModel->findByApplicantId((int)$app['id']);
            $prop = $this->propertyDetailModel->findByApplicantId((int)$app['id']);
            $svcTitle = '';
            if (!empty($app['service_type_id'])) {
                $svc = (new ServiceType())->findById((int)$app['service_type_id']);
                $svcTitle = $svc['title'] ?? '';
            }
            $row = [
                $app['tracking_code'],
                $svcTitle ?: 'مجوز صنایع دستی',
                $app['first_name'],
                $app['last_name'],
                $app['national_code'],
                $app['mobile'],
                $app['postal_code'],
                persianDate((string)($app['birth_date'] ?? '')),
                $app['father_name'] ?? '',
                $app['marital_status'] ?? '',
                (string)($app['children_count'] ?? 0),
                $app['physical_condition'] ?? '',
                $job['license_title'] ?? '',
                (string)($job['experience_months'] ?? ''),
                $job['training_method'] ?? '',
                $prop['ownership_status'] ?? '',
                $prop['province'] ?? '',
                $prop['county'] ?? '',
                $prop['city'] ?? '',
                $prop['village'] ?? '',
                $app['current_status'],
                $app['license_state'] ?? 'جاری',
                $app['priority'] ? 'بله' : 'خیر',
                persianDate((string)($app['registration_started_at'] ?? '')),
                persianDate((string)($app['registration_completed_at'] ?? '')),
            ];

            // مقادیر فیلدهای سفارشی این متقاضی (ارجاع فایلی به متن خوانا تبدیل می‌شود)
            if ($customFields) {
                $vals = [];
                foreach ($fvModel->findByApplicantId((int)$app['id']) as $dv) {
                    $vals[$dv['field_key']] = str_starts_with((string)$dv['field_value'], 'doc:')
                        ? '[فایل پیوست #' . (int)substr($dv['field_value'], 4) . ']'
                        : (string)$dv['field_value'];
                }
                foreach ($customFields as $cf) {
                    $row[] = $vals[$cf['field_key']] ?? '';
                }
            }
            $rows[] = $row;
        }

        CsvExporter::export('گزارش_درخواست‌ها_' . jdate('Y-m-d'), $headers, $rows);
    }

    /**
     * خروجی قابل چاپ/PDF — صفحهٔ HTML بهینه برای چاپ با دیالوگ پرینت خودکار.
     * کاربر در مرورگر گزینهٔ «Save as PDF» را انتخاب می‌کند.
     * (تولید PDF باینری واقعی با فونت فارسی بدون کتابخانهٔ خارجی ممکن نیست؛
     *  این روش طبق بند ۶ نیازمندی‌ها جایگزین قابل قبول است.)
     */
    public function pdfReport(): void
    {
        $this->checkAdmin();
        $filters = $this->getFiltersFromRequest();
        $applicants = $this->applicantModel->getAll($filters);

        if (ob_get_level()) {
            ob_end_clean();
        }

        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $html = '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
        $html .= '<title>گزارش درخواست‌های مجوز صنایع دستی</title>';
        $html .= '<link rel="stylesheet" href="' . $e(rtrim(BASE_URL, '/') . '/assets/font.css') . '">';
        $html .= '<style>
            body{font-family:Vazirmatn,Tahoma,sans-serif;padding:12mm;color:#111;background:#fff}
            h2{text-align:center;margin-bottom:4px}
            .meta{text-align:center;color:#555;font-size:.85rem;margin-bottom:14px}
            table{width:100%;border-collapse:collapse;font-size:.8rem}
            th,td{border:1px solid #888;padding:5px 6px;text-align:right}
            th{background:#eef3f6}
            tr:nth-child(even) td{background:#fafafa}
            thead{display:table-header-group}
            tr{page-break-inside:avoid}
            .toolbar{position:sticky;top:0;text-align:center;padding:10px;background:#fff;border-bottom:1px solid #ddd;margin-bottom:12px}
            .toolbar button{font-family:inherit;font-size:1rem;padding:.5rem 1.5rem;border-radius:8px;border:1px solid #0096C7;background:#00B4D8;color:#fff;cursor:pointer}
            @media print{ .toolbar{display:none} body{padding:0} }
            @page{ size:A4 landscape; margin:12mm; }
        </style></head><body>';

        $html .= '<div class="toolbar no-print"><button onclick="window.print()">🖨️ چاپ / ذخیرهٔ PDF</button></div>';
        $html .= '<h2>گزارش درخواست‌های مجوز صنایع دستی</h2>';
        $html .= '<p class="meta">تاریخ تولید گزارش: ' . jdate('Y/m/d H:i') . ' — تعداد رکوردها: ' . count($applicants) . '</p>';

        // نمایش فیلترهای اعمال‌شده در سربرگ گزارش
        $activeFilters = array_filter($filters);
        if ($activeFilters) {
            $parts = [];
            $labels = ['tracking_code' => 'کد رهگیری', 'name' => 'نام', 'national_code' => 'کد ملی',
                'mobile' => 'موبایل', 'province' => 'استان', 'city' => 'شهر', 'village' => 'روستا',
                'status' => 'وضعیت', 'license_title' => 'رشته'];
            foreach ($activeFilters as $k => $v) {
                $parts[] = ($labels[$k] ?? $k) . ': ' . $e($v);
            }
            $html .= '<p class="meta">فیلترها → ' . implode(' | ', $parts) . '</p>';
        }

        $html .= '<table><thead><tr>';
        $html .= '<th>ردیف</th><th>کد رهگیری</th><th>نام و نام خانوادگی</th><th>کد ملی</th><th>موبایل</th>'
            . '<th>رشته</th><th>استان</th><th>شهر</th><th>وضعیت</th><th>اولویت</th><th>تاریخ تکمیل</th>';
        $html .= '</tr></thead><tbody>';

        $i = 1;
        foreach ($applicants as $app) {
            $html .= '<tr>';
            $html .= '<td>' . $i++ . '</td>';
            $html .= '<td>' . $e($app['tracking_code']) . '</td>';
            $html .= '<td>' . $e($app['first_name'] . ' ' . $app['last_name']) . '</td>';
            $html .= '<td>' . $e($app['national_code']) . '</td>';
            $html .= '<td>' . $e($app['mobile']) . '</td>';
            $html .= '<td>' . $e($app['license_title'] ?? '') . '</td>';
            $html .= '<td>' . $e($app['province'] ?? '') . '</td>';
            $html .= '<td>' . $e($app['city'] ?? '') . '</td>';
            $html .= '<td>' . $e($app['current_status']) . '</td>';
            $html .= '<td>' . ($app['priority'] ? '⭐' : '') . '</td>';
            $html .= '<td>' . persianDate((string)($app['registration_completed_at'] ?? '')) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="license-report.html"');
        header('Cache-Control: no-store');
        echo $html;
        exit;
    }

    public function byProvince(): void
    {
        $this->checkAdmin();
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT p.province, COUNT(*) as cnt FROM applicants a JOIN property_details p ON a.id=p.applicant_id GROUP BY p.province ORDER BY cnt DESC");
        $stats = $stmt->fetchAll();
        $this->renderStats('بر اساس استان', 'استان', 'province', $stats);
    }

    public function byCity(): void
    {
        $this->checkAdmin();
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT p.city, COUNT(*) as cnt FROM applicants a JOIN property_details p ON a.id=p.applicant_id GROUP BY p.city ORDER BY cnt DESC");
        $stats = $stmt->fetchAll();
        $this->renderStats('بر اساس شهر', 'شهر', 'city', $stats);
    }

    public function byVillage(): void
    {
        $this->checkAdmin();
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT p.village, COUNT(*) as cnt FROM applicants a JOIN property_details p ON a.id=p.applicant_id WHERE p.village IS NOT NULL AND p.village != '' GROUP BY p.village ORDER BY cnt DESC");
        $stats = $stmt->fetchAll();
        $this->renderStats('بر اساس روستا', 'روستا', 'village', $stats);
    }

    public function byLicense(): void
    {
        $this->checkAdmin();
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT j.license_title, COUNT(*) as cnt FROM job_details j JOIN applicants a ON j.applicant_id=a.id GROUP BY j.license_title ORDER BY cnt DESC");
        $stats = $stmt->fetchAll();
        $this->renderStats('بر اساس رشته', 'رشته', 'license_title', $stats);
    }

    public function byDate(): void
    {
        $this->checkAdmin();
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT DATE(registration_completed_at) as reg_date, COUNT(*) as cnt FROM applicants WHERE registration_completed_at IS NOT NULL GROUP BY DATE(registration_completed_at) ORDER BY reg_date DESC");
        $stats = $stmt->fetchAll();
        $this->renderStats('بر اساس تاریخ ثبت‌نام', 'تاریخ', 'reg_date', $stats);
    }

    private function renderStats(string $title, string $label, string $field, array $stats): void
    {
        $this->render('admin/report_stats', [
            'title' => $title,
            'label' => $label,
            'field' => $field,
            'stats' => $stats,
            'currentPage' => 'reports'
        ], 'admin_layout');
    }

    // گزارش کلی (صفحه فیلتر + خروجی)
    public function generalReport(): void
    {
        $this->checkAdmin();
        $this->render('admin/output_general', [
            'title' => 'گزارش کلی',
            'filters' => $_GET,
            'currentPage' => 'outputs'
        ], 'admin_layout');
    }

    // گزارش متقاضی خاص (پروندهٔ چاپی از دید مدیر — بدون ثبت رویداد مشاهدهٔ کاربر)
    public function singleApplicantReport(string $trackingCode): void
    {
        $this->checkAdmin();
        $applicant = $this->applicantModel->findByTrackingCode(strtoupper(trim($trackingCode)));
        if (!$applicant) {
            Session::set('flash_error', 'متقاضی با کد رهگیری «' . htmlspecialchars($trackingCode) . '» یافت نشد.');
            $this->redirect('/admin/dashboard');
        }
        $this->render('print', [
            'title' => 'پرونده متقاضی - ' . $applicant['tracking_code'],
            'applicant' => $applicant,
            'jobDetail' => $this->jobDetailModel->findByApplicantId((int)$applicant['id']),
            'propertyDetail' => $this->propertyDetailModel->findByApplicantId((int)$applicant['id']),
            'documents' => $this->documentModel->findByApplicantId((int)$applicant['id']),
            'statusLogs' => $this->statusLogModel->findByApplicantId((int)$applicant['id']),
            'postalChanges' => $this->postalCodeChangeModel->findByApplicantId((int)$applicant['id']),
            'dynValues' => (new ApplicantFieldValue())->findByApplicantId((int)$applicant['id']),
        ], 'layout_print');
    }

    // فرم گزارش گروه‌بندی شده
    public function groupedReportForm(): void
    {
        $this->checkAdmin();
        $this->render('admin/output_grouped_form', [
            'title' => 'گزارش گروه‌بندی‌شده',
            'currentPage' => 'outputs'
        ], 'admin_layout');
    }

    /**
     * نتیجهٔ گزارش گروه‌بندی‌شده
     * اصلاح باگ: کلید field که نما به آن نیاز دارد ارسال می‌شود
     */
    public function groupedReportResult(): void
    {
        $this->checkAdmin();
        if (!$this->isPost()) {
            $this->redirect('/admin/output/grouped');
        }
        $this->verifyCsrf();

        $groupBy = $_POST['group_by'] ?? 'province';
        $pdo = Database::getInstance()->getConnection();

        // نگاشت سفیدِ ستون → کوئری (از تزریق SQL از طریق group_by جلوگیری می‌کند)
        $queries = [
            'province' => "SELECT p.province AS label, COUNT(*) AS cnt FROM applicants a JOIN property_details p ON a.id=p.applicant_id GROUP BY p.province ORDER BY cnt DESC",
            'city' => "SELECT p.city AS label, COUNT(*) AS cnt FROM applicants a JOIN property_details p ON a.id=p.applicant_id GROUP BY p.city ORDER BY cnt DESC",
            'village' => "SELECT p.village AS label, COUNT(*) AS cnt FROM applicants a JOIN property_details p ON a.id=p.applicant_id WHERE p.village IS NOT NULL AND p.village != '' GROUP BY p.village ORDER BY cnt DESC",
            'license_title' => "SELECT j.license_title AS label, COUNT(*) AS cnt FROM job_details j JOIN applicants a ON j.applicant_id=a.id GROUP BY j.license_title ORDER BY cnt DESC",
            'registration_date' => "SELECT DATE(registration_completed_at) AS label, COUNT(*) AS cnt FROM applicants WHERE registration_completed_at IS NOT NULL GROUP BY DATE(registration_completed_at) ORDER BY label DESC",
            'status' => "SELECT current_status AS label, COUNT(*) AS cnt FROM applicants GROUP BY current_status ORDER BY cnt DESC",
        ];
        $sql = $queries[$groupBy] ?? $queries['province'];
        $stats = $pdo->query($sql)->fetchAll();

        $labelMap = [
            'province' => 'استان',
            'city' => 'شهر',
            'village' => 'روستا',
            'license_title' => 'رشته',
            'registration_date' => 'تاریخ ثبت‌نام',
            'status' => 'وضعیت',
        ];

        $this->render('admin/report_stats', [
            'title' => 'گزارش گروه‌بندی‌شده',
            'label' => $labelMap[$groupBy] ?? 'گروه',
            'field' => 'label', // رفع هشدار Undefined variable $field در نما
            'stats' => $stats,
            'currentPage' => 'outputs'
        ], 'admin_layout');
    }

    private function getFiltersFromRequest(): array
    {
        $clean = fn($k) => trim((string)($_GET[$k] ?? ''));
        return [
            'tracking_code' => $clean('tracking_code'),
            'name' => $clean('name'),
            'national_code' => $clean('national_code'),
            'mobile' => $clean('mobile'),
            'province' => $clean('province'),
            'city' => $clean('city'),
            'village' => $clean('village'),
            'status' => $clean('status'),
            'license_title' => $clean('license_title'),
        ];
    }
}
