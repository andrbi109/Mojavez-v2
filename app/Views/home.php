<?php
/**
 * صفحهٔ اصلی — طراحی «ایرانی مدرن»
 */
$base = rtrim(BASE_URL, '/') . '/';
?>

<!-- هیرو -->
<section class="hero text-center">
    <div style="position:relative; z-index:1;">
        <span class="owner-chip mb-3 d-inline-flex">👤 به مدیریت <?= htmlspecialchars(owner_name()) ?></span>
        <h1>ثبت درخواست مجوز صنایع دستی، ساده و شفاف</h1>
        <p class="lead mb-4">
            از پر کردن فرم چندمرحله‌ای تا بارگذاری مدارک و پیگیری لحظه‌به‌لحظهٔ پرونده —
            همه‌چیز در چند دقیقه، بدون مراجعهٔ حضوری.
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-2">
            <a href="<?= $base ?>register" class="btn btn-gold btn-lg px-4"><?= icon('edit', '', 17) ?> شروع ثبت‌نام</a>
            <a href="<?= $base ?>tracking" class="btn btn-outline-light btn-lg px-4"><?= icon('search', '', 17) ?> پیگیری پرونده</a>
        </div>
    </div>
</section>

<!-- کارت‌های سرویس -->
<div class="row g-3 g-md-4 mb-5">
    <div class="col-12 col-md-4">
        <div class="service-card">
            <div class="service-icon"><span><?= icon('form', '', 26) ?></span></div>
            <h5 class="fw-bold">ثبت‌نام چندمرحله‌ای</h5>
            <p class="text-muted small mb-3">
                اطلاعات هویتی، شغلی و ملک را گام‌به‌گام وارد کنید و مدارک را بارگذاری نمایید.
                در پایان <strong>کد رهگیری یکتا</strong> دریافت می‌کنید.
            </p>
            <a href="<?= $base ?>register" class="btn btn-primary">ورود به فرم ثبت‌نام</a>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="service-card">
            <div class="service-icon gold"><span><?= icon('search', '', 26) ?></span></div>
            <h5 class="fw-bold">پیگیری وضعیت</h5>
            <p class="text-muted small mb-3">
                با کد رهگیری، تایم‌لاین کامل پرونده را ببینید، درخواست تغییر کد پستی بدهید
                و نسخهٔ چاپی A4 دریافت کنید.
            </p>
            <a href="<?= $base ?>tracking" class="btn btn-outline-primary">پیگیری کنید</a>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="service-card">
            <div class="service-icon"><span><?= icon('book', '', 26) ?></span></div>
            <h5 class="fw-bold">راهنمای جامع</h5>
            <p class="text-muted small mb-3">
                فهرست مدارک، قواعد زمانی (لغو خودکار ۱۴ روزه)، سؤالات متداول
                و <strong>نحوهٔ اطلاع‌رسانی به مدیر سامانه</strong> را مطالعه کنید.
            </p>
            <a href="<?= $base ?>guide" class="btn btn-outline-primary">مشاهدهٔ راهنما</a>
        </div>
    </div>
</div>

<!-- نوار مراحل -->
<div class="card p-4 mb-5">
    <div class="section-heading">
        <span class="ornament">❖</span><h5 class="fw-bold mb-0"><?= icon('list', '', 19) ?> مراحل ثبت درخواست مجوز</h5>
    </div>
    <div class="row g-3 text-center">
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3" style="background:var(--turquoise-light)">
                <div class="fs-3 mb-1">🪪</div>
                <strong class="small">۱. اطلاعات هویتی</strong>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3" style="background:var(--saffron-light)">
                <div class="fs-3 mb-1">🏺</div>
                <strong class="small">۲. اطلاعات شغلی</strong>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3" style="background:var(--turquoise-light)">
                <div class="fs-3 mb-1">🏠</div>
                <strong class="small">۳. اطلاعات ملک</strong>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3" style="background:var(--saffron-light)">
                <div class="fs-3 mb-1">📎</div>
                <strong class="small">۴. بارگذاری مدارک</strong>
            </div>
        </div>
    </div>
</div>

<!-- اطلاع‌رسانی مهم -->
<div class="card p-4 mb-5" style="border-right:5px solid var(--saffron);">
    <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
        <div style="color:var(--saffron)"><?= icon('megaphone', '', 40) ?></div>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1" style="color:var(--saffron-dark);">پس از دریافت کد رهگیری، به ما پیام دهید!</h5>
            <p class="text-muted small mb-2">
                برای اطلاع مدیر سامانه از ثبت درخواست خود، پس از اخذ کد رهگیری به آیدی
                <strong>@<?= htmlspecialchars(setting('owner_username', 'Derogaramin')) ?></strong>
                در شبکه‌های اجتماعی <strong>ایتا، روبیکا و بله</strong> پیام دهید
                (قالب آمادهٔ پیام در صفحهٔ موفقیت و راهنما موجود است).
            </p>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach (social_links() as $net => $link): ?>
                    <a class="social-chip <?= $net ?>" href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener">
                        <span class="dot"></span> <?= $link['label'] ?>
                    </a>
                <?php endforeach; ?>
                <a href="<?= $base ?>guide" class="social-chip"><?= icon('book', '', 16) ?> مشاهدهٔ قالب پیام</a>
            </div>
        </div>
    </div>
</div>

<!-- دربارهٔ مدیر سامانه -->
<div class="row g-3 align-items-center">
    <div class="col-md-8">
        <div class="card p-4 h-100">
            <div class="section-heading">
                <span class="ornament">❖</span><h5 class="fw-bold mb-0">دربارهٔ سامانه</h5>
            </div>
            <p class="text-muted small mb-2">
                این سامانه فرآیند درخواست مجوز صنایع دستی را از ثبت‌نام اولیه تا صدور مجوز مدیریت می‌کند:
                پروندهٔ الکترونیکی، تایم‌لاین شفاف رویدادها، لغو خودکار درخواست‌های راکد و گزارش‌گیری دقیق.
            </p>
            <a href="<?= $base ?>about" class="btn btn-outline-primary btn-sm align-self-start"><?= icon('info', '', 15) ?> دربارهٔ ما</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center h-100" style="background:linear-gradient(135deg,#16283C,#22374E); color:#fff; border:none;">
            <?php $ownerImg = setting('owner_image', ''); ?>
            <?php if ($ownerImg !== ''): ?>
                <div class="owner-avatar" style="width:72px;height:72px;border-color:rgba(255,255,255,.3);">
                    <img src="<?= $base . htmlspecialchars($ownerImg) ?>" alt="مدیر سامانه">
                </div>
            <?php else: ?>
                <div class="fs-1 mb-2">🧑‍💼</div>
            <?php endif; ?>
            <strong><?= htmlspecialchars(owner_name()) ?></strong>
            <small style="color:var(--saffron)">@<?= htmlspecialchars(setting('owner_username', 'Derogaramin')) ?></small>
            <small class="mt-2" style="color:#B9C4CF">طراح، توسعه‌دهنده و مدیر سامانه</small>
        </div>
    </div>
</div>
