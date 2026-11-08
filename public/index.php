<?php
require_once dirname(__DIR__) . '/config/config.php';

// نمایش خطاها فقط در حالت اشکال‌زدایی — در تولید خطاها لاگ می‌شوند
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

require_once dirname(__DIR__) . '/config/helper.php';
require_once dirname(__DIR__) . '/config/jdf.php';
require_once CORE_PATH . '/AutoLoader.php';
AutoLoader::register();

// ==================== مدیریت سراسری خطاها ====================
// هیچ خطای کنترل‌نشده‌ای نباید مسیر فایل‌های سرور را به کاربر نشان دهد
set_exception_handler(function (\Throwable $e) {
    error_log('Uncaught exception: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>خطای سرور</title>'
        . '<style>body{font-family:Tahoma,sans-serif;display:flex;align-items:center;justify-content:center;'
        . 'min-height:100vh;margin:0;background:#F0F2F5}.box{background:#fff;padding:2rem 3rem;border-radius:12px;'
        . 'box-shadow:0 2px 12px rgba(0,0,0,.08);text-align:center;max-width:560px}'
        . 'pre{text-align:left;direction:ltr;font-size:.75rem;color:#a33;max-height:220px;overflow:auto}'
        . 'a{display:inline-block;margin-top:1rem;background:#00B4D8;color:#fff;padding:.5rem 1.5rem;border-radius:8px;text-decoration:none}</style>'
        . '</head><body><div class="box"><h2>⚠️ خطای داخلی سرور</h2>'
        . '<p>پردازش درخواست با خطا مواجه شد. در صورت تکرار، با مدیر سامانه تماس بگیرید.</p>';
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<pre>' . htmlspecialchars($e->__toString(), ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    echo '<a href="/">بازگشت به صفحهٔ اصلی</a></div></body></html>';
    exit;
});
set_error_handler(function (int $severity, string $message, string $file, int $line) {
    // ثبت هشدارها/اعلان‌ها در لاگ؛ اجرا ادامه می‌یابد (فقط استثناهای واقعی ۵۰۰ می‌شوند)
    error_log("PHP {$severity}: {$message} @ {$file}:{$line}");
    return false; // واگذاری نمایش به display_errors (مطابق APP_DEBUG)
});

// ==================== هدرهای امنیتی سراسری ====================
// این هدرها در تمام پاسخ‌های سامانه اعمال می‌شوند:
header('X-Content-Type-Options: nosniff');            // جلوگیری از sniffing نوع محتوا
header('X-Frame-Options: SAMEORIGIN');                 // مقابله با Clickjacking
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()'); // غیرفعال‌سازی APIهای حساس مرورگر

Session::start();

$router = new Router(BASE_URL);

// Home
$router->get('/', ['HomeController', 'index']);
$router->get('/guide', ['HomeController', 'guide']);
$router->get('/about', ['HomeController', 'about']);
/*$router->get('/', function () {
    $controller = new class extends Controller {}; // anonymous
    $controller->render('home', ['title' => 'سامانه مجوز صنایع دستی']);
});*/

// صفحات عمومی
$router->get('/', ['HomeController', 'index']);

// احراز هویت کاربران (مدل فروشگاهی)
$router->get('/signup', ['AuthController', 'signupForm']);
$router->post('/signup', ['AuthController', 'signup']);
$router->get('/login', ['AuthController', 'loginForm']);
$router->post('/login', ['AuthController', 'login']);
$router->get('/logout', ['AuthController', 'logout']);

// هدایت /dashboard قدیمی به /panel
$router->get('/dashboard', ['UserPanelController', 'index']);

// پنل کاربری (مدل فروشگاهی — سایدبار + محتوا)
$router->get('/panel', ['UserPanelController', 'index']);
$router->get('/panel/profile', ['UserPanelController', 'profile']);
$router->post('/panel/profile', ['UserPanelController', 'updateProfile']);
$router->post('/panel/avatar', ['UserPanelController', 'uploadAvatar']);
$router->get('/panel/requests', ['UserPanelController', 'requests']);
$router->get('/panel/payments', ['UserPanelController', 'payments']);
$router->get('/panel/licenses', ['UserPanelController', 'licenses']);
$router->get('/panel/notifications', ['UserPanelController', 'notifications']);
$router->post('/panel/notifications/read', ['UserPanelController', 'markAllRead']);
$router->get('/panel/tracking', ['UserPanelController', 'tracking']);

// خدمات (مدل فروشگاهی — کارت‌های محصول)
$router->get('/services', ['ServiceController', 'index']);
$router->post('/services/{id}/purchase', ['ServiceController', 'purchase']);

// Registration — موتور ویزارد پویا (موقعیت‌محور) + نام‌های مستعار قدیمی
$router->get('/register', ['RegistrationController', 'index']);
$router->get('/register/service/{id}', ['RegistrationController', 'selectService']);
$router->get('/register/step/{n}', ['RegistrationController', 'stepByPosition']);
$router->post('/register/step/{n}', ['RegistrationController', 'processByPosition']);
$router->get('/register/step1', ['RegistrationController', 'step1']);
$router->post('/register/step1', ['RegistrationController', 'processStep1']);
$router->get('/register/step2', ['RegistrationController', 'step2']);
$router->post('/register/step2', ['RegistrationController', 'processStep2']);
$router->get('/register/step3', ['RegistrationController', 'step3']);
$router->post('/register/step3', ['RegistrationController', 'processStep3']);
$router->get('/register/step4', ['RegistrationController', 'step4']);
$router->post('/register/step4', ['RegistrationController', 'processStep4']);
$router->get('/register/success/{trackingCode}', ['RegistrationController', 'success']);

// Tracking
$router->get('/tracking', ['TrackingController', 'search']);
$router->post('/tracking/search', ['TrackingController', 'find']);
$router->get('/tracking/status/{trackingCode}', ['TrackingController', 'status']);
$router->post('/tracking/change-postal-code', ['TrackingController', 'changePostalCode']);
$router->get('/tracking/print/{trackingCode}', ['TrackingController', 'printView']);

// Admin
$router->get('/admin/login', ['AdminController', 'loginForm']);
$router->post('/admin/login', ['AdminController', 'login']);
$router->get('/admin/logout', ['AdminController', 'logout']);
$router->get('/admin/dashboard', ['AdminController', 'dashboard']);
$router->get('/admin/view/{id}', ['AdminController', 'viewApplication']);
$router->post('/admin/change-status', ['AdminController', 'changeStatus']);
// Admin new routes
$router->get('/admin/applications', ['AdminController', 'applications']); // لیست کامل با فیلتر
$router->get('/admin/reports/by-province', ['ReportController', 'byProvince']);
$router->get('/admin/reports/by-city', ['ReportController', 'byCity']);
$router->get('/admin/reports/by-village', ['ReportController', 'byVillage']);
$router->get('/admin/reports/by-license', ['ReportController', 'byLicense']);
$router->get('/admin/reports/by-date', ['ReportController', 'byDate']);
$router->get('/admin/output/general', ['ReportController', 'generalReport']);
$router->get('/admin/output/single/{trackingCode}', ['ReportController', 'singleApplicantReport']);
$router->get('/admin/output/grouped', ['ReportController', 'groupedReportForm']);
$router->post('/admin/output/grouped', ['ReportController', 'groupedReportResult']);
$router->get('/admin/register', ['AdminController', 'registerForm']);
$router->post('/admin/register', ['AdminController', 'registerProcess']);
$router->get('/admin/edit/{id}', ['AdminController', 'editApplication']);
$router->post('/admin/update', ['AdminController', 'updateApplication']);
$router->post('/admin/add-document', ['AdminController', 'addDocument']);
$router->post('/admin/delete-document', ['AdminController', 'deleteDocument']);
$router->get('/admin/my-tasks', ['AdminController', 'myTasks']);
// گردش کار بررسی درخواست‌های تغییر کد پستی (تأیید/رد توسط مدیر)
$router->get('/admin/postal-requests', ['AdminController', 'postalRequests']);
$router->post('/admin/postal-requests/approve', ['AdminController', 'approvePostalRequest']);
$router->post('/admin/postal-requests/reject', ['AdminController', 'rejectPostalRequest']);
$router->get('/admin/report/csv', ['ReportController', 'csvReport']);
$router->get('/admin/report/pdf', ['ReportController', 'pdfReport']);
$router->get('/admin/users', ['UserController', 'index']);
$router->get('/admin/users/create', ['UserController', 'createForm']);
$router->post('/admin/users/store', ['UserController', 'store']);
$router->get('/admin/users/edit/{id}', ['UserController', 'editForm']);
$router->post('/admin/users/update/{id}', ['UserController', 'update']);
$router->post('/admin/users/delete', ['UserController', 'delete']);

// فرم‌ساز پویا (مدیریت فیلدهای فرم ثبت‌نام)
$router->get('/admin/form-fields', ['FormFieldController', 'index']);
$router->get('/admin/form-fields/create', ['FormFieldController', 'editForm']);
$router->get('/admin/form-fields/edit/{id}', ['FormFieldController', 'editForm']);
$router->post('/admin/form-fields/save', ['FormFieldController', 'save']);
$router->post('/admin/form-fields/toggle/{id}', ['FormFieldController', 'toggle']);
$router->post('/admin/form-fields/delete', ['FormFieldController', 'delete']);
$router->post('/admin/form-fields/move', ['FormFieldController', 'move']);

// انواع خدمات (فاز ۹ نقشهٔ راه)
$router->get('/admin/service-types', ['ServiceTypeController', 'index']);
$router->post('/admin/service-types/save', ['ServiceTypeController', 'save']);
$router->post('/admin/service-types/delete', ['ServiceTypeController', 'delete']);

// مراحل ویزارد (فاز ۱ نقشهٔ راه)
$router->get('/admin/wizard-steps', ['WizardStepController', 'index']);
$router->post('/admin/wizard-steps/save', ['WizardStepController', 'save']);
$router->post('/admin/wizard-steps/toggle/{id}', ['WizardStepController', 'toggle']);
$router->post('/admin/wizard-steps/delete', ['WizardStepController', 'delete']);
$router->post('/admin/wizard-steps/move', ['WizardStepController', 'move']);

// تنظیمات سامانه (هویت، شبکه‌های اجتماعی، قالب پیام، دربارهٔ ما)
$router->get('/admin/settings', ['SettingController', 'index']);
$router->post('/admin/settings/save', ['SettingController', 'save']);
$router->post('/admin/settings/upload-image', ['SettingController', 'uploadImage']);

// مالی: پرداخت‌ها و کانال‌ها (فاز ۳ و ۴ نقشهٔ راه)
$router->get('/admin/payments', ['PaymentController', 'index']);
$router->post('/admin/payments/review', ['PaymentController', 'review']);
$router->get('/admin/payments/csv', ['PaymentController', 'csv']);
$router->post('/admin/payment-channels/save', ['PaymentController', 'saveChannel']);
$router->post('/admin/payment-channels/delete', ['PaymentController', 'deleteChannel']);

// ایمپورت دسته‌جمعی کاربران
$router->get('/admin/bulk-import', ['BulkImportController', 'index']);
$router->post('/admin/bulk-import/upload', ['BulkImportController', 'upload']);
$router->get('/admin/bulk-import/preview', ['BulkImportController', 'preview']);
$router->post('/admin/bulk-import/execute', ['BulkImportController', 'execute']);
$router->get('/admin/bulk-import/result', ['BulkImportController', 'result']);
$router->get('/admin/bulk-import/missing-docs', ['BulkImportController', 'missingDocs']);
$router->get('/admin/bulk-import/documents/{code}', ['BulkImportController', 'uploadDocs']);
$router->post('/admin/bulk-import/documents/{code}/save', ['BulkImportController', 'saveDocs']);

// نرخ‌نامهٔ چندگانه و تیک خدمات
$router->get('/admin/fee-tariff', ['FeeTariffController', 'index']);
$router->post('/admin/fee-tariff/settings', ['FeeTariffController', 'saveSettings']);
$router->post('/admin/fee-tariff/group/save', ['FeeTariffController', 'groupSave']);
$router->post('/admin/fee-tariff/group/{id}/delete', ['FeeTariffController', 'groupDelete']);
$router->post('/admin/fee-tariff/items/save', ['FeeTariffController', 'itemsSave']);
$router->post('/admin/payment/tick', ['FeeTariffController', 'toggleTick']);
// درخواست پرداخت هزینه توسط مدیر روی پرونده
$router->post('/admin/request-payment', ['AdminController', 'requestPayment']);
// وضعیت مجوز و سلسله‌مراتب پرونده‌ها (فاز ۷)
$router->post('/admin/license-state', ['AdminController', 'updateLicenseState']);

// سمت متقاضی: ثبت رسید پرداخت
$router->post('/tracking/payments/submit', ['TrackingController', 'submitPayment']);

// تصویر مجوز (فاز ۶ نقشهٔ راه)
$router->post('/tracking/license/first-access', ['LicenseController', 'firstAccess']);
$router->post('/tracking/license/request-access', ['LicenseController', 'requestAccess']);
$router->post('/tracking/license/pay', ['LicenseController', 'submitReissuePayment']);
$router->get('/license/view/{token}', ['LicenseController', 'viewByToken']);
$router->post('/admin/license/upload', ['LicenseController', 'upload']);
$router->get('/admin/license-requests', ['LicenseController', 'requests']);
$router->post('/admin/license-requests/review', ['LicenseController', 'reviewRequest']);

// پیامک‌های وضعیت (فاز ۵ نقشهٔ راه)
$router->post('/tracking/sms/submit', ['TrackingController', 'submitSms']);
$router->get('/admin/sms', ['SmsController', 'index']);
$router->post('/admin/sms/save-template', ['SmsController', 'saveTemplate']);
$router->post('/admin/sms/delete-template', ['SmsController', 'deleteTemplate']);
$router->post('/admin/sms/review-entry', ['SmsController', 'reviewEntry']);

// مشاهدهٔ سوابق با احراز هویت اطلاعات هویتی (فاز ۸ نقشهٔ راه)
$router->get('/my-records', ['RecordsController', 'form']);
$router->post('/my-records/verify', ['RecordsController', 'verify']);

// بازبینی پرونده توسط متقاضی (فاز ۲ نقشهٔ راه)
$router->get('/tracking/revision/{trackingCode}', ['RevisionController', 'form']);
$router->post('/tracking/revision/submit', ['RevisionController', 'submit']);
$router->get('/admin/revisions', ['RevisionController', 'index']);
$router->get('/admin/revisions/view/{id}', ['RevisionController', 'detail']);
$router->post('/admin/revisions/approve/{id}', ['RevisionController', 'approve']);
$router->post('/admin/revisions/reject/{id}', ['RevisionController', 'reject']);

// مدیریت رشته‌های مجوز
$router->get('/admin/license-titles', ['LicenseTitleController', 'index']);
$router->post('/admin/license-titles/save', ['LicenseTitleController', 'save']);
$router->post('/admin/license-titles/delete', ['LicenseTitleController', 'delete']);
$router->post('/admin/license-titles/toggle/{id}', ['LicenseTitleController', 'toggle']);

// Documents
$router->post('/document/view', ['DocumentController', 'view']);
// Thumbnail
$router->get('/document/thumb/{id}', ['DocumentController', 'thumb']);
//$router->get('/document/{id}', ['DocumentController', 'serve']);

// Check cancel
$router->get('/check-cancel', function () {
    $count = AutoCancelService::cancelExpired();
    AutoCancelService::updatePriorities();
    echo json_encode(['cancelled' => $count, 'message' => 'OK']);
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);