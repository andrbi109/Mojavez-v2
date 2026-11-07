<?php
/**
 * قالب پنل مدیریت
 * -------------------------------------------------------------
 * بازنویسی سایدبار با الگوی Bootstrap 5.3 Sidebars Example
 * https://getbootstrap.com/docs/5.3/examples/sidebars/
 *
 * الگو:
 *   <div class="d-flex flex-column flex-shrink-0 p-3" style="width: 280px;">
 *     <a class="d-flex align-items-center mb-3 text-decoration-none">...</a>
 *     <hr>
 *     <ul class="nav nav-pills flex-column mb-auto">
 *       <li class="nav-item"><a class="nav-link active">...</a></li>
 *       <li><a class="nav-link">...</a></li>
 *     </ul>
 *     <hr>
 *     <div class="dropdown">...</div>
 *   </div>
 *
 * برای گروه‌های قابل جمع‌شدن از btn-toggle استفاده می‌شود:
 *   <button class="btn btn-toggle" data-bs-toggle="collapse">...</button>
 *   <div class="collapse">
 *     <ul class="btn-toggle-nav list-unstyled">...</ul>
 *   </div>
 */
$role = Session::get('admin_role', 'admin');
$admin = ($role === 'admin');
$displayName = Session::get('admin_display_name', 'مدیر');
$base = rtrim(BASE_URL, '/') . '/';
$cp = $currentPage ?? '';

$pendingPostalCount = 0;
try { $pendingPostalCount = (new PostalCodeChange())->countPending(); } catch (\Throwable $e) {}

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

