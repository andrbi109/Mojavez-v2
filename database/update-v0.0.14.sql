-- =============================================
-- به‌روزرسانی v0.0.14 — فاز ۱۰ نقشهٔ راه
-- پشتیبانی اشخاص حقوقی در ثبت‌نام عمومی
-- =============================================

USE handicraft_license;

ALTER TABLE applicants
    ADD COLUMN applicant_type ENUM('حقیقی','حقوقی') NOT NULL DEFAULT 'حقیقی' AFTER current_status,
    ADD INDEX idx_applicant_type (applicant_type);

-- ستون‌های شخص حقوقی در v0.0.11 روی persons افزوده شده‌اند:
-- company_name, company_national_id, reg_number, reg_date, company_type, economic_code
