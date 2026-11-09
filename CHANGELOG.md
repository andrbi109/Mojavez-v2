# تاریخچهٔ تغییرات

این پروژه از [Semantic Versioning](https://semver.org/) پیروی می‌کند.
قالب: `MAJOR.MINOR.PATCH`
- **MAJOR:** تغییرات ناسازگار API
- **MINOR:** افزودن قابلیت‌های جدید به‌صورت سازگار با عقب
- **PATCH:** اصلاحات باگ و بهبودهای کوچک

---

## [0.6.2] — ۲۰۲۶-۱۱-۱۰ — فایل نصب واحد + حساب مدیر پیش‌فرض

### 🎯 هدف
تجمیع همهٔ فایل‌های SQL در یک فایل واحد برای نصب تازه بدون نیاز به
اجرای مهاجرت‌ها. همچنین افزودن حساب مدیر پیش‌فرض برای راه‌اندازی سریع.

### ✨ قابلیت‌های جدید

#### ۱) فایل نصب واحد (`database/install.sql`)
- **یک فایل واحد** شامل همهٔ جداول، داده‌های اولیه و محدودیت‌ها
- **بدون نیاز به مهاجرت‌ها** — فقط همین فایل را اجرا کنید
- **Idempotent** — اجرای چندباره خطا نمی‌دهد (`CREATE TABLE IF NOT EXISTS`)
- **سازگار با MySQL 5.7+، MariaDB 10.3+**
- شامل ۲۹ جدول، ۴۹ INSERT، ۲۲ ALTER TABLE
- حذف دستورات مشکل‌ساز: `SET @`, `PREPARE`, `EXECUTE`, `DEALLOCATE`
- حذف `USE` های اضافی (فقط یک `USE` در ابتدا)
- حذف `SELECT` های تشخیصی

#### ۲) فایل ساخت حساب مدیر (`database/seed-admin.sql`)
- ساخت حساب `admin` با رمز `Mojavez@2026` (هش bcrypt با cost=10)
- Idempotent — اجرای مجدد حساب قبلی را حذف و دوباره می‌سازد
- هشدار واضح برای تغییر رمز پس از اولین ورود

#### ۳) راهنمای جامع پایگاه داده (`database/README.md`)
- توضیح فایل‌های مختلف (install.sql، schema.sql، seed-admin.sql، migrations/)
- نحوهٔ نصب در محیط‌های مختلف (سرور، هاست اشتراکی، Docker)
- نحوهٔ پشتیبان‌گیری و بازیابی
- رفع مشکلات رایج

### 🔧 تغییرات فنی

#### `deploy/shared-hosting/install.php`
- اکنون از `database/install.sql` استفاده می‌کند (به‌جای `schema.sql`)
- Fallback به `schema.sql` اگر `install.sql` موجود نباشد

#### `deploy/shared-hosting/README.md`
- گام ۵ به‌روزرسانی شد: ساخت حساب مدیر با `seed-admin.sql`
- افزودن «روش جایگزین: نصب دستی با فایل SQL»

### 📦 فایل‌های جدید
- `database/install.sql` — فایل نصب واحد (۷۳ کیلوبایت، ۱۲۷۹ خط)
- `database/seed-admin.sql` — ساخت حساب مدیر پیش‌فرض
- `database/README.md` — راهنمای جامع پایگاه داده

### 📝 فایل‌های تغییر یافته
- `deploy/shared-hosting/install.php` — استفاده از install.sql
- `deploy/shared-hosting/README.md` — به‌روزرسانی گام ۵
- `VERSION` → `0.6.2`
- `config/config.php` → `APP_VERSION` → `0.6.2`

### 📊 آمار فایل `install.sql`
- ۲۹ `CREATE TABLE IF NOT EXISTS`
- ۴۹ `INSERT INTO` (با `ON DUPLICATE KEY UPDATE`)
- ۲۲ `ALTER TABLE` (برای افزودن محدودیت‌ها و ستون‌ها)
- ۱۰۱ دستور کل
- ۸۰۷ پرانتز باز/بسته (متعادل)
- ۰ دستور `SET @` / `PREPARE` / `EXECUTE` (حذف شدند)

---

## [0.6.1] — ۲۰۲۶-۱۱-۰۹ — استقرار روی هاست اشتراکی (نت افراز)

### 🎯 هدف
آماده‌سازی پروژه برای استقرار روی هاست اشتراکی با DirectAdmin
(مثل سرویس برنزی نت افراز با Nginx + Apache).

### ✨ قابلیت‌های جدید

#### ۱) راهنمای جامع استقرار
فایل `deploy/shared-hosting/README.md` با راهنمای گام‌به‌گام فارسی:
- مشخصات سرویس برنزی نت افراز و تطبیق با نیازهای پروژه
- ساخت دیتابیس در DirectAdmin
- فعال‌سازی PHP 8.x و SSL رایگان
- تنظیم cron job برای لغو خودکار
- چک‌لیست نهایی استقرار
- رفع مشکلات رایج (خطای ۵۰۰، صفحهٔ سفید، آپلود ناموفق)
- پشتیبان‌گیری و ارتقا

#### ۲) بررسی‌کنندهٔ پیش‌نیازها (`check.php`)
فایل `deploy/shared-hosting/check.php`:
- بررسی نسخهٔ PHP
- بررسی پسوندهای ضروری (PDO, mbstring, openssl, json, ...)
- بررسی محدودیت‌های آپلود (upload_max_filesize, post_max_size, memory_limit)
- بررسی mod_rewrite, mod_headers, mod_deflate, mod_expires
- بررسی HTTPS/SSL
- بررسی قابلیت نوشتن پوشهٔ uploads
- تست اتصال به MySQL (با فرم تعاملی)

#### ۳) نصب‌کنندهٔ وب (`install.php`)
فایل `deploy/shared-hosting/install.php`:
- فرم دو مرحله‌ای: اطلاعات دیتابیس → نصب schema
- ساخت خودکار فایل `.env` با مقادیر واقعی
- تولید خودکار `CRON_SECRET` (رمز ۶۴ کاراکتری)
- اجرای `schema.sql` با تقسیم‌کنندهٔ هوشمند SQL
- گزارش نهایی با تعداد دستورات موفق/skip/خطا

#### ۴) `.htaccess` سازگار با هاست اشتراکی
فایل `deploy/shared-hosting/.htaccess`:
- بهینه‌شده برای معماری Nginx + Apache (DirectAdmin)
- بدون `<LimitExcept>` (در برخی هاست‌ها نامعتبر است)
- بدون `ServerTokens/ServerSignature` (فقط در server config معتبرند)
- بدون `mod_php` (در Nginx+Apache معمولاً PHP-FPM است)
- همهٔ قواعد با `<IfModule>` محافظت می‌شوند

### 📦 فایل‌های جدید
- `deploy/shared-hosting/README.md` — راهنمای استقرار
- `deploy/shared-hosting/check.php` — بررسی‌کنندهٔ پیش‌نیازها
- `deploy/shared-hosting/install.php` — نصب‌کنندهٔ وب
- `deploy/shared-hosting/.htaccess` — `.htaccess` هاست اشتراکی

### 📝 فایل‌های تغییر یافته
- `VERSION` → `0.6.1`
- `config/config.php` → `APP_VERSION` → `0.6.1`

---

## [0.6.0] — ۲۰۲۶-۱۱-۰۹ — سخت‌سازی امنیتی و حریم خصوصی برای سامانهٔ خصوصی

### 🎯 هدف
این سامانه خصوصی است — نیازی به شناسایی توسط موتورهای جستجو و خزنده‌های هوش مصنوعی ندارد.
این نسخه بر سه محور تمرکز دارد:
1. **امنیت** — گام اول
2. **بهینه‌سازی** — گام دوم
3. **سرعت و پایداری** — گام سوم

### 🔒 امنیت (گام اول)

#### ۱) مسدودسازی موتورهای جستجو و خزنده‌های هوش مصنوعی
این سامانه اکنون از سه لایه در برابر موتورهای جستجو و خزنده‌ها محافظت می‌شود:

- **`public/robots.txt`** — بلیک‌لیست جامع ربات‌ها (Googlebot, Bingbot, GPTBot, ChatGPT-User, CCBot, anthropic-ai, Claude-Web, cohere-ai, Google-Extended, PerplexityBot, Amazonbot, Bytespider, Diffbot, Meta-ExternalAgent, OAI-SearchBot, Applebot-Extended، و ربات‌های SEO مثل SemrushBot, AhrefsBot, MJ12bot, DotBot)
- **`.htaccess` و `index.php`** — هدر `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex` روی همهٔ پاسخ‌ها
- **مداخلهٔ سطح Apache/Nginx** — مسدودسازی User-Agent با کد ۴۱۰ (Gone) — قبل از رسیدن به PHP

#### ۲) متاتگ‌های noindex در همهٔ قالب‌ها
- `app/Views/layout.php` (قالب عمومی)
- `app/Views/admin_layout.php` (پنل مدیریت)
- `app/Views/user_layout.php` (پنل کاربری)
- `app/Views/layout_print.php` (صفحات چاپی)

هر چهار قالب اکنون `<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">` دارند.

#### ۳) سخت‌سازی نشست (Session Hardening)
- `session.use_strict_mode = On` — رد شناسه‌های نامعتبر (مقابله با Session Fixation)
- `session.use_only_cookies = On` — فقط کوکی (نه URL — جلوگیری از Session Leak در URL)
- `session.sid_bits_per_character = 6` و `session.sid_length = 48` — شناسه‌های غیرقابل حدس‌تر
- `session.cookie_httponly = On` و `session.cookie_samesite = Lax`
- تشخیص صحیح HTTPS با پشتیبانی از پروکسی معتبر (`X-Forwarded-Proto`)
- بازتولید خودکار شناسهٔ نشست هر ۳۰ دقیقه برای محدود کردن پنجرهٔ سوءاستفاده

#### ۴) هدرهای امنیتی جامع روی همهٔ پاسخ‌ها
- `Content-Security-Policy` کامل (default-src 'self'؛ object-src 'none'؛ base-uri 'self'؛ frame-ancestors 'self')
- `Cross-Origin-Opener-Policy: same-origin` — محافظت Spectre-class
- `Cross-Origin-Resource-Policy: same-site`
- `Permissions-Policy` — غیرفعال‌سازی `geolocation`, `microphone`, `camera`, `payment`, `usb`, `interest-cohort`
- `Cache-Control: no-store` پیش‌فرض برای صفحات پویا (جلوگیری از کش داده‌های حساس)
- `Strict-Transport-Security` (HSTS) روی HTTPS با `includeSubDomains; preload`

#### ۵) محدودیت آی‌پی (اختیاری)
- `IP_BLOCKLIST` در `.env` — لیست آی‌پی‌های مسدودشده (denylist عمومی)
- `ADMIN_IP_ALLOWLIST` در `.env` — فقط آی‌پی‌های فهرست‌شده به پنل مدیریت دسترسی دارند
- پشتیبانی از پروکسی معتبر با `X-Forwarded-For` (با اعتبارسنجی IP)

#### ۶) مسدودسازی فایل‌های مخفی و حساس
- `.` dotfiles (مثل `.env`, `.git`, `.htaccess`)
- پسوندهای حساس: `.env`, `.sql`, `.md`, `.sh`, `.py`, `.bak`, `.log`, `.lock`, `.json`, `.yaml`, `.ini`, `.conf`
- غیرفعال‌سازی اجرای PHP در فایل‌های آپلود (لایهٔ دوم)
- محدود کردن روش‌های HTTP: فقط `GET, POST, HEAD, OPTIONS` مجاز است

#### ۷) ناپدید کردن امضای سرور
- `ServerTokens Prod` و `ServerSignature Off` (Apache)
- `server_tokens off` (Nginx)
- `expose_php = Off` (PHP)
- نام فایل در پیام خطا — `basename()` فقط نام فایل را برمی‌گرداند (نه مسیر کامل)

### 🚀 بهینه‌سازی (گام دوم)

#### ۱) توصیه‌های PHP برای محیط تولید
فایل جدید `deploy/php/mojavez.production.ini`:
- **OPcache** فعال با ۱۲۸ مگابایت حافظه، `validate_timestamps=0` برای حداکثر سرعت
- **realpath_cache** — ۴ مگابایت برای شتاب‌دهی autoloader
- **zlib.output_compression** — فشرده‌سازی خودکار gzip سطح ۶
- **output_buffering** — ۴ کیلوبایت برای کاهش TTFB
- `memory_limit = 256M`, `max_execution_time = 60s`

#### ۲) کش‌گذاری هوشمند فایل‌های استاتیک
- کش طولانی‌مدت (`expires 1y` با `Cache-Control: public, immutable`) برای فایل‌های `.js`, `.css`, `.png`, `.jpg`, `.woff2`
- تابع جدید `asset_url()` در `helper.php` — افزودن پارامتر `?v={mtime}` برای کش‌باستینگ
- با هر بار تغییر فایل، مرورگر به‌صورت خودکار نسخهٔ جدید را بارگذاری می‌کند

### ⚡ سرعت و پایداری (گام سوم)

#### ۱) `/health` endpoint برای مانیتورینگ
- بدون احراز هویت — فقط بررسی سریع اتصال به DB
- `GET /health` → `{ "status": "healthy", "checks": { "db": "ok", "version": "0.6.0", "time": "..." } }`
- کد HTTP: ۲۰۰ (سالم) یا ۵۰۳ (degraded)
- بدون نشاندادن اطلاعات حساس — آماده برای استفاده در Uptime Robot، Zabbix، Kubernetes probes

#### ۲) مدیریت خطای قوی‌تر
- `set_exception_handler` اکنون برای درخواست‌های AJAX به جای HTML، JSON برمی‌گرداند
- لاگ خطا اکنون شامل زمینهٔ درخواست است (متد، مسیر، IP) برای دیباگ بهتر
- در محیط تولید (`APP_DEBUG=false`)، استک تریس نمایش داده نمی‌شود
- هدرهای امنیتی (`X-Robots-Tag`, `Cache-Control`) حتی در پاسخ خطا اعمال می‌شوند

#### ۳) اطمینان از `display_errors = Off` در تولید
- `ini_set('display_errors', APP_DEBUG ? '1' : '0')` — اگر `APP_DEBUG=false` باشد، خطاها هرگز به کاربر نمایش داده نمی‌شوند

### 📦 فایل‌های جدید
- `public/robots.txt` — بلیک‌لیست جامع ربات‌ها
- `deploy/php/mojavez.production.ini` — توصیه‌های PHP برای تولید

### 📝 فایل‌های تغییر یافته
- `public/.htaccess` — بازنویسی کامل با قواعد مسدودسازی، هدرها و خطاهای سفارشی
- `public/index.php` — هدرهای سراسری، `/health`، مدیریت خطای AJAX، IP blocklist
- `core/Session.php` — سخت‌سازی نشست با `use_strict_mode`, `sid_bits_per_character`, بازتولید دوره‌ای
- `app/Controllers/Controller.php` — `requireLogin()` اکنون ADMIN_IP_ALLOWLIST را بررسی می‌کند
- `config/config.php` — تعریف `APP_ENV`, `ERROR_LOG_PATH`, `ADMIN_IP_ALLOWLIST`, `IP_BLOCKLIST`
- `config/helper.php` — تابع جدید `asset_url()` برای کش‌باستینگ
- `app/Views/layout.php`, `admin_layout.php`, `user_layout.php`, `layout_print.php` — متاتگ‌های noindex
- `deploy/nginx/mojavez.conf` — بازنویسی کامل با مسدودسازی ربات، هدرها و صفحهٔ ۴۱۰
- `deploy/apache/mojavez.conf` — بازنویسی کامل با مسدودسازی ربات، هدرها و صفحهٔ ۴۱۰
- `.env.example` — متغیرهای جدید: `APP_ENV`, `ERROR_LOG_PATH`, `ADMIN_IP_ALLOWLIST`, `IP_BLOCKLIST`
- `VERSION` → `0.6.0`
- `config/config.php` → `APP_VERSION` → `0.6.0`

### ⚙️ نصب بر روی سرور تولید

۱) تنظیم `.env`:
```ini
APP_DEBUG=false
APP_ENV=production
ADMIN_IP_ALLOWLIST=192.168.1.10,192.168.1.20  # آی‌پی مدیران
ERROR_LOG_PATH=/var/log/php/mojavez-error.log
```

