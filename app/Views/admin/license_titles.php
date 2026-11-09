<?php
/**
 * مدیریت رشته‌های مجوز — فهرست گروه‌بندی‌شده + فرم افزودن/ویرایش
 * ورودی‌ها: $grouped (خدمت → دسته → [ردیف‌ها]), $flatRows, $categories, $services, $filterServiceId, $editing, $message, $errors, $formData
 */
$base = rtrim(BASE_URL, '/') . '/';
$fd = $formData ?: ($editing ?: []);
$err = $errors ?? [];
$services = $services ?? [];
$filterServiceId = (int)($filterServiceId ?? 0);

// محاسبهٔ تعداد کل رشته‌ها
$totalCount = 0;
foreach ($grouped as $serviceTitle => $byCat) {
    foreach ($byCat as $rows) {
        $totalCount += count($rows);
    }
}

// سرویس انتخاب‌شده برای فرم افزودن/ویرایش (پیش‌فرض = فیلتر فعلی)
$formServiceId = (int)($fd['service_type_id'] ?? $filterServiceId);
?>

<div class="card p-3 p-md-4 mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><?= icon('grid', 'me-1') ?> مدیریت رشته‌های مجوز</h4>
            <p class="text-muted small mb-0">
                فهرست رشته‌های قابل انتخاب در فرم ثبت‌نام — هر سرویس فهرست اختصاصی خود را دارد.
                افزودن، ویرایش، حذف و فعال/غیرفعال‌سازی.
            </p>
        </div>
        <span class="badge bg-info fs-6"><?= $totalCount ?> رشته</span>
    </div>
    <?php if (!empty($message)): ?>
        <div class="alert alert-info py-2 mt-3 mb-0"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
</div>

<!-- نوار فیلتر بر اساس سرویس -->
<div class="card p-3 mb-4">
    <form method="get" action="<?= $base ?>admin/license-titles" class="d-flex align-items-center gap-2 flex-wrap">
        <label class="form-label mb-0 fw-bold">فیلتر بر اساس سرویس:</label>
        <select name="service" class="form-select form-select-sm" style="max-width:300px" onchange="this.form.submit()">
            <option value="0">همهٔ سرویس‌ها</option>
            <?php foreach ($services as $sv): ?>
                <option value="<?= (int)$sv['id'] ?>" <?= $filterServiceId === (int)$sv['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sv['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($filterServiceId > 0): ?>
            <a href="<?= $base ?>admin/license-titles" class="btn btn-sm btn-outline-secondary">نمایش همه</a>
        <?php endif; ?>
    </form>
</div>

<!-- فرم افزودن/ویرایش -->
<div class="card p-3 p-md-4 mb-4" id="titleForm">
    <h5 class="fw-bold mb-3"><?= $editing ? '✏️ ویرایش رشته' : '➕ افزودن رشتهٔ جدید' ?></h5>
    <form method="post" action="<?= $base ?>admin/license-titles/save">
        <?= Session::csrfField() ?>
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">سرویس <span class="text-danger">*</span></label>
                <select class="form-select <?= isset($err['service_type_id']) ? 'is-invalid' : '' ?>"
                        name="service_type_id" required>
                    <option value="">انتخاب کنید...</option>
                    <?php foreach ($services as $sv): ?>
                        <option value="<?= (int)$sv['id'] ?>" <?= $formServiceId === (int)$sv['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sv['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($err['service_type_id'])): ?><div class="invalid-feedback"><?= implode('<br>', $err['service_type_id']) ?></div><?php endif; ?>
                <small class="text-muted">هر سرویس فهرست اختصاصی خود را دارد — رشته باید به یک سرویس وصل باشد.</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">دسته / گروه <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($err['category']) ? 'is-invalid' : '' ?>"
                       name="category" list="catList" maxlength="80" required
                       value="<?= htmlspecialchars($fd['category'] ?? '') ?>" placeholder="مثلاً: صنایع چوبی">
                <datalist id="catList">
                    <?php foreach ($categories as $c): ?><option value="<?= htmlspecialchars($c) ?>"></option><?php endforeach; ?>
                </datalist>
                <?php if (!empty($err['category'])): ?><div class="invalid-feedback"><?= implode('<br>', $err['category']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">عنوان رشته <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($err['title']) ? 'is-invalid' : '' ?>"
                       name="title" maxlength="120" required
                       value="<?= htmlspecialchars($fd['title'] ?? '') ?>" placeholder="مثلاً: معرق چوب">
                <?php if (!empty($err['title'])): ?><div class="invalid-feedback"><?= implode('<br>', $err['title']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-1">
                <label class="form-label">ترتیب</label>
                <input type="number" class="form-control" name="sort_order" value="<?= (int)($fd['sort_order'] ?? 100) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="isActive" name="is_active" value="1"
                           <?= !isset($fd['is_active']) || (int)$fd['is_active'] === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="isActive">فعال</label>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-success"><?= $editing ? '💾 ذخیرهٔ تغییرات' : '➕ افزودن رشته' ?></button>
            <?php if ($editing): ?><a href="<?= $base ?>admin/license-titles<?= $filterServiceId ? '?service=' . $filterServiceId : '' ?>" class="btn btn-outline-secondary">انصراف از ویرایش</a><?php endif; ?>
        </div>
    </form>
</div>

<!-- فهرست گروه‌بندی‌شده: سرویس → دسته → ردیف‌ها -->
<?php foreach ($grouped as $serviceTitle => $byCategory): ?>
<?php foreach ($byCategory as $category => $rows): ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>
            <span class="badge bg-primary ms-1"><?= htmlspecialchars($serviceTitle) ?></span>
            <?= icon('folder', 'me-1') ?>
            <?= htmlspecialchars($category) ?>
        </span>
        <span class="badge bg-secondary"><?= count($rows) ?></span>
    </div>
    <div class="card-body py-2">
        <?php foreach ($rows as $r): ?>
        <div class="field-row <?= (int)$r['is_active'] ? '' : 'inactive' ?>">
            <div class="flex-grow-1">
                <strong><?= htmlspecialchars($r['title']) ?></strong>
                <?php if (!(int)$r['is_active']): ?><span class="badge bg-secondary">غیرفعال</span><?php endif; ?>
                <span class="field-key">ترتیب: <?= (int)$r['sort_order'] ?></span>
            </div>
            <div class="d-flex gap-1">
                <a href="<?= $base ?>admin/license-titles?edit=<?= (int)$r['id'] ?>#titleForm" class="btn btn-sm btn-outline-primary">✏️</a>
                <form method="post" action="<?= $base ?>admin/license-titles/toggle/<?= (int)$r['id'] ?>">
                    <?= Session::csrfField() ?>
                    <button class="btn btn-sm <?= (int)$r['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                            title="<?= (int)$r['is_active'] ? 'غیرفعال‌سازی (حذف از فرم ثبت‌نام)' : 'فعال‌سازی' ?>">
                        <?= (int)$r['is_active'] ? '🚫' : '✔' ?>
                    </button>
                </form>
                <form method="post" action="<?= $base ?>admin/license-titles/delete"
                      onsubmit="return confirm('رشتهٔ «<?= htmlspecialchars(addslashes($r['title'])) ?>» حذف شود؟\n(اگر در پرونده‌ای استفاده شده باشد، فقط غیرفعال می‌شود)');">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" title="حذف">🗑</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endforeach; ?>

<?php if (empty($grouped)): ?>
<div class="card p-4 text-center text-muted">
    هیچ رشته‌ای برای نمایش وجود ندارد. برای افزودن رشته از فرم بالا استفاده کنید.
</div>
<?php endif; ?>
