<?php
/**
 * بارگذار خودکار کلاس‌ها بدون نیاز به کامپوزر
 */
class AutoLoader
{
    public static function register(): void
    {
        spl_autoload_register([self::class, 'loadClass']);
    }

    private static function loadClass(string $class): void
    {
        $paths = [
            CORE_PATH . '/' . $class . '.php',
            APP_PATH . '/Controllers/' . $class . '.php',
            APP_PATH . '/Models/' . $class . '.php',
            APP_PATH . '/Services/' . $class . '.php',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                return;
            }
        }
    }
}