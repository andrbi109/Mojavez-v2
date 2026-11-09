<?php
/**
 * تست‌های ویزارد و فیلدهای فرم
 * -----------------------------------------------------------------
 * بررسی:
 *   - چهار مرحلهٔ سیستمی (step1..step4) موجودند
 *   - مراحل اختصاصی سرویس ۳ (پشتیبان) موجودند
 *   - مراحل اختصاصی سرویس ۲ (مشاغل خانگی مستقل) موجودند
 *   - فیلدهای پویای dyn_* برای سرویس ۲ و ۳ موجودند
 *   - مدل WizardStep و FormField متدهای صحیح دارند
 */
class WizardFormTest extends TestCase
{
    private string $schemaSql;

    public function setUp(): void
    {
        $this->schemaSql = file_get_contents(ROOT_PATH . '/database/schema.sql');
    }

    /* ==================== مراحل سیستمی ==================== */

    public function testHasFourSystemSteps(): void
    {
        // چهار مرحلهٔ سیستمی با id ۱ تا ۴
        $this->assertContains("(1, 'step1', 'اطلاعات هویتی'", $this->schemaSql);
        $this->assertContains("(2, 'step2', 'اطلاعات شغلی'", $this->schemaSql);
        $this->assertContains("(3, 'step3', 'اطلاعات ملک'", $this->schemaSql);
        $this->assertContains("(4, 'step4', 'بارگذاری مدارک'", $this->schemaSql);
    }

    public function testHasService3StepSupporter(): void
    {
        // مرحلهٔ «اطلاعات پشتیبان» برای سرویس ۳
        $this->assertContains("'اطلاعات پشتیبان'", $this->schemaSql);
        $this->assertContains("'طرح توجیهی و اطلاعات افراد تحت پوشش", $this->schemaSql);
    }

    public function testHasService3Fields(): void
    {
        // سه فیلد الزامی برای پشتیبان
        $this->assertContains("dyn_justification_plan", $this->schemaSql);
        $this->assertContains("dyn_covered_persons", $this->schemaSql);
        $this->assertContains("dyn_income_details", $this->schemaSql);
    }

    /* ==================== مراحل اختصاصی مشاغل خانگی مستقل (سرویس ۲) ==================== */

    public function testHasService2StepJobPlan(): void
    {
        $this->assertContains("'اطلاعات طرح و شغل'", $this->schemaSql);
        $this->assertContains("'اطلاعات شغل، مهارت و طرح کسب‌وکار خانگی'", $this->schemaSql);
    }

    public function testHasService2StepFinancial(): void
    {
        $this->assertContains("'اطلاعات مالی'", $this->schemaSql);
        $this->assertContains("'سرمایه، وام، سود و هزینه تجهیزات'", $this->schemaSql);
    }

    public function testHasService2StepActivityPlace(): void
    {
        $this->assertContains("'محل فعالیت شخصی'", $this->schemaSql);
        $this->assertContains("'مشخصات فضای فعالیت و امکانات'", $this->schemaSql);
    }

    /* ==================== فیلدهای dyn_* سرویس ۲ ==================== */

    public function testHasService2DynFields(): void
    {
        $dynFields = [
            'dyn_skill_level', 'dyn_has_skill', 'dyn_job_title',
            'dyn_skill_acquisition', 'dyn_need_support', 'dyn_cooperation',
            'dyn_plan_summary', 'dyn_experience_months', 'dyn_customers',
            'dyn_marketing_methods', 'dyn_target_markets', 'dyn_risks',
            // فیلدهای مالی
            'dyn_fixed_capital', 'dyn_working_capital', 'dyn_cash_share',
            'dyn_noncash_share', 'dyn_loan_request', 'dyn_financial_rate',
            'dyn_break_even', 'dyn_annual_profit', 'dyn_annual_production',
            'dyn_equipment_costs',
            // فیلدهای محل فعالیت
            'dyn_infrastructure_area', 'dyn_property_ownership',
            'dyn_building_type', 'dyn_activity_area', 'dyn_facilities',
        ];
        foreach ($dynFields as $f) {
            $this->assertContains($f, $this->schemaSql, "فیلد پویا {$f} باید در schema باشد");
        }
    }

