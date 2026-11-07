<?php
/**
 * فرم درخواست بازبینی پرونده (فاز ۲ نقشهٔ راه)
 * -------------------------------------------------------
 * همهٔ اطلاعات فعلی پرونده پیش‌پر شده و قابل ویرایش است (به‌جز کد ملی)
 * + بارگذاری تا ۵ مدرک جدید + یادداشت برای مدیر.
 * ورودی‌ها: $applicant, $jobDetail, $propertyDetail, $dynFields, $dynCurrent, $licenseGroups, $errors, $old
 */
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$o = fn(string $key, $fallback = '') => $e($old[$key] ?? $fallback);   // دادهٔ بازگشتی خطا اولویت دارد
$app = $applicant;
$jd = $jobDetail ?: [];
$pd = $propertyDetail ?: [];
$jy = $jm = $jdd = '';
if (!empty($app['birth_date'])) {
    $arr = gregorian_to_jalali_array((string)$app['birth_date']);
    $jy = $arr['year']; $jm = $arr['month']; $jdd = $arr['day'];
}
$err = fn(string $k) => !empty($errors[$k]) ? '<div class="invalid-feedback d-block">' . $e(implode(' ', $errors[$k])) . '</div>' : '';
$inv = fn(string $k) => isset($errors[$k]) ? 'is-invalid' : '';
$docTypeOptions = [
    'national_card' => 'کارت ملی', 'birth_cert' => 'شناسنامه', 'ownership_doc' => 'مدرک مالکیت',
    'vocational_cert' => 'گواهی فنی‌وحرفه‌ای', 'education_degree' => 'مدرک تحصیلی',
    'product_photos' => 'تصویر محصولات', 'optional' => 'سایر',
];
?>
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card p-3 p-md-4 mb-4">
            <h4 class="fw-bold mb-1"><?= icon('edit', 'me-1') ?> درخواست بازبینی پرونده</h4>
            <p class="text-muted small mb-0">
                کد رهگیری: <code><?= $e($app['tracking_code']) ?></code> —
                همهٔ اطلاعات فعلی پرونده پیش‌پر شده است؛ موارد نیازمند اصلاح را تغییر دهید،
                مدارک جدید را بارگذاری کنید و درخواست را ثبت نمایید.
                <strong>تا تأیید مدیر، اطلاعات رسمی پرونده تغییر نمی‌کند.</strong>
            </p>
        </div>

        <form method="post" action="<?= BASE_URL ?>tracking/revision/submit" enctype="multipart/form-data">
            <?= Session::csrfField() ?>
            <input type="hidden" name="tracking_code" value="<?= $e($app['tracking_code']) ?>">

            <!-- اطلاعات هویتی -->
            <div class="card mb-4">
                <div class="card-header"><?= icon('id-card', 'me-1') ?> اطلاعات هویتی</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small">کد ملی <span class="text-muted">(غیرقابل تغییر)</span></label>
                            <input type="text" class="form-control" value="<?= $e($app['national_code']) ?>" readonly disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">نام <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $inv('first_name') ?>" name="first_name" value="<?= $o('first_name', $app['first_name']) ?>" required maxlength="50">
                            <?= $err('first_name') ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">نام خانوادگی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $inv('last_name') ?>" name="last_name" value="<?= $o('last_name', $app['last_name']) ?>" required maxlength="50">
                            <?= $err('last_name') ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">کد پستی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $inv('postal_code') ?>" name="postal_code" value="<?= $o('postal_code', $app['postal_code']) ?>" required maxlength="10" pattern="\d{10}" inputmode="numeric">
                            <?= $err('postal_code') ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">موبایل <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $inv('mobile') ?>" name="mobile" value="<?= $o('mobile', $app['mobile']) ?>" required maxlength="11" pattern="09\d{9}" inputmode="numeric" dir="ltr">
                            <?= $err('mobile') ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">نام پدر <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $inv('father_name') ?>" name="father_name" value="<?= $o('father_name', $app['father_name']) ?>" required maxlength="50">
                            <?= $err('father_name') ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">تاریخ تولد</label>
                            <div class="row g-1">
                                <div class="col-4"><select class="form-select form-select-sm" name="birth_year">
                                    <?php $cy = currentPersianYear(); for ($y = 1300; $y <= $cy; $y++): ?>
                                        <option value="<?= $y ?>" <?= (int)$jy === $y ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select></div>
                                <div class="col-4"><select class="form-select form-select-sm" name="birth_month">
                                    <?php $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                                    foreach ($months as $mi => $mn): ?><option value="<?= $mi + 1 ?>" <?= (int)$jm === $mi + 1 ? 'selected' : '' ?>><?= $mn ?></option><?php endforeach; ?>
                                </select></div>
                                <div class="col-4"><select class="form-select form-select-sm" name="birth_day">
                                    <?php for ($d = 1; $d <= 31; $d++): ?><option value="<?= $d ?>" <?= (int)$jdd === $d ? 'selected' : '' ?>><?= $d ?></option><?php endfor; ?>
                                </select></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">وضعیت تأهل</label>
                            <select class="form-select" name="marital_status" onchange="document.getElementById('ccBox').style.display=this.value==='متأهل'?'':'none'">
                                <option value="مجرد" <?= ($old['marital_status'] ?? $app['marital_status']) === 'مجرد' ? 'selected' : '' ?>>مجرد</option>
                                <option value="متأهل" <?= ($old['marital_status'] ?? $app['marital_status']) === 'متأهل' ? 'selected' : '' ?>>متأهل</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="ccBox" style="<?= ($old['marital_status'] ?? $app['marital_status']) === 'متأهل' ? '' : 'display:none' ?>">
                            <label class="form-label small">تعداد فرزندان</label>
                            <input type="number" class="form-control" name="children_count" min="0" max="20" value="<?= (int)($old['children_count'] ?? $app['children_count']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">وضعیت جسمانی</label>
                            <select class="form-select" name="physical_condition">
                                <option value="سالم" <?= ($old['physical_condition'] ?? $app['physical_condition']) === 'سالم' ? 'selected' : '' ?>>سالم</option>
                                <option value="معلولیت" <?= ($old['physical_condition'] ?? $app['physical_condition']) === 'معلولیت' ? 'selected' : '' ?>>معلولیت</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">توضیحات معلولیت</label>
                            <input type="text" class="form-control" name="disability_details" maxlength="255" value="<?= $o('disability_details', $app['disability_details']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">پروندهٔ قضایی</label>
                            <select class="form-select" name="has_court_case">
                                <option value="خیر" <?= ($old['has_court_case'] ?? $app['has_court_case']) === 'خیر' ? 'selected' : '' ?>>خیر</option>
                                <option value="بله" <?= ($old['has_court_case'] ?? $app['has_court_case']) === 'بله' ? 'selected' : '' ?>>بله</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">وام فعال</label>
                            <select class="form-select" name="has_active_loan">
                                <option value="خیر" <?= ($old['has_active_loan'] ?? $app['has_active_loan']) === 'خیر' ? 'selected' : '' ?>>خیر</option>
                                <option value="بله" <?= ($old['has_active_loan'] ?? $app['has_active_loan']) === 'بله' ? 'selected' : '' ?>>بله</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">اقساط معوقه</label>
                            <select class="form-select" name="has_overdue_installments">
                                <option value="خیر" <?= ($old['has_overdue_installments'] ?? $app['has_overdue_installments']) === 'خیر' ? 'selected' : '' ?>>خیر</option>
                                <option value="بله" <?= ($old['has_overdue_installments'] ?? $app['has_overdue_installments']) === 'بله' ? 'selected' : '' ?>>بله</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">دهک درآمدی (اختیاری)</label>
                            <input type="number" class="form-control" name="income_decile" min="1" max="10" value="<?= $o('income_decile', $app['income_decile']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- اطلاعات شغلی -->
            <div class="card mb-4">
                <div class="card-header"><?= icon('vase', 'me-1') ?> اطلاعات شغلی</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small">عنوان مجوز <span class="text-danger">*</span></label>
                            <select class="form-select <?= $inv('license_title') ?>" name="license_title" required>
                                <?php $curLic = $old['license_title'] ?? ($jd['license_title'] ?? ''); ?>
                                <option value="">انتخاب کنید...</option>
                                <?php foreach (($licenseGroups ?? []) as $cat => $titles): ?>
                                    <optgroup label="<?= $e($cat) ?>">
                                        <?php foreach ($titles as $t): ?>
                                            <option value="<?= $e($t) ?>" <?= $curLic === $t ? 'selected' : '' ?>><?= $e($t) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                                <?php if ($curLic !== '' && !in_array($curLic, array_merge(...array_values(array_map(fn($g) => $g, $licenseGroups ?: [[]]))), true)): ?>
                                    <option value="<?= $e($curLic) ?>" selected><?= $e($curLic) ?> (فعلی)</option>
                                <?php endif; ?>
                            </select>
                            <?= $err('license_title') ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">سابقه (ماه) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control <?= $inv('experience_months') ?>" name="experience_months" min="0" required value="<?= $o('experience_months', $jd['experience_months'] ?? 0) ?>">
                            <?= $err('experience_months') ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">نحوهٔ آموزش <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 pt-1 flex-wrap">
                                <?php foreach (['در خانه', 'استاد شاگردی', 'کارگاهی'] as $mth): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="training_method" id="tm_<?= $mth ?>" value="<?= $mth ?>" <?= ($old['training_method'] ?? ($jd['training_method'] ?? '')) === $mth ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="tm_<?= $mth ?>"><?= $mth ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?= $err('training_method') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- اطلاعات ملک -->
            <div class="card mb-4">
                <div class="card-header"><?= icon('home', 'me-1') ?> اطلاعات ملک</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small">وضعیت مالکیت</label>
                            <select class="form-select" name="ownership_status">
                                <?php foreach (['شخص', 'مستأجر', 'سرپرست'] as $os): ?>
                                    <option value="<?= $os ?>" <?= ($old['ownership_status'] ?? ($pd['ownership_status'] ?? '')) === $os ? 'selected' : '' ?>><?= $os ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">استان <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $inv('province') ?>" name="province" required maxlength="50" value="<?= $o('province', $pd['province'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">شهرستان <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $inv('county') ?>" name="county" required maxlength="50" value="<?= $o('county', $pd['county'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">شهر <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $inv('city') ?>" name="city" required maxlength="50" value="<?= $o('city', $pd['city'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">بخش</label>
                            <input type="text" class="form-control" name="district" maxlength="50" value="<?= $o('district', $pd['district'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">دهستان</label>
                            <input type="text" class="form-control" name="rural_district" maxlength="50" value="<?= $o('rural_district', $pd['rural_district'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">روستا</label>
                            <input type="text" class="form-control" name="village" maxlength="50" value="<?= $o('village', $pd['village'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">شناسهٔ قبض برق <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $inv('electricity_bill_id') ?>" name="electricity_bill_id" required maxlength="20" value="<?= $o('electricity_bill_id', $pd['electricity_bill_id'] ?? '') ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">خیابان <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $inv('address_street') ?>" name="address_street" required maxlength="100" value="<?= $o('address_street', $pd['address_street'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">کوچه</label>
                            <input type="text" class="form-control" name="address_alley" maxlength="100" value="<?= $o('address_alley', $pd['address_alley'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">پلاک</label>
                            <input type="text" class="form-control" name="address_plaque" maxlength="20" value="<?= $o('address_plaque', $pd['address_plaque'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- فیلدهای سفارشی -->
            <?php if (!empty($dynFields)): ?>
            <div class="card mb-4">
                <div class="card-header"><?= icon('form', 'me-1') ?> سؤالات تکمیلی</div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($dynFields as $f): ?>
                            <?php
                            $name = 'dyn_' . (int)$f['id'];
                            $val = $old[$name] ?? ($dynCurrent[$f['field_key']] ?? '');
                            $rules = FormField::parseRules($f['validation_json']);
                            $opts = FormField::parseOptions($f['options']);
                            ?>
                            <div class="col-md-6">
                                <label class="form-label small"><?= $e($f['label']) ?> <?= (int)$f['is_required'] ? '<span class="text-danger">*</span>' : '' ?></label>
                                <?php if ($f['field_type'] === 'textarea'): ?>
                                    <textarea class="form-control <?= $inv($name) ?>" name="<?= $name ?>" rows="2" <?= (int)$f['is_required'] ? 'required' : '' ?>><?= $e($val) ?></textarea>
                                <?php elseif ($f['field_type'] === 'select'): ?>
                                    <select class="form-select <?= $inv($name) ?>" name="<?= $name ?>" <?= (int)$f['is_required'] ? 'required' : '' ?>>
                                        <option value="">انتخاب کنید...</option>
                                        <?php foreach ($opts as $op): ?><option value="<?= $e($op) ?>" <?= $val === $op ? 'selected' : '' ?>><?= $e($op) ?></option><?php endforeach; ?>
                                    </select>
                                <?php elseif ($f['field_type'] === 'radio'): ?>
                                    <div class="d-flex gap-3 flex-wrap pt-1">
                                        <?php foreach ($opts as $oi => $op): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="<?= $name ?>" id="<?= $name ?>_<?= $oi ?>" value="<?= $e($op) ?>" <?= $val === $op ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="<?= $name ?>_<?= $oi ?>"><?= $e($op) ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($f['field_type'] === 'checkbox'): ?>
                                    <div class="form-check pt-2">
                                        <input class="form-check-input" type="checkbox" name="<?= $name ?>" value="بله" id="<?= $name ?>" <?= $val ? 'checked' : '' ?>>
                                        <label class="form-check-label small" for="<?= $name ?>"><?= $e($f['placeholder'] ?: $f['label']) ?></label>
                                    </div>
                                <?php elseif ($f['field_type'] === 'number'): ?>
                                    <input type="number" class="form-control <?= $inv($name) ?>" name="<?= $name ?>" value="<?= $e($val) ?>" step="any"
                                           <?= isset($rules['min']) ? 'min="' . (float)$rules['min'] . '"' : '' ?> <?= isset($rules['max']) ? 'max="' . (float)$rules['max'] . '"' : '' ?>>
                                <?php else: ?>
                                    <input type="text" class="form-control <?= $inv($name) ?>" name="<?= $name ?>" value="<?= $e($val) ?>" maxlength="<?= (int)($rules['maxlength'] ?? 255) ?>">
                                <?php endif; ?>
                                <?= $err($name) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- مدارک جدید -->
            <div class="card mb-4">
                <div class="card-header"><?= icon('upload', 'me-1') ?> مدارک جدید (اختیاری — حداکثر ۵ فایل)</div>
                <div class="card-body">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-4">
                            <select class="form-select form-select-sm" name="rev_doc_type_<?= $i ?>">
                                <?php foreach ($docTypeOptions as $k => $lbl): ?><option value="<?= $k ?>"><?= $lbl ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <input type="file" class="form-control form-control-sm <?= $inv('rev_doc_' . $i) ?>" name="rev_doc_<?= $i ?>" accept=".jpg,.jpeg,.png,.pdf">
                            <?= $err('rev_doc_' . $i) ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                    <div class="form-text">jpg / png / pdf — حداکثر ۲ مگابایت. مدارک جدید پس از تأیید مدیر به پرونده اضافه می‌شوند.</div>
                </div>
            </div>

            <!-- یادداشت -->
            <div class="card mb-4">
                <div class="card-body">
                    <label class="form-label small">توضیح برای مدیر (اختیاری)</label>
                    <textarea class="form-control" name="note" rows="2" maxlength="500" placeholder="مثلاً: آدرس کارگاه تغییر کرده و اجاره‌نامهٔ جدید بارگذاری شد."><?= $o('note') ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between mb-4">
                <a href="<?= BASE_URL ?>tracking/status/<?= $e(rawurlencode($app['tracking_code'])) ?>" class="btn btn-outline-secondary"><?= icon('chevron-right', '', 15) ?> بازگشت</a>
                <button type="submit" class="btn btn-gold px-4"><?= icon('send', '', 16) ?> ثبت درخواست بازبینی</button>
            </div>
        </form>
    </div>
</div>
