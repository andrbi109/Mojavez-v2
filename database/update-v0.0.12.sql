-- =============================================
-- به‌روزرسانی v0.0.12 — فاز ۱ نقشهٔ راه
-- ویزارد پویا: مراحل سفارشی ثبت‌نام
-- =============================================

USE handicraft_license;

CREATE TABLE IF NOT EXISTS wizard_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    view_key VARCHAR(30) NULL,             -- برای مراحل سیستمی: step1..step4؛ سفارشی‌ها NULL
    title VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    icon VARCHAR(30) NULL,                 -- نام آیکون از دیکشنری icon()
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 50,    -- مرحلهٔ مدارک sort=1000 (همیشه آخر)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- اتصال فیلدها به مراحل ویزارد
ALTER TABLE form_fields
    ADD COLUMN wizard_step_id INT NULL AFTER step,
    ADD CONSTRAINT fk_field_step FOREIGN KEY (wizard_step_id) REFERENCES wizard_steps(id) ON DELETE SET NULL;

-- بذر چهار مرحلهٔ سیستمی
INSERT INTO wizard_steps (id, view_key, title, icon, is_system, sort_order) VALUES
(1, 'step1', 'اطلاعات هویتی', 'id-card', 1, 10),
(2, 'step2', 'اطلاعات شغلی', 'vase', 1, 20),
(3, 'step3', 'اطلاعات ملک', 'home', 1, 30),
(4, 'step4', 'بارگذاری مدارک', 'upload', 1, 1000)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- نگاشت فیلدهای موجود به مراحل سیستمی
UPDATE form_fields SET wizard_step_id = step WHERE wizard_step_id IS NULL AND step BETWEEN 1 AND 4;
