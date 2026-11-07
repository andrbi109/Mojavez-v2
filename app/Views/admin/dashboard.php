<?php
/**
 * داشبورد مدیریت — بازطراحی کامل v0.2.0
 * کارت‌های آماری با آیکون، هشدار در آستانهٔ لغو، توزیع وضعیت‌ها، رویدادهای اخیر و اقدام‌های سریع
 */
$base = rtrim(BASE_URL, '/') . '/';
$issued = (int)($statusCounts['صدور مجوز'] ?? 0);
$maxCount = max(1, ...array_values($statusCounts));
?>

<!-- ===== ردیف کارت‌های آماری ===== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <a class="stat-card" href="<?= $base ?>admin/applications">
            <div class="stat-icon navy"><?= icon('folder', '', 24) ?></div>
            <div>
                <div class="stat-value"><?= (int)$total ?></div>
                <div class="stat-label">کل پرونده‌ها</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <a class="stat-card" href="<?= $base ?>admin/my-tasks">
            <div class="stat-icon turquoise"><?= icon('clock', '', 24) ?></div>
            <div>
                <div class="stat-value"><?= (int)$pending ?></div>
                <div class="stat-label">در انتظار رسیدگی</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <a class="stat-card" href="<?= $base ?>admin/applications?status=<?= rawurlencode('صدور مجوز') ?>">
            <div class="stat-icon green"><?= icon('check-circle', '', 24) ?></div>
            <div>
                <div class="stat-value"><?= $issued ?></div>
                <div class="stat-label">مجوز صادرشده</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <a class="stat-card" href="<?= $base ?>admin/applications?status=<?= rawurlencode('لغو') ?>">
            <div class="stat-icon red"><?= icon('x-circle', '', 24) ?></div>
            <div>
                <div class="stat-value"><?= (int)$expired ?></div>
                <div class="stat-label">لغو / منقضی‌شده</div>
            </div>
        </a>
    </div>
</div>

<!-- ===== ردیف دوم: اولویت‌دارها + کد پستی ===== -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <a class="stat-card" style="border-right:4px solid var(--saffron);" href="<?= $base ?>admin/applications">
            <div class="stat-icon gold"><?= icon('star', '', 24) ?></div>
            <div>
                <div class="stat-value"><?= (int)($priorityCount ?? 0) ?></div>
                <div class="stat-label">پروندهٔ اولویت‌دار باز (ثبت‌نام بیش از <?= PRIORITY_DAYS ?> روز)</div>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a class="stat-card" style="border-right:4px solid var(--turquoise);" href="<?= $base ?>admin/postal-requests">
            <div class="stat-icon turquoise"><?= icon('mail', '', 24) ?></div>
            <div>
                <div class="stat-value"><?= (int)($pendingPostal ?? 0) ?></div>
                <div class="stat-label">درخواست تغییر کد پستی در انتظار بررسی</div>
            </div>
        </a>
    </div>
</div>

<!-- ردیف مالی (فاز ۴) -->
<?php if (!empty($payStats)): ?>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <a class="stat-card" style="border-right:4px solid var(--persian-green);" href="<?= $base ?>admin/payments">
            <div class="stat-icon green"><?= icon('money', '', 24) ?></div>
            <div>
                <div class="stat-value" style="font-size:1.05rem"><?= number_format((int)$payStats['approvedMonth']) ?> <small class="stat-label">تومان</small></div>
                <div class="stat-label">درآمد تأییدشدهٔ ماه شمسی جاری</div>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a class="stat-card" style="border-right:4px solid var(--saffron);" href="<?= $base ?>admin/payments?status=<?= rawurlencode('در انتظار بررسی') ?>">
            <div class="stat-icon gold"><?= icon('clock', '', 24) ?></div>
            <div>
                <div class="stat-value"><?= (int)$payStats['pendingCount'] ?></div>
                <div class="stat-label">رسید پرداخت در انتظار بررسی</div>
            </div>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- ===== هشدار در آستانهٔ لغو ===== -->
