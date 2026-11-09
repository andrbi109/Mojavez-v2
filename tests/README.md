# تست‌های سامانهٔ Mojavez

این پوشه شامل تست‌های جامع برای تمامی حالات، خدمات، وضعیت‌ها و سناریوهای
امنیتی و عملکردی سامانهٔ مجوز است. تست‌ها به‌صورت **بدون وابستگی به PHPUnit**
نوشته شده‌اند — فقط به PHP 8.0+ نیاز دارند.

## نحوهٔ اجرا

### ۱) اجرای همهٔ تست‌ها

```bash
cd /path/to/Mojavez-v2
php tests/run.php
```

### ۲) اجرای یک کلاس خاص

```bash
php tests/run.php SecurityTest
php tests/run.php ServiceScopeTest
php tests/run.php StatusWorkflowTest
```

### ۳) فهرست کلاس‌های تست

```bash
php tests/run.php --list
```

### ۴) کمک

```bash
php tests/run.php --help
```

## پیش‌نیازها

- **PHP 8.0 یا بالاتر** (تست شده با PHP 8.5)
- **MySQL 5.7+ یا MariaDB 10.4+** برای تست‌های کامل
  - یا فقط PHP برای تست‌های static analysis (بدون نیاز به DB)

### تنظیم پایگاه دادهٔ تست (اختیاری)

برای اجرای تست‌های DB-level، یک دیتابیس آزمایشی بسازید:

```bash
mysql -u root -p -e "CREATE DATABASE handicraft_license_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# اجرای تست با MySQL:
TEST_DB_DRIVER=mysql \
TEST_DB_HOST=127.0.0.1 \
TEST_DB_NAME=handicraft_license_test \
TEST_DB_USER=root \
TEST_DB_PASS=yourpassword \
php tests/run.php
```

اگر MySQL در دسترس نباشد، تست‌ها به‌صورت **static analysis** (بدون نیاز به DB) اجرا می‌شوند
و تست‌های DB با علامت `[SKIP]` نمایش داده می‌شوند.

## ساختار

```
tests/
├── bootstrap.php          # بوت‌استرپ: ثابت‌ها، autoloader، اتصال DB
├── TestCase.php           # کلاس پایهٔ تست با assertion ها
├── run.php                # اجرای تست‌ها (runner اصلی)
├── ConfigTest.php         # پیکربندی: ثابت‌ها، .env.example، schema.sql
├── SecurityTest.php       # امنیت: robots.txt, .htaccess, session, CSRF, IP blocklist
├── ServiceScopeTest.php   # اختصاصی‌سازی رشته‌ها به هر سرویس (۱/۲/۳)
├── StatusWorkflowTest.php # گردش کار وضعیت‌ها، ماتریس انتقال، terminal/pre_issue
├── LicenseTitleTest.php   # CRUD رشته‌های مجوز، یکتایی در دامنهٔ سرویس
├── WizardFormTest.php     # مراحل ویزارد، فیلدهای پویا، مدل‌ها
└── FunctionalTest.php     # روت‌ها، کنترلرها، اعتبارسنجی، CSRF
```

## پوشش تست‌ها

### SecurityTest (۳۲ تست)
- `robots.txt`: مسدودسازی Googlebot, Bingbot, GPTBot, ChatGPT-User, CCBot, anthropic-ai,
  Claude-Web, cohere-ai, Google-Extended, PerplexityBot, Amazonbot, Bytespider, Diffbot,
  Meta-ExternalAgent, OAI-SearchBot, Applebot-Extended
- `.htaccess`: X-Robots-Tag, X-Frame-Options, CSP, Referrer-Policy, Permissions-Policy,
  مسدودسازی فایل‌های حساس (.env, .sql, .bak, .log)
- متاتگ‌های noindex در همهٔ layoutها (layout.php, admin_layout.php, user_layout.php, layout_print.php)
- هدرهای امنیتی در `public/index.php`
- session hardening: strict_mode, only_cookies, sid_length=40, httponly, samesite
- CSRF: تولید توکن با random_bytes(32)، مقایسهٔ زمان‌ثابت با hash_equals
- Rate limiting: متدهای hit, isLimited, retryAfter
- ADMIN_IP_ALLOWLIST و IP_BLOCKLIST در Controller
- Validator: prepared statements، تمام ruleها موجودند

### ConfigTest (۱۱ تست)
- ثابت‌های اصلی تعریف شده‌اند (APP_VERSION, DB_HOST, ...)
- نسخهٔ SemVer معتبر است
- `.env.example` شامل همهٔ کلیدهای مورد نیاز است
- `.gitignore` از `.env` و `uploads/*` محافظت می‌کند
- `schema.sql` وجود دارد و شامل جداول اصلی است
- همهٔ مهاجرت‌ها موجودند و idempotent هستند
- پیکربندی workflow.php معتبر است
- پیکربندی statuses.php شامل وضعیت‌های اختصاصی مشاغل خانگی است
- تابع کمکی `asset_url()` موجود است

### ServiceScopeTest (۲۲ تست)
- سرویس ۱ (صنایع دستی): حداقل ۵۰ رشته در schema.sql
- سرویس ۲ (مشاغل خانگی مستقل): حداقل ۳۰ رشته
- سرویس ۳ (پشتیبان): حداقل ۲۰ رشته
- دسته‌های سرویس‌ها متمایز هستند (هیچ هم‌پوشانی)
- ۵ سرویس در `service_types` بذر شده‌اند
- مدل `LicenseTitle` متدهای `groupedActiveByService`, `isTitleUniqueInService` را دارد
- کنترلر `LicenseTitleController` متد `byService` و فیلتر سرویس دارد
- مسیر `/admin/license-titles/by-service/{id}` ثبت شده است
- سایر کنترلرها (Registration, Admin, BulkImport, Revision) از مدل service-aware استفاده می‌کنند

