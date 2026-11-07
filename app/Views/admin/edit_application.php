<?php
$formData = $formData ?? [];
$errors = $errors ?? [];
?>
<div class="card p-4">
    <h4 class="fw-bold mb-4">ویرایش متقاضی #<?= $applicant['id'] ?></h4>
    <form method="post" action="<?= BASE_URL ?>admin/update">
        <?= Session::csrfField() ?>
        <input type="hidden" name="applicant_id" value="<?= $applicant['id'] ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام</label>
                <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" name="first_name" value="<?= htmlspecialchars($formData['first_name'] ?? $applicant['first_name']) ?>">
                <?php if (!empty($errors['first_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['first_name']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">نام خانوادگی</label>
                <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" name="last_name" value="<?= htmlspecialchars($formData['last_name'] ?? $applicant['last_name']) ?>">
                <?php if (!empty($errors['last_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['last_name']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">موبایل</label>
                <input type="text" class="form-control <?= isset($errors['mobile']) ? 'is-invalid' : '' ?>" name="mobile" value="<?= htmlspecialchars($formData['mobile'] ?? $applicant['mobile']) ?>">
                <?php if (!empty($errors['mobile'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['mobile']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">کد پستی</label>
                <input type="text" class="form-control <?= isset($errors['postal_code']) ? 'is-invalid' : '' ?>" name="postal_code" value="<?= htmlspecialchars($formData['postal_code'] ?? $applicant['postal_code']) ?>">
                <?php if (!empty($errors['postal_code'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['postal_code']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">خیابان</label>
                <input type="text" class="form-control" name="address_street" value="<?= htmlspecialchars($formData['address_street'] ?? $propertyDetail['address_street'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">کوچه</label>
                <input type="text" class="form-control" name="address_alley" value="<?= htmlspecialchars($formData['address_alley'] ?? $propertyDetail['address_alley'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">پلاک</label>
                <input type="text" class="form-control" name="address_plaque" value="<?= htmlspecialchars($formData['address_plaque'] ?? $propertyDetail['address_plaque'] ?? '') ?>">
            </div>
        </div>
        <hr>
        <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
        <a href="<?= BASE_URL ?>admin/view/<?= $applicant['id'] ?>" class="btn btn-secondary">انصراف</a>
    </form>

    <hr>
    <h5>افزودن مدرک جدید</h5>
    <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>admin/add-document">
        <?= Session::csrfField() ?>
        <input type="hidden" name="applicant_id" value="<?= $applicant['id'] ?>">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <select class="form-select" name="doc_type">
                    <option value="optional">سایر</option>
                    <option value="national_card">کارت ملی</option>
                    <option value="birth_cert">شناسنامه</option>
                    <option value="ownership_doc">مدرک مالکیت</option>
                    <option value="vocational_cert">گواهینامه فنی‌حرفه‌ای</option>
                    <option value="education_degree">مدرک تحصیلی</option>
                </select>
            </div>
            <div class="col-md-6">
                <input type="file" class="form-control" name="document_file" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100">آپلود</button>
            </div>
        </div>
    </form>
</div>