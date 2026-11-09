<?php
/**
 * تست‌های گردش کار وضعیت‌ها
 * -----------------------------------------------------------------
 * بررسی:
 *   - همهٔ وضعیت‌های موجود در config/statuses.php با ENUM در schema.sql هم‌خوان‌اند
 *   - وضعیت‌های اختصاصی مشاغل خانگی (service_type_id=2) درست برچسب‌گذاری شده‌اند
 *   - ماتریس انتقال (workflow.php) برای هر نقش معتبر است
 *   - وضعیت‌های terminal درست هستند
 */
class StatusWorkflowTest extends TestCase
{
    private array $statusConfig;
    private array $workflow;
    private string $schemaSql;

    protected function setUp(): void
    {
        $this->statusConfig = require CONFIG_PATH . '/statuses.php';
        $this->workflow = require CONFIG_PATH . '/workflow.php';
        $this->schemaSql = file_get_contents(ROOT_PATH . '/database/schema.sql');
    }

    /* ==================== تعداد وضعیت‌ها ==================== */

    public function testHasAllCoreStatuses(): void
    {
        $statuses = $this->statusConfig['statuses'];
        $core = ['پیش‌نویس', 'تکمیل اطلاعات', 'احراز نشانی', 'بررسی کارشناسی رشته',
                 'ثبت درخواست صدور', 'بارگذاری مدارک به سازمان', 'درخواست اصلاح مدارک',
                 'تأیید اولیه', 'تأیید نهایی', 'صدور مجوز', 'تحویل مجوز',
                 'مشاهده و چاپ', 'رد درخواست', 'لغو'];
        foreach ($core as $s) {
            $this->assertArrayHasKey($s, $statuses, "وضعیت هستهٔ {$s} باید موجود باشد");
        }
    }

    public function testHasHouseholdSpecificStatuses(): void
    {
        $statuses = $this->statusConfig['statuses'];
        $hk = ['تطبیق آدرس با ثبت احوال', 'دریافت تصویر مجوز',
               'تأیید اولیه در سامانه مشاغل خانگی', 'تأیید نهایی دریافت کد رهگیری'];
        foreach ($hk as $s) {
            $this->assertArrayHasKey($s, $statuses, "وضعیت اختصاصی مشاغل خانگی {$s} باید موجود باشد");
        }
    }

    public function testTotalStatusCountAtLeast18(): void
    {
        $count = count($this->statusConfig['statuses']);
        $this->assertTrue($count >= 18, "باید حداقل ۱۸ وضعیت موجود باشد — واقعی: {$count}");
    }

    /* ==================== وضعیت‌های اختصاصی مشاغل خانگی ==================== */

    public function testHouseholdStatusesAreService2Only(): void
    {
        $statuses = $this->statusConfig['statuses'];
        $hk = ['تطبیق آدرس با ثبت احوال', 'دریافت تصویر مجوز',
               'تأیید اولیه در سامانه مشاغل خانگی', 'تأیید نهایی دریافت کد رهگیری'];
        foreach ($hk as $s) {
            $services = $statuses[$s]['services'] ?? null;
            $this->assertEquals([2], $services, "وضعیت {$s} باید فقط برای سرویس ۲ باشد");
        }
    }

    public function testCoreStatusesAreNotServiceScoped(): void
    {
        // وضعیت‌های مشترک نباید کلید services داشته باشند
        $statuses = $this->statusConfig['statuses'];
        $core = ['پیش‌نویس', 'تکمیل اطلاعات', 'صدور مجوز', 'لغو', 'رد درخواست'];
        foreach ($core as $s) {
            $services = $statuses[$s]['services'] ?? null;
            $this->assertNull($services, "وضعیت {$s} نباید services داشته باشد (مشترک)");
        }
    }

    /* ==================== وضعیت‌های terminal ==================== */

    public function testTerminalStatusesAreValid(): void
    {
        $terminal = $this->statusConfig['terminal'];
        $expectedTerminal = ['رد درخواست', 'لغو', 'تحویل مجوز', 'تأیید نهایی دریافت کد رهگیری'];
        foreach ($expectedTerminal as $t) {
            $this->assertContains($t, $terminal, "وضعیت {$t} باید در terminal باشد");
        }
    }

    public function testNonTerminalStatusesAreNotInTerminal(): void
    {
        $terminal = $this->statusConfig['terminal'];
        $nonTerminal = ['پیش‌نویس', 'تکمیل اطلاعات', 'احراز نشانی', 'ثبت درخواست صدور',
                        'بارگذاری مدارک به سازمان', 'تأیید اولیه'];
        foreach ($nonTerminal as $s) {
            $this->assertNotContains($s, $terminal, "وضعیت {$s} نباید terminal باشد");
        }
    }

    /* ==================== pre_issue ==================== */

    public function testPreIssueStatusesAreCorrect(): void
    {
        $preIssue = $this->statusConfig['pre_issue'];
        $should = ['پیش‌نویس', 'تکمیل اطلاعات', 'احراز نشانی', 'بررسی کارشناسی رشته',
                   'ثبت درخواست صدور', 'بارگذاری مدارک به سازمان', 'درخواست اصلاح مدارک',
                   'تأیید اولیه', 'تأیید نهایی', 'تطبیق آدرس با ثبت احوال'];
        foreach ($should as $s) {
            $this->assertContains($s, $preIssue, "وضعیت {$s} باید در pre_issue باشد");
        }
    }

