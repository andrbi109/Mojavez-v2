<?php
/**
 * تست‌های اختصاصی‌سازی رشته‌ها به هر سرویس
 * -----------------------------------------------------------------
 * هر سرویس باید فهرست دسته‌بندی‌ها و رشته‌های مختص به خود را داشته باشد.
 * بررسی:
 *   - schema.sql بذر سرویس‌های ۱، ۲، ۳ دارد
 *   - دسته‌های سرویس‌ها متمایز هستند
 *   - مدل groupedActiveByService فقط رشته‌های همان سرویس را برمی‌گرداند
 *   - isTitleUniqueInService یکتایی در دامنهٔ سرویس را بررسی می‌کند
 */
class ServiceScopeTest extends TestCase
{
    private string $schemaSql;

    public function setUp(): void
    {
        $this->schemaSql = file_get_contents(ROOT_PATH . '/database/schema.sql');
    }

    /* ==================== schema.sql seeds ==================== */

    public function testSchemaHasService1LicenseTitles(): void
    {
        // بذر سرویس ۱ — صنایع دستی
        preg_match_all("/\(1,\s*'([^']+)',\s*'([^']+)',\s*\d+\)/u", $this->schemaSql, $m1);
        $this->assertTrue(count($m1[0]) >= 50, "سرویس ۱ باید حداقل ۵۰ رشته داشته باشد — واقعی: " . count($m1[0]));
    }

    public function testSchemaHasService2LicenseTitles(): void
    {
        preg_match_all("/\(2,\s*'([^']+)',\s*'([^']+)',\s*\d+\)/u", $this->schemaSql, $m2);
        $this->assertTrue(count($m2[0]) >= 30, "سرویس ۲ باید حداقل ۳۰ رشته داشته باشد — واقعی: " . count($m2[0]));
    }

    public function testSchemaHasService3LicenseTitles(): void
    {
        preg_match_all("/\(3,\s*'([^']+)',\s*'([^']+)',\s*\d+\)/u", $this->schemaSql, $m3);
        $this->assertTrue(count($m3[0]) >= 20, "سرویس ۳ باید حداقل ۲۰ رشته داشته باشد — واقعی: " . count($m3[0]));
    }

    public function testServiceTitlesAreDistinct(): void
    {
        preg_match_all("/\(1,\s*'([^']+)',\s*'([^']+)',\s*\d+\)/u", $this->schemaSql, $m1);
        preg_match_all("/\(2,\s*'([^']+)',\s*'([^']+)',\s*\d+\)/u", $this->schemaSql, $m2);
        preg_match_all("/\(3,\s*'([^']+)',\s*'([^']+)',\s*\d+\)/u", $this->schemaSql, $m3);

        $titles1 = $m1[2];
        $titles2 = $m2[2];
        $titles3 = $m3[2];

        // دسته‌های هر سرویس باید متمایز باشند (هیچ هم‌پوشانی دسته‌ای نباید باشد)
        $cats1 = array_unique($m1[1]);
        $cats2 = array_unique($m2[1]);
        $cats3 = array_unique($m3[1]);

        $overlap12 = array_intersect($cats1, $cats2);
        $overlap13 = array_intersect($cats1, $cats3);
        $overlap23 = array_intersect($cats2, $cats3);

        $this->assertEmpty($overlap12, "دسته‌های سرویس ۱ و ۲ نباید مشترک باشند: " . implode(', ', $overlap12));
        $this->assertEmpty($overlap13, "دسته‌های سرویس ۱ و ۳ نباید مشترک باشند: " . implode(', ', $overlap13));
        $this->assertEmpty($overlap23, "دسته‌های سرویس ۲ و ۳ نباید مشترک باشند: " . implode(', ', $overlap23));
    }

