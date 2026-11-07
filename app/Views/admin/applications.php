<?php
/**
 * نمای فهرست درخواست‌ها (همه درخواست‌ها / وظایف من)
 * متغیرها: $applicants, $filters, $page?, $totalPages?, $total?, $title, $isTasks?
 */
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$total = $total ?? count($applicants);
$isTasks = ($currentPage ?? '') === 'tasks';
// ساختQueryString پایدار برای لینک‌های صفحه‌بندی (با حفظ فیلترها)
$qs = function (array $extra) use ($filters) {
    return http_build_query(array_merge(array_filter($filters), $extra));
};
// رنگ نشان وضعیت‌ها — از مرجع مرکزی
$statusMeta = (require CONFIG_PATH . '/statuses.php')['statuses'];
$statusColor = fn(string $st): string => $statusMeta[$st]['color'] ?? 'secondary';
?>
<!-- کارت‌های خلاصه -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="fs-4 fw-bold"><?= $total ?></div>
            <small class="text-muted">کل نتایج فیلتر</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="fs-4 fw-bold text-warning"><?= count(array_filter($applicants, fn($a) => (int)$a['priority'] === 1)) ?></div>
            <small class="text-muted">اولویت‌دار در این صفحه</small>
        </div>
    </div>
</div>

<!-- فیلترها — اکشن اصلاح شد: پیش‌تر به داشبورد می‌رفت! -->
<form method="get" action="<?= BASE_URL ?>admin/<?= $isTasks ? 'my-tasks' : 'applications' ?>" class="card p-3 mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">کد رهگیری</label>
            <input type="text" class="form-control form-control-sm" name="tracking_code" value="<?= htmlspecialchars($filters['tracking_code']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">نام</label>
            <input type="text" class="form-control form-control-sm" name="name" value="<?= htmlspecialchars($filters['name']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">کد ملی</label>
            <input type="text" class="form-control form-control-sm" name="national_code" inputmode="numeric" value="<?= htmlspecialchars($filters['national_code']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">موبایل</label>
            <input type="text" class="form-control form-control-sm" name="mobile" inputmode="numeric" value="<?= htmlspecialchars($filters['mobile']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">استان</label>
            <input type="text" class="form-control form-control-sm" name="province" value="<?= htmlspecialchars($filters['province']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">شهر</label>
            <input type="text" class="form-control form-control-sm" name="city" value="<?= htmlspecialchars($filters['city'] ?? '') ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">روستا</label>
            <input type="text" class="form-control form-control-sm" name="village" value="<?= htmlspecialchars($filters['village'] ?? '') ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">عنوان مجوز</label>
            <input type="text" class="form-control form-control-sm" name="license_title" value="<?= htmlspecialchars($filters['license_title'] ?? '') ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">وضعیت</label>
            <select class="form-select form-select-sm" name="status">
                <option value="">همه</option>
                <?php foreach (array_keys($statusMeta) as $st): ?>
                    <option value="<?= $st ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">🔍 اعمال فیلتر</button>
            <a href="<?= BASE_URL ?>admin/<?= $isTasks ? 'my-tasks' : 'applications' ?>" class="btn btn-outline-secondary btn-sm">پاک‌سازی</a>
        </div>
    </div>
</form>

<?php if (!$isTasks): ?>
<!-- دکمه‌های خروجی گزارش (با حفظ فیلترهای جاری) -->
<div class="mb-3 d-flex flex-wrap gap-2">
    <a href="<?= BASE_URL ?>admin/report/csv?<?= $qs([]) ?>" class="btn btn-success btn-sm">📗 خروجی Excel/CSV</a>
    <a href="<?= BASE_URL ?>admin/report/pdf?<?= $qs([]) ?>" target="_blank" class="btn btn-info btn-sm">📕 خروجی چاپی/PDF</a>
</div>
<?php endif; ?>

<!-- جدول — در موبایل به‌صورت افقی پیمایش می‌شود -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>اولویت</th>
                        <th>کد رهگیری</th>
                        <th>نام متقاضی</th>
                        <th class="d-none d-md-table-cell">رشته</th>
                        <th class="d-none d-md-table-cell">استان/شهر</th>
                        <th>وضعیت</th>
                        <th class="d-none d-lg-table-cell">تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($applicants as $app): ?>
                    <tr class="<?= $app['priority'] ? 'table-warning' : '' ?>">
                        <td><?= $app['priority'] ? '<span class="priority-badge" title="ثبت‌نام بیش از ۱۲ روز طول کشیده">⭐</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td><code><?= htmlspecialchars($app['tracking_code']) ?></code></td>
                        <td class="text-nowrap"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($app['license_title'] ?? '—') ?></td>
                        <td class="d-none d-md-table-cell"><?= htmlspecialchars(trim(($app['province'] ?? '') . ' / ' . ($app['city'] ?? ''), ' /')) ?></td>
                        <td><span class="badge bg-<?= $statusColor($app['current_status']) ?>"><?= htmlspecialchars($app['current_status']) ?></span></td>
                        <td class="d-none d-lg-table-cell"><?= persianDate((string)($app['registration_completed_at'] ?? '')) ?></td>
                        <td class="text-nowrap">
                            <a href="<?= BASE_URL ?>admin/view/<?= (int)$app['id'] ?>" class="btn btn-sm btn-outline-primary" title="مشاهده جزئیات">👁️</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($applicants)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">هیچ درخواستی یافت نشد.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- صفحه‌بندی -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3" aria-label="صفحه‌بندی">
    <ul class="pagination pagination-sm justify-content-center flex-wrap">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $qs(['page' => $page - 1]) ?>">قبلی</a>
        </li>
        <?php
        $from = max(1, $page - 2);
        $to = min($totalPages, $page + 2);
        for ($i = $from; $i <= $to; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= $qs(['page' => $i]) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $qs(['page' => $page + 1]) ?>">بعدی</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
