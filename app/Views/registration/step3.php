<div class="mb-4">
    <h2 class="fw-bold">اطلاعات ملک و مالکیت</h2>
</div>

<?php $currentStep = 3; require VIEW_PATH . '/registration/_progress.php'; ?>

<div class="card p-4">
    <form method="post" action="<?= BASE_URL ?>register/step/<?= (int)($stepPosition ?? 3) ?>">
        <?= Session::csrfField() ?>
        <div class="mb-4">
            <label class="form-label">وضعیت مالکیت <span class="text-danger">*</span></label>
            <div class="row g-3">
                <?php foreach (['شخص', 'مستأجر', 'سرپرست'] as $status): ?>
                <div class="col-md-4">
                    <div class="card p-3 text-center ownership-option <?= (isset($stepData['ownership_status']) && $stepData['ownership_status'] === $status) ? 'border-primary' : '' ?>" style="cursor: pointer;" data-value="<?= $status ?>">
                        <div class="fs-4">🏠</div>
                        <div><?= $status ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="ownership_status" id="ownership_status" value="<?= htmlspecialchars($stepData['ownership_status'] ?? '') ?>">
            <?php if (!empty($errors['ownership_status'])): ?>
                <div class="invalid-feedback d-block"><?= implode('<br>', $errors['ownership_status']) ?></div>
            <?php endif; ?>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">استان <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['province']) ? 'is-invalid' : '' ?>"
                       name="province" value="<?= htmlspecialchars($stepData['province'] ?? '') ?>" required>
                <?php if (!empty($errors['province'])): ?>
                    <div class="invalid-feedback"><?= implode('<br>', $errors['province']) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">شهرستان <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['county']) ? 'is-invalid' : '' ?>"
                       name="county" value="<?= htmlspecialchars($stepData['county'] ?? '') ?>" required>
                <?php if (!empty($errors['county'])): ?>
                    <div class="invalid-feedback"><?= implode('<br>', $errors['county']) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">شهر <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>"
                       name="city" value="<?= htmlspecialchars($stepData['city'] ?? '') ?>" required>
                <?php if (!empty($errors['city'])): ?>
                    <div class="invalid-feedback"><?= implode('<br>', $errors['city']) ?></div>
                <?php endif; ?>
            </div>
            <?php if ($sysActive['district'] ?? true): ?>
            <div class="col-md-6">
                <label class="form-label">بخش</label>
                <input type="text" class="form-control" name="district" value="<?= htmlspecialchars($stepData['district'] ?? '') ?>">
            </div>
            <?php endif; ?>
            <?php if ($sysActive['rural_district'] ?? true): ?>
            <div class="col-md-6">
                <label class="form-label">دهستان</label>
                <input type="text" class="form-control" name="rural_district" value="<?= htmlspecialchars($stepData['rural_district'] ?? '') ?>">
            </div>
            <?php endif; ?>
            <?php if ($sysActive['village'] ?? true): ?>
            <div class="col-md-6">
                <label class="form-label">روستا</label>
                <input type="text" class="form-control" name="village" value="<?= htmlspecialchars($stepData['village'] ?? '') ?>">
            </div>
            <?php endif; ?>
            <div class="col-md-6">
                <label class="form-label">خیابان <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['address_street']) ? 'is-invalid' : '' ?>" name="address_street" value="<?= htmlspecialchars($stepData['address_street'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">کوچه <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['address_alley']) ? 'is-invalid' : '' ?>" name="address_alley" value="<?= htmlspecialchars($stepData['address_alley'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">پلاک <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['address_plaque']) ? 'is-invalid' : '' ?>" name="address_plaque" value="<?= htmlspecialchars($stepData['address_plaque'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">شناسه قبض برق <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?= isset($errors['electricity_bill_id']) ? 'is-invalid' : '' ?>"
                       name="electricity_bill_id" value="<?= htmlspecialchars($stepData['electricity_bill_id'] ?? '') ?>" maxlength="20" required>
                <?php if (!empty($errors['electricity_bill_id'])): ?>
                    <div class="invalid-feedback"><?= implode('<br>', $errors['electricity_bill_id']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php
        $dynStep = 3; $dynValues = $stepData; $dynErrors = $errors;
        require VIEW_PATH . '/registration/_dynamic_fields.php';
        ?>

        <div class="mt-4 d-flex justify-content-between">
            <?php $pp = max(1, (int)($stepPosition ?? 3) - 1); ?><a href="<?= BASE_URL ?>register/step/<?= $pp ?>" class="btn btn-outline-secondary">→ مرحلهٔ قبل</a>
            <button type="submit" class="btn btn-primary px-4">مرحله بعد ←</button>
        </div>
    </form>
</div>

<script>
// انتخاب کارتی وضعیت مالکیت
document.querySelectorAll('.ownership-option').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.ownership-option').forEach(c => c.classList.remove('border-primary'));
        this.classList.add('border-primary');
        document.getElementById('ownership_status').value = this.getAttribute('data-value');
    });
});
</script>