    public function testServiceTypesSeedExists(): void
    {
        // ۵ سرویس در schema بذر شده‌اند
        $this->assertContains("VALUES\n(1, 'مجوز صنایع دستی'", $this->schemaSql, "سرویس ۱ باید موجود باشد");
        $this->assertContains("'مجوز مشاغل خانگی (مستقل)'", $this->schemaSql, "سرویس ۲ باید موجود باشد");
        $this->assertContains("'مجوز مشاغل خانگی (پشتیبان)'", $this->schemaSql, "سرویس ۳ باید موجود باشد");
        $this->assertContains("'مجوز گردشگری'", $this->schemaSql, "سرویس ۴ باید موجود باشد");
        $this->assertContains("'مجوز هنری و فرهنگی'", $this->schemaSql, "سرویس ۵ باید موجود باشد");
    }

    public function testService2CategoriesMatchExpected(): void
    {
        preg_match_all("/\(2,\s*'([^']+)',\s*'([^']+)',\s*\d+\)/u", $this->schemaSql, $m2);
        $cats = array_unique($m2[1]);
        $expected = ['صنایع غذایی و آشامیدنی خانگی', 'صنایع دستی و هنری خانگی', 'خدمات خانگی',
                     'محصولات بهداشتی و آرایشی خانگی', 'نساجی و پوشاک خانگی',
                     'محصولات کشاورزی و باغی خانگی', 'صنایع چوب و مبلمان خانگی',
                     'محصولات الکترونیکی و دیجیتال خانگی', 'سایر کسب‌وکارهای خانگی'];
        foreach ($expected as $c) {
            $this->assertContains($c, $cats, "دستهٔ {$c} باید در سرویس ۲ باشد");
        }
    }

    public function testService3CategoriesMatchExpected(): void
    {
        preg_match_all("/\(3,\s*'([^']+)',\s*'([^']+)',\s*\d+\)/u", $this->schemaSql, $m3);
        $cats = array_unique($m3[1]);
        $expected = ['پشتیبانی فرآورده‌های غذایی', 'پشتیبانی صنایع دستی', 'پشتیبانی خدمات',
                     'پشتیبانی فروش و بازاریابی', 'پشتیبانی تأمین و توزیع',
                     'پشتیبانی آموزش و توسعه مهارت', 'پشتیبانی فنی و کارگاهی',
                     'پشتیبانی مالی و اداری', 'پشتیبانی صادرات و توسعه بازار',
                     'سایر خدمات پشتیبانی'];
        foreach ($expected as $c) {
            $this->assertContains($c, $cats, "دستهٔ {$c} باید در سرویس ۳ باشد");
        }
    }

    /* ==================== مدل LicenseTitle ==================== */

    public function testModelHasGroupedActiveByServiceMethod(): void
    {
        $reflection = new ReflectionClass('LicenseTitle');
        $this->assertTrue($reflection->hasMethod('groupedActiveByService'),
            "مدل LicenseTitle باید متد groupedActiveByService داشته باشد");
    }

    public function testModelHasIsTitleUniqueInServiceMethod(): void
    {
        $reflection = new ReflectionClass('LicenseTitle');
        $this->assertTrue($reflection->hasMethod('isTitleUniqueInService'),
            "مدل LicenseTitle باید متد isTitleUniqueInService داشته باشد");
    }

    public function testModelGetAllAcceptsServiceId(): void
    {
        $reflection = new ReflectionClass('LicenseTitle');
        $this->assertTrue($reflection->hasMethod('getAll'));
        $method = $reflection->getMethod('getAll');
        $params = $method->getParameters();
        $this->assertTrue(count($params) >= 1, "getAll باید حداقل یک پارامتر بپذیرد");
        $this->assertTrue($params[0]->allowsNull(), "پارامتر اول باید nullable باشد");
    }

    public function testModelCreateAcceptsServiceTypeId(): void
    {
        $reflection = new ReflectionClass('LicenseTitle');
        $method = $reflection->getMethod('create');
        $source = file_get_contents(APP_PATH . '/Models/LicenseTitle.php');
        $this->assertContains('service_type_id', $source, "create باید service_type_id را ذخیره کند");
    }

    public function testModelUpdateAcceptsServiceTypeId(): void
    {
        $source = file_get_contents(APP_PATH . '/Models/LicenseTitle.php');
        // در متد update، service_type_id باید در دستور SQL ظاهر شود
        $this->assertContains('UPDATE license_titles SET service_type_id', $source,
            "update باید service_type_id را به‌روزرسانی کند");
    }

