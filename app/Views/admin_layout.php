<?php
/**
 * قالب پنل مدیریت
 * -------------------------------------------------------------
 * - منوی معنایی HTML5 (nav/ul/li) با آیکون‌های SVG درون‌خطی
 * - رفع باگ ناوبری: data-bs-dismiss از لینک‌ها حذف شده و بستن
 *   منوی موبایل با جاوااسکریپت انجام می‌شود (بدون preventDefault)
 * - کلید تم روز/شب + اسکریپت زودهنگام ضد پرش رنگ
 */
$role = Session::get('admin_role', 'admin');
$admin = ($role === 'admin');
$displayName = Session::get('admin_display_name', 'مدیر');
$base = rtrim(BASE_URL, '/') . '/';
$cp = $currentPage ?? '';

$pendingPostalCount = 0;
try {
    $pendingPostalCount = (new PostalCodeChange())->countPending();
} catch (\Throwable $e) { /* مهاجرت انجام نشده */ }

$roleLabels = ['admin' => 'مدیر کل', 'expert' => 'کارشناس', 'document_uploader' => 'متصدی مدارک'];
$nav = fn(string $page) => $cp === $page ? 'active' : '';
$ownerImg = setting('owner_image', '');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Session::csrfToken() ?>">
    <title><?= htmlspecialchars($title ?? 'پنل مدیریت') ?></title>
    <!-- تم زودهنگام (ضد پرش رنگ) -->
    <script src="<?= $base ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= $base ?>assets/font.css">
    <?php if (USE_CDN): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="<?= $base ?>assets/bootstrap.rtl.min.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body class="admin-body">

<!-- نوار بالای موبایل -->
<header class="admin-topbar d-lg-none">
    <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-label="باز کردن منو">
        <?= icon('menu', '', 18) ?> منو
    </button>
    <span class="fw-bold text-white small text-truncate"><?= htmlspecialchars($title ?? 'پنل مدیریت') ?></span>
    <button type="button" class="theme-toggle ms-auto" data-theme-toggle onclick="mojavezToggleTheme()" aria-label="تغییر تم روز/شب">
        <span class="icon-sun"><?= icon('sun', '', 17) ?></span><span class="icon-moon"><?= icon('moon', '', 17) ?></span>
    </button>
</header>

