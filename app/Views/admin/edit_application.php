<?php
/**
 * ویرایش متقاضی — عیناً همسان با صفحهٔ ثبت‌نام دستی (admin/register.php)
 * با تفاوت‌های ضروری:
 *  - مقادیر همهٔ فیلدها از داده‌های متقاضی پیش‌پر می‌شوند
 *  - national_code فقط نمایش (readonly) — لنگر هویت
 *  - نوع خدمت نیز قابل ویرایش است
 *  - بخش «مدارک فعلی» برای مدیریت مدارک موجود (مشاهده/حذف)
 *  - فیلدهای پویا (custom fields) در صورت وجود، نمایش داده می‌شوند
 */
$base = rtrim(BASE_URL, '/') . '/';
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$applicant = $applicant ?? [];
$jobDetail = $jobDetail ?? [];
$propertyDetail = $propertyDetail ?? [];
$documents = $documents ?? [];
$errors = $errors ?? [];
$formData = $formData ?? [];
$licenseGroups = $licenseGroups ?? [];
$services = $services ?? [];
$wizardSteps = $wizardSteps ?? [];
$dynFieldsByStep = $dynFieldsByStep ?? [];
$dynValues = $dynValues ?? [];

// انتخاب منبع مقدار: formData (بازگشت از اعتبارسنجی) → رکورد دیتابیس
$fv = function (string $key, $default = '') use ($formData, $applicant, $jobDetail, $propertyDetail) {
    if (isset($formData[$key])) return $formData[$key];
    if (isset($applicant[$key])) return $applicant[$key];
    if (isset($jobDetail[$key])) return $jobDetail[$key];
    if (isset($propertyDetail[$key])) return $propertyDetail[$key];
    return $default;
};

// تجزیهٔ تاریخ تولد میلادی ذخیره‌شده به سه فیلد شمسی
$birthDateGreg = (string)($applicant['birth_date'] ?? '');
$birthParts = ['year' => '', 'month' => '', 'day' => ''];
if ($birthDateGreg && $birthDateGreg !== '0000-00-00') {
    $jArr = gregorian_to_jalali_array($birthDateGreg);
    $birthParts['year']  = (string)$jArr['year'];
    $birthParts['month'] = (string)$jArr['month'];
    $birthParts['day']   = (string)$jArr['day'];
}
// در صورت بازگشت از اعتبارسنجی، از formData استفاده کن
if (isset($formData['birth_year']))  $birthParts['year']  = (string)$formData['birth_year'];
if (isset($formData['birth_month'])) $birthParts['month'] = (string)$formData['birth_month'];
if (isset($formData['birth_day']))   $birthParts['day']   = (string)$formData['birth_day'];

// merge dynValues با formData (برای فیلدهای پویا در صورت بازگشت از اعتبارسنجی)
foreach ($formData as $k => $v) {
    if (str_starts_with($k, 'dyn_')) {
        $dynValues[$k] = $v;
    }
}

$months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
$currentPersianYear = currentPersianYear();

