<?php
/**
 * نصب‌کنندهٔ وب برای هاست اشتراکی
 * -----------------------------------------------------------------
 * این فایل را در DocumentRoot هاست آپلود کنید و با مرورگر باز کنید:
 *   https://your-domain.com/install.php
 *
 * این نصب‌کننده:
 *   ۱) فایل .env می‌سازد (با اطلاعات دیتابیس)
 *   ۲) پایگاه داده را با اجرای install.sql راه‌اندازی می‌کند
 *   ۳) گزارش نهایی نمایش می‌دهد
 *
 * پس از نصب موفق، این فایل را حذف کنید.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(120); // ۲ دقیقه برای اجرای schema

$step = (int)($_POST['step'] ?? 1);
$message = '';
$errors = [];
$success = false;

// ==================== گام ۱: فرم اطلاعات دیتابیس ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    $baseUrl = trim($_POST['base_url'] ?? '/');
    $cronSecret = bin2hex(random_bytes(32)); // تولید رمز تصادفی

    if (!$dbName || !$dbUser) {
        $errors[] = 'نام دیتابیس و نام کاربری الزامی است.';
    }
    if (!empty($errors)) {
        $step = 1;
    } else {
        // تست اتصال
        try {
            $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            // ذخیره در سشن برای گام ۲
            session_start();
            $_SESSION['install_db'] = [
                'host' => $dbHost, 'name' => $dbName,
                'user' => $dbUser, 'pass' => $dbPass,
                'base_url' => $baseUrl, 'cron_secret' => $cronSecret,
            ];

            $step = 2;
            $message = "اتصال به دیتابیس موفق بود. اکنون schema را نصب کنید.";
        } catch (PDOException $e) {
            $errors[] = 'خطا در اتصال: ' . $e->getMessage();
            $step = 1;
        }
    }
}

// ==================== گام ۲: اجرای install.sql ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    session_start();
    $info = $_SESSION['install_db'] ?? null;
    if (!$info) {
        $errors[] = 'اطلاعات دیتابیس یافت نشد. از ابتدا شروع کنید.';
        $step = 1;
    } else {
        try {
            $dsn = "mysql:host={$info['host']};dbname={$info['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $info['user'], $info['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // مسیر install.sql (فایل واحد نصب — بدون نیاز به مهاجرت‌ها)
            $schemaPath = dirname(__DIR__, 2) . '/database/install.sql';
            if (!file_exists($schemaPath)) {
                // fallback به schema.sql اگر install.sql موجود نبود
                $schemaPath = dirname(__DIR__, 2) . '/database/schema.sql';
            }
            if (!file_exists($schemaPath)) {
                throw new \Exception("فایل install.sql یافت نشد");
            }

            $sql = file_get_contents($schemaPath);

            // تقسیم‌کنندهٔ هوشمند SQL
            $statements = splitSqlStatements($sql);
            $okCount = 0;
            $skipCount = 0;
            $failCount = 0;
            $failMessages = [];

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '') continue;

                // حذف کامنت‌ها از ابتدا
                $stmt = preg_replace('/^(\s*--[^\n]*\n\s*)+/', '', $stmt);
                $stmt = trim($stmt);
                if ($stmt === '') { $skipCount++; continue; }

                // skip دستورات مشکل‌ساز
                $upper = strtoupper($stmt);
                if (str_starts_with($upper, 'USE ')) { $skipCount++; continue; }
                if (str_starts_with($upper, 'CREATE DATABASE')) { $skipCount++; continue; }
                if (str_starts_with($upper, 'SET @')) { $skipCount++; continue; }
                if (str_starts_with($upper, 'PREPARE ')) { $skipCount++; continue; }
                if (str_starts_with($upper, 'EXECUTE ')) { $skipCount++; continue; }
                if (str_starts_with($upper, 'DEALLOCATE ')) { $skipCount++; continue; }
                if (str_starts_with($upper, 'SELECT 1 FROM DUAL')) { $skipCount++; continue; }
                if (str_starts_with($upper, 'SELECT @')) { $skipCount++; continue; }

                try {
                    $pdo->exec($stmt);
                    $okCount++;
                } catch (PDOException $e) {
                    $msg = $e->getMessage();
                    if (str_contains($msg, 'already exists') ||
                        str_contains($msg, 'Duplicate') ||
                        str_contains($msg, "Can't DROP")) {
                        $skipCount++;
                    } else {
                        $failCount++;
                        if (count($failMessages) < 5) {
                            $failMessages[] = substr(preg_replace('/\s+/', ' ', $stmt), 0, 80) . '... → ' . $msg;
                        }
                    }
                }
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            // ساخت فایل .env
            $envPath = dirname(__DIR__, 2) . '/.env';
            $envContent = "# تولیدشده توسط install.php در " . date('Y-m-d H:i:s') . "\n";
            $envContent .= "APP_DEBUG=false\n";
            $envContent .= "DB_HOST={$info['host']}\n";
            $envContent .= "DB_NAME={$info['name']}\n";
            $envContent .= "DB_USER={$info['user']}\n";
            $envContent .= "DB_PASS={$info['pass']}\n";
            $envContent .= "BASE_URL={$info['base_url']}\n";
            $envContent .= "USE_CDN=false\n";
            $envContent .= "CRON_SECRET={$info['cron_secret']}\n";
            $envContent .= "SESSION_LIFETIME=86400\n";
            $envContent .= "APP_ENV=production\n";
            $envContent .= "ERROR_LOG_PATH=\n";
            $envContent .= "ADMIN_IP_ALLOWLIST=\n";
            $envContent .= "IP_BLOCKLIST=\n";

            if (file_put_contents($envPath, $envContent) === false) {
                $errors[] = "نوشتن فایل .env ناموفق بود. دسترسی پوشهٔ ریشه را بررسی کنید.";
            } else {
                $success = true;
                $message = "نصب موفق!\n\n";
                $message .= "دستورات اجرا شده: {$okCount} موفق، {$skipCount} skip، {$failCount} خطا\n";
                $message .= "فایل .env ساخته شد.\n";
                $message .= "رمز CRON_SECRET: " . $info['cron_secret'] . "\n\n";
                if (!empty($failMessages)) {
                    $message .= "خطاهای جزئی (غیربحرانی):\n";
                    foreach ($failMessages as $fm) {
                        $message .= "  • " . $fm . "\n";
                    }
                }
                $message .= "\n⚠️ اکنون فایل install.php و check.php را حذف کنید.";

                // پاک کردن سشن
                unset($_SESSION['install_db']);
            }
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
            $step = 1;
        }
    }
}

/**
 * تقسیم‌کنندهٔ هوشمند SQL
 */
