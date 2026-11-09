<?php
/**
 * کنترلر پایه - شامل متدهای کمکی برای رندر، هدایت و محافظت‌های امنیتی
 */
class Controller
{
    /**
     * رندر یک نما با قالب اصلی
     * @param string $view مسیر نسبی فایل نما از پوشه Views
     * @param array $data داده‌های ارسالی به نما
     * @param string $layout نام فایل قالب (بدون پسوند .php)
     */
    public function render(string $view, array $data = [], string $layout = 'layout'): void
    {
        // استخراج داده‌ها به متغیرهای مستقل
        extract($data);

        // دریافت خروجی نما
        ob_start();
        $viewPath = VIEW_PATH . '/' . ltrim($view, '/') . '.php';
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View file not found: {$viewPath}");
        }
        require $viewPath;
        $content = ob_get_clean();

        // رندر قالب اصلی
        $layoutPath = VIEW_PATH . '/' . $layout . '.php';
        if (!file_exists($layoutPath)) {
            throw new \RuntimeException("Layout file not found: {$layoutPath}");
        }
        // متغیرهای قابل استفاده در قالب
        $title = $data['title'] ?? 'سامانه مجوز صنایع دستی';
        require $layoutPath;
    }

    /**
     * هدایت به یک مسیر
     */
    protected function redirect(string $url): void
    {
        // جلوگیری از تولید آدرس با دو اسلش (//) وقتی BASE_URL برابر '/' است
        $base = rtrim(BASE_URL, '/');
        $location = $base . '/' . ltrim($url, '/');
        // لاگ تشخیصی — برای رفع مشکل ذخیره‌سازی
        if (APP_DEBUG) {
            error_log('[redirect] to: ' . $location . ' from: ' . ($_SERVER['REQUEST_URI'] ?? '?') . ' method: ' . ($_SERVER['REQUEST_METHOD'] ?? '?'));
        }
        header('Location: ' . $location);
        exit;
    }

    /**
     * برگرداندن پاسخ JSON
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * بررسی درخواست POST
     */
    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /**
     * دریافت مقدار یک فیلد از درخواست
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * دریافت تمام ورودی‌ها
     */
    protected function allInputs(): array
    {
        return $_POST;
    }

    /* ==================== محافظت‌های امنیتی ==================== */

    /**
     * اعتبارسنجی اجباری توکن CSRF برای همهٔ درخواست‌های POST
     * در صورت نامعتبر بودن توکن، پاسخ ۴۱۹ برگردانده و اجرا متوقف می‌شود.
     */
    protected function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? null;
        // پشتیبانی از Authorization header برای درخواست‌های AJAX
        if (!$token && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        if (!Session::checkCsrf($token)) {
            // اگر درخواست AJAX است (Accept: application/json یا X-Requested-With) → پاسخ JSON
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            $acceptJson = !empty($_SERVER['HTTP_ACCEPT'])
                && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

            if ($isAjax || $acceptJson) {
                http_response_code(419);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'نشست منقضی شده — لطفاً صفحه را تازه‌سازی کنید.',
                    'code' => 'csrf_invalid',
                ]);
                exit;
            }

            http_response_code(419);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">'
                . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>نشست منقضی شده</title>'
                . '<style>body{font-family:Tahoma,sans-serif;display:flex;align-items:center;justify-content:center;'
                . 'min-height:100vh;margin:0;background:#F0F2F5}.box{background:#fff;padding:2rem 3rem;border-radius:12px;'
                . 'box-shadow:0 2px 12px rgba(0,0,0,.08);text-align:center;max-width:420px}'
                . 'a{display:inline-block;margin-top:1rem;background:#00B4D8;color:#fff;padding:.5rem 1.5rem;'
                . 'border-radius:8px;text-decoration:none}</style></head><body><div class="box">'
                . '<h2>⚠️ توکن امنیتی نامعتبر یا منقضی</h2>'
                . '<p>این فرم یا منقضی شده یا از جای دیگری باز شده است. لطفاً صفحه را تازه‌سازی کرده و دوباره تلاش کنید.</p>'
                . '<a href="javascript:history.back()">بازگشت و تلاش مجدد</a>'
                . '</div></body></html>';
            exit;
        }
    }

    /**
     * بررسی ورود مدیر — در صورت عدم ورود به صفحهٔ لاگین هدایت می‌کند
     *
     * نکتهٔ امنیتی: اگر ADMIN_IP_ALLOWLIST در .env تنظیم شده باشد، فقط آی‌پی‌های
     * فهرست‌شده به پنل مدیریت دسترسی دارند — مگر اینکه صفحهٔ لاگین خودش باشد.
     */
    protected function requireLogin(): void
    {
        // بررسی allowlist آی‌پی برای پنل مدیریت
        if (defined('ADMIN_IP_ALLOWLIST') && ADMIN_IP_ALLOWLIST !== '') {
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $forwardedIp = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
                if (filter_var($forwardedIp, FILTER_VALIDATE_IP)) {
                    $clientIp = $forwardedIp;
                }
            }
            $allowed = array_filter(array_map('trim', explode(',', ADMIN_IP_ALLOWLIST)));
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isLoginPage = str_contains($uri, '/admin/login');
            if ($clientIp && !in_array($clientIp, $allowed, true) && !$isLoginPage) {
                http_response_code(403);
                header('Content-Type: text/html; charset=utf-8');
                header('X-Robots-Tag: noindex, nofollow, noarchive');
                echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">'
                    . '<meta name="robots" content="noindex,nofollow"><title>دسترسی محدود</title>'
                    . '<style>body{font-family:Tahoma,sans-serif;text-align:center;padding:3rem;'
                    . 'background:#f8f9fa;color:#495057}</style></head><body>'
                    . '<h2>دسترسی به پنل مدیریت از این شبکه محدود شده است.</h2>'
                    . '<p>در صورت نیاز، با مدیر سامانه تماس بگیرید.</p>'
                    . '</body></html>';
                exit;
            }
        }
        if (!Session::get('admin_logged_in')) {
            $this->redirect('/admin/login');
        }
    }

    /**
     * بررسی ورود کاربر (مدل فروشگاهی) — در صورت عدم ورود به صفحهٔ لاگین هدایت می‌کند
     */
    protected function requireUserLogin(): void
    {
        if (!Session::get('user_logged_in')) {
            Session::set('redirect_after_login', $_SERVER['REQUEST_URI'] ?? '/panel');
            Session::set('login_error', 'برای دسترسی، ابتدا وارد شوید.');
            $this->redirect('/login');
        }
    }

    /**
     * بررسی ورود مدیر یا کاربر — هرکدام وارد شده باشد، مجاز است.
     * برای endpointهایی که هم مدیر و هم کاربر می‌توانند ببینند (مثل آواتار).
     */
    protected function requireAnyLogin(): void
    {
        if (!Session::get('admin_logged_in') && !Session::get('user_logged_in')) {
            $this->redirect('/login');
        }
    }

    /**
     * بررسی نقش مدیر — فقط نقش‌های فهرست‌شده اجازهٔ عبور دارند
     * @param array $roles نقش‌های مجاز، مثال: ['admin']
     * @param string|null $redirectTo مسیر هدایت در صورت عدم دسترسی (پیش‌فرض: داشبورد)
     */
    protected function requireRole(array $roles, ?string $redirectTo = '/admin/dashboard'): void
    {
        $this->requireLogin();
        $role = Session::get('admin_role', 'admin');
        if (!in_array($role, $roles, true)) {
            if ($redirectTo) {
                $this->redirect($redirectTo);
            }
            http_response_code(403);
            echo 'دسترسی غیرمجاز';
            exit;
        }
    }

    /**
     * آیا کاربر جاری نقش مدیر کل (admin) است؟
     */
    protected function isSuperAdmin(): bool
    {
        return Session::get('admin_logged_in') && Session::get('admin_role') === 'admin';
    }

    /**
     * بررسی مجاز بودن انتقال وضعیت بر اساس نقش کاربر و قواعد workflow.php.
     *
     * این متد مرکزی برای جلوگیری از تکرار منطق در چند کنترلر (مثل AdminController::changeStatus
     * و SmsController::reviewEntry). admin همیشه مجاز است؛ سایر نقش‌ها بر اساس
     * transitions[$role]['from']/['to'] بررسی می‌شوند.
     *
     * @param string $currentStatus وضعیت فعلی پرونده
     * @param string $newStatus     وضعیت مقصد
     * @param string $role          نقش کاربر ('admin', 'expert', 'document_uploader', ...)
     * @return bool                 true = انتقال مجاز
     */
    protected function isStatusTransitionAllowed(string $currentStatus, string $newStatus, string $role): bool
    {
        // admin مجاز به همهٔ انتقال‌ها
        if ($role === 'admin') return true;

        $workflow = require CONFIG_PATH . '/workflow.php';
        $transitions = $workflow['transitions'] ?? [];
        $rule = $transitions[$role] ?? null;
        if (!$rule) return false;

        $fromOk = in_array($currentStatus, $rule['from'] ?? [], true);
        $toOk   = in_array($newStatus, $rule['to'] ?? [], true);
        return $fromOk && $toOk;
    }

    /**
     * اعتبارسنجی کامل انتقال وضعیت شامل: workflow + مسیر یکطرفه + terminal.
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @param string $role
     * @return array{allowed: bool, reason: string}
     */
    protected function validateStatusTransition(string $currentStatus, string $newStatus, string $role): array
    {
        $statusCfg = (require CONFIG_PATH . '/statuses.php')['statuses'];
        $terminal  = (require CONFIG_PATH . '/statuses.php')['terminal'];

        // پرونده‌های بسته‌شده (terminal) قابل تغییر نیستند
        // اما لغو/رد همیشه مجازند حتی از terminal
        if (in_array($currentStatus, $terminal, true) && !in_array($newStatus, ['لغو', 'رد درخواست'], true)) {
            // استثنا: برای مشاغل خانگی مستقل، «صدور مجوز» terminal نیست
            // چون مراحل بعدی (دریافت تصویر، تأیید اولیه، تأیید نهایی) وجود دارد.
            // اما چون validateStatusTransition نوع خدمت را نمی‌داند، این بررسی
            // در AdminController::changeStatus انجام شده و جلوی terminal را می‌گیرد.
            // اینجا فقط اگر currentStatus terminal باشد و newStatus لغو/رد نباشد، رد می‌کنیم.
            return ['allowed' => false, 'reason' => 'پرونده در وضعیت پایانی است و قابل تغییر نیست.'];
        }

        // بررسی قواعد workflow.php بر اساس نقش
        if (!$this->isStatusTransitionAllowed($currentStatus, $newStatus, $role)) {
            return ['allowed' => false, 'reason' => "نقش «{$role}» مجاز به این انتقال نیست ({$currentStatus} → {$newStatus})."];
        }

        // مسیر یکطرفه (به‌جز لغو/رد که همیشه مجازند)
        $currentOrder = $statusCfg[$currentStatus]['order'] ?? 0;
        $newOrder     = $statusCfg[$newStatus]['order'] ?? 0;
        if (!in_array($newStatus, ['لغو', 'رد درخواست'], true) && $newOrder <= $currentOrder) {
            return ['allowed' => false, 'reason' => 'مسیر یکطرفه — بازگشت به عقب مجاز نیست.'];
        }

        return ['allowed' => true, 'reason' => ''];
    }
}
