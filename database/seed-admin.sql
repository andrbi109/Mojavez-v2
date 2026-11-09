-- =================================================================
-- Mojavez — ساخت حساب مدیر پیش‌فرض (اولین راه‌اندازی)
-- =================================================================
-- این فایل یک حساب مدیر کل می‌سازد تا بتوانید وارد پنل مدیریت شوید.
--
-- ⚠️ مهم: پس از ورود اول، رمز عبور را از داخل پنل تغییر دهید!
--
-- نحوهٔ اجرا:
--   mysql -u <user> -p <db_name> < database/seed-admin.sql
--
-- یا در phpMyAdmin:
--   ۱) این فایل را Import کنید
--   ۲) با مرورگر به /admin/login بروید
--   ۳) با نام کاربری admin و رمز Mojavez@2026 وارد شوید
--   ۴) بلافاصله رمز را تغییر دهید!
--
-- اطلاعات ورود پیش‌فرض:
--   نام کاربری: admin
--   رمز عبور: Mojavez@2026
--
-- برای تولید هش جدید: php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_BCRYPT);"
-- =================================================================

USE handicraft_license;

-- حذف حساب admin قبلی در صورت وجود (برای idempotency)
DELETE FROM users WHERE username = 'admin';

-- ساخت حساب مدیر کل
-- رمز عبور: Mojavez@2026 (هش bcrypt با cost=10)
INSERT INTO users (username, password, display_name, role) VALUES (
    'admin',
    '$2y$10$xYgOnOLFOd/QG85MLso5hud67sGt9A5hJJHpeyZpmFH15UH9buq2C',
    'مدیر کل سامانه',
    'admin'
);

-- تأیید
SELECT id, username, display_name, role FROM users WHERE username = 'admin';

-- =================================================================
-- راهنمای تغییر رمز پس از اولین ورود:
--   ۱) به /admin/login بروید
--   ۲) با admin / Mojavez@2026 وارد شوید
--   ۳) به بخش «کاربران» بروید
--   ۴) رمز عبور جدید را تنظیم کنید
-- =================================================================
