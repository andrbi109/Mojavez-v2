-- =============================================
-- به‌روزرسانی v0.0.4
-- افزودن گردش کار «بررسی درخواست تغییر کد پستی» توسط مدیر
-- و ستون‌های ممیزی (Audit) برای رهگیری اقدامات
-- =============================================

USE handicraft_license;

-- وضعیت بررسی درخواست تغییر کد پستی:
--   در انتظار  → کاربر درخواست داده و مدیر هنوز بررسی نکرده
--   تأیید شده → مدیر کد پستی جدید را در جدول applicants اعمال کرده
--   رد شده    → مدیر درخواست را نپذیرفته
ALTER TABLE postal_code_changes
    ADD COLUMN status ENUM('در انتظار','تأیید شده','رد شده') NOT NULL DEFAULT 'در انتظار' AFTER new_postal_code,
    ADD COLUMN reviewed_by INT NULL AFTER status,
    ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by,
    ADD COLUMN reject_reason VARCHAR(255) NULL AFTER reviewed_at,
    ADD INDEX idx_postal_status (status);

-- کلید خارجی reviewed_by به جدول مدیران (در صورت حذف مدیر، مقدار NULL می‌شود)
ALTER TABLE postal_code_changes
    ADD CONSTRAINT fk_postal_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL;

-- ایندکس ترکیبی برای گزارش‌گیری سریع‌تر بر اساس تاریخ ثبت
ALTER TABLE applicants
    ADD INDEX idx_completed_priority (registration_completed_at, priority);