۲) اعمال توصیه‌های PHP:
```bash
sudo cp deploy/php/mojavez.production.ini /etc/php/8.2/fpm/conf.d/99-mojavez.ini
sudo systemctl reload php8.2-fpm
```

۳) راه‌اندازی پایش (اختیاری):
```
GET https://mojavez.example/health
```

۴) تست مسدودسازی ربات‌ها:
```bash
curl -I -A "Googlebot/2.1" https://mojavez.example/
# باید برگرداند: HTTP/1.1 410 Gone
```

---

## [0.5.6] — ۲۰۲۶-۱۱-۰۹ — اختصاصی‌سازی رشته‌های مجوز به هر سرویس

### ✨ قابلیت‌های جدید

#### ۱) اختصاصی‌سازی فهرست رشته‌ها به هر سرویس
تا پیش از این، فهرست رشته‌های مجوز (`license_titles`) مشترک میان همهٔ
سرویس‌ها بود — یک متقاضیِ «صنایع دستی» و یک متقاضیِ «مشاغل خانگی مستقل»
هر دو فهرست یکسانی را در فرم ثبت‌نام می‌دیدند.

اکنون هر سرویس فهرست دسته‌بندی‌ها و رشته‌های مختص به خود را دارد، مشابه
وضعیت‌ها در `service_types`. این طراحی پاک‌سازی فرم ثبت‌نام را ممکن می‌کند:

