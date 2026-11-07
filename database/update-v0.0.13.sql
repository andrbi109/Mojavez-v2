-- =============================================
-- به‌روزرسانی v0.0.13 — فاز ۹ نقشهٔ راه
-- انواع خدمات: صنایع دستی، مشاغل خانگی (مستقل/پشتیبان)، گردشگری، هنری
-- =============================================

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
