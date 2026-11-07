<?php
/**
 * کنترلر پنل کاربری (UserDashboardController)
 * ---------------------------------------------------
 * پنل شخصی کاربر: پروفایل، درخواست‌ها، سوابق، پرداخت‌ها، مجوزها.
 *
 * مسیرها:
 *   GET /dashboard — پنل کاربری
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

        // دریافت تمام درخواست‌ها با جزئیات کامل
        $applications = $this->userModel->getApplications($userId);

        // دریافت پرداخت‌ها
        $payments = $this->userModel->getPayments($userId);

        // دریافت مجوزهای صادرشده (پرونده‌هایی که وضعیت صدور/تحویل دارند)
        $licenses = array_filter($applications, function($a) {
            return in_array($a['current_status'] ?? '', ['صدور مجوز', 'تحویل مجوز'], true);
        });

        // دریافت تایم‌لاین آخرین درخواست (برای نمایش روند)
        $latestApp = !empty($applications) ? $applications[0] : null;
        $latestTimeline = [];
        if ($latestApp) {
            try {
                $statusLogModel = new StatusLog();
                $latestTimeline = $statusLogModel->findByApplicantId((int)$latestApp['id']);
            } catch (\Throwable $e) {}
        }

        // محاسبهٔ آمار
        $totalApps = count($applications);
        $pendingApps = count(array_filter($applications, fn($a) =>
            !in_array($a['current_status'] ?? '', ['صدور مجوز', 'تحویل مجوز', 'لغو'], true)
        ));
        $issuedApps = count($licenses);
        $cancelledApps = count(array_filter($applications, fn($a) =>
            ($a['current_status'] ?? '') === 'لغو'
        ));

        $totalPaid = 0;
        $pendingPayments = 0;
        foreach ($payments as $p) {
            if (($p['status'] ?? '') === 'تأیید شده') {
                $totalPaid += (int)($p['amount'] ?? 0);
            } elseif (in_array($p['status'] ?? '', ['در انتظار بررسی', 'در انتظار رسید'], true)) {
                $pendingPayments++;
            }
        }

        $statusMeta = (require CONFIG_PATH . '/statuses.php')['statuses'];

        $this->render('user/panel', [
            'title' => 'پنل کاربری',
            'user' => $user,
            'applications' => $applications,
            'payments' => $payments,
            'licenses' => array_values($licenses),
            'latestApp' => $latestApp,
            'latestTimeline' => $latestTimeline,
            'statusMeta' => $statusMeta,
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
            Session::set('redirect_after_login', '/panel');
            Session::set('login_error', 'برای دسترسی به پنل کاربری، ابتدا وارد شوید.');
            $this->redirect('/login');
        }
    }
}
