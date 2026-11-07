<?php
/**
 * پنل کاربری — نمایش پروفایل، درخواست‌ها، سوابق، مجوزها، روند درخواست
 */
$base = rtrim(BASE_URL, '/') . '/';
$u = $user ?? [];
$apps = $applications ?? [];
$pays = $payments ?? [];
$lics = $licenses ?? [];
$latestApp = $latestApp ?? null;
$timeline = $latestTimeline ?? [];
$sm = $statusMeta ?? [];
$s = $stats ?? [];
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$stateBadge = ['جاری' => 'primary', 'صادر شده' => 'success', 'باطل شده' => 'danger', 'منقضی' => 'secondary'];
?>

<style>
.panel-hero {
    background: linear-gradient(135deg, var(--navy) 0%, #1B3A5C 50%, #0E7490 100%);
    color: #fff;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 32px rgba(26,42,79,.3);
    position: relative;
    overflow: hidden;
}
.panel-hero::before {
    content: '';
    position: absolute;
    top: -50%; inset-inline-end: -20%;
    width: 60%; height: 200%;
    background: radial-gradient(ellipse, rgba(244,163,0,.12) 0%, transparent 70%);
    pointer-events: none;
}
.panel-hero h1,.panel-hero h2,.panel-hero h3,.panel-hero h4,.panel-hero h5,.panel-hero h6 { color: #fff !important; }
.panel-hero .avatar {
    width: 64px; height: 64px; border-radius: 50%;
    background: linear-gradient(135deg, var(--turquoise), var(--turquoise-dark));
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; font-weight: 700; color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(0,180,216,.4);
}
.panel-hero .display-num {
    font-size: 2.2rem; font-weight: 800; line-height: 1; margin: 0; color: #fff;
}
.panel-hero .display-label {
    font-size: .78rem; opacity: .7; margin: 0; color: #B9C4CF;
}

.stat-mini {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, rgba(0,0,0,.06));
    border-radius: 14px; padding: 1.2rem;
    height: 100%; display: flex; align-items: center; gap: .75rem;
    transition: transform .2s ease;
}
.stat-mini:hover { transform: translateY(-2px); }
.stat-mini .stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; flex-shrink: 0;
}
.stat-mini .stat-icon.navy { background: linear-gradient(135deg, var(--navy), #2C3E66); }
.stat-mini .stat-icon.turq { background: linear-gradient(135deg, var(--turquoise), var(--turquoise-dark)); }
.stat-mini .stat-icon.green { background: linear-gradient(135deg, #10B981, #059669); }
.stat-mini .stat-icon.gold { background: linear-gradient(135deg, var(--saffron), #D97706); }
.stat-mini .stat-val { font-size: 1.6rem; font-weight: 800; line-height: 1; color: var(--ink, #1A2A4F); }
.stat-mini .stat-lbl { font-size: .78rem; color: var(--muted, #6B7280); margin: 0; }

.app-row {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, rgba(0,0,0,.06));
    border-radius: 14px; padding: 1rem;
    transition: all .2s ease;
    text-decoration: none; color: inherit;
    display: flex; align-items: center; gap: .75rem;
}
.app-row:hover {
    border-color: var(--turquoise);
    box-shadow: 0 4px 16px rgba(0,0,0,.08);
    text-decoration: none; color: inherit;
}
.app-row .app-num {
    width: 40px; height: 40px; border-radius: 10px;
    background: var(--turquoise-light); color: var(--turquoise-dark);
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; font-weight: 700; flex-shrink: 0;
}

.timeline { list-style: none; padding: 0; margin: 0; position: relative; }
.timeline::before {
    content: ''; position: absolute; inset-inline-start: 11px; top: 0; bottom: 0;
    width: 2px; background: var(--border-color, rgba(0,0,0,.08));
}
.timeline-item {
    position: relative; padding: .4rem 0 .4rem 2.2rem;
}
.timeline-item::before {
    content: ''; position: absolute; inset-inline-start: 6px; top: .65rem;
    width: 12px; height: 12px; border-radius: 50%;
    background: var(--turquoise);
    border: 2px solid var(--card-bg, #fff);
    box-shadow: 0 0 0 2px var(--turquoise);
}

.profile-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, rgba(0,0,0,.06));
    border-radius: 16px; overflow: hidden;
}
.profile-card .profile-header {
    background: linear-gradient(135deg, var(--turquoise), var(--turquoise-dark));
    padding: 1.5rem; color: #fff; text-align: center;
}
.profile-card .profile-body { padding: 1.5rem; }
.profile-item {
    display: flex; justify-content: space-between; align-items: center;
    padding: .6rem 0; border-bottom: 1px solid var(--border-color, rgba(0,0,0,.04));
}
.profile-item:last-child { border-bottom: none; }
.profile-item .lbl { font-size: .82rem; color: var(--muted, #6B7280); }
.profile-item .val { font-size: .88rem; font-weight: 600; color: var(--ink, #1A2A4F); }

[data-bs-theme="dark"] .stat-mini, [data-bs-theme="dark"] .app-row,
[data-bs-theme="dark"] .profile-card {
    background: #1E293B; border-color: rgba(255,255,255,.08);
}
[data-bs-theme="dark"] .stat-mini .stat-val { color: #fff; }
[data-bs-theme="dark"] .stat-mini .stat-lbl { color: #94A3B8; }
[data-bs-theme="dark"] .profile-item .val { color: #E2E8F0; }
[data-bs-theme="dark"] .profile-item .lbl { color: #94A3B8; }
[data-bs-theme="dark"] .timeline::before { background: rgba(255,255,255,.08); }
[data-bs-theme="dark"] .timeline-item::before { box-shadow: 0 0 0 2px #1E293B; }
[data-bs-theme="dark"] .app-row .app-num { background: rgba(0,180,216,.15); color: var(--turquoise); }
</style>

<!-- ===== بخش هیرو: پروفایل ===== -->
<div class="panel-hero">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="avatar"><?= $e(mb_substr($u['first_name'] ?? '؟', 0, 1)) ?></div>
        <div class="flex-grow-1">
            <h4 class="fw-bold mb-1"><?= $e($u['first_name'] . ' ' . $u['last_name']) ?></h4>
            <p class="mb-0 display-label">پنل کاربری — مدیریت درخواست‌ها و سوابق</p>
        </div>
        <div class="text-start">
            <a href="<?= $base ?>services" class="btn btn-light btn-sm fw-bold mb-1">
                <?= icon('plus', '', 14) ?> درخواست جدید
            </a><br>
            <a href="<?= $base ?>tracking" class="btn btn-outline-light btn-sm">
                <?= icon('search', '', 14) ?> پیگیری
            </a>
        </div>
    </div>

    <!-- شمارنده‌های سریع -->
    <div class="row g-3 mt-2">
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

<!-- ===== کارت‌های آماری ===== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-mini">
            <div class="stat-icon navy"><?= icon('folder', '', 20) ?></div>
            <div>
                <div class="stat-val"><?= (int)$s['total_apps'] ?></div>
                <p class="stat-lbl">کل درخواست‌ها</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-mini">
            <div class="stat-icon turq"><?= icon('clock', '', 20) ?></div>
            <div>
                <div class="stat-val"><?= (int)$s['pending_apps'] ?></div>
                <p class="stat-lbl">در حال رسیدگی</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-mini">
            <div class="stat-icon green"><?= icon('check-circle', '', 20) ?></div>
            <div>
                <div class="stat-val"><?= (int)$s['issued_apps'] ?></div>
                <p class="stat-lbl">مجوز صادرشده</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-mini">
            <div class="stat-icon gold"><?= icon('money', '', 20) ?></div>
            <div>
                <div class="stat-val" style="font-size:1.2rem"><?= number_format($s['total_paid']) ?></div>
                <p class="stat-lbl">تومان پرداخت‌شده</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ===== ستون چپ: درخواست‌ها + روند ===== -->
    <div class="col-lg-8">

        <!-- درخواست‌های من -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><?= icon('folder', 'me-1', 18) ?> درخواست‌های من</span>
                <a href="<?= $base ?>services" class="btn btn-sm btn-primary"><?= icon('plus', '', 14) ?> جدید</a>
            </div>
            <div class="card-body">
                <?php if (empty($apps)): ?>
                    <div class="text-center py-4">
                        <div style="font-size:3rem;opacity:.2"><?= icon('folder', '', 48) ?></div>
                        <p class="text-muted mt-2 mb-3">هنوز درخواستی ثبت نکرده‌اید</p>
                        <a href="<?= $base ?>services" class="btn btn-primary"><?= icon('shopping-cart', '', 16) ?> مشاهدهٔ خدمات و شروع درخواست</a>
                    </div>
                <?php else: ?>
                    <div class="d-grid gap-2">
                        <?php foreach ($apps as $app): ?>
                            <a href="<?= $base ?>tracking/status/<?= rawurlencode($app['tracking_code']) ?>" class="app-row">
                                <span class="app-num"><?= $e(mb_substr($app['service_title'] ?? 'م', 0, 1)) ?></span>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-bold small text-truncate"><?= $e($app['service_title'] ?? 'مجوز صنایع دستی') ?></div>
                                    <code class="small text-muted" dir="ltr"><?= $e($app['tracking_code']) ?></code>
                                    <span class="small text-muted"> · <?= persianDate((string)$app['created_at']) ?></span>
                                </div>
                                <span class="badge bg-<?= $sm[$app['current_status']]['color'] ?? 'secondary' ?>"><?= $e($app['current_status']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- روند آخرین درخواست -->
        <?php if (!empty($timeline)): ?>
        <div class="card mb-4">
            <div class="card-header fw-bold"><?= icon('clock', 'me-1', 18) ?> روند آخرین درخواست</div>
            <div class="card-body">
                <?php if ($latestApp): ?>
                    <div class="mb-3">
                        <code dir="ltr"><?= $e($latestApp['tracking_code']) ?></code>
                        <span class="badge bg-<?= $sm[$latestApp['current_status']]['color'] ?? 'secondary' ?> ms-1"><?= $e($latestApp['current_status']) ?></span>
                    </div>
                <?php endif; ?>
                <ul class="timeline">
                    <?php foreach (array_slice($timeline, 0, 8) as $log): ?>
                        <li class="timeline-item">
                            <div class="d-flex justify-content-between flex-wrap gap-1">
                                <div>
                                    <strong class="small"><?= $e($log['status_name']) ?></strong>
                                    <?php if (!empty($log['description'])): ?>
                                        <div class="small text-muted"><?= $e($log['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted text-nowrap"><?= persianDate((string)$log['created_at']) ?> <?= date('H:i', strtotime($log['created_at'])) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- مجوزهای صادرشده -->
        <?php if (!empty($lics)): ?>
        <div class="card mb-4" style="border-color:var(--persian-green)">
            <div class="card-header fw-bold" style="color:var(--persian-green)"><?= icon('award', 'me-1', 18) ?> مجوزهای صادرشده</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr><th>کد رهگیری</th><th>خدمت</th><th>وضعیت مجوز</th><th>تاریخ ثبت</th><th>اقدام</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lics as $lic): ?>
                                <tr>
                                    <td><code dir="ltr"><?= $e($lic['tracking_code']) ?></code></td>
                                    <td class="small"><?= $e($lic['service_title'] ?? 'مجوز صنایع دستی') ?></td>
                                    <td><span class="badge bg-<?= $stateBadge[$lic['license_state'] ?? 'جاری'] ?? 'primary' ?>"><?= $e($lic['license_state'] ?? 'جاری') ?></span></td>
                                    <td class="small text-muted"><?= persianDate((string)$lic['created_at']) ?></td>
                                    <td><a href="<?= $base ?>tracking/status/<?= rawurlencode($lic['tracking_code']) ?>" class="btn btn-sm btn-outline-primary py-0"><?= icon('eye', '', 13) ?></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- پرداخت‌های من -->
        <div class="card mb-4">
            <div class="card-header fw-bold"><?= icon('money', 'me-1', 18) ?> پرداخت‌های من</div>
            <div class="card-body">
                <?php if (empty($pays)): ?>
                    <p class="text-muted text-center py-3 mb-0">هنوز پرداختی ثبت نشده است.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr><th>مبلغ</th><th>کد رهگیری</th><th>کانال</th><th>وضعیت</th><th>تاریخ</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pays as $pay): ?>
                                    <tr>
                                        <td><strong><?= number_format((int)$pay['amount']) ?></strong> ت</td>
                                        <td><code dir="ltr"><?= $e($pay['tracking_code'] ?? '') ?></code></td>
                                        <td class="small"><?= $e($pay['channel_title'] ?? '—') ?></td>
                                        <td><span class="badge bg-<?= $pay['status'] === 'تأیید شده' ? 'success' : ($pay['status'] === 'رد شده' ? 'danger' : 'warning text-dark') ?>"><?= $e($pay['status']) ?></span></td>
                                        <td class="small text-muted"><?= persianDate((string)$pay['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== ستون راست: پروفایل کاربر ===== -->
    <div class="col-lg-4">
        <div class="profile-card mb-4 sticky-top" style="top: 1rem;">
            <div class="profile-header">
                <div style="width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:700;margin:0 auto .5rem;">
                    <?= $e(mb_substr($u['first_name'] ?? '؟', 0, 1)) ?>
                </div>
                <h5 class="fw-bold mb-0"><?= $e($u['first_name'] . ' ' . $u['last_name']) ?></h5>
                <small class="opacity-75"><?= $e($roleLabels[$role] ?? $u['mobile'] ?? '') ?></small>
            </div>
            <div class="profile-body">
                <div class="profile-item">
                    <span class="lbl">شماره موبایل</span>
                    <span class="val" dir="ltr"><?= $e($u['mobile'] ?? '') ?></span>
                </div>
                <div class="profile-item">
                    <span class="lbl">کد ملی</span>
                    <span class="val" dir="ltr"><?= $e($u['national_code'] ?? '') ?></span>
                </div>
                <div class="profile-item">
                    <span class="lbl">تاریخ ثبت‌نام</span>
                    <span class="val"><?= persianDate((string)($u['created_at'] ?? '')) ?></span>
                </div>
                <div class="profile-item">
                    <span class="lbl">آخرین ورود</span>
                    <span class="val"><?= !empty($u['last_login_at']) ? persianDate((string)$u['last_login_at']) : '—' ?></span>
                </div>
                <div class="profile-item">
                    <span class="lbl">کل درخواست‌ها</span>
                    <span class="val"><?= (int)$s['total_apps'] ?></span>
                </div>
                <div class="profile-item">
                    <span class="lbl">مجوزهای صادرشده</span>
                    <span class="val"><?= (int)$s['issued_apps'] ?></span>
                </div>
                <div class="profile-item">
                    <span class="lbl">مجموع پرداخت‌ها</span>
                    <span class="val"><?= number_format($s['total_paid']) ?> ت</span>
                </div>

                <hr class="my-3">
                <div class="d-grid gap-2">
                    <a href="<?= $base ?>services" class="btn btn-primary">
                        <?= icon('shopping-cart', '', 16) ?> درخواست جدید
                    </a>
                    <a href="<?= $base ?>tracking" class="btn btn-outline-primary">
                        <?= icon('search', '', 16) ?> پیگیری با کد رهگیری
                    </a>
                    <a href="<?= $base ?>guide" class="btn btn-outline-secondary">
                        <?= icon('book', '', 16) ?> راهنمای جامع
                    </a>
                    <a href="<?= $base ?>logout" class="btn btn-outline-danger">
                        <?= icon('logout', '', 16) ?> خروج از حساب
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
