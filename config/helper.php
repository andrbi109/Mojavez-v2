<?php
/**
 * توابع کمکی تاریخ شمسی (با استفاده از jdf)
 */

if (!function_exists('persianDate')) {
    function persianDate(string $gregorianDate): string
    {
        if (empty($gregorianDate) || $gregorianDate === '0000-00-00' || $gregorianDate === '0000-00-00 00:00:00') {
            return '—';
        }
        // جدا کردن بخش تاریخ از ساعت (در صورت وجود)
        $datePart = explode(' ', $gregorianDate)[0];
        list($year, $month, $day) = explode('-', $datePart);
        list($jy, $jm, $jd) = gregorian_to_jalali((int)$year, (int)$month, (int)$day);
        return $jy . '/' . str_pad($jm, 2, '0', STR_PAD_LEFT) . '/' . str_pad($jd, 2, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('currentPersianYear')) {
    function currentPersianYear(): int
    {
        list($y, $m, $d) = gregorian_to_jalali((int)date('Y'), (int)date('n'), (int)date('j'));
        return $y;
    }
}

/**
 * تبدیل تاریخ شمسی به میلادی (رشته Y-m-d)
 */
if (!function_exists('persian_to_gregorian')) {
    function persian_to_gregorian(int $year, int $month, int $day): string
    {
        $result = jalali_to_gregorian($year, $month, $day);
        return sprintf('%04d-%02d-%02d', $result[0], $result[1], $result[2]);
    }
}

/**
 * تبدیل تاریخ میلادی (Y-m-d) به آرایه شمسی [year, month, day]
 */
if (!function_exists('gregorian_to_jalali_array')) {
    function gregorian_to_jalali_array(string $gregorianDate): array
    {
        $parts = explode('-', $gregorianDate);
        if (count($parts) !== 3) {
            return ['year' => 0, 'month' => 0, 'day' => 0];
        }
        $gy = (int)$parts[0];
        $gm = (int)$parts[1];
        $gd = (int)$parts[2];
        $result = gregorian_to_jalali($gy, $gm, $gd);
        return ['year' => $result[0], 'month' => $result[1], 'day' => $result[2]];
    }
}

/**
 * بررسی صحت تاریخ شمسی
 */
if (!function_exists('is_valid_persian_date')) {
    function is_valid_persian_date(int $year, int $month, int $day): bool
    {
        return jcheckdate($month, $day, $year);
    }
}
/* =====================================================
   توابع سراسری تنظیمات و برندینگ سامانه
   ===================================================== */

/**
 * دریافت یک تنظیم سامانه از جدول settings (با کش درون‌درخواستی)
 * در دسترس همهٔ نماها و کنترلرها.
 */
if (!function_exists('setting')) {
    function setting(string $key, string $default = ''): string
    {
        static $setting = null;
        if ($setting === null) {
            $setting = new Setting();
        }
        return $setting->get($key, $default);
    }
}

/**
 * لینک‌های شبکه‌های اجتماعی مدیر سامانه (ایتا، روبیکا، بله)
 * @return array<string, array{label:string, url:string, id:string}>
 */
if (!function_exists('social_links')) {
    function social_links(): array
    {
        $id = setting('owner_username', 'Derogaramin');
        $links = [];
        $eitaa = setting('social_eitaa', $id);
        $rubika = setting('social_rubika', $id);
        $bale = setting('social_bale', $id);
        if ($eitaa !== '')  $links['eitaa']  = ['label' => 'ایتا',  'url' => 'https://eitaa.com/' . $eitaa,  'id' => $eitaa];
        if ($rubika !== '') $links['rubika'] = ['label' => 'روبیکا', 'url' => 'https://rubika.ir/' . $rubika, 'id' => $rubika];
        if ($bale !== '')   $links['bale']   = ['label' => 'بله',   'url' => 'https://ble.ir/' . $bale,     'id' => $bale];
        return $links;
    }
}

/**
 * نام مالک/مدیر سامانه برای برندینگ در نماها
 */
if (!function_exists('owner_name')) {
    function owner_name(): string
    {
        return setting('owner_name', 'امین دروگر بزدی');
    }
}

/**
 * قالب پیام اطلاع‌رسانی متقاضی با جایگذاری نام و کد رهگیری
 */
if (!function_exists('build_notify_message')) {
    function build_notify_message(string $fullName, string $trackingCode): string
    {
        $template = setting('success_message_template',
            "باسلام\n{name} هستم .\nدر سامانه مدارک رو بارگذاری کردم و کد رهگیری {code} را اخذ نمودم .\nبا تشکر {name}");
        return str_replace(['{name}', '{code}', '{نام و نام خانوادگی متقاضی}', '{کد رهگیری}'],
            [$fullName, $trackingCode, $fullName, $trackingCode], $template);
    }
}

/* =====================================================
   توابع کمکی فایل و آپلود
   ===================================================== */

if (!function_exists('safe_file_extension')) {
    /**
     * استخراج پسوند فایل به‌صورت قابل اعتماد و امن.
     *
     * این تابع بر خلاف pathinfo() در موارد زیر رفتار صحیح دارد:
     *   - نام فایل فارسی/با کاراکترهای غیر ASCII (UTF-8)
     *   - نام فایل بدون پسوند (رشتهٔ خالی برمی‌گرداند)
     *   - نام فایل با چند نقطه (مثل «archive.tar.gz» — آخرین پسوند)
     *   - نام فایل با نقطه در ابتدا (مثل «.htaccess» — پسوند ندارد)
     *   - نام فایل با فاصله یا کاراکترهای کنترلی در انتها
     *   - نام فایل با کاراکترهای ویندوزی (مانند IMG_20240406_165048_478.jpg)
     *   - نام فایل با خط تیره در ابتدا (مانند -2147483648_-210002.jpg)
     *   - نام فایل با کاراکترهای غیر ASCII در بخش میانی
     *
     * الگوریتم:
     *   ۱. نرمال‌سازی encoding (تبدیل ویندوز-۱۲۵۶ → UTF-8 در صورت نیاز)
     *   ۲. حذف کاراکترهای کنترلی (nul, CR, LF, tab) و BOM
     *   ۳. حذف مسیر (basename)
     *   ۴. یافتن آخرین نقطه با strrpos
     *   ۵. استخراج پسوند و حذف کاراکترهای غیر ASCII
     *   ۶. تبدیل به حروف کوچک
     *
     * @param string $filename نام فایل (می‌تواند $_FILES['name'] باشد)
     * @return string پسوند به حروف کوچک، بدون نقطه (مثلاً 'jpg' یا '')
     */
    function safe_file_extension(string $filename): string
    {
        // نرمال‌سازی: حذف BOM، trim فاصله‌ها از ابتدا و انتها
        $filename = trim($filename);
        if ($filename === '') {
            return '';
        }

        // اگر نام فایل از encoding ویندوزی باشد (مثلاً cp1256 برای فارسی یا ascii mangled)
        if (!mb_check_encoding($filename, 'UTF-8')) {
            $converted = @iconv('Windows-1256', 'UTF-8//IGNORE', $filename);
            if ($converted !== false && $converted !== '') {
                $filename = $converted;
            }
        }

        // حذف BOM (UTF-8 BOM در ابتدای رشته)
        if (substr($filename, 0, 3) === "\xEF\xBB\xBF") {
            $filename = substr($filename, 3);
        }

        // حذف کاراکترهای کنترلی (NUL byte که می‌تواند برای Path Traversal استفاده شود)
        $filename = str_replace(["\0", "\r", "\n", "\t", "\x0B", "\x1A"], '', $filename);

        // trim مجدد پس از حذف کاراکترهای کنترلی
        $filename = trim($filename);

        if ($filename === '') {
            return '';
        }

        // گرفتن basename — حذف مسیر در صورت وجود (مانند "C:\Users\..." یا "/tmp/...")
        // basename در PHP ممکن است برای مسیرهای ویندوز با کاراکترهای خاص مشکل داشته باشد
        // بنابراین ابتدا/بک‌اسلش را نرمال‌سازی می‌کنیم
        $filename = str_replace('\\', '/', $filename);
        // حذف مسیر: فقط آخر بخش پس از آخرین /
        $lastSlash = strrpos($filename, '/');
        if ($lastSlash !== false) {
            $filename = substr($filename, $lastSlash + 1);
        }

        if ($filename === '' || $filename === '.') {
            return '';
        }

        // پیدا کردن آخرین نقطه
        $lastDot = strrpos($filename, '.');
        if ($lastDot === false || $lastDot === 0) {
            // نقطه نیست یا فایل با نقطه شروع شده (مثل .htaccess — پسوند ندارد)
            return '';
        }

        // استخراج پسوند
        $ext = substr($filename, $lastDot + 1);

        // محدودیت طول پسوند (بیش از ۲۰ کاراکتر غیرمعقول است)
        if (strlen($ext) > 20) {
            $ext = substr($ext, 0, 20);
        }

        // حذف کاراکترهای غیر ASCII (پسوند فقط باید ASCII باشد)
        // این کار همچنین کاراکترهای کنترلی احتمالی در پسوند را پاک می‌کند
        $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);

        if ($ext === '' || $ext === null) {
            return '';
        }

        return mb_strtolower($ext, 'UTF-8');
    }
}

if (!function_exists('sanitize_upload_filename')) {
    /**
     * نرمال‌سازی و پاک‌سازی نام فایل آپلودشده برای نمایش امن.
     *
     * - حذف BOM و کاراکترهای کنترلی
     * - تبدیل encoding ویندوزی به UTF-8
     * - trim فاصله‌ها
     * - حذف مسیر (basename)
     *
     * @param string $filename نام فایل خام از $_FILES
     * @return string نام فایل پاک‌سازی‌شده
     */
    function sanitize_upload_filename(string $filename): string
    {
        $filename = trim($filename);
        if ($filename === '') {
            return '';
        }
        if (!mb_check_encoding($filename, 'UTF-8')) {
            $converted = @iconv('Windows-1256', 'UTF-8//IGNORE', $filename);
            if ($converted !== false && $converted !== '') {
                $filename = $converted;
            }
        }
        // حذف BOM
        if (substr($filename, 0, 3) === "\xEF\xBB\xBF") {
            $filename = substr($filename, 3);
        }
        // حذف کاراکترهای کنترلی
        $filename = str_replace(["\0", "\r", "\n", "\t", "\x0B", "\x1A"], '', $filename);
        $filename = trim($filename);

        // نرمال‌سازی جداکننده‌های مسیر و گرفتن basename
        $filename = str_replace('\\', '/', $filename);
        $lastSlash = strrpos($filename, '/');
        if ($lastSlash !== false) {
            $filename = substr($filename, $lastSlash + 1);
        }

        return $filename === '.' ? '' : $filename;
    }
}

if (!function_exists('detect_extension_from_mime')) {
    /**
     * استخراج پسوند مورد انتظار از روی MIME type واقعی فایل.
     *
     * این تابع به‌عنوان fallback استفاده می‌شود وقتی نام فایل خالی است یا
     * پسوند قابل استخراج نیست. در این حالت، MIME واقعی محتوای فایل را بررسی می‌کنیم
     * و پسوند متناظر را برمی‌گردانیم.
     *
     * @param string $tmpPath مسیر فایل موقت آپلودشده
     * @return string|null پسوند پیشنهادی (مانند 'jpg') یا null اگر قابل تشخیص نباشد
     */
    function detect_extension_from_mime(string $tmpPath): ?string
    {
        if (!is_file($tmpPath) || !is_readable($tmpPath)) {
            return null;
        }

        // استفاده از finfo برای تشخیص MIME واقعی
        try {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string)$finfo->file($tmpPath);
        } catch (\Throwable $e) {
            return null;
        }

        if ($mime === '' || $mime === 'application/octet-stream') {
            // تلاش با FILEINFO_MIME برای اطلاعات بیشتر
            try {
                $finfo2 = new finfo(FILEINFO_MIME);
                $mimeRaw = (string)(new finfo(FILEINFO_MIME))->file($tmpPath);
                // الگوی "image/jpeg; charset=binary" → فقط بخش اول
                $mime = trim(explode(';', $mimeRaw)[0]);
            } catch (\Throwable $e) {
                return null;
            }
        }

        // نگاشت MIME به پسوندهای مجاز
        $mimeToExt = [
            'image/jpeg' => 'jpg',      // JPEG استاندارد
            'image/jpg' => 'jpg',       // برخی مرورگرها
            'image/pjpeg' => 'jpg',     // Progressive JPEG
            'image/png' => 'png',
            'image/x-png' => 'png',     // برخی مرورگرهای قدیمی
            'application/pdf' => 'pdf',
            'image/webp' => null,       // پشتیبانی نمی‌شود
            'image/gif' => null,        // پشتیبانی نمی‌شود
            'image/bmp' => null,        // پشتیبانی نمی‌شود
        ];

        return $mimeToExt[$mime] ?? null;
    }
}

