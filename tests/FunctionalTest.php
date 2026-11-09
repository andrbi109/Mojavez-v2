<?php
/**
 * تست‌های عملکردی
 * -----------------------------------------------------------------
 * بررسی:
 *   - روت‌های اصلی در index.php ثبت شده‌اند
 *   - کنترلرها متدهای مورد انتظار را دارند
 *   - endpoint /health وجود دارد
 *   - سیستم authentication مسیرهای protected دارد
 *   - سیستم admin/login، admin/logout موجودند
 */
class FunctionalTest extends TestCase
{
    private string $indexPhp;

    public function setUp(): void
    {
        $this->indexPhp = file_get_contents(ROOT_PATH . '/public/index.php');
    }

    /* ==================== روت‌های عمومی ==================== */

    public function testHasHomePageRoute(): void
    {
        $this->assertContains("'/', ['HomeController', 'index']", $this->indexPhp);
    }

    public function testHasGuideRoute(): void
    {
        $this->assertContains("'/guide', ['HomeController', 'guide']", $this->indexPhp);
    }

    public function testHasAboutRoute(): void
    {
        $this->assertContains("'/about', ['HomeController', 'about']", $this->indexPhp);
    }

    public function testHasHealthEndpoint(): void
    {
        $this->assertContains("'/health'", $this->indexPhp);
        $this->assertContains("'status' => \$ok ? 'healthy' : 'degraded'", $this->indexPhp);
    }

    /* ==================== احراز هویت ==================== */

    public function testHasSignupRoutes(): void
    {
        $this->assertContains("'/signup', ['AuthController', 'signupForm']", $this->indexPhp);
        $this->assertContains("'/signup', ['AuthController', 'signup']", $this->indexPhp);
    }

    public function testHasLoginRoutes(): void
    {
        $this->assertContains("'/login', ['AuthController', 'loginForm']", $this->indexPhp);
        $this->assertContains("'/login', ['AuthController', 'login']", $this->indexPhp);
    }

    public function testHasLogoutRoute(): void
    {
        $this->assertContains("'/logout', ['AuthController', 'logout']", $this->indexPhp);
    }

    /* ==================== پنل کاربری ==================== */

    public function testHasUserPanelRoutes(): void
    {
        $panel = [
            "'/dashboard', ['UserPanelController', 'index']",
            "'/panel', ['UserPanelController', 'index']",
            "'/panel/profile', ['UserPanelController', 'profile']",
            "'/panel/requests', ['UserPanelController', 'requests']",
            "'/panel/payments', ['UserPanelController', 'payments']",
            "'/panel/licenses', ['UserPanelController', 'licenses']",
            "'/panel/notifications', ['UserPanelController', 'notifications']",
            "'/panel/tracking', ['UserPanelController', 'tracking']",
        ];
        foreach ($panel as $r) {
            $this->assertContains($r, $this->indexPhp);
        }
    }

    /* ==================== ویزارد ثبت‌نام ==================== */

    public function testHasRegistrationRoutes(): void
    {
        $this->assertContains("'/register', ['RegistrationController', 'index']", $this->indexPhp);
        $this->assertContains("'/register/service/{id}'", $this->indexPhp);
        $this->assertContains("'/register/step/{n}'", $this->indexPhp);
        // مسیرهای قدیمی step1..step4 هم باید موجود باشند (backward compat)
        $this->assertContains("'/register/step1'", $this->indexPhp);
        $this->assertContains("'/register/step2'", $this->indexPhp);
        $this->assertContains("'/register/step3'", $this->indexPhp);
        $this->assertContains("'/register/step4'", $this->indexPhp);
        $this->assertContains("'/register/success/{trackingCode}'", $this->indexPhp);
    }

    /* ==================== پیگیری ==================== */

    public function testHasTrackingRoutes(): void
    {
        $this->assertContains("'/tracking', ['TrackingController', 'search']", $this->indexPhp);
        $this->assertContains("'/tracking/search', ['TrackingController', 'find']", $this->indexPhp);
        $this->assertContains("'/tracking/status/{trackingCode}'", $this->indexPhp);
        $this->assertContains("'/tracking/change-postal-code'", $this->indexPhp);
        $this->assertContains("'/tracking/print/{trackingCode}'", $this->indexPhp);
    }

    /* ==================== پنل مدیریت ==================== */

