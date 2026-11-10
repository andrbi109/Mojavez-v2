<?php
/**
 * سرویس مدیریت ذخیره‌سازی فایل‌ها
 * =====================================================================
 * این سرویس مسئولیت زیر را بر عهده دارد:
 *   ۱) ذخیرهٔ فایل‌های آپلودشده با ساختار منظم:
 *      uploads/{tracking_code}/{doc_type}_{filename}.ext
 *   ۲) فشرده‌سازی تصاویر (JPEG/PNG) برای بهینه‌سازی فضای سرور
 *   ۳) حذف امن فایل‌ها
 *
 * ساختار پوشه‌ها:
 *   uploads/
 *   ├── HG-ABC12345/                    ← پوشهٔ کد رهگیری متقاضی
 *   │   ├── national_card_front_a1b2c3.jpg
 *   │   ├── national_card_back_d4e5f6.jpg
 *   │   ├── birth_cert_7890ab.pdf
 *   │   └── ...
 *   └── HG-DEF67890/
 *       └── ...
 *
 * فشرده‌سازی تصاویر:
 *   - تصاویر بزرگ‌تر از ۱۹۲۰px به ۱۹۲۰px تغییر اندازه می‌یابند
 *   - JPEG با کیفیت ۸۵٪ ذخیره می‌شود (کاهش حجم بدون افت بصری قابل‌توجه)
 *   - PNG با فشرده‌سازی ۹ ذخیره می‌شود
 *   - PDF بدون تغییر ذخیره می‌شود
 */
class FileStorage
{
    /** حداکثر عرض/ارتفاع تصویر برای تغییر اندازه */
    private const MAX_IMAGE_DIMENSION = 1920;

    /** کیفیت JPEG برای فشرده‌سازی (۰-۱۰۰) */
    private const JPEG_QUALITY = 85;

    /** سطح فشرده‌سازی PNG (۰-۹) */
    private const PNG_COMPRESSION = 9;

