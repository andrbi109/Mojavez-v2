<div class="mb-4">
    <h2 class="fw-bold">اطلاعات شغلی</h2>
</div>

<?php $currentStep = 2; require VIEW_PATH . '/registration/_progress.php'; ?>

<div class="card p-4">
    <form method="post" action="<?= BASE_URL ?>register/step/<?= (int)($stepPosition ?? 2) ?>">
        <?= Session::csrfField() ?>
        <!-- بخش انتخاب عنوان مجوز -->
        <div class="mb-3">
            <label class="form-label">عنوان مجوز درخواستی <span class="text-danger">*</span></label>
            <select class="form-select <?= isset($errors['license_title']) ? 'is-invalid' : '' ?>" name="license_title" id="license_title" onchange="toggleCustomLicense()" required>
                <option value="">انتخاب کنید...</option>
                <?php foreach (($licenseGroups ?? []) as $cat => $titles): ?>
                    <optgroup label="<?= htmlspecialchars($cat) ?>">
                        <?php foreach ($titles as $t): ?>
                            <?php if (str_starts_with($t, 'سایر')) continue; /* «سایر» انتهای فهرست */ ?>
                            <option value="<?= htmlspecialchars($t) ?>" <?= ($stepData['license_title'] ?? '') === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
                <option value="سایر" <?= str_starts_with($stepData['license_title'] ?? '', 'سایر') ? 'selected' : '' ?>>سایر (عنوان دلخواه)</option>
            </select>
            <div id="custom-license-wrapper" style="display:<?= (isset($stepData['license_title']) && str_starts_with($stepData['license_title'] ?? '', 'سایر')) ? 'block' : 'none' ?>; margin-top:0.5rem;">
                <label>عنوان مجوز مورد نظر خود را وارد کنید:</label>
                <input type="text" class="form-control <?= isset($errors['custom_license_title']) ? 'is-invalid' : '' ?>" name="custom_license_title" value="<?= htmlspecialchars($stepData['custom_license_title'] ?? '') ?>">
            </div>
            <?php if (!empty($errors['license_title'])): ?>
                <div class="invalid-feedback d-block"><?= implode('<br>', $errors['license_title']) ?></div>
            <?php endif; ?>
        </div>

        <script>
        function toggleCustomLicense() {
            const select = document.getElementById('license_title');
            const wrapper = document.getElementById('custom-license-wrapper');
            if (select.value.startsWith('سایر')) {
                wrapper.style.display = 'block';
            } else {
                wrapper.style.display = 'none';
            }
        }
        </script>

        <div class="mb-3">
            <label class="form-label">سابقه فعالیت (ماه) <span class="text-danger">*</span></label>
            <input type="number" class="form-control <?= isset($errors['experience_months']) ? 'is-invalid' : '' ?>"
                   name="experience_months" value="<?= htmlspecialchars($stepData['experience_months'] ?? '') ?>" min="0" required>
            <?php if (!empty($errors['experience_months'])): ?>
                <div class="invalid-feedback"><?= implode('<br>', $errors['experience_months']) ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-4">
            <label class="form-label">نحوه آموزش و آشنایی با حرفه <span class="text-danger">*</span></label>
            <div>
                <?php foreach (['در خانه', 'استاد شاگردی', 'کارگاهی'] as $method): ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="training_method" value="<?= $method ?>"
                               id="train_<?= $method ?>" <?= (isset($stepData['training_method']) && $stepData['training_method'] === $method) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="train_<?= $method ?>"><?= $method ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($errors['training_method'])): ?>
                <div class="invalid-feedback"><?= implode('<br>', $errors['training_method']) ?></div>
            <?php endif; ?>
        </div>

        <?php
        $dynStep = 2; $dynValues = $stepData; $dynErrors = $errors;
        require VIEW_PATH . '/registration/_dynamic_fields.php';
        ?>

        <div class="d-flex justify-content-between mt-4">
            <?php $pp = max(1, (int)($stepPosition ?? 2) - 1); ?><a href="<?= BASE_URL ?>register/step/<?= $pp ?>" class="btn btn-outline-secondary">→ مرحلهٔ قبل</a>
            <button type="submit" class="btn btn-primary px-4">مرحله بعد ←</button>
        </div>
    </form>
</div>