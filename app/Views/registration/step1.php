<?php $currentStep = 1; require VIEW_PATH . '/registration/_progress.php'; ?>

<?php
// سرویس انتخاب‌شده (فاز ۹ نقشهٔ راه)
$selectedService = null;
try {
    $sid = Session::getStep('service_id');
    if ($sid) $selectedService = (new ServiceType())->findById((int)$sid);
} catch (\Throwable $e) {}
?>
<?php if ($selectedService): ?>
<div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="badge bg-info fs-6 p-2">
        <?= icon('grid', '', 14) ?> <?= htmlspecialchars($selectedService['title']) ?>
    </span>
    <a href="<?= BASE_URL ?>register?change=service" class="small text-muted">تغییر نوع خدمت</a>
</div>
<?php endif; ?>

<div class="card p-4">
    <form method="post" action="<?= BASE_URL ?>register/step/<?= (int)($stepPosition ?? 1) ?>" novalidate>
        <?= Session::csrfField() ?>

        <!-- نوع متقاضی (فاز ۱۰ نقشهٔ راه) -->
        <div class="mb-4">
            <label class="form-label">نوع متقاضی <span class="text-danger">*</span></label>
            <div class="row g-2">
                <?php $curType = $stepData['applicant_type'] ?? 'حقیقی'; ?>
                <div class="col-6">
                    <label class="ownership-option card p-3 text-center h-100 mb-0 <?= $curType === 'حقیقی' ? 'border-primary' : '' ?>" style="cursor:pointer">
                        <input class="form-check-input d-none" type="radio" name="applicant_type" value="حقیقی" onchange="toggleApplicantType()" <?= $curType === 'حقیقی' ? 'checked' : '' ?>>
                        <div><?= icon('user', '', 26) ?></div>
                        <strong class="small">شخص حقیقی</strong>
                        <small class="text-muted" style="font-size:.7rem">برای خودم درخواست می‌دهم</small>
                    </label>
                </div>
                <div class="col-6">
                    <label class="ownership-option card p-3 text-center h-100 mb-0 <?= $curType === 'حقوقی' ? 'border-primary' : '' ?>" style="cursor:pointer">
                        <input class="form-check-input d-none" type="radio" name="applicant_type" value="حقوقی" onchange="toggleApplicantType()" <?= $curType === 'حقوقی' ? 'checked' : '' ?>>
                        <div><?= icon('users', '', 26) ?></div>
                        <strong class="small">شخص حقوقی</strong>
                        <small class="text-muted" style="font-size:.7rem">شرکت / تعاونی / مؤسسه</small>
                    </label>
                </div>
            </div>
        </div>

        <!-- اطلاعات شخص حقوقی -->
        <div id="legalBox" class="mb-4" style="<?= $curType === 'حقوقی' ? '' : 'display:none' ?>">
            <div class="card p-3" style="background:var(--turquoise-light); border-color:var(--turquoise)">
                <h6 class="fw-bold mb-3"><?= icon('users', 'me-1', 17) ?> اطلاعات شخص حقوقی</h6>
                <p class="small text-muted mb-3">در درخواست حقوقی، فیلدهای هویتی پایین به‌عنوان <strong>نمایندهٔ مجاز</strong> تفسیر می‌شوند.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">نام شرکت/مؤسسه <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['company_name']) ? 'is-invalid' : '' ?>" name="company_name" maxlength="150" value="<?= htmlspecialchars($stepData['company_name'] ?? '') ?>">
                        <?php if (!empty($errors['company_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['company_name']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">شناسهٔ ملی شخص حقوقی (۱۱ رقم) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['company_national_id']) ? 'is-invalid' : '' ?>" name="company_national_id" maxlength="11" pattern="\d{11}" inputmode="numeric" dir="ltr" value="<?= htmlspecialchars($stepData['company_national_id'] ?? '') ?>">
                        <?php if (!empty($errors['company_national_id'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['company_national_id']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">شمارهٔ ثبت <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['reg_number']) ? 'is-invalid' : '' ?>" name="reg_number" maxlength="30" dir="ltr" value="<?= htmlspecialchars($stepData['reg_number'] ?? '') ?>">
                        <?php if (!empty($errors['reg_number'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['reg_number']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نوع شخصیت حقوقی <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['company_type']) ? 'is-invalid' : '' ?>" name="company_type">
                            <option value="">انتخاب کنید...</option>
                            <?php foreach (['شرکت سهامی خاص', 'شرکت مسئولیت محدود', 'شرکت تعاونی', 'مؤسسه غیرتجاری', 'سایر'] as $ct): ?>
                                <option value="<?= $ct ?>" <?= ($stepData['company_type'] ?? '') === $ct ? 'selected' : '' ?>><?= $ct ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['company_type'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['company_type']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">کد اقتصادی (اختیاری)</label>
                        <input type="text" class="form-control" name="economic_code" maxlength="20" dir="ltr" inputmode="numeric" value="<?= htmlspecialchars($stepData['economic_code'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تاریخ ثبت شرکت <span class="text-danger">*</span></label>
                        <?php $rg = !empty($stepData['reg_date']) ? gregorian_to_jalali_array((string)$stepData['reg_date']) : ['year' => 0, 'month' => 0, 'day' => 0]; ?>
                        <div class="row g-1">
                            <div class="col-4"><select class="form-select form-select-sm" name="reg_year"><option value="">سال</option>
                                <?php $cy = currentPersianYear(); for ($y = 1350; $y <= $cy; $y++): ?><option value="<?= $y ?>" <?= (int)$rg['year'] === $y ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?>
                            </select></div>
                            <div class="col-4"><select class="form-select form-select-sm" name="reg_month"><option value="">ماه</option>
                                <?php $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                                foreach ($months as $mi => $mn): ?><option value="<?= $mi + 1 ?>" <?= (int)$rg['month'] === $mi + 1 ? 'selected' : '' ?>><?= $mn ?></option><?php endforeach; ?>
                            </select></div>
                            <div class="col-4"><select class="form-select form-select-sm" name="reg_day"><option value="">روز</option>
                                <?php for ($d = 1; $d <= 31; $d++): ?><option value="<?= $d ?>" <?= (int)$rg['day'] === $d ? 'selected' : '' ?>><?= $d ?></option><?php endfor; ?>
                            </select></div>
                        </div>
                        <?php if (!empty($errors['reg_date'])): ?><div class="invalid-feedback d-block"><?= implode('<br>', $errors['reg_date']) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- نام، نام خانوادگی -->
            <div class="col-md-6">
                <label class="form-label" id="lblFirstName">نام <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" name="first_name" value="<?= htmlspecialchars($stepData['first_name'] ?? '') ?>" maxlength="50" required>
                <?php if (!empty($errors['first_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['first_name']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" name="last_name" value="<?= htmlspecialchars($stepData['last_name'] ?? '') ?>" maxlength="50" required>
                <?php if (!empty($errors['last_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['last_name']) ?></div><?php endif; ?>
            </div>
            <!-- کد ملی -->
            <div class="col-md-6">
                <label class="form-label">کد ملی <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['national_code']) ? 'is-invalid' : '' ?>" name="national_code" value="<?= htmlspecialchars($stepData['national_code'] ?? '') ?>" maxlength="10" pattern="\d{10}" inputmode="numeric" autocomplete="off" required>
                <?php if (!empty($errors['national_code'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['national_code']) ?></div><?php endif; ?>
            </div>
            <!-- کد پستی -->
            <div class="col-md-6">
                <label class="form-label">کد پستی <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['postal_code']) ? 'is-invalid' : '' ?>" name="postal_code" value="<?= htmlspecialchars($stepData['postal_code'] ?? '') ?>" maxlength="10" pattern="\d{10}" inputmode="numeric" required>
                <?php if (!empty($errors['postal_code'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['postal_code']) ?></div><?php endif; ?>
            </div>
            <!-- موبایل -->
            <div class="col-md-6">
                <label class="form-label">شماره تلفن همراه <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['mobile']) ? 'is-invalid' : '' ?>" name="mobile" value="<?= htmlspecialchars($stepData['mobile'] ?? '') ?>" maxlength="11" placeholder="۰۹xxxxxxxxx" pattern="09\d{9}" inputmode="numeric" autocomplete="tel" required>
                <?php if (!empty($errors['mobile'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['mobile']) ?></div><?php endif; ?>
            </div>
            <!-- تاریخ تولد --> 
            <div class="col-md-6">
                <label class="form-label">تاریخ تولد <span class="text-danger">*</span></label>
                <div class="row g-2">
                    <div class="col">
                        <select class="form-select" name="birth_year" id="birth_year" required>
                            <option value="">سال</option>
                            <?php
                            $currentPersianYear = currentPersianYear();
                            for ($y = 1300; $y <= $currentPersianYear; $y++):
                                $sel = (isset($stepData['birth_year']) && $stepData['birth_year'] == $y) ? 'selected' : '';
                            ?>
                                <option value="<?= $y ?>" <?= $sel ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col">
                        <select class="form-select" name="birth_month" id="birth_month" required>
                            <option value="">ماه</option>
                            <?php
                            $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                            foreach ($months as $i => $name):
                                $m = $i + 1;
                                $sel = (isset($stepData['birth_month']) && $stepData['birth_month'] == $m) ? 'selected' : '';
                            ?>
                                <option value="<?= $m ?>" <?= $sel ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col">
                        <select class="form-select" name="birth_day" id="birth_day" required>
                            <option value="">روز</option>
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                                <option value="<?= $d ?>" <?= (isset($stepData['birth_day']) && $stepData['birth_day'] == $d) ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <?php if (!empty($errors['birth_date'])): ?>
                    <div class="invalid-feedback d-block"><?= implode('<br>', $errors['birth_date']) ?></div>
                <?php endif; ?>
            </div>
            <script>
                // تنظیم روزهای مجاز بر اساس ماه و سال (کبیسه)
                function updateDays() {
                    const year = parseInt(document.getElementById('birth_year').value) || 1399;
                    const month = parseInt(document.getElementById('birth_month').value) || 1;
                    const daySelect = document.getElementById('birth_day');
                    const maxDays = (month <= 6) ? 31 : (month <= 11) ? 30 : (isLeapYear(year) ? 30 : 29);
                    for (let i = 1; i <= 31; i++) {
                        const opt = daySelect.querySelector(`option[value="${i}"]`);
                        if (opt) opt.style.display = (i <= maxDays) ? '' : 'none';
                    }
                    // اگر روز انتخاب‌شده بزرگتر از maxDays بود، پاک کن
                    if (parseInt(daySelect.value) > maxDays) daySelect.value = '';
                }
                function isLeapYear(year) {
                    return ((year + 12) % 33) % 4 === 1;
                }
                document.getElementById('birth_year').addEventListener('change', updateDays);
                document.getElementById('birth_month').addEventListener('change', updateDays);
                updateDays();
            </script>

            <!-- نام پدر -->
            <div class="col-md-6">
                <label class="form-label">نام پدر <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['father_name']) ? 'is-invalid' : '' ?>" name="father_name" value="<?= htmlspecialchars($stepData['father_name'] ?? '') ?>" maxlength="50" required>
                <?php if (!empty($errors['father_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['father_name']) ?></div><?php endif; ?>
            </div>
            <!-- وضعیت تأهل -->
            <div class="col-md-6">
                <label class="form-label">وضعیت تأهل <span class="text-danger">*</span></label>
                <select class="form-select <?= isset($errors['marital_status']) ? 'is-invalid' : '' ?>" name="marital_status" required>
                    <option value="">انتخاب...</option>
                    <option value="مجرد" <?= (isset($stepData['marital_status']) && $stepData['marital_status'] === 'مجرد') ? 'selected' : '' ?>>مجرد</option>
                    <option value="متأهل" <?= (isset($stepData['marital_status']) && $stepData['marital_status'] === 'متأهل') ? 'selected' : '' ?>>متأهل</option>
                </select>
                <?php if (!empty($errors['marital_status'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['marital_status']) ?></div><?php endif; ?>
            </div>
            <!-- تعداد فرزندان (نمایش در صورت متأهل) -->
            <div class="col-md-6" id="children-wrapper" style="display: <?= (isset($stepData['marital_status']) && $stepData['marital_status'] === 'متأهل') ? 'block' : 'none' ?>;">
                <label class="form-label">تعداد فرزندان</label>
                <input type="number" class="form-control <?= isset($errors['children_count']) ? 'is-invalid' : '' ?>" name="children_count" value="<?= htmlspecialchars($stepData['children_count'] ?? '0') ?>" min="0">
            </div>
            <!-- وضعیت جسمانی -->
            <div class="col-md-6">
                <label class="form-label">وضعیت جسمانی <span class="text-danger">*</span></label>
                <select class="form-select <?= isset($errors['physical_condition']) ? 'is-invalid' : '' ?>" name="physical_condition" id="physical_condition" required>
                    <option value="سالم" <?= (!isset($stepData['physical_condition']) || $stepData['physical_condition'] === 'سالم') ? 'selected' : '' ?>>سالم</option>
                    <option value="معلولیت" <?= (isset($stepData['physical_condition']) && $stepData['physical_condition'] === 'معلولیت') ? 'selected' : '' ?>>معلولیت</option>
                </select>
            </div>
            <!-- جزئیات معلولیت (نمایش در صورت انتخاب معلولیت) -->
            <div class="col-md-6" id="disability-details-wrapper" style="display: <?= (isset($stepData['physical_condition']) && $stepData['physical_condition'] === 'معلولیت') ? 'block' : 'none' ?>;">
                <label class="form-label">توضیحات معلولیت</label>
                <input type="text" class="form-control <?= isset($errors['disability_details']) ? 'is-invalid' : '' ?>" name="disability_details" value="<?= htmlspecialchars($stepData['disability_details'] ?? '') ?>">
            </div>
            <?php if ($sysActive['income_decile'] ?? true): ?>
            <!-- دهک درآمدی (قابل حذف توسط مدیر از فرم‌ساز) -->
            <div class="col-md-6">
                <label class="form-label">دهک درآمدی (اختیاری)</label>
                <input type="number" class="form-control <?= isset($errors['income_decile']) ? 'is-invalid' : '' ?>" name="income_decile" value="<?= htmlspecialchars($stepData['income_decile'] ?? '') ?>" min="1" max="10">
            </div>
            <?php endif; ?>
        </div>

        <hr>
        <h5 class="fw-bold mb-3">سؤالات تکمیلی</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">آیا خودتان یا عضوی از خانواده دارای پرونده قضایی هستید؟ <span class="text-danger">*</span></label>
                <select class="form-select <?= isset($errors['has_court_case']) ? 'is-invalid' : '' ?>" name="has_court_case" required>
                    <option value="خیر" <?= (!isset($stepData['has_court_case']) || $stepData['has_court_case'] === 'خیر') ? 'selected' : '' ?>>خیر</option>
                    <option value="بله" <?= (isset($stepData['has_court_case']) && $stepData['has_court_case'] === 'بله') ? 'selected' : '' ?>>بله</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">آیا دارای وام فعال هستید؟ <span class="text-danger">*</span></label>
                <select class="form-select <?= isset($errors['has_active_loan']) ? 'is-invalid' : '' ?>" name="has_active_loan" required>
                    <option value="خیر" <?= (!isset($stepData['has_active_loan']) || $stepData['has_active_loan'] === 'خیر') ? 'selected' : '' ?>>خیر</option>
                    <option value="بله" <?= (isset($stepData['has_active_loan']) && $stepData['has_active_loan'] === 'بله') ? 'selected' : '' ?>>بله</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">آیا اقساط معوقه دارید؟ <span class="text-danger">*</span></label>
                <select class="form-select <?= isset($errors['has_overdue_installments']) ? 'is-invalid' : '' ?>" name="has_overdue_installments" required>
                    <option value="خیر" <?= (!isset($stepData['has_overdue_installments']) || $stepData['has_overdue_installments'] === 'خیر') ? 'selected' : '' ?>>خیر</option>
                    <option value="بله" <?= (isset($stepData['has_overdue_installments']) && $stepData['has_overdue_installments'] === 'بله') ? 'selected' : '' ?>>بله</option>
                </select>
            </div>
        </div>

        <?php
        // فیلدهای سفارشی این مرحله (فرم‌ساز پویا)
        $dynStep = 1; $dynValues = $stepData; $dynErrors = $errors;
        require VIEW_PATH . '/registration/_dynamic_fields.php';
        ?>

        <div class="mt-4 d-flex justify-content-between">
            <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary">انصراف</a>
            <button type="submit" class="btn btn-primary px-4">مرحله بعد ←</button>
        </div>
    </form>
</div>

<script>
document.querySelector('select[name="marital_status"]').addEventListener('change', function() {
    document.getElementById('children-wrapper').style.display = this.value === 'متأهل' ? 'block' : 'none';
});
document.getElementById('physical_condition').addEventListener('change', function() {
    document.getElementById('disability-details-wrapper').style.display = this.value === 'معلولیت' ? 'block' : 'none';
});
</script>
<script>
// جابه‌جایی بین فرم حقیقی/حقوقی (فاز ۱۰)
function toggleApplicantType() {
    const legal = document.querySelector('input[name="applicant_type"]:checked')?.value === 'حقوقی';
    document.getElementById('legalBox').style.display = legal ? '' : 'none';
    document.querySelectorAll('#legalBox input, #legalBox select').forEach(el => el.required = legal && !el.closest('.col-md-4, .col-md-6')?.querySelector('.form-text'));
    // برچسب نام → نماینده
    const lbl = document.getElementById('lblFirstName');
    if (lbl) lbl.innerHTML = (legal ? 'نام نمایندهٔ مجاز ' : 'نام ') + '<span class="text-danger">*</span>';
    // کارت‌های انتخاب
    document.querySelectorAll('input[name="applicant_type"]').forEach(r => {
        r.closest('.ownership-option')?.classList.toggle('border-primary', r.checked);
    });
}
document.addEventListener('DOMContentLoaded', toggleApplicantType);
</script>