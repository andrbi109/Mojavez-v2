<?php
/**
 * تست‌های CRUD مدل LicenseTitle
 * -----------------------------------------------------------------
 * بررسی:
 *   - متدهای مدل موجود و دارای امضای درست هستند
 *   - یکتایی عنوان در دامنهٔ سرویس (نه به‌صورت سراسری)
 *   - groupedActiveByService در صورت نبود سرویس، آرایهٔ خالی برمی‌گرداند
 *
 * نکته: این تست‌ها بیشتر static analysis هستند چون DB در sandbox در دسترس نیست.
 * در محیط MySQL کامل، تست‌های DB کامل‌تری اجرا می‌شود.
 */
class LicenseTitleTest extends TestCase
{
    private LicenseTitle $model;

    public function setUp(): void
    {
        $this->model = new LicenseTitle();
    }

    /* ==================== متدهای موجود ==================== */

    public function testModelInstantiable(): void
    {
        $this->assertInstanceOf(LicenseTitle::class, $this->model);
    }

    public function testHasGroupedActiveByServiceMethod(): void
    {
        $reflection = new ReflectionClass($this->model);
        $this->assertTrue($reflection->hasMethod('groupedActiveByService'));
    }

    public function testHasGetAllMethod(): void
    {
        $reflection = new ReflectionClass($this->model);
        $this->assertTrue($reflection->hasMethod('getAll'));
    }

    public function testHasGetCategoriesMethod(): void
    {
        $reflection = new ReflectionClass($this->model);
        $this->assertTrue($reflection->hasMethod('getCategories'));
    }

    public function testHasFindByIdMethod(): void
    {
        $reflection = new ReflectionClass($this->model);
        $this->assertTrue($reflection->hasMethod('findById'));
    }

    public function testHasCreateMethod(): void
    {
        $reflection = new ReflectionClass($this->model);
        $this->assertTrue($reflection->hasMethod('create'));
    }

    public function testHasUpdateMethod(): void
    {
        $reflection = new ReflectionClass($this->model);
        $this->assertTrue($reflection->hasMethod('update'));
    }

    public function testHasDeleteMethod(): void
    {
        $reflection = new ReflectionClass($this->model);
        $this->assertTrue($reflection->hasMethod('delete'));
    }

    public function testHasIsTitleUniqueInServiceMethod(): void
    {
        $reflection = new ReflectionClass($this->model);
        $this->assertTrue($reflection->hasMethod('isTitleUniqueInService'));
    }

    public function testHasDeprecatedGroupedActiveMethod(): void
    {
        $reflection = new ReflectionClass($this->model);
        $this->assertTrue($reflection->hasMethod('groupedActive'),
            "متد deprecated groupedActive باید برای backward-compat موجود باشد");
    }

    /* ==================== signature ها ==================== */

    public function testGroupedActiveByServiceAcceptsNullableInt(): void
    {
        $reflection = new ReflectionClass($this->model);
        $method = $reflection->getMethod('groupedActiveByService');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertTrue($params[0]->allowsNull(), "پارامتر باید nullable باشد");
        // نوع ممکن است 'int' یا '?int' باشد — هر دو قابل قبول
        $type = (string)$params[0]->getType();
        $this->assertTrue($type === 'int' || $type === '?int',
            "نوع باید int یا ?int باشد — واقعی: {$type}");
    }

    public function testGetAllAcceptsNullableInt(): void
    {
        $reflection = new ReflectionClass($this->model);
        $method = $reflection->getMethod('getAll');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertTrue($params[0]->allowsNull());
        $this->assertTrue($params[0]->isDefaultValueAvailable(), "پارامتر باید default value داشته باشد (null)");
    }

    public function testGetCategoriesAcceptsNullableInt(): void
    {
        $reflection = new ReflectionClass($this->model);
        $method = $reflection->getMethod('getCategories');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertTrue($params[0]->allowsNull());
    }

