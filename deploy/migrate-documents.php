#!/usr/bin/env php
<?php
/**
 * اسکریپت مهاجرت مدارک قدیمی به ساختار جدید
 * =================================================================
 * این اسکریپت فایل‌های قدیمی (uploads/YYYY/MM/) را به ساختار جدید
 * (uploads/{tracking_code}/{doc_type}_{random6}.ext) منتقل می‌کند.
 *
 * نحوه اجرا:
 *   cd /path/to/Mojavez-v2
 *   php deploy/migrate-documents.php
 *
 * یا روی هاست اشتراکی (اگر SSH دارید):
 *   php /home/user/mojavez/deploy/migrate-documents.php
 *
 * یا از طریق وب (پس از آپلود):
 *   https://your-domain.com/migrate-documents.php
 *   (این فایل را در public_html کپی کنید، اجرا کنید، سپس حذف کنید)
 *
 * نکات:
 *   - قبل از اجرا، از پوشهٔ uploads/ بک‌آپ بگیرید
 *   - این اسکریپت idempotent است — اجرای چندباره خطا نمی‌دهد
 *   - فقط فایل‌های قدیمی (با مسیر /YYYY/MM/) را پردازش می‌کند
 *   - فایل‌های جدید skip می‌شوند
 *   - اگر فایل فیزیکی موجود نباشد، رکورد دیتابیس حذف می‌شود
 *   - پس از اجرای موفق، پوشه‌های قدیمی خالی باقی می‌مانند
 *
 * پس از اجرای موفق:
 *   - پوشه‌های قدیمی YYYY/MM/ را می‌توانید دستی حذف کنید
 *   - جدول موقت _migration_report را می‌توانید DROP کنید
 * =================================================================
 */

// ==================== تنظیمات ====================
// اگر از خط فرمان اجرا می‌شود:
if (php_sapi_name() === 'cli') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    // اگر از وب اجرا می‌شود:
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '1');
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">'
       . '<meta name="robots" content="noindex,nofollow"><title>مهاجرت مدارک</title>'
       . '<style>body{font-family:Tahoma,sans-serif;max-width:900px;margin:20px auto;'
       . 'padding:20px;background:#f8f9fa;line-height:1.6} .ok{color:#28a745}'
       . ' .skip{color:#ffc107} .fail{color:#dc3545} .info{color:#17a2b8}'
       . ' pre{background:#fff;padding:15px;border-radius:8px;overflow-x:auto;'
       . 'border:1px solid #dee2e6} h1,h2{color:#00B4D8}</style></head><body>';
}

// بارگذاری پیکربندی
$rootPath = dirname(__DIR__, 1);
if (!file_exists($rootPath . '/config/config.php')) {
    // تلاش برای یافتن ریشهٔ پروژه
    $candidates = [
        dirname(__DIR__),
        dirname(__DIR__, 2),
        __DIR__ . '/..',
        getcwd(),
    ];
    foreach ($candidates as $c) {
        if (file_exists($c . '/config/config.php')) {
            $rootPath = $c;
            break;
        }
    }
}

if (!file_exists($rootPath . '/config/config.php')) {
    die("❌ فایل config/config.php یافت نشد. این اسکریپت را از ریشهٔ پروژه اجرا کنید.\n");
}

// بارگذاری ثابت‌ها و autoloader
require_once $rootPath . '/config/env.php';
load_env();
require_once $rootPath . '/config/config.php';
require_once $rootPath . '/config/helper.php';
require_once $rootPath . '/config/jdf.php';
require_once CORE_PATH . '/AutoLoader.php';
AutoLoader::register();

// ==================== شروع مهاجرت ====================
echo "<h1>🔄 مهاجرت مدارک به ساختار کد رهگیری</h1>\n";
echo "<pre>\n";

$db = Database::getInstance()->getConnection();

// آمار
$stats = [
    'total' => 0,
    'migrated' => 0,
    'skipped' => 0,
    'deleted' => 0,
    'errors' => 0,
];

echo "=== شروع مهاجرت ===\n";
echo "زمان شروع: " . date('Y-m-d H:i:s') . "\n\n";

