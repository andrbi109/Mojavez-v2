-- =================================================================
-- مهاجرت جامع: ارتقا از هر نسخهٔ v0.x به v2.0
-- -----------------------------------------------------------------
-- این مهاجرت همهٔ تغییرات schema مورد نیاز برای v2.0 را به‌صورت
-- idempotent اعمال می‌کند. می‌توان آن را بدون خطر چندین بار اجرا کرد.
--
-- نحوه اجرا:
--   mysql -u <user> -p <db_name> < database/migrations/20251109_v2_upgrade.sql
--
-- نکته: این فایل فقط تغییرات v2.0 را شامل می‌شود. برای نصب تازه،
-- database/schema.sql را اجرا کنید.
-- =================================================================

USE handicraft_license;

-- ==================== ستون‌های mobile_2 و mobile_3 در persons ====================
-- (برای پشتیبانی از چند موبایل در ایمپورت دسته‌جمعی)

SET @col_m2 = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'persons'
                 AND COLUMN_NAME = 'mobile_2');
SET @ddl_m2 = IF(@col_m2 = 0,
    'ALTER TABLE persons ADD COLUMN mobile_2 CHAR(11) NULL AFTER mobile, ADD INDEX idx_person_mobile_2 (mobile_2)',
    'SELECT 1');
PREPARE stmt_m2 FROM @ddl_m2;
EXECUTE stmt_m2;
DEALLOCATE PREPARE stmt_m2;

SET @col_m3 = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'persons'
                 AND COLUMN_NAME = 'mobile_3');
SET @ddl_m3 = IF(@col_m3 = 0,
    'ALTER TABLE persons ADD COLUMN mobile_3 CHAR(11) NULL AFTER mobile_2, ADD INDEX idx_person_mobile_3 (mobile_3)',
    'SELECT 1');
PREPARE stmt_m3 FROM @ddl_m3;
EXECUTE stmt_m3;
DEALLOCATE PREPARE stmt_m3;

-- ==================== تأیید وجود ستون‌های اساسی v2 ====================
-- این بخش فقط برای اطمینان است که schema کامل است.

-- بررسی وجود جدول user_accounts
SET @tbl_ua = (SELECT COUNT(*) FROM information_schema.TABLES
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_accounts');
-- اگر جدول وجود ندارد، کاربر باید schema.sql کامل را اجرا کند
SELECT IF(@tbl_ua = 1, 'OK: user_accounts exists', 'WARNING: user_accounts missing — run schema.sql') AS status;

-- بررسی وجود جدول persons
SET @tbl_p = (SELECT COUNT(*) FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'persons');
SELECT IF(@tbl_p = 1, 'OK: persons exists', 'WARNING: persons missing — run schema.sql') AS status;

-- ==================== پایان مهاجرت ====================
SELECT 'Migration v2.0 completed successfully' AS result;
