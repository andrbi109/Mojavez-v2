# طرح بازنویسی Mojavez v2

> **مخزن هدف:** `https://github.com/andrbi109/Mojavez-v2.git`
> **مبنا:** کلون از `https://github.com/andrbi109/Mojavez.git` در کامیت `ad133b5 v0.7.2`
> **استراتژی:** هر فاز در شاخهٔ جدا، تست syntax با python، تست دستی در پایان فاز ۱۳.
> **مرجع یافته‌ها:** `download/گزارش-جامع-تحلیل-پروژه-Mojavez.md` (۴۵ یافته، ۱۳ HIGH)

---

## فهرست فازها

### فاز ۱ — حفره‌های امنیتی (HIGH) — شاخه `fix/security`
- **S-1**: `APP_DEBUG=true` → env-based (default false)
- **S-2**: `DB_USER=root` / `DB_PASS=` → env-based
- **S-10**: `display_errors` بر اساس `APP_DEBUG` (به‌طور خودکار با S-1 حل می‌شود)
- **S-4**: `/tracking/print/{code}` بدون احراز هویت → rate limit + optional auth
- **S-5**: `GET /check-cancel` بدون احراز هویت → shared-secret یا IP allowlist

### فاز ۲ — یکپارچگی داده (HIGH) — شاخه `fix/data-integrity`
- **S-9**: `Person::mobileBelongsToOtherPerson` روی `applicants` کوئری می‌زند نه `persons`. اصلاح: کوئری روی `persons.mobile`, `persons.mobile_2`, `persons.mobile_3`.
- **D-C-3**: همراه با S-9.

### فاز ۳ — جریان وضعیت (HIGH) — شاخه `fix/status-flow`
- **D-A-4**: ایمپورت دسته‌جمعی متقاضیان در `'پیش‌نویس'` می‌مانند → افزودن `Applicant::completeRegistration()` و `StatusLog::create()`.
- **D-E-1**: `SmsController::reviewEntry` از `workflow.php` عبور می‌کند → اعمال قواعد `workflow.php['transitions'][$role]`.
- **D-G-2**: همراه با D-E-1.

### فاز ۴ — استخراج `ApplicantRegistrationService` (HIGH) — شاخه `refactor/registration-service`
- **D-A-1**: کپی ۴ بارهٔ زنجیرهٔ اعتبارسنجی هویتی.
- **D-A-2**: کپی ۸ بارهٔ کلوژر `$saveFile`.
- **D-A-4**: همراه با فاز ۳.
- **D-A-5**: نوتیفیکیشن کاربر فقط در ایمپورت — اضافه کردن به همه.
- **D-A-6**: شخص حقوقی فقط در ویزارد — اضافه کردن به مدیر.
- **D-C-1**: ایمپورت از `Person::findOrCreate` عبور می‌کند.
- **D-C-2**: persons های ایمپورت‌شده دادهٔ ناقص دارند.

### فاز ۵ — استخراج `PaymentReceiptService` (HIGH) — شاخه `refactor/payment-service`
- **D-D-1**: دو جریان موازی ثبت رسید (۸۰ خط تکراری).
- **D-D-2**: سه شکل مختلف INSERT در `payments`.
- **D-D-3**: `PaymentController::review` ممیزی نمی‌نویسد.

### فاز ۶ — مرکزی‌سازی doc-type و فایل‌نام (HIGH) — شاخه `refactor/document-types`
- **D-A-3**: ۵ فهرست مجاز doc-type متناقض.
- **D-F-1**: همراه با D-A-3.
- **D-F-2**: دو الگوی نام‌گذاری فایل موازی.

### فاز ۷ — اصلاح RevisionController (HIGH) — شاخه `fix/revision-approve`
- **D-B-1**: بازبینی و ویرایش مدیر با اعتبارسنجی متفاوت.
- **D-B-2**: `RevisionController::approve` منطق `updateApplication` را تکرار می‌کند.

### فاز ۸ — اصلاح نقش‌ها در مدیریت مدارک (MEDIUM) — شاخه `fix/document-roles`
- **D-F-4**: `addDocument` از `checkAuth` ولی `deleteDocument` از `requireRole(['admin'])`.
- **D-J-4**: همراه با D-F-4.

