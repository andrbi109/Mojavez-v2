<?php
/**
 * تست‌های امنیتی
 * -----------------------------------------------------------------
 * بررسی:
 *   - public/robots.txt وجود دارد و بات‌های کلیدی را مسدود می‌کند
 *   - public/.htaccess قواعد امنیتی دارد
 *   - متاتگ‌های noindex در همهٔ layoutها
 *   - هدرهای امنیتی در public/index.php
 *   - CSRF token و session hardening
 *   - فایل‌های حساس (.env, .git, .sql) مسدود می‌شوند
 */
class SecurityTest extends TestCase
{
    /* ==================== robots.txt ==================== */

    public function testRobotsTxtExists(): void
    {
        $path = ROOT_PATH . '/public/robots.txt';
        $this->assertTrue(file_exists($path), 'public/robots.txt باید موجود باشد');
    }

    public function testRobotsTxtBlocksAllAgents(): void
    {
        $content = file_get_contents(ROOT_PATH . '/public/robots.txt');
        // User-agent: * Disallow: /
        $this->assertContains('User-agent: *', $content);
        $this->assertContains('Disallow: /', $content);
    }

    public function testRobotsTxtBlocksAiBots(): void
    {
        $content = file_get_contents(ROOT_PATH . '/public/robots.txt');
        $aiBots = ['GPTBot', 'ChatGPT-User', 'CCBot', 'anthropic-ai', 'Claude-Web',
                   'cohere-ai', 'Google-Extended', 'PerplexityBot', 'Amazonbot',
                   'Bytespider', 'Diffbot', 'Meta-ExternalAgent', 'OAI-SearchBot',
                   'Applebot-Extended'];
        foreach ($aiBots as $bot) {
            $this->assertContains($bot, $content, "بات {$bot} باید در robots.txt مسدود شود");
        }
    }

    public function testRobotsTxtBlocksSearchEngines(): void
    {
        $content = file_get_contents(ROOT_PATH . '/public/robots.txt');
        $searchEngines = ['Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot',
                          'Baiduspider', 'YandexBot', 'Sogou', 'Exabot',
                          'ia_archiver', 'Applebot'];
        foreach ($searchEngines as $bot) {
            $this->assertContains($bot, $content, "موتور جستجو {$bot} باید مسدود شود");
        }
    }

    public function testRobotsTxtBlocksSeoBots(): void
    {
        $content = file_get_contents(ROOT_PATH . '/public/robots.txt');
        $seoBots = ['SemrushBot', 'AhrefsBot', 'MJ12bot', 'DotBot', 'BLEXBot',
                    'SeznamBot', 'PetalBot'];
        foreach ($seoBots as $bot) {
            $this->assertContains($bot, $content, "بات SEO {$bot} باید مسدود شود");
        }
    }

    /* ==================== .htaccess ==================== */

    public function testHtaccessBlocksBots(): void
    {
        $htaccess = file_get_contents(ROOT_PATH . '/public/.htaccess');
        $this->assertContains('GPTBot', $htaccess, ".htaccess باید GPTBot را مسدود کند");
        $this->assertContains('ChatGPT-User', $htaccess);
        $this->assertContains('anthropic-ai', $htaccess);
        $this->assertContains('Googlebot', $htaccess);
    }

    public function testHtaccessSetsSecurityHeaders(): void
    {
        $htaccess = file_get_contents(ROOT_PATH . '/public/.htaccess');
        $this->assertContains('X-Robots-Tag', $htaccess, "باید X-Robots-Tag داشته باشد");
        $this->assertContains('X-Frame-Options', $htaccess);
        $this->assertContains('X-Content-Type-Options', $htaccess);
        $this->assertContains('Referrer-Policy', $htaccess);
        $this->assertContains('Permissions-Policy', $htaccess);
        $this->assertContains('Content-Security-Policy', $htaccess);
    }

    public function testHtaccessBlocksSensitiveFiles(): void
    {
        $htaccess = file_get_contents(ROOT_PATH . '/public/.htaccess');
        // .htaccess شامل FilesMatch با regex است که پسوندهای حساس را مسدود می‌کند
        // رشتهٔ تحت‌اللفظی 'env' در آن موجود است (داخل \.(env|sql|...))
        $this->assertContains('env', $htaccess, "باید دسترسی به فایل‌های env را مسدود کند");
        $this->assertContains('sql', $htaccess);
        $this->assertContains('bak', $htaccess);
        $this->assertContains('log', $htaccess);
        $this->assertContains('Require all denied', $htaccess);
    }

    public function testHtaccessPreventsDirectoryListing(): void
    {
        $htaccess = file_get_contents(ROOT_PATH . '/public/.htaccess');
        $this->assertContains('Options -Indexes', $htaccess, "باید -Indexes داشته باشد");
    }

