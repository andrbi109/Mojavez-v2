<?php
/**
 * مرحلهٔ ۴ — بارگذاری مدارک
 * -----------------------------------------------
 * مدارک اجباری + مدارک مشروط (قابل حذف توسط مدیر از فرم‌ساز)
 * + فیلدهای سفارشی پویا (شامل فیلد فایلی سفارشی)
 */
$sysActive = $sysActive ?? [];
$on = fn(string $key): bool => $sysActive[$key] ?? true;
?>
<div class="mb-4">
    <h2 class="fw-bold">بارگذاری مدارک</h2>
    <p class="text-muted small mb-0">فقط jpg، jpeg، png و pdf — حداکثر ۲ مگابایت برای هر فایل</p>
</div>

<?php $currentStep = 4; require VIEW_PATH . '/registration/_progress.php'; ?>

<div class="card p-4">
    <?php if (!empty($errors['files'])): ?>
        <div class="alert alert-danger border-end border-4 py-2">
            <?= implode('<br>', array_map('htmlspecialchars', $errors['files'])) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>register/step/<?= (int)($stepPosition ?? 4) ?>" enctype="multipart/form-data">
        <?= Session::csrfField() ?>

        <!-- ===== مدارک هویتی (اجباری) ===== -->
        <div class="section-heading">
            <span class="ornament">❖</span><h6 class="fw-bold mb-0">مدارک هویتی</h6>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="upload-box" onclick="document.getElementById('national_card_front').click()">
                    <input type="file" id="national_card_front" name="national_card_front" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                    <div class="mb-1">🪪</div>
                    <strong class="small">تصویر کارت ملی (رو) <span class="text-danger">*</span></strong>
                    <p class="text-muted small mb-0" id="file_national_card_front">فایلی انتخاب نشده</p>
                </div>
                <?php if (!empty($errors['national_card'])): ?><div class="invalid-feedback d-block"><?= implode('<br>', $errors['national_card']) ?></div><?php endif; ?>
            </div>
            <?php if ($on('national_card_back')): ?>
            <div class="col-md-6">
                <div class="upload-box" onclick="document.getElementById('national_card_back').click()">
                    <input type="file" id="national_card_back" name="national_card_back" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                    <div class="mb-1">🪪</div>
                    <strong class="small">تصویر کارت ملی (پشت)</strong>
                    <p class="text-muted small mb-0" id="file_national_card_back">فایلی انتخاب نشده</p>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-md-6">
                <div class="upload-box" onclick="document.getElementById('birth_cert').click()">
                    <input type="file" id="birth_cert" name="birth_cert" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                    <div class="mb-1">📄</div>
                    <strong class="small">تصویر شناسنامه <span class="text-danger">*</span></strong>
                    <p class="text-muted small mb-0" id="file_birth_cert">فایلی انتخاب نشده</p>
                </div>
                <?php if (!empty($errors['birth_cert'])): ?><div class="invalid-feedback d-block"><?= implode('<br>', $errors['birth_cert']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <div class="upload-box" onclick="document.getElementById('ownership_doc').click()">
                    <input type="file" id="ownership_doc" name="ownership_doc" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                    <div class="mb-1">🏠</div>
                    <strong class="small">
                        <?= ['شخص' => 'سند مالکیت یا قولنامه', 'مستأجر' => 'اجاره‌نامه یا رضایت‌نامهٔ مالک', 'سرپرست' => 'حکم سرپرستی یا رضایت‌نامهٔ مرجع'][$ownershipStatus ?? ''] ?? 'مدرک محل فعالیت' ?>
                        <span class="text-danger">*</span>
                    </strong>
                    <p class="text-muted small mb-0" id="file_ownership_doc">فایلی انتخاب نشده — (وضعیت مالکیت: <?= htmlspecialchars($ownershipStatus ?? '') ?>)</p>
                </div>
                <?php if (!empty($errors['ownership_doc'])): ?><div class="invalid-feedback d-block"><?= implode('<br>', $errors['ownership_doc']) ?></div><?php endif; ?>
            </div>
        </div>

        <!-- ===== مدارک تکمیلی (مشروط به تنظیم مدیر) ===== -->
        <?php if ($on('vocational_cert') || $on('education_degree')): ?>
        <hr class="my-4">
        <div class="section-heading">
            <span class="ornament">❖</span><h6 class="fw-bold mb-0">مدارک تکمیلی (اختیاری)</h6>
        </div>
        <div class="row g-3">
            <?php if ($on('vocational_cert')): ?>
            <div class="col-md-6">
                <div class="upload-box" onclick="document.getElementById('vocational_cert').click()">
                    <input type="file" id="vocational_cert" name="vocational_cert" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                    <div class="mb-1">🎓</div>
                    <strong class="small">گواهینامهٔ فنی‌وحرفه‌ای</strong>
                    <p class="text-muted small mb-0" id="file_vocational_cert">فایلی انتخاب نشده</p>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($on('education_degree')): ?>
            <div class="col-md-6">
                <div class="upload-box" onclick="document.getElementById('education_degree').click()">
                    <input type="file" id="education_degree" name="education_degree" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                    <div class="mb-1">📜</div>
                    <strong class="small">آخرین مدرک تحصیلی</strong>
                    <p class="text-muted small mb-0" id="file_education_degree">فایلی انتخاب نشده</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ===== تصاویر محصولات (مشروط) ===== -->
        <?php if ($on('product_photos')): ?>
        <hr class="my-4">
        <div class="section-heading">
            <span class="ornament">❖</span><h6 class="fw-bold mb-0">تصاویر متقاضی با محصولات حین تولید</h6>
        </div>
        <p class="text-muted small">حداقل ۵ و حداکثر ۱۵ عکس روشن و واضح بارگذاری کنید.</p>
        <div class="row g-2" id="product-photos">
            <?php for ($i = 1; $i <= 15; $i++): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="upload-box p-2" onclick="document.getElementById('product_photo_<?= $i ?>').click()">
                        <input type="file" id="product_photo_<?= $i ?>" name="product_photos[]" accept=".jpg,.jpeg,.png" style="display:none" onchange="showFileName(this)">
                        <div>📸</div>
                        <strong class="small">عکس <?= $i ?></strong>
                        <p class="text-muted mb-0" style="font-size:.68rem" id="file_product_photo_<?= $i ?>">انتخاب نشده</p>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- ===== مدارک اختیاری (مشروط) ===== -->
        <?php if ($on('optional_1') || $on('optional_2') || $on('optional_3')): ?>
        <hr class="my-4">
        <div class="section-heading">
            <span class="ornament">❖</span><h6 class="fw-bold mb-0">سایر مدارک (حداکثر ۳ فایل)</h6>
        </div>
        <div class="row g-3">
            <?php foreach ([1, 2, 3] as $i): if (!$on("optional_{$i}")) continue; ?>
            <div class="col-md-4">
                <div class="upload-box" onclick="document.getElementById('optional_<?= $i ?>').click()">
                    <input type="file" id="optional_<?= $i ?>" name="optional_<?= $i ?>" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="showFileName(this)">
                    <div class="mb-1">📎</div>
                    <strong class="small">سایر <?= $i ?></strong>
                    <p class="text-muted small mb-0" id="file_optional_<?= $i ?>">فایلی انتخاب نشده</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ===== فیلدهای سفارشی مرحلهٔ ۴ (فرم‌ساز پویا) ===== -->
        <?php
        $dynStep = 4;
        $dynValues = [];      // فایل‌ها از پیش پر نمی‌شوند
        $dynErrors = $errors;
        require VIEW_PATH . '/registration/_dynamic_fields.php';
        ?>

        <div class="mt-4 d-flex justify-content-between">
            <?php $pp = max(1, (int)($stepPosition ?? 4) - 1); ?><a href="<?= BASE_URL ?>register/step/<?= $pp ?>" class="btn btn-outline-secondary">→ مرحلهٔ قبل</a>
            <button type="submit" class="btn btn-gold px-4">✔ تأیید و نهایی‌سازی</button>
        </div>
    </form>
</div>

<script>
function showFileName(input) {
    const hasFile = input.files.length > 0;
    const p = document.getElementById('file_' + input.id);
    if (p) p.textContent = hasFile ? input.files[0].name : (p.dataset.default || 'فایلی انتخاب نشده');
    const box = input.closest('.upload-box');
    if (box) box.classList.toggle('has-file', hasFile);
    if (hasFile) {
        // هشدار حجم بیش از ۲ مگابایت پیش از ارسال
        if (input.files[0].size > 2 * 1024 * 1024) {
            alert('⚠️ حجم فایل «' + input.files[0].name + '» بیش از ۲ مگابایت است و پذیرفته نمی‌شود.');
        }
    }
}
// ذخیرهٔ متن پیش‌فرض برای بازنشانی
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[id^="file_"]').forEach(el => el.dataset.default = el.textContent.trim());
});
</script>
