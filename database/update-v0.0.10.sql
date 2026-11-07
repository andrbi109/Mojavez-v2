-- =============================================
-- به‌روزرسانی v0.0.10 — فاز ۲ نقشهٔ راه
-- چرخهٔ بازبینی پرونده توسط متقاضی (ویرایش اطلاعات + افزودن مدرک)
-- با شرط بازبینی کامل همهٔ اطلاعات پیشین
-- =============================================

USE handicraft_license;

CREATE TABLE IF NOT EXISTS applicant_revisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    payload JSON NOT NULL,                 -- کل دادهٔ پیشنهادی (مراحل ۱-۳ + فهرست مدارک جدید + یادداشت)
    note VARCHAR(500) NULL,                -- توضیح متقاضی برای مدیر
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
