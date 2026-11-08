<?php
/**
 * فهرست مرجع انواع مدرک (doc_types)
 * =================================================================
 * این فایل منبع حقیقت واحد برای انواع مدرک قابل آپلود است.
 *
 * مسائل برطرف‌شده:
 *   - D-A-3 / D-F-1: ۵ فهرست مجاز doc-type مختلف و متناقض در ۵ کنترلر
 *
 * استفاده:
 *   $allowed = DocTypes::allowedForUserUpload();   // آپلود کاربر
 *   $allowed = DocTypes::allowedForAdminAdd();     // افزودن مدیر
 *   $label = DocTypes::label('national_card');
 *
 * ثابت‌های ApplicantRegistrationService::FILE_INPUT_TO_DOC_TYPE برای نگاشت
 * نام ورودی فرم → doc_type ذخیره‌شده در documents استفاده می‌شوند.
 */

class DocTypes
{
    /**
     * همهٔ انواع مدرک با برچسب فارسی.
     * این فهرست مرجع است — هر تغییری فقط در اینجا اعمال شود.
     */
    public const ALL = [
        'national_card_front' => 'کارت ملی (رو)',
        'national_card_back'  => 'کارت ملی (پشت)',
        'national_card'       => 'کارت ملی',
        'birth_cert'          => 'شناسنامه',
        'ownership_doc'        => 'مدرک مالکیت',
        'lease_agreement'     => 'قرارداد اجاره',
        'supervisor_doc'      => 'مدرک سرپرستی',
        'vocational_cert'     => 'گواهینامه فنی‌وحرفه‌ای',
        'education_degree'    => 'مدرک تحصیلی',
        'product_photos'      => 'تصویر محصول',
        'optional_1'          => 'مدرک اختیاری ۱',
        'optional_2'          => 'مدرک اختیاری ۲',
        'optional_3'          => 'مدرک اختیاری ۳',
        'optional'             => 'سایر مدارک',
        'payment_receipt'     => 'رسید پرداخت',
        'license_image'       => 'تصویر مجوز',
        'dynamic'             => 'مدرک سفارشی',
        'revision_doc'        => 'مدرک بازبینی (موقت)',
    ];

    /**
     * انواع مدرک مجاز برای آپلود کاربر (ویزارد، فرم تغییرات).
     * شامل front/back separation برای کارت ملی.
     */
    public const USER_UPLOAD = [
        'national_card_front', 'national_card_back',
        'birth_cert', 'ownership_doc',
        'vocational_cert', 'education_degree',
        'product_photos',
        'optional_1', 'optional_2', 'optional_3',
    ];

    /**
     * انواع مدرک مجاز برای افزودن توسط مدیر (addDocument).
     * شامل lease_agreement و supervisor_doc که فقط مدیر می‌تواند اضافه کند.
     */
    public const ADMIN_ADD = [
        'national_card', 'birth_cert', 'ownership_doc',
        'lease_agreement', 'supervisor_doc',
        'vocational_cert', 'education_degree',
        'product_photos', 'optional',
    ];

    /**
     * دریافت برچسب فارسی نوع مدرک.
     */
    public static function label(string $docType): string
    {
        return self::ALL[$docType] ?? $docType;
    }

    /**
     * آیا نوع مدرک مجاز است (در فهرست کاربر یا مدیر).
     */
    public static function isAllowed(string $docType, bool $adminContext = false): bool
    {
        if ($adminContext) {
            return in_array($docType, self::ADMIN_ADD, true);
        }
        return in_array($docType, self::USER_UPLOAD, true);
    }

    /**
     * فهرست مجاز برای آپلود کاربر.
     */
    public static function allowedForUserUpload(): array
    {
        return self::USER_UPLOAD;
    }

    /**
     * فهرست مجاز برای افزودن توسط مدیر.
     */
    public static function allowedForAdminAdd(): array
    {
        return self::ADMIN_ADD;
    }

    /**
     * فهرست گزینه‌ها برای select در فرم افزودن مدرک (admin).
     * @return array<string> [doc_type => label, ...]
     */
    public static function adminAddOptions(): array
    {
        $options = [];
        foreach (self::ADMIN_ADD as $dt) {
            $options[$dt] = self::label($dt);
        }
        return $options;
    }
}