    public function testHasAdminLoginRoutes(): void
    {
        $this->assertContains("'/admin/login', ['AdminController', 'loginForm']", $this->indexPhp);
        $this->assertContains("'/admin/login', ['AdminController', 'login']", $this->indexPhp);
        $this->assertContains("'/admin/logout', ['AdminController', 'logout']", $this->indexPhp);
    }

    public function testHasAdminDashboardRoute(): void
    {
        $this->assertContains("'/admin/dashboard', ['AdminController', 'dashboard']", $this->indexPhp);
    }

    public function testHasAdminApplicationsRoute(): void
    {
        $this->assertContains("'/admin/applications', ['AdminController', 'applications']", $this->indexPhp);
    }

    public function testHasAdminViewApplicationRoute(): void
    {
        $this->assertContains("'/admin/view/{id}', ['AdminController', 'viewApplication']", $this->indexPhp);
    }

    public function testHasAdminChangeStatusRoute(): void
    {
        $this->assertContains("'/admin/change-status', ['AdminController', 'changeStatus']", $this->indexPhp);
    }

    /* ==================== گزارش‌ها ==================== */

    public function testHasReportRoutes(): void
    {
        $reports = ['by-province', 'by-city', 'by-village', 'by-license', 'by-date'];
        foreach ($reports as $r) {
            $this->assertContains("'/admin/reports/{$r}'", $this->indexPhp);
        }
    }

    public function testHasOutputRoutes(): void
    {
        $this->assertContains("'/admin/output/general'", $this->indexPhp);
        $this->assertContains("'/admin/output/single/{trackingCode}'", $this->indexPhp);
        $this->assertContains("'/admin/output/grouped'", $this->indexPhp);
    }

    /* ==================== مدیریت رشته‌ها ==================== */

    public function testHasLicenseTitleAdminRoutes(): void
    {
        $this->assertContains("'/admin/license-titles', ['LicenseTitleController', 'index']", $this->indexPhp);
        $this->assertContains("'/admin/license-titles/save'", $this->indexPhp);
        $this->assertContains("'/admin/license-titles/delete'", $this->indexPhp);
        $this->assertContains("'/admin/license-titles/toggle/{id}'", $this->indexPhp);
        $this->assertContains("'/admin/license-titles/by-service/{id}'", $this->indexPhp);
    }

    /* ==================== ایمپورت دسته‌جمعی ==================== */

    public function testHasBulkImportRoutes(): void
    {
        $this->assertContains("'/admin/bulk-import'", $this->indexPhp);
        $this->assertContains("'/admin/bulk-import/upload'", $this->indexPhp);
        $this->assertContains("'/admin/bulk-import/execute'", $this->indexPhp);
    }

    /* ==================== مدارک ==================== */

    public function testHasDocumentRoutes(): void
    {
        $this->assertContains("'/document/view', ['DocumentController', 'view']", $this->indexPhp);
        $this->assertContains("'/document/avatar', ['DocumentController', 'avatar']", $this->indexPhp);
        $this->assertContains("'/document/thumb/{id}'", $this->indexPhp);
    }

    /* ==================== پرداخت‌ها ==================== */

    public function testHasPaymentRoutes(): void
    {
        $this->assertContains("'/admin/payment", $this->indexPhp);
        $this->assertContains("PaymentController", $this->indexPhp);
    }

    /* ==================== endpoint cron ==================== */

    public function testHasCheckCancelEndpoint(): void
    {
        $this->assertContains("'/check-cancel'", $this->indexPhp);
        $this->assertContains('CRON_SECRET', $this->indexPhp);
    }

    /* ==================== کنترلرها متدهای مورد انتظار را دارند ==================== */

    public function testAdminControllerHasLoginMethod(): void
    {
        $source = file_get_contents(APP_PATH . '/Controllers/AdminController.php');
        $this->assertContains('function login', $source);
        $this->assertContains('function loginForm', $source);
        $this->assertContains('function dashboard', $source);
        $this->assertContains('function applications', $source);
        $this->assertContains('function changeStatus', $source);
        $this->assertContains('function viewApplication', $source);
    }

    public function testAuthControllerHasSignupMethod(): void
    {
        $source = file_get_contents(APP_PATH . '/Controllers/AuthController.php');
        $this->assertContains('function signup', $source);
        $this->assertContains('function signupForm', $source);
        $this->assertContains('function login', $source);
        $this->assertContains('function loginForm', $source);
        $this->assertContains('function logout', $source);
    }