<!-- نوار کناری -->
<div class="offcanvas-lg offcanvas-end admin-sidebar-wrap" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
    <nav class="admin-sidebar h-100 d-flex flex-column" aria-label="منوی اصلی پنل مدیریت">
        <header class="sidebar-header d-flex justify-content-between align-items-center">
            <span id="adminSidebarLabel" class="d-flex align-items-center gap-2">
                <span class="brand-badge">🏺</span> پنل مدیریت
            </span>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="بستن"></button>
        </header>

        <div class="px-3 mb-2">
            <div class="sidebar-user">
                <strong><?= htmlspecialchars($displayName) ?></strong>
                <small><?= htmlspecialchars($roleLabels[$role] ?? $role) ?></small>
            </div>
        </div>

        <ul class="nav flex-column list-unstyled pb-4 mb-0 flex-grow-1 overflow-auto">
            <li class="nav-item">
                <a class="nav-link <?= $nav('dashboard') ?>" href="<?= $base ?>admin/dashboard">
                    <?= icon('dashboard') ?> داشبورد
                </a>
            </li>

            <!-- دستهٔ پرونده‌ها -->
            <li class="nav-item">
                <button class="nav-link sidebar-group w-100 border-0 bg-transparent text-start d-flex align-items-center gap-2"
                        type="button" data-bs-toggle="collapse" data-bs-target="#filesMenu" aria-expanded="<?= in_array($cp, ['tasks', 'applications', 'postal-requests', 'license-requests', 'register'], true) ? 'true' : 'false' ?>" aria-controls="filesMenu">
                    <?= icon('folder') ?> پرونده‌ها <?= icon('chevron-down', 'ms-auto', 15) ?>
                </button>
                <div class="collapse <?= in_array($cp, ['tasks', 'applications', 'postal-requests', 'license-requests', 'register'], true) ? 'show' : '' ?>" id="filesMenu">
                    <ul class="nav flex-column list-unstyled">
                        <li class="nav-item"><a class="nav-link me-3 <?= $nav('tasks') ?>" href="<?= $base ?>admin/my-tasks"><?= icon('task', '', 16) ?> وظایف من</a></li>
                        <?php if ($admin): ?>
                            <li class="nav-item"><a class="nav-link me-3 <?= $nav('applications') ?>" href="<?= $base ?>admin/applications"><?= icon('list', '', 16) ?> همهٔ درخواست‌ها</a></li>
                            <li class="nav-item"><a class="nav-link me-3 <?= $nav('register') ?>" href="<?= $base ?>admin/register"><?= icon('plus', '', 16) ?> ثبت‌نام دستی متقاضی</a></li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link me-3 <?= $nav('postal-requests') ?>" href="<?= $base ?>admin/postal-requests">
                                <?= icon('mail', '', 16) ?> تغییر کد پستی
                                <?php if ($pendingPostalCount > 0): ?>
                                    <span class="badge bg-warning text-dark ms-auto"><?= (int)$pendingPostalCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php if ($admin): ?>
                        <li class="nav-item">
                            <a class="nav-link me-3 <?= $nav('revisions') ?>" href="<?= $base ?>admin/revisions">
                                <?= icon('edit', '', 16) ?> بازبینی پرونده‌ها
                                <?php $pendingRevCount = ApplicantRevision::pendingBadge(); if ($pendingRevCount > 0): ?>
                                    <span class="badge bg-warning text-dark ms-auto"><?= (int)$pendingRevCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link me-3 <?= $nav('sms') ?>" href="<?= $base ?>admin/sms">
                                <?= icon('phone', '', 16) ?> پیامک‌های وضعیت
                                <?php
                                $pendingSmsCount = 0;
                                try { $pendingSmsCount = (new UserSmsEntry())->countPending(); } catch (\Throwable $e) {}
                                if ($pendingSmsCount > 0): ?>
                                    <span class="badge bg-warning text-dark ms-auto"><?= (int)$pendingSmsCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link me-3 <?= $nav('license-requests') ?>" href="<?= $base ?>admin/license-requests">
                                <?= icon('award', '', 16) ?> درخواست‌های تصویر مجوز
                                <?php
                                $pendingLicense = 0;
                                try { $pendingLicense = (new LicenseAccessRequest())->countPending(); } catch (\Throwable $e) {}
                                if ($pendingLicense > 0): ?>
                                    <span class="badge bg-warning text-dark ms-auto"><?= (int)$pendingLicense ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>

            <?php if ($admin): ?>
                <!-- دستهٔ گزارش‌ها -->
                <li class="nav-item">
                    <button class="nav-link sidebar-group w-100 border-0 bg-transparent text-start d-flex align-items-center gap-2"
                            type="button" data-bs-toggle="collapse" data-bs-target="#reportsMenu" aria-expanded="<?= $cp === 'reports' ? 'true' : 'false' ?>" aria-controls="reportsMenu">
                        <?= icon('chart') ?> گزارش‌ها <?= icon('chevron-down', 'ms-auto', 15) ?>
                    </button>
                    <div class="collapse <?= $cp === 'reports' ? 'show' : '' ?>" id="reportsMenu">
                        <ul class="nav flex-column list-unstyled">
                            <li class="nav-item"><a class="nav-link me-4" href="<?= $base ?>admin/reports/by-province">• بر اساس استان</a></li>
                            <li class="nav-item"><a class="nav-link me-4" href="<?= $base ?>admin/reports/by-city">• بر اساس شهر</a></li>
                            <li class="nav-item"><a class="nav-link me-4" href="<?= $base ?>admin/reports/by-village">• بر اساس روستا</a></li>
                            <li class="nav-item"><a class="nav-link me-4" href="<?= $base ?>admin/reports/by-license">• بر اساس رشته</a></li>
                            <li class="nav-item"><a class="nav-link me-4" href="<?= $base ?>admin/reports/by-date">• بر اساس زمان ثبت‌نام</a></li>
                            <li class="nav-item"><a class="nav-link me-4" href="<?= $base ?>admin/output/grouped">• گروه‌بندی‌شده</a></li>
                        </ul>
                    </div>
                </li>

                <!-- دستهٔ خروجی‌ها -->
                <li class="nav-item">
                    <button class="nav-link sidebar-group w-100 border-0 bg-transparent text-start d-flex align-items-center gap-2"
                            type="button" data-bs-toggle="collapse" data-bs-target="#outputMenu" aria-expanded="<?= $cp === 'outputs' ? 'true' : 'false' ?>" aria-controls="outputMenu">
                        <?= icon('download') ?> دریافت خروجی <?= icon('chevron-down', 'ms-auto', 15) ?>
                    </button>
                    <div class="collapse <?= $cp === 'outputs' ? 'show' : '' ?>" id="outputMenu">
                        <ul class="nav flex-column list-unstyled">
                            <li class="nav-item"><a class="nav-link me-4" href="<?= $base ?>admin/output/general">• گزارش کلی (CSV/چاپ)</a></li>
                            <li class="nav-item"><a class="nav-link me-4" href="#" onclick="promptTrackingCode(); return false;">• پروندهٔ متقاضی خاص</a></li>
                        </ul>
                    </div>
                </li>

                <!-- دستهٔ مالی (فاز ۴ نقشهٔ راه) -->
                <li class="nav-item">
                    <button class="nav-link sidebar-group w-100 border-0 bg-transparent text-start d-flex align-items-center gap-2"
                            type="button" data-bs-toggle="collapse" data-bs-target="#financeMenu" aria-expanded="<?= in_array($cp, ['payments'], true) ? 'true' : 'false' ?>" aria-controls="financeMenu">
                        <?= icon('money') ?> مالی <?= icon('chevron-down', 'ms-auto', 15) ?>
                        <?php
                        $pendingPayments = 0;
                        try { $pendingPayments = (int)(new Payment())->stats()['pendingCount']; } catch (\Throwable $e) {}
                        if ($pendingPayments > 0): ?>
                            <span class="badge bg-danger ms-1"><?= $pendingPayments ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="collapse <?= in_array($cp, ['payments'], true) ? 'show' : '' ?>" id="financeMenu">
                        <ul class="nav flex-column list-unstyled">
                            <li class="nav-item"><a class="nav-link me-4 <?= $nav('payments') ?>" href="<?= $base ?>admin/payments"><?= icon('chart', '', 16) ?> درآمد و پرداخت‌ها</a></li>
                        </ul>
                    </div>
                </li>

                <!-- دستهٔ فرم‌ها، فهرست‌ها و صفحات -->
                <li class="nav-item">
                    <button class="nav-link sidebar-group w-100 border-0 bg-transparent text-start d-flex align-items-center gap-2"
                            type="button" data-bs-toggle="collapse" data-bs-target="#contentMenu" aria-expanded="<?= in_array($cp, ['form-fields', 'wizard-steps', 'license-titles', 'service-types', 'fee-tariff', 'settings'], true) ? 'true' : 'false' ?>" aria-controls="contentMenu">
                        <?= icon('form') ?> فرم‌ها و صفحات <?= icon('chevron-down', 'ms-auto', 15) ?>
                    </button>
                    <div class="collapse <?= in_array($cp, ['form-fields', 'wizard-steps', 'license-titles', 'service-types', 'fee-tariff', 'settings'], true) ? 'show' : '' ?>" id="contentMenu">
                        <ul class="nav flex-column list-unstyled">
                            <li class="nav-item"><a class="nav-link me-4 <?= $nav('form-fields') ?>" href="<?= $base ?>admin/form-fields"><?= icon('grid', '', 16) ?> مدیریت فیلدهای فرم</a></li>
                            <li class="nav-item"><a class="nav-link me-4 <?= $nav('wizard-steps') ?>" href="<?= $base ?>admin/wizard-steps"><?= icon('task', '', 16) ?> مراحل ثبت‌نام</a></li>
                            <li class="nav-item"><a class="nav-link me-4 <?= $nav('license-titles') ?>" href="<?= $base ?>admin/license-titles"><?= icon('vase', '', 16) ?> مدیریت رشته‌ها</a></li>
                            <li class="nav-item"><a class="nav-link me-4 <?= $nav('service-types') ?>" href="<?= $base ?>admin/service-types"><?= icon('grid', '', 16) ?> انواع خدمات</a></li>
                            <li class="nav-item"><a class="nav-link me-4 <?= $nav('fee-tariff') ?>" href="<?= $base ?>admin/fee-tariff"><?= icon('wallet', '', 16) ?> نرخ‌نامه و تعرفه‌ها</a></li>
                            <li class="nav-item"><a class="nav-link me-4 <?= $nav('settings') ?>" href="<?= $base ?>admin/settings"><?= icon('settings', '', 16) ?> تنظیمات و دربارهٔ ما</a></li>
                        </ul>
                    </div>
                </li>

                <!-- دستهٔ کاربران -->
                <li class="nav-item">
                    <button class="nav-link sidebar-group w-100 border-0 bg-transparent text-start d-flex align-items-center gap-2"
                            type="button" data-bs-toggle="collapse" data-bs-target="#userMenu" aria-expanded="<?= $cp === 'users' ? 'true' : 'false' ?>" aria-controls="userMenu">
                        <?= icon('users') ?> کاربران <?= icon('chevron-down', 'ms-auto', 15) ?>
                    </button>
                    <div class="collapse <?= $cp === 'users' ? 'show' : '' ?>" id="userMenu">
                        <ul class="nav flex-column list-unstyled">
                            <li class="nav-item"><a class="nav-link me-4" href="<?= $base ?>admin/users">• لیست کاربران</a></li>
                            <li class="nav-item"><a class="nav-link me-4" href="<?= $base ?>admin/users/create">• کاربر جدید</a></li>
                        </ul>
                    </div>
                </li>
            <?php endif; ?>

            <li class="nav-item"><hr class="border-secondary my-2 mx-3"></li>
            <li class="nav-item">
                <a class="nav-link" href="<?= $base ?>" target="_blank" rel="noopener"><?= icon('home') ?> صفحهٔ اصلی سامانه</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="<?= $base ?>admin/logout" onclick="return confirm('از پنل مدیریت خارج می‌شوید؟')">
                    <?= icon('logout') ?> خروج
                </a>
            </li>
        </ul>

        <footer class="px-3 py-3 d-flex align-items-center justify-content-between gap-2" style="border-top:1px solid rgba(255,255,255,.07)">
            <small style="color:#5A6B7D; font-size:.7rem;">مدیریت: <?= htmlspecialchars(owner_name()) ?> · <span style="opacity:.7">v<?= defined('APP_VERSION') ? APP_VERSION : '0.2.4' ?></span></small>
            <button type="button" class="theme-toggle d-none d-lg-inline-flex" data-theme-toggle onclick="mojavezToggleTheme()" aria-label="تغییر تم روز/شب" style="width:32px;height:32px;">
                <span class="icon-sun"><?= icon('sun', '', 15) ?></span><span class="icon-moon"><?= icon('moon', '', 15) ?></span>
            </button>
        </footer>
    </nav>
