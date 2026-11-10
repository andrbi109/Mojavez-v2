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
     * این متد برای نمایش PDF در iframe استفاده می‌شود.
     */
    public function inline(int|string $id): void
    {
        $this->requireLogin();

        $document = is_numeric($id) ? $this->documentModel->findById((int)$id) : null;
        if (!$document) {
            $this->deny(404, 'فایل یافت نشد.');
        }

        $this->stream($document, 'inline');
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

        $safeName = preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', (string)$document['original_name']);

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
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
}
