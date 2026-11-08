<?php
/**
 * ویرایش متقاضی — الگوی همسان با ثبت‌نام دستی (admin/register.php)
 * با داده‌های پیش‌فرض متقاضی پر شده.
 * ---------------------------------------------------------------
 * ۴ بخش: هویتی، شغلی، ملک، فیلدهای پویا + گالری مدارک + افزودن مدرک.
 * national_code و tracking_code فقط نمایش (readonly).
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
$dynErrors = $dynErrors ?? [];

// اگر formData موجود است (بازگشت از اعتبارسنجی) از آن استفاده کن، وگرنه از رکوردهای دیتابیس
$fv = function (string $key, $default = '') use ($formData, $applicant, $jobDetail, $propertyDetail) {
    if (isset($formData[$key])) return $formData[$key];
    if (isset($applicant[$key])) return $applicant[$key];
    if (isset($jobDetail[$key])) return $jobDetail[$key];
    if (isset($propertyDetail[$key])) return $propertyDetail[$key];
    return $default;
};

// تجزیهٔ تاریخ تولد میلادی به سه فیلد شمسی (year/month/day)
$birthDateGregorian = (string)($formData['birth_date'] ?? $applicant['birth_date'] ?? '');
$birthParts = ['year' => '', 'month' => '', 'day' => ''];
if ($birthDateGregorian && $birthDateGregorian !== '0000-00-00') {
    $jArr = gregorian_to_jalali_array($birthDateGregorian);
    $birthParts['year'] = (string)$jArr['year'];
    $birthParts['month'] = (string)$jArr['month'];
    $birthParts['day'] = (string)$jArr['day'];
}

// برای فیلدهای پویا: اگر formData شامل dyn_X باشد، آن را به dynValues merge کن
foreach ($formData as $k => $v) {
    if (str_starts_with($k, 'dyn_')) {
        $dynValues[$k] = $v;
    }
}

$months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
$currentPersianYear = currentPersianYear();

// گروه‌بندی مدارک برای گالری
$docLabels = [
    'national_card'   => 'کارت ملی',
    'birth_cert'      => 'شناسنامه',
    'ownership_doc'   => 'مدرک مالکیت',
    'vocational_cert' => 'گواهی فنی‌وحرفه‌ای',
    'education_degree'=> 'مدرک تحصیلی',
    'product_photos'  => 'تصویر محصول',
    'optional'        => 'سایر',
];
$regularDocs = array_filter($documents, fn($d) => $d['doc_type'] !== 'product_photos');
$productPhotos = array_filter($documents, fn($d) => $d['doc_type'] === 'product_photos');
?>
<div class="container-fluid py-4">

    <!-- هدر -->
    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3 class="fw-bold mb-1">✏️ ویرایش متقاضی #<?= (int)$applicant['id'] ?></h3>
                <div class="text-muted small">
                    کد رهگیری: <code dir="ltr"><?= $e($applicant['tracking_code']) ?></code> ·
                    کد ملی: <code dir="ltr"><?= $e($applicant['national_code']) ?></code> ·
                    وضعیت: <span class="badge bg-secondary"><?= $e($applicant['current_status'] ?? '') ?></span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= $base ?>admin/view/<?= (int)$applicant['id'] ?>" class="btn btn-outline-secondary btn-sm"><?= icon('eye','',14) ?> مشاهدهٔ پرونده</a>
                <a href="<?= $base ?>admin/applications" class="btn btn-outline-secondary btn-sm"><?= icon('list','',14) ?> فهرست</a>
            </div>
        </div>
    </div>

    <?php if (!empty($errors['system'])): ?>
        <div class="alert alert-danger"><?= implode('<br>', (array)$errors['system']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= $base ?>admin/update" enctype="multipart/form-data">
        <?= Session::csrfField() ?>
        <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">

        <!-- ========== بخش ۱: اطلاعات هویتی ========== -->
        <fieldset class="border rounded p-3 mb-4">
            <legend class="float-none w-auto px-2 h5">👤 اطلاعات هویتی</legend>
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
                    <input type="text" class="form-control <?= isset($errors['postal_code']) ? 'is-invalid' : '' ?>" name="postal_code" value="<?= $e($fv('postal_code')) ?>" maxlength="10" pattern="\d{10}" inputmode="numeric" required>
                    <?php if (!empty($errors['postal_code'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['postal_code']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">شماره تلفن همراه <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['mobile']) ? 'is-invalid' : '' ?>" name="mobile" value="<?= $e($fv('mobile')) ?>" maxlength="11" pattern="09\d{9}" placeholder="۰۹xxxxxxxxx" inputmode="numeric" required>
                    <?php if (!empty($errors['mobile'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['mobile']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">نام پدر <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['father_name']) ? 'is-invalid' : '' ?>" name="father_name" value="<?= $e($fv('father_name')) ?>" maxlength="50" required>
                    <?php if (!empty($errors['father_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['father_name']) ?></div><?php endif; ?>
                </div>

                <!-- تاریخ تولد -->
                <div class="col-md-6">
                    <label class="form-label">تاریخ تولد <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col">
                            <select class="form-select" name="birth_year" id="birth_year_edit" required>
                                <option value="">سال</option>
                                <?php for ($y = 1300; $y <= $currentPersianYear; $y++): ?>
                                    <option value="<?= $y ?>" <?= $birthParts['year'] === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col">
                            <select class="form-select" name="birth_month" id="birth_month_edit" required>
                                <option value="">ماه</option>
                                <?php foreach ($months as $i => $name):
                                    $m = $i + 1; ?>
                                    <option value="<?= $m ?>" <?= $birthParts['month'] === (string)$m ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <select class="form-select" name="birth_day" id="birth_day_edit" required>
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
                    <select class="form-select <?= isset($errors['marital_status']) ? 'is-invalid' : '' ?>" name="marital_status" id="marital_status_edit">
                        <option value="">انتخاب...</option>
                        <option value="مجرد" <?= $fv('marital_status') === 'مجرد' ? 'selected' : '' ?>>مجرد</option>
                        <option value="متأهل" <?= $fv('marital_status') === 'متأهل' ? 'selected' : '' ?>>متأهل</option>
                    </select>
                </div>
                <div class="col-md-6" id="children_wrapper_edit" style="display: <?= $fv('marital_status') === 'متأهل' ? 'block' : 'none' ?>;">
                    <label class="form-label">تعداد فرزندان</label>
                    <input type="number" class="form-control <?= isset($errors['children_count']) ? 'is-invalid' : '' ?>" name="children_count" value="<?= $e($fv('children_count', '0')) ?>" min="0">
                    <?php if (!empty($errors['children_count'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['children_count']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">وضعیت جسمانی <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['physical_condition']) ? 'is-invalid' : '' ?>" name="physical_condition" id="physical_condition_edit">
                        <option value="سالم" <?= $fv('physical_condition') === 'سالم' ? 'selected' : '' ?>>سالم</option>
                        <option value="معلولیت" <?= $fv('physical_condition') === 'معلولیت' ? 'selected' : '' ?>>معلولیت</option>
                    </select>
                </div>
                <div class="col-md-6" id="disability_wrapper_edit" style="display: <?= $fv('physical_condition') === 'معلولیت' ? 'block' : 'none' ?>;">
                    <label class="form-label">توضیحات معلولیت</label>
                    <input type="text" class="form-control <?= isset($errors['disability_details']) ? 'is-invalid' : '' ?>" name="disability_details" value="<?= $e($fv('disability_details')) ?>">
                    <?php if (!empty($errors['disability_details'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['disability_details']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">دهک درآمدی (اختیاری)</label>
                    <input type="number" class="form-control <?= isset($errors['income_decile']) ? 'is-invalid' : '' ?>" name="income_decile" value="<?= $e($fv('income_decile')) ?>" min="1" max="10">
                </div>
            </div>

            <hr class="mt-3">
            <h6>سؤالات تکمیلی</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">پرونده قضایی <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['has_court_case']) ? 'is-invalid' : '' ?>" name="has_court_case">
                        <option value="خیر" <?= $fv('has_court_case') === 'خیر' ? 'selected' : '' ?>>خیر</option>
                        <option value="بله" <?= $fv('has_court_case') === 'بله' ? 'selected' : '' ?>>بله</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">وام فعال <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['has_active_loan']) ? 'is-invalid' : '' ?>" name="has_active_loan">
                        <option value="خیر" <?= $fv('has_active_loan') === 'خیر' ? 'selected' : '' ?>>خیر</option>
                        <option value="بله" <?= $fv('has_active_loan') === 'بله' ? 'selected' : '' ?>>بله</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">اقساط معوقه <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['has_overdue_installments']) ? 'is-invalid' : '' ?>" name="has_overdue_installments">
                        <option value="خیر" <?= $fv('has_overdue_installments') === 'خیر' ? 'selected' : '' ?>>خیر</option>
                        <option value="بله" <?= $fv('has_overdue_installments') === 'بله' ? 'selected' : '' ?>>بله</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <!-- ========== بخش ۲: اطلاعات شغلی ========== -->
        <fieldset class="border rounded p-3 mb-4">
            <legend class="float-none w-auto px-2 h5">💼 اطلاعات شغلی</legend>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">نوع خدمت <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['service_type_id']) ? 'is-invalid' : '' ?>" name="service_type_id" required>
                        <option value="">انتخاب کنید...</option>
                        <?php foreach ($services as $sv): ?>
                            <option value="<?= (int)$sv['id'] ?>" <?= (int)$fv('service_type_id') === (int)$sv['id'] ? 'selected' : '' ?>><?= $e($sv['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">عنوان مجوز <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['license_title']) ? 'is-invalid' : '' ?>" name="license_title" id="license_title_edit" onchange="toggleCustomLicenseEdit()" required>
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
                    <div id="custom_license_wrapper_edit" style="display: <?= str_starts_with((string)$fv('license_title'), 'سایر') ? 'block' : 'none' ?>; margin-top:0.5rem;">
                        <label class="small">عنوان دلخواه:</label>
                        <input type="text" class="form-control <?= isset($errors['custom_license_title']) ? 'is-invalid' : '' ?>" name="custom_license_title" value="<?= $e(str_starts_with((string)$fv('license_title'), 'سایر') ? $fv('license_title') : '') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">سابقه فعالیت (ماه) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control <?= isset($errors['experience_months']) ? 'is-invalid' : '' ?>" name="experience_months" value="<?= $e($fv('experience_months', '0')) ?>" min="0" required>
                    <?php if (!empty($errors['experience_months'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['experience_months']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">نحوه آموزش <span class="text-danger">*</span></label>
                    <div class="pt-2">
                        <?php foreach (['در خانه','استاد شاگردی','کارگاهی'] as $method): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="training_method" value="<?= $e($method) ?>" <?= $fv('training_method') === $method ? 'checked' : '' ?> required>
                                <label class="form-check-label"><?= $e($method) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($errors['training_method'])): ?><div class="invalid-feedback d-block"><?= implode('<br>', $errors['training_method']) ?></div><?php endif; ?>
                </div>
            </div>
        </fieldset>

        <!-- ========== بخش ۳: اطلاعات ملک ========== -->
        <fieldset class="border rounded p-3 mb-4">
            <legend class="float-none w-auto px-2 h5">🏠 اطلاعات ملک</legend>
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
                <input type="hidden" name="ownership_status" id="ownership_status_edit" value="<?= $e($currentOwnership) ?>">
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">استان <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['province']) ? 'is-invalid' : '' ?>" name="province" value="<?= $e($fv('province')) ?>" required>
                    <?php if (!empty($errors['province'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['province']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">شهرستان <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['county']) ? 'is-invalid' : '' ?>" name="county" value="<?= $e($fv('county')) ?>" required>
                    <?php if (!empty($errors['county'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['county']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">شهر <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>" name="city" value="<?= $e($fv('city')) ?>" required>
                    <?php if (!empty($errors['city'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['city']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">بخش / دهستان / روستا</label>
                    <input type="text" class="form-control mb-2" name="district" placeholder="بخش" value="<?= $e($fv('district')) ?>">
                    <input type="text" class="form-control mb-2" name="rural_district" placeholder="دهستان" value="<?= $e($fv('rural_district')) ?>">
                    <input type="text" class="form-control" name="village" placeholder="روستا" value="<?= $e($fv('village')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">شناسه قبض برق <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['electricity_bill_id']) ? 'is-invalid' : '' ?>" name="electricity_bill_id" value="<?= $e($fv('electricity_bill_id')) ?>" maxlength="20" required>
                    <?php if (!empty($errors['electricity_bill_id'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['electricity_bill_id']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">خیابان <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['address_street']) ? 'is-invalid' : '' ?>" name="address_street" value="<?= $e($fv('address_street')) ?>" required>
                    <?php if (!empty($errors['address_street'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['address_street']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">کوچه</label>
                    <input type="text" class="form-control" name="address_alley" value="<?= $e($fv('address_alley')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">پلاک <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['address_plaque']) ? 'is-invalid' : '' ?>" name="address_plaque" value="<?= $e($fv('address_plaque')) ?>" required>
                    <?php if (!empty($errors['address_plaque'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['address_plaque']) ?></div><?php endif; ?>
                </div>
            </div>
        </fieldset>

        <!-- ========== بخش ۴: فیلدهای پویا (custom fields) ========== -->
        <?php
        // رندر فیلدهای پویا برای هر مرحله
        $hasAnyDynFields = false;
        foreach ($dynFieldsByStep as $stepPos => $dynFields):
            if (empty($dynFields)) continue;
            $hasAnyDynFields = true;
            $stepTitle = $wizardSteps[$stepPos - 1]['title'] ?? ('مرحلهٔ ' . $stepPos);
            ?>
            <fieldset class="border rounded p-3 mb-4">
                <legend class="float-none w-auto px-2 h5">📋 <?= $e($stepTitle) ?> <small class="text-muted fw-normal">(فیلدهای پویا)</small></legend>
                <?php
                // تنظیم متغیرهای مورد انتظار partial _dynamic_fields.php
                $dynStep = $stepPos;
                // فیلتر خطاهای مربوط به فیلدهای پویا (مبتنی بر پیشوند dyn_)
                $dynErrorsFiltered = [];
                foreach ($errors as $ek => $ev) {
                    if (str_starts_with($ek, 'dyn_')) {
                        $dynErrorsFiltered[$ek] = $ev;
                    }
                }
                // متغیرهای محلی ($dynErrors در partial بازنویسی می‌شود، پس از نامی استفاده می‌کنیم که تداخل نکند)
                $localDynErrors = $dynErrorsFiltered;
                // partial از $dynValues، $dynErrors، $dynFields، $dynStep استفاده می‌کند
                $dynErrorsBackup = $dynErrors ?? [];
                $dynErrors = $localDynErrors;
                include VIEW_PATH . '/registration/_dynamic_fields.php';
                $dynErrors = $dynErrorsBackup;
                ?>
            </fieldset>
        <?php endforeach; ?>

        <div class="text-center mb-4">
            <button type="submit" class="btn btn-success btn-lg"><?= icon('check','',16) ?> ذخیرهٔ تغییرات</button>
            <a href="<?= $base ?>admin/view/<?= (int)$applicant['id'] ?>" class="btn btn-outline-secondary btn-lg">انصراف</a>
        </div>
    </form>

    <!-- ========== بخش ۵: گالری مدارک ========== -->
    <fieldset class="border rounded p-3 mb-4">
        <legend class="float-none w-auto px-2 h5">📎 مدارک پرونده</legend>

        <?php if (!empty($errors['document'])): ?>
            <div class="alert alert-danger py-2 small">
                <?= is_array($errors['document']) ? implode(' ', $errors['document']) : $e($errors['document']) ?>
            </div>
        <?php endif; ?>

        <?php if ($regularDocs): ?>
            <h6 class="mb-3">مدارک اصلی</h6>
            <div class="row g-3 mb-4">
                <?php foreach ($regularDocs as $doc): ?>
                    <?php
                    $ext = safe_file_extension((string)$doc['file_name']);
                    $isImage = in_array($ext, ['jpg','jpeg','png'], true);
                    $docLabel = $docLabels[$doc['doc_type']] ?? $doc['doc_type'];
                    ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="card h-100 p-2 text-center" style="border-style:dashed;">
                            <?php if ($isImage): ?>
                                <img src="<?= $base ?>document/thumb/<?= (int)$doc['id'] ?>" class="rounded mb-1" style="height:90px;width:100%;object-fit:cover;" alt="پیش‌نمایش">
                            <?php else: ?>
                                <div class="py-3" style="color:var(--terracotta)"><?= icon('file-text','',34) ?></div>
                            <?php endif; ?>
                            <span class="badge bg-info mb-1"><?= $e($docLabel) ?></span>
                            <small class="text-muted text-truncate d-block" dir="ltr" title="<?= $e($doc['original_name']) ?>"><?= $e($doc['original_name']) ?></small>
                            <small class="text-muted" style="font-size:.68rem"><?= persianDate((string)$doc['uploaded_at']) ?></small>
                            <div class="d-flex gap-1 mt-1">
                                <a href="<?= $base ?>document/view/<?= (int)$doc['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary flex-fill">
                                    <?= icon('eye','',12) ?>
                                </a>
                                <form method="post" action="<?= $base ?>admin/delete-document" onsubmit="return confirm('این مدرک حذف شود؟ فایل فیزیکی نیز پاک خواهد شد.');" class="flex-fill">
                                    <?= Session::csrfField() ?>
                                    <input type="hidden" name="document_id" value="<?= (int)$doc['id'] ?>">
                                    <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100"><?= icon('trash','',12) ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted small mb-3">مدرکی بارگذاری نشده.</p>
        <?php endif; ?>

        <?php if ($productPhotos): ?>
            <h6 class="mb-3">تصاویر محصول (<?= count($productPhotos) ?>)</h6>
            <div class="row g-2 mb-4">
                <?php foreach ($productPhotos as $doc):
                    $ext = safe_file_extension((string)$doc['file_name']);
                    $isImage = in_array($ext, ['jpg','jpeg','png'], true);
                ?>
                    <div class="col-4 col-md-2">
                        <div class="card h-100 p-1 text-center" style="border-style:dashed;">
                            <?php if ($isImage): ?>
                                <a href="<?= $base ?>document/view/<?= (int)$doc['id'] ?>" target="_blank">
                                    <img src="<?= $base ?>document/thumb/<?= (int)$doc['id'] ?>" class="img-thumbnail w-100" style="height:84px;object-fit:cover;" alt="">
                                </a>
                            <?php else: ?>
                                <div class="text-center py-3 border rounded"><?= icon('file-text','',24) ?></div>
                            <?php endif; ?>
                            <form method="post" action="<?= $base ?>admin/delete-document" onsubmit="return confirm('حذف شود؟');" class="mt-1">
                                <?= Session::csrfField() ?>
                                <input type="hidden" name="document_id" value="<?= (int)$doc['id'] ?>">
                                <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100"><?= icon('trash','',12) ?></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- افزودن مدرک جدید -->
        <hr class="my-3">
        <h6 class="mb-2">افزودن مدرک جدید</h6>
        <form method="post" action="<?= $base ?>admin/add-document" enctype="multipart/form-data" class="row g-2 align-items-end">
            <?= Session::csrfField() ?>
            <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">نوع مدرک</label>
                <select class="form-select form-select-sm" name="doc_type">
                    <option value="optional">سایر</option>
                    <option value="national_card">کارت ملی</option>
                    <option value="birth_cert">شناسنامه</option>
                    <option value="ownership_doc">مدرک مالکیت</option>
                    <option value="vocational_cert">گواهی فنی‌وحرفه‌ای</option>
                    <option value="education_degree">مدرک تحصیلی</option>
                    <option value="product_photos">تصویر محصول</option>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label small mb-1">فایل (jpg/png/pdf — حداکثر ۲MB)</label>
                <input type="file" class="form-control form-control-sm" name="document_file" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>
            <div class="col-6 col-md-3">
                <button type="submit" class="btn btn-sm btn-success w-100"><?= icon('upload','',14) ?> آپلود</button>
            </div>
        </form>
    </fieldset>

</div>

<script>
// --- وضعیت مالکیت (کارت‌های کلیک‌شدنی) ---
document.querySelectorAll('.ownership-option').forEach(card => {
    card.addEventListener('click', function(){
        document.querySelectorAll('.ownership-option').forEach(c => c.classList.remove('border-primary'));
        this.classList.add('border-primary');
        document.getElementById('ownership_status_edit').value = this.dataset.value;
    });
});

// --- toggle فیلدهای شرطی ---
document.getElementById('marital_status_edit')?.addEventListener('change', function() {
    document.getElementById('children_wrapper_edit').style.display = this.value === 'متأهل' ? 'block' : 'none';
});

document.getElementById('physical_condition_edit')?.addEventListener('change', function() {
    document.getElementById('disability_wrapper_edit').style.display = this.value === 'معلولیت' ? 'block' : 'none';
});

// --- toggle رشتهٔ مجوز «سایر» ---
function toggleCustomLicenseEdit() {
    const sel = document.getElementById('license_title_edit');
    document.getElementById('custom_license_wrapper_edit').style.display = sel.value.startsWith('سایر') ? 'block' : 'none';
}

// --- پویا بودن روزهای ماه شمسی ---
function updateDaysEdit() {
    const year = parseInt(document.getElementById('birth_year_edit').value) || 1399;
    const month = parseInt(document.getElementById('birth_month_edit').value) || 1;
    const daySelect = document.getElementById('birth_day_edit');
    const maxDays = (month <= 6) ? 31 : (month <= 11) ? 30 : (((year + 12) % 33 % 4) === 1) ? 30 : 29;
    for (let i = 1; i <= 31; i++) {
        const opt = daySelect.querySelector(`option[value="${i}"]`);
        if (opt) opt.style.display = (i <= maxDays) ? '' : 'none';
    }
    if (parseInt(daySelect.value) > maxDays) daySelect.value = '';
}
document.getElementById('birth_year_edit')?.addEventListener('change', updateDaysEdit);
document.getElementById('birth_month_edit')?.addEventListener('change', updateDaysEdit);
updateDaysEdit();

// --- showFileName برای فیلدهای پویا (file) ---
function showFileName(input) {
    let text = input.files.length > 0 ? input.files[0].name : 'فایلی انتخاب نشده';
    let targetId = 'file_' + input.id;
    let p = document.getElementById(targetId);
    if (p) p.textContent = text;
}

toggleCustomLicenseEdit();
</script>
