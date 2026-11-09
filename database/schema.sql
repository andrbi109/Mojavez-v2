-- =================================================================
-- سامانهٔ مدیریت ثبت‌نام و پیگیری صدور مجوز صنایع دستی
-- MySQL/MariaDB — نصب تازه با اجرای همین یک فایل انجام می‌شود
-- -----------------------------------------------------------------
-- این فایل یکپارچه است و شامل همهٔ جداول، داده‌های اولیه و
-- محدودیت‌ها است. نیازی به اجرای مهاجرت‌های جداگانه نیست.
--
-- ویژگی‌ها:
--   ✓ بدون ALTER TABLE — همهٔ ستون‌ها مستقیماً در CREATE TABLE
--   ✓ بدون SET @ / PREPARE / EXECUTE / DEALLOCATE
--   ✓ Idempotent — CREATE TABLE IF NOT EXISTS + ON DUPLICATE KEY UPDATE
--   ✓ سازگار با MySQL 5.7+، MariaDB 10.3+
--   ✓ کاراکترست utf8mb4 با collation utf8mb4_unicode_ci
--   ✓ شامل ۲۹ جدول، ۵ سرویس، ۱۸ وضعیت، ۲۳۰+ رشتهٔ مجوز
--   ✓ شامل ۲۸ فیلد فرم سیستمی و اختصاصی، ۴ گروه قیمتی با ۱۲ خدمت
--
-- نحوهٔ اجرا:
--   mysql -u <user> -p < database/schema.sql
--   یا در phpMyAdmin: Import → انتخاب این فایل → Go
--
-- برای ساخت حساب مدیر، پس از اجرای این فایل، فایل seed-admin.sql
-- را اجرا کنید.
--
-- طراح و توسعه‌دهنده: امین دروگر بزدی (Derogaramin@)
-- نسخهٔ پروژه: 0.6.3
-- =================================================================

CREATE DATABASE IF NOT EXISTS handicraft_license
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE handicraft_license;

-- برای جلوگیری از خطای ترتیب FOREIGN KEY هنگام نصب تازه
SET FOREIGN_KEY_CHECKS = 0;