function splitSqlStatements(string $sql): array
{
    $statements = [];
    $current = '';
    $len = strlen($sql);
    $i = 0;
    $state = 'normal';

    while ($i < $len) {
        $c = $sql[$i];
        $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

        switch ($state) {
            case 'normal':
                if ($c === "'") { $state = 'single_str'; $current .= $c; }
                elseif ($c === '"') { $state = 'double_str'; $current .= $c; }
                elseif ($c === '`') { $state = 'backtick'; $current .= $c; }
                elseif ($c === '-' && $next === '-') { $state = 'line_comment'; $current .= $c; }
                elseif ($c === '#') { $state = 'line_comment'; $current .= $c; }
                elseif ($c === '/' && $next === '*') { $state = 'block_comment'; $current .= $c; $i++; $current .= '/'; }
                elseif ($c === ';') {
                    $stmt = trim($current);
                    if ($stmt !== '') $statements[] = $stmt;
                    $current = '';
                } else {
                    $current .= $c;
                }
                break;
            case 'single_str':
                $current .= $c;
                if ($c === '\\' && $i + 1 < $len) { $current .= $sql[$i + 1]; $i++; }
                elseif ($c === "'") $state = 'normal';
                break;
            case 'double_str':
                $current .= $c;
                if ($c === '\\' && $i + 1 < $len) { $current .= $sql[$i + 1]; $i++; }
                elseif ($c === '"') $state = 'normal';
                break;
            case 'backtick':
                $current .= $c;
                if ($c === '`') $state = 'normal';
                break;
            case 'line_comment':
                $current .= $c;
                if ($c === "\n") $state = 'normal';
                break;
            case 'block_comment':
                $current .= $c;
                if ($c === '*' && $next === '/') { $current .= '/'; $i++; $state = 'normal'; }
                break;
        }
        $i++;
    }
    $stmt = trim($current);
    if ($stmt !== '') $statements[] = $stmt;
    return $statements;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>نصب سامانهٔ Mojavez</title>
<style>
* { box-sizing: border-box; }
body { font-family: Tahoma, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
.container { max-width: 700px; margin: 0 auto; }
.card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
h1 { color: #00B4D8; margin-top: 0; }
h2 { color: #495057; }
form { display: grid; gap: 15px; }
label { font-weight: bold; display: block; }
input { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px;
        font-family: inherit; font-size: 14px; }
input:focus { outline: none; border-color: #00B4D8; }
button { padding: 14px 28px; background: #00B4D8; color: #fff; border: none;
         border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; }
button:hover { background: #0096B8; }
.errors { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
.errors ul { margin: 0; padding-right: 20px; }
.message { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px;
           white-space: pre-wrap; font-family: monospace; }
.warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 6px; margin-top: 20px;
           border-left: 4px solid #ffc107; }
.help { font-size: 13px; color: #6c757d; margin-top: 4px; }
.step { display: inline-block; background: #00B4D8; color: #fff; padding: 4px 12px;
        border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 20px; }
.success-icon { font-size: 60px; text-align: center; color: #28a745; margin: 20px 0; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>🚀 نصب سامانهٔ Mojavez</h1>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <strong>خطاها:</strong>
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-icon">✓</div>
            <h2>نصب با موفقیت انجام شد!</h2>
            <div class="message"><?= htmlspecialchars($message) ?></div>
            <p>اکنون می‌توانید به آدرس <a href="<?= htmlspecialchars($info['base_url'] ?? '/') ?>">صفحهٔ اصلی</a> بروید.</p>
            <div class="warning">
                <strong>⚠️ مهم:</strong> فایل‌های <code>install.php</code> و <code>check.php</code> را حذف کنید.
            </div>
        <?php elseif ($step === 1): ?>
            <span class="step">گام ۱ از ۲</span>
            <h2>اطلاعات دیتابیس</h2>
            <p>این اطلاعات را از پنل هاست (DirectAdmin → MySQL Management) دریافت کنید.</p>
            <form method="post">
                <input type="hidden" name="step" value="1">
                <div>
                    <label>میزبان (Host)</label>
                    <input type="text" name="db_host" value="localhost" required>
                    <div class="help">معمولاً <code>localhost</code> است.</div>
                </div>
                <div>
                    <label>نام دیتابیس</label>
                    <input type="text" name="db_name" placeholder="مثلاً: mojavez_db" required
                           value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>">
                </div>
                <div>
                    <label>نام کاربری دیتابیس</label>
                    <input type="text" name="db_user" required
                           value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>">
                </div>
                <div>
                    <label>رمز عبور دیتابیس</label>
                    <input type="password" name="db_pass"
                           value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                </div>
                <div>
                    <label>Base URL (مسیر پایه)</label>
                    <input type="text" name="base_url" value="/" required>
                    <div class="help">اگر در ریشهٔ دامنه است، <code>/</code> بگذارید.<br>
                    اگر در زیرپوشه است، <code>/subfolder/</code> بگذارید.</div>
                </div>
                <button type="submit">تست اتصال →</button>
            </form>
        <?php elseif ($step === 2): ?>
            <span class="step">گام ۲ از ۲</span>
            <h2>نصب schema دیتابیس</h2>
            <p>اتصال موفق بود! اکنون فایل install.sql اجرا می‌شود تا همهٔ جداول ساخته شوند.</p>
            <div class="warning">
                <strong>توجه:</strong> اگر قبلاً دیتابیس را نصب کرده‌اید، این کار داده‌های جدیدی اضافه می‌کند
                ولی داده‌های موجود را حذف نمی‌کند (دستورات با ON DUPLICATE KEY اجرا می‌شوند).
            </div>
            <form method="post">
                <input type="hidden" name="step" value="2">
                <button type="submit">نصب schema →</button>
            </form>
        <?php endif; ?>

        <div class="warning">
            <strong>⚠️ امنیت:</strong> پس از نصب، حتماً این فایل و <code>check.php</code> را حذف کنید.
        </div>
    </div>
</div>
</body>
</html>