| سرویس | تعداد دسته‌ها | نمونهٔ دسته‌ها |
|---|---|---|
| ۱ — صنایع دستی | ۱۸ | بافته‌های داری، رودوزی‌های سنتی، صنایع چوبی، نگارگری و تذهیب |
| ۲ — مشاغل خانگی مستقل | ۱۰ | صنایع غذایی و آشامیدنی خانگی، خدمات خانگی، محصولات بهداشتی و آرایشی خانگی |
| ۳ — پشتیبان | ۱۰ | پشتیبانی فرآورده‌های غذایی، پشتیبانی صنایع دستی، پشتیبانی فروش و بازاریابی |

#### ۲) فیلتر سرویس در پنل مدیریت رشته‌ها
- نوار فیلتر در `/admin/license-titles` برای نمایش فقط رشته‌های یک سرویس
- هر کارت رشته، برچسب سرویس والد را نشان می‌دهد
- فرم افزودن/ویرایش اکنون فیلد «سرویس» اجباری دارد

#### ۳) بارگذاری پویای فهرست در فرم‌ها
- در فرم ثبت‌نام دستی مدیر (`/admin/register`): تغییر سرویس، فهرست رشته‌ها را از طریق AJAX به‌روز می‌کند
- در فرم ویرایش متقاضی (`/admin/applicant/{id}/edit`): همان رفتار
- در فرم ایمپورت دسته‌جمعی (`/admin/bulk-import`): همان رفتار
- در فرم ثبت‌نام عمومی (`/register/step/2`): فهرست از همان ابتدا بر اساس سرویس انتخاب‌شده فیلتر می‌شود
- در فرم درخواست بازبینی (`/tracking/revision/{code}`): فهرست بر اساس سرویس متقاضی فیلتر می‌شود

### 🔧 تغییرات فنی

#### ۱) پایگاه داده
- ستون `service_type_id` به جدول `license_titles` افزوده شد (مهاجرت idempotent)
- FOREIGN KEY به `service_types(id)` با `ON DELETE CASCADE`
- UNIQUE از روی `title` به `(service_type_id, title)` تغییر کرد — اکنون یک عنوان می‌تواند در چند سرویس تکرار شود
- ایندکس جدید `idx_active_service_sort` برای کارایی پرس‌وجوهای سرویس-محور
- بذر اختصاصی برای سرویس ۲ (۴۵ رشته در ۱۰ دسته) و سرویس ۳ (۳۴ رشته در ۱۰ دسته)

#### ۲) مدل `LicenseTitle`
- متد جدید `groupedActiveByService(?int $serviceTypeId)` — رشته‌های فعال یک سرویس را گروه‌بندی‌شده بازمی‌گرداند
- متد `getAll(?int $serviceTypeId = null)` اکنون سرویس را هم JOIN می‌کند تا `service_title` هم بازگردانده شود
- متد جدید `isTitleUniqueInService()` برای بررسی یکتایی در دامنهٔ سرویس
- متد `create` و `update` اکنون `service_type_id` را هم ذخیره می‌کنند
- متد `groupedActive()` به‌عنوان deprecated نگه‌داشته شد و به `groupedActiveByService(null)` ارجاع می‌دهد

#### ۳) کنترلر `LicenseTitleController`
- `index()` اکنون فیلتر `?service=N` را قبول می‌کند و فقط رشته‌های همان سرویس را نمایش می‌دهد
- `save()` اکنون `service_type_id` را از فرم می‌گیرد و یکتایی را در دامنهٔ سرویس بررسی می‌کند
- متد جدید `byService(int $id)` — API JSON برای بارگذاری پویای فهرست رشته‌ها در فرم‌ها
- مسیر جدید: `GET /admin/license-titles/by-service/{id}`

#### ۴) مسیر PHP
- `app/Models/LicenseTitle.php` — بازنویسی کامل
- `app/Controllers/LicenseTitleController.php` — بازنویسی کامل
- `app/Controllers/RegistrationController.php` — استفاده از `groupedActiveByService(selectedServiceId)`
- `app/Controllers/AdminController.php` — `registerForm` و `editForm` بر اساس سرویس فیلتر می‌کنند
- `app/Controllers/BulkImportController.php` — `index` و `preview` بر اساس سرویس فیلتر می‌کنند
- `app/Controllers/RevisionController.php` — فهرست بر اساس سرویس متقاضی فیلتر می‌شود
- `app/Views/admin/license_titles.php` — بازنویسی کامل با نوار فیلتر و برچسب سرویس
- `app/Views/admin/register.php` — افزودن انتخابگر سرویس و JS پویا
- `app/Views/admin/edit_application.php` — افزودن ID و JS پویا برای انتخابگر سرویس
- `app/Views/admin/bulk_import.php` — افزودن JS پویا
- `public/index.php` — مسیر جدید `by-service/{id}`

### 📦 مهاجرت

برای پایگاه‌های داده موجود، فایل زیر را در phpMyAdmin ایمپورت کنید:
```
database/migrations/20251113_license_titles_service_specific.sql
```

این مهاجرت:
- ستون `service_type_id` را اضافه می‌کند
- تمام رشته‌های موجود را به سرویس ۱ (صنایع دستی) انتساب می‌دهد
- UNIQUE قدیمی روی `title` را حذف و UNIQUE جدید روی `(service_type_id, title)` را اضافه می‌کند
- بذر اختصاصی سرویس‌های ۲ و ۳ را درج می‌کند

برای نصب تازه، کافی است `database/schema.sql` اجرا شود — همه چیز در آن گنجانده شده است.

---

## [0.2.3] — ۲۰۲۶-۱۱-۰۷ — اصلاح jdf، constructor، CSRF، بازطراحی داشبورد، تم شب

