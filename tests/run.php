#!/usr/bin/env php
<?php
/**
 * اجرای همهٔ تست‌ها — بدون وابستگی به PHPUnit
 * -----------------------------------------------------------------
 * نحوهٔ اجرا:
 *   php tests/run.php                  # اجرای همه
 *   php tests/run.php SecurityTest     # فقط یک کلاس
 *
 * برای استفاده از MySQL:
 *   TEST_DB_DRIVER=mysql TEST_DB_NAME=handicraft_license_test php tests/run.php
 *
 * برای استفاده از SQLite (in-memory — فقط تست‌های واحد مدل):
 *   TEST_DB_DRIVER=sqlite php tests/run.php
 */

require_once __DIR__ . '/bootstrap.php';

// فعال‌سازی نمایش خطاها در محیط تست
error_reporting(E_ALL);
ini_set('display_errors', '1');

// فهرست همهٔ کلاس‌های تست
$testClasses = [
    'SecurityTest',
    'ConfigTest',
    'ServiceScopeTest',
    'StatusWorkflowTest',
    'LicenseTitleTest',
    'WizardFormTest',
    'FunctionalTest',
];

// اگر آرگومان خط فرمان داده شد، فقط همان اجرا شود
if ($argc > 1) {
    $arg = $argv[1];
    if ($arg === '--list' || $arg === '-l') {
        echo "کلاس‌های تست موجود:\n";
        foreach ($testClasses as $c) {
            echo "  - {$c}\n";
        }
        exit(0);
    }
    if ($arg === '--help' || $arg === '-h') {
        echo "اجرای تست‌های پروژهٔ Mojavez\n\n";
        echo "نحوهٔ استفاده:\n";
        echo "  php tests/run.php                اجرای همهٔ تست‌ها\n";
        echo "  php tests/run.php SecurityTest   اجرای یک کلاس خاص\n";
        echo "  php tests/run.php --list         فهرست کلاس‌های تست\n\n";
        echo "متغیرهای محیطی:\n";
        echo "  TEST_DB_DRIVER=mysql|sqlite  (پیش‌فرض: mysql)\n";
        echo "  TEST_DB_HOST, TEST_DB_NAME, TEST_DB_USER, TEST_DB_PASS\n";
        exit(0);
    }
    $testClasses = [$arg];
}

$colors = [
    'red'    => "\033[31m",
    'green'  => "\033[32m",
    'yellow' => "\033[33m",
    'blue'   => "\033[34m",
    'reset'  => "\033[0m",
    'bold'   => "\033[1m",
];

$totalPass = 0;
$totalFail = 0;
$failures = [];

foreach ($testClasses as $className) {
    $file = __DIR__ . '/' . $className . '.php';
    if (!file_exists($file)) {
        echo "{$colors['red']}✘ کلاس تست یافت نشد: {$className} ({$file}){$colors['reset']}\n";
        $totalFail++;
        continue;
    }
    require_once $file;

    if (!class_exists($className)) {
        echo "{$colors['red']}✘ کلاس {$className} پس از require همچنان تعریف نشد{$colors['reset']}\n";
        $totalFail++;
        continue;
    }

    echo "\n{$colors['bold']}═══════════════════════════════════════════════════════════════\n";
    echo "  ▶ {$className}\n";
    echo "═══════════════════════════════════════════════════════════════{$colors['reset']}\n";

    try {
        /** @var TestCase $test */
        $test = new $className();
        // استفاده از reflection برای پیدا کردن متدهای test*
        $reflection = new ReflectionClass($test);
        // پیدا کردن همهٔ متدهای public که با test شروع می‌شوند و در همین کلاس تعریف شده‌اند
        $testMethods = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
            if (str_starts_with($m->name, 'test') && $m->getDeclaringClass()->getName() === $className) {
                $testMethods[] = $m->name;
            }
        }
        // اگر هیچ متد test* نبود، متد run را صدا بزن
        if (empty($testMethods)) {
            $testMethods = ['run'];
        }

        sort($testMethods);

        foreach ($testMethods as $method) {
            echo "  • {$method} ... ";
            try {
                $test->$method();
                echo "{$colors['green']}✓ PASS{$colors['reset']}\n";
                $totalPass++;
            } catch (AssertionFailed $e) {
                echo "{$colors['red']}✗ FAIL{$colors['reset']}\n";
                echo "    {$colors['red']}→ " . $e->getMessage() . "{$colors['reset']}\n";
                $failures[] = "{$className}::{$method}: " . $e->getMessage();
                $totalFail++;
            } catch (\Throwable $e) {
                echo "{$colors['red']}💥 ERROR{$colors['reset']}\n";
                echo "    {$colors['red']}→ " . get_class($e) . ": " . $e->getMessage() . "{$colors['reset']}\n";
                echo "    {$colors['yellow']}  @ " . basename($e->getFile()) . ":" . $e->getLine() . "{$colors['reset']}\n";
                $failures[] = "{$className}::{$method}: ERROR " . get_class($e) . ": " . $e->getMessage();
                $totalFail++;
            }
        }
    } catch (\Throwable $e) {
        echo "{$colors['red']}✘ خطا در ساخت کلاس: " . $e->getMessage() . "{$colors['reset']}\n";
        $failures[] = "{$className}: " . $e->getMessage();
        $totalFail++;
    }
}

echo "\n{$colors['bold']}═══════════════════════════════════════════════════════════════\n";
echo "  خلاصهٔ نهایی\n";
echo "═══════════════════════════════════════════════════════════════{$colors['reset']}\n";
echo "  {$colors['green']}PASS: {$totalPass}{$colors['reset']}  |  {$colors['red']}FAIL: {$totalFail}{$colors['reset']}\n";

if (!empty($failures)) {
    echo "\n{$colors['red']}=== شکست‌ها ==={$colors['reset']}\n";
    foreach ($failures as $f) {
        echo "  {$colors['red']}✗{$colors['reset']} {$f}\n";
    }
}

exit($totalFail === 0 ? 0 : 1);
