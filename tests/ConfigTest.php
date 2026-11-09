<?php
/**
 * تست‌های اعتبارسنجی پیکربندی
 * -----------------------------------------------------------------
 * بررسی می‌کند که ثابت‌های اصلی تعریف شده‌اند، فایل‌های ضروری موجودند،
 * و محتوای فایل‌های پیکربندی سازگار است.
 */
class ConfigTest extends TestCase
{
    public function testConstantsDefined(): void
    {
        $required = ['APP_VERSION', 'APP_DEBUG', 'DB_HOST', 'DB_NAME', 'BASE_URL',
                     'ROOT_PATH', 'APP_PATH', 'CORE_PATH', 'VIEW_PATH', 'CONFIG_PATH',
                     'MAX_UPLOAD_SIZE', 'ALLOWED_EXTENSIONS', 'USE_CDN', 'SESSION_LIFETIME',
                     'CRON_SECRET', 'APP_ENV', 'ERROR_LOG_PATH', 'ADMIN_IP_ALLOWLIST', 'IP_BLOCKLIST'];
        foreach ($required as $c) {
            $this->assertTrue(defined($c), "ثابت {$c} باید تعریف شده باشد");
        }
    }

    public function testVersionString(): void
    {
        $v = APP_VERSION;
        // SemVer: MAJOR.MINOR.PATCH
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $v, "نسخه باید SemVer باشد");
    }

    public function testEnvExampleHasAllKeys(): void
    {
        $content = file_get_contents(ROOT_PATH . '/.env.example');
        $this->assertNotEmpty($content, ".env.example باید موجود باشد");

        $requiredKeys = ['APP_DEBUG', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
                         'BASE_URL', 'USE_CDN', 'CRON_SECRET', 'SESSION_LIFETIME',
                         'APP_ENV', 'ERROR_LOG_PATH', 'ADMIN_IP_ALLOWLIST', 'IP_BLOCKLIST'];
        foreach ($requiredKeys as $k) {
            $this->assertContains($k . '=', $content, "کلید {$k} باید در .env.example باشد");
        }
    }

    public function testGitignoreProtectsEnv(): void
    {
        $gitignore = file_get_contents(ROOT_PATH . '/.gitignore');
        $this->assertContains('.env', $gitignore, ".env باید در .gitignore باشد");
        $this->assertContains('uploads/*', $gitignore, "uploads/* باید در .gitignore باشد");
    }

    public function testSchemaSqlExists(): void
    {
        $this->assertTrue(file_exists(ROOT_PATH . '/database/schema.sql'), "schema.sql باید موجود باشد");
        $content = file_get_contents(ROOT_PATH . '/database/schema.sql');
        $this->assertContains('CREATE TABLE IF NOT EXISTS license_titles', $content);
        $this->assertContains('CREATE TABLE IF NOT EXISTS service_types', $content);
        $this->assertContains('CREATE TABLE IF NOT EXISTS applicants', $content);
        $this->assertContains('service_type_id', $content, "schema باید service_type_id داشته باشد");
    }

    public function testMigrationsExist(): void
    {
        $expected = [
            '20251109_v2_upgrade.sql',
            '20251110_household_independent_steps.sql',
            '20251111_household_status_enum.sql',
            '20251112_license_titles_update.sql',
            '20251113_license_titles_service_specific.sql',
        ];
        foreach ($expected as $m) {
            $path = ROOT_PATH . '/database/migrations/' . $m;
            $this->assertTrue(file_exists($path), "مهاجرت {$m} باید موجود باشد");
        }
    }

    public function testLicenseTitlesServiceMigrationHasServiceTypeId(): void
    {
        $path = ROOT_PATH . '/database/migrations/20251113_license_titles_service_specific.sql';
        $content = file_get_contents($path);
        $this->assertContains('service_type_id', $content, "مهاجرت باید service_type_id را اضافه کند");
        $this->assertContains('ALTER TABLE license_titles', $content);
        $this->assertContains('FOREIGN KEY', $content);
        $this->assertContains('uniq_service_title', $content, "مهاجرت باید UNIQUE جدید را اضافه کند");
        // idempotent — باید information_schema را بررسی کند
        $this->assertContains('information_schema', $content, "مهاجرت باید idempotent باشد");
    }

    public function testWorkflowConfigValid(): void
    {
        $workflow = require CONFIG_PATH . '/workflow.php';
        $this->assertArrayHasKey('transitions', $workflow);
        $this->assertArrayHasKey('expert', $workflow['transitions']);
        $this->assertArrayHasKey('document_uploader', $workflow['transitions']);
        $this->assertArrayHasKey('admin', $workflow['transitions']);
        // admin از هر وضعیتی می‌تواند به هر وضعیتی برود
        $this->assertEmpty($workflow['transitions']['admin']['from'], "admin نباید محدودیت from داشته باشد");
        $this->assertEmpty($workflow['transitions']['admin']['to'], "admin نباید محدودیت to داشته باشد");
    }

    public function testStatusesConfigValid(): void
    {
        $cfg = require CONFIG_PATH . '/statuses.php';
        $this->assertArrayHasKey('statuses', $cfg);
        $this->assertArrayHasKey('terminal', $cfg);
        $this->assertArrayHasKey('pre_issue', $cfg);

        // همهٔ وضعیت‌های پایانی باید در فهرست وضعیت‌ها باشند
        foreach ($cfg['terminal'] as $terminal) {
            $this->assertArrayHasKey($terminal, $cfg['statuses'], "وضعیت پایانی {$terminal} باید در فهرست وضعیت‌ها باشد");
        }

        // بررسی وضعیت‌های اختصاصی مشاغل خانگی
        $hkStatuses = ['تطبیق آدرس با ثبت احوال', 'دریافت تصویر مجوز',
                       'تأیید اولیه در سامانه مشاغل خانگی', 'تأیید نهایی دریافت کد رهگیری'];
        foreach ($hkStatuses as $s) {
            $this->assertArrayHasKey($s, $cfg['statuses'], "وضعیت اختصاصی مشاغل خانگی {$s} باید موجود باشد");
            $this->assertEquals([2], $cfg['statuses'][$s]['services'] ?? null, "وضعیت {$s} باید فقط برای سرویس ۲ باشد");
        }

        // حداقل ۱۴ وضعیت
        $this->assertTrue(count($cfg['statuses']) >= 14, 'باید حداقل ۱۴ وضعیت موجود باشد');
    }

    public function testHelperFunctionsExist(): void
    {
        $required = ['setting', 'owner_name', 'icon', 'asset_url', 'build_notify_message'];
        foreach ($required as $fn) {
            $this->assertTrue(function_exists($fn), "تابع کمکی {$fn} باید موجود باشد");
        }
    }

    public function testAssetUrlFunction(): void
    {
        // asset_url باید ?v=... اضافه کند
        $url = asset_url('assets/css/style.css');
        $this->assertContains('?v=', $url, "asset_url باید پارامتر نسخه اضافه کند");
    }
}
