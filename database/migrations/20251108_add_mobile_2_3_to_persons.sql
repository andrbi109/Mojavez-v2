-- =================================================================
-- مهاجرت: افزودن ستون‌های mobile_2 و mobile_3 به جدول persons
-- -----------------------------------------------------------------
-- تاریخ: ۱۴۰۵/۰۸/۱۸
-- هدف: پشتیبانی از متقاضیانی که با چند شماره موبایل در فایل CSV
--      ایمپورت دسته‌جمعی ظاهر می‌شوند.
--      شماره اصلی در persons.mobile و شماره‌های اضافی در
--      persons.mobile_2 و persons.mobile_3 ذخیره می‌شوند.
--      این مهاجرت idempotent است.
-- نحوه اجرا:
--   mysql -u <user> -p <db_name> < database/migrations/20251108_add_mobile_2_3_to_persons.sql
-- =================================================================

USE handicraft_license;

-- mobile_2
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

-- mobile_3
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

-- تأیید وجود ستون‌ها
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'persons'
  AND COLUMN_NAME IN ('mobile', 'mobile_2', 'mobile_3');
