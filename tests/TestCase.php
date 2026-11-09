<?php
/**
 * کلاس پایهٔ تست — بدون وابستگی به PHPUnit
 * -----------------------------------------------------------------
 * هر کلاس تست از TestCase ارث می‌برد و متدهای test* را تعریف می‌کند.
 * متد assertion ها در صورت شکست، استثنا AssertionFailed می‌اندازند.
 *
 * نحوهٔ استفاده:
 *   class MyTest extends TestCase {
 *       public function testSomething(): void {
 *           $this->assertTrue(1 + 1 === 2, 'جمع باید ۲ باشد');
 *           $this->assertEquals('预期的', '预期的', 'مقادیر برابرند');
 *       }
 *   }
 */

// اطمینان از بارگذاری bootstrap — اگر از طریق run.php فراخوانی نشده باشد
if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/bootstrap.php';
}

class AssertionFailed extends \Exception {}

abstract class TestCase
{
    /** نام کلاس تست */
    public function name(): string { return static::class; }

    /**
     * متد اجرای تست — به‌صورت پیش‌فرض هیچ کاری نمی‌کند.
     * کلاس‌های فرزند می‌توانند متدهای test* تعریف کنند که توسط runner
     * به‌صورت خودکار شناسایی و اجرا می‌شوند. در صورت نیاز می‌توانند این
     * متد را نیز override کنند.
     */
    public function run(): void
    {
        // runner به‌صورت خودکار متدهای test* را پیدا می‌کند — این متد صرفاً
        // برای سازگاری با کلاس‌هایی است که فقط run() دارند.
    }

    // ==================== assertion ها ====================

    public function assertTrue(bool $cond, string $msg = ''): void
    {
        if (!$cond) throw new AssertionFailed($msg ?: 'انتظار true بود');
    }

    public function assertFalse(bool $cond, string $msg = ''): void
    {
        if ($cond) throw new AssertionFailed($msg ?: 'انتظار false بود');
    }

    public function assertEquals(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            throw new AssertionFailed(
                $msg . " — انتظار: " . $this->dump($expected) . " | واقعی: " . $this->dump($actual)
            );
        }
    }

    public function assertNotEquals(mixed $a, mixed $b, string $msg = ''): void
    {
        if ($a === $b) throw new AssertionFailed($msg ?: 'نباید برابر باشند');
    }

    public function assertNull(mixed $v, string $msg = ''): void
    {
        if ($v !== null) throw new AssertionFailed($msg ?: 'انتظار null بود');
    }

    public function assertNotNull(mixed $v, string $msg = ''): void
    {
        if ($v === null) throw new AssertionFailed($msg ?: 'انتظار non-null بود');
    }

    public function assertEmpty(mixed $v, string $msg = ''): void
    {
        if (!empty($v)) throw new AssertionFailed($msg ?: 'انتظار empty بود');
    }

    public function assertNotEmpty(mixed $v, string $msg = ''): void
    {
        if (empty($v)) throw new AssertionFailed($msg ?: 'انتظار non-empty بود');
    }

    public function assertContains(string $needle, string|array $haystack, string $msg = ''): void
    {
        if (is_array($haystack)) {
            if (!in_array($needle, $haystack, true)) {
                throw new AssertionFailed($msg . " — '{$needle}' در آرایه نیست");
            }
            return;
        }
        if (!str_contains($haystack, $needle)) {
            throw new AssertionFailed($msg . " — رشتهٔ '{$needle}' در متن یافت نشد");
        }
    }

    public function assertNotContains(string $needle, string|array $haystack, string $msg = ''): void
    {
        if (is_array($haystack)) {
            if (in_array($needle, $haystack, true)) {
                throw new AssertionFailed($msg . " — '{$needle}' نباید در آرایه باشد");
            }
            return;
        }
        if (str_contains($haystack, $needle)) {
            throw new AssertionFailed($msg . " — رشتهٔ '{$needle}' نباید در متن باشد");
        }
    }

    public function assertCount(int $expected, array|Countable $arr, string $msg = ''): void
    {
        $actual = count($arr);
        if ($actual !== $expected) {
            throw new AssertionFailed($msg . " — انتظار {$expected} آیتم، واقعی {$actual}");
        }
    }

    public function assertGreaterThan(mixed $a, mixed $b, string $msg = ''): void
    {
        if (!($b > $a)) throw new AssertionFailed($msg ?: "انتظار {$b} > {$a}");
    }

    public function assertGreaterThanOrEqual(mixed $a, mixed $b, string $msg = ''): void
    {
        if (!($b >= $a)) throw new AssertionFailed($msg ?: "انتظار {$b} >= {$a}");
    }

    public function assertArrayHasKey(string|int $key, array $arr, string $msg = ''): void
    {
        if (!array_key_exists($key, $arr)) {
            throw new AssertionFailed($msg . " — کلید '{$key}' در آرایه نیست");
        }
    }

    public function assertInstanceOf(string $class, mixed $obj, string $msg = ''): void
    {
        if (!($obj instanceof $class)) {
            throw new AssertionFailed($msg . " — انتظار نمونهٔ {$class}");
        }
    }

    public function assertMatchesRegularExpression(string $pattern, string $subject, string $msg = ''): void
    {
        if (!preg_match($pattern, $subject)) {
            throw new AssertionFailed($msg . " — الگو روی رشته تطابق ندارد");
        }
    }

    private function dump(mixed $v): string
    {
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_null($v)) return 'null';
        if (is_array($v)) return 'array(' . count($v) . ')';
        if (is_string($v)) return "'" . (strlen($v) > 80 ? substr($v, 0, 80) . '...' : $v) . "'";
        return (string)$v;
    }

    /** اجرای یک callback و انتظار پرتاب استثنا */
    public function expectException(string $exceptionClass, callable $fn, string $msg = ''): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            if (!($e instanceof $exceptionClass) && !is_subclass_of($e, $exceptionClass)) {
                throw new AssertionFailed($msg . " — استثنای دیگری پرتاب شد: " . get_class($e) . ": " . $e->getMessage());
            }
            return;
        }
        throw new AssertionFailed($msg . " — هیچ استثنایی پرتاب نشد (انتظار {$exceptionClass})");
    }
}
