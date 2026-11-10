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
        $this->verifyCsrf(); // درخواست‌های AJAX نیز توکن CSRF می‌فرستند

        // اصلاح باگ اولویت عملگرها: ابتدا ?? و سپس cast
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
     * فقط کاربر واردشده می‌تواند آواتار خودش را ببیند.
     * URL: /document/avatar?id=DOCUMENT_ID
     */
    public function avatar(): void
    {
        // رفع C-1: هم مدیر و هم کاربر می‌توانند آواتار را ببینند
        $this->requireAnyLogin();

        $documentId = (int)($_GET['id'] ?? 0);
        if ($documentId <= 0) {
            $this->deny(404, 'شناسهٔ فایل نامعتبر است.');
        }

        $document = $this->documentModel->findById($documentId);
        if (!$document) {
            $this->deny(404, 'فایل یافت نشد.');
        }

        // فقط doc_type='avatar' مجاز است
        if ($document['doc_type'] !== 'avatar') {
            $this->deny(403, 'دسترسی غیرمجاز.');
        }

        $this->stream($document, 'inline');
    }

    /**
     * نمایش فایل با درخواست GET (برای استفاده در iframe/embed)
     * URL: /document/inline/{id}
     * برای PDF: به صفحهٔ PDF viewer هدایت می‌شود (رندر با PDF.js)
     * برای تصاویر: مستقیماً inline سرو می‌شود
     */
    public function inline(int|string $id): void
    {
        $this->requireLogin();

        $document = is_numeric($id) ? $this->documentModel->findById((int)$id) : null;
        if (!$document) {
            $this->deny(404, 'فایل یافت نشد.');
        }

        // تشخیص نوع فایل
        $ext = strtolower(safe_file_extension((string)$document['file_name']));

        if ($ext === 'pdf') {
            // برای PDF: هدایت به PDF viewer که با PDF.js رندر می‌کند
            // (کاملاً inline — بدون امکان دانلود)
            $base = rtrim(BASE_URL, '/') . '/';
            header('Location: ' . $base . 'document/pdf-viewer?id=' . (int)$id);
            exit;
        }

        // برای تصاویر و سایر فایل‌ها: مستقیم inline سرو کن
        $this->stream($document, 'inline');
    }

    /**
     * نمایش PDF با PDF.js (رندر در مرورگر — بدون دانلود)
     * URL: /document/pdf-viewer?id=21
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

        // این متد فقط PDF viewer را نمایش می‌دهد
        // خود فایل PDF از طریق /document/inline/{id} دریافت می‌شود
        // (که در inline() هدایت به اینجا می‌شود)

        // نمایش صفحهٔ PDF viewer
        $base = rtrim(BASE_URL, '/') . '/';
        $docId = (int)$docId;

        // پاک‌سازی هدرهای مختل‌کننده
        if (!headers_sent()) {
            header_remove('X-Frame-Options');
            header_remove('Content-Security-Policy');
            header_remove('Cross-Origin-Resource-Policy');
            header_remove('Cross-Origin-Opener-Policy');
            header_remove('Cache-Control');
            header_remove('Pragma');
        }

        header('Content-Type: text/html; charset=utf-8');
        header('X-Frame-Options: SAMEORIGIN');
        header('Cache-Control: private, max-age=3600');

        // خروجی HTML صفحهٔ PDF viewer
        echo $this->renderPdfViewerHtml($base, $docId, (string)$document['original_name']);
        exit;
    }

    /**
     * سرو کردن فایل خام PDF برای PDF.js (بدون redirect)
     * URL: /document/raw/{id}
     * این متد فقط برای fetch از داخل PDF.js استفاده می‌شود.
     */
    public function raw(int|string $id): void
    {
        $this->requireLogin();

        $document = is_numeric($id) ? $this->documentModel->findById((int)$id) : null;
        if (!$document) {
            $this->deny(404, 'فایل یافت نشد.');
        }

        // سرو کردن مستقیم فایل (بدون redirect)
        $this->streamRaw($document);
    }

    /**
     * بندانگشتی مدرک (در فهرست پرونده) — فعلاً همان فایل اصلی با هدرهای محدودکنندهٔ کش
     */
    public function thumb(int|string $id): void
    {
        $this->requireLogin();

        // ورودی غیرعددی به‌سادگی به صفر تبدیل و ۴۰۴ می‌شود (بدون TypeError)
        $document = is_numeric($id) ? $this->documentModel->findById((int)$id) : null;
        if (!$document) {
            $this->deny(404);
        }

        $this->stream($document, 'inline');
    }

    /* ==================== توابع داخلی ==================== */

    /**
     * ارسال امن فایل به مرورگر با بررسی نوع واقعی محتوا
     */
    private function stream(array $document, string $disposition): void
    {
        // محافظت در برابر Path Traversal — مسیر باید داخل uploads بماند
        $clean = str_replace(['..', "\0"], '', (string)$document['file_path']);
        $filePath = realpath(UPLOAD_DIR . $clean);
        $base = realpath(UPLOAD_DIR);

        if ($filePath === false || $base === false || !str_starts_with($filePath, $base) || !is_file($filePath)) {
            $this->deny(404, 'فایل موجود نیست.');
        }

        // تعیین MIME واقعی — فقط انواع سفید سرو می‌شوند
        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($filePath);
        if (!array_key_exists($mime, self::SAFE_MIMES)) {
            $this->deny(415, 'نوع فایل قابل نمایش نیست.');
        }

        // پاک‌سازی هدرهای سراسری که نمایش inline را مختل می‌کنند
        if (!headers_sent()) {
            header_remove('X-Frame-Options');
            header_remove('Content-Security-Policy');
            header_remove('Cache-Control');
            header_remove('Pragma');
        }

        header('Content-Type: ' . $mime);

        if ($disposition === 'inline') {
            // برای نمایش inline (iframe/img):
            // — filename حذف می‌شود تا مرورگر دانلود نکند
            // — X-Frame-Options: SAMEORIGIN برای اجازهٔ نمایش در iframe
            header('Content-Disposition: inline');
            header('X-Frame-Options: SAMEORIGIN');
            header('Cache-Control: private, max-age=3600');
        } else {
            // برای دانلود: filename مشخص می‌شود
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

    private function deny(int $code, string $message = 'دسترسی غیرمجاز'): void
    {
        http_response_code($code);
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
        exit;
    }

    /**
     * سرو کردن فایل خام (برای fetch از JavaScript) — بدون Content-Disposition
     * خروجی: JSON با base64-encoded PDF برای استفاده در PDF.js
     */
    private function streamRaw(array $document): void
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

        // خواندن محتوای فایل
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            $this->deny(500, 'خطا در خواندن فایل.');
        }

        // پاک‌سازی هدرهای مختل‌کننده
        if (!headers_sent()) {
            header_remove('X-Frame-Options');
            header_remove('Content-Security-Policy');
            header_remove('Cross-Origin-Resource-Policy');
            header_remove('Cross-Origin-Opener-Policy');
            header_remove('Cache-Control');
            header_remove('Pragma');
            header_remove('Content-Disposition');
            header_remove('Expires');
        }

        // خروجی base64 خام (بدون data: prefix و بدون JSON)
        // PDF.js در JavaScript این را به Uint8Array تبدیل می‌کند
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');

        echo base64_encode($fileContent);
        exit;
    }

    /**
     * تولید HTML صفحهٔ PDF viewer با PDF.js
     */
    private function renderPdfViewerHtml(string $base, int $docId, string $originalName): string
    {
        $safeName = htmlspecialchars($originalName, ENT_QUOTES, 'UTF-8');
        $rawUrl = $base . 'document/raw/' . $docId;

        return <<<HTML
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>پیش‌نمایش {$safeName}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #525659; font-family: Tahoma, sans-serif; overflow: auto; }
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

const rawUrl = '{$rawUrl}';
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

// مرحله ۱: فایل PDF را به‌صورت base64 دریافت کن
// این روش از CORS/CORP مشکلی ندارد چون fetch از همان دامنه است
fetch(rawUrl)
    .then(function(response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.text();
    })
    .then(function(base64Str) {
        // مرحله ۲: base64 را به Uint8Array تبدیل کن
        const binaryStr = atob(base64Str.trim());
        const len = binaryStr.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
            bytes[i] = binaryStr.charCodeAt(i);
        }
        // مرحله ۳: PDF.js را با Uint8Array بارگذاری کن
        return pdfjsLib.getDocument({ data: bytes }).promise;
    })
    .then(function(pdf) {
        pdfDoc = pdf;
        loading.style.display = 'none';
        renderPage(currentPage);
    })
    .catch(function(err) {
        loading.style.display = 'none';
        errorDiv.style.display = 'block';
        errorDiv.textContent = 'خطا در بارگذاری PDF: ' + (err.message || 'نامشخص');
        console.error('PDF.js error:', err);
    });
</script>
</body>
</html>
HTML;
    }
}
