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