<!-- نوار کناری — الگوی Bootstrap 5.3 Sidebar -->
<div class="offcanvas-lg offcanvas-end admin-sidebar-wrap" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
<nav class="admin-sidebar h-100 d-flex flex-column" aria-label="منوی اصلی پنل مدیریت">

    <!-- هدر: لوگو + دکمه بستن (موبایل) -->
    <div class="sidebar-header d-flex align-items-center justify-content-between px-3 py-3 flex-shrink-0">
        <a href="<?= $base ?>admin/dashboard" class="d-flex align-items-center gap-2 text-white text-decoration-none">
            <span class="brand-badge d-flex align-items-center justify-content-center">🏺</span>
            <span class="fs-5 fw-bold" id="adminSidebarLabel">پنل مدیریت</span>
        </a>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="بستن"></button>
    </div>

    <!-- اطلاعات کاربر -->
    <div class="px-3 pb-2 flex-shrink-0">
        <div class="sidebar-user d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:rgba(255,255,255,.1);font-size:.9rem;flex-shrink:0">
                <?= htmlspecialchars(mb_substr($displayName, 0, 1)) ?>
            </div>
            <div class="flex-grow-1 overflow-hidden">
                <strong class="d-block text-truncate" style="font-size:.85rem"><?= htmlspecialchars($displayName) ?></strong>
                <small class="d-block text-truncate"><?= htmlspecialchars($roleLabels[$role] ?? $role) ?></small>
            </div>
        </div>
    </div>

    <hr class="mx-3 my-0" style="border-color:rgba(255,255,255,.08)">

    <!-- ناوبری اصلی — الگوی Bootstrap: list-unstyled ps-0 -->
    <div class="sidebar-nav flex-grow-1 overflow-y-auto overflow-x-hidden px-2 py-2">

        <ul class="list-unstyled ps-0 mb-0">
            <!-- داشبورد -->
            <li class="mb-1">
                <a href="<?= $base ?>admin/dashboard" class="nav-link <?= $nav('dashboard') ?>">
                    <?= icon('dashboard', '', 16) ?> داشبورد
                </a>
            </li>

            <!-- ===== گروه: پرونده‌ها ===== -->
            <li class="mb-1">
                <button class="btn btn-toggle w-100 text-end d-inline-flex align-items-center rounded border-0"
                        data-bs-toggle="collapse" data-bs-target="#filesMenu"
                        aria-expanded="<?= in_array($cp, ['tasks','applications','postal-requests','license-requests','register'], true) ? 'true' : 'false' ?>">
                    <?= icon('folder', '', 16) ?>
                    <span class="me-auto">پرونده‌ها</span>
                </button>
                <div class="collapse <?= in_array($cp, ['tasks','applications','postal-requests','license-requests','register'], true) ? 'show' : '' ?>" id="filesMenu">
                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                        <li><a href="<?= $base ?>admin/my-tasks" class="d-inline-flex text-decoration-none rounded <?= $nav('tasks') ?>"><?= icon('task', '', 14) ?> وظایف من</a></li>
                        <?php if ($admin): ?>
                            <li><a href="<?= $base ?>admin/applications" class="d-inline-flex text-decoration-none rounded <?= $nav('applications') ?>"><?= icon('list', '', 14) ?> همهٔ درخواست‌ها</a></li>
                            <li><a href="<?= $base ?>admin/register" class="d-inline-flex text-decoration-none rounded <?= $nav('register') ?>"><?= icon('plus', '', 14) ?> ثبت‌نام دستی</a></li>
                        <?php endif; ?>
                        <li><a href="<?= $base ?>admin/postal-requests" class="d-inline-flex text-decoration-none rounded <?= $nav('postal-requests') ?>">
                            <?= icon('mail', '', 14) ?> تغییر کد پستی
                            <?php if ($pendingPostalCount > 0): ?><span class="badge bg-warning text-dark"><?= (int)$pendingPostalCount ?></span><?php endif; ?>
                        </a></li>
                        <?php if ($admin): ?>
                            <?php $pendingRevCount = ApplicantRevision::pendingBadge(); ?>
                            <li><a href="<?= $base ?>admin/revisions" class="d-inline-flex text-decoration-none rounded <?= $nav('revisions') ?>">
                                <?= icon('edit', '', 14) ?> بازبینی پرونده‌ها
                                <?php if ($pendingRevCount > 0): ?><span class="badge bg-warning text-dark"><?= (int)$pendingRevCount ?></span><?php endif; ?>
                            </a></li>
                            <?php $pendingSmsCount = 0; try { $pendingSmsCount = (new UserSmsEntry())->countPending(); } catch (\Throwable $e) {} ?>
                            <li><a href="<?= $base ?>admin/sms" class="d-inline-flex text-decoration-none rounded <?= $nav('sms') ?>">
                                <?= icon('phone', '', 14) ?> پیامک‌های وضعیت
                                <?php if ($pendingSmsCount > 0): ?><span class="badge bg-warning text-dark"><?= (int)$pendingSmsCount ?></span><?php endif; ?>
                            </a></li>
                            <?php $pendingLicense = 0; try { $pendingLicense = (new LicenseAccessRequest())->countPending(); } catch (\Throwable $e) {} ?>
                            <li><a href="<?= $base ?>admin/license-requests" class="d-inline-flex text-decoration-none rounded <?= $nav('license-requests') ?>">
                                <?= icon('award', '', 14) ?> درخواست‌های تصویر مجوز
                                <?php if ($pendingLicense > 0): ?><span class="badge bg-warning text-dark"><?= (int)$pendingLicense ?></span><?php endif; ?>
                            </a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>

            <?php if ($admin): ?>
            <!-- ===== گروه: گزارش‌ها ===== -->
            <li class="mb-1">
                <button class="btn btn-toggle w-100 text-end d-inline-flex align-items-center rounded border-0"
                        data-bs-toggle="collapse" data-bs-target="#reportsMenu"
                        aria-expanded="<?= $cp === 'reports' ? 'true' : 'false' ?>">
                    <?= icon('chart', '', 16) ?>
                    <span class="me-auto">گزارش‌ها</span>
                </button>
                <div class="collapse <?= $cp === 'reports' ? 'show' : '' ?>" id="reportsMenu">
                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                        <li><a href="<?= $base ?>admin/reports/by-province" class="d-inline-flex text-decoration-none rounded">بر اساس استان</a></li>
                        <li><a href="<?= $base ?>admin/reports/by-city" class="d-inline-flex text-decoration-none rounded">بر اساس شهر</a></li>
                        <li><a href="<?= $base ?>admin/reports/by-village" class="d-inline-flex text-decoration-none rounded">بر اساس روستا</a></li>
                        <li><a href="<?= $base ?>admin/reports/by-license" class="d-inline-flex text-decoration-none rounded">بر اساس رشته</a></li>
                        <li><a href="<?= $base ?>admin/reports/by-date" class="d-inline-flex text-decoration-none rounded">بر اساس زمان</a></li>
                        <li><a href="<?= $base ?>admin/output/grouped" class="d-inline-flex text-decoration-none rounded">گروه‌بندی‌شده</a></li>
                    </ul>
                </div>
            </li>

            <!-- ===== گروه: خروجی‌ها ===== -->
            <li class="mb-1">
                <button class="btn btn-toggle w-100 text-end d-inline-flex align-items-center rounded border-0"
                        data-bs-toggle="collapse" data-bs-target="#outputMenu"
                        aria-expanded="<?= $cp === 'outputs' ? 'true' : 'false' ?>">
                    <?= icon('download', '', 16) ?>
                    <span class="me-auto">دریافت خروجی</span>
                </button>
                <div class="collapse <?= $cp === 'outputs' ? 'show' : '' ?>" id="outputMenu">
                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                        <li><a href="<?= $base ?>admin/output/general" class="d-inline-flex text-decoration-none rounded">گزارش کلی (CSV/چاپ)</a></li>
                        <li><a href="#" onclick="promptTrackingCode(); return false;" class="d-inline-flex text-decoration-none rounded">پروندهٔ متقاضی خاص</a></li>
                    </ul>
                </div>
            </li>

            <!-- ===== گروه: مالی ===== -->
            <li class="mb-1">
                <button class="btn btn-toggle w-100 text-end d-inline-flex align-items-center rounded border-0"
                        data-bs-toggle="collapse" data-bs-target="#financeMenu"
                        aria-expanded="<?= $cp === 'payments' ? 'true' : 'false' ?>">
                    <?= icon('money', '', 16) ?>
                    <span class="me-auto">مالی</span>
                    <?php $pendingPayments = 0; try { $pendingPayments = (int)(new Payment())->stats()['pendingCount']; } catch (\Throwable $e) {} ?>
                    <?php if ($pendingPayments > 0): ?><span class="badge bg-danger ms-1"><?= $pendingPayments ?></span><?php endif; ?>
                </button>
                <div class="collapse <?= $cp === 'payments' ? 'show' : '' ?>" id="financeMenu">
                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                        <li><a href="<?= $base ?>admin/payments" class="d-inline-flex text-decoration-none rounded <?= $nav('payments') ?>"><?= icon('chart', '', 14) ?> درآمد و پرداخت‌ها</a></li>
                    </ul>
                </div>
            </li>

            <!-- ===== گروه: فرم‌ها و صفحات ===== -->
            <li class="mb-1">
                <button class="btn btn-toggle w-100 text-end d-inline-flex align-items-center rounded border-0"
                        data-bs-toggle="collapse" data-bs-target="#contentMenu"
                        aria-expanded="<?= in_array($cp, ['form-fields','wizard-steps','license-titles','service-types','fee-tariff','settings'], true) ? 'true' : 'false' ?>">
                    <?= icon('form', '', 16) ?>
                    <span class="me-auto">فرم‌ها و صفحات</span>
                </button>
                <div class="collapse <?= in_array($cp, ['form-fields','wizard-steps','license-titles','service-types','fee-tariff','settings'], true) ? 'show' : '' ?>" id="contentMenu">
                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                        <li><a href="<?= $base ?>admin/form-fields" class="d-inline-flex text-decoration-none rounded <?= $nav('form-fields') ?>"><?= icon('grid', '', 14) ?> فیلدهای فرم</a></li>
                        <li><a href="<?= $base ?>admin/wizard-steps" class="d-inline-flex text-decoration-none rounded <?= $nav('wizard-steps') ?>"><?= icon('task', '', 14) ?> مراحل ثبت‌نام</a></li>
                        <li><a href="<?= $base ?>admin/license-titles" class="d-inline-flex text-decoration-none rounded <?= $nav('license-titles') ?>"><?= icon('vase', '', 14) ?> رشته‌ها</a></li>
                        <li><a href="<?= $base ?>admin/service-types" class="d-inline-flex text-decoration-none rounded <?= $nav('service-types') ?>"><?= icon('grid', '', 14) ?> انواع خدمات</a></li>
                        <li><a href="<?= $base ?>admin/fee-tariff" class="d-inline-flex text-decoration-none rounded <?= $nav('fee-tariff') ?>"><?= icon('wallet', '', 14) ?> نرخ‌نامه</a></li>
                        <li><a href="<?= $base ?>admin/settings" class="d-inline-flex text-decoration-none rounded <?= $nav('settings') ?>"><?= icon('settings', '', 14) ?> تنظیمات</a></li>
                    </ul>
                </div>
            </li>

            <!-- ===== گروه: کاربران ===== -->
            <li class="mb-1">
                <button class="btn btn-toggle w-100 text-end d-inline-flex align-items-center rounded border-0"
                        data-bs-toggle="collapse" data-bs-target="#userMenu"
                        aria-expanded="<?= $cp === 'users' ? 'true' : 'false' ?>">
                    <?= icon('users', '', 16) ?>
                    <span class="me-auto">کاربران</span>
                </button>
                <div class="collapse <?= $cp === 'users' ? 'show' : '' ?>" id="userMenu">
                    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                        <li><a href="<?= $base ?>admin/users" class="d-inline-flex text-decoration-none rounded">لیست کاربران</a></li>
                        <li><a href="<?= $base ?>admin/users/create" class="d-inline-flex text-decoration-none rounded">کاربر جدید</a></li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- جداساز -->
            <li class="border-top my-2 mx-1"></li>

            <!-- لینک‌های پایانی -->
            <li class="mb-1">
                <a href="<?= $base ?>" target="_blank" rel="noopener" class="nav-link">
                    <?= icon('home', '', 16) ?> صفحهٔ اصلی
                </a>
            </li>
            <li class="mb-1">
                <a href="<?= $base ?>admin/logout" class="nav-link text-danger"
                   onclick="return confirm('از پنل مدیریت خارج می‌شوید؟')">
                    <?= icon('logout', '', 16) ?> خروج
                </a>
            </li>
        </ul>
    </div>

    <hr class="mx-3 my-0" style="border-color:rgba(255,255,255,.08)">

    <!-- فوتر -->
    <div class="px-3 py-2 d-flex align-items-center justify-content-between flex-shrink-0">
        <small style="color:#5A6B7D; font-size:.7rem;">
            <?= htmlspecialchars(owner_name()) ?>
            <span style="opacity:.6">v<?= defined('APP_VERSION') ? APP_VERSION : '0.3.7' ?></span>
        </small>
        <button type="button" class="theme-toggle d-none d-lg-inline-flex" data-theme-toggle onclick="mojavezToggleTheme()" aria-label="تغییر تم روز/شب" style="width:28px;height:28px;">
            <span class="icon-sun"><?= icon('sun', '', 13) ?></span><span class="icon-moon"><?= icon('moon', '', 13) ?></span>
        </button>
    </div>

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
// بستن منوی موبایل پس از کلیک روی هر لینک
document.querySelectorAll('#adminSidebar a[href]').forEach(function (link) {
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