if (!function_exists('validate_uploaded_file')) {
    /**
     * اعتبارسنجی کامل یک فایل آپلودشده با رویکرد چندلایه‌ای.
     *
     * این تابع مرکزی، تمام بررسی‌های امنیتی لازم را روی یک فایل آپلودشده انجام می‌دهد:
     *
     *   ۱. بررسی خطای آپلود (UPLOAD_ERR_OK)
     *   ۲. استخراج پسوند از نام فایل (با safe_file_extension)
     *   ۳. اگر نام خالی بود یا پسوند قابل استخراج نبود → استفاده از detect_extension_from_mime
     *      به‌عنوان fallback (تشخیص از روی محتوای واقعی فایل)
     *   ۴. بررسی حجم فایل
     *   ۵. بررسی MIME واقعی با finfo (نه فقط پسوند)
     *   ۶. تطبیق پسوند با MIME (مثلاً jpg با image/jpeg)
     *
     * این رویکرد تضمین می‌کند:
     *   - فایل‌هایی با نام‌های عجیب (مانند -2147483648_-210002.jpg) پذیرفته شوند
     *   - فایل‌هایی با نام‌های فارسی پذیرفته شوند
     *   - فایل‌هایی که نامشان در PHP خالی می‌شود (به دلیل encoding) باز هم پذیرفته شوند
     *   - اما فایل‌های مخرب (مثل PHP با پسوند jpg) رد شوند
     *
     * @param array $file ورودی از $_FILES (با کلیدهای name, tmp_name, size, error)
     * @param int $maxSizeBytes حداکثر حجم مجاز (پیش‌فرض از MAX_UPLOAD_SIZE)
     * @return array {
     *   valid: bool,           آیا فایل قابل پذیرش است؟
     *   ext: string,           پسوند نهایی (تأیید شده)
     *   mime: string,          MIME واقعی
     *   safe_name: string,     نام امن برای نمایش
     *   error: ?string,        پیام خطا (در صورت رد)
     *   reason: string,        دلیل رد (برای لاگ)
     * }
     */
    function validate_uploaded_file(array $file, ?int $maxSizeBytes = null): array
    {
        $maxSize = $maxSizeBytes ?? (defined('MAX_UPLOAD_SIZE') ? MAX_UPLOAD_SIZE : 2 * 1024 * 1024);
        $allowedExts = defined('ALLOWED_EXTENSIONS') ? ALLOWED_EXTENSIONS : ['jpg', 'jpeg', 'png', 'pdf'];
        $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];

        $result = [
            'valid' => false,
            'ext' => '',
            'mime' => '',
            'safe_name' => '',
            'error' => null,
            'reason' => '',
        ];

        // ۱. بررسی خطای آپلود
        $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'حجم فایل بیش از حد مجاز سرور (upload_max_filesize) است.',
                UPLOAD_ERR_FORM_SIZE => 'حجم فایل بیش از حد مجاز فرم است.',
                UPLOAD_ERR_PARTIAL => 'فایل به‌طور ناقص آپلود شد.',
                UPLOAD_ERR_NO_FILE => 'هیچ فایلی ارسال نشده است.',
                UPLOAD_ERR_NO_TMP_DIR => 'پوشهٔ موقت سرور موجود نیست.',
                UPLOAD_ERR_CANT_WRITE => 'سرور نمی‌تواند فایل را روی دیسک بنویسد.',
                UPLOAD_ERR_EXTENSION => 'افزونهٔ PHP این آپلود را مسدود کرد.',
            ];
            $result['error'] = $errorMessages[$uploadError] ?? 'خطای ناشناخته در آپلود فایل.';
            $result['reason'] = 'upload_error_' . $uploadError;
            return $result;
        }

        // ۲. نرمال‌سازی نام فایل
        $rawName = (string)($file['name'] ?? '');
        $safeName = sanitize_upload_filename($rawName);
        $result['safe_name'] = $safeName ?: '(نام نامعتبر)';

        // ۳. استخراج پسوند از نام فایل
        $ext = safe_file_extension($rawName);

        // ۴. اگر پسوند قابل استخراج نبود، از MIME واقعی استفاده کن (fallback)
        $tmpPath = (string)($file['tmp_name'] ?? '');
        $realMime = '';
        if (is_file($tmpPath) && is_readable($tmpPath)) {
            try {
                $realMime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($tmpPath);
            } catch (\Throwable $e) {
                $realMime = '';
            }
        }
        $result['mime'] = $realMime;

        if ($ext === '' || !in_array($ext, $allowedExts, true)) {
            // پسوند از نام قابل استخراج نیست یا مجاز نیست
            // تلاش برای تشخیص از روی MIME واقعی
            $detectedExt = detect_extension_from_mime($tmpPath);
            if ($detectedExt !== null && in_array($detectedExt, $allowedExts, true)) {
                // MIME واقعی تأیید می‌کند که فایل تصویر/PDF است
                $ext = $detectedExt;
            } else {
                // نه نام و نه MIME مجاز نیست
                $result['error'] = 'پسوند فایل «' . $result['safe_name'] . '» مجاز نیست. فقط '
                    . implode(', ', $allowedExts) . ' مجاز است.';
                $result['reason'] = 'invalid_extension_and_mime';
                return $result;
            }
        }

        $result['ext'] = $ext;

        // ۵. بررسی حجم
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            $result['error'] = 'حجم فایل «' . $result['safe_name'] . '» قابل تشخیص نیست یا صفر است.';
            $result['reason'] = 'zero_size';
            return $result;
        }
        if ($size > $maxSize) {
            $result['error'] = 'حجم فایل «' . $result['safe_name'] . '» بیش از حد مجاز ('
                . number_format($maxSize / 1024 / 1024, 1) . ' مگابایت) است.';
            $result['reason'] = 'size_exceeded';
            return $result;
        }

        // ۶. بررسی MIME واقعی (نه فقط پسوند)
        if (!in_array($realMime, $allowedMimes, true)) {
            // تلاش مجدد با FILEINFO_MIME (اطلاعات دقیق‌تر)
            try {
                $mimeRaw = (string)(new finfo(FILEINFO_MIME))->file($tmpPath);
                $realMime = trim(explode(';', $mimeRaw)[0]);
            } catch (\Throwable $e) {}

            if (!in_array($realMime, $allowedMimes, true)) {
                $result['error'] = 'محتوای فایل «' . $result['safe_name'] . '» با پسوندهای مجاز '
                    . '(تصویر JPG/PNG یا PDF) همخوانی ندارد. MIME تشخیص‌داده‌شده: ' . $realMime;
                $result['reason'] = 'mime_mismatch';
                return $result;
            }
        }

        // ۷. تطبیق پسوند با MIME (برای امنیت مضاعف)
        $extToMime = [
            'jpg' => ['image/jpeg', 'image/jpg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/jpg', 'image/pjpeg'],
            'png' => ['image/png', 'image/x-png'],
            'pdf' => ['application/pdf'],
        ];
        // اگر پسوند استخراج‌شده از نام بود، بررسی تطبیق
        if (isset($extToMime[$ext]) && !in_array($realMime, $extToMime[$ext], true)) {
            // عدم تطبیق مجاز نیست چون ممکن است کاربر پسوند را دستی تغییر داده باشد
            // اما اگر پسوند از MIME تشخیص داده شده باشد، تطبیق خودکار است
            $nameExt = safe_file_extension($rawName);
            if ($nameExt !== '' && $nameExt === $ext) {
                $result['error'] = 'پسوند فایل «' . $result['safe_name'] . '» با محتوای واقعی همخوانی ندارد. '
                    . 'احتمالاً پسوند فایل تغییر داده شده است.';
                $result['reason'] = 'ext_mime_mismatch';
                return $result;
            }
            // در غیر این صورت، ext از MIME آمده و تطبیق خودکار است
        }

        $result['valid'] = true;
        $result['reason'] = 'ok';
        return $result;
    }
}