### 🔧 اصلاحات (Bug Fixes)

#### ۱) خطای syntax در `config/jdf.php` خط ۱۰۳
- **علت:** `case 'k';` به‌جای `case 'k':` (سمی‌کالن به‌جای کالن) — خطای parser
- **راه‌حل:** اصلاح به `case 'k':`

#### ۲) خطای `Cannot call constructor` در FeeTariffController
- **علت:** `FeeTariffController` در constructor خود `parent::__construct()` را فراخوانی می‌کرد،
  ولی کنترلر پایه `Controller` constructor ندارد. در PHP، فراخوانی constructor والد
  وقتی والد constructor ندارد، باعث خطای `Cannot call constructor` می‌شود.
- **راه‌حل:** حذف `parent::__construct()` از `FeeTariffController`
- **نتیجه:**
  - صفحهٔ `/admin/fee-tariff` اکنون بدون خطا لود می‌شود
  - دکمهٔ «ذخیره تنظیمات» کار می‌کند
  - تیک خدمات (toggle tick) کار می‌کند

#### ۳) خطای ۵۰۰ در `/admin/payment/tick` (تیک خدمات)
- **علت اصلی:** همان مشکل constructor — هیچ متدی از `FeeTariffController` قابل فراخوانی نبود
- **بهبود اضافی:**
  - افزودن `try/catch` کامل در `toggleTick` برای مدیریت خطاها
  - پاسخ JSON مناسب برای خطاها (به‌جای exit با HTML)
  - بررسی `tickId > 0` قبل از پردازش
  - fallback امن برای audit log (در صورت نبود جدول)

#### ۴) دکمهٔ ذخیره تنظیمات کار نمی‌کرد
- **علت:** همان مشکل constructor — کنترلر قابل نمونه‌سازی نبود
- **راه‌حل:** اکنون با اصلاح constructor، ذخیره‌سازی تنظیمات کار می‌کند
- **افزایش امنیت:** افزودن کلیدهای `fee_*` به `Setting::editableKeys()`

#### ۵) بهبود CSRF برای درخواست‌های AJAX
- **مشکل:** وقتی CSRF نامعتبر بود، `verifyCsrf` یک صفحهٔ HTML کامل برمی‌گرداند
  که برای درخواست‌های AJAX مناسب نبود و باعث «خطا در ارتباط با سرور» می‌شد
- **راه‌حل:**
  - تشخیص درخواست AJAX (با `X-Requested-With` یا `Accept: application/json`)
  - پاسخ JSON مناسب با خطای `csrf_invalid`
  - پشتیبانی از `X-CSRF-TOKEN` header برای درخواست‌های AJAX

### ✨ بازطراحی‌ها (UI/UX)

#### ۶) بازطراحی کامل داشبورد مدیریت
- **مشکل قبلی:** منوی داشبورد «اسفناک» و بدون رابط کاربری زیبا
- **راه‌حل:**
  - بخش هیرو با گرادیان فیروزه‌ای و نمایش ۴ شاخص اصلی
  - کارت‌های KPI مدرن با آیکون‌های گرادیانی و نوار روند
  - نوار توزیع وضعیت‌ها با انیمیشن
  - تایم‌لاین برای آخرین رویدادها
  - کارت‌های اقدام سریع با hover effect
  - پشتیبانی کامل از تم روز/شب

#### ۷) رفع مشکلات رنگ متن در تم شب
- **مشکل:** در تم شب، برخی متن‌ها سفید روی پس‌زمینه سفید دیده نمی‌شدند
  (`.text-body`, `.text-dark` در بوت‌استرپ تیره می‌ماندند)
- **راه‌حل:** افزودن ۴۰+ قانون CSS برای اصلاح رنگ‌ها در تم شب:
  - `.text-body`, `.text-dark` → روشن در تم شب
  - `.badge` ها با رنگ‌های مناسب
  - `.alert` ها با پس‌زمینه‌های تیره
  - `.dropdown-menu`, `.modal`, `.list-group`
  - `.btn-light`, `.btn-outline-light`
  - `.form-label`, `label`, `small`
  - `.code`, `.pre`, `placeholder`

### 📊 تحلیل مدل فروشگاهی (گزارش جداگانه)
- فایل: `/home/z/my-project/download/تحلیل-مدل-فروشگاهی.md`
- شامل ۶ فاز برای تبدیل به مدل فروشگاهی:
  ۱. زیرساخت کاربر (user_accounts)
  ۲. صفحات عمومی (login/signup)
  ۳. داشبورد کاربر
  ۴. مدل خریداری (services)
  ۵. حذف احراز ۶ عاملی
  ۶. زیباسازی رابط کاربری
- **هیچ تغییری برای این فاز اعمال نشده** — منتظر تأیید کاربر

### 📁 فایل‌های اصلاح‌شده (۸)
- `config/jdf.php` — اصلاح `case 'k';` → `case 'k':`
- `app/Controllers/Controller.php` — بهبود `verifyCsrf` برای AJAX
- `app/Controllers/FeeTariffController.php` — حذف `parent::__construct()` + try/catch در toggleTick
- `app/Models/Setting.php` — افزودن کلیدهای fee_* به editableKeys
- `app/Views/admin/dashboard.php` — بازطراحی کامل
- `config/helper.php` — افزودن آیکون `trending-up`
- `public/assets/css/style.css` — ۴۰+ قانون برای تم شب
- `config/config.php`, `VERSION`, `CHANGELOG.md` — به‌روزرسانی نسخه
- `app/Views/admin_layout.php`, `app/Views/layout.php` — نسخهٔ 0.2.3

### 🎯 موارد تست‌شده
- ✓ صفحهٔ `/admin/fee-tariff` بدون خطا لود می‌شود
- ✓ دکمهٔ «ذخیره تنظیمات» کار می‌کند
- ✓ تیک خدمات (toggle tick) کار می‌کند
- ✓ داشبورد با رابط کاربری زیبا نمایش داده می‌شود
- ✓ تم شب: همهٔ متن‌ها خوانا هستند
- ✓ تم روز: همهٔ متن‌ها خوانا هستند
- ✓ توابع تاریخ شمسی (jdate) بدون خطا کار می‌کنند

---

## [0.2.2] — ۲۰۲۶-۱۱-۰۷ — اصلاح sort_order و وضعیت «در انتظار رسید»

### 🔧 اصلاحات (Bug Fixes)

#### ۱) خطای `Column not found: 1054 Unknown column 'sort_order' in 'ORDER BY'`
- **علت:** جدول `payment_service_ticks` در CREATE TABLE اولیه فاقد ستون `sort_order` بود
  ولی کوئری `findByPaymentId` از `ORDER BY sort_order, id` استفاده می‌کرد. این باعث
  خطای ۵۰۰ و از بین رفتن استایل صفحهٔ پرونده می‌شد.
- **راه‌حل:**
  ۱. افزودن ستون `sort_order INT NOT NULL DEFAULT 0` به `CREATE TABLE` در `schema.sql`
  ۲. افزودن مهاجرت idempotent برای دیتابیس‌های موجود:
     ```sql
     SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
                        WHERE TABLE_NAME = 'payment_service_ticks' AND COLUMN_NAME = 'sort_order');
     SET @ddl = IF(@col_exists = 0,
         'ALTER TABLE payment_service_ticks ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER untick_reason',
         'SELECT 1');
     PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
     ```
  ۳. به‌روزرسانی `createForPayment` برای ذخیرهٔ `sort_order`
  ۴. افزودن fallback امن در `findByPaymentId` (try/catch) برای دیتابیس‌های قدیمی

