# پایگاه دادهٔ Mojavez

این پوشه شامل همهٔ فایل‌های SQL لازم برای راه‌اندازی و نگهداری پایگاه دادهٔ سامانه است.

## فایل‌ها

### 📦 فایل اصلی نصب (توصیه می‌شود)

| فایل | کاربرد |
|---|---|
| **`install.sql`** | **نصب تازه** — یک فایل واحد شامل همهٔ جداول، داده‌های اولیه و محدودیت‌ها. نیازی به مهاجرت‌ها نیست. |

این فایل برای:
- نصب روی هاست اشتراکی (از طریق `install.php` یا phpMyAdmin)
- نصب روی سرور اختصاصی (با دستور `mysql`)
- نصب در محیط Docker

استفاده می‌شود.

### 🌱 فایل‌های کمکی

| فایل | کاربرد |
|---|---|
| `seed-admin.sql` | ساخت حساب مدیر پیش‌فرض (نام کاربری: `admin`، رمز: `Mojavez@2026`) |

### 📚 فایل‌های مرجع

| فایل | کاربرد |
|---|---|
| `schema.sql` | فایل مرجع کامل — شامل همهٔ جداول و داده‌های اولیه به‌صورت تکاملی (با `ALTER TABLE`). این فایل برای توسعه‌دهندگان است. |

### 🔄 مهاجرت‌ها (برای ارتقا از نسخه‌های قدیمی)

پوشهٔ `migrations/` شامل مهاجرت‌های قدیمی برای ارتقا از نسخه‌های قبلی است. **اگر از ابتدا با `install.sql` نصب می‌کنید، نیازی به اجرای این مهاجرت‌ها ندارید.**

| مهاجرت | کاربرد |
|---|---|
| `20251109_v2_upgrade.sql` | ارتقا از v0.x به v2.0 (افزودن mobile_2, mobile_3) |
| `20251110_household_independent_steps.sql` | افزودن مراحل اختصاصی مشاغل خانگی مستقل |
| `20251111_household_status_enum.sql` | افزودن وضعیت‌های اختصاصی مشاغل خانگی به ENUM |
| `20251112_license_titles_update.sql` | به‌روزرسانی فهرست رشته‌های مجوز |
| `20251113_license_titles_service_specific.sql` | اختصاصی‌سازی رشته‌ها به هر سرویس |

---

## نحوهٔ نصب

### روش ۱: نصب تازه با `install.sql` (توصیه می‌شود)

#### روی سرور اختصاصی / VPS

```bash
# ساخت دیتابیس
mysql -u root -p -e "CREATE DATABASE mojavez CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# ساخت کاربر
mysql -u root -p -e "CREATE USER 'mojavez'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD'; GRANT ALL ON mojavez.* TO 'mojavez'@'localhost'; FLUSH PRIVILEGES;"

# اجرای install.sql
mysql -u mojavez -p mojavez < database/install.sql

# ساخت حساب مدیر
mysql -u mojavez -p mojavez < database/seed-admin.sql
```

#### روی هاست اشتراکی (cPanel / DirectAdmin)

۱) در cPanel → **MySQL Database Wizard** یک دیتابیس بسازید

۲) در cPanel → **phpMyAdmin** → دیتابیس خود را انتخاب کنید → **Import** → فایل `database/install.sql` را آپلود کنید

۳) مجدداً **Import** → فایل `database/seed-admin.sql` را آپلود کنید

#### با `install.php` وب (ساده‌ترین روش)

۱) فایل `deploy/shared-hosting/install.php` را در `public_html/` کپی کنید

۲) با مرورگر به `https://your-domain.com/install.php` بروید

۳) اطلاعات دیتابیس را وارد کنید — `install.sql` به‌صورت خودکار اجرا می‌شود

۴) فایل `install.php` را حذف کنید

۵) برای ساخت حساب مدیر، فایل `database/seed-admin.sql` را در phpMyAdmin اجرا کنید