### StatusWorkflowTest (۱۹ تست)
- همهٔ وضعیت‌های هسته (۱۴ وضعیت) موجودند
- وضعیت‌های اختصاصی مشاغل خانگی (۴ وضعیت) موجودند
- کل تعداد وضعیت‌ها ≥ ۱۸
- وضعیت‌های اختصاصی فقط برای service_type_id=2 برچسب‌گذاری شده‌اند
- وضعیت‌های مشترک `services` ندارند
- terminal درست است (شامل: رد درخواست، لغو، تحویل مجوز، تأیید نهایی دریافت کد رهگیری)
- pre_issue درست است (شامل وضعیت‌های پیش از صدور)
- هیچ وضعیتی هم terminal و هم pre_issue نیست
- همهٔ وضعیت‌ها `order` و `color` دارند
- ماتریس انتقال برای نقش‌های expert, document_uploader, admin معتبر است
- admin هیچ محدودیتی ندارد
- همهٔ وضعیت‌ها در schema.sql (ENUM) موجودند

### LicenseTitleTest (۲۰ تست)
- مدل قابل نمونه‌سازی است
- تمام متدها موجودند: groupedActiveByService, getAll, getCategories, findById,
  create, update, delete, isTitleUniqueInService
- متد deprecated `groupedActive` برای backward-compat نگه‌داشته شده
- signatureها درست هستند (nullable params)
- source code از service_type_id در queryها استفاده می‌کند
- delete در صورت استفاده در پرونده، soft-delete می‌کند
- getAll با LEFT JOIN service_types انجام می‌شود
- تست‌های DB: یکتایی عنوان، فیلتر سرویس

### WizardFormTest (۱۸ تست)
- چهار مرحلهٔ سیستمی (step1..step4) موجودند
- مرحلهٔ اختصاصی سرویس ۳ (اطلاعات پشتیبان) موجود است
- سه فیلد الزامی پشتیبان موجودند (dyn_justification_plan, dyn_covered_persons, dyn_income_details)
- مراحل اختصاصی سرویس ۲ (اطلاعات طرح و شغل، اطلاعات مالی، محل فعالیت شخصی) موجودند
- ۲۶ فیلد پویای dyn_* برای سرویس ۲ موجودند
- مدل‌های WizardStep و FormField و ApplicantFieldValue موجودند
- مدل WizardStep متد orderedActive دارد
- مدل FormField متدهای activeByStepId و systemActiveMap دارد
- کنترلر RegistrationController متدهای selectService و selectedServiceId دارد
- سیستم AutoLoader همهٔ مدل‌ها و کنترلرها را پیدا می‌کند

### FunctionalTest (۳۸ تست)
- روت‌های عمومی: /, /guide, /about, /health
- احراز هویت: /signup, /login, /logout
- پنل کاربری: /dashboard, /panel, /panel/profile, /panel/requests, /panel/payments,
  /panel/licenses, /panel/notifications, /panel/tracking
- ویزارد ثبت‌نام: /register, /register/service/{id}, /register/step/{n}, /register/success/{trackingCode}
- پیگیری: /tracking, /tracking/search, /tracking/status/{trackingCode},
  /tracking/change-postal-code, /tracking/print/{trackingCode}
- پنل مدیریت: /admin/login, /admin/logout, /admin/dashboard, /admin/applications,
  /admin/view/{id}, /admin/change-status
- گزارش‌ها: by-province, by-city, by-village, by-license, by-date
- خروجی: /admin/output/general, /admin/output/single/{trackingCode}, /admin/output/grouped
- مدیریت رشته‌ها: /admin/license-titles + save/delete/toggle/by-service
- ایمپورت دسته‌جمعی: /admin/bulk-import
- مدارک: /document/view, /document/avatar, /document/thumb/{id}
- endpoint cron: /check-cancel با CRON_SECRET
- کنترلرها متدهای مورد انتظار را دارند
- Validator: کد ملی، موبایل، کد پستی، required
- CSRF: توکن ثابت در نشست، رد توکن نامعتبر

## خروجی نمونه

```
═══════════════════════════════════════════════════════════════
  ▶ SecurityTest
═══════════════════════════════════════════════════════════════
  • testRobotsTxtExists ... ✓ PASS
  • testRobotsTxtBlocksAllAgents ... ✓ PASS
  • testRobotsTxtBlocksAiBots ... ✓ PASS
  ...
  • testValidatorUsesPreparedStatements ... ✓ PASS
  • testDatabaseIsSingleton ... ✓ PASS

═══════════════════════════════════════════════════════════════
  خلاصهٔ نهایی
═══════════════════════════════════════════════════════════════
  PASS: 160  |  FAIL: 0
```

## نکات

- تست‌ها به‌صورت **idempotent** هستند — اجرای چندباره هیچ اثر جانبی ندارد
- تست‌های DB در صورت نبود MySQL با `[SKIP]` نمایش داده می‌شوند
- اگر تستی شکست خورد، پیام دقیق خطا نمایش داده می‌شود
- کد خروجی: `0` در صورت موفقیت همه، `1` در صورت شکست حداقل یک تست
