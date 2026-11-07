<?php
/**
 * مدیریت انواع خدمات (فاز ۹ نقشهٔ راه)
 */
$base = rtrim(BASE_URL, '/') . '/';
$fd = $formData ?: ($editing ?: []);
$err = $errors ?? [];
?>
<div class="card p-3 p-md-4 mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><?= icon('grid', 'me-1') ?> مدیریت انواع خدمات</h4>
            <p class="text-muted small mb-0">
                سرویس‌هایی که سامانه برای آن‌ها درخواست ثبت می‌گیرد (صنایع دستی، مشاغل خانگی، گردشگری، هنری…).
                هر سرویس می‌تواند مراحل و فیلدهای اختصاصی خود را داشته باشد.
            </p>
        </div>
        <span class="badge bg-info fs-6"><?= count($services) ?> سرویس</span>
    </div>
    <?php if (!empty($message)): ?>
        <div class="alert alert-info py-2 mt-3 mb-0"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">فهرست سرویس‌ها</div>
            <div class="card-body">
                <?php foreach ($services as $sv): ?>
                <div class="field-row <?= (int)$sv['is_active'] ? '' : 'inactive' ?>">
                    <div class="flex-grow-1" style="min-width:180px">
                        <strong><?= htmlspecialchars($sv['title']) ?></strong>
                        <?php if (!(int)$sv['is_active']): ?><span class="badge bg-secondary">غیرفعال</span><?php endif; ?>
                        <div class="small text-muted"><?= htmlspecialchars($sv['authority'] ?? '') ?></div>
                        <div class="small text-muted"><?= htmlspecialchars(mb_substr((string)($sv['description'] ?? ''), 0, 80)) ?></div>
                        <div class="field-key">پرونده‌ها: <?= (int)($counts[(int)$sv['id']] ?? 0) ?></div>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="<?= $base ?>admin/service-types?edit=<?= (int)$sv['id'] ?>#svcForm" class="btn btn-sm btn-outline-primary py-0"><?= icon('edit', '', 13) ?></a>
                        <form method="post" action="<?= $base ?>admin/service-types/delete"
                              onsubmit="return confirm('سرویس «<?= htmlspecialchars(addslashes($sv['title'])) ?>» حذف/غیرفعال شود؟');">
                            <?= Session::csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int)$sv['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger py-0"><?= icon('trash', '', 13) ?></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card" id="svcForm">
            <div class="card-header"><?= $editing ? '✏️ ویرایش سرویس' : '➕ سرویس جدید' ?></div>
            <div class="card-body">
                <form method="post" action="<?= $base ?>admin/service-types/save">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
                    <div class="mb-2">
                        <label class="form-label small mb-1">عنوان سرویس <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm <?= isset($err['title']) ? 'is-invalid' : '' ?>"
                               name="title" maxlength="120" required value="<?= htmlspecialchars($fd['title'] ?? '') ?>"
                               placeholder="مثلاً: مجوز اشتغال خانگی">
                        <?php if (!empty($err['title'])): ?><div class="invalid-feedback"><?= implode('<br>', $err['title']) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">مرجع صدور</label>
                        <input type="text" class="form-control form-control-sm" name="authority" maxlength="150" value="<?= htmlspecialchars($fd['authority'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">توضیحات</label>
                        <textarea class="form-control form-control-sm" name="description" rows="3" maxlength="1000"><?= htmlspecialchars($fd['description'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small mb-1">ترتیب</label>
                            <input type="number" class="form-control form-control-sm" name="sort_order" value="<?= (int)($fd['sort_order'] ?? 100) ?>">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="svcActive" name="is_active" value="1"
                                       <?= !isset($fd['is_active']) || (int)($fd['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label small fw-bold" for="svcActive">فعال</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success mt-2 w-100"><?= icon('save', '', 14) ?> ذخیرهٔ سرویس</button>
                    <?php if ($editing): ?><a href="<?= $base ?>admin/service-types" class="btn btn-sm btn-outline-secondary mt-2 w-100">انصراف از ویرایش</a><?php endif; ?>
                </form>
                <div class="alert alert-light border small mt-3 mb-0">
                    💡 مراحل و فیلدهای اختصاصی هر سرویس: در «مراحل ثبت‌نام» و «مدیریت فیلدهای فرم»
                    می‌توانید مرحله/فیلد را به یک سرویس خاص محدود کنید (در حال توسعهٔ کامل در فازهای بعدی نقشهٔ راه).
                </div>
            </div>
        </div>
    </div>
</div>
