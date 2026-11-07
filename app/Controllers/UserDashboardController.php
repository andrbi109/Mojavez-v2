<?php
/**
 * کنترلر داشبورد کاربر (UserDashboardController)
 * ---------------------------------------------------
 * مدل فروشگاهی: داشبورد شخصی کاربر برای مشاهدهٔ درخواست‌ها و پرداخت‌ها.
 *
 * مسیرها:
 *   GET /dashboard — داشبورد کاربر
 */
class UserDashboardController extends Controller
{
    private UserAccount $userModel;

    public function __construct()
    {
        $this->userModel = new UserAccount();
    }

    public function index(): void
    {
        $this->requireUserLogin();

        $userId = (int)Session::get('user_id', 0);
        $user = $this->userModel->findById($userId);
        if (!$user) {
            Session::destroy();
            $this->redirect('/login');
        }

        $applications = $this->userModel->getApplications($userId);
        $payments = $this->userModel->getPayments($userId);

        // آمار خلاصه
        $totalApps = count($applications);
        $pendingApps = count(array_filter($applications, fn($a) => !in_array($a['current_status'], ['صدور مجوز', 'تحویل مجوز', 'لغو'], true)));
        $issuedApps = count(array_filter($applications, fn($a) => in_array($a['current_status'], ['صدور مجوز', 'تحویل مجوز'], true)));
        $cancelledApps = count(array_filter($applications, fn($a) => $a['current_status'] === 'لغو'));

        // آمار پرداخت
        $totalPaid = 0;
        $pendingPayments = 0;
        foreach ($payments as $p) {
            if ($p['status'] === 'تأیید شده') {
                $totalPaid += (int)$p['amount'];
            } elseif ($p['status'] === 'در انتظار بررسی' || $p['status'] === 'در انتظار رسید') {
                $pendingPayments++;
            }
        }

        $this->render('user/dashboard', [
            'title' => 'داشبورد من',
            'user' => $user,
            'applications' => $applications,
            'payments' => $payments,
            'stats' => [
                'total_apps' => $totalApps,
                'pending_apps' => $pendingApps,
                'issued_apps' => $issuedApps,
                'cancelled_apps' => $cancelledApps,
                'total_paid' => $totalPaid,
                'pending_payments' => $pendingPayments,
            ],
        ]);
    }

    private function requireUserLogin(): void
    {
        if (!Session::get('user_logged_in')) {
            $this->redirect('/login');
        }
    }
}