---

### روش ۲: نصب با Docker

```bash
# از روی docker-compose
docker-compose up -d

# کانتینر app به‌صورت خودکار schema.sql را اجرا می‌کند
```

---

## پس از نصب

### ورود به پنل مدیریت

۱) به `https://your-domain.com/admin/login` بروید

۲) با حساب پیش‌فرض وارد شوید:
   - **نام کاربری**: `admin`
   - **رمز عبور**: `Mojavez@2026`

۳) ⚠️ **بلافاصله رمز را تغییر دهید!**

### بررسی سلامت سامانه

```
https://your-domain.com/health
```

باید برگرداند:
```json
{"status":"healthy","checks":{"db":"ok","version":"0.6.2","time":"..."}}
```

---

## ساختار پایگاه داده

### جداول اصلی (۲۹ جدول)

| دسته | جداول |
|---|---|
| احراز هویت | `users`, `user_accounts`, `persons` |
| پرونده‌ها | `applicants`, `job_details`, `property_details`, `documents` |
| وضعیت‌ها | `status_log`, `postal_code_changes` |
| خدمات | `service_types`, `license_titles`, `wizard_steps`, `form_fields`, `applicant_field_values` |
| پرداخت | `payment_channels`, `payments`, `payment_service_ticks`, `payment_audit_log`, `fee_groups`, `fee_group_items` |
| مجوزها | `license_files`, `license_access_requests` |
| بازبینی | `applicant_revisions` |
| پیامک | `sms_templates`, `user_sms_entries` |
| تنظیمات | `settings` |
| اعلان‌ها | `user_notifications` |

### داده‌های اولیه

- **۵ سرویس** (صنایع دستی، مشاغل خانگی مستقل، پشتیبان، گردشگری، هنری)
- **۱۸ وضعیت** (۱۴ مشترک + ۴ اختصاصی مشاغل خانگی)
- **۲۳۰+ رشتهٔ مجوز** (۱۴۰+ صنایع دستی، ۵۳+ مشاغل خانگی، ۳۷+ پشتیبان)
- **۲۸ فیلد فرم** سیستمی + اختصاصی
- **۴ گروه قیمتی** با ۱۲ خدمت در هر گروه
- **قالب پیامک** و تنظیمات پایه

---

## پشتیبان‌گیری

```bash
# بک‌آپ کامل
mysqldump -u USER -p DATABASE > backup_$(date +%Y%m%d).sql

# فقط داده‌ها (بدون schema)
mysqldump -u USER -p --no-create-info DATABASE > data_backup.sql

# فقط schema (بدون داده)
mysqldump -u USER -p --no-data DATABASE > schema_backup.sql
```

---

## بازیابی

```bash
# بازیابی از بک‌آپ
mysql -u USER -p DATABASE < backup_20260101.sql
```

---

## رفع مشکل

### خطای "Table already exists"

`install.sql` از `CREATE TABLE IF NOT EXISTS` استفاده می‌کند، بنابراین اجرای مجدد خطا نمی‌دهد. اگر خطا گرفتید، احتمالاً جدول با ساختار متفاوت موجود است — جدول را drop کنید و دوباره اجرا کنید.

### خطای "Foreign key constraint"

```sql
SET FOREIGN_KEY_CHECKS = 0;
-- اجرای install.sql
SET FOREIGN_KEY_CHECKS = 1;
```

### خطای "Unknown column"

این خطا نشان می‌دهد که نسخهٔ قدیمی دیتابیس دارید. `install.sql` را روی یک دیتابیس خالی اجرا کنید، یا مهاجرت‌های مربوطه را اجرا کنید.

### کاراکترست

همهٔ جداول با `utf8mb4` و `utf8mb4_unicode_ci` ساخته می‌شوند. اگر کاراکترهای فارسی به‌هم ریخت:
```sql
ALTER DATABASE mojavez CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
