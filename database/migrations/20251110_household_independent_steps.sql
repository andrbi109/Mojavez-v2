-- =================================================================
-- مهاجرت: افزودن مراحل اختصاصی مشاغل خانگی مستقل (service_type_id=2)
-- -----------------------------------------------------------------
-- ۳ مرحلهٔ جدید اضافه می‌شود:
--   ۵) اطلاعات طرح و شغل
--   ۶) اطلاعات مالی
--   ۷) محل فعالیت شخصی
-- مراحل ۱-۴ (هویتی، شغلی، ملک، مدارک) مشترک هستند و از قبل موجودند.
--
-- نکته: این مهاجرت idempotent است — می‌توان آن را چند بار در phpMyAdmin اجرا کرد.
-- اگر رکوردی از قبل وجود داشته باشد، skip می‌شود.
-- =================================================================

-- ==================== مرحلهٔ ۵: اطلاعات طرح و شغل ====================

-- مرحله را بساز (اگر وجود ندارد)
INSERT INTO wizard_steps (title, description, icon, view_key, service_type_id, is_system, is_active, sort_order)
SELECT 'اطلاعات طرح و شغل', 'اطلاعات شغل، مهارت و طرح کسب‌وکار خانگی', 'briefcase', NULL, 2, 0, 1, 40
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2
);

-- شناسهٔ مرحله را ذخیره کن
SET @hk_step5 = (SELECT id FROM wizard_steps WHERE title = 'اطلاعات طرح و شغل' AND service_type_id = 2 LIMIT 1);

-- فیلدها (هر کدام فقط اگر وجود نداشته باشد اضافه شود)
INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step5, 2, 'dyn_skill_level', 'سطح مهارت', 'select',
    'دارای مهارت کامل انجام کسب و کار مورد نظر\nنیاز به آموزش\nنیاز به مشاوره',
    1, 0, 1, 10
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_skill_level' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step5, 2, 'dyn_has_skill', 'داشتن مهارت لازم', 'radio',
    'بله\nخیر',
    1, 0, 1, 20
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_has_skill' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, placeholder)
SELECT 0, @hk_step5, 2, 'dyn_job_title', 'عنوان شغل', 'text',
    1, 0, 1, 30, 'مثلاً قالیباف، فرَتَ باف، سفالگر'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_job_title' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step5, 2, 'dyn_skill_acquisition', 'نحوه کسب مهارت', 'select',
    'شیوه تجربی (استاد شاگردی - شغل پدری - روش خودآموز)\nشیوه آموزشی (آموزشگاه فنی و حرفه‌ای)\nشیوه دانشگاهی',
    1, 0, 1, 40
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_skill_acquisition' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step5, 2, 'dyn_need_support', 'نیاز به پشتیبانی', 'radio',
    'بله\nخیر',
    1, 0, 1, 50
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_need_support' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step5, 2, 'dyn_cooperation', 'تمایل به همکاری', 'radio',
    'بله\nخیر',
    1, 0, 1, 60
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_cooperation' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, placeholder)
SELECT 0, @hk_step5, 2, 'dyn_plan_summary', 'خلاصه طرح', 'textarea',
    1, 0, 1, 70, 'مثلاً تولید محصولات دستباف سنتی'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_plan_summary' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step5, 2, 'dyn_experience_months', 'ماه‌های تجربه', 'number',
    1, 0, 1, 80
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_experience_months' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, @hk_step5, 2, 'dyn_customers', 'مشتریان', 'checkbox',
    'سفارشی\nمغازه یا کارگاه\nعرضه عمومی\nآنلاین (مجازی)',
    0, 0, 1, 90, 'می‌توانید چند گزینه انتخاب کنید'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_customers' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step5, 2, 'dyn_marketing_methods', 'روش‌های بازاریابی', 'checkbox',
    'معاملاتی\nچهره به چهره\nتلفنی\nرابطه‌ای',
    0, 0, 1, 100
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_marketing_methods' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step5, 2, 'dyn_target_markets', 'بازارهای هدف', 'checkbox',
    'بازار داخل\nمنطقه‌ای\nصادرات',
    0, 0, 1, 110
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_target_markets' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, @hk_step5, 2, 'dyn_risks', 'ریسک‌ها', 'textarea',
    0, 0, 1, 120, 'ریسک‌های احتمالی طرح'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_risks' AND service_type_id = 2);

-- ==================== مرحلهٔ ۶: اطلاعات مالی ====================

