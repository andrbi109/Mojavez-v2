<?php
/**
 * کنترلر نمایش امن مدارک
 * -------------------------------------------------
 * فایل‌های بارگذاری‌شده هرگز مستقیماً از وب سرو نمی‌شوند؛
 * پوشهٔ uploads بیرون از document root و همراه .htaccess محافظ است.
 * دسترسی به فایل‌ها فقط از طریق این کنترلر و صرفاً برای مدیران
 * واردشده (با بررسی نشست) امکان‌پذیر است.
 */
class DocumentController extends Controller
{
    private Document $documentModel;

    /** انواع MIME مجاز برای سرو کردن */
    private const SAFE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'application/pdf' => 'pdf',
    ];

    /** حداکثر ابعاد بندانگشتی */
    private const THUMB_MAX_WIDTH = 200;
    private const THUMB_MAX_HEIGHT = 200;

    public function __construct()
    {
        $this->documentModel = new Document();
    }

    /**
     * نمایش فایل با درخواست POST (استفاده در مودال مشاهدهٔ مدرک)
     */
    public function view(): void
    {
        $this->requireLogin();
        $this->verifyCsrf();

        $documentId = (int)($_POST['document_id'] ?? 0);
        $applicantId = (int)($_POST['applicant_id'] ?? 0);

        $document = $this->documentModel->findById($documentId);
        if (!$document || ($applicantId > 0 && (int)$document['applicant_id'] !== $applicantId)) {
            $this->deny(404, 'فایل یافت نشد یا دسترسی ندارید.');
        }

        $this->stream($document, 'inline');
    }

    /**
     * نمایش آواتار کاربر با درخواست GET (بدون CSRF)
     */
    public function avatar(): void
    {
        $this->requireAnyLogin();

        $documentId = (int)($_GET['id'] ?? 0);
        if ($documentId <= 0) {
            $this->deny(404, 'شناسهٔ فایل نامعتبر است.');
        }

        $document = $this->documentModel->findById($documentId);
        if (!$document) {
            $this->deny(404, 'فایل یافت نشد.');
        }

        if ($document['doc_type'] !== 'avatar') {
            $this->deny(403, 'دسترسی غیرمجاز.');
        }

        $this->stream($document, 'inline');
    }

    /**
     * نمایش فایل با درخواست GET (برای iframe/img)
     * برای PDF: به صفحهٔ PDF viewer هدایت می‌شود
     * برای تصاویر: مستقیماً inline سرو می‌شود
     */
    public function inline(int|string $id): void
    {
        $this->requireLogin();

        $document = is_numeric($id) ? $this->documentModel->findById((int)$id) : null;
        if (!$document) {
            $this->deny(404, 'فایل یافت نشد.');
        }

        $ext = strtolower(safe_file_extension((string)$document['file_name']));

        if ($ext === 'pdf') {
            $base = rtrim(BASE_URL, '/') . '/';
            header('Location: ' . $base . 'document/pdf-viewer?id=' . (int)$id);
            exit;
        }

        $this->stream($document, 'inline');
    }

    /**
     * نمایش PDF با PDF.js — دادهٔ PDF مستقیماً در HTML embed می‌شود
     * (بدون نیاز به fetch جداگانه — حل مشکل CSP/CORP)
     */
    public function pdfViewer(): void
    {
        $this->requireLogin();

        $docId = (int)($_GET['id'] ?? 0);
        if ($docId <= 0) {
            $this->deny(404, 'شناسهٔ فایل نامعتبر.');
        }

        $document = $this->documentModel->findById($docId);
        if (!$document) {
            $this->deny(404, 'فایل یافت نشد.');
        }

        // خواندن فایل PDF
        $clean = str_replace(['..', "\0"], '', (string)$document['file_path']);
        $filePath = realpath(UPLOAD_DIR . $clean);
        $base = realpath(UPLOAD_DIR);

        if ($filePath === false || $base === false || !str_starts_with($filePath, $base) || !is_file($filePath)) {
            $this->deny(404, 'فایل موجود نیست.');
        }

        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($filePath);
        if ($mime !== 'application/pdf') {
            $this->deny(415, 'این فایل PDF نیست.');
        }

        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            $this->deny(500, 'خطا در خواندن فایل.');
        }

        // تبدیل به base64 برای embed در HTML
        $base64Data = base64_encode($fileContent);

        $base = rtrim(BASE_URL, '/') . '/';
        $safeName = htmlspecialchars((string)$document['original_name'], ENT_QUOTES, 'UTF-8');

        // خروجی HTML با PDF data embedded
        header('Content-Type: text/html; charset=utf-8');
        header('X-Frame-Options: SAMEORIGIN');
        header('Cache-Control: private, max-age=3600');

        echo <<<HTML
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>پیش‌نمایش {$safeName}</title>
<link rel="stylesheet" href="{$base}assets/font.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #525659; font-family: Vazir, Tahoma, Arial, sans-serif; overflow: auto; }
#pdfContainer { width: 100%; text-align: center; }
#pdfContainer canvas { max-width: 100%; margin: 10px auto; display: block;
    box-shadow: 0 2px 10px rgba(0,0,0,.3); background: #fff; }