    /** پسوندهای تصویری که فشرده می‌شوند */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * ذخیرهٔ یک فایل آپلودشده با ساختار جدید.
     *
     * @param array  $file         ورودی $_FILES[name]
     * @param int    $applicantId  شناسهٔ متقاضی
     * @param string $trackingCode کد رهگیری متقاضی
     * @param string $docType      نوع مدرک (national_card_front، birth_cert، ...)
     * @return array{success: bool, error: ?string, document_id: ?int, file_path: ?string}
     */
    public function storeUpload(array $file, int $applicantId, string $trackingCode, string $docType): array
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => null, 'document_id' => null, 'file_path' => null];
        }

        // اعتبارسنجی فایل
        $validation = validate_uploaded_file($file);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error'] ?? 'فایل نامعتبر', 'document_id' => null, 'file_path' => null];
        }

        $ext = $validation['ext'];
        $originalName = sanitize_upload_filename((string)$file['name']);
        $tmpPath = $file['tmp_name'];

        // نرمال‌سازی کد رهگیری برای استفاده به‌عنوان نام پوشه
        $safeTrackingCode = $this->sanitizeFolderName($trackingCode);
        $docTypeSafe = $this->sanitizeFileName($docType);

        // ساخت پوشهٔ کد رهگیری
        $folderPath = UPLOAD_DIR . '/' . $safeTrackingCode;
        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0755, true);
        }

        // تولید نام فایل: {doc_type}_{random6}.{ext}
        $randomSuffix = bin2hex(random_bytes(3)); // ۶ کاراکتر hex
        $fileName = $docTypeSafe . '_' . $randomSuffix . '.' . $ext;
        $destPath = $folderPath . '/' . $fileName;
        $relativePath = '/' . $safeTrackingCode . '/' . $fileName;

        // پردازش و ذخیرهٔ فایل
        $isImage = in_array(strtolower($ext), self::IMAGE_EXTENSIONS, true);
        if ($isImage && extension_loaded('gd')) {
            // فشرده‌سازی تصویر
            $compressed = $this->compressImage($tmpPath, $destPath, $ext);
            if (!$compressed) {
                // اگر فشرده‌سازی ناموفق بود، فایل اصلی را کپی کن
                if (!move_uploaded_file($tmpPath, $destPath)) {
                    return ['success' => false, 'error' => 'خطا در ذخیرهٔ فایل', 'document_id' => null, 'file_path' => null];
                }
            }
        } else {
            // فایل غیرتصویری (PDF) — مستقیم کپی کن
            if (!move_uploaded_file($tmpPath, $destPath)) {
                return ['success' => false, 'error' => 'خطا در ذخیرهٔ فایل', 'document_id' => null, 'file_path' => null];
            }
        }

        @chmod($destPath, 0640);

        // ذخیره در دیتابیس
        $document = new Document();
        $docId = $document->create([
            'applicant_id'  => $applicantId,
            'doc_type'       => $docType,
            'file_name'      => $fileName,
            'original_name'  => $originalName,
            'file_path'      => $relativePath,
        ]);

        return [
            'success'      => true,
            'error'        => null,
            'document_id'  => $docId,
            'file_path'    => $relativePath,
        ];
    }

    /**
     * فشرده‌سازی یک تصویر و ذخیرهٔ آن در مسیر مقصد.
     *
     * @param string $srcPath  مسیر فایل مبدأ (tmp)
     * @param string $destPath مسیر مقصد نهایی
     * @param string $ext     پسوند فایل (jpg، jpeg، png)
     * @return bool آیا فشرده‌سازی موفق بود؟
     */
    private function compressImage(string $srcPath, string $destPath, string $ext): bool
    {
        $ext = strtolower($ext);

        // بارگذاری تصویر بر اساس نوع
        $image = null;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($srcPath);
                break;
            case 'png':
                $image = @imagecreatefrompng($srcPath);
                break;
        }

        if ($image === false || $image === null) {
            return false;
        }

        // تغییر اندازه اگر بزرگ‌تر از حداکثر مجاز است
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        if ($origWidth > self::MAX_IMAGE_DIMENSION || $origHeight > self::MAX_IMAGE_DIMENSION) {
            $ratio = min(self::MAX_IMAGE_DIMENSION / $origWidth, self::MAX_IMAGE_DIMENSION / $origHeight);
            $newWidth = (int)round($origWidth * $ratio);
            $newHeight = (int)round($origHeight * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            if ($resized === false) {
                imagedestroy($image);
                return false;
            }

            // حفظ شفافیت برای PNG
            if ($ext === 'png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($image);
            $image = $resized;
        }

        // ذخیرهٔ تصویر فشرده‌شده
        $result = false;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $result = imagejpeg($image, $destPath, self::JPEG_QUALITY);
                break;
            case 'png':
                $result = imagepng($image, $destPath, self::PNG_COMPRESSION);
                break;
        }

        imagedestroy($image);
        return $result;
    }

    /**
     * حذف یک فایل فیزیکی از دیسک.
     *
     * @param string $relativePath مسیر نسبی (مثل /HG-ABC12345/national_card_front_a1b2c3.jpg)
     * @return bool آیا حذف شد؟
     */
    public function deleteFile(string $relativePath): bool
    {
        if ($relativePath === '') return false;

        // پاک‌سازی مسیر برای جلوگیری از directory traversal
        $cleanPath = str_replace(['..', "\0"], '', $relativePath);
        $fullPath = UPLOAD_DIR . $cleanPath;
        $realPath = realpath($fullPath);
        $realBase = realpath(UPLOAD_DIR);

        // اطمینان از اینکه مسیر داخل UPLOAD_DIR است
        if ($realPath === false || $realBase === false || !str_starts_with($realPath, $realBase)) {
            return false;
        }

        if (is_file($realPath)) {
            return @unlink($realPath);
        }
        return false;
    }

    /**
     * حذف کامل پوشهٔ یک متقاضی (همهٔ فایل‌های او).
     *
     * @param string $trackingCode کد رهگیری متقاضی
     * @return bool آیا حذف شد؟
     */
    public function deleteApplicantFolder(string $trackingCode): bool
    {
        $safeTrackingCode = $this->sanitizeFolderName($trackingCode);
        $folderPath = UPLOAD_DIR . '/' . $safeTrackingCode;
        $realPath = realpath($folderPath);
        $realBase = realpath(UPLOAD_DIR);

        if ($realPath === false || $realBase === false || !str_starts_with($realPath, $realBase)) {
            return false;
        }

        if (!is_dir($realPath)) {
            return false;
        }

        // حذف همهٔ فایل‌های داخل پوشه
        $files = glob($realPath . '/*');
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        // حذف پوشهٔ خالی
        return @rmdir($realPath);
    }

    /**
     * دریافت مسیر کامل فیزیکی یک فایل (برای سرو کردن به کاربر).
     *
     * @param string $relativePath مسیر نسبی ذخیره‌شده در دیتابیس
     * @return string|null مسیر کامل یا null اگر نامعتبر
     */
    public function getFullPath(string $relativePath): ?string
    {
        if ($relativePath === '') return null;

        $cleanPath = str_replace(['..', "\0"], '', $relativePath);
        $fullPath = UPLOAD_DIR . $cleanPath;
        $realPath = realpath($fullPath);
        $realBase = realpath(UPLOAD_DIR);

        if ($realPath === false || $realBase === false || !str_starts_with($realPath, $realBase)) {
            return null;
        }

        return is_file($realPath) ? $realPath : null;
    }

    /**
     * نرمال‌سازی نام پوشه (کد رهگیری) — فقط حروف، اعداد، خط تیره.
     */
    private function sanitizeFolderName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/[^A-Za-z0-9\-_]/', '', $name);
        return $name === '' ? 'unknown' : $name;
    }

    /**
     * نرمال‌سازی نام فایل (نوع مدرک) — فقط حروف، اعداد، خط زیر.
     */
    private function sanitizeFileName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/[^A-Za-z0-9_]/', '_', $name);
        return $name === '' ? 'document' : $name;
    }
}
