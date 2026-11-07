-- =============================================
-- به‌روزرسانی v0.0.7 — فاز ۳ و ۴ نقشهٔ راه
-- گردش پرداخت هزینهٔ ثبت‌نام: کانال‌های پرداخت + پرداخت‌ها
-- =============================================

USE handicraft_license;

-- کانال‌های پرداخت (از پنل مدیریت قابل CRUD)
CREATE TABLE IF NOT EXISTS payment_channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    channel_type ENUM('کارت به کارت','حساب بانکی','پرداخت اینترنتی','سایر') NOT NULL DEFAULT 'کارت به کارت',
    account_info TEXT NULL,               -- شمارهٔ کارت/حساب/شبا/لینک درگاه + نام دارنده
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- پرداخت‌های متقاضیان
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    payment_channel_id INT NULL,
    purpose ENUM('هزینهٔ ثبت‌نام','هزینهٔ دریافت مجدد تصویر مجوز') NOT NULL DEFAULT 'هزینهٔ ثبت‌نام',
    amount BIGINT UNSIGNED NOT NULL DEFAULT 0,      -- تومان
    reference_code VARCHAR(50) NULL,                 -- کد پیگیری بانکی
    paid_at DATE NULL,                               -- تاریخ واریز اعلامی (میلادی، از شمسی تبدیل می‌شود)
    receipt_document_id INT NULL,                    -- تصویر رسید → documents
    linked_request_id INT NULL,                      -- درخواست مرتبط (بازصدور تصویر مجوز — فاز ۶)
    status ENUM('در انتظار بررسی','تأیید شده','رد شده') NOT NULL DEFAULT 'در انتظار بررسی',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    reject_reason VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_channel_id) REFERENCES payment_channels(id) ON DELETE SET NULL,
    FOREIGN KEY (receipt_document_id) REFERENCES documents(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pay_status (status),
    INDEX idx_pay_applicant (applicant_id),
    INDEX idx_pay_purpose (purpose)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- پرچم درخواست پرداخت روی پرونده (مدیر فعال می‌کند)
ALTER TABLE applicants
    ADD COLUMN payment_required TINYINT(1) NOT NULL DEFAULT 0 AFTER priority,
    ADD COLUMN payment_requested_at DATETIME NULL AFTER payment_required;

-- مبلغ پیش‌فرض هزینهٔ ثبت‌نام (تومان) — از پنل تنظیمات قابل تغییر
INSERT INTO settings (setting_key, setting_value) VALUES
('registration_fee_amount', '500000'),
('license_reissue_fee_amount', '100000')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- دو کانال نمونه (مدیر از پنل ویرایش/حذف می‌کند)
INSERT INTO payment_channels (title, channel_type, account_info, sort_order) VALUES
('کارت بانک ملت — به نام امین دروگر بزدی', 'کارت به کارت', '6104-3333-0000-0000 (نمونه — از پنل تنظیمات ویرایش کنید)', 10),
('حساب بانکی سامانه', 'حساب بانکی', 'شبا: IR00-0000-0000-0000-0000-0000-00 (نمونه)', 20)
ON DUPLICATE KEY UPDATE title = title;
