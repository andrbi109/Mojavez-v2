<?php
/**
 * نمای عمومی مرحلهٔ سفارشی ویزارد (فاز ۱ نقشهٔ راه)
 * ورودی‌ها: $wizardStep, $dynFields, $stepData, $errors, $stepPosition, $wizardSteps
 */
$base = rtrim(BASE_URL, '/') . '/';
$stepPosition = $stepPosition ?? 1;
$total = count($wizardSteps ?? []);
$prevPos = $stepPosition - 1;
?>
<div class="mb-4">
    <h2 class="fw-bold"><?= icon(($wizardStep['icon'] ?? 'form'), 'me-1', 24) ?> <?= htmlspecialchars($wizardStep['title']) ?></h2>
    <?php if (!empty($wizardStep['description'])): ?>
        <p class="text-muted small mb-0"><?= htmlspecialchars($wizardStep['description']) ?></p>
    <?php endif; ?>
</div>

<?php $currentStep = $stepPosition; require VIEW_PATH . '/registration/_progress.php'; ?>

<div class="card p-4">
    <?php if (empty($dynFields)): ?>
        <div class="alert alert-light border py-3 text-center">
            <?= icon('info', '', 22) ?>
            <p class="mb-1 mt-2">این مرحله فعلاً فیلدی ندارد.</p>
            <small class="text-muted">مدیر سامانه می‌تواند از «مدیریت فرم ثبت‌نام» فیلدهای این مرحله را اضافه کند.</small>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= $base ?>register/step/<?= (int)$stepPosition ?>">
        <?= Session::csrfField() ?>

        <?php
        // رندر فیلدهای پویای همین مرحله
        $dynStep = (int)$wizardStep['id'];
        $dynValues = $stepData ?? [];
        $dynErrors = $errors ?? [];
        require VIEW_PATH . '/registration/_dynamic_fields.php';
        ?>

        <div class="mt-4 d-flex justify-content-between">
            <?php if ($prevPos >= 1): ?>
                <a href="<?= $base ?>register/step/<?= $prevPos ?>" class="btn btn-outline-secondary">→ مرحلهٔ قبل</a>
            <?php else: ?>
                <a href="<?= $base ?>register" class="btn btn-outline-secondary">→ شروع</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary px-4">مرحلهٔ بعد ←</button>
        </div>
    </form>
</div>