    public function testTerminalStatusesAreNotInPreIssue(): void
    {
        $preIssue = $this->statusConfig['pre_issue'];
        $terminal = $this->statusConfig['terminal'];
        $overlap = array_intersect($preIssue, $terminal);
        $this->assertEmpty($overlap, "هیچ وضعیتی نباید هم در pre_issue و هم در terminal باشد");
    }

    /* ==================== order field ==================== */

    public function testAllStatusesHaveOrderField(): void
    {
        foreach ($this->statusConfig['statuses'] as $name => $cfg) {
            $this->assertArrayHasKey('order', $cfg, "وضعیت {$name} باید order داشته باشد");
            $this->assertTrue(is_int($cfg['order']) || is_string($cfg['order']),
                "order باید عدد باشد: {$name}");
            $this->assertTrue($cfg['order'] > 0, "order باید مثبت باشد: {$name}");
        }
    }

    public function testAllStatusesHaveColorField(): void
    {
        foreach ($this->statusConfig['statuses'] as $name => $cfg) {
            $this->assertArrayHasKey('color', $cfg, "وضعیت {$name} باید color داشته باشد");
        }
    }

    /* ==================== workflow matries ==================== */

    public function testWorkflowHasAllRoles(): void
    {
        $transitions = $this->workflow['transitions'];
        $this->assertArrayHasKey('expert', $transitions);
        $this->assertArrayHasKey('document_uploader', $transitions);
        $this->assertArrayHasKey('admin', $transitions);
    }

    public function testExpertTransitionsAreValid(): void
    {
        $expert = $this->workflow['transitions']['expert'];
        $this->assertArrayHasKey('from', $expert);
        $this->assertArrayHasKey('to', $expert);
        $this->assertNotEmpty($expert['from'], "expert باید from داشته باشد");
        $this->assertNotEmpty($expert['to'], "expert باید to داشته باشد");

        // from باید شامل «تکمیل اطلاعات» باشد
        $this->assertContains('تکمیل اطلاعات', $expert['from']);
        // to باید شامل «احراز نشانی» باشد
        $this->assertContains('احراز نشانی', $expert['to']);
    }

    public function testDocumentUploaderTransitionsAreValid(): void
    {
        $du = $this->workflow['transitions']['document_uploader'];
        $this->assertNotEmpty($du['from']);
        $this->assertNotEmpty($du['to']);
        // از «ثبت درخواست صدور» شروع می‌شود
        $this->assertContains('ثبت درخواست صدور', $du['from']);
        // می‌تواند به «بارگذاری مدارک به سازمان» برود
        $this->assertContains('بارگذاری مدارک به سازمان', $du['to']);
        // می‌تواند به «صدور مجوز» برود
        $this->assertContains('صدور مجوز', $du['to']);
    }

    public function testAdminHasNoRestrictions(): void
    {
        $admin = $this->workflow['transitions']['admin'];
        $this->assertEmpty($admin['from'], "admin نباید محدودیت from داشته باشد");
        $this->assertEmpty($admin['to'], "admin نباید محدودیت to داشته باشد");
    }

    /* ==================== isStatusTransitionAllowed در Controller ==================== */

    public function testControllerHasIsStatusTransitionAllowed(): void
    {
        $content = file_get_contents(APP_PATH . '/Controllers/Controller.php');
        $this->assertContains('function isStatusTransitionAllowed', $content);
        $this->assertContains("role === 'admin'", $content, "admin باید همیشه مجاز باشد");
    }

    /* ==================== enum در schema ==================== */

    public function testSchemaEnumHasAllStatuses(): void
    {
        // بررسی اینکه ENUM در schema.sql شامل همهٔ وضعیت‌ها باشد
        $statuses = array_keys($this->statusConfig['statuses']);
        $missing = [];
        foreach ($statuses as $s) {
            // در schema، ENUM با کوتیشن تک می‌نویسد: 'وضعیت'
            if (!str_contains($this->schemaSql, "'{$s}'")) {
                $missing[] = $s;
            }
        }
        $this->assertEmpty($missing,
            "این وضعیت‌ها در schema.sql نیستند: " . implode(', ', $missing));
    }

    public function testSchemaEnumForHouseholdStatuses(): void
    {
        // در فایل migration جدا (20251111_household_status_enum.sql)
        $migration = file_get_contents(ROOT_PATH . '/database/migrations/20251111_household_status_enum.sql');
        $hkStatuses = ['تطبیق آدرس با ثبت احوال', 'دریافت تصویر مجوز',
                       'تأیید اولیه در سامانه مشاغل خانگی', 'تأیید نهایی دریافت کد رهگیری'];
        foreach ($hkStatuses as $s) {
            $this->assertContains($s, $migration, "مهاجرت باید وضعیت {$s} را به ENUM اضافه کند");
        }
    }

    public function testMigrationIsIdempotent(): void
    {
        $migration = file_get_contents(ROOT_PATH . '/database/migrations/20251111_household_status_enum.sql');
        // باید MODIFY COLUMN باشد (نه ADD COLUMN) — idempotent
        $this->assertContains('MODIFY COLUMN current_status', $migration,
            "مهاجرت باید MODIFY COLUMN باشد (نه ADD)");
    }
}
