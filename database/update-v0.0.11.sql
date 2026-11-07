-- =============================================
-- به‌روزرسانی v0.0.11 — فاز ۷ نقشهٔ راه
-- تفکیک «شخص» از «پرونده/مجوز» → پشتیبانی چندمجوزی و سلسله‌مراتب
-- =============================================

USE handicraft_license;

-- ۱. جدول اشخاص (لنگر هویت = کد ملی)
CREATE TABLE IF NOT EXISTS persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    national_code CHAR(10) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    father_name VARCHAR(50) NULL,
    birth_date DATE NULL,
    mobile CHAR(11) NOT NULL,
    postal_code CHAR(10) NULL,
    person_type ENUM('حقیقی','حقوقی') NOT NULL DEFAULT 'حقیقی',   -- فاز ۱۰
    -- اطلاعات شخص حقوقی (فاز ۱۰)
    company_name VARCHAR(150) NULL,
    company_national_id CHAR(11) NULL,
    reg_number VARCHAR(30) NULL,
    reg_date DATE NULL,
    company_type VARCHAR(60) NULL,
    economic_code VARCHAR(20) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_person_mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۲. گسترش پرونده‌ها
ALTER TABLE applicants
    ADD COLUMN person_id INT NULL AFTER id,
    ADD COLUMN parent_application_id INT NULL AFTER person_id,
    ADD COLUMN license_state ENUM('جاری','صادر شده','باطل شده','منقضی') NOT NULL DEFAULT 'جاری' AFTER priority,
    ADD COLUMN license_valid_until DATE NULL AFTER license_state,
    ADD INDEX idx_person (person_id),
    ADD INDEX idx_parent_app (parent_application_id),
    ADD INDEX idx_license_state (license_state);

ALTER TABLE applicants
    ADD CONSTRAINT fk_app_person FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_app_parent FOREIGN KEY (parent_application_id) REFERENCES applicants(id) ON DELETE SET NULL;

-- ۳. مهاجرت داده: برای هر کد ملی یک شخص بساز و پرونده‌ها را متصل کن
INSERT INTO persons (national_code, first_name, last_name, father_name, birth_date, mobile, postal_code)
SELECT a.national_code,
       SUBSTRING_INDEX(GROUP_CONCAT(a.first_name ORDER BY a.id DESC), ',', 1),
       SUBSTRING_INDEX(GROUP_CONCAT(a.last_name ORDER BY a.id DESC), ',', 1),
       SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(a.father_name,'') ORDER BY a.id DESC), ',', 1),
       MAX(a.birth_date),
       SUBSTRING_INDEX(GROUP_CONCAT(a.mobile ORDER BY a.id DESC), ',', 1),
       SUBSTRING_INDEX(GROUP_CONCAT(a.postal_code ORDER BY a.id DESC), ',', 1)
FROM applicants a
GROUP BY a.national_code
ON DUPLICATE KEY UPDATE national_code = national_code;

UPDATE applicants a
JOIN persons p ON p.national_code = a.national_code
SET a.person_id = p.id
WHERE a.person_id IS NULL;

-- پرونده‌های صادرشده → license_state
UPDATE applicants SET license_state = 'صادر شده'
WHERE current_status IN ('صدور مجوز','تحویل مجوز') AND license_state = 'جاری';

-- ۴. رفع یکتایی سراسری کد ملی/موبایل روی پرونده‌ها (یکتا در سطح persons باقی می‌ماند)
-- فقط قید UNIQUE حذف می‌شود؛ ایندکس‌های عادی idx_national_code/idx_mobile از قبل در schema وجود دارند
ALTER TABLE applicants DROP INDEX national_code;
ALTER TABLE applicants DROP INDEX mobile;

-- ۵. لاگ تلاش‌های احراز هویت (فاز ۸)
CREATE TABLE IF NOT EXISTS identity_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    national_code CHAR(10) NOT NULL,
    ip VARCHAR(45) NULL,
    success TINYINT(1) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nc_time (national_code, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
