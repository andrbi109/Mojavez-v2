<?php
/**
 * قالب عمومی سامانه — طراحی «ایرانی مدرن»
 * برندینگ: نام مالک سامانه (امین دروگر بزدی) و آیدی شبکه‌های اجتماعی
 */
$base = rtrim(BASE_URL, '/') . '/';
$siteName = setting('site_name', 'سامانهٔ مجوزهای صنایع دستی');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($siteName) ?> — ثبت درخواست، پیگیری و صدور مجوز صنایع دستی | مدیریت: <?= htmlspecialchars(owner_name()) ?>">
    <title><?= htmlspecialchars($title ?? $siteName) ?></title>
    <!-- تم زودهنگام روز/شب (ضد پرش رنگ) -->
    <script src="<?= $base ?>assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="<?= $base ?>assets/font.css">
    <?php if (USE_CDN): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="<?= $base ?>assets/bootstrap.rtl.min.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
    <?= $extraHead ?? '' ?>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- سربرگ سرمه‌ای با نشان فیروزه‌ای -->
    <nav class="navbar navbar-expand-md site-navbar mb-4 no-print sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= $base ?>">
                <span class="brand-badge">🏺</span>
                <span><?= htmlspecialchars($siteName) ?></span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                    aria-controls="mainNav" aria-expanded="false" aria-label="منوی اصلی">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-md-auto align-items-md-center gap-md-1 mt-3 mt-md-0">
                    <li class="nav-item"><a class="nav-link <?= $path === $base || $path === rtrim($base,'/') ? 'active-link' : '' ?>" href="<?= $base ?>">خانه</a></li>
                    <li class="nav-item"><a class="nav-link <?= str_contains($path, 'services') ? 'active-link' : '' ?>" href="<?= $base ?>services">خدمات</a></li>
                    <li class="nav-item"><a class="nav-link <?= str_contains($path, 'tracking') ? 'active-link' : '' ?>" href="<?= $base ?>tracking">پیگیری</a></li>
                    <li class="nav-item"><a class="nav-link <?= str_contains($path, 'guide') ? 'active-link' : '' ?>" href="<?= $base ?>guide">راهنما</a></li>
                    <li class="nav-item"><a class="nav-link <?= str_contains($path, 'about') ? 'active-link' : '' ?>" href="<?= $base ?>about">دربارهٔ ما</a></li>
                    <li class="nav-item ms-md-2 mt-2 mt-md-0 d-flex align-items-md-center gap-2">
                        <button type="button" class="theme-toggle" data-theme-toggle onclick="mojavezToggleTheme()" aria-label="تغییر تم روز/شب">
                            <span class="icon-sun"><?= icon('sun', '', 16) ?></span><span class="icon-moon"><?= icon('moon', '', 16) ?></span>
                        </button>
                        <?php if (Session::get('user_logged_in')): ?>
                            <a href="<?= $base ?>dashboard" class="btn btn-primary btn-sm px-3"><?= icon('user', '', 15) ?> داشبورد من</a>
                        <?php else: ?>
                            <a href="<?= $base ?>login" class="btn btn-outline-primary btn-sm px-3">ورود</a>
                            <a href="<?= $base ?>signup" class="btn btn-gold btn-sm px-3">ثبت‌نام</a>
                        <?php endif; ?>
                        <a href="<?= $base ?>admin/login" class="btn btn-outline-secondary btn-sm px-3"><?= icon('lock', '', 15) ?> مدیریت</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- محتوای اصلی -->
    <main class="container pb-5 flex-grow-1">
        <?= $content ?? '' ?>
    </main>

    <!-- فوتر: برندینگ مالک سامانه + شبکه‌های اجتماعی -->
    <footer class="site-footer no-print">
        <div class="container">
            <div class="row g-3 align-items-center">
                <div class="col-md-5 text-center text-md-start">
                    <div class="footer-brand mb-1">🏺 <?= htmlspecialchars($siteName) ?></div>
                    <small>
                        طراحی، توسعه و مدیریت:
                        <strong class="text-white"><?= htmlspecialchars(owner_name()) ?></strong>
                        <span class="owner-chip ms-1 mt-1">@<?= htmlspecialchars(setting('owner_username', 'Derogaramin')) ?></span>
                    </small>
                </div>
                <div class="col-md-7">
                    <div class="d-flex justify-content-center justify-content-md-end flex-wrap gap-2">
                        <?php foreach (social_links() as $net => $link): ?>
                            <a class="social-chip <?= $net ?>" href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener">
                                <span class="dot"></span> <?= $link['label'] ?>: @<?= htmlspecialchars($link['id']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-center text-md-end small mt-2 mb-0" style="color:#8B98A5;">
                        © <?= jdate('Y') ?> — کلیهٔ حقوق محفوظ است
                        <span style="opacity:.6; margin-inline-start:.4rem;">v<?= defined('APP_VERSION') ? APP_VERSION : '0.3.7' ?></span>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <?php if (USE_CDN): ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php else: ?>
        <script src="<?= $base ?>assets/bootstrap.bundle.min.js"></script>
    <?php endif; ?>
    <script src="<?= $base ?>assets/js/theme.js"></script>
</body>
</html>
