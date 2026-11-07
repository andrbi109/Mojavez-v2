<?php
/**
 * قالب پنل کاربری — سایدبار راست با منوی کاربر + محتوا
 */
$base = rtrim(BASE_URL, '/') . '/';
$u = $user ?? [];
$active = $activeMenu ?? '';
$unread = $unreadCount ?? 0;
$sm = $statusMeta ?? [];
$navItem = fn(string $key) => $active === $key ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Session::csrfToken() ?>">
    <title><?= htmlspecialchars($title ?? 'پنل کاربری') ?></title>
    <script src="<?= $base ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= $base ?>assets/font.css">
    <?php if (USE_CDN): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="<?= $base ?>assets/bootstrap.rtl.min.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
<style>
.user-sidebar {
    background: linear-gradient(180deg, var(--navy) 0%, #101E2E 100%);
    width: 260px; max-width: 100%; min-height: 100vh;
    color: #B9C4CF; position: fixed; right: 0; top: 0; bottom: 0;
    z-index: 1000; overflow-y: auto; overflow-x: hidden;
    border-left: 1px solid rgba(217,164,65,.18);
    display: flex; flex-direction: column;
    scrollbar-width: thin; scrollbar-color: rgba(217,164,65,.3) transparent;
}
.user-sidebar::-webkit-scrollbar { width: 5px; }
.user-sidebar::-webkit-scrollbar-thumb { background: rgba(217,164,65,.25); border-radius: 3px; }

.us-header { padding: 1.2rem 1rem .8rem; border-bottom: 1px solid rgba(255,255,255,.08); flex-shrink: 0; }
.us-header .brand-badge {
    width: 34px; height: 34px; font-size: 1rem;
    background: linear-gradient(135deg, var(--turquoise), var(--turquoise-dark));
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 8px rgba(0,180,216,.3);
}
.us-nav { flex-grow: 1; overflow-y: auto; overflow-x: hidden; padding: .5rem .75rem 1rem; }
.us-nav::-webkit-scrollbar { width: 4px; }
.us-nav::-webkit-scrollbar-thumb { background: rgba(217,164,65,.15); border-radius: 2px; }

.us-link {
    color: #B9C4CF; border-radius: 8px; padding: .55rem .75rem;
    display: flex; align-items: center; gap: .55rem;
    transition: all .15s ease; font-size: .87rem; text-decoration: none;
    margin-bottom: 2px;
}
.us-link:hover { color: #fff; background-color: rgba(255,255,255,.06); text-decoration: none; }
.us-link.active { color: #fff; background: rgba(0,180,216,.18); font-weight: 600; }
.us-link .badge { margin-right: auto; font-size: .65rem; }

.us-footer { padding: .8rem 1rem; border-top: 1px solid rgba(255,255,255,.07); flex-shrink: 0; background: rgba(0,0,0,.15); }

.user-main { margin-right: 260px; padding: 1.5rem; min-width: 0; }

@media (min-width: 1400px) { .user-sidebar { width: 280px; } .user-main { margin-right: 280px; } }
@media (min-width: 992px) and (max-width: 1399.98px) { .user-sidebar { width: 240px; } .user-main { margin-right: 240px; } }
@media (max-width: 991.98px) {
    .user-sidebar { width: 100% !important; max-width: 300px; }
    .user-main { margin-right: 0 !important; padding: 1rem; width: 100%; }
}
@media (max-width: 575.98px) { .user-main { padding: .75rem; } .user-sidebar { max-width: 260px; } }

/* تایم‌لاین بدون هم‌پوشانی */
.timeline { list-style: none; padding: 0; margin: 0; position: relative; }
.timeline::before {
    content: ''; position: absolute; right: 11px; top: 0; bottom: 0;
    width: 2px; background: var(--border-color, rgba(0,0,0,.08));
}
.timeline-item {
    position: relative; padding: .6rem 2.5rem .6rem 0;
    border-bottom: 1px solid var(--border-color, rgba(0,0,0,.04));
}
.timeline-item:last-child { border-bottom: none; }
.timeline-item::before {
    content: ''; position: absolute; right: 6px; top: .85rem;
    width: 12px; height: 12px; border-radius: 50%;
    background: var(--turquoise);
    border: 2px solid var(--card-bg, #fff);
    box-shadow: 0 0 0 2px var(--turquoise);
}
.timeline-item .tl-status { font-weight: 600; font-size: .88rem; color: var(--ink, #1A2A4F); display: block; }
.timeline-item .tl-desc { font-size: .8rem; color: var(--muted, #6B7280); display: block; margin-top: .15rem; }
.timeline-item .tl-date { font-size: .75rem; color: var(--muted, #6B7280); display: block; margin-top: .15rem; }

[data-bs-theme="dark"] .timeline-item .tl-status { color: #E2E8F0; }
[data-bs-theme="dark"] .timeline::before { background: rgba(255,255,255,.08); }
[data-bs-theme="dark"] .timeline-item::before { box-shadow: 0 0 0 2px #1E293B; }

@media print { .user-sidebar { display: none !important; } .user-main { margin: 0 !important; padding: 0 !important; } }
</style>
</head>
<body class="admin-body">

<!-- نوار بالای موبایل -->
<header class="admin-topbar d-lg-none">
    <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#userSidebar" aria-label="منو">
        <?= icon('menu', '', 18) ?> منو
    </button>
    <span class="fw-bold text-white small text-truncate"><?= htmlspecialchars($title ?? 'پنل کاربری') ?></span>
    <button type="button" class="theme-toggle ms-auto" data-theme-toggle onclick="mojavezToggleTheme()" aria-label="تم">
        <span class="icon-sun"><?= icon('sun', '', 17) ?></span><span class="icon-moon"><?= icon('moon', '', 17) ?></span>
    </button>
</header>

<!-- سایدبار کاربر -->
<div class="offcanvas-lg offcanvas-end" tabindex="-1" id="userSidebar">
<nav class="user-sidebar h-100 d-flex flex-column" aria-label="منوی پنل کاربری">

    <div class="us-header d-flex align-items-center justify-content-between">
        <a href="<?= $base ?>panel" class="d-flex align-items-center gap-2 text-white text-decoration-none">
            <span class="brand-badge">🏛️</span>
            <span class="fw-bold">پنل کاربری</span>
        </a>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#userSidebar"></button>
    </div>

    <!-- اطلاعات کاربر -->
    <div class="px-3 pb-2 flex-shrink-0">
        <div class="d-flex align-items-center gap-2" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:.5rem .75rem;">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                 style="width:36px;height:36px;background:rgba(0,180,216,.3);font-size:.85rem;flex-shrink:0">
                <?= htmlspecialchars(mb_substr($u['first_name'] ?? '؟', 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                <strong class="d-block text-truncate text-white" style="font-size:.82rem"><?= htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?></strong>
                <small class="d-block text-truncate" style="color:var(--saffron)"><?= htmlspecialchars($u['mobile'] ?? '') ?></small>
            </div>
        </div>
    </div>

    <hr class="mx-3 my-0" style="border-color:rgba(255,255,255,.08)">

    <!-- منوی اصلی -->
    <div class="us-nav">
        <ul class="list-unstyled mb-0">
            <li class="mb-1"><a href="<?= $base ?>panel" class="us-link <?= $navItem('overview') ?>"><?= icon('dashboard', '', 16) ?> نمای کلی</a></li>
            <li class="mb-1"><a href="<?= $base ?>panel/profile" class="us-link <?= $navItem('profile') ?>"><?= icon('user', '', 16) ?> پروفایل من</a></li>
            <li class="mb-1"><a href="<?= $base ?>panel/requests" class="us-link <?= $navItem('requests') ?>"><?= icon('folder', '', 16) ?> درخواست‌های من</a></li>
            <li class="mb-1"><a href="<?= $base ?>panel/payments" class="us-link <?= $navItem('payments') ?>"><?= icon('money', '', 16) ?> پرداخت‌های من</a></li>
            <li class="mb-1"><a href="<?= $base ?>panel/licenses" class="us-link <?= $navItem('licenses') ?>"><?= icon('award', '', 16) ?> مجوزهای من</a></li>
            <li class="mb-1">
                <a href="<?= $base ?>panel/notifications" class="us-link <?= $navItem('notifications') ?>">
                    <?= icon('bell', '', 16) ?> اعلان‌ها
                    <?php if ($unread > 0): ?><span class="badge bg-danger"><?= $unread ?></span><?php endif; ?>
                </a>
            </li>
            <li class="mb-1"><a href="<?= $base ?>panel/tracking" class="us-link <?= $navItem('tracking') ?>"><?= icon('search', '', 16) ?> پیگیری</a></li>
            <li class="border-top my-2 mx-1"></li>
            <li class="mb-1"><a href="<?= $base ?>services" class="us-link"><?= icon('plus', '', 16) ?> درخواست جدید</a></li>
            <li class="mb-1"><a href="<?= $base ?>" class="us-link"><?= icon('home', '', 16) ?> صفحهٔ اصلی</a></li>
            <li class="mb-1"><a href="<?= $base ?>logout" class="us-link text-danger"><?= icon('logout', '', 16) ?> خروج</a></li>
        </ul>
    </div>

    <div class="us-footer d-flex align-items-center justify-content-between">
        <small style="color:#5A6B7D;font-size:.7rem;">
            <?= htmlspecialchars(owner_name()) ?>
            <span style="opacity:.6">v<?= defined('APP_VERSION') ? APP_VERSION : '0.4.0' ?></span>
        </small>
        <button type="button" class="theme-toggle d-none d-lg-inline-flex" data-theme-toggle onclick="mojavezToggleTheme()" style="width:28px;height:28px;">
            <span class="icon-sun"><?= icon('sun', '', 13) ?></span><span class="icon-moon"><?= icon('moon', '', 13) ?></span>
        </button>
    </div>
</nav>
</div>

<!-- محتوای اصلی -->
<main class="user-main">
    <div class="d-none d-lg-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0" style="color:var(--navy)"><?= htmlspecialchars($title ?? 'پنل کاربری') ?></h4>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="theme-toggle layout-light" data-theme-toggle onclick="mojavezToggleTheme()" aria-label="تم">
                <span class="icon-sun"><?= icon('sun', '', 16) ?></span><span class="icon-moon"><?= icon('moon', '', 16) ?></span>
            </button>
            <a href="<?= $base ?>services" class="btn btn-primary btn-sm"><?= icon('plus', '', 14) ?> درخواست جدید</a>
        </div>
    </div>
    <?= $content ?>
</main>

<script>
document.querySelectorAll('#userSidebar a[href]').forEach(function (link) {
    link.addEventListener('click', function () {
        if (window.innerWidth < 992 && window.bootstrap) {
            var el = document.getElementById('userSidebar');
            var inst = bootstrap.Offcanvas.getInstance(el);
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