<?php if (!empty($nearExpiry)): ?>
<div class="card mb-4" style="border-right:4px solid var(--terracotta);">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><?= icon('alert', '', 18) ?> در آستانهٔ لغو خودکار</span>
        <small class="text-muted">بیش از <?= (int)($warnDays ?? 10) ?> روز از «ثبت درخواست صدور» گذشته</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>کد رهگیری</th><th>متقاضی</th><th>وضعیت</th><th class="d-none d-md-table-cell">تاریخ ثبت درخواست صدور</th><th>اقدام</th></tr></thead>
                <tbody>
                <?php foreach ($nearExpiry as $n): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($n['tracking_code']) ?></code></td>
                        <td class="text-nowrap"><?= htmlspecialchars($n['first_name'] . ' ' . $n['last_name']) ?></td>
                        <td><span class="badge bg-warning"><?= htmlspecialchars($n['current_status']) ?></span></td>
                        <td class="d-none d-md-table-cell small text-muted"><?= persianDate((string)$n['submit_date']) ?></td>
                        <td><a href="<?= $base ?>admin/view/<?= (int)$n['id'] ?>" class="btn btn-sm btn-outline-primary py-0"><?= icon('eye', '', 14) ?> رسیدگی</a></td>
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
            <div class="card-header"><?= icon('chart', '', 18) ?> توزیع پرونده‌ها بر اساس وضعیت</div>
            <div class="card-body">
                <?php foreach ($statusCounts as $status => $count): ?>
                    <?php $pct = round((int)$count * 100 / $maxCount); ?>
                    <div class="dist-row">
                        <a class="dist-label text-decoration-none" href="<?= $base ?>admin/applications?status=<?= rawurlencode($status) ?>" title="مشاهده فهرست"><?= htmlspecialchars($status) ?></a>
                        <div class="dist-track"><div class="dist-fill" style="width: <?= max(2, $pct) ?>%;"></div></div>
                        <span class="dist-count"><?= (int)$count ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ===== اقدام‌های سریع ===== -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><?= icon('send', '', 18) ?> دسترسی سریع</div>
            <div class="card-body d-grid gap-2">
                <a href="<?= $base ?>admin/register" class="btn btn-primary text-start"><?= icon('plus', '', 16) ?> ثبت‌نام دستی متقاضی جدید</a>
                <a href="<?= $base ?>admin/postal-requests" class="btn btn-outline-primary text-start"><?= icon('mail', '', 16) ?> بررسی درخواست‌های کد پستی <?= ($pendingPostal ?? 0) > 0 ? '<span class="badge bg-warning text-dark float-start">' . (int)$pendingPostal . '</span>' : '' ?></a>
                <a href="<?= $base ?>admin/form-fields" class="btn btn-outline-primary text-start"><?= icon('form', '', 16) ?> مدیریت فرم ثبت‌نام (افزودن/حذف فیلد)</a>
                <a href="<?= $base ?>admin/settings" class="btn btn-outline-primary text-start"><?= icon('settings', '', 16) ?> تنظیمات سامانه و صفحهٔ دربارهٔ ما</a>
                <a href="<?= $base ?>admin/output/general" class="btn btn-outline-primary text-start"><?= icon('download', '', 16) ?> دریافت خروجی گزارش</a>
                <a href="<?= $base ?>admin/users" class="btn btn-outline-primary text-start"><?= icon('users', '', 16) ?> مدیریت کاربران و نقش‌ها</a>
            </div>
        </div>
    </div>
</div>

<!-- ===== آخرین رویدادها ===== -->
<?php if (!empty($recentLogs)): ?>
<div class="card mt-4">
    <div class="card-header"><?= icon('clock', '', 18) ?> آخرین رویدادهای سامانه</div>
    <ul class="list-group list-group-flush">
        <?php foreach ($recentLogs as $log): ?>
        <li class="list-group-item d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
                <strong><?= htmlspecialchars($log['status_name']) ?></strong>
                <span class="text-muted small">— <?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?></span>
                <code class="small ms-1"><?= htmlspecialchars($log['tracking_code']) ?></code>
                <?php if (!empty($log['description'])): ?>
                    <div class="small text-muted"><?= htmlspecialchars($log['description']) ?></div>
                <?php endif; ?>
            </div>
            <div class="text-nowrap">
                <small class="text-muted"><?= persianDate((string)$log['created_at']) ?></small>
                <a href="<?= $base ?>admin/view/<?= (int)$log['applicant_id'] ?>" class="btn btn-sm btn-outline-primary py-0 ms-1">مشاهده</a>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- یادآور سازوکارهای خودکار -->
<div class="alert mt-4 mb-0 small" style="background:var(--turquoise-light); color:var(--turquoise-dark); border:none;">
    💡 <strong>سازوکار خودکار:</strong> لغو درخواست‌های بیش از <?= AUTO_CANCEL_DAYS ?> روز و پرچم ⭐ برای
    ثبت‌نام‌های بیش از <?= PRIORITY_DAYS ?> روز، در هر بازدید از این صفحه آنی اجرا می‌شود (بدون cron).
    اجرای دستی: <code><?= $base ?>check-cancel</code>
</div>
