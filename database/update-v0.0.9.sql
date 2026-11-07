-- =============================================
-- به‌روزرسانی v0.0.9 — فاز ۵ نقشهٔ راه
-- ثبت پیامک‌های وضعیت توسط متقاضی (پس از تأیید مدیر → به‌روزرسانی وضعیت)
-- =============================================

USE handicraft_license;

-- الگوهای پیامک (متن + وضعیت مقصد) — تماماً از پنل مدیریت قابل CRUD
CREATE TABLE IF NOT EXISTS sms_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,           -- مثال: «پیامک پذیرش نهایی سازمان»
    sample_text TEXT NULL,                 -- نمونهٔ متن برای راهنمای متقاضی
    target_status VARCHAR(50) NOT NULL,    -- وضعیت مقصد پس از تأیید مدیر
    requires_code TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- پیامک‌های ثبت‌شده توسط متقاضیان
CREATE TABLE IF NOT EXISTS user_sms_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    sms_template_id INT NOT NULL,
    sms_code VARCHAR(50) NULL,
    sms_text TEXT NULL,
    received_at DATE NULL,                  -- تاریخ دریافت پیامک (میلادی، از شمسی تبدیل)
    status ENUM('در انتظار','تأیید شده','رد شده') NOT NULL DEFAULT 'در انتظار',
    applied_status TINYINT(1) NOT NULL DEFAULT 0,  -- آیا وضعیت پرونده اعمال شد؟
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

-- الگوهای پیش‌فرض متناسب با وضعیت‌های سامانه
INSERT INTO sms_templates (title, sample_text, target_status, requires_code, sort_order) VALUES
('پیامک احراز نشانی / تکمیل بررسی', 'پرونده شما در حال بررسی است...', 'احراز نشانی', 0, 10),
('پیامک ثبت درخواست صدور مجوز', 'درخواست صدور مجوز شما ثبت شد...', 'ثبت درخواست صدور', 0, 20),
('پیامک درخواست اصلاح مدارک', 'لطفاً مدارک ناقص را تکمیل نمایید...', 'درخواست اصلاح مدارک', 1, 30),
('پیامک تأیید اولیه سازمان', 'مدارک شما تأیید اولیه شد...', 'تأیید اولیه', 1, 40),
('پیامک تأیید نهایی', 'پرونده شما تأیید نهایی شد...', 'تأیید نهایی', 1, 50),
('پیامک صدور مجوز', 'مجوز شما صادر شد؛ برای دریافت اقدام کنید...', 'صدور مجوز', 1, 60)
ON DUPLICATE KEY UPDATE title = title;
