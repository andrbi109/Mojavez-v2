<?php
/**
 * کلاس مدیریت نشست‌ها
 * ----------------------------------------------
 * علاوه بر مدیریت داده‌های سشن، مسئولیت‌های امنیتی زیر را نیز بر عهده دارد:
 *  - تولید و اعتبارسنجی توکن CSRF (جلوگیری از جعل درخواست میان‌سایتی)
 *  - بازتولید شناسهٔ نشست هنگام ورود (جلوگیری از Session Fixation)
 *  - محدودسازی نرخ (Rate Limit) مبتنی بر سشن برای ورود و پیگیری
 */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'httponly' => true,          // عدم دسترسی جاوااسکریپت به کوکی (مقابله با XSS)
                'samesite' => 'Lax',         // مقابله با CSRF در درخواست‌های میان‌سایتی
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), // فقط روی HTTPS در سرور واقعی
            ]);
            session_name('MOJAVEZSESSID');
            session_start();
        }
    }

    /**
     * بازتولید شناسهٔ نشست — باید بلافاصله پس از ورود موفق صدا زده شود
     * تا حملات Session Fixation خنثی گردند.
     */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_unset();
        session_destroy();
    }

    /* ==================== محافظت CSRF ==================== */

    /**
     * دریافت (یا تولید) توکن CSRF جاری نشست
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * تولید فیلد مخفی CSRF برای قرارگیری داخل همهٔ فرم‌های POST
     */
    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::csrfToken() . '">';
    }

    /**
     * اعتبارسنجی توکن CSRF ارسال‌شده با فرم (مقایسهٔ زمان‌ثابت)
     */
    public static function checkCsrf(?string $token): bool
    {
        $valid = self::csrfToken();
        return is_string($token) && $token !== '' && hash_equals($valid, $token);
    }

    /* ==================== محدودسازی نرخ (Rate Limit) ==================== */

    /**
     * ثبت یک رخداد برای کلید مشخص (مثلاً تلاش ناموفق ورود)
     */
    public static function hit(string $key): void
    {
        $_SESSION['_rate'][$key]['count'] = ($_SESSION['_rate'][$key]['count'] ?? 0) + 1;
        $_SESSION['_rate'][$key]['first'] = $_SESSION['_rate'][$key]['first'] ?? time();
    }

    /**
     * آیا کلید مشخص از حد مجاز عبور کرده است؟
     * @param int $maxAttempts حداکثر تعداد مجاز در بازهٔ زمانی
     * @param int $windowSeconds طول بازهٔ زمانی به ثانیه
     */
    public static function isLimited(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $info = $_SESSION['_rate'][$key] ?? null;
        if (!$info) {
            return false;
        }
        // انقضای بازه → شمارنده بازنشانی می‌شود
        if (time() - $info['first'] > $windowSeconds) {
            self::resetRate($key);
            return false;
        }
        return $info['count'] >= $maxAttempts;
    }

    /**
     * ثانیه‌های باقی‌مانده تا پایان محدودیت
     */
    public static function retryAfter(string $key, int $windowSeconds): int
    {
        $info = $_SESSION['_rate'][$key] ?? null;
        if (!$info) {
            return 0;
        }
        return max(0, $windowSeconds - (time() - $info['first']));
    }

    /**
     * بازنشانی شمارندهٔ یک کلید (مثلاً پس از ورود موفق)
     */
    public static function resetRate(string $key): void
    {
        unset($_SESSION['_rate'][$key]);
    }

    /* ==================== داده‌های ویزارد ثبت‌نام ==================== */

    /**
     * دریافت تمام داده‌های یک مرحله از ویزارد
     */
    public static function getStep(string $step, mixed $default = []): mixed
    {
        return $_SESSION['registration'][$step] ?? $default;
    }

    /**
     * ذخیره داده‌های یک مرحله از ویزارد
     */
    public static function setStep(string $step, mixed $data): void
    {
        $_SESSION['registration'][$step] = $data;
    }

    /**
     * پاکسازی داده‌های ویزارد — انتخاب «نوع خدمت» حفظ می‌شود
     * تا کاربر برای ثبت درخواست بعدی مجبور به انتخاب مجدد نباشد (فاز ۹)
     */
    public static function clearRegistration(): void
    {
        $serviceId = $_SESSION['registration']['service_id'] ?? null;
        unset($_SESSION['registration']);
        if ($serviceId !== null) {
            $_SESSION['registration']['service_id'] = $serviceId;
        }
    }
}