/* =====================================================
   سیستم آیکون SVG درون‌خطی — بدون فونت آیکون خارجی
   همهٔ آیکون‌ها stroke-based به سبک Feather با viewBox 24x24
   ===================================================== */

if (!function_exists('icon_paths')) {
    /**
     * دیکشنری مسیرهای SVG آیکون‌ها
     */
    function icon_paths(): array
    {
        static $paths = null;
        if ($paths !== null) return $paths;
        $paths = [
            'home'       => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/>',
            'dashboard'  => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
            'folder'     => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
            'list'       => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>',
            'mail'       => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
            'users'      => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8"/><path d="M17.5 14.3A6.5 6.5 0 0 1 21.5 20"/>',
            'user'       => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5a7.5 7.5 0 0 1 15 0"/>',
            'settings'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .32 1.77l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.6 1.6 0 0 0-1.77-.32 1.6 1.6 0 0 0-1 1.46V21a2 2 0 1 1-4 0v-.1A1.6 1.6 0 0 0 9.1 19.4a1.6 1.6 0 0 0-1.77.32l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.6 1.6 0 0 0 4.82 15a1.6 1.6 0 0 0-1.46-1H3a2 2 0 1 1 0-4h.1A1.6 1.6 0 0 0 4.6 9.1a1.6 1.6 0 0 0-.32-1.77l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.6 1.6 0 0 0 9 4.82h.08A1.6 1.6 0 0 0 10 3.36V3a2 2 0 1 1 4 0v.1a1.6 1.6 0 0 0 1 1.46 1.6 1.6 0 0 0 1.77-.32l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.6 1.6 0 0 0 19.4 9v.08a1.6 1.6 0 0 0 1.46 1H21a2 2 0 1 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1z"/>',
            'form'       => '<rect x="4" y="3" width="16" height="18" rx="2"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="12" y2="16"/>',
            'grid'       => '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/>',
            'chart'      => '<line x1="4" y1="20" x2="20" y2="20"/><rect x="6" y="10" width="3" height="8"/><rect x="11" y="6" width="3" height="12"/><rect x="16" y="13" width="3" height="5"/>',
            'download'   => '<path d="M12 3v12"/><path d="m7 11 5 5 5-5"/><path d="M4 21h16"/>',
            'logout'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><line x1="21" y1="12" x2="9" y2="12"/>',
            'search'     => '<circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="21" y2="21"/>',
            'print'      => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="1.5"/><path d="M6 14h12v7H6z"/>',
            'copy'       => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/>',
            'check'      => '<polyline points="4 12.5 9.5 18 20 6.5"/>',
            'check-circle' => '<circle cx="12" cy="12" r="9"/><polyline points="8 12.2 11 15.2 16.5 9.5"/>',
            'x'          => '<line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/>',
            'x-circle'   => '<circle cx="12" cy="12" r="9"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/>',
            'alert'      => '<path d="M10.3 3.9 1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><circle cx="12" cy="17" r="0.6" fill="currentColor"/>',
            'clock'      => '<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 14"/>',
            'star'       => '<polygon points="12 2.5 14.9 8.6 21.5 9.5 16.7 14.1 17.9 20.7 12 17.5 6.1 20.7 7.3 14.1 2.5 9.5 9.1 8.6"/>',
            'edit'       => '<path d="M17 3.5a2.1 2.1 0 0 1 3 3L7.5 19 3 20.5 4.5 16z"/>',
            'trash'      => '<polyline points="3 6 21 6"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>',
            'eye'        => '<path d="M1.5 12S5 5.5 12 5.5 22.5 12 22.5 12 19 18.5 12 18.5 1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/>',
            'plus'       => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
            'upload'     => '<path d="M12 21V9"/><path d="m7 13 5-5 5 5"/><path d="M4 3h16"/>',
            'image'      => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
            'file-text'  => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><polyline points="14 3 14 8 19 8"/><line x1="8.5" y1="13" x2="15.5" y2="13"/><line x1="8.5" y1="17" x2="13" y2="17"/>',
            'map-pin'    => '<path d="M20 10.5c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0z"/><circle cx="12" cy="10.2" r="2.8"/>',
            'phone'      => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.13.96.36 1.9.7 2.8a2 2 0 0 1-.45 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.45c.9.34 1.84.57 2.8.7A2 2 0 0 1 22 16.9z"/>',
            'id-card'    => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><circle cx="8.5" cy="11" r="2.2"/><path d="M5 16.4a4 4 0 0 1 7 0"/><line x1="14.5" y1="10" x2="18.5" y2="10"/><line x1="14.5" y1="13.5" x2="18.5" y2="13.5"/>',
            'shield'     => '<path d="M12 22s8-3.5 8-10V5.5L12 2.5 4 5.5V12c0 6.5 8 10 8 10z"/><polyline points="9 11.5 11.2 13.8 15.2 9.6"/>',
            'sun'        => '<circle cx="12" cy="12" r="4.5"/><line x1="12" y1="1.5" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22.5"/><line x1="4.2" y1="4.2" x2="6" y2="6"/><line x1="18" y1="18" x2="19.8" y2="19.8"/><line x1="1.5" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22.5" y2="12"/><line x1="4.2" y1="19.8" x2="6" y2="18"/><line x1="18" y1="6" x2="19.8" y2="4.2"/>',
            'moon'       => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
            'menu'       => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>',
            'chevron-down' => '<polyline points="6 9 12 15 18 9"/>',
            'chevron-left' => '<polyline points="15 6 9 12 15 18"/>',
            'chevron-right' => '<polyline points="9 6 15 12 9 18"/>',
            'lock'       => '<rect x="4" y="10.5" width="16" height="10.5" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/>',
            'key'        => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.8 12.2 8-8"/><path d="m16 4 3 3"/><path d="m19 7 1.8-1.8"/>',
            'award'      => '<circle cx="12" cy="9" r="6"/><path d="m8.2 14-1.7 7.5L12 18.8l5.5 2.7L15.8 14"/>',
            'vase'       => '<path d="M9 3h6"/><path d="M10 3c0 3-4 4.5-4 8.5A7.5 7.5 0 0 0 12 21a7.5 7.5 0 0 0 6-9.5C18 7.5 14 6 14 3"/><path d="M7.5 13.5h9"/>',
            'package'    => '<path d="m12 2 9 5v10l-9 5-9-5V7z"/><path d="m3 7 9 5 9-5"/><path d="M12 12v10"/>',
            'send'       => '<path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/>',
            'info'       => '<circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16.5"/><circle cx="12" cy="7.8" r="0.6" fill="currentColor"/>',
            'book'       => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>',
            'arrow-up'   => '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5.5 11.5 12 5 18.5 11.5"/>',
            'arrow-down' => '<line x1="12" y1="5" x2="12" y2="19"/><polyline points="5.5 12.5 12 19 18.5 12.5"/>',
            'save'       => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>',
            'task'       => '<rect x="3" y="4" width="18" height="16" rx="2"/><polyline points="8 11.5 10.5 14 16 8.5"/>',
            'money'      => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.8"/><line x1="6" y1="12" x2="6.5" y2="12"/><line x1="17.5" y1="12" x2="18" y2="12"/>',
            'megaphone'  => '<path d="m3 11 15-6v14L3 13z"/><path d="M3 11v2a2 2 0 0 0 2 2h1"/><path d="M18 9.5a2.5 2.5 0 0 1 0 5"/><path d="m7 14.5 1 6h2.5l-.8-5"/>',
            'wallet'     => '<path d="M21 12V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3"/><path d="M16 12h5v3h-5a1.5 1.5 0 0 1 0-3z"/>',
            'check-square' => '<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
            'trending-up' => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
            'shopping-cart' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
        ];
        return $paths;
    }
}

if (!function_exists('icon')) {
    /**
     * خروجی یک آیکون SVG درون‌خطی
     * @param string $name  نام آیکون از دیکشنری
     * @param string $class کلاس‌های اضافی (اندازه/رنگ/فاصله)
     * @param int    $size  اندازه به پیکسل
     */
    function icon(string $name, string $class = '', int $size = 20): string
    {
        $paths = icon_paths();
        $body = $paths[$name] ?? $paths['info'];
        $cls = trim('svg-icon ' . $class);
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" '
            . 'fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" '
            . 'class="' . htmlspecialchars($cls) . '" aria-hidden="true">' . $body . '</svg>';
    }
}
