-- =============================================
-- به‌روزرسانی v0.0.8 — فاز ۶ نقشهٔ راه
-- تصویر مجوز صادرشده + قاعدهٔ «یک‌بار دریافت» + درخواست‌های دسترسی مجدد
-- =============================================

USE handicraft_license;

-- تصویر مجوز هر پرونده (توسط مدیر پس از صدور بارگذاری می‌شود)
CREATE TABLE IF NOT EXISTS license_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL UNIQUE,
    document_id INT NOT NULL,            -- تصویر مجوز در جدول documents
    download_count INT NOT NULL DEFAULT 0,
    first_accessed_at DATETIME NULL,
    issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- درخواست‌های دسترسی به تصویر مجوز (بار اول خودکار، دفعات بعد با تأیید مدیر و در صورت لزوم پرداخت)
CREATE TABLE IF NOT EXISTS license_access_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    is_first_access TINYINT(1) NOT NULL DEFAULT 0,  -- دریافت اول (خودکار)
    access_token CHAR(64) NULL UNIQUE,              -- توکن لینک زمان‌دار
    requires_payment TINYINT(1) NOT NULL DEFAULT 0,
    amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
    payment_id INT NULL,                            -- پرداخت مرتبط
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

-- اتصال پرداخت‌های «دریافت مجدد تصویر مجوز» به درخواست مرتبط
ALTER TABLE payments
    ADD CONSTRAINT fk_payment_license_request
    FOREIGN KEY (linked_request_id) REFERENCES license_access_requests(id) ON DELETE SET NULL;
