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
        header('Location: ' . $base . '/' . ltrim($url, '/'));
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
        if (!Session::checkCsrf($token)) {
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
     */
    protected function requireLogin(): void
    {
        if (!Session::get('admin_logged_in')) {
            $this->redirect('/admin/login');
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
}