INSERT INTO wizard_steps (title, description, icon, view_key, service_type_id, is_system, is_active, sort_order)
SELECT 'اطلاعات مالی', 'سرمایه، وام، سود و هزینه تجهیزات', 'wallet', NULL, 2, 0, 1, 50
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2
);

SET @hk_step6 = (SELECT id FROM wizard_steps WHERE title = 'اطلاعات مالی' AND service_type_id = 2 LIMIT 1);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, @hk_step6, 2, 'dyn_fixed_capital', 'سرمایه ثابت (میلیون ریال)', 'number',
    1, 0, 1, 10, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_fixed_capital' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, @hk_step6, 2, 'dyn_working_capital', 'سرمایه در گردش (میلیون ریال)', 'number',
    1, 0, 1, 20, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_working_capital' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, @hk_step6, 2, 'dyn_cash_share', 'سهم نقدی (میلیون ریال)', 'number',
    1, 0, 1, 30, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_cash_share' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, @hk_step6, 2, 'dyn_noncash_share', 'سهم غیرنقدی (میلیون ریال)', 'number',
    1, 0, 1, 40, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_noncash_share' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, @hk_step6, 2, 'dyn_loan_request', 'درخواست وام (میلیون ریال)', 'number',
    1, 0, 1, 50, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_loan_request' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step6, 2, 'dyn_financial_rate', 'نرخ مالی', 'number',
    0, 0, 1, 60
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_financial_rate' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, @hk_step6, 2, 'dyn_break_even', 'نقطه سر به سر (میلیون ریال)', 'number',
    1, 0, 1, 70, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_break_even' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, @hk_step6, 2, 'dyn_annual_profit', 'سود سالانه تخمینی (میلیون ریال)', 'number',
    1, 0, 1, 80, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_annual_profit' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, @hk_step6, 2, 'dyn_annual_production', 'تولید سالانه تخمینی (میلیون ریال)', 'number',
    1, 0, 1, 90, 'معادل X میلیون تومان'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_annual_production' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, @hk_step6, 2, 'dyn_equipment_costs', 'هزینه تجهیزات (شرح کامل)', 'textarea',
    0, 0, 1, 100, 'هر خط: عنوان، تعداد، قیمت واحد'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_equipment_costs' AND service_type_id = 2);

-- ==================== مرحلهٔ ۷: محل فعالیت شخصی ====================

INSERT INTO wizard_steps (title, description, icon, view_key, service_type_id, is_system, is_active, sort_order)
SELECT 'محل فعالیت شخصی', 'مشخصات فضای فعالیت و امکانات', 'home', NULL, 2, 0, 1, 60
FROM DUAL WHERE NOT EXISTS (
    SELECT 1 FROM wizard_steps WHERE title = 'محل فعالیت شخصی' AND service_type_id = 2
);

SET @hk_step7 = (SELECT id FROM wizard_steps WHERE title = 'محل فعالیت شخصی' AND service_type_id = 2 LIMIT 1);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step7, 2, 'dyn_infrastructure_area', 'مساحت زیرساخت (متر)', 'number',
    1, 0, 1, 10
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_infrastructure_area' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step7, 2, 'dyn_property_ownership', 'مالکیت', 'select',
    'ملک\nاجاره‌ای\nرایگان',
    1, 0, 1, 20
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_property_ownership' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step7, 2, 'dyn_building_type', 'نوع ساختمان', 'select',
    'ویلا\nآپارتمان\nسایر',
    1, 0, 1, 30
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_building_type' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, is_required, is_system, is_active, sort_order)
SELECT 0, @hk_step7, 2, 'dyn_activity_area', 'مساحت فعالیت (متر)', 'number',
    1, 0, 1, 40
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_activity_area' AND service_type_id = 2);

INSERT INTO form_fields (step, wizard_step_id, service_type_id, field_key, label, field_type, options, is_required, is_system, is_active, sort_order, help_text)
SELECT 0, @hk_step7, 2, 'dyn_facilities', 'امکانات', 'checkbox',
    'تلفن ثابت\nگاز لوله‌کشی\nبرق\nآب لوله‌کشی',
    0, 0, 1, 50, 'می‌توانید چند گزینه انتخاب کنید'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM form_fields WHERE field_key = 'dyn_facilities' AND service_type_id = 2);

-- ==================== گزارش نهایی ====================
SELECT 'Migration completed successfully' AS result;
SELECT COUNT(*) AS household_steps FROM wizard_steps WHERE service_type_id = 2;
SELECT COUNT(*) AS household_fields FROM form_fields WHERE service_type_id = 2;
