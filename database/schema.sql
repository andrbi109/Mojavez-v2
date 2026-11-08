-- =================================================================
-- سامانهٔ مدیریت ثبت‌نام و پیگیری صدور مجوز صنایع دستی
-- MySQL/MariaDB — نصب تازه با اجرای همین یک فایل انجام می‌شود
-- -----------------------------------------------------------------
-- تولید شده است. ترتیب اجرا برای جلوگیری از تداخل FOREIGN KEY
-- و وابستگی ستون‌ها (ALTER TABLE) به‌دقت حفظ شده است.
-- -----------------------------------------------------------------
-- طراح و توسعه‌دهنده: امین دروگر بزدی (Derogaramin@)
-- =================================================================


-- ================================================================
-- schema.sql
-- ================================================================


CREATE DATABASE IF NOT EXISTS handicraft_license
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE handicraft_license;

-- ۱. جدول مدیران سیستم
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۲. جدول متقاضیان (اطلاعات اصلی)
CREATE TABLE IF NOT EXISTS applicants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    national_code CHAR(10) NOT NULL UNIQUE,
    postal_code CHAR(10) NOT NULL,
    mobile CHAR(11) NOT NULL UNIQUE,
    tracking_code VARCHAR(20) NOT NULL UNIQUE,
    current_status ENUM(
        'پیش‌نویس',
        'تکمیل اطلاعات',
        'احراز نشانی',
        'ثبت درخواست صدور',
        'بارگذاری مدارک به سازمان',
        'تأیید اولیه',
        'صدور مجوز',
        'مشاهده و چاپ',
        'لغو'
    ) DEFAULT 'پیش‌نویس',
    priority TINYINT(1) DEFAULT 0,
    registration_started_at DATETIME NULL,
    registration_completed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tracking_code (tracking_code),
    INDEX idx_national_code (national_code),
    INDEX idx_status (current_status),
    INDEX idx_priority (priority),
    INDEX idx_mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۳. جدول اطلاعات شغلی (۱:۱ با applicants)
