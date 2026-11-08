<?php
/**
 * صفحهٔ تشخیص تنظیمات PHP
 * ------------------------
 * این صفحه به شما کمک می‌کند مشکلات آپلود فایل را تشخیص دهید.
 * بعد از بررسی، این فایل را حذف کنید.
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تشخیص تنظیمات PHP — Mojavez</title>
    <style>
        body { font-family: Tahoma, sans-serif; max-width: 900px; margin: 2rem auto; padding: 1rem; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background: #f5f5f5; }
        .ok { color: #28a745; font-weight: bold; }
        .warn { color: #ffc107; font-weight: bold; }
        .err { color: #dc3545; font-weight: bold; }
        .note { background: #fff3cd; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
    </style>
</head>
<body>
    <h1>🔧 تشخیص تنظیمات PHP</h1>
    
    <div class="note">
        <strong>نکته:</strong> بعد از بررسی، این فایل (<code>phpinfo.php</code>) را حذف کنید.
    </div>

    <h2>📋 تنظیمات آپلود فایل</h2>
    <table>
        <tr><th>تنظیم</th><th>مقدار فعلی</th><th>مقدار توصیه‌شده</th><th>وضعیت</th></tr>
        <?php
        $settings = [
            'upload_max_filesize' => ['current' => ini_get('upload_max_filesize'), 'recommended' => '10M', 'min' => '2M'],
            'post_max_size' => ['current' => ini_get('post_max_size'), 'recommended' => '20M', 'min' => '8M'],
            'memory_limit' => ['current' => ini_get('memory_limit'), 'recommended' => '256M', 'min' => '128M'],
            'max_execution_time' => ['current' => ini_get('max_execution_time'), 'recommended' => '60', 'min' => '30'],
            'max_input_time' => ['current' => ini_get('max_input_time'), 'recommended' => '60', 'min' => '30'],
        ];
        foreach ($settings as $name => $info) {
            $status = '<span class="ok">✅ مناسب</span>';
            $class = 'ok';
            // تبدیل به بایت برای مقایسه
            $val = $info['current'];
            $valBytes = 0;
            if (preg_match('/(\d+)([KMG]?)/i', $val, $m)) {
                $num = (int)$m[1];
                $unit = strtoupper($m[2] ?? '');
                $valBytes = $num * ($unit === 'K' ? 1024 : ($unit === 'M' ? 1024*1024 : ($unit === 'G' ? 1024*1024*1024 : 1)));
            }
            if ($name === 'upload_max_filesize' || $name === 'post_max_size') {
                if ($valBytes < 2 * 1024 * 1024) {
                    $status = '<span class="err">❌ خیلی کم</span>';
                } elseif ($valBytes < 8 * 1024 * 1024) {
                    $status = '<span class="warn">⚠️ ممکن است کم باشد</span>';
                }
            }
            echo "<tr><td>$name</td><td>{$info['current']}</td><td>{$info['recommended']}</td><td>$status</td></tr>";
        }
        ?>
    </table>

    <h2>📁 مسیر آپلود موقت</h2>
    <table>
        <tr><th>تنظیم</th><th>مقدار</th><th>قابل نوشتن؟</th></tr>
        <?php
        $tmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $writable = is_writable($tmpDir) ? '<span class="ok">✅ بله</span>' : '<span class="err">❌ خیر</span>';
        echo "<tr><td>upload_tmp_dir</td><td>$tmpDir</td><td>$writable</td></tr>";
        ?>
    </table>

    <h2>📂 مسیر ذخیرهٔ فایل‌ها</h2>
    <table>
        <tr><th>مسیر</th><th>موجود؟</th><th>قابل نوشتن؟</th></tr>
        <?php
        $uploadDir = dirname(__DIR__) . '/uploads';
        $exists = is_dir($uploadDir) ? '<span class="ok">✅ بله</span>' : '<span class="err">❌ خیر</span>';
        $writable = is_writable($uploadDir) ? '<span class="ok">✅ بله</span>' : '<span class="err">❌ خیر</span>';
        echo "<tr><td>$uploadDir</td><td>$exists</td><td>$writable</td></tr>";
        
        // مسیر ماه جاری
        $monthDir = $uploadDir . '/' . date('Y/m');
        $monthExists = is_dir($monthDir) ? '<span class="ok">✅ بله</span>' : '<span class="warn">⚠️ ساخته نشده (خودکار ساخته می‌شود)</span>';
        $monthWritable = is_writable(dirname($monthDir)) ? '<span class="ok">✅ والد قابل نوشتن</span>' : '<span class="err">❌ والد قابل نوشتن نیست</span>';
        echo "<tr><td>$monthDir</td><td>$monthExists</td><td>$monthWritable</td></tr>";
        ?>
    </table>

    <h2>🌐 اطلاعات درخواست فعلی</h2>
    <table>
        <tr><th>کلید</th><th>مقدار</th></tr>
        <tr><td>HTTP_HOST</td><td><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '') ?></td></tr>
        <tr><td>SERVER_NAME</td><td><?= htmlspecialchars($_SERVER['SERVER_NAME'] ?? '') ?></td></tr>
        <tr><td>REMOTE_ADDR</td><td><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '') ?></td></tr>
        <tr><td>DOCUMENT_ROOT</td><td><?= htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? '') ?></td></tr>
        <tr><td>PHP Version</td><td><?= PHP_VERSION ?></td></tr>
    </table>

    <h2>📝 راهنمای اصلاح php.ini</h2>
    <div class="note">
        <p><strong>فایل php.ini در XAMPP معمولاً در:</strong></p>
        <ul>
            <li>Windows: <code>C:\xampp\php\php.ini</code></li>
            <li>Linux: <code>/etc/php/[version]/apache2/php.ini</code></li>
        </ul>
        <p><strong>این مقادیر را تنظیم کنید:</strong></p>
        <pre>
upload_max_filesize = 10M
post_max_size = 20M
memory_limit = 256M
max_execution_time = 60
max_input_time = 60
        </pre>
        <p><strong>بعد از تغییر، Apache را restart کنید.</strong></p>
    </div>

    <h2>🔍 تست آپلود</h2>
    <div class="note">
        <form method="post" enctype="multipart/form-data" action="">
            <p>یک فایل کوچک (کمتر از 2MB) انتخاب کنید:</p>
            <input type="file" name="test_file" accept=".jpg,.jpeg,.png,.pdf" required>
            <button type="submit" style="margin-top: 0.5rem; padding: 0.5rem 1rem;">تست آپلود</button>
        </form>
        <?php
        if (!empty($_FILES['test_file'])) {
            echo '<h3>نتیجهٔ تست:</h3>';
            echo '<table><tr><th>کلید</th><th>مقدار</th></tr>';
            foreach ($_FILES['test_file'] as $k => $v) {
                echo '<tr><td>' . htmlspecialchars($k) . '</td><td>' . htmlspecialchars((string)$v) . '</td></tr>';
            }
            echo '</table>';
            
            if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
                echo '<p class="ok">✅ آپلود موفق بود! فایل در: ' . htmlspecialchars($_FILES['test_file']['tmp_name']) . '</p>';
            } else {
                $errors = [
                    UPLOAD_ERR_INI_SIZE => 'حجم فایل بیش از upload_max_filesize است',
                    UPLOAD_ERR_FORM_SIZE => 'حجم فایل بیش از حد مجاز فرم است',
                    UPLOAD_ERR_PARTIAL => 'فایل ناقص آپلود شد',
                    UPLOAD_ERR_NO_FILE => 'هیچ فایلی ارسال نشد',
                    UPLOAD_ERR_NO_TMP_DIR => 'پوشهٔ موقت موجود نیست',
                    UPLOAD_ERR_CANT_WRITE => 'سرور نمی‌تواند فایل را بنویسد',
                    UPLOAD_ERR_EXTENSION => 'افزونهٔ PHP این آپلود را مسدود کرد',
                ];
                $errCode = $_FILES['test_file']['error'];
                echo '<p class="err">❌ خطای آپلود: ' . ($errors[$errCode] ?? 'خطای ناشناخته (' . $errCode . ')') . '</p>';
            }
        }
        ?>
    </div>

</body>
</html>
