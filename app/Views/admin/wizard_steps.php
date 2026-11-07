<?php
/**
 * مدیریت مراحل ثبت‌نام (فاز ۱ نقشهٔ راه)
 * افزودن مرحلهٔ سفارشی، ترتیب، فعال/غیرفعال و حذف
 */
$base = rtrim(BASE_URL, '/') . '/';
$fd = $formData ?: ($editing ?: []);
$err = $errors ?? [];
?>

<div class="card p-3 p-md-4 mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><?= icon('task', 'me-1') ?> مدیریت مراحل ثبت‌نام</h4>
            <p class="text-muted small mb-0">
                مراحل ویزارد را بچینید: مرحلهٔ سفارشی بسازید، جابه‌جا یا غیرفعال کنید.
                مرحلهٔ «بارگذاری مدارک» همیشه <strong>آخرین</strong> مرحله و فعال می‌ماند.
            </p>
        </div>
        <a href="<?= $base ?>admin/form-fields" class="btn btn-outline-primary btn-sm"><?= icon('form', '', 14) ?> مدیریت فیلدها</a>
    </div>
    <?php if (!empty($message)): ?>
        <div class="alert alert-info py-2 mt-3 mb-0"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- فهرست مراحل -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">مراحل فعال ویزارد (به ترتیب نمایش)</div>
            <div class="card-body">
                <?php foreach ($steps as $i => $st): ?>
                    <?php $isSys = (int)$st['is_system'] === 1; $isDocs = $st['view_key'] === 'step4'; ?>
                    <div class="field-row <?= (int)$st['is_active'] ? '' : 'inactive' ?>">
                        <!-- ترتیب -->
                        <div class="d-flex flex-column gap-1">
                            <form method="post" action="<?= $base ?>admin/wizard-steps/move">
                                <?= Session::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int)$st['id'] ?>">
                                <input type="hidden" name="direction" value="up">
                                <button class="btn btn-sm btn-outline-secondary py-0 px-1" <?= ($i === 0 || $isDocs) ? 'disabled' : '' ?>>▲</button>
                            </form>
                            <form method="post" action="<?= $base ?>admin/wizard-steps/move">
                                <?= Session::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int)$st['id'] ?>">
                                <input type="hidden" name="direction" value="down">
                                <button class="btn btn-sm btn-outline-secondary py-0 px-1" <?= ($i === count($steps) - 1 || $isDocs) ? 'disabled' : '' ?>>▼</button>
                            </form>
                        </div>

                        <div class="flex-grow-1" style="min-width:170px">
                            <strong><?= icon($st['icon'] ?: 'form', 'me-1', 16) ?> <?= htmlspecialchars($st['title']) ?></strong>
                            <?php if ($isSys): ?><span class="system-badge">سیستمی</span><?php endif; ?>
                            <?php if ($isDocs): ?><span class="field-type-badge">همیشه آخرین مرحله</span><?php endif; ?>
                            <?php if (!(int)$st['is_active']): ?><span class="badge bg-secondary">غیرفعال</span><?php endif; ?>
                            <div class="small text-muted">
                                <?= (int)($counts[(int)$st['id']] ?? 0) ?> فیلد
                                <?php if (!empty($st['description'])): ?> — <?= htmlspecialchars($st['description']) ?><?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex gap-1">
                            <a href="<?= $base ?>admin/wizard-steps?edit=<?= (int)$st['id'] ?>#stepForm" class="btn btn-sm btn-outline-primary py-0"><?= icon('edit', '', 13) ?></a>
                            <?php if (!$isDocs): ?>
                            <form method="post" action="<?= $base ?>admin/wizard-steps/toggle/<?= (int)$st['id'] ?>"
                                  onsubmit="return confirm('<?= (int)$st['is_active'] ? 'مرحله از ویزارد حذف (غیرفعال) شود؟' : 'مرحله فعال شود؟' ?>');">
                                <?= Session::csrfField() ?>
                                <button class="btn btn-sm <?= (int)$st['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?> py-0">
                                    <?= (int)$st['is_active'] ? icon('x', '', 13) : icon('check', '', 13) ?>
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if (!$isSys): ?>
                            <form method="post" action="<?= $base ?>admin/wizard-steps/delete"
                                  onsubmit="return confirm('مرحلهٔ «<?= htmlspecialchars(addslashes($st['title'])) ?>» برای همیشه حذف شود؟');">
                                <?= Session::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int)$st['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger py-0"><?= icon('trash', '', 13) ?></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- فرم ساخت/ویرایش مرحله -->
    <div class="col-lg-5">
        <div class="card" id="stepForm">
            <div class="card-header"><?= $editing ? '✏️ ویرایش مرحله' : '➕ مرحلهٔ جدید' ?></div>
            <div class="card-body">
                <form method="post" action="<?= $base ?>admin/wizard-steps/save">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label class="form-label">عنوان مرحله <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($err['title']) ? 'is-invalid' : '' ?>"
                               name="title" maxlength="100" required
                               value="<?= htmlspecialchars($fd['title'] ?? '') ?>" placeholder="مثلاً: سوابق بیمه و تسهیلات">
                        <?php if (!empty($err['title'])): ?><div class="invalid-feedback"><?= implode('<br>', $err['title']) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">توضیح کوتاه (اختیاری)</label>
                        <input type="text" class="form-control" name="description" maxlength="255"
                               value="<?= htmlspecialchars($fd['description'] ?? '') ?>">
                    </div>
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label">آیکون</label>
                            <select class="form-select" name="icon">
                                <option value="">بدون آیکون</option>
                                <?php foreach ($icons as $ic): ?>
                                    <option value="<?= $ic ?>" <?= ($fd['icon'] ?? '') === $ic ? 'selected' : '' ?>><?= $ic ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label">ترتیب</label>
                            <input type="number" class="form-control" name="sort_order" value="<?= (int)($fd['sort_order'] ?? 50) ?>" <?= ($editing['view_key'] ?? '') === 'step4' ? 'readonly' : '' ?>>
                            <div class="form-text">کمتر از ۱۰۰۰ → پیش از مرحلهٔ مدارک</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success mt-3 w-100"><?= icon('save', '', 15) ?> <?= $editing ? 'ذخیرهٔ تغییرات' : 'ساخت مرحله' ?></button>
                    <?php if ($editing): ?><a href="<?= $base ?>admin/wizard-steps" class="btn btn-outline-secondary mt-2 w-100">انصراف از ویرایش</a><?php endif; ?>
                </form>
                <div class="alert alert-light border small mt-3 mb-0">
                    💡 پس از ساخت مرحله، در «مدیریت فرم ثبت‌نام» فیلدهای آن مرحله را اضافه کنید.
                    مرحلهٔ بدون فیلد فعال، در ویزارد صفحهٔ خالی نشان می‌دهد — یا آن را غیرفعال نگه دارید.
                </div>
            </div>
        </div>
    </div>
</div>
