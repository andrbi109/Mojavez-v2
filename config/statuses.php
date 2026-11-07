<?php
/**
 * مرجع مرکزی وضعیت‌های پرونده (Single Source of Truth)
 * ----------------------------------------------------------------
 * ترتیب آرایه = ترتیب یکطرفهٔ رسیدگی. هر وضعیت:
 *   order  → شمارهٔ ترتیب برای جلوگیری از بازگشت
 *   color  → کلاس رنگ نشان (بوت‌استرپ)
 *   manual → آیا مدیر می‌تواند دستی تنظیم کند
 *   cancelable_before → آیا جزو وضعیت‌های «پیش از صدور» برای لغو خودکار است
 *
 * ⚠️ این فهرست باید با ENUM ستون applicants.current_status
 *    در database/schema.sql هم‌خوان باشد.
 */
return [
    'statuses' => [
        'پیش‌نویس'              => ['order' => 1,  'color' => 'secondary', 'manual' => true,  'pre_issue' => true],
        'تکمیل اطلاعات'          => ['order' => 2,  'color' => 'info',      'manual' => true,  'pre_issue' => true],
        'احراز نشانی'            => ['order' => 3,  'color' => 'primary',   'manual' => true,  'pre_issue' => true],
        'بررسی کارشناسی رشته'    => ['order' => 4,  'color' => 'primary',   'manual' => true,  'pre_issue' => true],
        'ثبت درخواست صدور'       => ['order' => 5,  'color' => 'primary',   'manual' => true,  'pre_issue' => true],
        'بارگذاری مدارک به سازمان' => ['order' => 6, 'color' => 'warning',   'manual' => true,  'pre_issue' => true],
        'درخواست اصلاح مدارک'    => ['order' => 7,  'color' => 'warning',   'manual' => true,  'pre_issue' => true],
        'تأیید اولیه'            => ['order' => 8,  'color' => 'success',   'manual' => true,  'pre_issue' => true],
        'تأیید نهایی'            => ['order' => 9,  'color' => 'success',   'manual' => true,  'pre_issue' => true],
        'صدور مجوز'              => ['order' => 10, 'color' => 'success',   'manual' => true,  'pre_issue' => false],
        'تحویل مجوز'             => ['order' => 11, 'color' => 'success',   'manual' => true,  'pre_issue' => false],
        'مشاهده و چاپ'           => ['order' => 12, 'color' => 'dark',      'manual' => false, 'pre_issue' => false],
        'رد درخواست'             => ['order' => 98, 'color' => 'danger',    'manual' => true,  'pre_issue' => false],
        'لغو'                    => ['order' => 99, 'color' => 'danger',    'manual' => true,  'pre_issue' => false],
    ],

    // وضعیت‌های پایانی (پرونده بسته شده است)
    'terminal' => ['صدور مجوز', 'تحویل مجوز', 'رد درخواست', 'لغو'],

    // وضعیت‌های پیش از صدور (مشمول لغو خودکار ۱۴ روزه)
    'pre_issue' => [
        'پیش‌نویس', 'تکمیل اطلاعات', 'احراز نشانی', 'بررسی کارشناسی رشته',
        'ثبت درخواست صدور', 'بارگذاری مدارک به سازمان', 'درخواست اصلاح مدارک',
        'تأیید اولیه', 'تأیید نهایی',
    ],
];