</div>

<!-- محتوای اصلی -->
<main class="admin-main flex-grow-1">
    <div class="d-none d-lg-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0" style="color:var(--navy)"><?= htmlspecialchars($title ?? 'داشبورد') ?></h3>
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($flash_error = Session::get('flash_error'))): ?>
                <span class="alert alert-danger py-1 px-2 mb-0 small"><?= htmlspecialchars($flash_error) ?></span>
                <?php Session::remove('flash_error'); ?>
            <?php endif; ?>
            <button type="button" class="theme-toggle layout-light" data-theme-toggle onclick="mojavezToggleTheme()" aria-label="تغییر تم روز/شب">
                <span class="icon-sun"><?= icon('sun', '', 17) ?></span><span class="icon-moon"><?= icon('moon', '', 17) ?></span>
            </button>
            <span class="text-muted small"><?= htmlspecialchars($displayName) ?> (<?= htmlspecialchars($roleLabels[$role] ?? $role) ?>)</span>
            <a href="<?= $base ?>admin/logout" class="btn btn-outline-danger btn-sm"><?= icon('logout', '', 15) ?> خروج</a>
        </div>
    </div>

    <?= $content ?>
</main>

<script>
function promptTrackingCode() {
    const code = prompt('کد رهگیری متقاضی را وارد کنید:');
    if (code && code.trim() !== '') {
        window.location.href = '<?= $base ?>admin/output/single/' + encodeURIComponent(code.trim());
    }
}
// بستن منوی موبایل پس از کلیک روی هر لینک — بدون preventDefault (رفع باگ ناوبری)
document.querySelectorAll('#adminSidebar a.nav-link[href]').forEach(function (link) {
    link.addEventListener('click', function () {
        if (window.innerWidth < 992 && window.bootstrap) {
            const el = document.getElementById('adminSidebar');
            const inst = bootstrap.Offcanvas.getInstance(el);
            if (inst) inst.hide();
        }
    });
});
</script>
<?php if (USE_CDN): ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php else: ?>
    <script src="<?= $base ?>assets/bootstrap.bundle.min.js"></script>
<?php endif; ?>
<script src="<?= $base ?>assets/js/theme.js"></script>
</body>
</html>