// یافتن همهٔ مدارک قدیمی (file_path با /YYYY/MM/ شروع می‌شود)
$stmt = $db->prepare(
    "SELECT d.id, d.applicant_id, d.doc_type, d.file_name, d.file_path,
            a.tracking_code
     FROM documents d
     JOIN applicants a ON a.id = d.applicant_id
     WHERE d.file_path REGEXP '^/[0-9]{4}/[0-9]{2}/'
     ORDER BY d.id"
);
$stmt->execute();
$oldDocs = $stmt->fetchAll();

$stats['total'] = count($oldDocs);
echo "تعداد مدارک قدیمی یافت‌شده: {$stats['total']}\n\n";

if ($stats['total'] === 0) {
    echo "✅ هیچ مدرک قدیمی برای مهاجرت وجود ندارد.\n";
    echo "\n=== پایان ===\n";
    echo "</pre>\n";
    if (php_sapi_name() !== 'cli') {
        echo '<p><strong>⚠️ این فایل را حذف کنید:</strong> migrate-documents.php</p>';
        echo '</body></html>';
    }
    exit(0);
}

echo "شروع پردازش...\n\n";

foreach ($oldDocs as $doc) {
    $docId = (int)$doc['id'];
    $trackingCode = $doc['tracking_code'];
    $docType = $doc['doc_type'];
    $oldPath = $doc['file_path'];
    $oldFileName = $doc['file_name'];

    // مسیر کامل فایل قدیمی
    $oldFullPath = UPLOAD_DIR . $oldPath;
    $oldRealPath = realpath($oldFullPath);
    $realBase = realpath(UPLOAD_DIR);

    // بررسی امنیت مسیر
    if ($oldRealPath === false || $realBase === false ||
        !str_starts_with($oldRealPath, $realBase)) {
        echo "  ❌ [{$docId}] مسیر نامعتبر: {$oldPath}\n";
        $stats['errors']++;
        logMigration($db, $docId, $oldPath, null, 'error', 'مسیر نامعتبر یا خارج از uploads/');
        continue;
    }

    // بررسی وجود فایل فیزیکی
    if (!is_file($oldRealPath)) {
        echo "  ⚠️ [{$docId}] فایل موجود نیست — حذف رکورد: {$oldPath}\n";
        // حذف رکورد دیتابیس (فایل فیزیکی از بین رفته)
        $delStmt = $db->prepare("DELETE FROM documents WHERE id = :id");
        $delStmt->execute(['id' => $docId]);
        $stats['deleted']++;
        logMigration($db, $docId, $oldPath, null, 'deleted', 'فایل فیزیکی موجود نبود');
        continue;
    }

    // نرمال‌سازی نام پوشه و فایل
    $safeTrackingCode = preg_replace('/[^A-Za-z0-9\-_]/', '', $trackingCode);
    if ($safeTrackingCode === '') {
        $safeTrackingCode = 'unknown';
    }
    $safeDocType = preg_replace('/[^A-Za-z0-9_]/', '_', $docType);
    if ($safeDocType === '') {
        $safeDocType = 'document';
    }

    // استخراج پسوند فایل
    $ext = pathinfo($oldFileName, PATHINFO_EXTENSION);
    if ($ext === '') {
        // تلاش برای استخراج از مسیر قدیمی
        $ext = pathinfo($oldRealPath, PATHINFO_EXTENSION);
    }
    $ext = strtolower($ext);

    // ساخت پوشهٔ کد رهگیری
    $newFolder = UPLOAD_DIR . '/' . $safeTrackingCode;
    if (!is_dir($newFolder)) {
        if (!mkdir($newFolder, 0755, true)) {
            echo "  ❌ [{$docId}] خطا در ساخت پوشه: {$newFolder}\n";
            $stats['errors']++;
            logMigration($db, $docId, $oldPath, null, 'error', "خطا در ساخت پوشه: {$newFolder}");
            continue;
        }
    }

    // تولید نام فایل جدید: {doc_type}_{random6}.{ext}
    $randomSuffix = bin2hex(random_bytes(3)); // ۶ کاراکتر hex
    $newFileName = $safeDocType . '_' . $randomSuffix . '.' . $ext;
    $newFullPath = $newFolder . '/' . $newFileName;

    // بررسی عدم وجود فایل با همین نام
    if (file_exists($newFullPath)) {
        // تولید نام دیگر
        $randomSuffix = bin2hex(random_bytes(4));
        $newFileName = $safeDocType . '_' . $randomSuffix . '.' . $ext;
        $newFullPath = $newFolder . '/' . $newFileName;
    }

    // انتقال فایل (rename = move)
    if (!rename($oldRealPath, $newFullPath)) {
        echo "  ❌ [{$docId}] خطا در انتقال فایل: {$oldPath} → {$newFullPath}\n";
        $stats['errors']++;
        logMigration($db, $docId, $oldPath, null, 'error', "خطا در rename: {$oldPath} → {$newFullPath}");
        continue;
    }

    // تنظیم دسترسی‌ها
    @chmod($newFullPath, 0640);

    // مسیر نسبی جدید
    $newRelativePath = '/' . $safeTrackingCode . '/' . $newFileName;

    // به‌روزرسانی دیتابیس
    $updStmt = $db->prepare(
        "UPDATE documents
         SET file_name = :fname, file_path = :fpath
         WHERE id = :id"
    );
    $updStmt->execute([
        'fname' => $newFileName,
        'fpath' => $newRelativePath,
        'id'    => $docId,
    ]);

    echo "  ✅ [{$docId}] منتقل شد: {$oldPath} → {$newRelativePath}\n";
    $stats['migrated']++;
    logMigration($db, $docId, $oldPath, $newRelativePath, 'migrated', 'موفق');
}