#### ۲) متقاضی پس از درخواست پرداخت توسط مدیر، فرم آپلود رسید را نمی‌بیند
- **علت:** وقتی مدیر «درخواست پرداخت» را فعال می‌کرد، یک رکورد `payments` با
  `status='در انتظار بررسی'` ایجاد می‌شد. سپس view با دیدن این وضعیت، پیام
  «رسید پرداخت شما در انتظار بررسی است» را نمایش می‌داد و فرم آپلود را مخفی می‌کرد.
  این یعنی متقاضی هرگز نمی‌توانست رسید آپلود کند!
- **راه‌حل:**
  ۱. افزودن وضعیت جدید `در انتظار رسید` به ENUM `payments.status`
     (این یعنی مدیر درخواست داده، ولی متقاضی هنوز رسید آپلود نکرده)
  ۲. مدیر هنگام فعال‌سازی، رکورد را با وضعیت `در انتظار رسید` ایجاد می‌کند
  ۳. متقاضی در صفحهٔ پیگیری، فرم آپلود رسید را می‌بیند
  ۴. وقتی متقاضی رسید را آپلود کرد، وضعیت به `در انتظار بررسی` تغییر می‌کند
  ۵. سپس مدیر می‌تواند تأیید/رد کند

#### ۳) به‌روزرسانی منطق نمایش فرم آپلود در `tracking/status.php`
- ۴ حالت نمایش:
  - `تأیید شده` → فقط پیام موفقیت
  - `در انتظار بررسی` → فقط پیام «در انتظار بررسی مدیر»
  - `در انتظار رسید` → فرم آپلود + پیام «مدیر درخواست صادر کرده»
  - `رد شده` → فرم آپلود + دلیل رد
- پیام‌های دقیق‌تر و فارسی‌تر

#### ۴) رفع باگ `original_name` در رسید پرداخت
- قبلاً از `basename((string)$file['name'])` استفاده می‌شد که برای نام‌های عجیب ممکن بود
  مشکل داشته باشد. اکنون از `sanitize_upload_filename()` استفاده می‌شود.

### 🗄️ پایگاه داده
- افزودن ستون `sort_order` به `payment_service_ticks`
- افزودن وضعیت `در انتظار رسید` به ENUM `payments.status`
- مهاجرت idempotent برای دیتابیس‌های موجود

### 📁 فایل‌های اصلاح‌شده (۵)
- `database/schema.sql` — CREATE TABLE + ALTER idempotent + MODIFY COLUMN
- `app/Models/PaymentServiceTick.php` — ایجاد sort_order + fallback امن
- `app/Controllers/AdminController.php` — استفاده از `در انتظار رسید` به‌جای `در انتظار بررسی`
- `app/Controllers/TrackingController.php` — به‌روزرسانی رکورد موجود به‌جای ایجاد رکورد جدید
- `app/Views/tracking/status.php` — منطق نمایش صحیح بر اساس ۴ وضعیت پرداخت

### 🎯 گردش کار صحیح
```
۱. مدیر در پنل → «درخواست پرداخت هزینه» را فعال می‌کند
   → رکورد payments با status='در انتظار رسید' و مبلغ محاسبه‌شده ایجاد می‌شود
۲. متقاضی در /tracking/status/{code} → فرم آپلود رسید را می‌بیند
   + پیام «مدیر درخواست پرداخت صادر کرده — مبلغ X تومان»
۳. متقاضی مبلغ را واریز می‌کند، رسید را آپلود می‌کند
   → رکورد payments به‌روزرسانی می‌شود: status='در انتظار بررسی'
۴. مدیر در پنل → تأیید یا رد می‌کند
   → status='تأیید شده' یا 'رد شده'
```

---
## [0.2.1] — ۲۰۲۶-۱۱-۰۷ — اعتبارسنجی چندلایه‌ای فایل آپلودی

### 🔧 اصلاحات (Bug Fixes)

#### خطای «پسوند فایل «» مجاز نیست» برای نام‌های عادی دوربین موبایل
- **علت:** فایل‌هایی با نام‌هایی مانند `IMG_20240406_165048_478.jpg`، `20250125_160820.jpg`،
  `VideoCapture_20261021-125720.jpg` و حتی `-2147483648_-210002.jpg` توسط دوربین موبایل،
  نرم‌افزارهای شبکه‌های اجتماعی و ابزارهای تبدیل تولید می‌شوند. در برخی پیکربندی‌های PHP
  (به‌ویژه XAMPP ویندوز)، نام فایل در `$_FILES['name']` ممکن است خالی یا نامعتبر دریافت شود.
- **راه‌حل:** افزودن تابع مرکزی `validate_uploaded_file()` با رویکرد چندلایه‌ای:

  ۱. بررسی خطای آپلود (`UPLOAD_ERR_OK`)
  ۲. استخراج پسوند از نام فایل (با `safe_file_extension`)
  ۳. **اگر پسوند قابل استخراج نبود → fallback به `detect_extension_from_mime`**
     که MIME واقعی محتوای فایل را با `finfo` بررسی می‌کند
  ۴. بررسی حجم فایل
  ۵. بررسی MIME واقعی (نه فقط پسوند)
  ۶. تطبیق پسوند با MIME (مثلاً jpg باید با `image/jpeg` مطابقت داشته باشد)

