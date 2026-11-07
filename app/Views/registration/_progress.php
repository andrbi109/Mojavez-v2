<?php
/**
 * استپر ویزارد — پویا (فاز ۱ نقشهٔ راه)
 * ورودی: $wizardSteps (فهرست مراحل فعال) + $stepPosition (جایگاه ۱-مبنای جاری)
 * اگر $wizardSteps موجود نباشد، حالت سازگار با ۴ مرحلهٔ سنتی.
 */
$base = rtrim(BASE_URL, '/') . '/';
$wizardSteps = $wizardSteps ?? [
    ['title' => 'اطلاعات هویتی', 'icon' => 'id-card'],
    ['title' => 'اطلاعات شغلی', 'icon' => 'vase'],
    ['title' => 'اطلاعات ملک', 'icon' => 'home'],
    ['title' => 'بارگذاری مدارک', 'icon' => 'upload'],
];
$stepPosition = $stepPosition ?? ($currentStep ?? 1);
$total = count($wizardSteps);
$percent = $total > 1 ? (int)round(($stepPosition - 1) * 100 / ($total - 1)) : 100;
?>
<div class="wizard-stepper" aria-label="پیشرفت ثبت‌نام">
    <div class="wizard-steps">
        <?php foreach ($wizardSteps as $i => $ws): ?>
            <?php $pos = $i + 1; $state = $pos < $stepPosition ? 'done' : ($pos === $stepPosition ? 'current' : ''); ?>
            <div class="w-step <?= $state ?>">
                <div class="w-circle">
                    <?= $pos < $stepPosition ? '✓' : icon($ws['icon'] ?? 'form', '', 19) ?>
                </div>
                <div class="w-label"><?= htmlspecialchars($ws['title']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="progress mt-3" role="progressbar" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100" style="height:6px">
        <div class="progress-bar wizard-bar" style="width: <?= max(4, $percent) ?>%;"></div>
    </div>
</div>
