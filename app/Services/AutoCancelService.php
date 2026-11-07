<?php
/**
 * سرویس لغو خودکار درخواست‌های منقضی شده
 * بدون نیاز به cron job - در نقاط ورودی کلیدی فراخوانی می‌شود
 */
class AutoCancelService
{
    /**
     * لغو درخواست‌هایی که از زمان ثبت درخواست صدور آن‌ها بیش از ۱۴ روز گذشته
     * و هنوز مجوز صادر نشده است
     */
    public static function cancelExpired(): int
    {
        $applicantModel = new Applicant();
        $statusLogModel = new StatusLog();

        $expiredApplications = $applicantModel->findExpiredApplications(AUTO_CANCEL_DAYS);
        $cancelledCount = 0;

        foreach ($expiredApplications as $app) {
            // تغییر وضعیت به لغو
            $applicantModel->updateStatus($app['id'], 'لغو');

            // ثبت در تاریخچه
            $statusLogModel->create([
                'applicant_id' => $app['id'],
                'status_name' => 'لغو',
                'description' => 'لغو خودکار به دلیل گذشت بیش از ' . AUTO_CANCEL_DAYS . ' روز از ثبت درخواست صدور مجوز'
            ]);

            $cancelledCount++;
        }

        return $cancelledCount;
    }

    /**
     * به‌روزرسانی پرچم اولویت برای ثبت‌نام‌های طولانی
     */
    public static function updatePriorities(): int
    {
        $db = Database::getInstance()->getConnection();

        // محاسبه اختلاف بین registration_started_at و registration_completed_at
        $sql = "UPDATE applicants
                SET priority = 1
                WHERE priority = 0
                AND registration_started_at IS NOT NULL
                AND registration_completed_at IS NOT NULL
                AND TIMESTAMPDIFF(SECOND, registration_started_at, registration_completed_at) > :seconds";

        $stmt = $db->prepare($sql);
        $stmt->execute(['seconds' => PRIORITY_DAYS * 86400]); // تبدیل روز به ثانیه
        return $stmt->rowCount();
    }
}