    public function testHtaccessHasCompression(): void
    {
        $htaccess = file_get_contents(ROOT_PATH . '/public/.htaccess');
        $this->assertContains('mod_deflate', $htaccess, "باید فشرده‌سازی داشته باشد");
        $this->assertContains('DEFLATE', $htaccess);
    }

    public function testHtaccessHasCacheControl(): void
    {
        $htaccess = file_get_contents(ROOT_PATH . '/public/.htaccess');
        $this->assertContains('mod_expires', $htaccess, "باید کش مرورگر داشته باشد");
        $this->assertContains('ExpiresActive', $htaccess);
    }

    public function testHtaccessDoesNotContainInvalidDirectives(): void
    {
        $htaccess = file_get_contents(ROOT_PATH . '/public/.htaccess');
        // این دستورات فقط در server config معتبرند، نه در .htaccess
        $this->assertNotContains('ServerTokens Prod', $htaccess, "ServerTokens نباید در .htaccess باشد");
        $this->assertNotContains('ServerSignature Off', $htaccess, "ServerSignature نباید در .htaccess باشد");
        $this->assertNotContains('SetHandler none', $htaccess, "SetHandler none نباید در .htaccess باشد (جلوگیری از اجرای index.php)");
    }

    /* ==================== layouts با noindex ==================== */

    public function testPublicLayoutHasNoindex(): void
    {
        $layout = file_get_contents(VIEW_PATH . '/layout.php');
        $this->assertContains('name="robots"', $layout, "layout.php باید متاتگ robots داشته باشد");
        $this->assertContains('noindex', $layout);
        $this->assertContains('nofollow', $layout);
        $this->assertContains('noarchive', $layout);
    }

    public function testAdminLayoutHasNoindex(): void
    {
        $layout = file_get_contents(VIEW_PATH . '/admin_layout.php');
        $this->assertContains('noindex', $layout, "admin_layout.php باید noindex داشته باشد");
        $this->assertContains('nofollow', $layout);
    }

    public function testUserLayoutHasNoindex(): void
    {
        $layout = file_get_contents(VIEW_PATH . '/user_layout.php');
        $this->assertContains('noindex', $layout, "user_layout.php باید noindex داشته باشد");
    }

    public function testPrintLayoutHasNoindex(): void
    {
        $layout = file_get_contents(VIEW_PATH . '/layout_print.php');
        $this->assertContains('noindex', $layout, "layout_print.php باید noindex داشته باشد");
    }

    /* ==================== public/index.php هدرها ==================== */

    public function testIndexPhpSetsSecurityHeaders(): void
    {
        $content = file_get_contents(ROOT_PATH . '/public/index.php');
        $this->assertContains("X-Robots-Tag", $content, "index.php باید X-Robots-Tag ست کند");
        $this->assertContains("X-Content-Type-Options", $content);
        $this->assertContains("X-Frame-Options", $content);
        $this->assertContains("Referrer-Policy", $content);
        $this->assertContains("Permissions-Policy", $content);
        $this->assertContains("Cache-Control", $content, "باید Cache-Control داشته باشد");
        $this->assertContains("no-store", $content, "صفحات پویا نباید کش شوند");
    }

    public function testIndexPhpHasGlobalExceptionHandler(): void
    {
        $content = file_get_contents(ROOT_PATH . '/public/index.php');
        $this->assertContains('set_exception_handler', $content, "باید exception handler داشته باشد");
        $this->assertContains('set_error_handler', $content);
        // در محیط تولید نباید استک تریس نمایش دهد
        $this->assertContains('APP_DEBUG', $content, "باید APP_DEBUG را بررسی کند");
    }

    public function testIndexPhpHasIpBlocklist(): void
    {
        $content = file_get_contents(ROOT_PATH . '/public/index.php');
        $this->assertContains('IP_BLOCKLIST', $content, "باید IP_BLOCKLIST را بررسی کند");
    }

    public function testIndexPhpHasHealthEndpoint(): void
    {
        $content = file_get_contents(ROOT_PATH . '/public/index.php');
        $this->assertContains("/health", $content, "باید /health endpoint داشته باشد");
        $this->assertContains('healthy', $content);
    }

    /* ==================== Session hardening ==================== */

    public function testSessionClassHasHardening(): void
    {
        $content = file_get_contents(CORE_PATH . '/Session.php');
        $this->assertContains('use_strict_mode', $content, "باید strict_mode داشته باشد");
        $this->assertContains('use_only_cookies', $content, "باید only_cookies داشته باشد");
        $this->assertContains('cookie_httponly', $content, "باید httponly داشته باشد");
        $this->assertContains('cookie_samesite', $content, "باید samesite داشته باشد");
    }

