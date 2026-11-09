<div class="card p-4">
    <h4 class="fw-bold mb-4">➕ ثبت نام متقاضی جدید (بایگانی)</h4>
    <?php if (!empty($errors['system'])): ?>
        <div class="alert alert-danger"><?= implode('<br>', $errors['system']) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>admin/register">
        <?= Session::csrfField() ?>
        <!-- ========== اطلاعات هویتی ========== -->
        <fieldset class="border rounded p-3 mb-4">
            <legend class="float-none w-auto px-2 h5">اطلاعات هویتی</legend>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">نام <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" name="first_name" value="<?= htmlspecialchars($formData['first_name'] ?? '') ?>" maxlength="50" required>
                    <?php if (!empty($errors['first_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['first_name']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" name="last_name" value="<?= htmlspecialchars($formData['last_name'] ?? '') ?>" maxlength="50" required>
                    <?php if (!empty($errors['last_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['last_name']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">کد ملی <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['national_code']) ? 'is-invalid' : '' ?>" name="national_code" value="<?= htmlspecialchars($formData['national_code'] ?? '') ?>" maxlength="10" pattern="\d{10}" required>
                    <?php if (!empty($errors['national_code'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['national_code']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">کد پستی <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['postal_code']) ? 'is-invalid' : '' ?>" name="postal_code" value="<?= htmlspecialchars($formData['postal_code'] ?? '') ?>" maxlength="10" pattern="\d{10}" required>
                    <?php if (!empty($errors['postal_code'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['postal_code']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">شماره تلفن همراه <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['mobile']) ? 'is-invalid' : '' ?>" name="mobile" value="<?= htmlspecialchars($formData['mobile'] ?? '') ?>" maxlength="11" placeholder="۰۹xxxxxxxxx" required>
                    <?php if (!empty($errors['mobile'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['mobile']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">نام پدر <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['father_name']) ? 'is-invalid' : '' ?>" name="father_name" value="<?= htmlspecialchars($formData['father_name'] ?? '') ?>" maxlength="50" required>
                    <?php if (!empty($errors['father_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['father_name']) ?></div><?php endif; ?>
                </div>

                <!-- تاریخ تولد (سه سلکت) -->
                <div class="col-md-6">
                    <label class="form-label">تاریخ تولد <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col">
                            <select class="form-select" name="birth_year" id="birth_year_reg" required>
                                <option value="">سال</option>
                                <?php
                                $currentPersianYear = currentPersianYear();
                                for ($y = 1300; $y <= $currentPersianYear; $y++):
                                    $sel = (isset($formData['birth_year']) && $formData['birth_year'] == $y) ? 'selected' : '';
                                ?>
                                    <option value="<?= $y ?>" <?= $sel ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col">
                            <select class="form-select" name="birth_month" id="birth_month_reg" required>
                                <option value="">ماه</option>
                                <?php
                                $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                                foreach ($months as $i => $name):
                                    $m = $i + 1;
                                    $sel = (isset($formData['birth_month']) && $formData['birth_month'] == $m) ? 'selected' : '';
                                ?>
                                    <option value="<?= $m ?>" <?= $sel ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <select class="form-select" name="birth_day" id="birth_day_reg" required>
                                <option value="">روز</option>
                                <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <option value="<?= $d ?>" <?= (isset($formData['birth_day']) && $formData['birth_day'] == $d) ? 'selected' : '' ?>><?= $d ?></option>
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
                        <option value="مجرد" <?= (isset($formData['marital_status']) && $formData['marital_status'] == 'مجرد') ? 'selected' : '' ?>>مجرد</option>
                        <option value="متأهل" <?= (isset($formData['marital_status']) && $formData['marital_status'] == 'متأهل') ? 'selected' : '' ?>>متأهل</option>
                    </select>
                </div>
                <div class="col-md-6" id="children_wrapper_reg" style="display: <?= (isset($formData['marital_status']) && $formData['marital_status'] == 'متأهل') ? 'block' : 'none' ?>;">
                    <label class="form-label">تعداد فرزندان</label>
                    <input type="number" class="form-control <?= isset($errors['children_count']) ? 'is-invalid' : '' ?>" name="children_count" value="<?= htmlspecialchars($formData['children_count'] ?? '0') ?>" min="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">وضعیت جسمانی <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['physical_condition']) ? 'is-invalid' : '' ?>" name="physical_condition" id="physical_condition_reg">
                        <option value="سالم" <?= (!isset($formData['physical_condition']) || $formData['physical_condition'] == 'سالم') ? 'selected' : '' ?>>سالم</option>
                        <option value="معلولیت" <?= (isset($formData['physical_condition']) && $formData['physical_condition'] == 'معلولیت') ? 'selected' : '' ?>>معلولیت</option>
                    </select>
                </div>
                <div class="col-md-6" id="disability_wrapper_reg" style="display: <?= (isset($formData['physical_condition']) && $formData['physical_condition'] == 'معلولیت') ? 'block' : 'none' ?>;">
                    <label class="form-label">توضیحات معلولیت</label>
                    <input type="text" class="form-control <?= isset($errors['disability_details']) ? 'is-invalid' : '' ?>" name="disability_details" value="<?= htmlspecialchars($formData['disability_details'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">دهک درآمدی (اختیاری)</label>
                    <input type="number" class="form-control <?= isset($errors['income_decile']) ? 'is-invalid' : '' ?>" name="income_decile" value="<?= htmlspecialchars($formData['income_decile'] ?? '') ?>" min="1" max="10">
                </div>
            </div>
            <hr class="mt-3">
            <h6>سؤالات تکمیلی</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">آیا پرونده قضایی دارید؟</label>
                    <select class="form-select" name="has_court_case">
                        <option value="خیر" <?= (!isset($formData['has_court_case']) || $formData['has_court_case'] == 'خیر') ? 'selected' : '' ?>>خیر</option>
                        <option value="بله" <?= (isset($formData['has_court_case']) && $formData['has_court_case'] == 'بله') ? 'selected' : '' ?>>بله</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">وام فعال دارید؟</label>
                    <select class="form-select" name="has_active_loan">
                        <option value="خیر" <?= (!isset($formData['has_active_loan']) || $formData['has_active_loan'] == 'خیر') ? 'selected' : '' ?>>خیر</option>
                        <option value="بله" <?= (isset($formData['has_active_loan']) && $formData['has_active_loan'] == 'بله') ? 'selected' : '' ?>>بله</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">اقساط معوقه دارید؟</label>
                    <select class="form-select" name="has_overdue_installments">
                        <option value="خیر" <?= (!isset($formData['has_overdue_installments']) || $formData['has_overdue_installments'] == 'خیر') ? 'selected' : '' ?>>خیر</option>
                        <option value="بله" <?= (isset($formData['has_overdue_installments']) && $formData['has_overdue_installments'] == 'بله') ? 'selected' : '' ?>>بله</option>
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
                    <?php foreach (($services ?? []) as $sv): ?>
                        <option value="<?= (int)$sv['id'] ?>" <?= (int)($formData['service_type_id'] ?? 0) === (int)$sv['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sv['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">عنوان مجوز <span class="text-danger">*</span></label>
                <select class="form-select <?= isset($errors['license_title']) ? 'is-invalid' : '' ?>" name="license_title" id="license_title_reg" onchange="toggleCustomLicenseReg()" required>
                    <option value="">انتخاب کنید...</option>
                    <?php foreach (($licenseGroups ?? []) as $cat => $titles): ?>
                        <optgroup label="<?= htmlspecialchars($cat) ?>">
                            <?php foreach ($titles as $t): ?>
                                <?php if (str_starts_with($t, 'سایر')) continue; ?>
                                <option value="<?= htmlspecialchars($t) ?>" <?= ($formData['license_title'] ?? '') === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                    <option value="سایر" <?= str_starts_with($formData['license_title'] ?? '', 'سایر') ? 'selected' : '' ?>>سایر (عنوان دلخواه)</option>
                </select>
                <div id="custom_license_wrapper_reg" style="display: <?= (isset($formData['license_title']) && str_starts_with($formData['license_title'] ?? '', 'سایر')) ? 'block' : 'none' ?>; margin-top:0.5rem;">
                    <label>عنوان مجوز دلخواه را وارد کنید:</label>
                    <input type="text" class="form-control <?= isset($errors['custom_license_title']) ? 'is-invalid' : '' ?>" name="custom_license_title" value="<?= htmlspecialchars($formData['custom_license_title'] ?? '') ?>">
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">سابقه (ماه) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control <?= isset($errors['experience_months']) ? 'is-invalid' : '' ?>" name="experience_months" value="<?= htmlspecialchars($formData['experience_months'] ?? '') ?>" min="0" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">نحوه آموزش <span class="text-danger">*</span></label>
                    <div>
                        <?php foreach (['در خانه','استاد شاگردی','کارگاهی'] as $method): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="training_method" value="<?= $method ?>" <?= (isset($formData['training_method']) && $formData['training_method'] == $method) ? 'checked' : '' ?>>
                                <label class="form-check-label"><?= $method ?></label>
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
                    $currentOwnership = $formData['ownership_status'] ?? '';
                    foreach ($ownershipOptions as $status): ?>
                    <div class="col-md-4">
                        <div class="card p-3 text-center ownership-option <?= ($currentOwnership === $status) ? 'border-primary' : '' ?>" data-value="<?= $status ?>" style="cursor:pointer;">
                            <?= $status ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="ownership_status" id="ownership_status_reg" value="<?= htmlspecialchars($currentOwnership) ?>">
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">استان <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['province']) ? 'is-invalid' : '' ?>" name="province" value="<?= htmlspecialchars($formData['province'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">شهرستان <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['county']) ? 'is-invalid' : '' ?>" name="county" value="<?= htmlspecialchars($formData['county'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">شهر <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>" name="city" value="<?= htmlspecialchars($formData['city'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">بخش / دهستان / روستا</label>
                    <input type="text" class="form-control" name="district" placeholder="بخش" value="<?= htmlspecialchars($formData['district'] ?? '') ?>">
                    <input type="text" class="form-control mt-2" name="rural_district" placeholder="دهستان" value="<?= htmlspecialchars($formData['rural_district'] ?? '') ?>">
                    <input type="text" class="form-control mt-2" name="village" placeholder="روستا" value="<?= htmlspecialchars($formData['village'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">شناسه قبض برق <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['electricity_bill_id']) ? 'is-invalid' : '' ?>" name="electricity_bill_id" value="<?= htmlspecialchars($formData['electricity_bill_id'] ?? '') ?>" maxlength="20" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">خیابان <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['address_street']) ? 'is-invalid' : '' ?>" name="address_street" value="<?= htmlspecialchars($formData['address_street'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">کوچه</label>
                    <input type="text" class="form-control" name="address_alley" value="<?= htmlspecialchars($formData['address_alley'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">پلاک <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['address_plaque']) ? 'is-invalid' : '' ?>" name="address_plaque" value="<?= htmlspecialchars($formData['address_plaque'] ?? '') ?>" required>
                </div>
            </div>
        </fieldset>

        <!-- ========== مدارک (همسان با ثبت‌نام عمومی) ========== -->
        <fieldset class="border rounded p-3 mb-4">
            <legend class="float-none w-auto px-2 h5">مدارک</legend>
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
            <h6>تصاویر متقاضی با محصولات (حداقل ۵)</h6>
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
            <button type="submit" class="btn btn-success btn-lg">ثبت متقاضی</button>
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
        document.getElementById('custom_license_wrapper_reg').style.display = select.value .startsWith('سایر') ? 'block' : 'none';
    }

    // بارگذاری پویای عناوین مجوز بر اساس سرویس انتخاب‌شده
    const csrfTokenReg = '<?= Session::csrfToken() ?>';
    const initialLicenseReg = <?= json_encode($formData['license_title'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
    function reloadLicenseTitlesByService(serviceId) {
        const select = document.getElementById('license_title_reg');
        if (!select) return;
        if (!serviceId || serviceId === '0') {
            // فقط گزینهٔ «انتخاب کنید» و «سایر» باقی بماند
            select.innerHTML = '<option value="">انتخاب کنید...</option><option value="سایر">سایر (عنوان دلخواه)</option>';
            toggleCustomLicenseReg();
            return;
        }
        fetch('<?= $base = rtrim(BASE_URL, '/') . '/'; ?>admin/license-titles/by-service/' + serviceId, {
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