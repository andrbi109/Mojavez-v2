<?php
/**
 * فهرست خدمات — کارت‌های محصول (مدل فروشگاهی)
 */
$base = rtrim(BASE_URL, '/') . '/';
$serviceIcons = [
    1 => ['icon' => 'vase', 'color' => 'turquoise'],
    2 => ['icon' => 'home', 'color' => 'gold'],
    3 => ['icon' => 'users', 'color' => 'green'],
    4 => ['icon' => 'map-pin', 'color' => 'navy'],
    5 => ['icon' => 'award', 'color' => 'purple'],
];
?>

<style>
.service-hero {
    background: linear-gradient(135deg, var(--navy) 0%, #1B3A5C 50%, #0E7490 100%);
    color: #fff;
    border-radius: 20px;
    padding: 2.5rem 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(26, 42, 79, 0.3);
    position: relative;
    overflow: hidden;
    text-align: center;
}
.service-hero::before {
    content: '';
    position: absolute;
    top: -50%; inset-inline-end: -20%;
    width: 60%; height: 200%;
    background: radial-gradient(ellipse, rgba(244,163,0,.12) 0%, transparent 70%);
    pointer-events: none;
}
.service-hero h1 { color: #fff !important; }

.product-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, rgba(0,0,0,0.06));
    border-radius: 20px;
    overflow: hidden;
    height: 100%;
    transition: transform .2s ease, box-shadow .2s ease;
    display: flex;
    flex-direction: column;
}
.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 40px rgba(0,0,0,.12);
}
.product-header {
    padding: 2rem 1.5rem 1rem;
    text-align: center;
    position: relative;
}
.product-icon {
    width: 72px; height: 72px;
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem;
    color: #fff;
    box-shadow: 0 6px 20px rgba(0,0,0,.12);
}
.product-icon.turquoise { background: linear-gradient(135deg, var(--turquoise), var(--turquoise-dark)); }
.product-icon.gold { background: linear-gradient(135deg, var(--saffron), #D97706); }
.product-icon.green { background: linear-gradient(135deg, #10B981, #059669); }
.product-icon.navy { background: linear-gradient(135deg, var(--navy), #2C3E66); }
.product-icon.purple { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }

.product-body {
    padding: 0 1.5rem 1.5rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}
.product-authority {
    font-size: .8rem;
    color: var(--muted);
    text-align: center;
    margin-bottom: .5rem;
}
.product-desc {
    font-size: .9rem;
    color: var(--ink);
    line-height: 1.7;
    text-align: justify;
    flex-grow: 1;
}
.product-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-color, rgba(0,0,0,0.06));
}

[data-bs-theme="dark"] .product-card { background: #1E293B; border-color: rgba(255,255,255,.08); }
[data-bs-theme="dark"] .product-desc { color: #E2E8F0; }
</style>

<!-- ===== بخش هیرو ===== -->
<div class="service-hero">
    <h1 class="fw-bold mb-2">خدمات و مجوزهای سامانه</h1>
    <p class="mb-0 opacity-75">مجوز مورد نیاز خود را انتخاب کنید و فرآیند ثبت‌نام را آغاز کنید</p>
</div>

<!-- ===== کارت‌های محصول ===== -->
<div class="row g-4 mb-5">
    <?php foreach ($services as $sv): ?>
        <?php
        $iconInfo = $serviceIcons[(int)$sv['id']] ?? ['icon' => 'form', 'color' => 'turquoise'];
        $iconName = $iconInfo['icon'];
        $colorClass = $iconInfo['color'];
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="product-card">
                <div class="product-header">
                    <div class="product-icon <?= $colorClass ?>"><?= icon($iconName, '', 32) ?></div>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($sv['title']) ?></h5>
                    <?php if (!empty($sv['authority'])): ?>
                        <div class="product-authority"><?= icon('shield', '', 12) ?> <?= htmlspecialchars($sv['authority']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="product-body">
                    <?php if (!empty($sv['description'])): ?>
                        <p class="product-desc"><?= htmlspecialchars($sv['description']) ?></p>
                    <?php else: ?>
                        <p class="product-desc text-muted">توضیحات این خدمت به‌زودی تکمیل می‌شود.</p>
                    <?php endif; ?>
                </div>
                <div class="product-footer">
                    <form method="post" action="<?= $base ?>services/<?= (int)$sv['id'] ?>/purchase">
                        <?= Session::csrfField() ?>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <?= icon('shopping-cart', '', 16) ?> شروع درخواست
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ===== راهنما ===== -->
<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="product-icon turquoise" style="width:48px;height:48px;border-radius:12px;flex-shrink:0;"><?= icon('info', '', 22) ?></div>
                    <div>
                        <h6 class="fw-bold">چگونه شروع کنم؟</h6>
                        <ol class="small text-muted mb-0 ps-3">
                            <li>یک حساب کاربری رایگان بسازید (یا وارد شوید)</li>
                            <li>خدمت مورد نظر خود را انتخاب کنید</li>
                            <li>فرم ثبت‌نام را در ۴ مرحله تکمیل کنید</li>
                            <li>کد رهگیری دریافت کنید و پرونده را پیگیری کنید</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="product-icon gold" style="width:48px;height:48px;border-radius:12px;flex-shrink:0;"><?= icon('phone', '', 22) ?></div>
                    <div>
                        <h6 class="fw-bold">نیاز به کمک دارید؟</h6>
                        <p class="small text-muted mb-2">اگر در انتخاب خدمت یا تکمیل ثبت‌نام سوالی دارید، با ما در تماس باشید:</p>
                        <p class="small mb-0">
                            آیدی <strong dir="ltr">@<?= htmlspecialchars(setting('owner_username', 'Derogaramin')) ?></strong>
                            در ایتا، روبیکا و بله
                        </p>
                        <a href="<?= $base ?>guide" class="btn btn-outline-primary btn-sm mt-2">راهنمای جامع</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
