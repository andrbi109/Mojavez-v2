<?php
/**
 * صفحهٔ اصلی — بازطراحی‌شده برای مدل فروشگاهی
 * ---------------------------------------------------
 * طراحی «ایرانی مدرن» با گرادیان navy→فیروزه‌ای
 * کارت‌های خدمت، فراخوان به ثبت‌نام/ورود، و نمایش برندهای سامانه
 */
$base = rtrim(BASE_URL, '/') . '/';
$userLoggedIn = Session::get('user_logged_in', false);
$userName = Session::get('user_name', '');
$services = [];
try {
    $services = (new ServiceType())->getActive();
} catch (\Throwable $e) {}
?>

<!-- هیرو -->
<section class="hero text-center">
    <div style="position:relative; z-index:1;">
        <?php if (!empty($userName)): ?>
            <span class="owner-chip mb-3 d-inline-flex">👋 <?= htmlspecialchars($userName) ?> خوش آمدید</span>
        <?php else: ?>
            <span class="owner-chip mb-3 d-inline-flex">👤 به مدیریت <?= htmlspecialchars(owner_name()) ?></span>
        <?php endif; ?>
        <h1>ثبت درخواست مجوز صنایع دستی، ساده و شفاف</h1>
        <p class="lead mb-4">
            از انتخاب خدمت تا بارگذاری مدارک و پیگیری لحظه‌به‌لحظهٔ پرونده —
            همه‌چیز در چند دقیقه، بدون مراجعهٔ حضوری.
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-2">
            <?php if ($userLoggedIn): ?>
                <a href="<?= $base ?>services" class="btn btn-gold btn-lg px-4"><?= icon('shopping-cart', '', 17) ?> مشاهدهٔ خدمات</a>
                <a href="<?= $base ?>dashboard" class="btn btn-outline-light btn-lg px-4"><?= icon('dashboard', '', 17) ?> داشبورد من</a>
            <?php else: ?>
                <a href="<?= $base ?>signup" class="btn btn-gold btn-lg px-4"><?= icon('user', '', 17) ?> ثبت‌نام کنید</a>
                <a href="<?= $base ?>services" class="btn btn-outline-light btn-lg px-4"><?= icon('grid', '', 17) ?> مشاهدهٔ خدمات</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- کارت‌های شاخص -->
<div class="row g-3 g-md-4 mb-5">
    <div class="col-12 col-md-4">
        <div class="service-card">
            <div class="service-icon"><span><?= icon('form', '', 26) ?></span></div>
            <h5 class="fw-bold">ثبت‌نام چندمرحله‌ای</h5>
            <p class="text-muted small mb-3">
                اطلاعات هویتی، شغلی و ملک را گام‌به‌گام وارد کنید و مدارک را بارگذاری نمایید.
                در پایان <strong>کد رهگیری یکتا</strong> دریافت می‌کنید.
            </p>
            <a href="<?= $base ?>services" class="btn btn-primary">شروع ثبت‌نام</a>
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

<!-- پیش‌نمایش خدمات -->
<?php if (!empty($services)): ?>
<div class="mb-5">
    <div class="text-center mb-4">
        <h3 class="fw-bold" style="color:var(--navy)">خدمات ما</h3>
        <p class="text-muted small mb-0">مجوز مورد نیاز خود را انتخاب کنید</p>
    </div>
    <div class="row g-3 g-md-4">
        <?php foreach (array_slice($services, 0, 3) as $sv): ?>
            <div class="col-12 col-md-4">
                <div class="service-card">
                    <div class="service-icon <?= ($sv['id'] ?? 1) % 2 === 0 ? 'gold' : '' ?>"><span><?= icon('vase', '', 26) ?></span></div>
                    <h5 class="fw-bold"><?= htmlspecialchars($sv['title']) ?></h5>
                    <p class="text-muted small mb-3">
                        <?= htmlspecialchars(mb_substr($sv['description'] ?? '', 0, 100)) ?><?= mb_strlen($sv['description'] ?? '') > 100 ? '...' : '' ?>
                    </p>
                    <a href="<?= $base ?>services" class="btn btn-outline-primary">مشاهده و شروع</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (count($services) > 3): ?>
        <div class="text-center mt-3">
            <a href="<?= $base ?>services" class="btn btn-link">مشاهدهٔ همهٔ خدمات (<?= count($services) ?>) ←</a>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- آمار سریع -->
<div class="row g-3 mb-5 text-center">
    <div class="col-6 col-md-3">
        <div class="p-3 rounded-3" style="background:var(--turquoise-light)">
            <h3 class="fw-bold mb-0" style="color:var(--turquoise-dark)"><?= icon('clock', '', 24) ?></h3>
            <p class="small text-muted mb-0 mt-1">چند دقیقه‌ای</p>
            <p class="fw-bold small mb-0">زمان ثبت‌نام</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 rounded-3" style="background:var(--saffron-light)">
            <h3 class="fw-bold mb-0" style="color:var(--saffron-dark)"><?= icon('shield', '', 24) ?></h3>
            <p class="small text-muted mb-0 mt-1">امن و شفاف</p>
            <p class="fw-bold small mb-0">محافظت داده</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 rounded-3" style="background:var(--turquoise-light)">
            <h3 class="fw-bold mb-0" style="color:var(--turquoise-dark)"><?= icon('phone', '', 24) ?></h3>
            <p class="small text-muted mb-0 mt-1">آنی</p>
            <p class="fw-bold small mb-0">اطلاع‌رسانی</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-3 rounded-3" style="background:var(--saffron-light)">
            <h3 class="fw-bold mb-0" style="color:var(--saffron-dark)"><?= icon('check-circle', '', 24) ?></h3>
            <p class="small text-muted mb-0 mt-1">رایگان</p>
            <p class="fw-bold small mb-0">حساب کاربری</p>
        </div>
    </div>
</div>

<!-- فراخوان نهایی -->
<div class="text-center py-4 mb-3">
    <h4 class="fw-bold mb-2" style="color:var(--navy)">آماده شروع هستید؟</h4>
    <p class="text-muted mb-3">همین حالا حساب کاربری رایگان بسازید و اولین درخواست خود را ثبت کنید</p>
    <div class="d-flex justify-content-center flex-wrap gap-2">
        <?php if ($userLoggedIn): ?>
            <a href="<?= $base ?>services" class="btn btn-primary btn-lg px-4"><?= icon('shopping-cart', '', 17) ?> مشاهدهٔ خدمات</a>
        <?php else: ?>
            <a href="<?= $base ?>signup" class="btn btn-gold btn-lg px-4"><?= icon('user', '', 17) ?> ثبت‌نام کنید</a>
            <a href="<?= $base ?>login" class="btn btn-outline-primary btn-lg px-4">ورود</a>
        <?php endif; ?>
    </div>
</div>
