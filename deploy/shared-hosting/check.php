<?php
/**
 * بررسی پیش‌نیازهای هاست — پیش از استقرار
 * -----------------------------------------------------------------
 * این فایل را در DocumentRoot هاست آپلود کنید و با مرورگر باز کنید:
 *   https://your-domain.com/check.php
 *
 * پس از بررسی، حتماً این فایل را حذف کنید (امنیت).
 *
 * سازگار با: هاست اشتراکی (DirectAdmin / cPanel) — نت افراز، ایران‌استاندارد، و...
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$checks = [];
$allPass = true;

function check(string $name, bool $ok, string $detail = ''): void
{
    global $checks, $allPass;
    $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    if (!$ok) $allPass = false;
}

// ==================== نسخهٔ PHP ====================
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '8.0.0', '>=');
check('نسخهٔ PHP', $phpOk,
    $phpOk ? "PHP {$phpVersion} — سازگار (نیاز: 8.0+)" : "PHP {$phpVersion} — باید 8.0 یا بالاتر باشد");

// ==================== پسوندهای ضروری PHP ====================
$required = [
    'pdo'        => 'پایگاه داده (PDO)',
    'pdo_mysql'  => 'پایگاه دادهٔ MySQL (PDO MySQL)',
    'mbstring'   => 'پشتیبانی Unicode/فارسی',
    'openssl'    => 'رمزنگاری (CSRF، توکن‌ها)',
    'json'       => 'JSON (API ها)',
    'fileinfo'   => 'تشخیص نوع فایل (آپلود)',
    'session'    => 'نشست‌ها (ورود/احراز هویت)',
    'ctype'      => 'اعتبارسنجی کاراکترها',
    'filter'     => 'اعتبارسنجی (filter_var)',
    'curl'       => 'درخواست‌های HTTP (اختیاری ولی توصیه می‌شود)',
];
foreach ($required as $ext => $label) {
    $loaded = extension_loaded($ext);
    check("افزونهٔ {$ext}", $loaded,
        $loaded ? "بارگذاری شده ✓ — {$label}" : "بارگذاری نشده ✗ — {$label}");
}

// ==================== پسوندهای اختیاری ====================
$optional = ['gd', 'imagick', 'zip', 'xml', 'intl'];
$optionalInfo = [];
foreach ($optional as $ext) {
    if (extension_loaded($ext)) $optionalInfo[] = $ext;
}
check('پسوندهای اختیاری', true,
    'بارگذاری‌شده: ' . (empty($optionalInfo) ? 'هیچ' : implode(', ', $optionalInfo)));

// ==================== محدودیت آپلود ====================
$maxUpload = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
$memLimit = ini_get('memory_limit');
$maxExec = ini_get('max_execution_time');

// تبدیل به بایت
function toBytes(string $val): int
{
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $num = (int)$val;
    switch ($last) {
        case 'g': $num *= 1024;
        case 'm': $num *= 1024;
        case 'k': $num *= 1024;
    }
    return $num;
}

$needUpload = 2 * 1024 * 1024; // 2MB برای مدارک
$needPost = 8 * 1024 * 1024;   // 8MB برای فرم‌های چندبخشی
$needMem = 128 * 1024 * 1024;  // 128MB

$uploadOk = toBytes($maxUpload) >= $needUpload;
$postOk = toBytes($postMax) >= $needPost;
$memOk = toBytes($memLimit) >= $needMem;

check('حداکثر حجم آپلود', $uploadOk,
    "upload_max_filesize = {$maxUpload} (نیاز: ۲M+)");

check('حداکثر حجم POST', $postOk,
    "post_max_size = {$postMax} (نیاز: ۸M+)");

check('حداکثر حافظه', $memOk,
    "memory_limit = {$memLimit} (نیاز: ۱۲۸M+)");

check('حداکثر زمان اجرا', $maxExec >= 30 || $maxExec == 0,
    "max_execution_time = {$maxExec} ثانیه (نیاز: ۳۰+ یا ۰ = نامحدود)");

// ==================== mod_rewrite (برای .htaccess) ====================
$rewriteOk = in_array('mod_rewrite', apache_get_modules() ?: []);
check('mod_rewrite', $rewriteOk,
    $rewriteOk ? 'فعال — مسیریابی تمیز کار می‌کند' : 'غیرفعال — مسیرها کار نخواهند کرد');

// ==================== mod_headers (برای هدرهای امنیتی) ====================
$headersOk = in_array('mod_headers', apache_get_modules() ?: []);
check('mod_headers', $headersOk,
    $headersOk ? 'فعال — هدرهای امنیتی اعمال می‌شوند' : 'غیرفعال — هدرهای امنیتی اعمال نمی‌شوند (اختیاری)');

// ==================== mod_deflate (فشرده‌سازی) ====================
$deflateOk = in_array('mod_deflate', apache_get_modules() ?: []);
check('mod_deflate', $deflateOk,
    $deflateOk ? 'فعال — فشرده‌سازی gzip کار می‌کند' : 'غیرفعال (اختیاری)');

// ==================== mod_expires (کش مرورگر) ====================
$expiresOk = in_array('mod_expires', apache_get_modules() ?: []);
check('mod_expires', $expiresOk,
    $expiresOk ? 'فعال — کش مرورگر کار می‌کند' : 'غیرفعال (اختیاری)');

// ==================== HTTPS ====================
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
check('HTTPS/SSL', $isHttps,
    $isHttps ? 'فعال — ارتباط امن' : 'غیرفعال — باید گواهی SSL نصب کنید');

// ==================== قابلیت نوشتن پوشه‌ها ====================
// بررسی پوشهٔ uploads (در صورت وجود)
$uploadDirCandidates = [
    __DIR__ . '/uploads',
    dirname(__DIR__) . '/uploads',
    __DIR__ . '/../uploads',
];
$uploadDir = null;
foreach ($uploadDirCandidates as $dir) {
    if (is_dir($dir)) { $uploadDir = $dir; break; }
}
if ($uploadDir) {
    $writable = is_writable($uploadDir);
    check('پوشهٔ uploads', $writable,
        $writable ? "قابل نوشتن ✓ — {$uploadDir}" : "غیرقابل نوشتن ✗ — chmod 755 یا 777");
} else {
    check('پوشهٔ uploads', false, 'پوشهٔ uploads یافت نشد — باید آن را بسازید');
}

// بررسی session.save_path
$sessionPath = session_save_path() ?: sys_get_temp_dir();
$sessionWritable = is_writable($sessionPath);
check('session.save_path', $sessionWritable,
    "مسیر: {$sessionPath} — " . ($sessionWritable ? 'قابل نوشتن ✓' : 'غیرقابل نوشتن ✗'));

// ==================== اتصال به MySQL ====================
$mysqlHost = $_POST['db_host'] ?? 'localhost';
$mysqlUser = $_POST['db_user'] ?? '';
$mysqlPass = $_POST['db_pass'] ?? '';
$mysqlName = $_POST['db_name'] ?? '';

if ($mysqlUser || $mysqlName) {
    try {
        $dsn = "mysql:host={$mysqlHost};dbname={$mysqlName};charset=utf8mb4";
        $pdo = new PDO($dsn, $mysqlUser, $mysqlPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        check('اتصال MySQL', true, "اتصال موفق — نسخهٔ {$version}");

        // بررسی collation
        $collation = $pdo->query("SELECT @@character_set_database, @@collation_database")->fetch();
        check('utf8mb4', $collation && str_contains($collation['@@collation_database'] ?? '', 'utf8mb4'),
            "collation فعلی: " . ($collation['@@collation_database'] ?? 'نامشخص'));
    } catch (PDOException $e) {
        check('اتصال MySQL', false, $e->getMessage());
    }
} else {
    check('اتصال MySQL', true, 'برای تست، فرم پایین را پر کنید');
}

// ==================== نمایش نتیجهٔ نهایی ====================
$passCount = count(array_filter($checks, fn($c) => $c['ok']));
$failCount = count($checks) - $passCount;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>بررسی پیش‌نیازهای هاست — Mojavez</title>
<style>
* { box-sizing: border-box; }
body { font-family: Tahoma, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
.container { max-width: 900px; margin: 0 auto; }
.card { background: #fff; border-radius: 12px; padding: 24px; margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,.08); }
h1 { color: #00B4D8; margin-top: 0; }
h2 { color: #495057; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
.result { padding: 12px 16px; margin: 8px 0; border-radius: 8px; display: flex;
          justify-content: space-between; align-items: center; }
.result.ok { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
.result.fail { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
.result .icon { font-size: 20px; }
.summary { text-align: center; padding: 20px; font-size: 18px; font-weight: bold;
           border-radius: 12px; margin-bottom: 20px; }
.summary.ok { background: #d4edda; color: #155724; }
.summary.fail { background: #f8d7da; color: #721c24; }
form { display: grid; gap: 10px; max-width: 400px; margin-top: 20px; }
label { font-weight: bold; }
input { padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-family: inherit; }
button { padding: 12px 24px; background: #00B4D8; color: #fff; border: none;
         border-radius: 6px; cursor: pointer; font-size: 16px; }
button:hover { background: #0096B8; }
.warning { background: #fff3cd; color: #856404; padding: 12px; border-radius: 6px;
           margin-top: 20px; border-left: 4px solid #ffc107; }
code { background: #e9ecef; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
.detail { font-size: 13px; color: #6c757d; margin-top: 4px; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>🔍 بررسی پیش‌نیازهای هاست — Mojavez</h1>
        <p>این صفحه بررسی می‌کند که آیا هاست شما برای اجرای سامانهٔ مجوز آماده است یا خیر.</p>
        <div class="summary <?= $allPass ? 'ok' : 'fail' ?>">
            <?= $passCount ?> / <?= count($checks) ?> مورد گذرانده شد
            <?= $failCount > 0 ? " — {$failCount} مورد ناموفق" : " ✓ همه‌چیز آماده" ?>
        </div>

        <h2>مشخصات سرور</h2>
        <div class="detail">
            PHP <?= $phpVersion ?> |
            <?= php_uname('s') . ' ' . php_uname('r') ?> |
            <?= php_sapi_name() ?> |
            <?= $_SERVER['SERVER_SOFTWARE'] ?? 'نامشخص' ?>
        </div>

        <h2>بررسی‌ها</h2>
        <?php foreach ($checks as $c): ?>
            <div class="result <?= $c['ok'] ? 'ok' : 'fail' ?>">
                <span><strong><?= htmlspecialchars($c['name']) ?></strong>
                    <div class="detail"><?= htmlspecialchars($c['detail']) ?></div>
                </span>
                <span class="icon"><?= $c['ok'] ? '✓' : '✗' ?></span>
            </div>
        <?php endforeach; ?>

        <h2>تست اتصال MySQL</h2>
        <p>برای تست اتصال به دیتابیس، اطلاعات زیر را وارد کنید:</p>
        <form method="post">
            <label>میزبان (Host): <input type="text" name="db_host" value="localhost" /></label>
            <label>نام دیتابیس: <input type="text" name="db_name" placeholder="مثلاً: mojavez_db" /></label>
            <label>نام کاربری: <input type="text" name="db_user" /></label>
            <label>رمز عبور: <input type="password" name="db_pass" /></label>
            <button type="submit">تست اتصال</button>
        </form>
        <p class="detail">این اطلاعات ذخیره نمی‌شوند — فقط برای تست لحظه‌ای استفاده می‌شوند.</p>

        <div class="warning">
            <strong>⚠️ توجه:</strong> پس از بررسی، این فایل <code>check.php</code> را حذف کنید.
            قرار دادن آن در هاست، خطرات امنیتی دارد (نمایش اطلاعات سرور).
        </div>

        <h2>گام‌های بعدی</h2>
        <p>اگر همه‌چیز سبز است، به راهنمای <code>deploy/shared-hosting/README.md</code> مراجعه کنید.</p>
    </div>
</div>
</body>
</html>
