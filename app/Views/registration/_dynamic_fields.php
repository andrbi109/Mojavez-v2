<?php
/**
 * جزء مشترک: رندر فیلدهای پویا (سفارشی) یک مرحله از ویزارد
 * ------------------------------------------------------------------
 * ورودی‌ها:
 *   $dynStep    — شمارهٔ مرحله
 *   $dynFields  — فیلدهای سفارشی فعال این مرحله (از FormField)
 *   $dynValues  — مقادیر بازگشتی از سشن (کلید: dyn_{id})
 *   $dynErrors  — خطاهای اعتبارسنجی (کلید: dyn_{id})
 */
$dynStep = $dynStep ?? 0;
$dynFields = $dynFields ?? [];
$dynValues = $dynValues ?? [];
$dynErrors = $dynErrors ?? [];
if (empty($dynFields)) return;

$typeLabels = FormField::TYPES;
?>
<hr class="my-4">
<div class="d-flex align-items-center gap-2 mb-3">
    <span style="color:var(--saffron); font-size:1.2rem">❖</span>
    <h6 class="fw-bold mb-0" style="color:var(--navy)">سؤالات تکمیلی این مرحله</h6>
    <small class="text-muted">(تعیین‌شده توسط مدیر سامانه)</small>
</div>

<div class="row g-3">
    <?php foreach ($dynFields as $f): ?>
        <?php
        $fid = (int)$f['id'];
        $name = 'dyn_' . $fid;
        $value = $dynValues[$name] ?? '';
        $err = $dynErrors[$name] ?? [];
        $req = (int)$f['is_required'] === 1;
        $rules = FormField::parseRules($f['validation_json']);
        $options = FormField::parseOptions($f['options']);
        $invalid = $err ? 'is-invalid' : '';
        $help = !empty($f['help_text']) ? '<div class="form-text">' . htmlspecialchars($f['help_text']) . '</div>' : '';
        ?>
        <div class="<?= in_array($f['field_type'], ['textarea', 'file'], true) ? 'col-12' : 'col-md-6' ?>">
            <label class="form-label" for="<?= $name ?>">
                <?= htmlspecialchars($f['label']) ?> <?= $req ? '<span class="text-danger">*</span>' : '' ?>
            </label>

            <?php if ($f['field_type'] === 'textarea'): ?>
                <textarea class="form-control <?= $invalid ?>" id="<?= $name ?>" name="<?= $name ?>" rows="3"
                          maxlength="<?= (int)($rules['maxlength'] ?? 1000) ?>"
                          placeholder="<?= htmlspecialchars($f['placeholder'] ?? '') ?>"
                          <?= $req ? 'required' : '' ?>><?= htmlspecialchars($value) ?></textarea>

            <?php elseif ($f['field_type'] === 'select'): ?>
                <select class="form-select <?= $invalid ?>" id="<?= $name ?>" name="<?= $name ?>" <?= $req ? 'required' : '' ?>>
                    <option value="">انتخاب کنید...</option>
                    <?php foreach ($options as $opt): ?>
                        <option value="<?= htmlspecialchars($opt) ?>" <?= $value === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ($f['field_type'] === 'radio'): ?>
                <div class="d-flex flex-wrap gap-3 pt-1">
                    <?php foreach ($options as $oi => $opt): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="<?= $name ?>"
                                   id="<?= $name . '_' . $oi ?>" value="<?= htmlspecialchars($opt) ?>"
                                   <?= $value === $opt ? 'checked' : '' ?> <?= $req ? 'required' : '' ?>>
                            <label class="form-check-label" for="<?= $name . '_' . $oi ?>"><?= htmlspecialchars($opt) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($f['field_type'] === 'checkbox'): ?>
                <div class="form-check pt-2">
                    <input class="form-check-input" type="checkbox" name="<?= $name ?>" value="بله"
                           id="<?= $name ?>" <?= $value ? 'checked' : '' ?>>
                    <label class="form-check-label" for="<?= $name ?>">
                        <?= htmlspecialchars($f['placeholder'] ?: $f['label']) ?>
                    </label>
                </div>

            <?php elseif ($f['field_type'] === 'number'): ?>
                <input type="number" class="form-control <?= $invalid ?>" id="<?= $name ?>" name="<?= $name ?>"
                       value="<?= htmlspecialchars($value) ?>" inputmode="numeric"
                       <?= isset($rules['min']) ? 'min="' . (float)$rules['min'] . '"' : '' ?>
                       <?= isset($rules['max']) ? 'max="' . (float)$rules['max'] . '"' : '' ?>
                       placeholder="<?= htmlspecialchars($f['placeholder'] ?? '') ?>"
                       <?= $req ? 'required' : '' ?>>

            <?php elseif ($f['field_type'] === 'file'): ?>
                <div class="upload-box" onclick="document.getElementById('<?= $name ?>').click()">
                    <input type="file" id="<?= $name ?>" name="<?= $name ?>" accept=".jpg,.jpeg,.png,.pdf"
                           style="display:none" onchange="showFileName(this)">
                    <div class="mb-1">📎</div>
                    <strong class="small"><?= htmlspecialchars($f['label']) ?> <?= $req ? '<span class="text-danger">*</span>' : '' ?></strong>
                    <p class="text-muted small mb-0" id="file_<?= $name ?>">
                        <?= !empty($f['help_text']) ? htmlspecialchars($f['help_text']) : 'فایلی انتخاب نشده' ?>
                    </p>
                </div>

            <?php else: /* text */ ?>
                <input type="text" class="form-control <?= $invalid ?>" id="<?= $name ?>" name="<?= $name ?>"
                       value="<?= htmlspecialchars($value) ?>"
                       maxlength="<?= (int)($rules['maxlength'] ?? 255) ?>"
                       <?= !empty($rules['pattern']) ? 'pattern="' . htmlspecialchars($rules['pattern']) . '"' : '' ?>
                       placeholder="<?= htmlspecialchars($f['placeholder'] ?? '') ?>"
                       <?= $req ? 'required' : '' ?>>
            <?php endif; ?>

            <?= $help ?>
            <?php if ($err): ?><div class="invalid-feedback d-block"><?= implode('<br>', array_map('htmlspecialchars', $err)) ?></div><?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
