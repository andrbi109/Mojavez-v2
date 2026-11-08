<?php
/**
 * داشبورد مدیریت — بازطراحی‌شده با کارت‌های مدرن و رابط کاربری زیباتر
 * -----------------------------------------------------------------------------
 * ویژگی‌ها:
 *  - کارت‌های آماری با گرادیان رنگی و آیکون‌های بزرگ
 *  - نمایش سریع درآمد ماه جاری با نمودار شاخص
 *  - هشدار در آستانهٔ لغو با طراحی برجسته
 *  - توزیع وضعیت‌ها با نوار پیشرفت متحرک
 *  - آخرین رویدادها با تایم‌لاین
 *  - اقدام‌های سریع با کارت‌های تعاملی
 *  - پشتیبانی کامل از تم روز/شب
 */
$base = rtrim(BASE_URL, '/') . '/';
$issued = (int)($statusCounts['صدور مجوز'] ?? 0);
$maxCount = max(1, ...array_values($statusCounts));
$successRate = $total > 0 ? round(($issued / $total) * 100) : 0;
?>

<style>
/* استایل اختصاصی داشبورد */
.dash-hero {
    background: linear-gradient(135deg, var(--navy) 0%, #1B3A5C 50%, #0E7490 100%);
    color: #fff;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 32px rgba(26, 42, 79, 0.3);
    position: relative;
    overflow: hidden;
}
.dash-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    inset-inline-end: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(ellipse, rgba(244, 163, 0, 0.12) 0%, transparent 70%);
    pointer-events: none;
}
.dash-hero::after {
    content: '';
    position: absolute;
    bottom: -30%;
    inset-inline-start: -10%;
    width: 50%;
    height: 150%;
    background: radial-gradient(ellipse, rgba(0, 180, 216, 0.15) 0%, transparent 70%);
    pointer-events: none;
}
.dash-hero h1, .dash-hero h2, .dash-hero h3, .dash-hero h4, .dash-hero h5, .dash-hero h6 {
    color: #fff !important;
}
.dash-hero .display-num {
    font-size: 2.8rem;
    font-weight: 800;
    line-height: 1;
    margin: 0;
    color: #fff;
    text-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.dash-hero .display-num small {
    font-size: 1rem;
    font-weight: 600;
    opacity: 0.85;
    color: var(--saffron);
}
.dash-hero .display-label {
    font-size: 0.8rem;
    opacity: 0.75;
    margin: 0;
    color: #B9C4CF;
}

/* کارت‌های شاخص */
.kpi-card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, rgba(0,0,0,0.06));
    border-radius: 16px;
    padding: 1.5rem;
    height: 100%;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    position: relative;
    overflow: hidden;
}
.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.10);
    text-decoration: none;
    color: inherit;
}
.kpi-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,.12);
}
.kpi-icon.navy { background: linear-gradient(135deg, var(--navy), #2C3E66); }
.kpi-icon.turquoise { background: linear-gradient(135deg, var(--turquoise), var(--turquoise-dark)); }
.kpi-icon.green { background: linear-gradient(135deg, #10B981, #059669); }
.kpi-icon.red { background: linear-gradient(135deg, #EF5350, #C62828); }
.kpi-icon.gold { background: linear-gradient(135deg, var(--saffron), #D97706); }
.kpi-icon.purple { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }

.kpi-value {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1;
    color: var(--ink, #1A2A4F);
}
.kpi-label {
    font-size: 0.85rem;
    color: var(--muted, #6B7280);
    margin: 0;
}
.kpi-trend {
    font-size: 0.72rem;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-weight: 600;
}
.kpi-trend.up { background: rgba(16, 185, 129, 0.15); color: #059669; }
.kpi-trend.down { background: rgba(239, 68, 68, 0.15); color: #DC2626; }
.kpi-trend.neutral { background: rgba(107, 114, 128, 0.15); color: #6B7280; }

/* نوار توزیع */
.dist-bar-track {
    background: var(--track-bg, rgba(0,0,0,0.05));
    border-radius: 999px;
    height: 8px;
    overflow: hidden;
    flex: 1;
    margin-inline: 0.5rem;
}
.dist-bar-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--turquoise), var(--turquoise-dark));
    transition: width 0.5s ease;
}

/* تایم‌لاین */
.timeline {
    list-style: none;
    padding: 0;
    margin: 0;
    position: relative;
}
.timeline::before {
    content: '';
    position: absolute;
    inset-inline-start: 14px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--border-color, rgba(0,0,0,0.08));
}
.timeline-item {
    position: relative;
    padding: 0.5rem 0 0.5rem 2.5rem;
    margin-inline-start: 0;
}
.timeline-item::before {
    content: '';
    position: absolute;
    inset-inline-start: 8px;
    top: 0.85rem;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--turquoise);
    border: 3px solid var(--card-bg, #fff);
    box-shadow: 0 0 0 2px var(--turquoise);
}

/* هشدار */
.alert-urgent {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.08), rgba(239, 68, 68, 0.02));
    border: 1px solid rgba(239, 68, 68, 0.25);
    border-radius: 16px;
}
.alert-urgent .card-header {
    background: transparent;
    border-bottom: 1px solid rgba(239, 68, 68, 0.15);
}

/* کارت اقدام سریع */
.quick-action {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, rgba(0,0,0,0.06));
    border-radius: 14px;
    padding: 1rem;
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.2s ease;
}
.quick-action:hover {
    transform: translateX(-4px);
    border-color: var(--turquoise);
    background: var(--turquoise-light);
    text-decoration: none;
    color: inherit;
}
.quick-action-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--turquoise-light);
    color: var(--turquoise-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.quick-action-text {
    flex: 1;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--ink, #1A2A4F);
}
.quick-action-badge {
    background: var(--saffron);
    color: #fff;
    border-radius: 999px;
    padding: 0.1rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 700;
}

/* پشتیبانی از تم شب */
[data-bs-theme="dark"] .kpi-value { color: #fff; }
[data-bs-theme="dark"] .kpi-label { color: #94A3B8; }
[data-bs-theme="dark"] .kpi-card,
[data-bs-theme="dark"] .quick-action {
    background: #1E293B;
    border-color: rgba(255,255,255,0.08);
}
[data-bs-theme="dark"] .quick-action-icon {
    background: rgba(0, 180, 216, 0.15);
    color: var(--turquoise);
}
[data-bs-theme="dark"] .quick-action-text { color: #E2E8F0; }
[data-bs-theme="dark"] .dist-bar-track { background: rgba(255,255,255,0.08); }
[data-bs-theme="dark"] .timeline::before { background: rgba(255,255,255,0.08); }
[data-bs-theme="dark"] .timeline-item::before { box-shadow: 0 0 0 2px #1E293B; }

/* چاپ */
@media print {
    .dash-hero { background: #fff !important; color: #000 !important; border: 1px solid #000; }
    .kpi-card, .quick-action { box-shadow: none !important; }
}
</style>

<!-- ===== بخش هیرو: خلاصهٔ کلی ===== -->
<div class="dash-hero">
    <div class="row align-items-center g-3">
        <div class="col-md-8">
            <div class="mb-2 display-label">به سامانهٔ مدیریت مجوزهای صنایع دستی خوش آمدید 👋</div>
            <div class="row g-4">
                <div class="col-6 col-md-3">
                    <p class="display-num"><?= (int)$total ?></p>
                    <p class="display-label mb-0">کل پرونده‌ها</p>
                </div>
                <div class="col-6 col-md-3">
                    <p class="display-num"><?= (int)$pending ?></p>
                    <p class="display-label mb-0">در انتظار رسیدگی</p>
                </div>
                <div class="col-6 col-md-3">
                    <p class="display-num"><?= $issued ?></p>
                    <p class="display-label mb-0">مجوز صادرشده</p>
                </div>
                <div class="col-6 col-md-3">
                    <p class="display-num"><?= $successRate ?><small>٪</small></p>
                    <p class="display-label mb-0">نرخ موفقیت</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                <a href="<?= $base ?>admin/register" class="btn btn-light fw-bold">
                    <?= icon('plus', '', 16) ?> ثبت‌نام جدید
                </a>
                <a href="<?= $base ?>admin/applications" class="btn btn-outline-light">
                    <?= icon('folder', '', 16) ?> همهٔ پرونده‌ها
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ===== ردیف کارت‌های KPI ===== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <a class="kpi-card" href="<?= $base ?>admin/applications">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon navy"><?= icon('folder', '', 24) ?></div>
                <span class="kpi-trend neutral">کل</span>
            </div>
            <div>
                <div class="kpi-value"><?= (int)$total ?></div>
                <p class="kpi-label">کل پرونده‌ها</p>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a class="kpi-card" href="<?= $base ?>admin/my-tasks">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon turquoise"><?= icon('clock', '', 24) ?></div>
                <span class="kpi-trend up">فعال</span>
            </div>
            <div>
                <div class="kpi-value"><?= (int)$pending ?></div>
                <p class="kpi-label">در انتظار رسیدگی</p>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a class="kpi-card" href="<?= $base ?>admin/applications?status=<?= rawurlencode('صدور مجوز') ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon green"><?= icon('check-circle', '', 24) ?></div>
                <span class="kpi-trend up"><?= $successRate ?>٪</span>
            </div>
            <div>
                <div class="kpi-value"><?= $issued ?></div>
                <p class="kpi-label">مجوز صادرشده</p>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a class="kpi-card" href="<?= $base ?>admin/applications?status=<?= rawurlencode('لغو') ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div class="kpi-icon red"><?= icon('x-circle', '', 24) ?></div>
                <span class="kpi-trend down">منقضی</span>
            </div>
            <div>
                <div class="kpi-value"><?= (int)$expired ?></div>
                <p class="kpi-label">لغو / منقضی‌شده</p>
            </div>
        </a>
    </div>
</div>

<!-- ===== ردیف دوم: اولویت‌دارها + کد پستی + مالی ===== -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a class="kpi-card" href="<?= $base ?>admin/applications" style="border-inline-start: 4px solid var(--saffron);">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon gold"><?= icon('star', '', 24) ?></div>
                <div class="flex-grow-1">
                    <div class="kpi-value"><?= (int)($priorityCount ?? 0) ?></div>
                    <p class="kpi-label mb-0">پروندهٔ اولویت‌دار (ثبت‌نام &gt; <?= PRIORITY_DAYS ?> روز)</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a class="kpi-card" href="<?= $base ?>admin/postal-requests" style="border-inline-start: 4px solid var(--turquoise);">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon turquoise"><?= icon('mail', '', 24) ?></div>
                <div class="flex-grow-1">
                    <div class="kpi-value"><?= (int)($pendingPostal ?? 0) ?></div>
                    <p class="kpi-label mb-0">درخواست تغییر کد پستی</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a class="kpi-card" href="<?= $base ?>admin/payments" style="border-inline-start: 4px solid var(--persian-green);">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon green"><?= icon('money', '', 24) ?></div>
                <div class="flex-grow-1">
                    <div class="kpi-value" style="font-size:1.4rem"><?= !empty($payStats) ? number_format((int)$payStats['pendingCount']) : '0' ?></div>
                    <p class="kpi-label mb-0">رسید پرداخت در انتظار بررسی</p>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- ردیف مالی (درآمد) -->
<?php if (!empty($payStats) && (int)$payStats['approvedMonth'] > 0): ?>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="kpi-card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.02)); border-color: rgba(16, 185, 129, 0.2);">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon green"><?= icon('trending-up', '', 28) ?></div>
                <div class="flex-grow-1">
                    <div class="kpi-value text-success"><?= number_format((int)$payStats['approvedMonth']) ?></div>
                    <p class="kpi-label mb-0">تومان — درآمد تأییدشدهٔ ماه شمسی جاری</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <a class="kpi-card" href="<?= $base ?>admin/fee-tariff">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon purple"><?= icon('wallet', '', 28) ?></div>
                <div class="flex-grow-1">
                    <div class="kpi-value" style="font-size:1.4rem"><?= number_format((int)$payStats['approvedTotal']) ?></div>
                    <p class="kpi-label mb-0">تومان — مجموع کل درآمد تأییدشده</p>
                </div>
            </div>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- ===== هشدار در آستانهٔ لغو ===== -->
<?php if (!empty($nearExpiry)): ?>
<div class="card mb-4 alert-urgent">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="text-danger fw-bold"><?= icon('alert', 'me-1', 18) ?> در آستانهٔ لغو خودکار</span>
        <small class="text-muted">بیش از <?= (int)($warnDays ?? 10) ?> روز از «ثبت درخواست صدور» گذشته</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>کد رهگیری</th><th>متقاضی</th><th>وضعیت</th><th class="d-none d-md-table-cell">تاریخ ثبت</th><th>اقدام</th></tr></thead>
                <tbody>
                <?php foreach ($nearExpiry as $n): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($n['tracking_code']) ?></code></td>
                        <td class="text-nowrap"><?= htmlspecialchars($n['first_name'] . ' ' . $n['last_name']) ?></td>
                        <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($n['current_status']) ?></span></td>
                        <td class="d-none d-md-table-cell small text-muted"><?= persianDate((string)$n['submit_date']) ?></td>
                        <td><a href="<?= $base ?>admin/view/<?= (int)$n['id'] ?>" class="btn btn-sm btn-outline-danger py-0"><?= icon('eye', '', 14) ?> رسیدگی</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- ===== توزیع وضعیت‌ها ===== -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><?= icon('chart', 'me-1', 18) ?> توزیع پرونده‌ها بر اساس وضعیت</span>
                <small class="text-muted">حداکثر: <?= $maxCount ?></small>
            </div>
            <div class="card-body">
                <?php foreach ($statusCounts as $status => $count): ?>
                    <?php $pct = round((int)$count * 100 / $maxCount); ?>
                    <div class="d-flex align-items-center mb-2">
                        <a class="text-decoration-none text-body small fw-bold flex-shrink-0" style="width:140px" href="<?= $base ?>admin/applications?status=<?= rawurlencode($status) ?>" title="مشاهدهٔ فهرست"><?= htmlspecialchars($status) ?></a>
                        <div class="dist-bar-track">
                            <div class="dist-bar-fill" style="width: <?= max(2, $pct) ?>%;"></div>
                        </div>
                        <span class="badge bg-light text-dark ms-2" style="min-width:32px"><?= (int)$count ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ===== اقدام‌های سریع ===== -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header fw-bold"><?= icon('send', 'me-1', 18) ?> دسترسی سریع</div>
            <div class="card-body d-grid gap-2">
                <a href="<?= $base ?>admin/register" class="quick-action">
                    <span class="quick-action-icon"><?= icon('plus', '', 18) ?></span>
                    <span class="quick-action-text">ثبت‌نام دستی متقاضی جدید</span>
                </a>
                <a href="<?= $base ?>admin/postal-requests" class="quick-action">
                    <span class="quick-action-icon"><?= icon('mail', '', 18) ?></span>
                    <span class="quick-action-text">بررسی درخواست‌های کد پستی</span>
                    <?php if (($pendingPostal ?? 0) > 0): ?><span class="quick-action-badge"><?= (int)$pendingPostal ?></span><?php endif; ?>
                </a>
                <a href="<?= $base ?>admin/fee-tariff" class="quick-action">
                    <span class="quick-action-icon"><?= icon('wallet', '', 18) ?></span>
                    <span class="quick-action-text">نرخ‌نامه و تعرفه‌ها</span>
                </a>
                <a href="<?= $base ?>admin/form-fields" class="quick-action">
                    <span class="quick-action-icon"><?= icon('form', '', 18) ?></span>
                    <span class="quick-action-text">مدیریت فیلدهای فرم</span>
                </a>
                <a href="<?= $base ?>admin/settings" class="quick-action">
                    <span class="quick-action-icon"><?= icon('settings', '', 18) ?></span>
                    <span class="quick-action-text">تنظیمات سامانه</span>
                </a>
                <a href="<?= $base ?>admin/output/general" class="quick-action">
                    <span class="quick-action-icon"><?= icon('download', '', 18) ?></span>
                    <span class="quick-action-text">دریافت خروجی گزارش</span>
                </a>
                <a href="<?= $base ?>admin/users" class="quick-action">
                    <span class="quick-action-icon"><?= icon('users', '', 18) ?></span>
                    <span class="quick-action-text">مدیریت کاربران و نقش‌ها</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ===== مجوزهای منقضی‌شده یا در حال انقضا ===== -->
<div class="card mt-4">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,rgba(181,73,63,.08),rgba(181,73,63,.02)); border-color:rgba(181,73,63,.2)">
        <span><?= icon('alert-triangle','me-1',18) ?> مجوزهای منقضی‌شده یا در آستانه انقضا</span>
        <?php if (!empty($expiringLicenses)): ?>
            <span class="badge bg-danger"><?= count($expiringLicenses) ?> مورد</span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($expiringLicenses)): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>نام متقاضی</th>
                        <th>کد رهگیری</th>
                        <th>رشته مجوز</th>
                        <th>موبایل</th>
                        <th>تاریخ انقضا</th>
                        <th>وضعیت</th>
                        <th>اقدام</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiringLicenses as $lic):
                        $days = (int)$lic['days_remaining'];
                        $isExpired = $days < 0;
                        $isUrgent = $days >= 0 && $days <= 7;
                    ?>
                    <tr class="<?= $isExpired ? 'table-danger' : ($isUrgent ? 'table-warning' : '') ?>">
                        <td class="fw-bold"><?= htmlspecialchars($lic['first_name'] . ' ' . $lic['last_name']) ?></td>
                        <td><code dir="ltr"><?= htmlspecialchars($lic['tracking_code']) ?></code></td>
                        <td class="small"><?= htmlspecialchars($lic['license_title'] ?? '—') ?></td>
                        <td class="small"><code dir="ltr"><?= htmlspecialchars($lic['mobile']) ?></code></td>
                        <td class="small text-nowrap"><?= persianDate((string)$lic['license_valid_until']) ?></td>
                        <td>
                            <?php if ($isExpired): ?>
                                <span class="badge bg-danger">منقضی شده (<?= abs($days) ?> روز پیش)</span>
                            <?php elseif ($isUrgent): ?>
                                <span class="badge bg-warning text-dark"><?= $days ?> روز مانده</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark"><?= $days ?> روز مانده</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= $base ?>admin/view/<?= (int)$lic['id'] ?>" class="btn btn-sm btn-outline-primary py-0"><?= icon('eye','',14) ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4 text-muted">
            <?= icon('check-circle','',28) ?>
            <p class="mt-2 mb-0">در حال حاضر مجوز منقضی‌شده یا در آستانه انقضایی وجود ندارد.</p>
            <small class="text-muted">برای فعال شدن این بخش، در پروندهٔ متقاضیانِ دارای مجوز، «تاریخ انقضای مجوز» را تنظیم کنید.</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== آخرین رویدادها (تایم‌لاین) ===== -->
<?php if (!empty($recentLogs)): ?>
<div class="card mt-4">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span><?= icon('clock', 'me-1', 18) ?> آخرین رویدادهای سامانه</span>
        <a href="<?= $base ?>admin/applications" class="btn btn-sm btn-outline-primary">مشاهدهٔ همه</a>
    </div>
    <div class="card-body">
        <ul class="timeline">
            <?php foreach (array_slice($recentLogs, 0, 8) as $log): ?>
            <li class="timeline-item">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <strong><?= htmlspecialchars($log['status_name']) ?></strong>
                        <span class="text-muted small">— <?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?></span>
                        <code class="small ms-1"><?= htmlspecialchars($log['tracking_code']) ?></code>
                        <?php if (!empty($log['description'])): ?>
                            <div class="small text-muted mt-1"><?= htmlspecialchars($log['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-nowrap d-flex align-items-center gap-2">
                        <small class="text-muted"><?= persianDate((string)$log['created_at']) ?></small>
                        <a href="<?= $base ?>admin/view/<?= (int)$log['applicant_id'] ?>" class="btn btn-sm btn-outline-primary py-0"><?= icon('eye', '', 13) ?></a>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<!-- یادآور سازوکارهای خودکار -->
<div class="alert mt-4 mb-0 small d-flex align-items-center gap-2" style="background:var(--turquoise-light); color:var(--turquoise-dark); border:none; border-radius:14px;">
    <span style="font-size:1.4rem">💡</span>
    <div>
        <strong>سازوکار خودکار:</strong> لغو درخواست‌های بیش از <?= AUTO_CANCEL_DAYS ?> روز و پرچم ⭐ برای
        ثبت‌نام‌های بیش از <?= PRIORITY_DAYS ?> روز، در هر بازدید از این صفحه آنی اجرا می‌شود (بدون cron).
        اجرای دستی: <code><?= $base ?>check-cancel</code>
    </div>
</div>