### فاز ۹ — پاک‌سازی کد مرده (LOW) — شاخه `cleanup/dead-code`
- حذف `UserDashboardController.php` (۱۰۱ خط).
- حذف `app/Views/user/dashboard.php` (۲۷۰ خط).
- حذف یا بازگردانی مسیر `DocumentController::serve`.
- حذف کلوژر کامنت‌شده و مسیر تکراری `GET /`.
- حذف کد مرده در `_dynamic_values.php` (شاخه `'admin'`).
- بررسی ۱۳ متد مدل احتمالاً مرده.

### فاز ۱۰ — Schema و نسخه (MEDIUM) — شاخه `fix/schema-idempotency`
- **C-5**: ۱۵ ALTER TABLE غیر idempotent → افزودن محافظ `information_schema`.
- **C-6**: ۲ INSERT غیر idempotent → `INSERT … SELECT … WHERE NOT EXISTS`.
- **C-1**: `APP_VERSION` از `0.5.5` به `2.0.0`.
- **C-2**: `APP_VERSION_NAME` به‌روزرسانی.
- **C-7**: مستندسازی یا حذف مهاجرت تکراری.

### فاز ۱۱ — اولویت‌های MEDIUM و LOW باقی‌مانده — شاخه `cleanup/misc`
- **D-B-3**: UI ویرایش شخص حقوقی.
- **D-B-5**: `UserPanelController::updateProfile` فقط ایمیل را ویرایش می‌کند.
- **D-D-4**: کش `FeeCalculatorService`.
- **D-E-2**: ۴۵+ رشتهٔ وضعیت هاردکد.
- **D-E-5**: `Applicant::updateStatus` بسیار باز.
- **D-G-1**: اتصال `UserNotification` به ویزارد.
- **D-G-3**: ۵ ماشین وضعیت موازی.
- **D-H-2**: دو لایهٔ اعتبارسنجی موازی.
- **D-H-5**: کش `setting()`.
- **D-I-3**: استثنای صریح CSV برای `MAX_UPLOAD_SIZE`.
- **C-3**: حذف `SERVICES_PATH`.
- **C-4**: همراه با D-I-3.
- **D-C-4**: `Person::syncFromApplicant` یک‌طرفه.
- **D-C-5**: یکسان‌سازی `linkApplication`.
- **D-D-5**: `PaymentServiceTick::createForPayment` از `$_SESSION` می‌خواند.
- **D-D-6**: `linked_request_id` فقط برای license-reissue.
- **D-F-5**: پاک‌سازی `revision_doc` موقت.
- **D-G-4**: نام `SmsController` گمراه‌کننده.
- **D-H-3**: `get_upload_path` فقط در یک جا.
- **D-H-4**: ۵۰+ SVG در `icon_paths`.
- **D-J-7**: rate limit برای `/license/view/{token}`.

### فاز ۱۲ — اصلاحات view
- **§5.4**: `admin/fee_tariff.php` باید از `admin_layout` استفاده کند.
- **D-A-6 (پایان)**: افزودن فیلدهای حقوقی به `admin/register.php` و `admin/edit_application.php`.

### فاز ۱۳ — تست و تأیید نهایی
- اجرای تست syntax روی همهٔ فایل‌ها.
- تست دستی روی هر ۱۳ مسئلهٔ HIGH.
- به‌روزرسانی `APP_VERSION` به `2.0.0`.
- merge همهٔ شاخه‌ها به `main`.
- tag `v2.0.0`.

---

## وضعیت فعلی

- [x] مخزن محلی ساخته شد: `/home/z/my-project/Mojavez-v2/`
- [x] remoteها تنظیم شدند:
  - `origin` → مخزن جدید GitHub (منتظر URL)
  - `origin-legacy` → مخزن قدیم (فقط-خواندنی)
- [x] این طرح مکتوب شد.
- [ ] فاز ۱: شاخهٔ `fix/security` ساخته شود.

---

## قواعد کاری

1. **هیچ فایلی بدون بررسی قبلی حذف نمی‌شود.**
2. **هر کامیت با syntax check اعتبارسنجی می‌شود.**
3. **هر شاخه قبل از merge به main بررسی می‌شود.**
4. **تغییرات schema همیشه idempotent می‌شوند.**
5. **هیچ کلید/رمز/توکنی در کد قرار نمی‌گیرد.**
6. **پیام کامیت به فارسی، با پیشوند شماره فاز.**

---

**آمادهٔ شروع فاز ۱ به‌محض دریافت تأیید یا URL مخزن جدید.**
