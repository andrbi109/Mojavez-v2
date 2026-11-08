<?php
/**
 * کنترلر مشاهدهٔ سوابق متقاضی (بازنویسی‌شده برای مدل فروشگاهی)
 * =====================================================================
 * تغییر: به‌جای احراز هویت ۶ عاملی، از نشست کاربر استفاده می‌شود.
 * کاربر اگر وارد شده باشد، مستقیماً سوابق خود را می‌بیند.
 * اگر وارد نشده باشد، به صفحهٔ ورود هدایت می‌شود.
 *
 * محافظت‌ها:
 *  - نیاز به ورود کاربر (session-based)
 *  - نمایش سوابق فقط برای حساب کاربری خود کاربر
 *  - جدول identity_checks همچنان برای audit حفظ می‌شود
 */
class RecordsController extends Controller
{
    private UserAccount $userModel;

    public function __construct()
    {
        $this->userModel = new UserAccount();
    }

    /**
     * نمایش سوابق — نیاز به ورود کاربر
     */
    public function form(): void
    {
        // اگر کاربر وارد نشده → هدایت به ورود
        if (!Session::get('user_logged_in')) {
            Session::set('redirect_after_login', '/my-records');
            Session::set('login_error', 'برای مشاهدهٔ سوابق خود، ابتدا وارد شوید.');
            $this->redirect('/login');
        }

        $userId = (int)Session::get('user_id', 0);
        $user = $this->userModel->findById($userId);

        if (!$user) {
            Session::destroy();
            $this->redirect('/login');
        }

        // دریافت تمام درخواست‌ها (پرونده‌ها) و پرداخت‌ها
        $applications = $this->userModel->getApplications($userId);
        $payments = $this->userModel->getPayments($userId);

        $this->render('records', [
            'title' => 'سوابق من',
            'user' => $user,
            'applications' => $applications,
            'payments' => $payments,
        ]);
    }

    /**
     * متد verify حذف شد — دیگر نیازی به احراز ۶ عاملی نیست.
     * این متد فقط برای سازگاری عقب باقی می‌ماند و به form هدایت می‌کند.
     */
    public function verify(): void
    {
        // رفع M-5: افزودن CSRF حتی برای redirect
        if ($this->isPost()) {
            $this->verifyCsrf();
        }
        $this->redirect('/my-records');
    }
}