    public function testTrackingControllerHasSearchMethod(): void
    {
        $source = file_get_contents(APP_PATH . '/Controllers/TrackingController.php');
        $this->assertContains('function search', $source);
        $this->assertContains('function find', $source);
        $this->assertContains('function status', $source);
    }

    public function testHomeControllerHasIndexMethod(): void
    {
        $source = file_get_contents(APP_PATH . '/Controllers/HomeController.php');
        $this->assertContains('function index', $source);
    }

    /* ==================== Router سیستم ==================== */

    public function testRouterClassExists(): void
    {
        $this->assertTrue(file_exists(CORE_PATH . '/Router.php'));
        $source = file_get_contents(CORE_PATH . '/Router.php');
        $this->assertContains('class Router', $source);
        $this->assertContains('function get', $source);
        $this->assertContains('function post', $source);
        $this->assertContains('function dispatch', $source);
        // پشتیبانی از پارامترهای داینامیک مثل {id}
        $this->assertContains('preg_replace', $source);
    }

    /* ==================== AutoLoader ==================== */

    public function testAutoLoaderRegistersAllPaths(): void
    {
        $source = file_get_contents(CORE_PATH . '/AutoLoader.php');
        $this->assertContains('Controllers', $source);
        $this->assertContains('Models', $source);
        $this->assertContains('Services', $source);
        $this->assertContains('CORE_PATH', $source);
    }

    /* ==================== تولید کد ملی معتبر (کمکی) ==================== */

    public function testNationalCodeValidatorAcceptsValidCode(): void
    {
        // کد ملی معتبر تستی: 1111111111
        $v = new Validator(['nc' => '1111111111']);
        $v->nationalCode('nc', 'کد ملی');
        $this->assertFalse($v->hasErrors(), "کد ملی معتبر نباید خطا تولید کند");
    }

    public function testNationalCodeValidatorRejectsInvalidCode(): void
    {
        $v = new Validator(['nc' => '1234567890']);
        $v->nationalCode('nc', 'کد ملی');
        $this->assertTrue($v->hasErrors(), "کد ملی نامعتبر باید خطا تولید کند");
    }

    public function testMobileValidatorAcceptsValidMobile(): void
    {
        $v = new Validator(['mobile' => '09123456789']);
        $v->mobile('mobile', 'موبایل');
        $this->assertFalse($v->hasErrors(), "موبایل معتبر نباید خطا تولید کند");
    }

    public function testMobileValidatorRejectsInvalidMobile(): void
    {
        $v = new Validator(['mobile' => '1234']);
        $v->mobile('mobile', 'موبایل');
        $this->assertTrue($v->hasErrors(), "موبایل نامعتبر باید خطا تولید کند");
    }

    public function testPostalCodeValidatorAcceptsValidCode(): void
    {
        $v = new Validator(['pc' => '1234567890']);
        $v->postalCode('pc', 'کد پستی');
        $this->assertFalse($v->hasErrors(), "کد پستی معتبر نباید خطا تولید کند");
    }

    public function testRequiredValidatorRejectsEmpty(): void
    {
        $v = new Validator(['name' => '']);
        $v->required('name', 'نام');
        $this->assertTrue($v->hasErrors(), "فیلد خالی باید خطا تولید کند");
    }

    /* ==================== session/csrf ==================== */

    public function testCsrfTokenIsStringAndLongEnough(): void
    {
        $token = Session::csrfToken();
        $this->assertTrue(is_string($token));
        // توکن تولیدشده با random_bytes(32) → ۶۴ hex chars
        $this->assertTrue(strlen($token) >= 32, "توکن باید حداقل ۳۲ کاراکتر باشد");
    }

    public function testCsrfTokenIsConsistentWithinSession(): void
    {
        $t1 = Session::csrfToken();
        $t2 = Session::csrfToken();
        $this->assertEquals($t1, $t2, "توکن باید در یک نشست ثابت باشد");
    }

    public function testCsrfCheckAcceptsValidToken(): void
    {
        $token = Session::csrfToken();
        $this->assertTrue(Session::checkCsrf($token), "توکن معتبر باید پذیرفته شود");
    }

    public function testCsrfCheckRejectsInvalidToken(): void
    {
        $this->assertFalse(Session::checkCsrf('invalid-token'), "توکن نامعتبر باید رد شود");
        $this->assertFalse(Session::checkCsrf(''), "توکن خالی باید رد شود");
        $this->assertFalse(Session::checkCsrf(null), "توکن null باید رد شود");
    }
}