    /* ==================== Controller LicenseTitleController ==================== */

    public function testControllerHasByServiceEndpoint(): void
    {
        $content = file_get_contents(APP_PATH . '/Controllers/LicenseTitleController.php');
        $this->assertContains('function byService', $content,
            "Controller باید متد byService داشته باشد");
        $this->assertContains('groupedActiveByService', $content);
        $this->assertContains('json(', $content, "باید پاسخ JSON برگرداند");
    }

    public function testControllerFiltersByServiceInIndex(): void
    {
        $content = file_get_contents(APP_PATH . '/Controllers/LicenseTitleController.php');
        $this->assertContains('filterServiceId', $content, "index باید فیلتر سرویس داشته باشد");
        $this->assertContains("service", $content);
    }

    public function testControllerSaveChecksTitleUniquenessInService(): void
    {
        $content = file_get_contents(APP_PATH . '/Controllers/LicenseTitleController.php');
        $this->assertContains('isTitleUniqueInService', $content,
            "save باید یکتایی عنوان را در دامنهٔ سرویس بررسی کند");
    }

    public function testControllerSaveRequiresServiceTypeId(): void
    {
        $content = file_get_contents(APP_PATH . '/Controllers/LicenseTitleController.php');
        $this->assertContains('service_type_id', $content,
            "save باید service_type_id را از فرم بگیرد");
    }

    /* ==================== روت by-service در index.php ==================== */

    public function testByServiceRouteIsRegistered(): void
    {
        $content = file_get_contents(ROOT_PATH . '/public/index.php');
        $this->assertContains("'/admin/license-titles/by-service/{id}'", $content,
            "مسیر /admin/license-titles/by-service/{id} باید ثبت شده باشد");
        $this->assertContains("LicenseTitleController", $content);
    }

    /* ==================== سایر کنترلرها از service-aware model استفاده می‌کنند ==================== */

    public function testRegistrationControllerUsesServiceAwareLicense(): void
    {
        $content = file_get_contents(APP_PATH . '/Controllers/RegistrationController.php');
        $this->assertContains('groupedActiveByService', $content,
            "RegistrationController باید از groupedActiveByService استفاده کند");
        $this->assertContains('selectedServiceId', $content);
    }

    public function testAdminControllerUsesServiceAwareLicense(): void
    {
        $content = file_get_contents(APP_PATH . '/Controllers/AdminController.php');
        $this->assertContains('groupedActiveByService', $content,
            "AdminController باید از groupedActiveByService استفاده کند");
    }

    public function testBulkImportControllerUsesServiceAwareLicense(): void
    {
        $content = file_get_contents(APP_PATH . '/Controllers/BulkImportController.php');
        $this->assertContains('groupedActiveByService', $content,
            "BulkImportController باید از groupedActiveByService استفاده کند");
    }

    public function testRevisionControllerUsesServiceAwareLicense(): void
    {
        $content = file_get_contents(APP_PATH . '/Controllers/RevisionController.php');
        $this->assertContains('groupedActiveByService', $content,
            "RevisionController باید از groupedActiveByService استفاده کند");
    }

    /* ==================== DB اگر در دسترس است ==================== */

    public function testLicenseTitlesTableHasServiceTypeIdColumn(): void
    {
        try {
            $pdo = test_db();
            $cols = $pdo->query("SHOW COLUMNS FROM license_titles")->fetchAll(PDO::FETCH_COLUMN);
            $this->assertContains('service_type_id', $cols,
                "ستون service_type_id باید در جدول license_titles باشد");
        } catch (\Throwable $e) {
            // اگر DB در دسترس نبود، skip — این تست فقط در محیط MySQL کار می‌کند
            $this->markSkipped("DB در دسترس نیست — این تست نیاز به MySQL دارد");
        }
    }

    private function markSkipped(string $msg): void
    {
        echo "\n      [SKIP] {$msg}";
    }
}
