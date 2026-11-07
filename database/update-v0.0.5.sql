-- =============================================
-- به‌روزرسانی v0.0.5
-- فرم‌ساز پویا + تنظیمات سامانه + مقادیر فیلدهای پویا
-- =============================================

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