#### تقویت تابع `safe_file_extension`
- حذف BOM (UTF-8 BOM در ابتدای رشته)
- نرمال‌سازی جداکننده‌های مسیر (`\` به `/`)
- محدودیت طول پسوند (بیش از ۲۰ کاراکتر غیرمعقول)
- پشتیبانی از نام‌های با خط تیره در ابتدا (مثل `-2147483648_-210002.jpg`)

#### تابع جدید: `detect_extension_from_mime()`
- وقتی نام فایل خالی است یا پسوند قابل استخراج نیست
- از `finfo(FILEINFO_MIME_TYPE)` برای تشخیص نوع واقعی فایل استفاده می‌کند
- نگاشت MIME به پسوندهای مجاز: `image/jpeg` → `jpg`, `image/png` → `png`, `application/pdf` → `pdf`
- تلاش مجدد با `FILEINFO_MIME` اگر نوع `application/octet-stream` بود

#### تابع جدید: `validate_uploaded_file()`
- تابع مرکزی برای اعتبارسنجی کامل یک فایل آپلودشده
- خروجی: `valid`, `ext`, `mime`, `safe_name`, `error`, `reason`
- پیام‌های خطای دقیق فارسی برای هر نوع خطا
- ۷ لایهٔ امنیتی

### 🛡️ امنیت مضاعف

| لایه | شرح |
|---|---|
| ۱. خطای آپلود | بررسی `UPLOAD_ERR_OK` با پیام‌های فارسی دقیق |
| ۲. نرمال‌سازی نام | حذف BOM، کاراکترهای کنترلی، نرمال‌سازی مسیر |
| ۳. استخراج پسوند از نام | `safe_file_extension` تقویت‌شده |
| ۴. fallback به MIME | اگر نام خالی بود، MIME واقعی بررسی می‌شود |
| ۵. حجم فایل | بررسی `size > 0` و `size <= MAX_UPLOAD_SIZE` |
| ۶. MIME واقعی | `finfo(FILEINFO_MIME_TYPE)` (نه فقط پسوند) |
| ۷. تطبیق پسوند با MIME | جلوگیری از تغییر دستی پسوند (مثلاً `.php.jpg`) |

### 📁 فایل‌های اصلاح‌شده (۶)
- `config/helper.php` — تقویت `safe_file_extension` + توابع جدید `detect_extension_from_mime` و `validate_uploaded_file`
- `app/Controllers/RegistrationController.php` — استفاده از `validate_uploaded_file` در اعتبارسنجی و ذخیره
- `app/Controllers/AdminController.php` — استفاده در `addDocument` و `$saveFile` (ثبت دستی مدیر)
- `app/Controllers/TrackingController.php` — استفاده در `submitPayment` (رسید پرداخت)
- `app/Controllers/RevisionController.php` — استفاده در `submitRevision` (مدارک بازبینی)
- `app/Controllers/LicenseController.php` — استفاده در `submitAccessRequest` و `uploadLicense` (تصویر مجوز)

### 🎯 فایل‌های تست‌شده (همگی اکنون پذیرفته می‌شوند)
- `-2147483648_-210002.jpg` (نام با خط تیره در ابتدا)
- `20250125_160820.jpg` (نام دوربین موبایل)
- `IMG_20240406_165048_478.jpg` (نام سامسونگ)
- `VideoCapture_20261021-125720.jpg` (نام ویندوز)
- نام‌های فارسی با encoding ویندوز-۱۲۵۶
- نام‌های با BOM
- نام‌های با کاراکترهای کنترلی

### 🔒 مواردی که همچنان رد می‌شوند (امنیت حفظ می‌شود)
- فایل‌های PHP با پسوند `.jpg` (MIME واقعی PHP است نه JPEG)
- فایل‌های مخرب با پسوند جعلی
- فایل‌های با حجم بیش از `MAX_UPLOAD_SIZE`
- فایل‌های با MIME واقعی غیرمجاز (مثلاً `image/gif`, `image/webp`, `application/zip`)

---

## [0.2.0] — ۲۰۲۶-۱۱-۰۷ — نرخ‌نامهٔ چندگانه + تیک خدمات + مودال پیام

### ✨ افزوده‌ها (MINOR)

#### ۱. نرخ‌نامهٔ چندگانه بر اساس دهک درآمدی
- ۴ گروه قیمتی: الف (روستایی/دهک ۱-۲: ۱,۳۵۰,۰۰۰)، ب (۳-۴: ۲,۲۵۰,۰۰۰)، ج (۵-۶: ۳,۸۰۰,۰۰۰)، د (۷-۱۰: ۶,۵۰۰,۰۰۰)
- ۱۲ خدمت پایه برای هر گروه (قابل تنظیم از پنل)
- محاسبهٔ خودکار بر اساس `income_decile` و `village/rural_district`
- معافیت خودکار روستاییان (گروه الف) — قابل لغو توسط مدیر
- fallback هوشمند برای دهک خالی (گروه ج با flag `fallback_used`)
- سازگاری عقب کامل: اگر `fee_tariff_active=0`، مبلغ ثابت قبلی اعمال می‌شود

#### ۲. تیک خدمات در پنل مدیریت
- ۱۲ تیک برای هر پرداخت (پیش‌فرض همه تیک‌خورده)
- مدیر می‌تواند خدمات انجام‌نشده را تیک بردارد
- محاسبهٔ خودکار مبلغ نهایی (`final_amount`)
- حداقل مبلغ نهایی: ۱۰٪ مبلغ اصلی (محافظت)
- دلیل تخفیف اجباری اگر تعداد تیک‌برداشته‌شده > ۲ باشد
- هشدار تخفیف بیش از ۵۰٪ (نیازمند مدیر ارشد)
- به‌روزرسانی AJAX (بدون رفرش صفحه)
- نمایش مبلغ اصلی، کسر، نهایی، و مبلغ واریزی متقاضی

#### ۳. تشخیص خودکار موارد مشکوک
- `rural_address_mismatch`: روستا + شهر بزرگ (تهران/مشهد/...)
- `decile_history_conflict`: دهک فعلی ۳+ کمتر از دهک قبلی شخص
- `low_decile_urban`: دهک ۱-۲ + آدرس شهری بزرگ
- `fallback_used`: دهک خالی یا نامعتبر
- نمایش flags در صفحهٔ پرونده با badge های رنگی

#### ۴. مودال پیام حمایتی پس از کد رهگیری
- نمایش خودکار پس از ۱.۵ ثانیه در صفحهٔ موفقیت ثبت‌نام
- متن پیام مخصوص هر گروه (قابل تنظیم از پنل)
- فقط یک بار در هر نشست مرورگر نمایش داده می‌شود (`sessionStorage`)
- قابل بستن توسط کاربر

#### ۵. پنل مدیریت نرخ‌نامه (مسیر `/admin/fee-tariff`)
- تنظیمات کلی: فعال‌سازی، تاریخ اعتبار، معافیت روستایی، fallback
- CRUD کامل گروه‌های قیمتی (مودال)
- ویرایش مبالغ ۱۲ آیتم هر گروه
- راهنمای منطق محاسبه

#### ۶. درخواست پرداخت پیشرفته
- هنگام فعال‌سازی، مبلغ خودکار محاسبه می‌شود (نه ثابت ۵۰۰,۰۰۰)
- ایجاد رکورد `payments` با `original_amount`, `final_amount`, `fee_group_id`, `suspicion_flags`
- ایجاد خودکار ۱۲ تیک اولیه برای پرداخت جدید
- ثبت در audit log (`payment_audit_log`)

#### ۷. نمایش هوشمند دکمه‌ها در صفحهٔ پرونده
- اگر `payment_required = 0` → «درخواست پرداخت هزینه» (gold)
- اگر `payment_required = 1` و پرداخت در انتظار → «لغو درخواست پرداخت» (outline-danger)
- اگر پرداخت تأیید شده → دکمه لغو **مخفی**، نمایش «درخواست پرداخت جدید (هزینهٔ اضافی)» (outline-primary)

#### ۸. تاریخچهٔ ممیزی کامل (Audit Log)
- هر تغییر مدیر روی تیک‌ها، تخفیف، تأیید/رد ثبت می‌شود
- نمایش تاریخچه در صفحهٔ پرونده (قابل باز/بسته شدن)

### 🗄️ پایگاه داده

#### جداول جدید (۳)
- `fee_groups` — ۴ گروه قیمتی
- `fee_group_items` — ۱۲ خدمت برای هر گروه (۴۸ ردیف بذر)
- `payment_service_ticks` — تیک خدمات انجام‌شده برای هر پرداخت
- `payment_audit_log` — تاریخچهٔ تغییرات مدیر

#### ستون‌های جدید در `payments` (۸ ستون)
- `original_amount`, `final_amount`, `discount_reason`
- `fee_group_id`, `is_rural_exempt`, `fallback_used`, `suspicion_flags`

#### تنظیمات جدید (۹ کلید)
- `fee_tariff_active`, `fee_tariff_valid_until`, `fee_default_amount`
- `fee_rural_auto_eligible`, `fee_modal_enabled`, `fee_modal_dismissable`
- `fee_fallback_group_code`, `fee_min_amount_percent`
- `fee_max_discount_without_reason`, `fee_max_discount_with_reason`

### 📁 فایل‌های جدید (۷)
- `app/Models/FeeGroup.php`
- `app/Models/FeeGroupItem.php`
- `app/Models/PaymentServiceTick.php`
- `app/Models/PaymentAuditLog.php`
- `app/Services/FeeCalculatorService.php`
- `app/Controllers/FeeTariffController.php`
- `app/Views/admin/fee_tariff.php`

### 📝 فایل‌های اصلاح‌شده (۸)
- `database/schema.sql` — افزودن ۳ جدول + ۸ ستون + ۹ تنظیم + بذر اولیه
- `app/Controllers/AdminController.php` — اصلاح `requestPayment` و `viewApplication`
- `app/Controllers/TrackingController.php` — استفاده از FeeCalculatorService
- `app/Controllers/RegistrationController.php` — ارسال feeGroup به success.php
- `app/Views/admin/view_application.php` — دکمه‌های هوشمند + کارت تیک خدمات
- `app/Views/registration/success.php` — مودال پیام حمایتی
- `app/Views/admin_layout.php` — آیتم منو + آیکون‌های جدید
- `config/helper.php` — توابع `safe_file_extension`, `sanitize_upload_filename` + آیکون‌های `wallet`, `check-square`
- `public/index.php` — مسیرهای جدید (۶ مسیر)

### 🛡️ محافظت‌های امنیتی

| محافظت | مقدار |
|---|---|
| حداقل مبلغ نهایی | ۱۰٪ مبلغ اصلی |
| حداکثر تخفیف بدون دلیل | ۲ خدمت |
| حداکثر تخفیف با دلیل | ۵۰٪ (با تأیید مدیر ارشد بیشتر) |
| پیش‌فرض تیک‌ها | همه تیک‌خورده |
| Audit log | تمام تغییرات مدیر |
| سازگاری عقب | اگر `fee_tariff_active=0`، سیستم به حالت قبلی برمی‌گردد |

### 🎯 تجربهٔ متقاضی (بدون تغییر ساختاری)
- متقاضی فقط مبلغ جدید را می‌بیند (به‌جای ۵۰۰,۰۰۰ مثلاً ۱,۳۵۰,۰۰۰)
- کانال‌های پرداخت همان‌اند
- رسید آپلود می‌کند همان‌طور
- گردش تأیید مدیر همان است
- تنها افزوده: مودال پیام حمایتی پس از کد رهگیری

---

## [0.1.2] — ۲۰۲۶-۱۱-۰۷ — اصلاح خطای JavaScript و تغییر نوع خدمت

### 🔧 اصلاحات (Bug Fixes)

#### خطای `Uncaught SyntaxError: Unexpected token '<'` در register:453
- **علت:** در فایل `app/Views/registration/step1.php` خط ۲۸۷، به‌جای بستن تگ `<script>` با `</script>`،
  تگ جدید `<script>` باز شده بود. این باعث می‌شد مرورگر هر دو بلوک کد JavaScript را به‌عنوان یک
  بلوک تفسیر کند و خطای SyntaxError رخ دهد. در نتیجه:
  - تابع `toggleApplicantType()` اجرا نمی‌شد → **جابه‌جایی حقیقی/حقوقی کار نمی‌کرد**
  - تابع `updateDays()` (تنظیم روزهای ماه شمسی) ممکن بود تحت تأثیر قرار گیرد
- **راه‌حل:** افزودن `</script>` صحیح بین دو بلوک.

#### عدم امکان تغییر نوع خدمت پس از انتخاب اولیه
- **علت اول:** لینک «تغییر نوع خدمت» در step1.php به `/register` اشاره می‌کرد، اما متد `index()`
  چون `service_id` از قبل در نشست بود، مستقیم به مرحلهٔ ۱ می‌رفت و صفحهٔ انتخاب را نشان نمی‌داد.
- **علت دوم:** در متد `selectService()` از `Session::remove('registration_step1')` استفاده می‌شد
  که کلید اشتباهی بود (داده‌ها در `$_SESSION['registration']['step1']` ذخیره می‌شوند، نه `registration_step1`).
- **راه‌حل:**
  - لینک به `register?change=service` تغییر کرد
  - تشخیص `?change=service` در `index()` با پاک‌سازی نشست و نمایش صفحهٔ انتخاب
  - افزودن متد کمکی `clearWizardData()` که پاک‌سازی کامل داده‌های ویزارد را انجام می‌دهد
    (شامل step1..step4 و مراحل سفارشی wiz_*)
  - اصلاح `selectService()` برای استفاده از این متد

### 📁 فایل‌های اصلاح‌شده
- `app/Views/registration/step1.php` — اصلاح تگ `<script>` + به‌روزرسانی لینک
- `app/Controllers/RegistrationController.php` — اصلاح `index()` و `selectService()` + متد جدید

### 🎯 موارد تست‌شده
- ✓ صفحهٔ `/register/step/1` بدون خطای JavaScript لود می‌شود
- ✓ کلیک روی «شخص حقیقی»/«شخص حقوقی» فیلدهای مربوطه را نمایش/مخفی می‌کند
- ✓ کلیک روی «تغییر نوع خدمت» به صفحهٔ انتخاب سرویس می‌رود
- ✓ انتخاب سرویس جدید داده‌های ویزارد قبلی را پاک می‌کند

---

## [0.1.1] — ۲۰۲۶-۱۱-۰۷ — اصلاح خطاهای runtime

### 🔧 اصلاحات (Bug Fixes)

#### خطای آپلود فایل با پسوند خالی
- **علت:** استفاده از `pathinfo($file['name'], PATHINFO_EXTENSION)` در نام فایل‌های فارسی یا
  دارای کاراکترهای غیر ASCII (مثلاً فایل‌های jpg در XAMPP ویندوز) باعث می‌شد پسوند خالی
  (`''`) برگردد و کاربر پیام «پسوند فایل «» مجاز نیست» را ببیند.
- **راه‌حل:** افزودن تابع helper جدید `safe_file_extension(string $filename): string`
  که با نرمال‌سازی encoding (Windows-1256 → UTF-8)، حذف BOM و کاراکترهای کنترلی،
  و استفاده از `strrpos` به‌جای `pathinfo` به‌صورت قابل اعتماد پسوند را استخراج می‌کند.
- **همچنین:** تابع `sanitize_upload_filename()` برای نرمال‌سازی نام فایل در نمایش و ذخیره‌سازی.
- **فایل‌های اصلاح‌شده:**
  - `config/helper.php` (افزودن دو تابع کمکی)
  - `app/Controllers/RegistrationController.php` (۲ نقطه)
  - `app/Controllers/TrackingController.php` (۲ نقطه)
  - `app/Controllers/AdminController.php` (۲ نقطه)
  - `app/Controllers/RevisionController.php` (۲ نقطه)
  - `app/Controllers/LicenseController.php` (۳ نقطه)
  - `app/Views/admin/view_application.php` (۳ نقطه — تشخیص تصویر برای پیش‌نمایش)
  - `app/Views/admin/revision_detail.php` (۱ نقطه + محافظت null)

#### خطای Deprecated: PDO::MYSQL_ATTR_INIT_COMMAND
- **علت:** در PHP 8.5+ ثابت `PDO::MYSQL_ATTR_INIT_COMMAND` منسوخ شده و هشدار Deprecated نمایش می‌دهد.
- **راه‌حل:** حذف از آرایه `$options` و استفاده از `charset=utf8mb4` در DSN (که خودش SET NAMES را اجرا می‌کند).
  سپس با `$this->pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci")` پس از ساخت PDO،
  collation تنظیم می‌شود.
- **فایل اصلاح‌شده:** `core/Database.php`

#### Notice: Undefined array key در $_FILES چندفایلی
- **علت:** در آپلود فیلد `product_photos[]` (آرایه‌ای از فایل‌ها)، دسترسی مستقیم به
  `$_FILES['product_photos']['tmp_name'][$idx]` بدون null coalescing ممکن است
  Notice تولید کند اگر اندیس موجود نباشد.
- **راه‌حل:** استخراج اولیهٔ آرایه‌های `name`, `tmp_name`, `size`, `error` با `?? []`
  و سپس دسترسی ایمن.
- **فایل‌های اصلاح‌شده:**
  - `app/Controllers/RegistrationController.php` (مرحلهٔ ۴ ثبت‌نام)
  - `app/Controllers/AdminController.php` (ثبت دستی مدیر)

#### محافظت دسترسی به آرایه چندسطحی در Session
- **علت:** در `Session::clearRegistration()`, مقداردهی `$_SESSION['registration']['service_id']`
  ممکن است اگر `$_SESSION['registration']` تنظیم نشده باشد، خطا دهد.
- **راه‌حل:** بررسی `isset` و `is_array` قبل از مقداردهی.
- **فایل اصلاح‌شده:** `core/Session.php`

#### بهبود امنیت در revision_detail.php
- استخراج همهٔ اندیس‌های `$nd['doc']` با مقادیر پیش‌فرض (همراه با null coalescing)
  برای جلوگیری از Notice در صورت نبود کلید.
- استفاده از `safe_file_extension()` به‌جای `pathinfo()` برای تشخیص تصویری بودن مدرک.

### 🛡️ ابزارهای جدید تست و اسکن
- `tools/scan-runtime-errors.py` — اسکنر خطاهای Deprecated و توابع حذف‌شده (PHP 8+)
- `tools/scan-runtime-v2.py` — اسکنر پیشرفته برای Notice/Warning (دسترسی به $_POST/$_GET/$_FILES بدون null coalescing)
- گزارش JSON ذخیره‌شده: `tests/runtime-errors-scan.json`

### 📊 نتایج اسکن
- **CRITICAL (Deprecated/Removed):** 0 مورد ✓
- **HIGH (احتمال Notice/Warning):** 0 مورد ✓
- **MEDIUM (دسترسی چندسطحی):** 1 مورد (false positive با محافظت isset)
- **INFO (پیشنهادات سبک):** 9 مورد `@chmod` که عمدی هستند برای suppress warning

### 🎯 سازگاری
- PHP 8.0 تا 8.5+ ✓
- MariaDB 10.4+ ✓
- MySQL 5.7+ ✓
- XAMPP (آخرین نسخه) ✓

---

## [0.1.0] — ۲۰۲۶-۱۱-۰۷ — اولین نسخهٔ رسمی

### ✨ افزوده‌ها

#### امکانات سامانه
- **ثبت‌نام متقاضی:** ویزارد ۴ مرحله‌ای با نوار پیشرفت گرادیانی، اعتبارسنجی سمت سرور و کلاینت، آپلود امن مدارک
- **کد رهگیری یکتا:** قالب `HG-XXXXXXXX`، دسترسی کاربر به پرونده فقط با کد
- **پیگیری پرونده:** تایم‌لاین عمودی رویدادها، درخواست تغییر کد پستی با گردش کار تأیید، نسخهٔ چاپی A4
- **پنل مدیریت نقش‌محور:** سه نقش `admin`, `expert`, `document_uploader`
- **۱۴ وضعیت پرونده:** مسیر یکطرفهٔ رسیدگی با نوار بصری
- **لغو خودکار:** درخواست‌های منقضی پس از ۱۴ روز، بدون نیاز به cron job
- **اولویت‌بندی خودکار:** ثبت‌نام‌های طولانی‌تر از ۱۲ روز با پرچم ⭐
- **فرم‌ساز پویا:** افزودن/حذف فیلد از پنل مدیریت
- **تنظیمات از پنل:** نام سامانه، برندینگ، محتوای «دربارهٔ ما»
- **تم روز/شب** سراسری با ذخیره در localStorage
- **آیکون‌های SVG درون‌خطی:** بیش از ۵۰ آیکون بدون فونت خارجی
- **فونت تزئینی لاله‌زار** برای تیترها و لوگو

#### نقشهٔ راه ۱۰ فازی (همه پیاده‌سازی شد)
- **فاز ۱:** ویزارد پویا با مراحل سفارشی (`wizard_steps`)
- **فاز ۲:** چرخهٔ بازبینی پرونده با نمای مقایسه (`applicant_revisions`)
- **فاز ۳+۴:** گردش پرداخت هزینهٔ ثبت‌نام (`payments`, `payment_channels`)
- **فاز ۵:** پیامک‌های وضعیت با ارتقای خودکار (`sms_templates`, `user_sms_entries`)
- **فاز ۶:** تصویر مجوز با قاعدهٔ «یک‌بار دریافت» و توکن ۷۲ ساعته (`license_files`, `license_access_requests`)
- **فاز ۷:** چندمجوزی و تفکیک شخص/پرونده (`persons` + سلسله‌مراتب)
- **فاز ۸:** احراز هویت شش‌عاملی برای سوابق با قفل تصاعدی (`identity_checks`)
- **فاز ۹:** انواع خدمات (صنایع دستی، خانگی، گردشگری، هنری) (`service_types`)
- **فاز ۱۰:** اشخاص حقوقی با اعتبارسنجی رقم کنترل کد ملی/شناسهٔ ملی

#### استقرار و پیکربندی
- **پوشهٔ `deploy/`** با پشتیبانی کامل از سه محیط:
  - **ویندوز:** اسکریپت‌های `install-and-run.bat` و `install-xampp.bat` برای XAMPP/Laragon
  - **لینوکس:** `install.sh` (Apache+MariaDB خودکار) و `install-service.sh` (systemd)
  - **داکر:** `Dockerfile`, `docker-compose.yml` (سه سرویس app+db+web)، `entrypoint.sh` با اجرای خودکار `schema.sql`
- **پیکربندی Apache:** vhost کامل با HSTS، محدودسازی دسترسی به پوشه‌های حساس
- **پیکربندی Nginx:** server block با قواعد rewrite معادل `.htaccess`
- **فایل `.htaccess` در ریشه:** هدایت خودکار به `public/` وقتی DocumentRoot روی ریشه است
- **فایل `.htaccess` در `public/`:** مسیریابی + هدرهای امنیتی + کش + فشرده‌سازی
- **فایل `VERSION`** برای ابزارهای CI/CD
- **ثابت `APP_VERSION`** در `config.php` و نمایش در فوتر پنل

#### مستندات
- [`docs/00-blueprint.md`](docs/00-blueprint.md): بلوپرینت فنی کامل (۱۰۴۰ خط، ۶ نمودار Mermaid)
- [`docs/08-گزارش-واکنش‌گرایی.md`](docs/08-گزارش-واکنش‌گرایی.md): نتایج تست ۸ دستگاه
- [`docs/09-گزارش-تست-عملکردی.md`](docs/09-گزارش-تست-عملکردی.md): نتایج ۱۸ تحلیل استاتیک

#### آزمون‌ها
- **۱۷۱ آزمون سرتاسری** (`tests/smoke-test.sh`) با curl
- **۱۸ تحلیل استاتیک** (`tools/static-analysis.py`) — ۱۶ PASS، ۲ WARN، ۰ FAIL
- **تست واکنش‌گرایی ۸ دستگاه** (`tools/playwright-responsive.js`) — همه بدون اسکرول افقی و سرریز

### 🔒 امنیت
- CSRF روی همهٔ فرم‌های POST
- Prepared Statements در همهٔ کوئری‌ها
- `session_regenerate_id` پس از ورود + کوکی `HttpOnly`/`SameSite`/`Secure`
- Rate-limiting ورود (۵ تلاش → قفل ۵ دقیقه‌ای)
- اعتبارسنجی **MIME واقعی** در آپلود فایل
- محافظت Path Traversal با `realpath` در سرو فایل‌ها
- `chmod 0640` فایل‌های آپلودشده
- هدرهای امنیتی سراسری (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`)
- **پیشگیری از CSV Injection** در `CsvExporter::sanitizeCell()` (OWASP)
- اعتبارسنجی رقم کنترل کد ملی و شناسهٔ ملی حقوقی
- احراز هویت شش‌عاملی برای مشاهدهٔ سوابق

### 🗄️ پایگاه داده
- ۲۲ جدول در یک فایل `database/schema.sql` یکپارچه
- نصب با یک دستور: `mysql -u root -p < database/schema.sql`
- با `IF NOT EXISTS` و `ON DUPLICATE KEY UPDATE` امن برای اجرای مجدد

### 🎨 رابط کاربری
- طراحی «ایرانی مدرن» با پالت فیروزه‌ای/زعفرانی/سرمه‌ای
- واکنش‌گرایی کامل موبایل/تبلت/دسکتاپ
- Off-canvas sidebar در موبایل
- `inputmode="numeric"` برای فیلدهای رقمی
- فونت وزیرمتن برای متن و لاله‌زار برای تیترها

---

## قواعد ارتقا

- هر اصلاح باگ یا بهبود کوچک: یک واحد به **PATCH** اضافه می‌شود (مثلاً 0.1.0 → 0.1.1)
- افزودن قابلیت جدید به‌صورت سازگار: یک واحد به **MINOR** اضافه می‌شود (مثلاً 0.1.0 → 0.2.0)
- تغییر ناسازگار API: یک واحد به **MAJOR** اضافه می‌شود (مثلاً 0.1.0 → 1.0.0)