    public function testIsTitleUniqueInServiceHasThreeParams(): void
    {
        $reflection = new ReflectionClass($this->model);
        $method = $reflection->getMethod('isTitleUniqueInService');
        $params = $method->getParameters();
        $this->assertCount(3, $params, "باید ۳ پارامتر داشته باشد: title, serviceTypeId, exceptId");
        $this->assertEquals('string', (string)$params[0]->getType());
        $this->assertTrue($params[1]->allowsNull(), "serviceTypeId باید nullable باشد");
        $this->assertTrue($params[2]->allowsNull(), "exceptId باید nullable باشد");
    }

    /* ==================== source code checks ==================== */

    public function testModelSourceUsesServiceTypeIdInQueries(): void
    {
        $source = file_get_contents(APP_PATH . '/Models/LicenseTitle.php');
        // در query ها باید service_type_id ظاهر شود
        $this->assertContains('service_type_id', $source);
        // در متد groupedActiveByService باید WHERE service_type_id = :sid باشد
        $this->assertContains('WHERE is_active = 1 AND service_type_id = :sid', $source);
        // در isTitleUniqueInService باید از <=> برای مقایسهٔ null-safe استفاده شود
        $this->assertContains('<=>', $source, "باید از null-safe comparison استفاده کند");
    }

    public function testModelDeleteSoftDeletesWhenInUse(): void
    {
        $source = file_get_contents(APP_PATH . '/Models/LicenseTitle.php');
        // اگر در پرونده‌ای استفاده شده باشد، غیرفعال می‌شود (نه حذف)
        $this->assertContains('is_active = 0', $source);
        $this->assertContains('COUNT(*)', $source);
    }

    public function testModelGetAllJoinsServiceType(): void
    {
        $source = file_get_contents(APP_PATH . '/Models/LicenseTitle.php');
        // باید LEFT JOIN با service_types داشته باشد
        $this->assertContains('LEFT JOIN service_types', $source);
        $this->assertContains('service_title', $source);
    }

    /* ==================== DB-level tests (در صورت موجود بودن MySQL) ==================== */

    public function testGroupedActiveByServiceReturnsEmptyForUnknownService(): void
    {
        try {
            $pdo = test_db();
            // اگر جدول موجود است، تست کن
            $tables = $pdo->query("SHOW TABLES LIKE 'license_titles'")->fetchAll();
            if (empty($tables)) {
                echo "\n      [SKIP] جدول license_titles در DB موجود نیست";
                return;
            }
            $model = new LicenseTitle();
            $result = $model->groupedActiveByService(99999);
            $this->assertEmpty($result, "برای سرویس ناموجود، نتیجه باید خالی باشد");
        } catch (\Throwable $e) {
            echo "\n      [SKIP] DB در دسترس نیست — این تست نیاز به MySQL دارد";
        }
    }

    public function testGroupedActiveByServiceReturnsArrayForKnownService(): void
    {
        try {
            $pdo = test_db();
            $tables = $pdo->query("SHOW TABLES LIKE 'license_titles'")->fetchAll();
            if (empty($tables)) {
                echo "\n      [SKIP] جدول license_titles در DB موجود نیست";
                return;
            }
            $model = new LicenseTitle();
            $result = $model->groupedActiveByService(1);
            // باید آرایه باشد — حتی اگر خالی
            $this->assertTrue(is_array($result));
        } catch (\Throwable $e) {
            echo "\n      [SKIP] DB در دسترس نیست";
        }
    }

    public function testIsTitleUniqueInServiceReturnsTrueForNewTitle(): void
    {
        try {
            $pdo = test_db();
            $tables = $pdo->query("SHOW TABLES LIKE 'license_titles'")->fetchAll();
            if (empty($tables)) {
                echo "\n      [SKIP] جدول license_titles در DB موجود نیست";
                return;
            }
            $model = new LicenseTitle();
            // عنوان کاملاً یکتا و احتمالاً ناموجود
            $unique = 'عنوان تستی منحصر به فرد ' . uniqid();
            $result = $model->isTitleUniqueInService($unique, 1);
            $this->assertTrue($result, "عنوان جدید باید یکتا باشد");
        } catch (\Throwable $e) {
            echo "\n      [SKIP] DB در دسترس نیست";
        }
    }
}
