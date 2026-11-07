<?php
/**
 * صفحهٔ «دربارهٔ ما»
 * -------------------------------------------------
 * محتوا کاملاً از تنظیمات سامانه خوانده می‌شود و مدیر می‌تواند آن را
 * از پنل مدیریت → تنظیمات تغییر دهد.
 * قالب‌بندی ساده: خطوط با «###» سرفصل می‌شوند؛ پاراگراف‌ها با خط خالی جدا می‌شوند.
 */
$base = rtrim(BASE_URL, '/') . '/';
$aboutTitle = setting('about_title', 'دربارهٔ ما');
$aboutContent = setting('about_content', '');
$username = setting('owner_username', 'Derogaramin');
$links = social_links();

// تبدیل متن ساده به HTML امن
$renderAbout = function (string $text): string {
    $blocks = preg_split('/\n\s*\n/', trim($text));
    $html = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') continue;
        if (str_starts_with($block, '### ')) {
            $html .= '<h5 class="fw-bold mt-4 mb-2" style="color:var(--turquoise-dark)">'
                . htmlspecialchars(mb_substr($block, 4)) . '</h5>';
        } else {
            $html .= '<p class="mb-3" style="line-height:2.1">' . nl2br(htmlspecialchars($block)) . '</p>';
        }
    }
    return $html;
};
?>

<!-- سربرگ صفحهٔ درباره -->
<div class="hero text-center mb-4" style="padding:2rem 1.2rem;">
    <div style="position:relative;z-index:1">
        <div class="fs-1 mb-2">🏺</div>
        <h1 class="mb-2"><?= htmlspecialchars($aboutTitle) ?></h1>
        <p class="lead mb-0"><?= htmlspecialchars(setting('site_name')) ?></p>
    </div>
</div>

<div class="row g-4">
    <!-- محتوای درباره (قابل ویرایش از پنل) -->
    <div class="col-lg-8">
        <div class="card p-4 p-md-5">
            <?= $renderAbout($aboutContent) ?>
        </div>
    </div>

    <!-- کارت مدیر سامانه -->
    <div class="col-lg-4">
        <div class="card p-4 text-center mb-4" style="background:linear-gradient(135deg,#16283C,#22374E); color:#fff; border:none;">
            <?php $ownerImg = setting('owner_image', ''); ?>
            <div class="owner-avatar">
                <?php if ($ownerImg !== ''): ?>
                    <img src="<?= $base . htmlspecialchars($ownerImg) ?>" alt="<?= htmlspecialchars(owner_name()) ?>">
                <?php else: ?>
                    <span style="font-size:2.2rem">🧑‍💼</span>
                <?php endif; ?>
            </div>
            <h5 class="fw-bold text-white mb-1"><?= htmlspecialchars(owner_name()) ?></h5>
            <small style="color:var(--saffron)" dir="ltr">@<?= htmlspecialchars($username) ?></small>
            <p class="small mt-2 mb-0" style="color:#B9C4CF;">
                طراح، توسعه‌دهنده و مدیر سامانهٔ مجوزهای صنایع دستی
            </p>
        </div>

        <div class="card p-4">
            <h6 class="fw-bold mb-3">📱 ارتباط با مدیر سامانه</h6>
            <p class="small text-muted">
                برای هماهنگی، پشتیبانی و اطلاع‌رسانی ثبت درخواست، به آیدی
                <strong dir="ltr">@<?= htmlspecialchars($username) ?></strong>
                در شبکه‌های اجتماعی زیر پیام دهید:
            </p>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($links as $net => $link): ?>
                    <a class="social-chip <?= $net ?> justify-content-between" href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener">
                        <span class="d-flex align-items-center gap-2"><span class="dot"></span> <?= $link['label'] ?></span>
                        <small dir="ltr">@<?= htmlspecialchars($link['id']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card p-4 mt-4 text-center">
            <a href="<?= $base ?>register" class="btn btn-primary w-100 mb-2">📝 شروع ثبت‌نام</a>
            <a href="<?= $base ?>guide" class="btn btn-outline-primary w-100">📖 راهنمای جامع</a>
        </div>
    </div>
</div>
