<?php
/**
 * داشبورد کاربر — نمایش درخواست‌ها و پرداخت‌ها (مدل فروشگاهی)
 */
$base = rtrim(BASE_URL, '/') . '/';
$s = $stats ?? [];
$apps = $applications ?? [];
$pays = $payments ?? [];
$statusMeta = (require CONFIG_PATH . '/statuses.php')['statuses'];
?>

<style>
.user-hero {
    background: linear-gradient(135deg, var(--navy) 0%, #1B3A5C 50%, #0E7490 100%);
    color: #fff;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 32px rgba(26, 42, 79, 0.3);
    position: relative;
    overflow: hidden;
}
.user-hero::before {
    content: '';
    position: absolute;
    top: -50%; inset-inline-end: -20%;
    width: 60%; height: 200%;
    background: radial-gradient(ellipse, rgba(244,163,0,.12) 0%, transparent 70%);
    pointer-events: none;
}
.user-hero h1, .user-hero h2, .user-hero h3, .user-hero h4, .user-hero h5, .user-hero h6 { color: #fff !important; }
.user-hero .display-num {
    font-size: 2.5rem; font-weight: 800; line-height: 1; margin: 0; color: #fff;
    text-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.user-hero .display-num small { font-size: .9rem; font-weight: 600; opacity: .85; color: var(--saffron); }
.user-hero .display-label { font-size: .8rem; opacity: .75; margin: 0; color: #B9C4CF; }

.kpi-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, rgba(0,0,0,0.06));
    border-radius: 16px; padding: 1.5rem; height: 100%;
    transition: transform .2s ease, box-shadow .2s ease;
    text-decoration: none; color: inherit;
    display: flex; flex-direction: column; gap: .75rem;
    position: relative; overflow: hidden;
}
.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(0,0,0,.10);
    text-decoration: none; color: inherit;
}
.kpi-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,.12);
}
.kpi-icon.navy { background: linear-gradient(135deg, var(--navy), #2C3E66); }
.kpi-icon.turquoise { background: linear-gradient(135deg, var(--turquoise), var(--turquoise-dark)); }
.kpi-icon.green { background: linear-gradient(135deg, #10B981, #059669); }
.kpi-icon.gold { background: linear-gradient(135deg, var(--saffron), #D97706); }
.kpi-value { font-size: 1.8rem; font-weight: 800; line-height: 1; color: var(--ink, #1A2A4F); }
.kpi-label { font-size: .85rem; color: var(--muted, #6B7280); margin: 0; }

[data-bs-theme="dark"] .kpi-value { color: #fff; }
[data-bs-theme="dark"] .kpi-label { color: #94A3B8; }
[data-bs-theme="dark"] .kpi-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.app-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, rgba(0,0,0,0.06));
    border-radius: 14px; padding: 1rem;
    transition: all .2s ease;
    text-decoration: none; color: inherit;
    display: flex; align-items: center; gap: .75rem;
}
.app-card:hover {
    border-color: var(--turquoise);
    transform: translateX(-3px);
    text-decoration: none; color: inherit;
}
.app-card .app-icon {
    width: 44px; height: 44px; border-radius: 10px;
    background: var(--turquoise-light); color: var(--turquoise-dark);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
[data-bs-theme="dark"] .app-card { background: #1E293B; border-color: rgba(255,255,255,.08); }
[data-bs-theme="dark"] .app-card .app-icon { background: rgba(0,180,216,.15); color: var(--turquoise); }
</style>

<!-- ===== بخش هیرو ===== -->
<div class="user-hero">
    <div class="row align-items-center g-3">
        <div class="col-md-8">
            <div class="mb-2 display-label">خوش آمدید 👋</div>
            <h4 class="fw-bold mb-3"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
            <div class="row g-4">
                <div class="col-6 col-md-3">
                    <p class="display-num"><?= (int)$s['total_apps'] ?></p>
                    <p class="display-label mb-0">کل درخواست‌ها</p>
                </div>
                <div class="col-6 col-md-3">
                    <p class="display-num"><?= (int)$s['pending_apps'] ?></p>
                    <p class="display-label mb-0">در حال رسیدگی</p>
                </div>
                <div class="col-6 col-md-3">
                    <p class="display-num"><?= (int)$s['issued_apps'] ?></p>
                    <p class="display-label mb-0">مجوز صادرشده</p>
                </div>
                <div class="col-6 col-md-3">
                    <p class="display-num"><?= number_format($s['total_paid']) ?><small> ت</small></p>
                    <p class="display-label mb-0">پرداخت‌شده</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?= $base ?>services" class="btn btn-light fw-bold mb-2">
                <?= icon('plus', '', 16) ?> درخواست جدید
            </a><br>
            <a href="<?= $base ?>tracking" class="btn btn-outline-light">
                <?= icon('search', '', 16) ?> پیگیری با کد رهگیری
            </a>
        </div>
    </div>
</div>

<!-- ===== کارت‌های آماری ===== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon navy"><?= icon('folder', '', 22) ?></div>
            </div>
            <div>
                <div class="kpi-value"><?= (int)$s['total_apps'] ?></div>
                <p class="kpi-label">کل درخواست‌ها</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon turquoise"><?= icon('clock', '', 22) ?></div>
            </div>
            <div>
                <div class="kpi-value"><?= (int)$s['pending_apps'] ?></div>
                <p class="kpi-label">در حال رسیدگی</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon green"><?= icon('check-circle', '', 22) ?></div>
            </div>
            <div>
                <div class="kpi-value"><?= (int)$s['issued_apps'] ?></div>
                <p class="kpi-label">مجوز صادرشده</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon gold"><?= icon('money', '', 22) ?></div>
            </div>
            <div>
                <div class="kpi-value" style="font-size:1.4rem"><?= number_format($s['total_paid']) ?></div>
                <p class="kpi-label">تومان — پرداخت‌شده</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ===== درخواست‌های من ===== -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><?= icon('folder', 'me-1', 18) ?> درخواست‌های من</span>
                <a href="<?= $base ?>services" class="btn btn-sm btn-primary"><?= icon('plus', '', 14) ?> جدید</a>
            </div>
            <div class="card-body">
                <?php if (empty($apps)): ?>
                    <div class="text-center py-4">
                        <div style="font-size:3rem;opacity:.3"><?= icon('folder', '', 48) ?></div>
                        <p class="text-muted mt-2 mb-3">هنوز درخواستی ثبت نکرده‌اید</p>
                        <a href="<?= $base ?>services" class="btn btn-primary">اولین درخواست خود را شروع کنید</a>
                    </div>
                <?php else: ?>
                    <div class="d-grid gap-2">
                        <?php foreach ($apps as $app): ?>
                            <a href="<?= $base ?>tracking/status/<?= rawurlencode($app['tracking_code']) ?>" class="app-card">
                                <span class="app-icon"><?= icon('vase', '', 20) ?></span>
                                <div class="flex-grow-1">
                                    <div class="fw-bold small"><?= htmlspecialchars($app['service_title'] ?? 'مجوز صنایع دستی') ?></div>
                                    <code class="small text-muted" dir="ltr"><?= htmlspecialchars($app['tracking_code']) ?></code>
                                </div>
                                <span class="badge bg-<?= $statusMeta[$app['current_status']]['color'] ?? 'secondary' ?>"><?= htmlspecialchars($app['current_status']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== پرداخت‌های من ===== -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header fw-bold"><?= icon('money', 'me-1', 18) ?> پرداخت‌های من</div>
            <div class="card-body">
                <?php if (empty($pays)): ?>
                    <div class="text-center py-4">
                        <div style="font-size:3rem;opacity:.3"><?= icon('money', '', 48) ?></div>
                        <p class="text-muted mt-2 mb-0">هنوز پرداختی ثبت نشده</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($pays, 0, 5) as $pay): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center small">
                                <span>
                                    <strong><?= number_format((int)$pay['amount']) ?> ت</strong>
                                    <span class="text-muted">(<?= htmlspecialchars($pay['tracking_code'] ?? '') ?>)</span>
                                </span>
                                <span class="badge bg-<?= $pay['status'] === 'تأیید شده' ? 'success' : ($pay['status'] === 'رد شده' ? 'danger' : 'warning text-dark') ?>"><?= htmlspecialchars($pay['status']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($pays) > 5): ?>
                        <div class="text-center mt-2">
                            <small class="text-muted">و <?= count($pays) - 5 ?> پرداخت دیگر...</small>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== اطلاعات حساب ===== -->
<div class="card mt-4">
    <div class="card-header fw-bold"><?= icon('user', 'me-1', 18) ?> اطلاعات حساب من</div>
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-4">
                <label class="text-muted d-block">نام و نام خانوادگی</label>
                <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
            </div>
            <div class="col-md-4">
                <label class="text-muted d-block">شماره موبایل</label>
                <strong dir="ltr"><?= htmlspecialchars($user['mobile']) ?></strong>
            </div>
            <div class="col-md-4">
                <label class="text-muted d-block">کد ملی</label>
                <strong dir="ltr"><?= htmlspecialchars($user['national_code']) ?></strong>
            </div>
            <div class="col-md-4">
                <label class="text-muted d-block">تاریخ ثبت‌نام</label>
                <strong><?= persianDate((string)$user['created_at']) ?></strong>
            </div>
            <div class="col-md-4">
                <label class="text-muted d-block">آخرین ورود</label>
                <strong><?= !empty($user['last_login_at']) ? persianDate((string)$user['last_login_at']) : '—' ?></strong>
            </div>
        </div>
        <hr>
        <a href="<?= $base ?>logout" class="btn btn-outline-danger btn-sm"><?= icon('logout', '', 14) ?> خروج از حساب</a>
    </div>
</div>
