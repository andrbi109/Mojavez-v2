<?php
/**
 * مدیریت فرم ثبت‌نام (فرم‌ساز پویا)
 * ------------------------------------------------------
 * مدیر می‌تواند فیلد سفارشی اضافه/ویرایش/حذف کند، فیلدهای سیستمیِ اختیاری را
 * فعال/غیرفعال کند و ترتیب نمایش فیلدها را تغییر دهد.
 */
$base = rtrim(BASE_URL, '/') . '/';
$stepTitles = [
    1 => '🪪 مرحلهٔ ۱ — اطلاعات هویتی',
    2 => '🏺 مرحلهٔ ۲ — اطلاعات شغلی',
    3 => '🏠 مرحلهٔ ۳ — اطلاعات ملک و مالکیت',
    4 => '📎 مرحلهٔ ۴ — بارگذاری مدارک',
];
$togglable = FormField::TOGGLABLE_SYSTEM_KEYS;
$typeLabels = FormField::TYPES;
?>

<div class="card p-3 p-md-4 mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><?= icon('form', '', 20) ?> مدیریت فرم ثبت‌نام</h4>
            <p class="text-muted small mb-0">
                فیلدهای هر مرحله را اضافه، حذف یا جابه‌جا کنید. تغییرات <strong>بلافاصله</strong>
                در فرم ثبت‌نام عمومی اعمال می‌شوند.
            </p>
        </div>
        <a href="<?= $base ?>admin/form-fields/create" class="btn btn-gold"><?= icon('plus', '', 16) ?> افزودن فیلد جدید</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info py-2 mt-3 mb-0"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
</div>

<?php foreach ($grouped as $stepId => $group): ?>
    <?php $fields = $group['fields'] ?? []; ?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><?= icon('task', 'me-1', 16) ?> <?= htmlspecialchars($group['title'] ?? 'بدون مرحله') ?></span>
        <span class="badge bg-secondary"><?= count($fields) ?> فیلد</span>
    </div>
    <div class="card-body">
        <?php if (empty($fields)): ?>
            <p class="text-muted text-center py-3 mb-0">فیلدی در این مرحله وجود ندارد — <a href="<?= $base ?>admin/form-fields/create?step=<?= (int)$stepId ?>">افزودن</a></p>
        <?php endif; ?>

        <?php foreach ($fields as $i => $f): ?>
            <?php
            $isSystem = (int)$f['is_system'] === 1;
            $isActive = (int)$f['is_active'] === 1;
            $canToggle = !$isSystem || in_array($f['field_key'], $togglable, true);
            $canEdit = !$isSystem; // سیستمی: فقط help/active از مسیر ویرایش محدود
            ?>
            <div class="field-row <?= $isActive ? '' : 'inactive' ?>">
                <!-- جابه‌جایی ترتیب -->
                <div class="d-flex flex-column gap-1">
                    <form method="post" action="<?= $base ?>admin/form-fields/move">
                        <?= Session::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                        <input type="hidden" name="direction" value="up">
                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" <?= $i === 0 ? 'disabled' : '' ?> title="بالا">▲</button>
                    </form>
                    <form method="post" action="<?= $base ?>admin/form-fields/move">
                        <?= Session::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                        <input type="hidden" name="direction" value="down">
                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" <?= $i === count($fields) - 1 ? 'disabled' : '' ?> title="پایین">▼</button>
                    </form>
                </div>

                <!-- اطلاعات فیلد -->
                <div class="flex-grow-1" style="min-width:180px">
                    <strong><?= htmlspecialchars($f['label']) ?></strong>
                    <?php if ((int)$f['is_required'] === 1): ?><span class="text-danger" title="اجباری">*</span><?php endif; ?>
                    <div>
                        <span class="field-type-badge"><?= $typeLabels[$f['field_type']] ?? $f['field_type'] ?></span>
                        <?php if ($isSystem): ?><span class="system-badge">سیستمی</span><?php endif; ?>
                        <?php if (!$isActive): ?><span class="badge bg-secondary">غیرفعال — در فرم نمایش داده نمی‌شود</span><?php endif; ?>
                        <span class="field-key"><?= htmlspecialchars($f['field_key']) ?></span>
                    </div>
                </div>

                <!-- عملیات -->
                <div class="d-flex gap-1 flex-wrap">
                    <?php if ($canToggle): ?>
                    <form method="post" action="<?= $base ?>admin/form-fields/toggle/<?= (int)$f['id'] ?>"
                          onsubmit="return confirm('<?= $isActive ? 'این فیلد از فرم ثبت‌نام حذف (غیرفعال) شود؟' : 'این فیلد دوباره به فرم اضافه (فعال) شود؟' ?>');">
                        <?= Session::csrfField() ?>
                        <button class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                            <?= $isActive ? '🗑 حذف از فرم' : '✔ بازگرداندن' ?>
                        </button>
                    </form>
                    <?php endif; ?>

                    <a href="<?= $base ?>admin/form-fields/edit/<?= (int)$f['id'] ?>" class="btn btn-sm btn-outline-primary">✏️ ویرایش</a>

                    <?php if ($canEdit): ?>
                    <form method="post" action="<?= $base ?>admin/form-fields/delete"
                          onsubmit="return confirm('فیلد «<?= htmlspecialchars(addslashes($f['label'])) ?>» برای همیشه حذف شود؟\nمقادیر ثبت‌شدهٔ متقاضیان برای این فیلد نیز پاک می‌شوند.');">
                        <?= Session::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" title="حذف کامل فیلد سفارشی">✘</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<div class="alert alert-light border small">
    <strong>راهنما:</strong>
    فیلدهای «سیستمی» در هستهٔ سامانه پیاده‌سازی شده‌اند (ستون‌های ثابت پایگاه داده) و حذف‌شدنی نیستند؛
    اما موارد <em>اختیاری</em> آن‌ها (مانند دهستان، روستا، دهک درآمدی، مدارک تکمیلی) با «🗑 حذف از فرم» پنهان می‌شوند.
    فیلدهای سفارشی که خودتان می‌سازید کاملاً قابل ویرایش و حذف‌اند و مقادیرشان برای هر متقاضی ذخیره و در
    پرونده، نسخهٔ چاپی و خروجی CSV نمایش داده می‌شود.
</div>