    public function testSessionClassHasCsrfProtection(): void
    {
        $content = file_get_contents(CORE_PATH . '/Session.php');
        $this->assertContains('csrfToken', $content);
        $this->assertContains('checkCsrf', $content);
        $this->assertContains('hash_equals', $content, "باید مقایسهٔ زمان‌ثابت استفاده کند");
        $this->assertContains('random_bytes', $content, "باید از random_bytes برای تولید توکن استفاده کند");
    }

    public function testSessionClassHasRateLimiting(): void
    {
        $content = file_get_contents(CORE_PATH . '/Session.php');
        $this->assertContains('hit', $content, "باید متد hit داشته باشد");
        $this->assertContains('isLimited', $content, "باید متد isLimited داشته باشد");
        $this->assertContains('retryAfter', $content);
    }

    public function testSessionClassHasRegeneration(): void
    {
        $content = file_get_contents(CORE_PATH . '/Session.php');
        $this->assertContains('regenerate', $content, "باید قابلیت بازتولید نشست داشته باشد");
        $this->assertContains('session_regenerate_id', $content);
        $this->assertContains('_session_created_at', $content, "باید بازتولید دوره‌ای داشته باشد");
    }

    /* ==================== Controller requireLogin ==================== */

    public function testControllerChecksAdminIpAllowlist(): void
    {
        $content = file_get_contents(APP_PATH . '/Controllers/Controller.php');
        $this->assertContains('ADMIN_IP_ALLOWLIST', $content, "requireLogin باید allowlist را بررسی کند");
        $this->assertContains('HTTP_X_FORWARDED_FOR', $content, "باید از پروکسی معتبر پشتیبانی کند");
        $this->assertContains('filter_var', $content, "باید IP را اعتبارسنجی کند");
    }

    public function testControllerHasRoleCheck(): void
    {
        $content = file_get_contents(APP_PATH . '/Controllers/Controller.php');
        $this->assertContains('requireRole', $content);
        $this->assertContains('requireLogin', $content);
        $this->assertContains('requireUserLogin', $content);
        $this->assertContains('requireAnyLogin', $content);
    }

    public function testControllerHasCsrfCheck(): void
    {
        $content = file_get_contents(APP_PATH . '/Controllers/Controller.php');
        $this->assertContains('verifyCsrf', $content);
        $this->assertContains('csrf_invalid', $content, "باید کد خطای csrf_invalid برگرداند");
    }

    /* ==================== uploads/.htaccess ==================== */

    public function testUploadsHtaccessBlocksAccess(): void
    {
        $path = ROOT_PATH . '/uploads/.htaccess';
        $this->assertTrue(file_exists($path), "uploads/.htaccess باید موجود باشد");
        $content = file_get_contents($path);
        $this->assertContains('Require all denied', $content, "uploads باید Require all denied داشته باشد");
        $this->assertContains('php_flag engine off', $content, "اجرای PHP باید خاموش باشد");
    }

    /* ==================== Validator ==================== */

    public function testValidatorHasAllRules(): void
    {
        $content = file_get_contents(CORE_PATH . '/Validator.php');
        $rules = ['required', 'minLength', 'maxLength', 'persianAlpha',
                  'nationalCode', 'legalNationalId', 'postalCode', 'mobile',
                  'unsignedInt', 'enum', 'unique'];
        foreach ($rules as $r) {
            $this->assertContains("function {$r}", $content, "Validator باید rule {$r} داشته باشد");
        }
    }

    public function testValidatorUsesPreparedStatements(): void
    {
        $content = file_get_contents(CORE_PATH . '/Validator.php');
        $this->assertContains('prepare(', $content, "Validator باید از prepared statements استفاده کند");
        $this->assertContains('bindValue', $content);
    }

    /* ==================== Database singleton ==================== */

    public function testDatabaseIsSingleton(): void
    {
        $content = file_get_contents(CORE_PATH . '/Database.php');
        $this->assertContains('private static', $content, "Database باید singleton باشد");
        $this->assertContains('ERRMODE_EXCEPTION', $content, "باید exception mode داشته باشد");
        $this->assertContains('EMULATE_PREPARES', $content, "باید prepares را شبیه‌سازی نکند");
        $this->assertContains('utf8mb4', $content, "باید از utf8mb4 استفاده کند");
    }

    /* ==================== PHP version ==================== */

    public function testPhpVersionIsCompatible(): void
    {
        $v = PHP_VERSION_ID;
        // پشتیبانی از PHP 8.0 تا 8.5+
        $this->assertTrue($v >= 80000, "نیاز به PHP 8.0+ — فعلی: " . PHP_VERSION);
    }
}
