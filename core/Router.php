<?php
/**
 * کلاس مسیریابی ساده برای URL های تمیز
 */
class Router
{
    private array $routes = [];
    private string $baseUrl;

    public function __construct(string $baseUrl = '/')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * ثبت یک مسیر GET
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * ثبت یک مسیر POST
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $path = $this->baseUrl . '/' . trim($path, '/');
        $path = $path === '/' ? $path : rtrim($path, '/');
        // تبدیل پارامترهای داینامیک مثل {id}
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    /**
     * اجرای مسیریابی
     */
    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // فیلتر کردن کلیدهای عددی
                $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

                $handler = $route['handler'];

                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    call_user_func_array([$controller, $action], $params);
                } elseif (is_callable($handler)) {
                    call_user_func_array($handler, $params);
                }
                return;
            }
        }

        // صفحه ۴۰۴ — استایل بر اساس پیکربندی USE_CDN (محلی یا CDN)
        http_response_code(404);
        $base = rtrim(BASE_URL, '/') . '/';
        $css = defined('USE_CDN') && USE_CDN
            ? 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css'
            : $base . 'assets/bootstrap.rtl.min.css';
        echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1"><title>۴۰۴ — یافت نشد</title>';
        echo '<link rel="stylesheet" href="' . $css . '">';
        echo '<link rel="stylesheet" href="' . $base . 'assets/font.css"></head>';
        echo '<body class="d-flex align-items-center justify-content-center vh-100 bg-light" style="font-family:Vazirmatn,Tahoma,sans-serif">';
        echo '<div class="text-center"><h1 class="display-1">۴۰۴</h1><p class="lead">صفحه مورد نظر یافت نشد.</p>';
        echo '<a href="' . $base . '" class="btn btn-primary">بازگشت به خانه</a></div></body></html>';
    }
}