<?php
/**
 * لودر سادهٔ فایل .env — وابستگی به کتابخانهٔ خارجی ندارد.
 * ----------------------------------------------------------------
 * فایل .env را در ریشهٔ پروژه می‌خواند و متغیرهای محیطی را در $_ENV و getenv() قرار می‌دهد.
 * اگر فایل موجود نباشد، سکوت می‌کند (محیط‌های تولید ممکن است از متغیرهای محیطی واقعی
 * سرور استفاده کنند — Apache SetEnv، docker-compose environment، systemd EnvironmentFile).
 *
 * نکتهٔ امنیتی: این فایل هرگز نباید مقادیر حساس را در خروجی نمایش دهد.
 */

if (!function_exists('load_env')) {
    /**
     * بارگذاری فایل .env در صورت وجود. فقط یک بار اجرا می‌شود.
     *
     * @param string|null $path مسیر فایل .env (پیش‌فرض: ROOT_PATH/.env)
     */
    function load_env(?string $path = null): void
    {
        static $loaded = false;
        if ($loaded) return;
        $loaded = true;

        $path = $path ?? (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__)) . '/.env';
        if (!is_file($path) || !is_readable($path)) {
            return; // سکوت — فایل .env اختیاری است
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) return;

        foreach ($lines as $line) {
            $line = trim($line);
            // نادیده گرفتن کامنت‌ها
            if ($line === '' || $line[0] === '#') continue;
            // فقط خطوط KEY=value
            if (!str_contains($line, '=')) continue;

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // حذف کوتیشن‌های اطراف مقدار
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // اگر متغیر قبلاً از محیط سرور تنظیم شده (Apache SetEnv، docker)، آن را دور نزن
            if (getenv($key) !== false) continue;
            if (isset($_ENV[$key])) continue;

            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

if (!function_exists('env')) {
    /**
     * دریافت مقدار یک متغیر محیطی با پیش‌فرض.
     *
     * @param string $key نام متغیر
     * @param mixed $default مقدار پیش‌فرض در صورت نبود
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? null;
        }
        if ($val === null) return $default;

        // تطبیق مقادیر بولی
        $lower = strtolower($val);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if ($lower === 'null' || $lower === '') return null;

        return $val;
    }
}