-- ================================================================
-- ۱. جدول مدیران سیستم
-- ================================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'admin',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۲. جدول اشخاص (لنگر هویت = کد ملی)
-- ================================================================
CREATE TABLE IF NOT EXISTS persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    national_code CHAR(10) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    father_name VARCHAR(50) NULL,
    birth_date DATE NULL,
    mobile CHAR(11) NOT NULL,
    mobile_2 CHAR(11) NULL,
    mobile_3 CHAR(11) NULL,
    postal_code CHAR(10) NULL,
    person_type ENUM('حقیقی','حقوقی') NOT NULL DEFAULT 'حقیقی',
    -- اطلاعات شخص حقوقی
    company_name VARCHAR(150) NULL,
    company_national_id CHAR(11) NULL,
    reg_number VARCHAR(30) NULL,
    reg_date DATE NULL,
    company_type VARCHAR(60) NULL,
    economic_code VARCHAR(20) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_person_mobile (mobile),
    INDEX idx_person_mobile_2 (mobile_2),
    INDEX idx_person_mobile_3 (mobile_3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۳. جدول سرویس‌ها (نوع خدمت)
-- ================================================================
CREATE TABLE IF NOT EXISTS service_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL UNIQUE,
    authority VARCHAR(150) NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۴. جدول متقاضیان (اطلاعات اصلی)
-- ================================================================
CREATE TABLE IF NOT EXISTS applicants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NULL,
    parent_application_id INT NULL,
    service_type_id INT NULL,
    user_account_id INT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    national_code CHAR(10) NOT NULL,
    postal_code CHAR(10) NOT NULL,
    mobile CHAR(11) NOT NULL,
    tracking_code VARCHAR(20) NOT NULL UNIQUE,
    birth_date DATE NULL,
    father_name VARCHAR(50) NULL,
    current_status ENUM(
        'پیش‌نویس',
        'تکمیل اطلاعات',
        'احراز نشانی',
        'بررسی کارشناسی رشته',
        'ثبت درخواست صدور',
        'بارگذاری مدارک به سازمان',
        'درخواست اصلاح مدارک',
        'تأیید اولیه',
        'تأیید نهایی',
        'صدور مجوز',
        'تحویل مجوز',
        'مشاهده و چاپ',
        'رد درخواست',
        'لغو',
        'تطبیق آدرس با ثبت احوال',
        'دریافت تصویر مجوز',
        'تأیید اولیه در سامانه مشاغل خانگی',
        'تأیید نهایی دریافت کد رهگیری'
    ) DEFAULT 'پیش‌نویس',
    applicant_type ENUM('حقیقی','حقوقی') NOT NULL DEFAULT 'حقیقی',
    marital_status ENUM('مجرد','متأهل') DEFAULT 'مجرد',
    children_count TINYINT UNSIGNED DEFAULT 0,
    physical_condition ENUM('سالم','معلولیت') DEFAULT 'سالم',
    disability_details VARCHAR(255) NULL,
    income_decile TINYINT UNSIGNED NULL,
    has_court_case ENUM('بله','خیر') DEFAULT 'خیر',
    has_active_loan ENUM('بله','خیر') DEFAULT 'خیر',
    has_overdue_installments ENUM('بله','خیر') DEFAULT 'خیر',
    priority TINYINT(1) DEFAULT 0,
    payment_required TINYINT(1) NOT NULL DEFAULT 0,
    payment_requested_at DATETIME NULL,
    license_state ENUM('جاری','صادر شده','باطل شده','منقضی') NOT NULL DEFAULT 'جاری',
    license_valid_until DATE NULL,
    registration_started_at DATETIME NULL,
    registration_completed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_application_id) REFERENCES applicants(id) ON DELETE SET NULL,
    FOREIGN KEY (service_type_id) REFERENCES service_types(id) ON DELETE SET NULL,
    INDEX idx_tracking_code (tracking_code),
    INDEX idx_national_code (national_code),
    INDEX idx_status (current_status),
    INDEX idx_priority (priority),
    INDEX idx_mobile (mobile),
    INDEX idx_completed_priority (registration_completed_at, priority),
    INDEX idx_person (person_id),
    INDEX idx_parent_app (parent_application_id),
    INDEX idx_license_state (license_state),
    INDEX idx_applicant_type (applicant_type),
    INDEX idx_applicant_user (user_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۵. جدول اطلاعات شغلی (۱:۱ با applicants)
-- ================================================================
CREATE TABLE IF NOT EXISTS job_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    license_title VARCHAR(100) NOT NULL,
    experience_months INT NOT NULL,
    training_method ENUM('در خانه', 'استاد شاگردی', 'کارگاهی') NOT NULL,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_applicant_job (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۶. جدول اطلاعات ملک (۱:۱ با applicants)
-- ================================================================
CREATE TABLE IF NOT EXISTS property_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    ownership_status ENUM('شخص', 'مستأجر', 'سرپرست') NOT NULL,
    province VARCHAR(50) NOT NULL,
    county VARCHAR(50) NOT NULL,
    city VARCHAR(50) NOT NULL,
    district VARCHAR(50) NULL,
    rural_district VARCHAR(50) NULL,
    village VARCHAR(50) NULL,
    electricity_bill_id VARCHAR(20) NOT NULL,
    address_street VARCHAR(100) NULL,
    address_alley VARCHAR(100) NULL,
    address_plaque VARCHAR(20) NULL,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_applicant_property (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۷. جدول مدارک (۱:N با applicants)
-- ================================================================
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    doc_type VARCHAR(50) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    INDEX idx_applicant_docs (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۸. جدول تاریخچه وضعیت‌ها
-- ================================================================
CREATE TABLE IF NOT EXISTS status_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    status_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    INDEX idx_applicant_log (applicant_id),
    INDEX idx_status_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۹. جدول تاریخچه تغییرات کد پستی
-- ================================================================
CREATE TABLE IF NOT EXISTS postal_code_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    old_postal_code CHAR(10) NOT NULL,
    new_postal_code CHAR(10) NOT NULL,
    status ENUM('در انتظار','تأیید شده','رد شده') NOT NULL DEFAULT 'در انتظار',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    reject_reason VARCHAR(255) NULL,
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_applicant_postal (applicant_id),
    INDEX idx_postal_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۱۰. تنظیمات کلید/مقداری سامانه
-- ================================================================
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(64) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۱۱. مراحل ویزارد
-- ================================================================
CREATE TABLE IF NOT EXISTS wizard_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    view_key VARCHAR(30) NULL,
    title VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    icon VARCHAR(30) NULL,
    service_type_id INT NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 50,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_type_id) REFERENCES service_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۱۲. فیلدهای فرم ثبت‌نام
-- ================================================================
CREATE TABLE IF NOT EXISTS form_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    step TINYINT NOT NULL,
    wizard_step_id INT NULL,
    service_type_id INT NULL,
    field_key VARCHAR(64) NOT NULL UNIQUE,
    label VARCHAR(150) NOT NULL,
    field_type VARCHAR(30) NOT NULL DEFAULT 'text',
    options TEXT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    validation_json TEXT NULL,
    placeholder VARCHAR(150) NULL,
    help_text VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wizard_step_id) REFERENCES wizard_steps(id) ON DELETE SET NULL,
    FOREIGN KEY (service_type_id) REFERENCES service_types(id) ON DELETE CASCADE,
    INDEX idx_step_active (step, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۱۳. مقادیر فیلدهای پویا برای هر متقاضی
-- ================================================================
CREATE TABLE IF NOT EXISTS applicant_field_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    field_id INT NOT NULL,
    field_value TEXT NULL,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES form_fields(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_applicant_field (applicant_id, field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۱۴. رشته‌های مجوز (اختصاصی هر سرویس)
-- ================================================================
CREATE TABLE IF NOT EXISTS license_titles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_type_id INT NULL,
    category VARCHAR(80) NOT NULL,
    title VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_type_id) REFERENCES service_types(id) ON DELETE CASCADE,
    UNIQUE INDEX uniq_service_title (service_type_id, title),
    INDEX idx_active_sort (is_active, sort_order),
    INDEX idx_active_service_sort (service_type_id, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۱۵. کانال‌های پرداخت
-- ================================================================
CREATE TABLE IF NOT EXISTS payment_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    channel_type ENUM('کارت به کارت','حساب بانکی','پرداخت اینترنتی','سایر') NOT NULL DEFAULT 'کارت به کارت',
    account_info TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۱۶. درخواست‌های دسترسی به تصویر مجوز
--    (قبل از payments ساخته می‌شود تا FK از payments به این جدول برقرار شود)
-- ================================================================
CREATE TABLE IF NOT EXISTS license_access_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    is_first_access TINYINT(1) NOT NULL DEFAULT 0,
    access_token CHAR(64) NULL UNIQUE,
    requires_payment TINYINT(1) NOT NULL DEFAULT 0,
    amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
    payment_id INT NULL,
    status ENUM('در انتظار','تأیید شده','رد شده','در انتظار پرداخت','منقضی') NOT NULL DEFAULT 'در انتظار',
    expires_at DATETIME NULL,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    reject_reason VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lar_status (status),
    INDEX idx_lar_applicant (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۱۷. پرداخت‌های متقاضیان
-- ================================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    user_account_id INT NULL,
    payment_channel_id INT NULL,
    purpose ENUM('هزینهٔ ثبت‌نام','هزینهٔ دریافت مجدد تصویر مجوز') NOT NULL DEFAULT 'هزینهٔ ثبت‌نام',
    amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
    original_amount BIGINT UNSIGNED NULL,
    final_amount BIGINT UNSIGNED NULL,
    discount_reason VARCHAR(500) NULL,
    fee_group_id INT NULL,
    is_rural_exempt TINYINT(1) NOT NULL DEFAULT 0,
    fallback_used TINYINT(1) NOT NULL DEFAULT 0,
    suspicion_flag VARCHAR(255) NULL,
    reference_code VARCHAR(50) NULL,
    paid_at DATE NULL,
    receipt_document_id INT NULL,
    linked_request_id INT NULL,
    status ENUM('در انتظار رسید','در انتظار بررسی','تأیید شده','رد شده') NOT NULL DEFAULT 'در انتظار رسید',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    reject_reason VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_account_id) REFERENCES user_accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (payment_channel_id) REFERENCES payment_channels(id) ON DELETE SET NULL,
    FOREIGN KEY (receipt_document_id) REFERENCES documents(id) ON DELETE SET NULL,
    FOREIGN KEY (linked_request_id) REFERENCES license_access_requests(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pay_status (status),
    INDEX idx_pay_applicant (applicant_id),
    INDEX idx_pay_purpose (purpose),
    INDEX idx_pay_user (user_account_id),
    INDEX idx_pay_group (fee_group_id),
    INDEX idx_pay_suspicion (suspicion_flag(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۱۸. تصویر مجوز هر پرونده
-- ================================================================
CREATE TABLE IF NOT EXISTS license_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL UNIQUE,
    document_id INT NOT NULL,
    download_count INT NOT NULL DEFAULT 0,
    first_accessed_at DATETIME NULL,
    issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۱۹. الگوهای پیامک
-- ================================================================
CREATE TABLE IF NOT EXISTS sms_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    sample_text TEXT NULL,
    target_status VARCHAR(50) NOT NULL,
    requires_code TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۲۰. پیامک‌های ثبت‌شده توسط متقاضیان
-- ================================================================
CREATE TABLE IF NOT EXISTS user_sms_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    sms_template_id INT NOT NULL,
    sms_code VARCHAR(50) NULL,
    sms_text TEXT NULL,
    received_at DATE NULL,
    status ENUM('در انتظار','تأیید شده','رد شده') NOT NULL DEFAULT 'در انتظار',
    applied_status TINYINT(1) NOT NULL DEFAULT 0,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    reject_reason VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (sms_template_id) REFERENCES sms_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sms_status (status),
    INDEX idx_sms_applicant (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۲۱. درخواست‌های بازبینی پرونده
-- ================================================================
CREATE TABLE IF NOT EXISTS applicant_revisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    payload JSON NOT NULL,
    note VARCHAR(500) NULL,
    status ENUM('در انتظار','تأیید شده','رد شده') NOT NULL DEFAULT 'در انتظار',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    reject_reason VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_rev_status (status),
    INDEX idx_rev_applicant (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۲۲. لاگ تلاش‌های احراز هویت
-- ================================================================
CREATE TABLE IF NOT EXISTS identity_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    national_code CHAR(10) NOT NULL,
    ip VARCHAR(45) NULL,
    success TINYINT(1) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nc_time (national_code, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۲۳. نرخ‌نامهٔ چندگانه — گروه‌ها
-- ================================================================
CREATE TABLE IF NOT EXISTS fee_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(30),
    decile_min TINYINT NOT NULL,
    decile_max TINYINT NOT NULL,
    is_rural_eligible TINYINT(1) NOT NULL DEFAULT 0,
    total_amount BIGINT UNSIGNED NOT NULL,
    message_title VARCHAR(100),
    message_body TEXT,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۲۴. نرخ‌نامهٔ چندگانه — آیتم‌ها
-- ================================================================
CREATE TABLE IF NOT EXISTS fee_group_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fee_group_id INT NOT NULL,
    item_key VARCHAR(40) NOT NULL,
    item_label VARCHAR(150) NOT NULL,
    amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
    notes VARCHAR(255),
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (fee_group_id) REFERENCES fee_groups(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_group_item (fee_group_id, item_key),
    INDEX idx_group_sort (fee_group_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۲۵. تیک خدمات انجام‌شده برای هر پرداخت
-- ================================================================
CREATE TABLE IF NOT EXISTS payment_service_ticks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    item_key VARCHAR(40) NOT NULL,
    item_label VARCHAR(150) NOT NULL,
    original_amount BIGINT UNSIGNED NOT NULL,
    is_done TINYINT(1) NOT NULL DEFAULT 1,
    ticked_by INT NULL,
    ticked_at DATETIME NULL,
    untick_reason VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (ticked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_payment_item (payment_id, item_key),
    INDEX idx_payment (payment_id),
    INDEX idx_done (is_done)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۲۶. تاریخچهٔ تغییرات مدیر روی پرداخت‌ها (audit log)
-- ================================================================
CREATE TABLE IF NOT EXISTS payment_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    field_changed VARCHAR(50) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    note VARCHAR(500) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_audit_payment (payment_id),
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۲۷. حساب‌های کاربری متقاضیان (مدل فروشگاهی)
-- ================================================================
CREATE TABLE IF NOT EXISTS user_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL UNIQUE,
    username VARCHAR(50) UNIQUE NULL,
    mobile CHAR(11) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(150) NULL,
    avatar_document_id INT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    failed_login_count INT NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE CASCADE,
    FOREIGN KEY (avatar_document_id) REFERENCES documents(id) ON DELETE SET NULL,
    INDEX idx_username (username),
    INDEX idx_mobile (mobile),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- ۲۸. اعلان‌های کاربر
-- ================================================================
CREATE TABLE IF NOT EXISTS user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_account_id INT NOT NULL,
    applicant_id INT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    body TEXT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_account_id) REFERENCES user_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    INDEX idx_notif_user (user_account_id),
    INDEX idx_notif_unread (user_account_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- فعال‌سازی مجدد بررسی FK
SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- بذر داده‌ها
-- ================================================================

-- === بذر تنظیمات پیش‌فرض ===
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'سامانهٔ مجوزهای صنایع دستی'),
('owner_name', 'امین دروگر بزدی'),
('owner_username', 'Derogaramin'),
('social_eitaa', 'Derogaramin'),
('social_rubika', 'Derogaramin'),
('social_bale', 'Derogaramin'),
('success_message_template', 'باسلام\n{name} هستم .\nدر سامانه مدارک رو بارگذاری کردم و کد رهگیری {code} را اخذ نمودم .\nبا تشکر {name}'),
('about_title', 'دربارهٔ ما'),
('about_content', 'سامانهٔ مجوزهای صنایع دستی، بستری یکپارچه برای ثبت، پیگیری و صدور مجوزهای صنایع دستی است که توسط «امین دروگر بزدی» طراحی و راه‌اندازی شده است.\n\nهدف ما ساده‌سازی فرآیند اداری دریافت مجوز برای هنرمندان و صنعتگران عزیز ایرانی است؛ به‌گونه‌ای که متقاضی بدون مراجعهٔ حضوری مکرر، تمام مراحل ثبت‌نام، بارگذاری مدارک و پیگیری وضعیت پرونده را به‌صورت الکترونیکی انجام دهد.\n\n### مسیر ارتباطی\nبرای هماهنگی، پیگیری سریع‌تر و اطلاع مدیر سامانه از ثبت درخواست خود، پس از اخذ کد رهگیری به آیدی Derogaramin در شبکه‌های اجتماعی ایتا، روبیکا و بله پیام دهید.\n\n### تعهدات ما\n- حفظ محرمانگی اطلاعات و مدارک متقاضیان\n- رسیدگی شفاف و مرحله‌به‌مرحله به پرونده‌ها\n- اطلاع‌رسانی دقیق وضعیت از طریق تایم‌لاین پرونده'),
('owner_image', ''),
('registration_fee_amount', '500000'),
('license_reissue_fee_amount', '100000'),
('fee_tariff_active', '1'),
('fee_tariff_valid_until', '1405/12/29'),
('fee_default_amount', '500000'),
('fee_rural_auto_eligible', '1'),
('fee_modal_enabled', '1'),
('fee_modal_dismissable', '1'),
('fee_fallback_group_code', 'C'),
('fee_min_amount_percent', '10'),
('fee_max_discount_without_reason', '2'),
('fee_max_discount_with_reason', '50')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- === بذر سرویس‌ها ===
INSERT INTO service_types (id, title, authority, description, sort_order) VALUES
(1, 'مجوز صنایع دستی', 'وزارت میراث فرهنگی، گردشگری و صنایع دستی',
 'صدور مجوز رشته‌های صنایع دستی برای اشخاص حقیقی — فرآیند پایهٔ سامانه.', 10),
(2, 'مجوز مشاغل خانگی (مستقل)', 'وزارت تعاون، کار و رفاه اجتماعی — سامانهٔ مشاغل خانگی',
 'کسب‌وکار خانگی توسط خود متقاضی در فضای مسکونی؛ همان مدارک پایه کفایت می‌کند.', 20),
(3, 'مجوز مشاغل خانگی (پشتیبان)', 'وزارت تعاون، کار و رفاه اجتماعی — سامانهٔ مشاغل خانگی',
 'پشتیبانِ چند شاغل خانگی: نیازمند طرح توجیهی، فهرست افراد تحت پوشش و جزئیات درآمدی (حداقل ۵ سال سابقهٔ مرتبط).', 30),
(4, 'مجوز گردشگری', 'وزارت میراث فرهنگی، گردشگری و صنایع دستی — درگاه ملی مجوزها / سامانهٔ جانا',
 'اقامتگاه بوم‌گردی، تأسیسات گردشگری و دفاتر خدمات مسافرتی.', 40),
(5, 'مجوز هنری و فرهنگی', 'وزارت فرهنگ و ارشاد اسلامی — پنجرهٔ واحد وفا',
 'فعالیت‌های هنری، آموزشگاه‌های آزاد هنری و مؤسسات فرهنگی.', 50)
ON DUPLICATE KEY UPDATE authority = VALUES(authority);

-- === بذر مراحل ویزارد سیستمی ===
INSERT INTO wizard_steps (id, view_key, title, icon, is_system, sort_order) VALUES
(1, 'step1', 'اطلاعات هویتی', 'id-card', 1, 10),
(2, 'step2', 'اطلاعات شغلی', 'vase', 1, 20),
(3, 'step3', 'اطلاعات ملک', 'home', 1, 30),
(4, 'step4', 'بارگذاری مدارک', 'upload', 1, 1000)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- === بذر مراحل اختصاصی سرویس ۳ (پشتیبان) ===
INSERT INTO wizard_steps (title, description, icon, view_key, service_type_id, is_system, is_active, sort_order)
SELECT 'اطلاعات پشتیبان', 'طرح توجیهی و اطلاعات افراد تحت پوشش (اخصاصی مجوز پشتیبان)', 'users', NULL, 3, 0, 1, 35
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM wizard_steps WHERE title = 'اطلاعات پشتیبان' AND service_type_id = 3
);

-- === بذر مراحل اختصاصی سرویس ۲ (مشاغل خانگی مستقل) ===
INSERT INTO wizard_steps (title, description, icon, view_key, service_type_id, is_system, is_active, sort_order)
SELECT 'اطلاعات طرح و شغل', 'اطلاعات شغل، مهارت و طرح کسب‌وکار خانگی', 'briefcase', NULL, 2, 0, 1, 40
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2
);

INSERT INTO wizard_steps (title, description, icon, view_key, service_type_id, is_system, is_active, sort_order)
SELECT 'اطلاعات مالی', 'سرمایه، وام، سود و هزینه تجهیزات', 'wallet', NULL, 2, 0, 1, 50
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2
);

INSERT INTO wizard_steps (title, description, icon, view_key, service_type_id, is_system, is_active, sort_order)
SELECT 'محل فعالیت شخصی', 'مشخصات فضای فعالیت و امکانات', 'home', NULL, 2, 0, 1, 60
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM wizard_steps WHERE title = 'محل فعالیت شخصی' AND service_type_id = 2
);

-- === بذر فیلدهای سیستمی فرم (مراحل ۱-۴) ===
INSERT INTO form_fields (step, wizard_step_id, field_key, label, field_type, is_required, is_system, is_active, sort_order) VALUES
(1, 1, 'first_name', 'نام', 'text', 1, 1, 1, 10),
(1, 1, 'last_name', 'نام خانوادگی', 'text', 1, 1, 1, 20),
(1, 1, 'national_code', 'کد ملی', 'text', 1, 1, 1, 30),
(1, 1, 'postal_code', 'کد پستی', 'text', 1, 1, 1, 40),
(1, 1, 'mobile', 'شماره تلفن همراه', 'text', 1, 1, 1, 50),
(1, 1, 'birth_date', 'تاریخ تولد', 'date_jalali', 1, 1, 1, 60),
(1, 1, 'father_name', 'نام پدر', 'text', 1, 1, 1, 70),
(1, 1, 'marital_status', 'وضعیت تأهل', 'radio', 1, 1, 1, 80),
(1, 1, 'children_count', 'تعداد فرزندان', 'number', 0, 1, 1, 90),
(1, 1, 'physical_condition', 'وضعیت جسمانی', 'radio', 1, 1, 1, 100),
(1, 1, 'disability_details', 'توضیحات معلولیت', 'textarea', 0, 1, 1, 110),
(1, 1, 'income_decile', 'دهک درآمدی', 'number', 0, 1, 1, 120),
(1, 1, 'has_court_case', 'پرونده قضایی', 'radio', 1, 1, 1, 130),
(1, 1, 'has_active_loan', 'وام فعال', 'radio', 1, 1, 1, 140),
(1, 1, 'has_overdue_installments', 'اقساط معوقه', 'radio', 1, 1, 1, 150),
(2, 2, 'license_title', 'عنوان مجوز درخواستی', 'select', 1, 1, 1, 10),
(2, 2, 'experience_months', 'سابقهٔ فعالیت (ماه)', 'number', 1, 1, 1, 20),
(2, 2, 'training_method', 'نحوهٔ آموزش و آشنایی با حرفه', 'radio', 1, 1, 1, 30),
(3, 3, 'ownership_status', 'وضعیت مالکیت', 'radio', 1, 1, 1, 10),
(3, 3, 'province', 'استان', 'text', 1, 1, 1, 20),
(3, 3, 'county', 'شهرستان', 'text', 1, 1, 1, 30),
(3, 3, 'city', 'شهر', 'text', 1, 1, 1, 40),
(3, 3, 'district', 'بخش', 'text', 0, 1, 1, 50),
(3, 3, 'rural_district', 'دهستان', 'text', 0, 1, 1, 60),
(3, 3, 'village', 'روستا', 'text', 0, 1, 1, 70),
(3, 3, 'electricity_bill_id', 'شناسهٔ قبض برق', 'text', 1, 1, 1, 80),
(3, 3, 'address_street', 'خیابان', 'text', 1, 1, 1, 90),
(3, 3, 'address_alley', 'کوچه', 'text', 1, 1, 1, 100),
(3, 3, 'address_plaque', 'پلاک', 'text', 1, 1, 1, 110),
(4, 4, 'national_card_front', 'تصویر کارت ملی (رو)', 'file', 1, 1, 1, 10),
(4, 4, 'national_card_back', 'تصویر کارت ملی (پشت)', 'file', 0, 1, 1, 20),
(4, 4, 'birth_cert', 'تصویر شناسنامه', 'file', 1, 1, 1, 30),
(4, 4, 'ownership_doc', 'مدرک مالکیت/اجاره‌نامه/حکم سرپرستی', 'file', 1, 1, 1, 40),
(4, 4, 'vocational_cert', 'گواهینامهٔ فنی‌وحرفه‌ای', 'file', 0, 1, 1, 50),
(4, 4, 'education_degree', 'آخرین مدرک تحصیلی', 'file', 0, 1, 1, 60),
(4, 4, 'product_photos', 'تصاویر متقاضی با محصولات حین تولید', 'file', 0, 1, 1, 70),
(4, 4, 'optional_1', 'مدارک اختیاری ۱', 'file', 0, 1, 1, 80),
(4, 4, 'optional_2', 'مدارک اختیاری ۲', 'file', 0, 1, 1, 90),
(4, 4, 'optional_3', 'مدارک اختیاری ۳', 'file', 0, 1, 1, 100)
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- === بذر فیلدهای اختصاصی سرویس ۳ (پشتیبان) — dyn_justification_plan، dyn_covered_persons، dyn_income_details ===
INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات پشتیبان' AND service_type_id = 3 LIMIT 1), 3,
       'dyn_justification_plan', 'طرح توجیهی کسب‌وکار', 'textarea', 1, 0, 1, 10,
       'شرح کامل طرح: محصول/خدمت، بازار هدف، برآورد هزینه و درآمد'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_justification_plan' AND service_type_id = 3);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات پشتیبان' AND service_type_id = 3 LIMIT 1), 3,
       'dyn_covered_persons', 'فهرست و مشخصات افراد تحت پوشش', 'textarea', 1, 0, 1, 20,
       'نام، کد ملی و رشتهٔ فعالیت هر شاغل خانگی تحت پوشش (هر نفر یک خط)'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_covered_persons' AND service_type_id = 3);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات پشتیبان' AND service_type_id = 3 LIMIT 1), 3,
       'dyn_income_details', 'جزئیات درآمدی', 'textarea', 1, 0, 1, 30,
       'منابع درآمد، سهم پشتیبان و شاغلان، و نحوهٔ تسویه'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_income_details' AND service_type_id = 3);

-- === بذر فیلدهای اختصاصی سرویس ۲ (مشاغل خانگی مستقل) — مرحلهٔ ۵: اطلاعات طرح و شغل ===
INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_skill_level', 'سطح مهارت', 'select', 'دارای مهارت کامل انجام کسب و کار مورد نظر\nنیاز به آموزش\nنیاز به مشاوره', 1, 0, 1, 10
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_skill_level' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_has_skill', 'داشتن مهارت لازم', 'radio', 'بله\nخیر', 1, 0, 1, 20
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_has_skill' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, placeholder)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_job_title', 'عنوان شغل', 'text', 1, 0, 1, 30, 'مثلاً قالیباف، فرَتَ باف، سفالگر'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_job_title' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_skill_acquisition', 'نحوه کسب مهارت', 'select',
       'شیوه تجربی (استاد شاگردی - شغل پدری - روش خودآموز)\nشیوه آموزشی (آموزشگاه فنی و حرفه‌ای)\nشیوه دانشگاهی', 1, 0, 1, 40
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_skill_acquisition' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_need_support', 'نیاز به پشتیبانی', 'radio', 'بله\nخیر', 1, 0, 1, 50
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_need_support' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_cooperation', 'تمایل به همکاری', 'radio', 'بله\nخیر', 1, 0, 1, 60
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_cooperation' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, placeholder)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_plan_summary', 'خلاصه طرح', 'textarea', 1, 0, 1, 70, 'مثلاً تولید محصولات دستباف سنتی'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_plan_summary' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_experience_months', 'ماه‌های تجربه', 'number', 1, 0, 1, 80
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_experience_months' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_customers', 'مشتریان', 'checkbox', 'سفارشی\nمغازه یا کارگاه\nعرضه عمومی\nآنلاین (مجازی)', 0, 0, 1, 90, 'چندانتخابی'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_customers' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_marketing_methods', 'روش‌های بازاریابی', 'checkbox', 'معاملاتی\nچهره به چهره\nتلفنی\nرابطه‌ای', 0, 0, 1, 100
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_marketing_methods' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_target_markets', 'بازارهای هدف', 'checkbox', 'بازار داخل\nمنطقه‌ای\nصادرات', 0, 0, 1, 110
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_target_markets' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_risks', 'ریسک‌ها', 'textarea', 0, 0, 1, 120, 'ریسک‌های احتمالی طرح'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_risks' AND service_type_id = 2);

-- === بذر فیلدهای اختصاصی سرویس ۲ — مرحلهٔ ۶: اطلاعات مالی ===
INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_fixed_capital', 'سرمایه ثابت (میلیون ریال)', 'number', 1, 0, 1, 10, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_fixed_capital' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_working_capital', 'سرمایه در گردش (میلیون ریال)', 'number', 1, 0, 1, 20, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_working_capital' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_cash_share', 'سهم نقدی (میلیون ریال)', 'number', 1, 0, 1, 30, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_cash_share' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_noncash_share', 'سهم غیرنقدی (میلیون ریال)', 'number', 1, 0, 1, 40, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_noncash_share' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_loan_request', 'درخواست وام (میلیون ریال)', 'number', 1, 0, 1, 50, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_loan_request' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_financial_rate', 'نرخ مالی', 'number', 0, 0, 1, 60
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_financial_rate' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_break_even', 'نقطه سر به سر (میلیون ریال)', 'number', 1, 0, 1, 70, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_break_even' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_annual_profit', 'سود سالانه تخمینی (میلیون ریال)', 'number', 1, 0, 1, 80, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_annual_profit' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_annual_production', 'تولید سالانه تخمینی (میلیون ریال)', 'number', 1, 0, 1, 90, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_annual_production' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_equipment_costs', 'هزینه تجهیزات (شرح کامل)', 'textarea', 0, 0, 1, 100, 'هر خط: عنوان، تعداد، قیمت واحد'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_equipment_costs' AND service_type_id = 2);

-- === بذر فیلدهای اختصاصی سرویس ۲ — مرحلهٔ ۷: محل فعالیت شخصی ===
INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'محل فعالیت شخصی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_infrastructure_area', 'مساحت زیرساخت (متر)', 'number', 1, 0, 1, 10
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_infrastructure_area' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'محل فعالیت شخصی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_property_ownership', 'مالکیت', 'select', 'ملک\nاجاره‌ای\nرایگان', 1, 0, 1, 20
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_property_ownership' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'محل فعالیت شخصی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_building_type', 'نوع ساختمان', 'select', 'ویلا\nآپارتمان\nسایر', 1, 0, 1, 30
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_building_type' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'محل فعالیت شخصی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_activity_area', 'مساحت فعالیت (متر)', 'number', 1, 0, 1, 40
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_activity_area' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, (SELECT id FROM wizard_steps WHERE title = 'محل فعالیت شخصی' AND service_type_id = 2 LIMIT 1), 2,
       'dyn_facilities', 'امکانات', 'checkbox', 'تلفن ثابت\nگاز لوله‌کشی\nبرق\nآب لوله‌کشی', 0, 0, 1, 50, 'چندانتخابی'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_facilities' AND service_type_id = 2);

-- === بذر رشته‌های صنایع دستی (سرویس ۱) ===
INSERT INTO license_titles (service_type_id, category, title, sort_order) VALUES
-- بافته‌های داری
(1, 'بافته‌های داری', 'قالی تمام پشمی', 10), (1, 'بافته‌های داری', 'قالی پشم و ابریشم', 11),
(1, 'بافته‌های داری', 'قالی تمام ابریشم', 12), (1, 'بافته‌های داری', 'گلیم', 13),
(1, 'بافته‌های داری', 'گبه', 14), (1, 'بافته‌های داری', 'زیلو', 15),
(1, 'بافته‌های داری', 'جاجیم', 16), (1, 'بافته‌های داری', 'نمد مالی', 17),
(1, 'بافته‌های داری', 'ترمه بافی', 18), (1, 'بافته‌های داری', 'مخمل بافی', 19),
(1, 'بافته‌های داری', 'زربفت', 20), (1, 'بافته‌های داری', 'دارایی بافی', 21),
(1, 'بافته‌های داری', 'چادرشب بافی', 22), (1, 'بافته‌های داری', 'شمد بافی', 23),
(1, 'بافته‌های داری', 'عبا بافی', 24), (1, 'بافته‌های داری', 'ابریشم بافی', 25),
(1, 'بافته‌های داری', 'پلاس بافی', 26), (1, 'بافته‌های داری', 'فرَتَ باف (تنُ باف)', 27),
(1, 'بافته‌های داری', 'گلیم نقش برجسته', 28), (1, 'بافته‌های داری', 'بافت سنتی عشایری', 31),
-- رودوزی‌های سنتی
(1, 'رودوزی‌های سنتی', 'پته دوزی', 30), (1, 'رودوزی‌های سنتی', 'سرمه دوزی', 31),
(1, 'رودوزی‌های سنتی', 'زر دوزی', 32), (1, 'رودوزی‌های سنتی', 'ابریشم دوزی', 33),
(1, 'رودوزی‌های سنتی', 'بلوچ دوزی', 34), (1, 'رودوزی‌های سنتی', 'ترکمن دوزی', 35),
(1, 'رودوزی‌های سنتی', 'ملیله دوزی', 36), (1, 'رودوزی‌های سنتی', 'قلیون دوزی', 37),
(1, 'رودوزی‌های سنتی', 'آینه دوزی', 38), (1, 'رودوزی‌های سنتی', 'پولک دوزی', 39),
(1, 'رودوزی‌های سنتی', 'پردوزی (سوزن دوزی)', 40), (1, 'رودوزی‌های سنتی', 'خس دوزی', 41),
(1, 'رودوزی‌های سنتی', 'تور بافی', 42), (1, 'رودوزی‌های سنتی', 'قلاب بافی', 43),
-- سفال، سرامیک و کاشی
(1, 'سفال و سرامیک و کاشی', 'سفالگری', 50), (1, 'سفال و سرامیک و کاشی', 'کاشی سنتی', 51),
(1, 'سفال و سرامیک و کاشی', 'سرامیک سازی', 52), (1, 'سفال و سرامیک و کاشی', 'گچ بری سنتی', 53),
-- صنایع فلزی
(1, 'صنایع فلزی', 'قلم زنی', 60), (1, 'صنایع فلزی', 'مشبک کاری', 61),
(1, 'صنایع فلزی', 'دواتگری', 62), (1, 'صنایع فلزی', 'ملیله سازی', 63),
(1, 'صنایع فلزی', 'فیروزه کوبی', 64), (1, 'صنایع فلزی', 'طلا و نقره سازی', 65),
(1, 'صنایع فلزی', 'مسگری', 66), (1, 'صنایع فلزی', 'چاقو و کارد سازی', 67),
(1, 'صنایع فلزی', 'آهنگری سنتی', 68), (1, 'صنایع فلزی', 'قفل‌سازی سنتی', 69),
(1, 'صنایع فلزی', 'ساخت ظروف مسی دست‌ساز', 70), (1, 'صنایع فلزی', 'زیورآلات فلزی دست‌ساز', 71),
-- صنایع چوبی
(1, 'صنایع چوبی', 'منبت کاری', 70), (1, 'صنایع چوبی', 'معرق کاری', 71),
(1, 'صنایع چوبی', 'خراطی', 72), (1, 'صنایع چوبی', 'پیکره سازی چوبی', 73),
(1, 'صنایع چوبی', 'گره چینی', 74), (1, 'صنایع چوبی', 'مقرنس', 75),
(1, 'صنایع چوبی', 'نقاشی چوب (رنگ روغن)', 76), (1, 'صنایع چوبی', 'خیمه جات', 77),
(1, 'صنایع چوبی', 'ساخت سازهای سنتی (تار، سه تار، کمانچه و...)', 78),
(1, 'صنایع چوبی', 'صندلی و مبل چوبی دست‌ساز', 77), (1, 'صنایع چوبی', 'ساخت مبلمان روستایی', 79),
(1, 'صنایع چوبی', 'ساخت اشیاء تزئینی چوبی', 80), (1, 'صنایع چوبی', 'قاشق و چنگال چوبی دست‌ساز', 81),
-- شیشه و نگارگری
(1, 'شیشه و آبگینه', 'شیشه گری', 85), (1, 'شیشه و آبگینه', 'نقاشی روی شیشه', 86),
(1, 'شیشه و آبگینه', 'آبگینه تراشی', 87), (1, 'شیشه و آبگینه', 'آینه کاری', 88),
-- سنگ
(1, 'صنایع سنگی', 'تراش سنگ قیمتی و نیمه قیمتی', 90), (1, 'صنایع سنگی', 'حکاکی روی سنگ', 91),
(1, 'صنایع سنگی', 'سنگ بری تزئینی', 92),
-- چرم و پوست
(1, 'چرم و پوست', 'چرم دوزی سنتی', 95), (1, 'چرم و پوست', 'حکاکی روی چرم', 96),
(1, 'چرم و پوست', 'گیوه دوزی', 97), (1, 'چرم و پوست', 'پوستین دوزی', 98),
-- نگارگری
(1, 'نگارگری و تذهیب', 'نگارگری ایرانی', 100), (1, 'نگارگری و تذهیب', 'تذهیب', 101),
(1, 'نگارگری و تذهیب', 'تشعیر', 102), (1, 'نگارگری و تذهیب', 'خوشنویسی', 103),
(1, 'نگارگری و تذهیب', 'جلدسازی سنتی', 104), (1, 'نگارگری و تذهیب', 'لاکه تراشی', 105),
(1, 'نگارگری و تذهیب', 'مرغش کاری', 106), (1, 'نگارگری و تذهیب', 'فرشید بافی', 107),
-- چاپ و رنگرزی
(1, 'چاپ و رنگرزی سنتی', 'باتیک', 110), (1, 'چاپ و رنگرزی سنتی', 'قلم کاری پارچه', 111),
(1, 'چاپ و رنگرزی سنتی', 'کلاقه ای', 112), (1, 'چاپ و رنگرزی سنتی', 'رنگرزی طبیعی', 113),
(1, 'چاپ و رنگرزی سنتی', 'چاپ دستی پارچه', 114),
-- الیاف طبیعی
(1, 'بافته‌های الیاف طبیعی', 'حصیر بافی', 120), (1, 'بافته‌های الیاف طبیعی', 'خس بافی', 121),
(1, 'بافته‌های الیاف طبیعی', 'بوته بافی', 122), (1, 'بافته‌های الیاف طبیعی', 'کپو بافی', 123),
(1, 'بافته‌های الیاف طبیعی', 'گندم بافی', 124), (1, 'بافته‌های الیاف طبیعی', 'مرصع بافی', 125),
-- سایر هنرهای دستی
(1, 'سایر هنرهای دستی', 'مینیاتور سفالی', 130), (1, 'سایر هنرهای دستی', 'معرق کاشی', 131),
(1, 'سایر هنرهای دستی', 'ورق کوبی', 132), (1, 'سایر هنرهای دستی', 'تراش شیشه', 133),
(1, 'سایر هنرهای دستی', 'ساخت زیورآلات دست ساز', 134), (1, 'سایر هنرهای دستی', 'شمع سازی دست ساز', 135),
(1, 'سایر هنرهای دستی', 'صابون دست ساز', 136), (1, 'سایر هنرهای دستی', 'عروسک سازی', 137),
(1, 'سایر هنرهای دستی', 'سفالینه و مجسمه سازی', 138), (1, 'سایر هنرهای دستی', 'بافته‌های تلفیقی', 139),
(1, 'سایر هنرهای دستی', 'سایر (با ذکر عنوان در توضیحات)', 140),
(1, 'سایر هنرهای دستی', 'عروسک و ماکت محلی', 141),
(1, 'سایر هنرهای دستی', 'ساخت بازی‌های سنتی', 142),
(1, 'سایر هنرهای دستی', 'کتاب دست‌ساز و صحافی هنری', 143),
(1, 'سایر هنرهای دستی', 'کاغذ دست‌ساز', 144),
(1, 'سایر هنرهای دستی', 'ساخت آینه و تزئینات دیواری', 145),
(1, 'سایر هنرهای دستی', 'ساخت محصولات بازیافتی (دستی)', 146),
(1, 'سایر هنرهای دستی', 'بنایی و گچ‌بری سنتی', 147),
(1, 'سایر هنرهای دستی', 'آینه کاری و طلا کوبی', 148)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- === بذر رشته‌های مشاغل خانگی مستقل (سرویس ۲) ===
INSERT INTO license_titles (service_type_id, category, title, sort_order) VALUES
-- === صنایع غذایی و آشامیدنی خانگی ===
(2, 'صنایع غذایی و آشامیدنی خانگی', 'پخت نان سنتی و محلی', 10),
(2, 'صنایع غذایی و آشامیدنی خانگی', 'پخت شیرینی و کلوچه خانگی', 11),
(2, 'صنایع غذایی و آشامیدنی خانگی', 'پخت کیک و قنادی خانگی', 12),
(2, 'صنایع غذایی و آشامیدنی خانگی', 'فرآوری محصولات لبنی (پنیر، ماست، کره)', 13),
(2, 'صنایع غذایی و آشامیدنی خانگی', 'رب و رب میوه سنتی', 14),
(2, 'صنایع غذایی و آشامیدنی خانگی', 'ترشی و شور خانگی', 15),
(2, 'صنایع غذایی و آشامیدنی خانگی', 'مربا و شربت خانگی', 16),
(2, 'صنایع غذایی و آشامیدنی خانگی', 'خشک کردن میوه و سبزی', 17),
(2, 'صنایع غذایی و آشامیدنی خانگی', 'فرآوری گیاهان دارویی و عرقیجات', 18),
(2, 'صنایع غذایی و آشامیدنی خانگی', 'آبگوشت و خورش آماده خانگی', 19),
(2, 'صنایع غذایی و آشامیدنی خانگی', 'سس و چاشنی خانگی', 20),
(2, 'صنایع غذایی و آشامیدنی خانگی', 'پخت غذای سنتی و محلی', 21),
(2, 'صنایع غذایی و آشامیدنی خانگی', 'تهیه خشکبار و آجیل مخلوط', 22),
-- === صنایع دستی و هنری خانگی ===
(2, 'صنایع دستی و هنری خانگی', 'خیاطی و دوخت لباس خانگی', 30),
(2, 'صنایع دستی و هنری خانگی', 'بافتنی دستی', 31),
(2, 'صنایع دستی و هنری خانگی', 'دوخت کیف و کاموا', 32),
(2, 'صنایع دستی و هنری خانگی', 'عروسک و ماکت دست‌ساز', 33),
(2, 'صنایع دستی و هنری خانگی', 'گل مصنوعی دست‌ساز', 34),
(2, 'صنایع دستی و هنری خانگی', 'شمع تزئینی و معطر', 35),
(2, 'صنایع دستی و هنری خانگی', 'ساخت اشیاء تزئینی خانگی', 36),
(2, 'صنایع دستی و هنری خانگی', 'بافت پرده و لوازم خانه', 37),
(2, 'صنایع دستی و هنری خانگی', 'ساخت زیورآلات دست‌ساز', 38),
(2, 'صنایع دستی و هنری خانگی', 'نقاشی روی پارچه و باتیک', 39),
(2, 'صنایع دستی و هنری خانگی', 'حروف‌نگاری و کالیگرافی', 40),
-- === خدمات خانگی ===
(2, 'خدمات خانگی', 'خیاطی و تعمیر لباس', 50),
(2, 'خدمات خانگی', 'آرایش و پیرایش خانگی', 51),
(2, 'خدمات خانگی', 'تزئین و گل‌آرایی خانگی', 52),
(2, 'خدمات خانگی', 'پذیرایی و تجهیز سفره خانگی', 53),
(2, 'خدمات خانگی', 'نظافت و شست‌وشوی خانگی', 54),
(2, 'خدمات خانگی', 'پرستاری و مراقبت از کودک در منزل', 55),
(2, 'خدمات خانگی', 'تعمیرات کوچک لوازم خانگی', 56),
(2, 'خدمات خانگی', 'بسته‌بندی و ارسال خانگی', 57),
-- === محصولات بهداشتی و آرایشی خانگی ===
(2, 'محصولات بهداشتی و آرایشی خانگی', 'صابون گیاهی و سنتی', 70),
(2, 'محصولات بهداشتی و آرایشی خانگی', 'محصولات آرایشی طبیعی (حنا، وسمه)', 71),
(2, 'محصولات بهداشتی و آرایشی خانگی', 'عطر و گلاب سنتی', 72),
(2, 'محصولات بهداشتی و آرایشی خانگی', 'محصولات مراقبت پوست طبیعی', 73),
(2, 'محصولات بهداشتی و آرایشی خانگی', 'محصولات مراقبت مو طبیعی', 74),
-- === نساجی و پوشاک خانگی ===
(2, 'نساجی و پوشاک خانگی', 'دوخت لباس محلی و قومی', 90),
(2, 'نساجی و پوشاک خانگی', 'دوخت چادر عربی و عشایری', 91),
(2, 'نساجی و پوشاک خانگی', 'دوخت کفش و دمپایی سنتی', 92),
(2, 'نساجی و پوشاک خانگی', 'حاشیه‌دوزی و تزئین پارچه', 93),
-- === محصولات کشاورزی و باغی خانگی ===
(2, 'محصولات کشاورزی و باغی خانگی', 'پرورش گیاهان آپارتمانی', 110),
(2, 'محصولات کشاورزی و باغی خانگی', 'تهیه بذر و نهال خانگی', 111),
(2, 'محصولات کشاورزی و باغی خانگی', 'پرورش قارچ خانگی', 112),
(2, 'محصولات کشاورزی و باغی خانگی', 'فرآوری صمغ و کود طبیعی', 113),
-- === صنایع چوب و مبلمان خانگی ===
(2, 'صنایع چوب و مبلمان خانگی', 'ساخت مبلمان روستایی و چوبی', 130),
(2, 'صنایع چوب و مبلمان خانگی', 'ساخت اشیاء تزئینی چوبی', 131),
(2, 'صنایع چوب و مبلمان خانگی', 'قاشق و ظروف چوبی دست‌ساز', 132),
-- === محصولات الکترونیکی و دیجیتال خانگی ===
(2, 'محصولات الکترونیکی و دیجیتال خانگی', 'تعمیر و اسمبل رایانه خانگی', 150),
(2, 'محصولات الکترونیکی و دیجیتال خانگی', 'تعمیر موبایل و لوازم جانبی', 151),
(2, 'محصولات الکترونیکی و دیجیتال خانگی', 'طراحی و چاپ دیجیتال', 152),
(2, 'محصولات الکترونیکی و دیجیتال خانگی', 'تولید محتوای دیجیتال', 153),
-- === سایر کسب‌وکارهای خانگی ===
(2, 'سایر کسب‌وکارهای خانگی', 'سایر (با ذکر عنوان در توضیحات)', 200)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- === بذر رشته‌های پشتیبان (سرویس ۳) ===
INSERT INTO license_titles (service_type_id, category, title, sort_order) VALUES
-- === پشتیبانی فرآورده‌های غذایی ===
(3, 'پشتیبانی فرآورده‌های غذایی', 'پشتیبانی تولید و بسته‌بندی نان و شیرینی', 10),
(3, 'پشتیبانی فرآورده‌های غذایی', 'پشتیبانی فرآوری لبنیات خانگی', 11),
(3, 'پشتیبانی فرآورده‌های غذایی', 'پشتیبانی تولید ترشیجات و مربا', 12),
(3, 'پشتیبانی فرآورده‌های غذایی', 'پشتیبانی فرآوری گیاهان دارویی و عرقیجات', 13),
(3, 'پشتیبانی فرآورده‌های غذایی', 'پشتیبانی تولید غذای آماده و سنتی', 14),
-- === پشتیبانی صنایع دستی ===
(3, 'پشتیبانی صنایع دستی', 'پشتیبانی تولید بافتههای سنتی (قالی، گلیم)', 30),
(3, 'پشتیبانی صنایع دستی', 'پشتیبانی تولید رودوزی‌های سنتی', 31),
(3, 'پشتیبانی صنایع دستی', 'پشتیبانی تولید سفال و سرامیک', 32),
(3, 'پشتیبانی صنایع دستی', 'پشتیبانی تولید صنایع چوبی و منبت', 33),
(3, 'پشتیبانی صنایع دستی', 'پشتیبانی تولید صنایع فلزی و قلمزنی', 34),
(3, 'پشتیبانی صنایع دستی', 'پشتیبانی تولید چرم و گیوه', 35),
(3, 'پشتیبانی صنایع دستی', 'پشتیبانی تولید زیورآلات و عروسک', 36),
-- === پشتیبانی خدمات ===
(3, 'پشتیبانی خدمات', 'پشتیبانی خدمات خیاطی و پوشاک', 50),
(3, 'پشتیبانی خدمات', 'پشتیبانی خدمات آرایشی و بهداشتی', 51),
(3, 'پشتیبانی خدمات', 'پشتیبانی خدمات پذیرایی و تجهیز سفره', 52),
(3, 'پشتیبانی خدمات', 'پشتیبانی خدمات نظافت خانگی', 53),
(3, 'پشتیبانی خدمات', 'پشتیبانی خدمات پرستاری و مراقبت از کودک', 54),
(3, 'پشتیبانی خدمات', 'پشتیبانی خدمات تعمیرات لوازم خانگی', 55),
-- === پشتیبانی فروش و بازاریابی ===
(3, 'پشتیبانی فروش و بازاریابی', 'پشتیبانی فروش آنلاین محصولات خانگی', 70),
(3, 'پشتیبانی فروش و بازاریابی', 'پشتیبانی بازاریابی محلی و محله‌ای', 71),
(3, 'پشتیبانی فروش و بازاریابی', 'پشتیبانی فروش در نمایشگاه‌ها و بازارهای محلی', 72),
(3, 'پشتیبانی فروش و بازاریابی', 'پشتیبانی برندسازی و بسته‌بندی فروش', 73),
-- === پشتیبانی تأمین و توزیع ===
(3, 'پشتیبانی تأمین و توزیع', 'پشتیبانی تأمین مواد اولیه برای شاغلان خانگی', 90),
(3, 'پشتیبانی تأمین و توزیع', 'پشتیبانی توزیع و ارسال محصولات', 91),
(3, 'پشتیبانی تأمین و توزیع', 'پشتیبانی انبارداری و مدیریت موجودی', 92),
-- === پشتیبانی آموزش و توسعه مهارت ===
(3, 'پشتیبانی آموزش و توسعه مهارت', 'پشتیبانی آموزش مهارت‌های تولیدی', 110),
(3, 'پشتیبانی آموزش و توسعه مهارت', 'پشتیبانی آموزش مهارت‌های خدماتی', 111),
(3, 'پشتیبانی آموزش و توسعه مهارت', 'پشتیبانی کارگاه‌های آموزشی شاغلان خانگی', 112),
-- === پشتیبانی فنی و کارگاهی ===
(3, 'پشتیبانی فنی و کارگاهی', 'پشتیبانی تجهیز و راه‌اندازی کارگاه خانگی', 130),
(3, 'پشتیبانی فنی و کارگاهی', 'پشتیبانی فنی و تعمیرات تجهیزات تولیدی', 131),
(3, 'پشتیبانی فنی و کارگاهی', 'پشتیبانی کنترل کیفیت محصولات', 132),
-- === پشتیبانی مالی و اداری ===
(3, 'پشتیبانی مالی و اداری', 'پشتیبانی امور مالی و حسابداری شاغلان', 150),
(3, 'پشتیبانی مالی و اداری', 'پشتیبانی اخذ مجوزها و تأمین مالی', 151),
(3, 'پشتیبانی مالی و اداری', 'پشتیبانی بیمه و امور اداری شاغلان خانگی', 152),
-- === پشتیبانی صادرات و توسعه بازار ===
(3, 'پشتیبانی صادرات و توسعه بازار', 'پشتیبانی صادرات محصولات خانگی', 170),
(3, 'پشتیبانی صادرات و توسعه بازار', 'پشتیبانی توسعه بازارهای منطقه‌ای و بین‌المللی', 171),
-- === سایر خدمات پشتیبانی ===
(3, 'سایر خدمات پشتیبانی', 'سایر (با ذکر عنوان در توضیحات)', 200)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- === بذر کانال‌های پرداخت نمونه ===
INSERT INTO payment_channels (title, channel_type, account_info, sort_order) VALUES
('کارت بانک ملت — به نام امین دروگر بزدی', 'کارت به کارت', '6104-3333-0000-0000 (نمونه — از پنل تنظیمات ویرایش کنید)', 10),
('حساب بانکی سامانه', 'حساب بانکی', 'شبا: IR00-0000-0000-0000-0000-0000-00 (نمونه)', 20)
ON DUPLICATE KEY UPDATE title = title;

-- === بذر الگوهای پیامک ===
INSERT INTO sms_templates (title, sample_text, target_status, requires_code, sort_order) VALUES
('پیامک احراز نشانی / تکمیل بررسی', 'پرونده شما در حال بررسی است...', 'احراز نشانی', 0, 10),
('پیامک ثبت درخواست صدور مجوز', 'درخواست صدور مجوز شما ثبت شد...', 'ثبت درخواست صدور', 0, 20),
('پیامک درخواست اصلاح مدارک', 'لطفاً مدارک ناقص را تکمیل نمایید...', 'درخواست اصلاح مدارک', 1, 30),
('پیامک تأیید اولیه سازمان', 'مدارک شما تأیید اولیه شد...', 'تأیید اولیه', 1, 40),
('پیامک تأیید نهایی', 'پرونده شما تأیید نهایی شد...', 'تأیید نهایی', 1, 50),
('پیامک صدور مجوز', 'مجوز شما صادر شد؛ برای دریافت اقدام کنید...', 'صدور مجوز', 1, 60)
ON DUPLICATE KEY UPDATE title = title;

-- === بذر ۴ گروه قیمتی ===
INSERT INTO fee_groups (code, name, icon, decile_min, decile_max, is_rural_eligible, total_amount, message_title, message_body, sort_order) VALUES
('A', 'گروه الف: حمایتی', '🌱', 1, 2, 1, 1350000,
 'پیام ما به شما',
 'هنرمند عزیز روستایی، ما می‌دانیم که برای آمدن به شهر و انجام این کارها، هم هزینه رفت‌وآمد می‌دهید و هم وقت ارزشمندتان را صرف می‌کنید. به همین دلیل، تمام تلاشمان را می‌کنیم که در یک مراجعه، تمام کارهایتان انجام شود. اگر هم پرونده‌تان نقص داشت، خودمان با شما تماس می‌گیریم و بدون هزینه اضافی، رفع نقص می‌کنیم. هنر شما سرمایه این سرزمین است و ما مفتخریم که در خدمت شما هستیم.',
 10),
('B', 'گروه ب: متوسط آسیب‌پذیر', '🌿', 3, 4, 0, 2250000,
 'پیام ما به شما',
 'هنرمند گرامی، ما می‌دانیم که شما با دستان هنرمندتان، آبروی خانواده‌تان را تأمین می‌کنید. هر ریالی که از شما می‌گیریم، در قبال خدماتی است که واقعاً انجام می‌دهیم. اگر در هر مرحله از کار، سوالی داشتید یا نگرانی برایتان پیش آمد، با روی خوش پاسخگو هستیم.',
 20),
('C', 'گروه ج: متوسط', '🌳', 5, 6, 0, 3800000,
 'پیام ما به شما',
 'همشهری گرامی، شفافیت، اصل اول کار ماست. شما با پرداخت این مبلغ، صاحب یک پرونده کامل و بدون نقص خواهید شد که در سریع‌ترین زمان ممکن به نتیجه می‌رسد. ما به وقت شما احترام می‌گذاریم.',
 30),
('D', 'گروه د: حرفه‌ای/VIP', '🌲', 7, 10, 0, 6500000,
 'پیام ما به شما',
 'همراه گرامی، این پکیج ویژه برای کسانی طراحی شده که به دنبال خدمات حرفه‌ای، سریع و بدون دغدغه هستند. در این پکیج، ما تمام مراحل را از ابتدا تا انتها به صورت اختصاصی برای شما مدیریت می‌کنیم.',
 40)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- === بذر ۱۲ خدمت پایه برای هر گروه قیمتی ===
INSERT INTO fee_group_items (fee_group_id, item_key, item_label, amount, sort_order) VALUES
-- گروه الف
((SELECT id FROM fee_groups WHERE code='A'), 'service_registration', 'ثبت‌نام و احراز هویت در درگاه ملی مجوزها', 80000, 10),
((SELECT id FROM fee_groups WHERE code='A'), 'service_postal_code', 'ثبت/اصلاح کد پستی در سامانه املاک و اسکان', 40000, 20),
((SELECT id FROM fee_groups WHERE code='A'), 'service_document_upload', 'ثبت و بارگذاری مدارک در سامانه میراث فرهنگی', 300000, 30),
((SELECT id FROM fee_groups WHERE code='A'), 'service_address_verify', 'احراز نشانی پستی (GNAF)', 120000, 40),
((SELECT id FROM fee_groups WHERE code='A'), 'service_scan', 'اسکن مدارک (تا ۱۰ صفحه)', 40000, 50),
((SELECT id FROM fee_groups WHERE code='A'), 'service_license_print', 'چاپ مجوز نهایی', 40000, 60),
((SELECT id FROM fee_groups WHERE code='A'), 'service_delivery', 'ارسال فایل نهایی به موبایل', 40000, 70),
((SELECT id FROM fee_groups WHERE code='A'), 'service_followup', 'پیگیری و رفع نقص احتمالی', 400000, 80),
((SELECT id FROM fee_groups WHERE code='A'), 'service_license_image', 'بارگذاری تصویر مجوز + توکن ۷۲ ساعته', 50000, 90),
((SELECT id FROM fee_groups WHERE code='A'), 'service_revision_cycle', 'چرخهٔ بازبینی پرونده', 50000, 100),
((SELECT id FROM fee_groups WHERE code='A'), 'service_sms_status', 'ثبت پیامک‌های وضعیت با ارتقای خودکار', 40000, 110),
((SELECT id FROM fee_groups WHERE code='A'), 'service_multi_license', 'پشتیبانی چندمجوزی + احراز شش‌عاملی', 150000, 120),
-- گروه ب
((SELECT id FROM fee_groups WHERE code='B'), 'service_registration', 'ثبت‌نام و احراز هویت در درگاه ملی مجوزها', 240000, 10),
((SELECT id FROM fee_groups WHERE code='B'), 'service_postal_code', 'ثبت/اصلاح کد پستی در سامانه املاک و اسکان', 80000, 20),
((SELECT id FROM fee_groups WHERE code='B'), 'service_document_upload', 'ثبت و بارگذاری مدارک در سامانه میراث فرهنگی', 650000, 30),
((SELECT id FROM fee_groups WHERE code='B'), 'service_address_verify', 'احراز نشانی پستی (GNAF)', 160000, 40),
((SELECT id FROM fee_groups WHERE code='B'), 'service_scan', 'اسکن مدارک (تا ۱۰ صفحه)', 80000, 50),
((SELECT id FROM fee_groups WHERE code='B'), 'service_license_print', 'چاپ مجوز نهایی', 80000, 60),
((SELECT id FROM fee_groups WHERE code='B'), 'service_delivery', 'ارسال فایل نهایی به موبایل', 40000, 70),
((SELECT id FROM fee_groups WHERE code='B'), 'service_followup', 'پیگیری و رفع نقص احتمالی', 480000, 80),
((SELECT id FROM fee_groups WHERE code='B'), 'service_license_image', 'بارگذاری تصویر مجوز + توکن ۷۲ ساعته', 80000, 90),
((SELECT id FROM fee_groups WHERE code='B'), 'service_revision_cycle', 'چرخهٔ بازبینی پرونده', 80000, 100),
((SELECT id FROM fee_groups WHERE code='B'), 'service_sms_status', 'ثبت پیامک‌های وضعیت با ارتقای خودکار', 60000, 110),
((SELECT id FROM fee_groups WHERE code='B'), 'service_multi_license', 'پشتیبانی چندمجوزی + احراز شش‌عاملی', 220000, 120),
-- گروه ج
((SELECT id FROM fee_groups WHERE code='C'), 'service_registration', 'ثبت‌نام و احراز هویت در درگاه ملی مجوزها', 400000, 10),
((SELECT id FROM fee_groups WHERE code='C'), 'service_postal_code', 'ثبت/اصلاح کد پستی در سامانه املاک و اسکان', 120000, 20),
((SELECT id FROM fee_groups WHERE code='C'), 'service_document_upload', 'ثبت و بارگذاری مدارک در سامانه میراث فرهنگی', 1100000, 30),
((SELECT id FROM fee_groups WHERE code='C'), 'service_address_verify', 'احراز نشانی پستی (GNAF)', 240000, 40),
((SELECT id FROM fee_groups WHERE code='C'), 'service_scan', 'اسکن مدارک (تا ۱۰ صفحه)', 120000, 50),
((SELECT id FROM fee_groups WHERE code='C'), 'service_license_print', 'چاپ مجوز نهایی', 120000, 60),
((SELECT id FROM fee_groups WHERE code='C'), 'service_delivery', 'ارسال فایل نهایی به موبایل', 40000, 70),
((SELECT id FROM fee_groups WHERE code='C'), 'service_followup', 'پیگیری و رفع نقص احتمالی', 800000, 80),
((SELECT id FROM fee_groups WHERE code='C'), 'service_license_image', 'بارگذاری تصویر مجوز + توکن ۷۲ ساعته', 120000, 90),
((SELECT id FROM fee_groups WHERE code='C'), 'service_revision_cycle', 'چرخهٔ بازبینی پرونده', 120000, 100),
((SELECT id FROM fee_groups WHERE code='C'), 'service_sms_status', 'ثبت پیامک‌های وضعیت با ارتقای خودکار', 100000, 110),
((SELECT id FROM fee_groups WHERE code='C'), 'service_multi_license', 'پشتیبانی چندمجوزی + احراز شش‌عاملی', 120000, 120),
-- گروه د
((SELECT id FROM fee_groups WHERE code='D'), 'service_registration', 'ثبت‌نام و احراز هویت در درگاه ملی مجوزها', 650000, 10),
((SELECT id FROM fee_groups WHERE code='D'), 'service_postal_code', 'ثبت/اصلاح کد پستی در سامانه املاک و اسکان', 240000, 20),
((SELECT id FROM fee_groups WHERE code='D'), 'service_document_upload', 'ثبت و بارگذاری مدارک در سامانه میراث فرهنگی', 1900000, 30),
((SELECT id FROM fee_groups WHERE code='D'), 'service_address_verify', 'احراز نشانی پستی (GNAF)', 360000, 40),
((SELECT id FROM fee_groups WHERE code='D'), 'service_scan', 'اسکن مدارک (تا ۱۰ صفحه)', 200000, 50),
((SELECT id FROM fee_groups WHERE code='D'), 'service_license_print', 'چاپ مجوز نهایی', 200000, 60),
((SELECT id FROM fee_groups WHERE code='D'), 'service_delivery', 'ارسال فایل نهایی به موبایل', 80000, 70),
((SELECT id FROM fee_groups WHERE code='D'), 'service_followup', 'پیگیری و رفع نقص احتمالی', 1500000, 80),
((SELECT id FROM fee_groups WHERE code='D'), 'service_license_image', 'بارگذاری تصویر مجوز + توکن ۷۲ ساعته', 200000, 90),
((SELECT id FROM fee_groups WHERE code='D'), 'service_revision_cycle', 'چرخهٔ بازبینی پرونده', 200000, 100),
((SELECT id FROM fee_groups WHERE code='D'), 'service_sms_status', 'ثبت پیامک‌های وضعیت با ارتقای خودکار', 160000, 110),
((SELECT id FROM fee_groups WHERE code='D'), 'service_multi_license', 'پشتیبانی چندمجوزی + احراز شش‌عاملی', 810000, 120)
ON DUPLICATE KEY UPDATE item_label = VALUES(item_label), amount = VALUES(amount);

-- گزارش نهایی
SELECT 'Schema installation completed' AS result;
SELECT COUNT(*) AS total_tables FROM information_schema.tables WHERE table_schema = DATABASE();
SELECT COUNT(*) AS total_license_titles FROM license_titles;
SELECT COUNT(*) AS total_service_types FROM service_types;
SELECT COUNT(*) AS total_form_fields FROM form_fields;
SELECT COUNT(*) AS total_wizard_steps FROM wizard_steps;