// برچسب‌های مدارک برای بخش «مدارک فعلی»
$docLabels = [
    'national_card'    => 'کارت ملی',
    'birth_cert'       => 'شناسنامه',
    'ownership_doc'    => 'مدرک مالکیت',
    'vocational_cert'  => 'گواهی فنی‌وحرفه‌ای',
    'education_degree' => 'مدرک تحصیلی',
    'product_photos'   => 'تصویر محصول',
    'optional'         => 'سایر',
];
$regularDocs   = array_filter($documents, fn($d) => $d['doc_type'] !== 'product_photos');
$productPhotos = array_filter($documents, fn($d) => $d['doc_type'] === 'product_photos');
?>
<div class="card p-4">
    <h4 class="fw-bold mb-4">✏️ ویرایش متقاضی #<?= (int)$applicant['id'] ?>
        <small class="text-muted fs-6">کد رهگیری: <code dir="ltr"><?= $e($applicant['tracking_code'] ?? '') ?></code></small>
    </h4>
    <?php if (!empty($errors['system'])): ?>
        <div class="alert alert-danger"><?= implode('<br>', (array)$errors['system']) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['document'])): ?>
        <div class="alert alert-warning py-2 small"><?= is_array($errors['document']) ? implode(' ', $errors['document']) : $e($errors['document']) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="<?= $base ?>admin/update">
        <?= Session::csrfField() ?>
        <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">

        <!-- ========== اطلاعات هویتی ========== -->
        <fieldset class="border rounded p-3 mb-4">
            <legend class="float-none w-auto px-2 h5">اطلاعات هویتی</legend>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">نام <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" name="first_name" value="<?= $e($fv('first_name')) ?>" maxlength="50" required>
                    <?php if (!empty($errors['first_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['first_name']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" name="last_name" value="<?= $e($fv('last_name')) ?>" maxlength="50" required>
                    <?php if (!empty($errors['last_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['last_name']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">کد ملی <small class="text-muted">(غیرقابل ویرایش)</small></label>
                    <input type="text" class="form-control bg-light" value="<?= $e($applicant['national_code'] ?? '') ?>" readonly disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">کد پستی <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['postal_code']) ? 'is-invalid' : '' ?>" name="postal_code" value="<?= $e($fv('postal_code')) ?>" maxlength="10" pattern="\d{10}" required>
                    <?php if (!empty($errors['postal_code'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['postal_code']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">شماره تلفن همراه <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['mobile']) ? 'is-invalid' : '' ?>" name="mobile" value="<?= $e($fv('mobile')) ?>" maxlength="11" placeholder="۰۹xxxxxxxxx" required>
                    <?php if (!empty($errors['mobile'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['mobile']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">نام پدر <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['father_name']) ? 'is-invalid' : '' ?>" name="father_name" value="<?= $e($fv('father_name')) ?>" maxlength="50" required>
                    <?php if (!empty($errors['father_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['father_name']) ?></div><?php endif; ?>
                </div>

                <!-- تاریخ تولد (سه سلکت) -->
                <div class="col-md-6">
                    <label class="form-label">تاریخ تولد <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col">
                            <select class="form-select" name="birth_year" id="birth_year_reg" required>
                                <option value="">سال</option>
                                <?php for ($y = 1300; $y <= $currentPersianYear; $y++): ?>
                                    <option value="<?= $y ?>" <?= $birthParts['year'] === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col">
                            <select class="form-select" name="birth_month" id="birth_month_reg" required>
                                <option value="">ماه</option>
                                <?php foreach ($months as $i => $name):
                                    $m = $i + 1; ?>
                                    <option value="<?= $m ?>" <?= $birthParts['month'] === (string)$m ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <select class="form-select" name="birth_day" id="birth_day_reg" required>
                                <option value="">روز</option>
                                <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <option value="<?= $d ?>" <?= $birthParts['day'] === (string)$d ? 'selected' : '' ?>><?= $d ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <?php if (!empty($errors['birth_date'])): ?><div class="invalid-feedback d-block"><?= implode('<br>', $errors['birth_date']) ?></div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">وضعیت تأهل <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['marital_status']) ? 'is-invalid' : '' ?>" name="marital_status" id="marital_status">
                        <option value="">انتخاب...</option>
                        <option value="مجرد" <?= $fv('marital_status') === 'مجرد' ? 'selected' : '' ?>>مجرد</option>
                        <option value="متأهل" <?= $fv('marital_status') === 'متأهل' ? 'selected' : '' ?>>متأهل</option>
                    </select>
                </div>
                <div class="col-md-6" id="children_wrapper_reg" style="display: <?= $fv('marital_status') === 'متأهل' ? 'block' : 'none' ?>;">
                    <label class="form-label">تعداد فرزندان</label>
                    <input type="number" class="form-control <?= isset($errors['children_count']) ? 'is-invalid' : '' ?>" name="children_count" value="<?= $e($fv('children_count', '0')) ?>" min="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">وضعیت جسمانی <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['physical_condition']) ? 'is-invalid' : '' ?>" name="physical_condition" id="physical_condition_reg">
                        <option value="سالم" <?= $fv('physical_condition') === 'سالم' ? 'selected' : '' ?>>سالم</option>
                        <option value="معلولیت" <?= $fv('physical_condition') === 'معلولیت' ? 'selected' : '' ?>>معلولیت</option>
                    </select>
                </div>
                <div class="col-md-6" id="disability_wrapper_reg" style="display: <?= $fv('physical_condition') === 'معلولیت' ? 'block' : 'none' ?>;">
                    <label class="form-label">توضیحات معلولیت</label>
                    <input type="text" class="form-control <?= isset($errors['disability_details']) ? 'is-invalid' : '' ?>" name="disability_details" value="<?= $e($fv('disability_details')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">دهک درآمدی (اختیاری)</label>
                    <input type="number" class="form-control <?= isset($errors['income_decile']) ? 'is-invalid' : '' ?>" name="income_decile" value="<?= $e($fv('income_decile')) ?>" min="1" max="10">
                </div>
            </div>
            <hr class="mt-3">
            <h6>سؤالات تکمیلی</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">آیا پرونده قضایی دارید؟</label>
                    <select class="form-select" name="has_court_case">
                        <option value="خیر" <?= $fv('has_court_case') === 'خیر' ? 'selected' : '' ?>>خیر</option>
                        <option value="بله" <?= $fv('has_court_case') === 'بله' ? 'selected' : '' ?>>بله</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">وام فعال دارید؟</label>
                    <select class="form-select" name="has_active_loan">
                        <option value="خیر" <?= $fv('has_active_loan') === 'خیر' ? 'selected' : '' ?>>خیر</option>
                        <option value="بله" <?= $fv('has_active_loan') === 'بله' ? 'selected' : '' ?>>بله</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">اقساط معوقه دارید؟</label>
                    <select class="form-select" name="has_overdue_installments">
                        <option value="خیر" <?= $fv('has_overdue_installments') === 'خیر' ? 'selected' : '' ?>>خیر</option>
                        <option value="بله" <?= $fv('has_overdue_installments') === 'بله' ? 'selected' : '' ?>>بله</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <!-- ========== اطلاعات شغلی ========== -->
        <fieldset class="border rounded p-3 mb-4">
            <legend class="float-none w-auto px-2 h5">اطلاعات شغلی</legend>
            <div class="mb-3">
                <label class="form-label">نوع خدمت <span class="text-danger">*</span></label>
                <select class="form-select <?= isset($errors['service_type_id']) ? 'is-invalid' : '' ?>" name="service_type_id" id="service_type_id_reg" onchange="reloadLicenseTitlesByService(this.value)" required>
                    <option value="">انتخاب کنید...</option>
                    <?php foreach ($services as $sv): ?>
                        <option value="<?= (int)$sv['id'] ?>" <?= (int)$fv('service_type_id') === (int)$sv['id'] ? 'selected' : '' ?>><?= $e($sv['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">عنوان مجوز <span class="text-danger">*</span></label>
                <select class="form-select <?= isset($errors['license_title']) ? 'is-invalid' : '' ?>" name="license_title" id="license_title_reg" onchange="toggleCustomLicenseReg()" required>
                    <option value="">انتخاب کنید...</option>
                    <?php foreach ($licenseGroups as $cat => $titles): ?>
                        <optgroup label="<?= $e($cat) ?>">
                            <?php foreach ($titles as $t): ?>
                                <?php if (str_starts_with($t, 'سایر')) continue; ?>
                                <option value="<?= $e($t) ?>" <?= $fv('license_title') === $t ? 'selected' : '' ?>><?= $e($t) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                    <option value="سایر" <?= str_starts_with((string)$fv('license_title'), 'سایر') ? 'selected' : '' ?>>سایر (عنوان دلخواه)</option>
                </select>
                <div id="custom_license_wrapper_reg" style="display: <?= str_starts_with((string)$fv('license_title'), 'سایر') ? 'block' : 'none' ?>; margin-top:0.5rem;">
                    <label>عنوان مجوز دلخواه را وارد کنید:</label>
                    <input type="text" class="form-control <?= isset($errors['custom_license_title']) ? 'is-invalid' : '' ?>" name="custom_license_title" value="<?= $e(str_starts_with((string)$fv('license_title'), 'سایر') ? $fv('license_title') : '') ?>">
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">سابقه (ماه) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control <?= isset($errors['experience_months']) ? 'is-invalid' : '' ?>" name="experience_months" value="<?= $e($fv('experience_months', '0')) ?>" min="0" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">نحوه آموزش <span class="text-danger">*</span></label>
                    <div>
                        <?php foreach (['در خانه','استاد شاگردی','کارگاهی'] as $method): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="training_method" value="<?= $e($method) ?>" <?= $fv('training_method') === $method ? 'checked' : '' ?> required>
                                <label class="form-check-label"><?= $e($method) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- ========== اطلاعات ملک ========== -->
        <fieldset class="border rounded p-3 mb-4">
            <legend class="float-none w-auto px-2 h5">اطلاعات ملک</legend>
            <div class="mb-3">
                <label class="form-label">وضعیت مالکیت <span class="text-danger">*</span></label>
                <div class="row g-3">
                    <?php
                    $ownershipOptions = ['شخص', 'مستأجر', 'سرپرست'];
                    $currentOwnership = $fv('ownership_status', '');
                    foreach ($ownershipOptions as $status): ?>
                    <div class="col-md-4">
                        <div class="card p-3 text-center ownership-option <?= ($currentOwnership === $status) ? 'border-primary' : '' ?>" data-value="<?= $e($status) ?>" style="cursor:pointer;">
                            <?= $e($status) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="ownership_status" id="ownership_status_reg" value="<?= $e($currentOwnership) ?>">
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">استان <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['province']) ? 'is-invalid' : '' ?>" name="province" value="<?= $e($fv('province')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">شهرستان <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['county']) ? 'is-invalid' : '' ?>" name="county" value="<?= $e($fv('county')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">شهر <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>" name="city" value="<?= $e($fv('city')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">بخش / دهستان / روستا</label>
                    <input type="text" class="form-control" name="district" placeholder="بخش" value="<?= $e($fv('district')) ?>">
                    <input type="text" class="form-control mt-2" name="rural_district" placeholder="دهستان" value="<?= $e($fv('rural_district')) ?>">
                    <input type="text" class="form-control mt-2" name="village" placeholder="روستا" value="<?= $e($fv('village')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">شناسه قبض برق <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['electricity_bill_id']) ? 'is-invalid' : '' ?>" name="electricity_bill_id" value="<?= $e($fv('electricity_bill_id')) ?>" maxlength="20" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">خیابان <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['address_street']) ? 'is-invalid' : '' ?>" name="address_street" value="<?= $e($fv('address_street')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">کوچه</label>
                    <input type="text" class="form-control" name="address_alley" value="<?= $e($fv('address_alley')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">پلاک <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['address_plaque']) ? 'is-invalid' : '' ?>" name="address_plaque" value="<?= $e($fv('address_plaque')) ?>" required>
                </div>
            </div>
        </fieldset>

        <!-- ========== فیلدهای پویا (custom fields) ========== -->
        <?php foreach ($dynFieldsByStep as $stepPos => $dynFieldsForStep):
            if (empty($dynFieldsForStep)) continue;
            $stepTitle = $wizardSteps[$stepPos - 1]['title'] ?? ('مرحلهٔ ' . $stepPos);
            // فیلتر خطاهای مربوط به فیلدهای پویا
            $dynErrorsFiltered = [];
            foreach ($errors as $ek => $ev) {
                if (str_starts_with($ek, 'dyn_')) {
                    $dynErrorsFiltered[$ek] = $ev;
                }
            }
        ?>
        <fieldset class="border rounded p-3 mb-4">
            <legend class="float-none w-auto px-2 h5">سؤالات تکمیلی — <?= $e($stepTitle) ?></legend>
            <?php
            // تنظیم متغیرهای مورد انتظار partial _dynamic_fields.php
            $dynStep = $stepPos;
            $dynFields = $dynFieldsForStep;       // ← انتساب ضروری (بدون این خط، partial برمی‌گردد)
            $dynErrorsBackup = $dynErrors ?? [];
            $dynErrors = $dynErrorsFiltered;
            include VIEW_PATH . '/registration/_dynamic_fields.php';
            // پاک‌سازی متغیر برای جلوگیری از نشت به تکرار بعدی
            unset($dynFields);
            $dynErrors = $dynErrorsBackup;
            ?>
        </fieldset>
        <?php endforeach; ?>

        <!-- ========== مدارک فعلی (مدیریت مدارک موجود) ========== -->
        <?php if (!empty($documents)): ?>
        <fieldset class="border rounded p-3 mb-4">
            <legend class="float-none w-auto px-2 h5">مدارک فعلی پرونده (<?= count($documents) ?> فایل)</legend>

            <?php if (!empty($regularDocs)): ?>
            <h6 class="mb-2">مدارک اصلی</h6>
            <div class="row g-2 mb-3">
                <?php foreach ($regularDocs as $doc):
                    $ext = safe_file_extension((string)$doc['file_name']);
                    $isImage = in_array($ext, ['jpg','jpeg','png'], true);
                    $docLabel = $docLabels[$doc['doc_type']] ?? $doc['doc_type'];
                ?>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card h-100 p-2 text-center" style="border-style:dashed;">
                        <?php if ($isImage): ?>
                            <a href="<?= $base ?>document/inline/<?= (int)$doc['id'] ?>" target="_blank">
                                <img src="<?= $base ?>document/thumb/<?= (int)$doc['id'] ?>" class="rounded mb-1" style="height:70px;width:100%;object-fit:cover;" alt="">
                            </a>
                        <?php else: ?>
                            <a href="<?= $base ?>document/inline/<?= (int)$doc['id'] ?>" target="_blank" class="d-block py-3" style="color:var(--terracotta);">
                                <?= icon('file-text','',28) ?>
                            </a>
                        <?php endif; ?>
                        <span class="badge bg-info mb-1" style="font-size:.65rem"><?= $e($docLabel) ?></span>
                        <small class="text-muted text-truncate d-block" dir="ltr" title="<?= $e($doc['original_name']) ?>" style="font-size:.7rem"><?= $e($doc['original_name']) ?></small>
                        <form method="post" action="<?= $base ?>admin/delete-document" onsubmit="return confirm('این مدرک حذف شود؟ فایل فیزیکی نیز پاک خواهد شد.');" class="mt-1">
                            <?= Session::csrfField() ?>
                            <input type="hidden" name="document_id" value="<?= (int)$doc['id'] ?>">
                            <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100" style="font-size:.7rem">حذف</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($productPhotos)): ?>
            <hr>
            <h6 class="mb-2">تصاویر متقاضی با محصولات (<?= count($productPhotos) ?>)</h6>
            <div class="row g-2">
                <?php foreach ($productPhotos as $doc):
                    $ext = safe_file_extension((string)$doc['file_name']);
                    $isImage = in_array($ext, ['jpg','jpeg','png'], true);
                ?>
                <div class="col-4 col-md-2">
                    <div class="card h-100 p-1 text-center" style="border-style:dashed;">
                        <?php if ($isImage): ?>
                            <a href="<?= $base ?>document/inline/<?= (int)$doc['id'] ?>" target="_blank">
                                <img src="<?= $base ?>document/thumb/<?= (int)$doc['id'] ?>" class="img-thumbnail w-100" style="height:60px;object-fit:cover;" alt="">
                            </a>
                        <?php else: ?>
                            <a href="<?= $base ?>document/inline/<?= (int)$doc['id'] ?>" target="_blank" class="d-block py-2" style="color:var(--terracotta);"><?= icon('file-text','',20) ?></a>
                        <?php endif; ?>
                        <form method="post" action="<?= $base ?>admin/delete-document" onsubmit="return confirm('حذف شود؟');" class="mt-1">
                            <?= Session::csrfField() ?>
                            <input type="hidden" name="document_id" value="<?= (int)$doc['id'] ?>">
                            <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100" style="font-size:.65rem">حذف</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </fieldset>
        <?php endif; ?>

        <!-- ========== افزودن/جایگزینی مدارک (همسان با ثبت‌نام دستی) ========== -->
        <fieldset class="border rounded p-3 mb-4">
            <legend class="float-none w-auto px-2 h5">افزودن / جایگزینی مدارک</legend>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="upload-box" onclick="document.getElementById('nat_front_reg').click()">
                        <input type="file" id="nat_front_reg" name="national_card_front" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                        کارت ملی (رو)
                        <p class="small" id="file_nat_front_reg">فایلی انتخاب نشده</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="upload-box" onclick="document.getElementById('nat_back_reg').click()">
                        <input type="file" id="nat_back_reg" name="national_card_back" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                        کارت ملی (پشت)
                        <p class="small" id="file_nat_back_reg">فایلی انتخاب نشده</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="upload-box" onclick="document.getElementById('birth_cert_reg').click()">
                        <input type="file" id="birth_cert_reg" name="birth_cert" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                        تصویر شناسنامه
                        <p class="small" id="file_birth_cert_reg">فایلی انتخاب نشده</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="upload-box" onclick="document.getElementById('ownership_doc_reg').click()">
                        <input type="file" id="ownership_doc_reg" name="ownership_doc" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                        مدرک مالکیت
                        <p class="small" id="file_ownership_doc_reg">فایلی انتخاب نشده</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="upload-box" onclick="document.getElementById('vocational_cert_reg').click()">
                        <input type="file" id="vocational_cert_reg" name="vocational_cert" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                        گواهینامه فنی‌وحرفه‌ای
                        <p class="small" id="file_vocational_cert_reg">فایلی انتخاب نشده</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="upload-box" onclick="document.getElementById('education_degree_reg').click()">
                        <input type="file" id="education_degree_reg" name="education_degree" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                        آخرین مدرک تحصیلی
                        <p class="small" id="file_education_degree_reg">فایلی انتخاب نشده</p>
                    </div>
                </div>
            </div>
            <hr>
            <h6>تصاویر متقاضی با محصولات</h6>
            <div class="row g-2" id="product_photos_container">
                <?php for ($i = 1; $i <= 15; $i++): ?>
                <div class="col-md-2">
                    <div class="upload-box" onclick="document.getElementById('prod_<?= $i ?>').click()">
                        <input type="file" id="prod_<?= $i ?>" name="product_photos[]" accept=".jpg,.jpeg,.png" style="display:none" onchange="showFileName(this)">
                        <p class="small" id="file_prod_<?= $i ?>">عکس <?= $i ?></p>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <hr>
            <h6>مدارک اختیاری (حداکثر ۳ فایل)</h6>
            <div class="row g-3">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                <div class="col-md-4">
                    <div class="upload-box" onclick="document.getElementById('optional_<?= $i ?>_reg').click()">
                        <input type="file" id="optional_<?= $i ?>_reg" name="optional_<?= $i ?>" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                        <p class="small" id="file_optional_<?= $i ?>_reg">سایر <?= $i ?></p>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <small class="text-muted">فقط jpg, jpeg, png, pdf - حداکثر ۲MB</small>
        </fieldset>

        <div class="text-center">
            <button type="submit" class="btn btn-success btn-lg">ذخیره تغییرات</button>
            <a href="<?= $base ?>admin/view/<?= (int)$applicant['id'] ?>" class="btn btn-outline-secondary btn-lg">انصراف</a>
        </div>
    </form>
</div>

<script>
    function showFileName(input) {
        let text = input.files.length > 0 ? input.files[0].name : 'فایلی انتخاب نشده';
        let targetId = 'file_' + input.id;
        let p = document.getElementById(targetId);
        if (p) p.textContent = text;
    }

    // وضعیت مالکیت
    document.querySelectorAll('.ownership-option').forEach(card => {
        card.addEventListener('click', function(){
            document.querySelectorAll('.ownership-option').forEach(c => c.classList.remove('border-primary'));
            this.classList.add('border-primary');
            document.getElementById('ownership_status_reg').value = this.dataset.value;
        });
    });

    document.getElementById('marital_status').addEventListener('change', function() {
        document.getElementById('children_wrapper_reg').style.display = this.value === 'متأهل' ? 'block' : 'none';
    });

    document.getElementById('physical_condition_reg').addEventListener('change', function() {
        document.getElementById('disability_wrapper_reg').style.display = this.value === 'معلولیت' ? 'block' : 'none';
    });

    function toggleCustomLicenseReg() {
        const select = document.getElementById('license_title_reg');
        document.getElementById('custom_license_wrapper_reg').style.display = select.value.startsWith('سایر') ? 'block' : 'none';
    }

    // بارگذاری پویای عناوین مجوز بر اساس سرویس انتخاب‌شده
    const csrfTokenReg = '<?= Session::csrfToken() ?>';
    const initialLicenseReg = <?= json_encode($fv('license_title') ?? '', JSON_UNESCAPED_UNICODE) ?>;
    function reloadLicenseTitlesByService(serviceId) {
        const select = document.getElementById('license_title_reg');
        if (!select) return;
        if (!serviceId || serviceId === '0') {
            select.innerHTML = '<option value="">انتخاب کنید...</option><option value="سایر">سایر (عنوان دلخواه)</option>';
            toggleCustomLicenseReg();
            return;
        }
        fetch('<?= rtrim(BASE_URL, '/') ?>/admin/license-titles/by-service/' + serviceId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfTokenReg }
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const groups = data.groups || {};
                let html = '<option value="">انتخاب کنید...</option>';
                for (const cat in groups) {
                    html += '<optgroup label="' + cat + '">';
                    groups[cat].forEach(t => {
                        if (t.startsWith('سایر')) return;
                        const sel = (t === initialLicenseReg) ? ' selected' : '';
                        html += '<option value="' + t + '"' + sel + '>' + t + '</option>';
                    });
                    html += '</optgroup>';
                }
                html += '<option value="سایر"' + (initialLicenseReg.startsWith('سایر') ? ' selected' : '') + '>سایر (عنوان دلخواه)</option>';
                select.innerHTML = html;
                toggleCustomLicenseReg();
            })
            .catch(() => { /* در صورت خطا، فهرست فعلی دست‌نخورده باقی می‌ماند */ });
    }

    // پویا بودن روزها
    function updateDaysReg() {
        const year = parseInt(document.getElementById('birth_year_reg').value) || 1399;
        const month = parseInt(document.getElementById('birth_month_reg').value) || 1;
        const daySelect = document.getElementById('birth_day_reg');
        const maxDays = (month <= 6) ? 31 : (month <= 11) ? 30 : (((year + 12) % 33 % 4) === 1) ? 30 : 29;
        for (let i = 1; i <= 31; i++) {
            const opt = daySelect.querySelector(`option[value="${i}"]`);
            if (opt) opt.style.display = (i <= maxDays) ? '' : 'none';
        }
        if (parseInt(daySelect.value) > maxDays) daySelect.value = '';
    }
    document.getElementById('birth_year_reg').addEventListener('change', updateDaysReg);
    document.getElementById('birth_month_reg').addEventListener('change', updateDaysReg);
    updateDaysReg();

    // نمایش اولیه custom license
    toggleCustomLicenseReg();
</script>