// ==================== گزارش نهایی ====================
echo "\n=== گزارش نهایی ===\n";
echo "کل مدارک قدیمی:    {$stats['total']}\n";
echo "مهاجرت موفق:        {$stats['migrated']}\n";
echo "skip (فایل موجود نبود، رکورد حذف شد): {$stats['deleted']}\n";
echo "خطا:                {$stats['errors']}\n";
echo "\nزمان پایان: " . date('Y-m-d H:i:s') . "\n";

if ($stats['migrated'] > 0) {
    echo "\n✅ مهاجرت با موفقیت انجام شد!\n";
    echo "\n📋 مراحل بعدی:\n";
    echo "  ۱) پوشه‌های قدیمی را بررسی کنید (باید خالی باشند):\n";
    echo "     ls -la uploads/2025/ uploads/2026/ (اگر وجود دارند)\n";
    echo "  ۲) پس از تأیید، پوشه‌های قدیمی را حذف کنید:\n";
    echo "     rm -rf uploads/2025/ uploads/2026/\n";
    echo "  ۳) جدول موقت گزارش را حذف کنید:\n";
    echo "     DROP TABLE _migration_report;\n";
} else if ($stats['errors'] > 0) {
    echo "\n⚠️ مهاجرت با خطا مواجه شد. خطاهای بالا را بررسی کنید.\n";
} else {
    echo "\nℹ️ هیچ فایلی برای مهاجرت نبود.\n";
}

echo "\n=== پایان ===\n";
echo "</pre>\n";

if (php_sapi_name() !== 'cli') {
    echo '<p><strong>⚠️ این فایل را حذف کنید:</strong> migrate-documents.php</p>';
    echo '</body></html>';
}

/**
 * ثبت گزارش مهاجرت در جدول موقت
 */
function logMigration(PDO $db, int $docId, string $oldPath, ?string $newPath, string $status, string $message): void
{
    try {
        $stmt = $db->prepare(
            "INSERT INTO _migration_report (document_id, old_path, new_path, status, message)
             VALUES (:doc_id, :old, :new, :status, :msg)"
        );
        $stmt->execute([
            'doc_id'  => $docId,
            'old'     => $oldPath,
            'new'     => $newPath,
            'status'  => $status,
            'msg'     => $message,
        ]);
    } catch (\Throwable $e) {
        // خطای لاگ‌گذاری نباید مهاجرت را متوقف کند
    }
}