#loading { color: #fff; text-align: center; padding: 40px; font-size: 16px; }
#error { color: #ff6b6b; text-align: center; padding: 40px; font-size: 16px; }
.toolbar { background: #323639; padding: 8px 16px; display: flex;
    justify-content: center; align-items: center; gap: 12px; color: #fff;
    font-size: 13px; position: sticky; top: 0; z-index: 100; }
.toolbar button { background: #00B4D8; color: #fff; border: none;
    padding: 4px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
.toolbar button:hover { background: #0096B8; }
.toolbar span { min-width: 60px; text-align: center; }
</style>
</head>
<body>
<div class="toolbar">
    <button onclick="prevPage()">→ قبلی</button>
    <span id="pageInfo">صفحهٔ ? از ?</span>
    <button onclick="nextPage()">بعدی ←</button>
    <button onclick="zoomOut()">−</button>
    <span id="zoomLevel">۱۰۰٪</span>
    <button onclick="zoomIn()">+</button>
</div>
<div id="loading">در حال بارگذاری PDF…</div>
<div id="error" style="display:none;"></div>
<div id="pdfContainer"></div>
<script src="{$base}assets/js/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = '{$base}assets/js/pdf.worker.min.js';

let pdfDoc = null;
let currentPage = 1;
let scale = 1.5;
let rendering = false;
let pendingPage = null;

// دادهٔ PDF به‌صورت base64 — مستقیماً در HTML embed شده
// (بدون نیاز به fetch — حل مشکل CSP/CORP)
const pdfBase64 = "{$base64Data}";
const pdfBytes = (function() {
    const binaryStr = atob(pdfBase64);
    const len = binaryStr.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryStr.charCodeAt(i);
    }
    return bytes;
})();

const container = document.getElementById('pdfContainer');
const loading = document.getElementById('loading');
const errorDiv = document.getElementById('error');
const pageInfo = document.getElementById('pageInfo');
const zoomLevel = document.getElementById('zoomLevel');

function toPersianDigits(str) {
    return String(str).replace(/[0-9]/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
}

function renderPage(num) {
    rendering = true;
    pdfDoc.getPage(num).then(function(page) {
        const viewport = page.getViewport({ scale: scale });
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        container.innerHTML = '';
        container.appendChild(canvas);
        page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function() {
            rendering = false;
            if (pendingPage !== null) {
                const p = pendingPage; pendingPage = null; renderPage(p);
            }
        });
        pageInfo.textContent = toPersianDigits('صفحهٔ ' + num + ' از ' + pdfDoc.numPages);
    }).catch(function(err) {
        rendering = false;
        console.error('Render error:', err);
    });
}

function queueRenderPage(num) {
    if (rendering) { pendingPage = num; } else { renderPage(num); }
}
function prevPage() { if (currentPage <= 1) return; currentPage--; queueRenderPage(currentPage); }
function nextPage() { if (currentPage >= pdfDoc.numPages) return; currentPage++; queueRenderPage(currentPage); }
function zoomIn() { scale += 0.25; zoomLevel.textContent = toPersianDigits(Math.round(scale * 100 / 1.5) + '٪'); queueRenderPage(currentPage); }
function zoomOut() { if (scale <= 0.5) return; scale -= 0.25; zoomLevel.textContent = toPersianDigits(Math.round(scale * 100 / 1.5) + '٪'); queueRenderPage(currentPage); }

// بارگذاری PDF از دادهٔ embed شده
pdfjsLib.getDocument({
    data: pdfBytes,
    cMapUrl: '{$base}assets/js/cmaps/',
    cMapPacked: true,
    standardFontDataUrl: '{$base}assets/js/standard_fonts/',
    useSystemFonts: true,
    disableFontFace: false,
    isEvalSupported: false
}).promise.then(function(pdf) {
    pdfDoc = pdf;
    loading.style.display = 'none';
    renderPage(currentPage);
}).catch(function(err) {
    loading.style.display = 'none';
    errorDiv.style.display = 'block';
    errorDiv.textContent = 'خطا در بارگذاری PDF: ' + (err.message || 'نامشخص');
    console.error('PDF.js error:', err);
});
</script>
</body>
</html>
HTML;
        exit;
    }

    /**
     * بندانگشتی مدرک — با تغییر اندازه واقعی برای بهینه‌سازی
     */
    public function thumb(int|string $id): void
    {
        $this->requireLogin();

        $document = is_numeric($id) ? $this->documentModel->findById((int)$id) : null;
        if (!$document) {
            $this->deny(404);
        }

        $clean = str_replace(['..', "\0"], '', (string)$document['file_path']);
        $filePath = realpath(UPLOAD_DIR . $clean);
        $base = realpath(UPLOAD_DIR);

        if ($filePath === false || $base === false || !str_starts_with($filePath, $base) || !is_file($filePath)) {
            $this->deny(404, 'فایل موجود نیست.');
        }

        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($filePath);
        if (!array_key_exists($mime, self::SAFE_MIMES)) {
            $this->deny(415, 'نوع فایل قابل نمایش نیست.');
        }

        // برای PDF: نمایش آیکون PDF (بدون رندر)
        if ($mime === 'application/pdf') {
            $this->servePdfIcon();
            return;
        }

        // برای تصاویر: تولید بندانگشتی با GD (اگر موجود است)
        if (extension_loaded('gd') && in_array($mime, ['image/jpeg', 'image/png'])) {
            $this->serveThumbnail($filePath, $mime);
            return;
        }

        // Fallback: سرو فایل اصلی
        $this->stream($document, 'inline');
    }

    /* ==================== توابع داخلی ==================== */

    /**
     * ارسال امن فایل به مرورگر
     */
    private function stream(array $document, string $disposition): void
    {
        $clean = str_replace(['..', "\0"], '', (string)$document['file_path']);
        $filePath = realpath(UPLOAD_DIR . $clean);
        $base = realpath(UPLOAD_DIR);

        if ($filePath === false || $base === false || !str_starts_with($filePath, $base) || !is_file($filePath)) {
            $this->deny(404, 'فایل موجود نیست.');
        }

        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($filePath);
        if (!array_key_exists($mime, self::SAFE_MIMES)) {
            $this->deny(415, 'نوع فایل قابل نمایش نیست.');
        }

        header('Content-Type: ' . $mime);

        if ($disposition === 'inline') {
            header('Content-Disposition: inline');
            header('X-Frame-Options: SAMEORIGIN');
            header('Cache-Control: private, max-age=3600');
        } else {
            $safeName = preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', (string)$document['original_name']);
            header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
        }

        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        readfile($filePath);
        exit;
    }

    /**
     * تولید و سرو بندانگشتی با GD
     */
    private function serveThumbnail(string $filePath, string $mime): void
    {
        // بارگذاری تصویر
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($filePath),
            'image/png' => @imagecreatefrompng($filePath),
            default => false,
        };

        if ($image === false) {
            // اگر GD ناموفق بود، فایل اصلی را سرو کن
            readfile($filePath);
            exit;
        }

        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        // محاسبه ابعاد جدید
        $ratio = min(self::THUMB_MAX_WIDTH / $origWidth, self::THUMB_MAX_HEIGHT / $origHeight);
        if ($ratio >= 1) {
            // تصویر کوچک‌تر از حداکثر است —无需 تغییر اندازه
            imagedestroy($image);
            header('Content-Type: ' . $mime);
            header('Cache-Control: private, max-age=86400');
            header('X-Content-Type-Options: nosniff');
            readfile($filePath);
            exit;
        }

        $newWidth = (int)round($origWidth * $ratio);
        $newHeight = (int)round($origHeight * $ratio);

        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        // حفظ شفافیت PNG
        if ($mime === 'image/png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
            imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        imagedestroy($image);

        // خروجی
        header('Content-Type: ' . $mime);
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');

        match ($mime) {
            'image/jpeg' => imagejpeg($thumb, null, 80),
            'image/png' => imagepng($thumb, null, 6),
        };

        imagedestroy($thumb);
        exit;
    }

    /**
     * نمایش آیکون PDF برای بندانگشتی
     */
    private function servePdfIcon(): void
    {
        // SVG آیکون PDF
        header('Content-Type: image/svg+xml');
        header('Cache-Control: private, max-age=86400');
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="150" viewBox="0 0 120 150">'
           . '<rect width="120" height="150" fill="#f8f9fa" stroke="#dee2e6" rx="8"/>'
           . '<text x="60" y="70" text-anchor="middle" font-size="14" fill="#dc3545" font-family="Arial">PDF</text>'
           . '<text x="60" y="95" text-anchor="middle" font-size="10" fill="#6c757d" font-family="Arial">سند</text>'
           . '</svg>';
        exit;
    }

    private function deny(int $code, string $message = 'دسترسی غیرمجاز'): void
    {
        http_response_code($code);
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
        exit;
    }
}