CREATE TABLE IF NOT EXISTS job_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    license_title VARCHAR(100) NOT NULL,
    experience_months INT NOT NULL,
    training_method ENUM('در خانه', 'استاد شاگردی', 'کارگاهی') NOT NULL,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_applicant_job (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۴. جدول اطلاعات ملک (۱:۱ با applicants)
CREATE TABLE IF NOT EXISTS property_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    ownership_status ENUM('شخص', 'مستأجر', 'سرپرست') NOT NULL,
    province VARCHAR(50) NOT NULL,
    county VARCHAR(50) NOT NULL,
    city VARCHAR(50) NOT NULL,
    district VARCHAR(50) NULL,
    rural_district VARCHAR(50) NULL,
    village VARCHAR(50) NULL,
    electricity_bill_id VARCHAR(20) NOT NULL,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_applicant_property (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۵. جدول مدارک (۱:N با applicants)
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    doc_type VARCHAR(50) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    INDEX idx_applicant_docs (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۶. جدول تاریخچه وضعیت‌ها
CREATE TABLE IF NOT EXISTS status_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    status_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    INDEX idx_applicant_log (applicant_id),
    INDEX idx_status_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۷. جدول تاریخچه تغییرات کد پستی
CREATE TABLE IF NOT EXISTS postal_code_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    old_postal_code CHAR(10) NOT NULL,
    new_postal_code CHAR(10) NOT NULL,
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    INDEX idx_applicant_postal (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- توجه: هیچ مدیر پیش‌فرضی در schema seed نمی‌شود (رفع C-2).
-- برای ایجاد مدیر، از install scripts استفاده کنید یا دستی:
--   INSERT INTO users (username, password, display_name, role) VALUES
--   ('admin', '<bcrypt-hash>', 'نام مدیر', 'admin');
-- هش را با PHP تولید کنید: php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_BCRYPT);"


ALTER TABLE applicants
ADD COLUMN birth_date DATE NULL AFTER mobile,
ADD COLUMN father_name VARCHAR(50) NULL AFTER birth_date,
ADD COLUMN marital_status ENUM('مجرد','متأهل') DEFAULT 'مجرد' AFTER father_name,
ADD COLUMN children_count TINYINT UNSIGNED DEFAULT 0 AFTER marital_status,
ADD COLUMN physical_condition ENUM('سالم','معلولیت') DEFAULT 'سالم' AFTER children_count,
ADD COLUMN disability_details VARCHAR(255) NULL AFTER physical_condition,
ADD COLUMN income_decile TINYINT UNSIGNED NULL AFTER disability_details,
ADD COLUMN has_court_case ENUM('بله','خیر') DEFAULT 'خیر' AFTER income_decile,
ADD COLUMN has_active_loan ENUM('بله','خیر') DEFAULT 'خیر' AFTER has_court_case,
ADD COLUMN has_overdue_installments ENUM('بله','خیر') DEFAULT 'خیر' AFTER has_active_loan;

ALTER TABLE property_details
ADD COLUMN address_street VARCHAR(100) NULL AFTER electricity_bill_id,
ADD COLUMN address_alley VARCHAR(100) NULL AFTER address_street,
ADD COLUMN address_plaque VARCHAR(20) NULL AFTER address_alley;


ALTER TABLE `users` ADD COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'admin' AFTER `display_name`;


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


USE handicraft_license;

-- ۱. تنظیمات کلید/مقداری سامانه (از پنل مدیریت قابل تغییر)
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(64) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۲. تعریف فیلدهای فرم ثبت‌نام (سیستمی و سفارشی)
CREATE TABLE IF NOT EXISTS form_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    step TINYINT NOT NULL,                     -- مرحلهٔ ویزارد: ۱ تا ۴
    field_key VARCHAR(64) NOT NULL UNIQUE,     -- کلید فنی (برای فیلدهای سیستمی = نام ستون)
    label VARCHAR(150) NOT NULL,               -- برچسب فارسی نمایشی
    field_type VARCHAR(30) NOT NULL DEFAULT 'text',
        -- text | number | textarea | select | radio | checkbox | date_jalali | file
    options TEXT NULL,                         -- گزینه‌ها (select/radio) — هر خط یکی
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    is_system TINYINT(1) NOT NULL DEFAULT 0,   -- فیلد داخلی سامانه: حذف‌شدنی نیست
    is_active TINYINT(1) NOT NULL DEFAULT 1,   -- مدیر می‌تواند غیرفعال (حذف از فرم) کند
    validation_json TEXT NULL,                 -- {"min":..,"max":..,"maxlength":..,"pattern":..}
    placeholder VARCHAR(150) NULL,
    help_text VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_step_active (step, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۳. مقادیر فیلدهای پویا برای هر متقاضی
CREATE TABLE IF NOT EXISTS applicant_field_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    field_id INT NOT NULL,
    field_value TEXT NULL,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES form_fields(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_applicant_field (applicant_id, field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- بذر تنظیمات پیش‌فرض
-- =============================================
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'سامانهٔ مجوزهای صنایع دستی'),
('owner_name', 'امین دروگر بزدی'),
('owner_username', 'Derogaramin'),
('social_eitaa', 'Derogaramin'),
('social_rubika', 'Derogaramin'),
('social_bale', 'Derogaramin'),
('success_message_template', 'باسلام\n{name} هستم .\nدر سامانه مدارک رو بارگذاری کردم و کد رهگیری {code} را اخذ نمودم .\nبا تشکر {name}'),
('about_title', 'دربارهٔ ما'),
('about_content', 'سامانهٔ مجوزهای صنایع دستی، بستری یکپارچه برای ثبت، پیگیری و صدور مجوزهای صنایع دستی است که توسط «امین دروگر بزدی» طراحی و راه‌اندازی شده است.\n\nهدف ما ساده‌سازی فرآیند اداری دریافت مجوز برای هنرمندان و صنعتگران عزیز ایرانی است؛ به‌گونه‌ای که متقاضی بدون مراجعهٔ حضوری مکرر، تمام مراحل ثبت‌نام، بارگذاری مدارک و پیگیری وضعیت پرونده را به‌صورت الکترونیکی انجام دهد.\n\n### مسیر ارتباطی\nبرای هماهنگی، پیگیری سریع‌تر و اطلاع مدیر سامانه از ثبت درخواست خود، پس از اخذ کد رهگیری به آیدی Derogaramin در شبکه‌های اجتماعی ایتا، روبیکا و بله پیام دهید.\n\n### تعهدات ما\n- حفظ محرمانگی اطلاعات و مدارک متقاضیان\n- رسیدگی شفاف و مرحله‌به‌مرحله به پرونده‌ها\n- اطلاع‌رسانی دقیق وضعیت از طریق تایم‌لاین پرونده')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- =============================================
-- بذر فیلدهای سیستمی فرم (فقط در صورت خالی بودن جدول)
-- =============================================
INSERT INTO form_fields (step, field_key, label, field_type, is_required, is_system, is_active, sort_order)
SELECT * FROM (
    SELECT 1 AS step, 'first_name' AS field_key, 'نام' AS label, 'text' AS field_type, 1 AS is_required, 1 AS is_system, 1 AS is_active, 10 AS sort_order
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM form_fields);

INSERT INTO form_fields (step, field_key, label, field_type, is_required, is_system, sort_order) VALUES
(1, 'last_name', 'نام خانوادگی', 'text', 1, 1, 20),
(1, 'national_code', 'کد ملی', 'text', 1, 1, 30),
(1, 'postal_code', 'کد پستی', 'text', 1, 1, 40),
(1, 'mobile', 'شماره تلفن همراه', 'text', 1, 1, 50),
(1, 'birth_date', 'تاریخ تولد', 'date_jalali', 1, 1, 60),
(1, 'father_name', 'نام پدر', 'text', 1, 1, 70),
(1, 'marital_status', 'وضعیت تأهل', 'radio', 1, 1, 80),
(1, 'children_count', 'تعداد فرزندان', 'number', 0, 1, 90),
(1, 'physical_condition', 'وضعیت جسمانی', 'radio', 1, 1, 100),
(1, 'disability_details', 'توضیحات معلولیت', 'textarea', 0, 1, 110),
(1, 'income_decile', 'دهک درآمدی', 'number', 0, 1, 120),
(1, 'has_court_case', 'پرونده قضایی', 'radio', 1, 1, 130),
(1, 'has_active_loan', 'وام فعال', 'radio', 1, 1, 140),
(1, 'has_overdue_installments', 'اقساط معوقه', 'radio', 1, 1, 150),
(2, 'license_title', 'عنوان مجوز درخواستی', 'select', 1, 1, 10),
(2, 'experience_months', 'سابقهٔ فعالیت (ماه)', 'number', 1, 1, 20),
(2, 'training_method', 'نحوهٔ آموزش و آشنایی با حرفه', 'radio', 1, 1, 30),
(3, 'ownership_status', 'وضعیت مالکیت', 'radio', 1, 1, 10),
(3, 'province', 'استان', 'text', 1, 1, 20),
(3, 'county', 'شهرستان', 'text', 1, 1, 30),
(3, 'city', 'شهر', 'text', 1, 1, 40),
(3, 'district', 'بخش', 'text', 0, 1, 50),
(3, 'rural_district', 'دهستان', 'text', 0, 1, 60),
(3, 'village', 'روستا', 'text', 0, 1, 70),
(3, 'electricity_bill_id', 'شناسهٔ قبض برق', 'text', 1, 1, 80),
(3, 'address_street', 'خیابان', 'text', 1, 1, 90),
(3, 'address_alley', 'کوچه', 'text', 1, 1, 100),
(3, 'address_plaque', 'پلاک', 'text', 1, 1, 110),
(4, 'national_card_front', 'تصویر کارت ملی (رو)', 'file', 1, 1, 10),
(4, 'national_card_back', 'تصویر کارت ملی (پشت)', 'file', 0, 1, 20),
(4, 'birth_cert', 'تصویر شناسنامه', 'file', 1, 1, 30),
(4, 'ownership_doc', 'مدرک مالکیت/اجاره‌نامه/حکم سرپرستی', 'file', 1, 1, 40),
(4, 'vocational_cert', 'گواهینامهٔ فنی‌وحرفه‌ای', 'file', 0, 1, 50),
(4, 'education_degree', 'آخرین مدرک تحصیلی', 'file', 0, 1, 60),
(4, 'product_photos', 'تصاویر متقاضی با محصولات حین تولید', 'file', 0, 1, 70),
(4, 'optional_1', 'مدارک اختیاری ۱', 'file', 0, 1, 80),
(4, 'optional_2', 'مدارک اختیاری ۲', 'file', 0, 1, 90),
(4, 'optional_3', 'مدارک اختیاری ۳', 'file', 0, 1, 100)
ON DUPLICATE KEY UPDATE field_key = field_key;


USE handicraft_license;

-- ۱. وضعیت‌های جدید (مسیر یکطرفهٔ رسیدگی گسترده‌تر شد)
ALTER TABLE applicants
MODIFY COLUMN current_status ENUM(
    'پیش‌نویس',
    'تکمیل اطلاعات',
    'احراز نشانی',
    'بررسی کارشناسی رشته',
    'ثبت درخواست صدور',
    'بارگذاری مدارک به سازمان',
    'درخواست اصلاح مدارک',
    'تأیید اولیه',
    'تأیید نهایی',
    'صدور مجوز',
    'تحویل مجوز',
    'مشاهده و چاپ',
    'رد درخواست',
    'لغو'
) DEFAULT 'پیش‌نویس';

-- ۲. جدول رشته‌های مجوز (به‌جای فهرست سخت‌کدشده در ویو)
CREATE TABLE IF NOT EXISTS license_titles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(80) NOT NULL,          -- گروه/خوشهٔ رشته (optgroup)
    title VARCHAR(120) NOT NULL UNIQUE,     -- عنوان رشته
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- بذر فهرست گستردهٔ رشته‌ها (بر پایهٔ طبقه‌بندی فرهنگستان هنر و فهرست وزارت میراث فرهنگی)
INSERT INTO license_titles (category, title, sort_order) VALUES
-- بافته‌های داری
('بافته‌های داری', 'قالی تمام پشمی', 10), ('بافته‌های داری', 'قالی پشم و ابریشم', 11),
('بافته‌های داری', 'قالی تمام ابریشم', 12), ('بافته‌های داری', 'گلیم', 13),
('بافته‌های داری', 'گبه', 14), ('بافته‌های داری', 'زیلو', 15),
('بافته‌های داری', 'جاجیم', 16), ('بافته‌های داری', 'نمد مالی', 17),
('بافته‌های داری', 'ترمه بافی', 18), ('بافته‌های داری', 'مخمل بافی', 19),
('بافته‌های داری', 'زربفت', 20), ('بافته‌های داری', 'دارایی بافی', 21),
('بافته‌های داری', 'چادرشب بافی', 22), ('بافته‌های داری', 'شمد بافی', 23),
('بافته‌های داری', 'عبا بافی', 24), ('بافته‌های داری', 'ابریشم بافی', 25),
('بافته‌های داری', 'پلاس بافی', 26),
-- رودوزی‌های سنتی
('رودوزی‌های سنتی', 'پته دوزی', 30), ('رودوزی‌های سنتی', 'سرمه دوزی', 31),
('رودوزی‌های سنتی', 'زر دوزی', 32), ('رودوزی‌های سنتی', 'ابریشم دوزی', 33),
('رودوزی‌های سنتی', 'بلوچ دوزی', 34), ('رودوزی‌های سنتی', 'ترکمن دوزی', 35),
('رودوزی‌های سنتی', 'ملیله دوزی', 36), ('رودوزی‌های سنتی', 'قلیون دوزی', 37),
('رودوزی‌های سنتی', 'آینه دوزی', 38), ('رودوزی‌های سنتی', 'پولک دوزی', 39),
('رودوزی‌های سنتی', 'پردوزی (سوزن دوزی)', 40), ('رودوزی‌های سنتی', 'خس دوزی', 41),
('رودوزی‌های سنتی', 'تور بافی', 42), ('رودوزی‌های سنتی', 'قلاب بافی', 43),
-- سفال، سرامیک و کاشی
('سفال و سرامیک و کاشی', 'سفالگری', 50), ('سفال و سرامیک و کاشی', 'کاشی سنتی', 51),
('سفال و سرامیک و کاشی', 'سرامیک سازی', 52), ('سفال و سرامیک و کاشی', 'گچ بری سنتی', 53),
-- صنایع فلزی
('صنایع فلزی', 'قلم زنی', 60), ('صنایع فلزی', 'مشبک کاری', 61),
('صنایع فلزی', 'دواتگری', 62), ('صنایع فلزی', 'ملیله سازی', 63),
('صنایع فلزی', 'فیروزه کوبی', 64), ('صنایع فلزی', 'طلا و نقره سازی', 65),
('صنایع فلزی', 'مسگری', 66), ('صنایع فلزی', 'چاقو و کارد سازی', 67),
-- صنایع چوبی
('صنایع چوبی', 'منبت کاری', 70), ('صنایع چوبی', 'معرق کاری', 71),
('صنایع چوبی', 'خراطی', 72), ('صنایع چوبی', 'پیکره سازی چوبی', 73),
('صنایع چوبی', 'گره چینی', 74), ('صنایع چوبی', 'مقرنس', 75),
('صنایع چوبی', 'نقاشی چوب (رنگ روغن)', 76), ('صنایع چوبی', 'خیمه جات', 77),
('صنایع چوبی', 'ساخت سازهای سنتی (تار، سه تار، کمانچه و...)', 78),
-- شیشه و نگارگری
('شیشه و آبگینه', 'شیشه گری', 85), ('شیشه و آبگینه', 'نقاشی روی شیشه', 86),
('شیشه و آبگینه', 'آبگینه تراشی', 87), ('شیشه و آبگینه', 'آینه کاری', 88),
-- سنگ
('صنایع سنگی', 'تراش سنگ قیمتی و نیمه قیمتی', 90), ('صنایع سنگی', 'حکاکی روی سنگ', 91),
('صنایع سنگی', 'سنگ بری تزئینی', 92),
-- چرم و پوست
('چرم و پوست', 'چرم دوزی سنتی', 95), ('چرم و پوست', 'حکاکی روی چرم', 96),
('چرم و پوست', 'گیوه دوزی', 97), ('چرم و پوست', 'پوستین دوزی', 98),
-- نگارگری
('نگارگری و تذهیب', 'نگارگری ایرانی', 100), ('نگارگری و تذهیب', 'تذهیب', 101),
('نگارگری و تذهیب', 'تشعیر', 102), ('نگارگری و تذهیب', 'خوشنویسی', 103),
('نگارگری و تذهیب', 'جلدسازی سنتی', 104), ('نگارگری و تذهیب', 'لاکه تراشی', 105),
('نگارگری و تذهیب', 'مرغش کاری', 106), ('نگارگری و تذهیب', 'فرشید بافی', 107),
-- چاپ و رنگرزی
('چاپ و رنگرزی سنتی', 'باتیک', 110), ('چاپ و رنگرزی سنتی', 'قلم کاری پارچه', 111),
('چاپ و رنگرزی سنتی', 'کلاقه ای', 112), ('چاپ و رنگرزی سنتی', 'رنگرزی طبیعی', 113),
('چاپ و رنگرزی سنتی', 'چاپ دستی پارچه', 114),
-- الیاف طبیعی
('بافته‌های الیاف طبیعی', 'حصیر بافی', 120), ('بافته‌های الیاف طبیعی', 'خس بافی', 121),
('بافته‌های الیاف طبیعی', 'بوته بافی', 122), ('بافته‌های الیاف طبیعی', 'کپو بافی', 123),
('بافته‌های الیاف طبیعی', 'گندم بافی', 124), ('بافته‌های الیاف طبیعی', 'مرصع بافی', 125),
-- سایر
('سایر هنرهای دستی', 'مینیاتور سفالی', 130), ('سایر هنرهای دستی', 'معرق کاشی', 131),
('سایر هنرهای دستی', 'ورق کوبی', 132), ('سایر هنرهای دستی', 'تراش شیشه', 133),
('سایر هنرهای دستی', 'ساخت زیورآلات دست ساز', 134), ('سایر هنرهای دستی', 'شمع سازی دست ساز', 135),
('سایر هنرهای دستی', 'صابون دست ساز', 136), ('سایر هنرهای دستی', 'عروسک سازی', 137),
('سایر هنرهای دستی', 'سفالینه و مجسمه سازی', 138), ('سایر هنرهای دستی', 'بافته‌های تلفیقی', 139),
('سایر هنرهای دستی', 'سایر (با ذکر عنوان در توضیحات)', 140)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- ۳. اصلاح دامنهٔ روبیکا و افزودن کلید تصویر مدیر
UPDATE settings SET setting_value = REPLACE(setting_value, 'rubika.cc', 'rubika.ir')
WHERE setting_key IN ('about_content');
INSERT INTO settings (setting_key, setting_value) VALUES
('owner_image', '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;


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
    status ENUM('در انتظار رسید','در انتظار بررسی','تأیید شده','رد شده') NOT NULL DEFAULT 'در انتظار رسید',
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


USE handicraft_license;

-- ۱. جدول اشخاص (لنگر هویت = کد ملی)
CREATE TABLE IF NOT EXISTS persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    national_code CHAR(10) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    father_name VARCHAR(50) NULL,
    birth_date DATE NULL,
    mobile CHAR(11) NOT NULL,
    mobile_2 CHAR(11) NULL,                          -- موبایل دوم (برای متقاضیان دارای چند شماره)
    mobile_3 CHAR(11) NULL,                          -- موبایل سوم
    postal_code CHAR(10) NULL,
    person_type ENUM('حقیقی','حقوقی') NOT NULL DEFAULT 'حقیقی',   -- فاز ۱۰
    -- اطلاعات شخص حقوقی (فاز ۱۰)
    company_name VARCHAR(150) NULL,
    company_national_id CHAR(11) NULL,
    reg_number VARCHAR(30) NULL,
    reg_date DATE NULL,
    company_type VARCHAR(60) NULL,
    economic_code VARCHAR(20) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_person_mobile (mobile),
    INDEX idx_person_mobile_2 (mobile_2),
    INDEX idx_person_mobile_3 (mobile_3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ۲. گسترش پرونده‌ها
ALTER TABLE applicants
    ADD COLUMN person_id INT NULL AFTER id,
    ADD COLUMN parent_application_id INT NULL AFTER person_id,
    ADD COLUMN license_state ENUM('جاری','صادر شده','باطل شده','منقضی') NOT NULL DEFAULT 'جاری' AFTER priority,
    ADD COLUMN license_valid_until DATE NULL AFTER license_state,
    ADD INDEX idx_person (person_id),
    ADD INDEX idx_parent_app (parent_application_id),
    ADD INDEX idx_license_state (license_state);

ALTER TABLE applicants
    ADD CONSTRAINT fk_app_person FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_app_parent FOREIGN KEY (parent_application_id) REFERENCES applicants(id) ON DELETE SET NULL;

-- ۳. مهاجرت داده: برای هر کد ملی یک شخص بساز و پرونده‌ها را متصل کن
INSERT INTO persons (national_code, first_name, last_name, father_name, birth_date, mobile, postal_code)
SELECT a.national_code,
       SUBSTRING_INDEX(GROUP_CONCAT(a.first_name ORDER BY a.id DESC), ',', 1),
       SUBSTRING_INDEX(GROUP_CONCAT(a.last_name ORDER BY a.id DESC), ',', 1),
       SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(a.father_name,'') ORDER BY a.id DESC), ',', 1),
       MAX(a.birth_date),
       SUBSTRING_INDEX(GROUP_CONCAT(a.mobile ORDER BY a.id DESC), ',', 1),
       SUBSTRING_INDEX(GROUP_CONCAT(a.postal_code ORDER BY a.id DESC), ',', 1)
FROM applicants a
GROUP BY a.national_code
ON DUPLICATE KEY UPDATE national_code = national_code;

UPDATE applicants a
JOIN persons p ON p.national_code = a.national_code
SET a.person_id = p.id
WHERE a.person_id IS NULL;

-- پرونده‌های صادرشده → license_state
UPDATE applicants SET license_state = 'صادر شده'
WHERE current_status IN ('صدور مجوز','تحویل مجوز') AND license_state = 'جاری';

-- ۴. رفع یکتایی سراسری کد ملی/موبایل روی پرونده‌ها (یکتا در سطح persons باقی می‌ماند)
-- فقط قید UNIQUE حذف می‌شود؛ ایندکس‌های عادی idx_national_code/idx_mobile از قبل در schema وجود دارند
ALTER TABLE applicants DROP INDEX national_code;
ALTER TABLE applicants DROP INDEX mobile;

-- ۵. لاگ تلاش‌های احراز هویت (فاز ۸)
CREATE TABLE IF NOT EXISTS identity_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    national_code CHAR(10) NOT NULL,
    ip VARCHAR(45) NULL,
    success TINYINT(1) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nc_time (national_code, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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


USE handicraft_license;

CREATE TABLE IF NOT EXISTS service_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL UNIQUE,
    authority VARCHAR(150) NULL,        -- مرجع صدور
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- بذر سرویس‌ها (بر پایهٔ مراجع رسمی: وزارت میراث فرهنگی، وزارت کار، وزارت ارشاد)
INSERT INTO service_types (id, title, authority, description, sort_order) VALUES
(1, 'مجوز صنایع دستی', 'وزارت میراث فرهنگی، گردشگری و صنایع دستی',
 'صدور مجوز رشته‌های صنایع دستی برای اشخاص حقیقی — فرآیند پایهٔ سامانه.', 10),
(2, 'مجوز مشاغل خانگی (مستقل)', 'وزارت تعاون، کار و رفاه اجتماعی — سامانهٔ مشاغل خانگی',
 'کسب‌وکار خانگی توسط خود متقاضی در فضای مسکونی؛ همان مدارک پایه کفایت می‌کند.', 20),
(3, 'مجوز مشاغل خانگی (پشتیبان)', 'وزارت تعاون، کار و رفاه اجتماعی — سامانهٔ مشاغل خانگی',
 'پشتیبانِ چند شاغل خانگی: نیازمند طرح توجیهی، فهرست افراد تحت پوشش و جزئیات درآمدی (حداقل ۵ سال سابقهٔ مرتبط).', 30),
(4, 'مجوز گردشگری', 'وزارت میراث فرهنگی، گردشگری و صنایع دستی — درگاه ملی مجوزها / سامانهٔ جانا',
 'اقامتگاه بوم‌گردی، تأسیسات گردشگری و دفاتر خدمات مسافرتی.', 40),
(5, 'مجوز هنری و فرهنگی', 'وزارت فرهنگ و ارشاد اسلامی — پنجرهٔ واحد وفا',
 'فعالیت‌های هنری، آموزشگاه‌های آزاد هنری و مؤسسات فرهنگی.', 50)
ON DUPLICATE KEY UPDATE authority = VALUES(authority);

-- اتصال پرونده‌ها و فیلدها/مراحل به سرویس (NULL = مشترک بین همهٔ سرویس‌ها)
ALTER TABLE applicants
    ADD COLUMN service_type_id INT NULL AFTER person_id,
    ADD CONSTRAINT fk_app_service FOREIGN KEY (service_type_id) REFERENCES service_types(id) ON DELETE SET NULL;

ALTER TABLE form_fields
    ADD COLUMN service_type_id INT NULL AFTER wizard_step_id,
    ADD CONSTRAINT fk_field_service FOREIGN KEY (service_type_id) REFERENCES service_types(id) ON DELETE CASCADE;

ALTER TABLE wizard_steps
    ADD COLUMN service_type_id INT NULL AFTER view_key,
    ADD CONSTRAINT fk_step_service FOREIGN KEY (service_type_id) REFERENCES service_types(id) ON DELETE CASCADE;

-- مرحلهٔ اختصاصی «اطلاعات پشتیبان» برای سرویس پشتیبان (با سه فیلد الزامی)
INSERT INTO wizard_steps (title, description, icon, view_key, service_type_id, is_system, is_active, sort_order)
VALUES ('اطلاعات پشتیبان', 'طرح توجیهی و اطلاعات افراد تحت پوشش (اختصاصی مجوز پشتیبان)', 'users', NULL, 3, 0, 1, 35);

SET @wsid = LAST_INSERT_ID();
INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, sort_order, help_text) VALUES
(0, @wsid, 3, 'dyn_justification_plan', 'طرح توجیهی کسب‌وکار', 'textarea', 1, 0, 10,
 'شرح کامل طرح: محصول/خدمت، بازار هدف، برآورد هزینه و درآمد'),
(0, @wsid, 3, 'dyn_covered_persons', 'فهرست و مشخصات افراد تحت پوشش', 'textarea', 1, 0, 20,
 'نام، کد ملی و رشتهٔ فعالیت هر شاغل خانگی تحت پوشش (هر نفر یک خط)'),
(0, @wsid, 3, 'dyn_income_details', 'جزئیات درآمدی', 'textarea', 1, 0, 30,
 'منابع درآمد، سهم پشتیبان و شاغلان، و نحوهٔ تسویه');

-- =================================================================
-- مراحل اختصاصی مشاغل خانگی مستقل (service_type_id=2)
-- =================================================================

-- مرحلهٔ ۵: اطلاعات طرح و شغل
SET @hk_step5 = (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1);
INSERT INTO wizard_steps (title, description, icon, view_key, service_type_id, is_system, is_active, sort_order)
SELECT 'اطلاعات طرح و شغل', 'اطلاعات شغل، مهارت و طرح کسب‌وکار خانگی', 'briefcase', NULL, 2, 0, 1, 40
FROM DUAL WHERE @hk_step5 IS NULL;
SET @hk_step5 = LAST_INSERT_ID() WHERE @hk_step5 IS NULL;

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order) VALUES
(0, @hk_step5, 2, 'dyn_skill_level', 'سطح مهارت', 'select', 'دارای مهارت کامل انجام کسب و کار مورد نظر\nنیاز به آموزش\nنیاز به مشاوره', 1, 0, 1, 10),
(0, @hk_step5, 2, 'dyn_has_skill', 'داشتن مهارت لازم', 'radio', 'بله\nخیر', 1, 0, 1, 20),
(0, @hk_step5, 2, 'dyn_job_title', 'عنوان شغل', 'text', NULL, 1, 0, 1, 30),
(0, @hk_step5, 2, 'dyn_skill_acquisition', 'نحوه کسب مهارت', 'select', 'شیوه تجربی (استاد شاگردی - شغل پدری - روش خودآموز)\nشیوه آموزشی (آموزشگاه فنی و حرفه‌ای)\nشیوه دانشگاهی', 1, 0, 1, 40),
(0, @hk_step5, 2, 'dyn_need_support', 'نیاز به پشتیبانی', 'radio', 'بله\nخیر', 1, 0, 1, 50),
(0, @hk_step5, 2, 'dyn_cooperation', 'تمایل به همکاری', 'radio', 'بله\nخیر', 1, 0, 1, 60),
(0, @hk_step5, 2, 'dyn_plan_summary', 'خلاصه طرح', 'textarea', NULL, 1, 0, 1, 70),
(0, @hk_step5, 2, 'dyn_experience_months', 'ماه‌های تجربه', 'number', NULL, 1, 0, 1, 80),
(0, @hk_step5, 2, 'dyn_customers', 'مشتریان', 'checkbox', 'سفارشی\nمغازه یا کارگاه\nعرضه عمومی\nآنلاین (مجازی)', 0, 0, 1, 90),
(0, @hk_step5, 2, 'dyn_marketing_methods', 'روش‌های بازاریابی', 'checkbox', 'معاملاتی\nچهره به چهره\nتلفنی\nرابطه‌ای', 0, 0, 1, 100),
(0, @hk_step5, 2, 'dyn_target_markets', 'بازارهای هدف', 'checkbox', 'بازار داخل\nمنطقه‌ای\nصادرات', 0, 0, 1, 110),
(0, @hk_step5, 2, 'dyn_risks', 'ریسک‌ها', 'textarea', NULL, 0, 0, 1, 120)
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- مرحلهٔ ۶: اطلاعات مالی
SET @hk_step6 = (SELECT id FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2 LIMIT 1);
INSERT INTO wizard_steps (title, description, icon, view_key, service_type_id, is_system, is_active, sort_order)
SELECT 'اطلاعات مالی', 'سرمایه، وام، سود و هزینه تجهیزات', 'wallet', NULL, 2, 0, 1, 50
FROM DUAL WHERE @hk_step6 IS NULL;
SET @hk_step6 = LAST_INSERT_ID() WHERE @hk_step6 IS NULL;

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text) VALUES
(0, @hk_step6, 2, 'dyn_fixed_capital', 'سرمایه ثابت (میلیون ریال)', 'number', 1, 0, 1, 10, 'معادل X میلیون تومان'),
(0, @hk_step6, 2, 'dyn_working_capital', 'سرمایه در گردش (میلیون ریال)', 'number', 1, 0, 1, 20, 'معادل X میلیون تومان'),
(0, @hk_step6, 2, 'dyn_cash_share', 'سهم نقدی (میلیون ریال)', 'number', 1, 0, 1, 30, 'معادل X میلیون تومان'),
(0, @hk_step6, 2, 'dyn_noncash_share', 'سهم غیرنقدی (میلیون ریال)', 'number', 1, 0, 1, 40, 'معادل X میلیون تومان'),
(0, @hk_step6, 2, 'dyn_loan_request', 'درخواست وام (میلیون ریال)', 'number', 1, 0, 1, 50, 'معادل X میلیون تومان'),
(0, @hk_step6, 2, 'dyn_financial_rate', 'نرخ مالی', 'number', 0, 0, 1, 60, NULL),
(0, @hk_step6, 2, 'dyn_break_even', 'نقطه سر به سر (میلیون ریال)', 'number', 1, 0, 1, 70, 'معادل X میلیون تومان'),
(0, @hk_step6, 2, 'dyn_annual_profit', 'سود سالانه تخمینی (میلیون ریال)', 'number', 1, 0, 1, 80, 'معادل X میلیون تومان'),
(0, @hk_step6, 2, 'dyn_annual_production', 'تولید سالانه تخمینی (میلیون ریال)', 'number', 1, 0, 1, 90, 'معادل X میلیون تومان'),
(0, @hk_step6, 2, 'dyn_equipment_costs', 'هزینه تجهیزات (شرح کامل)', 'textarea', 0, 0, 1, 100, 'هر خط: عنوان، تعداد، قیمت واحد')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- مرحلهٔ ۷: محل فعالیت شخصی
SET @hk_step7 = (SELECT id FROM wizard_steps WHERE title = 'محل فعالیت شخصی' AND service_type_id = 2 LIMIT 1);
INSERT INTO wizard_steps (title, description, icon, view_key, service_type_id, is_system, is_active, sort_order)
SELECT 'محل فعالیت شخصی', 'مشخصات فضای فعالیت و امکانات', 'home', NULL, 2, 0, 1, 60
FROM DUAL WHERE @hk_step7 IS NULL;
SET @hk_step7 = LAST_INSERT_ID() WHERE @hk_step7 IS NULL;

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order) VALUES
(0, @hk_step7, 2, 'dyn_infrastructure_area', 'مساحت زیرساخت (متر)', 'number', NULL, 1, 0, 1, 10),
(0, @hk_step7, 2, 'dyn_property_ownership', 'مالکیت', 'select', 'ملک\nاجاره‌ای\nرایگان', 1, 0, 1, 20),
(0, @hk_step7, 2, 'dyn_building_type', 'نوع ساختمان', 'select', 'ویلا\nآپارتمان\nسایر', 1, 0, 1, 30),
(0, @hk_step7, 2, 'dyn_activity_area', 'مساحت فعالیت (متر)', 'number', NULL, 1, 0, 1, 40),
(0, @hk_step7, 2, 'dyn_facilities', 'امکانات', 'checkbox', 'تلفن ثابت\nگاز لوله‌کشی\nبرق\nآب لوله‌کشی', 0, 0, 1, 50)
ON DUPLICATE KEY UPDATE label = VALUES(label);


USE handicraft_license;

ALTER TABLE applicants
    ADD COLUMN applicant_type ENUM('حقیقی','حقوقی') NOT NULL DEFAULT 'حقیقی' AFTER current_status,
    ADD INDEX idx_applicant_type (applicant_type);

-- company_name, company_national_id, reg_number, reg_date, company_type, economic_code

-- =================================================================
-- نرخ‌نامهٔ چندگانه (درخواست کاربر)
-- -----------------------------------------------------------------
-- ۴ گروه قیمتی بر اساس دهک درآمدی + معافیت روستایی
-- =================================================================

CREATE TABLE IF NOT EXISTS fee_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(30),
    decile_min TINYINT NOT NULL,
    decile_max TINYINT NOT NULL,
    is_rural_eligible TINYINT(1) NOT NULL DEFAULT 0,
    total_amount BIGINT UNSIGNED NOT NULL,
    message_title VARCHAR(100),
    message_body TEXT,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_group_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fee_group_id INT NOT NULL,
    item_key VARCHAR(40) NOT NULL,
    item_label VARCHAR(150) NOT NULL,
    amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
    notes VARCHAR(255),
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (fee_group_id) REFERENCES fee_groups(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_group_item (fee_group_id, item_key),
    INDEX idx_group_sort (fee_group_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- تیک خدمات انجام‌شده برای هر پرداخت (واسط مدیر)
CREATE TABLE IF NOT EXISTS payment_service_ticks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    item_key VARCHAR(40) NOT NULL,
    item_label VARCHAR(150) NOT NULL,
    original_amount BIGINT UNSIGNED NOT NULL,
    is_done TINYINT(1) NOT NULL DEFAULT 1,
    ticked_by INT NULL,
    ticked_at DATETIME NULL,
    untick_reason VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (ticked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_payment_item (payment_id, item_key),
    INDEX idx_payment (payment_id),
    INDEX idx_done (is_done)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- تاریخچهٔ تغییرات مدیر روی پرداخت‌ها (audit log)
CREATE TABLE IF NOT EXISTS payment_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    field_changed VARCHAR(50) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    note VARCHAR(500) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_audit_payment (payment_id),
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ستون‌های جدید در payments برای نرخ‌نامه و تخفیف
ALTER TABLE payments
    ADD COLUMN original_amount BIGINT UNSIGNED NULL AFTER amount,
    ADD COLUMN final_amount BIGINT UNSIGNED NULL AFTER original_amount,
    ADD COLUMN discount_reason VARCHAR(500) NULL AFTER final_amount,
    ADD COLUMN fee_group_id INT NULL AFTER discount_reason,
    ADD COLUMN is_rural_exempt TINYINT(1) NOT NULL DEFAULT 0 AFTER fee_group_id,
    ADD COLUMN fallback_used TINYINT(1) NOT NULL DEFAULT 0 AFTER is_rural_exempt,
    ADD COLUMN suspicion_flags VARCHAR(255) NULL AFTER fallback_used,
    ADD INDEX idx_pay_group (fee_group_id),
    ADD INDEX idx_pay_suspicion (suspicion_flags(50));

-- مهاجرت: افزودن وضعیت 'در انتظار رسید' به ENUM پرداخت‌ها
-- این وضعیت برای زمانی است که مدیر درخواست پرداخت صادر کرده ولی هنوز رسیدی آپلود نشده.
ALTER TABLE payments
    MODIFY COLUMN status ENUM('در انتظار رسید','در انتظار بررسی','تأیید شده','رد شده')
    NOT NULL DEFAULT 'در انتظار رسید';

-- مهاجرت: افزودن ستون sort_order به payment_service_ticks (در صورت نبود)
-- این مهاجرت idempotent است — اگر ستون از قبل موجود باشد، نادیده گرفته می‌شود.
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'payment_service_ticks'
                     AND COLUMN_NAME = 'sort_order');
SET @ddl = IF(@col_exists = 0,
    'ALTER TABLE payment_service_ticks ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER untick_reason',
    'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- بذر ۴ گروه قیمتی + ۱۲ خدمت برای هر گروه
INSERT INTO fee_groups (code, name, icon, decile_min, decile_max, is_rural_eligible, total_amount, message_title, message_body, sort_order) VALUES
('A', 'گروه الف: حمایتی', '🌱', 1, 2, 1, 1350000,
 'پیام ما به شما',
 'هنرمند عزیز روستایی، ما می‌دانیم که برای آمدن به شهر و انجام این کارها، هم هزینه رفت‌وآمد می‌دهید و هم وقت ارزشمندتان را صرف می‌کنید. به همین دلیل، تمام تلاشمان را می‌کنیم که در یک مراجعه، تمام کارهایتان انجام شود. اگر هم پرونده‌تان نقص داشت، خودمان با شما تماس می‌گیریم و بدون هزینه اضافی، رفع نقص می‌کنیم. هنر شما سرمایه این سرزمین است و ما مفتخریم که در خدمت شما هستیم.',
 10),
('B', 'گروه ب: متوسط آسیب‌پذیر', '🌿', 3, 4, 0, 2250000,
 'پیام ما به شما',
 'هنرمند گرامی، ما می‌دانیم که شما با دستان هنرمندتان، آبروی خانواده‌تان را تأمین می‌کنید. هر ریالی که از شما می‌گیریم، در قبال خدماتی است که واقعاً انجام می‌دهیم. اگر در هر مرحله از کار، سوالی داشتید یا نگرانی برایتان پیش آمد، با روی خوش پاسخگو هستیم.',
 20),
('C', 'گروه ج: متوسط', '🌳', 5, 6, 0, 3800000,
 'پیام ما به شما',
 'همشهری گرامی، شفافیت، اصل اول کار ماست. شما با پرداخت این مبلغ، صاحب یک پرونده کامل و بدون نقص خواهید شد که در سریع‌ترین زمان ممکن به نتیجه می‌رسد. ما به وقت شما احترام می‌گذاریم.',
 30),
('D', 'گروه د: حرفه‌ای/VIP', '🌲', 7, 10, 0, 6500000,
 'پیام ما به شما',
 'همراه گرامی، این پکیج ویژه برای کسانی طراحی شده که به دنبال خدمات حرفه‌ای، سریع و بدون دغدغه هستند. در این پکیج، ما تمام مراحل را از ابتدا تا انتها به صورت اختصاصی برای شما مدیریت می‌کنیم.',
 40)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- بذر ۱۲ خدمت پایه برای هر گروه (مبالغ اولیه)
-- کلیدهای آیتم‌ها
INSERT INTO fee_group_items (fee_group_id, item_key, item_label, amount, sort_order) VALUES
-- گروه الف
((SELECT id FROM fee_groups WHERE code='A'), 'service_registration', 'ثبت‌نام و احراز هویت در درگاه ملی مجوزها', 80000, 10),
((SELECT id FROM fee_groups WHERE code='A'), 'service_postal_code', 'ثبت/اصلاح کد پستی در سامانه املاک و اسکان', 40000, 20),
((SELECT id FROM fee_groups WHERE code='A'), 'service_document_upload', 'ثبت و بارگذاری مدارک در سامانه میراث فرهنگی', 300000, 30),
((SELECT id FROM fee_groups WHERE code='A'), 'service_address_verify', 'احراز نشانی پستی (GNAF)', 120000, 40),
((SELECT id FROM fee_groups WHERE code='A'), 'service_scan', 'اسکن مدارک (تا ۱۰ صفحه)', 40000, 50),
((SELECT id FROM fee_groups WHERE code='A'), 'service_license_print', 'چاپ مجوز نهایی', 40000, 60),
((SELECT id FROM fee_groups WHERE code='A'), 'service_delivery', 'ارسال فایل نهایی به موبایل', 40000, 70),
((SELECT id FROM fee_groups WHERE code='A'), 'service_followup', 'پیگیری و رفع نقص احتمالی', 400000, 80),
((SELECT id FROM fee_groups WHERE code='A'), 'service_license_image', 'بارگذاری تصویر مجوز + توکن ۷۲ ساعته', 50000, 90),
((SELECT id FROM fee_groups WHERE code='A'), 'service_revision_cycle', 'چرخهٔ بازبینی پرونده', 50000, 100),
((SELECT id FROM fee_groups WHERE code='A'), 'service_sms_status', 'ثبت پیامک‌های وضعیت با ارتقای خودکار', 40000, 110),
((SELECT id FROM fee_groups WHERE code='A'), 'service_multi_license', 'پشتیبانی چندمجوزی + احراز شش‌عاملی', 150000, 120),
-- گروه ب
((SELECT id FROM fee_groups WHERE code='B'), 'service_registration', 'ثبت‌نام و احراز هویت در درگاه ملی مجوزها', 240000, 10),
((SELECT id FROM fee_groups WHERE code='B'), 'service_postal_code', 'ثبت/اصلاح کد پستی در سامانه املاک و اسکان', 80000, 20),
((SELECT id FROM fee_groups WHERE code='B'), 'service_document_upload', 'ثبت و بارگذاری مدارک در سامانه میراث فرهنگی', 650000, 30),
((SELECT id FROM fee_groups WHERE code='B'), 'service_address_verify', 'احراز نشانی پستی (GNAF)', 160000, 40),
((SELECT id FROM fee_groups WHERE code='B'), 'service_scan', 'اسکن مدارک (تا ۱۰ صفحه)', 80000, 50),
((SELECT id FROM fee_groups WHERE code='B'), 'service_license_print', 'چاپ مجوز نهایی', 80000, 60),
((SELECT id FROM fee_groups WHERE code='B'), 'service_delivery', 'ارسال فایل نهایی به موبایل', 40000, 70),
((SELECT id FROM fee_groups WHERE code='B'), 'service_followup', 'پیگیری و رفع نقص احتمالی', 480000, 80),
((SELECT id FROM fee_groups WHERE code='B'), 'service_license_image', 'بارگذاری تصویر مجوز + توکن ۷۲ ساعته', 80000, 90),
((SELECT id FROM fee_groups WHERE code='B'), 'service_revision_cycle', 'چرخهٔ بازبینی پرونده', 80000, 100),
((SELECT id FROM fee_groups WHERE code='B'), 'service_sms_status', 'ثبت پیامک‌های وضعیت با ارتقای خودکار', 60000, 110),
((SELECT id FROM fee_groups WHERE code='B'), 'service_multi_license', 'پشتیبانی چندمجوزی + احراز شش‌عاملی', 220000, 120),
-- گروه ج
((SELECT id FROM fee_groups WHERE code='C'), 'service_registration', 'ثبت‌نام و احراز هویت در درگاه ملی مجوزها', 400000, 10),
((SELECT id FROM fee_groups WHERE code='C'), 'service_postal_code', 'ثبت/اصلاح کد پستی در سامانه املاک و اسکان', 120000, 20),
((SELECT id FROM fee_groups WHERE code='C'), 'service_document_upload', 'ثبت و بارگذاری مدارک در سامانه میراث فرهنگی', 1100000, 30),
((SELECT id FROM fee_groups WHERE code='C'), 'service_address_verify', 'احراز نشانی پستی (GNAF)', 240000, 40),
((SELECT id FROM fee_groups WHERE code='C'), 'service_scan', 'اسکن مدارک (تا ۱۰ صفحه)', 120000, 50),
((SELECT id FROM fee_groups WHERE code='C'), 'service_license_print', 'چاپ مجوز نهایی', 120000, 60),
((SELECT id FROM fee_groups WHERE code='C'), 'service_delivery', 'ارسال فایل نهایی به موبایل', 40000, 70),
((SELECT id FROM fee_groups WHERE code='C'), 'service_followup', 'پیگیری و رفع نقص احتمالی', 800000, 80),
((SELECT id FROM fee_groups WHERE code='C'), 'service_license_image', 'بارگذاری تصویر مجوز + توکن ۷۲ ساعته', 120000, 90),
((SELECT id FROM fee_groups WHERE code='C'), 'service_revision_cycle', 'چرخهٔ بازبینی پرونده', 120000, 100),
((SELECT id FROM fee_groups WHERE code='C'), 'service_sms_status', 'ثبت پیامک‌های وضعیت با ارتقای خودکار', 100000, 110),
((SELECT id FROM fee_groups WHERE code='C'), 'service_multi_license', 'پشتیبانی چندمجوزی + احراز شش‌عاملی', 120000, 120),
-- گروه د
((SELECT id FROM fee_groups WHERE code='D'), 'service_registration', 'ثبت‌نام و احراز هویت در درگاه ملی مجوزها', 650000, 10),
((SELECT id FROM fee_groups WHERE code='D'), 'service_postal_code', 'ثبت/اصلاح کد پستی در سامانه املاک و اسکان', 240000, 20),
((SELECT id FROM fee_groups WHERE code='D'), 'service_document_upload', 'ثبت و بارگذاری مدارک در سامانه میراث فرهنگی', 1900000, 30),
((SELECT id FROM fee_groups WHERE code='D'), 'service_address_verify', 'احراز نشانی پستی (GNAF)', 360000, 40),
((SELECT id FROM fee_groups WHERE code='D'), 'service_scan', 'اسکن مدارک (تا ۱۰ صفحه)', 200000, 50),
((SELECT id FROM fee_groups WHERE code='D'), 'service_license_print', 'چاپ مجوز نهایی', 200000, 60),
((SELECT id FROM fee_groups WHERE code='D'), 'service_delivery', 'ارسال فایل نهایی به موبایل', 80000, 70),
((SELECT id FROM fee_groups WHERE code='D'), 'service_followup', 'پیگیری و رفع نقص احتمالی', 1500000, 80),
((SELECT id FROM fee_groups WHERE code='D'), 'service_license_image', 'بارگذاری تصویر مجوز + توکن ۷۲ ساعته', 200000, 90),
((SELECT id FROM fee_groups WHERE code='D'), 'service_revision_cycle', 'چرخهٔ بازبینی پرونده', 200000, 100),
((SELECT id FROM fee_groups WHERE code='D'), 'service_sms_status', 'ثبت پیامک‌های وضعیت با ارتقای خودکار', 160000, 110),
((SELECT id FROM fee_groups WHERE code='D'), 'service_multi_license', 'پشتیبانی چندمجوزی + احراز شش‌عاملی', 810000, 120)
ON DUPLICATE KEY UPDATE item_label = VALUES(item_label), amount = VALUES(amount);

-- تنظیمات نرخ‌نامه
INSERT INTO settings (setting_key, setting_value) VALUES
('fee_tariff_active', '1'),
('fee_tariff_valid_until', '1405/12/29'),
('fee_default_amount', '500000'),
('fee_rural_auto_eligible', '1'),
('fee_modal_enabled', '1'),
('fee_modal_dismissable', '1'),
('fee_fallback_group_code', 'C'),
('fee_min_amount_percent', '10'),
('fee_max_discount_without_reason', '2'),
('fee_max_discount_with_reason', '50')
ON DUPLICATE KEY UPDATE setting_key = setting_key;


-- =================================================================
-- حساب‌های کاربری متقاضیان (مدل فروشگاهی)
-- -----------------------------------------------------------------
-- کاربران آزادانه ثبت‌نام می‌کنند، درخواست‌ها را «خریداری» می‌کنند
-- سابقهٔ خرید و پرداخت متمرکز روی حساب کاربر
-- =================================================================

CREATE TABLE IF NOT EXISTS user_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL UNIQUE,
    username VARCHAR(50) UNIQUE NULL,
    mobile CHAR(11) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(150) NULL,
    avatar_document_id INT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    failed_login_count INT NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE CASCADE,
    FOREIGN KEY (avatar_document_id) REFERENCES documents(id) ON DELETE SET NULL,
    INDEX idx_username (username),
    INDEX idx_mobile (mobile),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- مهاجرت idempotent: افزودن ستون user_account_id به applicants
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'applicants'
                     AND COLUMN_NAME = 'user_account_id');
SET @ddl = IF(@col_exists = 0,
    'ALTER TABLE applicants ADD COLUMN user_account_id INT NULL AFTER person_id, ADD INDEX idx_applicant_user (user_account_id)',
    'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- مهاجرت idempotent: افزودن ستون user_account_id به payments
SET @col_pay = (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'payments'
                  AND COLUMN_NAME = 'user_account_id');
SET @ddl_pay = IF(@col_pay = 0,
    'ALTER TABLE payments ADD COLUMN user_account_id INT NULL AFTER applicant_id, ADD INDEX idx_pay_user (user_account_id)',
    'SELECT 1');
PREPARE stmt_pay FROM @ddl_pay;
EXECUTE stmt_pay;
DEALLOCATE PREPARE stmt_pay;

-- =================================================================
-- اعلان‌های کاربر (نظام اعلان دیده‌شده/دیده‌نشده)
-- =================================================================
CREATE TABLE IF NOT EXISTS user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_account_id INT NOT NULL,
    applicant_id INT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    body TEXT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_account_id) REFERENCES user_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    INDEX idx_notif_user (user_account_id),
    INDEX idx_notif_unread (user_account_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- ستون‌های موبایل دوم/سوم برای اشخاص (ایمپورت دسته‌جمعی)
-- -----------------------------------------------------------------
-- برای متقاضیانی که در فایل CSV با چند شماره موبایل ظاهر می‌شوند،
-- شماره‌های اضافی در mobile_2 و mobile_3 ذخیره می‌شوند.
-- این مهاجرت idempotent است و روی پایگاه‌های داده موجود قابل اجراست.
-- =================================================================

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
