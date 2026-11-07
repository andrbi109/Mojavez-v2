-- =============================================
-- سامانه مدیریت ثبت‌نام و پیگیری صدور مجوز صنایع دستی
-- اسکیمای پایگاه داده - MySQL/MariaDB
-- =============================================

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

-- درج مدیر پیش‌فرض (نام کاربری: admin، رمز: admin123)
-- رمز عبور با password_hash تولید شده است
INSERT INTO users (username, password, display_name) VALUES
('admin', '$2y$10$mzPwgNnWxY74YtWVJYozmupleWLNc8MR.A4.8ph4i4US2Kc7.Aad6', 'امین دروگر بزدی');
-- توجه: لطفاً رمز عبور واقعی را با password_hash('admin123', PASSWORD_BCRYPT) جایگزین کنید