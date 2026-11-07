<?php
/**
 * فرم ایجاد/ویرایش فیلد فرم ثبت‌نام
 * ورودی‌ها: $field (رکورد فعلی یا دادهٔ بازگشتی)، $isEdit، $isSystem
 */
$base = rtrim(BASE_URL, '/') . '/';
$field = $field ?? [];
$isSystem = $isSystem ?? false;
$err = $errors ?? [];
$val = $isSystem ? [] : FormField::parseRules($field['validation_json'] ?? null);
$stepTitles = [1 => 'مرحلهٔ ۱ — اطلاعات هویتی', 2 => 'مرحلهٔ ۲ — اطلاعات شغلی', 3 => 'مرحلهٔ ۳ — اطلاعات ملک', 4 => 'مرحلهٔ ۴ — مدارک'];
$showOptions = in_array($field['field_type'] ?? 'text', ['select', 'radio'], true);
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card p-3 p-md-4">
            <h4 class="fw-bold mb-1"><?= $isEdit ? '✏️ ویرایش فیلد' : '➕ افزودن فیلد جدید' ?></h4>
            <p class="text-muted small">
                این فیلد در فرم عمومی ثبت‌نام متقاضیان نمایش داده می‌شود.
                <?php if ($isSystem): ?>
                    <span class="system-badge">فیلد سیستمی — فقط بخش‌های محدود قابل تغییر است</span>
                <?php endif; ?>
            </p>

            <form method="post" action="<?= $base ?>admin/form-fields/save">
                <?= Session::csrfField() ?>
                <input type="hidden" name="id" value="<?= (int)($field['id'] ?? 0) ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">برچسب فیلد (فارسی) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($err['label']) ? 'is-invalid' : '' ?>" name="label"
                               value="<?= htmlspecialchars($field['label'] ?? '') ?>" maxlength="150" required <?= $isSystem ? 'readonly' : '' ?>>
                        <?php if (!empty($err['label'])): ?><div class="invalid-feedback"><?= implode('<br>', $err['label']) ?></div><?php endif; ?>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">مرحلهٔ قرارگیری <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($err['wizard_step_id']) ? 'is-invalid' : '' ?>" name="wizard_step_id" id="wizardStep" required <?= $isSystem ? 'disabled' : '' ?> onchange="fileStepGuard()">
                            <?php foreach (($wizardSteps ?? []) as $ws): ?>
                                <option value="<?= (int)$ws['id'] ?>" data-view="<?= htmlspecialchars($ws['view_key'] ?? '') ?>"
                                    <?= (int)($field['wizard_step_id'] ?? (int)($_GET['step'] ?? 0)) === (int)$ws['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ws['title']) ?><?= (int)$ws['is_active'] ? '' : ' (غیرفعال)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($err['wizard_step_id'])): ?><div class="invalid-feedback"><?= implode('<br>', $err['wizard_step_id']) ?></div><?php endif; ?>
                        <?php if ($isSystem): ?><input type="hidden" name="wizard_step_id" value="<?= (int)$field['wizard_step_id'] ?>"><?php endif; ?>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">نوع فیلد <span class="text-danger">*</span></label>
                        <select class="form-select" name="field_type" id="field_type" onchange="toggleTypeOptions()" required <?= $isSystem ? 'disabled' : '' ?>>
                            <?php foreach (FormField::TYPES as $tk => $tl): ?>
                                <option value="<?= $tk ?>" <?= ($field['field_type'] ?? '') === $tk ? 'selected' : '' ?>><?= $tl ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($isSystem): ?><input type="hidden" name="field_type" value="<?= htmlspecialchars($field['field_type'] ?? 'text') ?>"><?php endif; ?>
                    </div>

                    <?php if (!$isSystem): ?>
                    <div class="col-md-6" id="optionsBox" style="<?= $showOptions ? '' : 'display:none' ?>">
                        <label class="form-label">گزینه‌ها (هر خط یکی) <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($err['options']) ? 'is-invalid' : '' ?>" name="options" rows="4"
                                  placeholder="مثال:&#10;گزینهٔ اول&#10;گزینهٔ دوم"><?= htmlspecialchars($field['options'] ?? '') ?></textarea>
                        <?php if (!empty($err['options'])): ?><div class="invalid-feedback"><?= implode('<br>', $err['options']) ?></div><?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">کلید فنی (انگلیسی — اختیاری)</label>
                        <input type="text" class="form-control" name="field_key" dir="ltr"
                               value="<?= htmlspecialchars(($field['field_key'] ?? '')) ?>"
                               placeholder="خودکار ساخته می‌شود" pattern="[A-Za-z0-9_]*" maxlength="40" <?= $isEdit ? 'readonly' : '' ?>>
                        <div class="form-text">اگر خالی بگذارید، کلید یکتا به‌صورت خودکار ساخته می‌شود.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">حداکثر طول</label>
                        <input type="number" class="form-control" name="maxlength" min="1" max="2000" value="<?= (int)($val['maxlength'] ?? 0) ?: '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">حداقل مقدار (عددی)</label>
                        <input type="number" class="form-control" name="min_value" step="any" value="<?= htmlspecialchars((string)($val['min'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">حداکثر مقدار (عددی)</label>
                        <input type="number" class="form-control" name="max_value" step="any" value="<?= htmlspecialchars((string)($val['max'] ?? '')) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الگوی اعتبارسنجی (regex — اختیاری)</label>
                        <input type="text" class="form-control <?= isset($err['pattern']) ? 'is-invalid' : '' ?>" name="pattern" dir="ltr"
                               value="<?= htmlspecialchars($val['pattern'] ?? '') ?>" placeholder="مثال: ^\d{6}$">
                        <?php if (!empty($err['pattern'])): ?><div class="invalid-feedback"><?= implode('<br>', $err['pattern']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ترتیب نمایش</label>
                        <input type="number" class="form-control" name="sort_order" value="<?= (int)($field['sort_order'] ?? 100) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">متن راهنما</label>
                        <input type="text" class="form-control" name="help_text" maxlength="255" value="<?= htmlspecialchars($field['help_text'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">جای‌نما (placeholder)</label>
                        <input type="text" class="form-control" name="placeholder" maxlength="150" value="<?= htmlspecialchars($field['placeholder'] ?? '') ?>">
                    </div>
                    <?php else: ?>
                    <div class="col-12">
                        <label class="form-label">متن راهنمای زیر فیلد</label>
                        <input type="text" class="form-control" name="help_text" maxlength="255" value="<?= htmlspecialchars($field['help_text'] ?? '') ?>">
                    </div>
                    <?php endif; ?>

                    <div class="col-12 d-flex gap-4 flex-wrap">
                        <?php if (!$isSystem || in_array($field['field_key'] ?? '', FormField::TOGGLABLE_SYSTEM_KEYS, true)): ?>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="isRequired" name="is_required" value="1"
                                   <?= (int)($field['is_required'] ?? 0) === 1 ? 'checked' : '' ?> <?= $isSystem ? 'disabled' : '' ?>>
                            <label class="form-check-label fw-bold" for="isRequired">فیلد اجباری است</label>
                        </div>
                        <?php endif; ?>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="isActive" name="is_active" value="1"
                                   <?= ($isEdit && $field) ? ((int)$field['is_active'] === 1 ? 'checked' : '') : 'checked' ?>
                                   <?= ($isSystem && !in_array($field['field_key'] ?? '', FormField::TOGGLABLE_SYSTEM_KEYS, true)) ? 'disabled checked' : '' ?>>
                            <label class="form-check-label fw-bold" for="isActive">در فرم ثبت‌نام فعال باشد</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success px-4">💾 ذخیرهٔ فیلد</button>
                    <a href="<?= $base ?>admin/form-fields" class="btn btn-outline-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTypeOptions() {
    const t = document.getElementById('field_type');
    if (!t) return;
    const box = document.getElementById('optionsBox');
    if (box) box.style.display = (t.value === 'select' || t.value === 'radio') ? '' : 'none';
    fileStepGuard();
}
// فیلد فایلی فقط در مرحلهٔ «بارگذاری مدارک»
function fileStepGuard() {
    const t = document.getElementById('field_type');
    const sel = document.getElementById('wizardStep');
    if (!t || !sel) return;
    const opt = sel.options[sel.selectedIndex];
    const isDocs = opt && opt.getAttribute('data-view') === 'step4';
    if (t.value === 'file' && !isDocs) {
        t.setCustomValidity('فیلد فایلی فقط در مرحلهٔ «بارگذاری مدارک» مجاز است.');
    } else {
        t.setCustomValidity('');
    }
    t.reportValidity();
}
document.addEventListener('DOMContentLoaded', fileStepGuard);
</script>