    /* ==================== مدل‌ها موجودند ==================== */

    public function testWizardStepModelExists(): void
    {
        $this->assertTrue(file_exists(APP_PATH . '/Models/WizardStep.php'));
    }

    public function testFormFieldModelExists(): void
    {
        $this->assertTrue(file_exists(APP_PATH . '/Models/FormField.php'));
    }

    public function testApplicantFieldValueModelExists(): void
    {
        $this->assertTrue(file_exists(APP_PATH . '/Models/ApplicantFieldValue.php'));
    }

    /* ==================== مدل WizardStep متدها ==================== */

    public function testWizardStepModelHasOrderedActiveMethod(): void
    {
        $source = file_get_contents(APP_PATH . '/Models/WizardStep.php');
        $this->assertContains('function orderedActive', $source,
            "WizardStep باید متد orderedActive داشته باشد");
        // باید پارامتر serviceTypeId بپذیرد
        $this->assertContains('service_type_id', $source);
    }

    /* ==================== مدل FormField متدها ==================== */

    public function testFormFieldModelHasActiveByStepId(): void
    {
        $source = file_get_contents(APP_PATH . '/Models/FormField.php');
        $this->assertContains('function activeByStepId', $source,
            "FormField باید متد activeByStepId داشته باشد");
        $this->assertContains('service_type_id', $source);
    }

    public function testFormFieldModelHasSystemActiveMap(): void
    {
        $source = file_get_contents(APP_PATH . '/Models/FormField.php');
        $this->assertContains('function systemActiveMap', $source,
            "FormField باید متد systemActiveMap داشته باشد");
    }

    /* ==================== کنترلر ویزارد ==================== */

    public function testRegistrationControllerSelectsService(): void
    {
        $source = file_get_contents(APP_PATH . '/Controllers/RegistrationController.php');
        $this->assertContains('function selectService', $source);
        $this->assertContains('function selectedServiceId', $source);
    }

    public function testRegistrationControllerRendersStep2(): void
    {
        $source = file_get_contents(APP_PATH . '/Controllers/RegistrationController.php');
        $this->assertContains("case 'step2'", $source);
        $this->assertContains("registration/step2", $source);
    }

    /* ==================== فیلدهای سیستمی اصلی ==================== */

    public function testHasCoreFormFields(): void
    {
        // فیلدهای سیستمی لازم در form_fields
        $this->assertContains("'license_title'", $this->schemaSql);
        $this->assertContains("'experience_months'", $this->schemaSql);
        $this->assertContains("'training_method'", $this->schemaSql);
        $this->assertContains("'ownership_status'", $this->schemaSql);
    }

    /* ==================== سیستم اتولودر ==================== */

    public function testAutoLoaderFindsAllModels(): void
    {
        $models = ['LicenseTitle', 'ServiceType', 'WizardStep', 'FormField',
                   'Applicant', 'JobDetail', 'PropertyDetail', 'Document',
                   'StatusLog', 'Payment', 'UserAccount', 'Person'];
        foreach ($models as $m) {
            $path = APP_PATH . '/Models/' . $m . '.php';
            $this->assertTrue(file_exists($path), "مدل {$m} باید موجود باشد");
        }
    }

    public function testAutoLoaderFindsAllControllers(): void
    {
        $controllers = ['AdminController', 'AuthController', 'BulkImportController',
                        'Controller', 'DocumentController', 'FeeTariffController',
                        'FormFieldController', 'HomeController', 'LicenseController',
                        'LicenseTitleController', 'PaymentController', 'RecordsController',
                        'RegistrationController', 'ReportController', 'RevisionController',
                        'ServiceController', 'ServiceTypeController', 'SettingController',
                        'SmsController', 'TrackingController', 'UserController',
                        'UserPanelController', 'WizardStepController'];
        foreach ($controllers as $c) {
            $path = APP_PATH . '/Controllers/' . $c . '.php';
            $this->assertTrue(file_exists($path), "کنترلر {$c} باید موجود باشد");
        }
    }
}
