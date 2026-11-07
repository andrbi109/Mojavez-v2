<?php
/**
 * کنترلر خدمات (ServiceController)
 * ---------------------------------------------------
 * مدل فروشگاهی: کاربران سرویس‌ها را به‌صورت «محصول» می‌بینند و می‌خرند.
 *
 * مسیرها:
 *   GET /services — فهرست سرویس‌ها (کارت‌های محصول)
 *   POST /services/{id}/purchase — شروع یک درخواست جدید (خرید)
 */
class ServiceController extends Controller
{
    /**
     * GET /services — فهرست سرویس‌ها به‌صورت کارت‌های محصول
     */
    public function index(): void
    {
        $serviceModel = new ServiceType();
        $services = $serviceModel->getActive();

        // اگر کاربر وارد شده، اطلاعات او را هم بفرست
        $userLoggedIn = Session::get('user_logged_in', false);
        $userName = Session::get('user_name', '');

        $this->render('services/list', [
            'title' => 'خدمات و مجوزها',
            'services' => $services,
            'userLoggedIn' => $userLoggedIn,
            'userName' => $userName,
        ]);
    }

    /**
     * POST /services/{id}/purchase — شروع یک درخواست جدید
     * کاربر یک سرویس را «خریداری» می‌کند → به ویزارد ثبت‌نام هدایت می‌شود
     */
    public function purchase(int $id): void
    {
        if (!$this->isPost()) $this->redirect('/services');
        $this->verifyCsrf();

        // اگر کاربر وارد نشده → به صفحهٔ ورود هدایت
        if (!Session::get('user_logged_in')) {
            Session::set('redirect_after_login', '/services/' . $id . '/purchase');
            Session::set('login_error', 'برای شروع درخواست، ابتدا وارد شوید یا ثبت‌نام کنید.');
            $this->redirect('/login');
        }

        $serviceModel = new ServiceType();
        $service = $serviceModel->findById($id);
        if (!$service || (int)$service['is_active'] !== 1) {
            Session::set('error', 'سرویس موردنظر یافت نشد یا غیرفعال است.');
            $this->redirect('/services');
        }

        // تنظیم service_id در نشست و هدایت به ویزارد ثبت‌نام
        Session::setStep('service_id', (int)$service['id']);

        // اگر کاربر حساب دارد، می‌توانیم user_account_id را در نشست هم ذخیره کنیم
        // تا پس از تکمیل ثبت‌نام، پرونده به حساب او متصل شود
        Session::set('register_user_account_id', (int)Session::get('user_id', 0));

        $this->redirect('/register');
    }
}
