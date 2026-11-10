<?php
/**
 * تنظیمات سامانه — هویت، شبکه‌های اجتماعی، قالب پیام و صفحهٔ دربارهٔ ما
 * ورودی‌ها: $settings (کلید→مقدار جاری)، $formData (بازگشت خطا)، $errors، $message
 */
$base = rtrim(BASE_URL, '/') . '/';
$fd = $formData ?: null;   // دادهٔ بازگشتی اولویت دارد
$v = fn(string $key, string $def = '') => htmlspecialchars($fd[$key] ?? $settings[$key] ?? $def);
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!empty($errors['system'])): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($errors['system']) ?></div>
<?php endif; ?>

<!-- ===== فرم بارگذاری/حذف تصویر مدیر (مستقل — نباید داخل فرم اصلی باشد) ===== -->
<?php $img = setting('owner_image', ''); ?>
<form method="post" action="<?= $base ?>admin/settings/upload-image" enctype="multipart/form-data" id="imageForm">
    <?= Session::csrfField() ?>
    <input type="hidden" name="remove_image" id="removeImageFlag" value="">

    <div class="row g-4">
        <!-- ===== هویت سامانه ===== -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">🏛️ هویت سامانه</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">نام سامانه <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['site_name']) ? 'is-invalid' : '' ?>"
                               name="site_name" value="<?= $v('site_name', 'سامانهٔ مجوزهای صنایع دستی') ?>" maxlength="100" required>
                        <?php if (!empty($errors['site_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['site_name']) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">نام مالک/مدیر سامانه <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['owner_name']) ? 'is-invalid' : '' ?>"
                               name="owner_name" value="<?= $v('owner_name', 'امین دروگر بزدی') ?>" maxlength="100" required>
                        <div class="form-text">در فوتر، صفحهٔ دربارهٔ ما، راهنما و پیام‌ها نمایش داده می‌شود.</div>
                        <?php if (!empty($errors['owner_name'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['owner_name']) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">آیدی مشترک شبکه‌های اجتماعی</label>
                        <input type="text" class="form-control <?= isset($errors['owner_username']) ? 'is-invalid' : '' ?>"
                               name="owner_username" dir="ltr" value="<?= $v('owner_username', 'Derogaramin') ?>" pattern="[A-Za-z0-9_]{3,40}">
                        <?php if (!empty($errors['owner_username'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['owner_username']) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== تصویر مدیر ===== -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">🖼️ تصویر مدیر سامانه</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-4 flex-wrap">
                        <div class="owner-avatar" style="background:<?= $img ? 'none' : 'linear-gradient(135deg,var(--turquoise),var(--saffron))' ?>;">
                            <?php if ($img !== ''): ?>
                                <img src="<?= $base . htmlspecialchars($img) ?>" alt="تصویر مدیر سامانه">
                            <?php else: ?>
                                <span style="font-size:2.2rem">🧑‍💼</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label small mb-1">بارگذاری تصویر جدید (JPG یا PNG — حداکثر ۲ مگابایت)</label>
                            <div class="input-group input-group-sm">
                                <input type="file" class="form-control" name="owner_image" accept=".jpg,.jpeg,.png" id="ownerImageInput">
                                <button type="submit" class="btn btn-primary" onclick="document.getElementById('removeImageFlag').value=''; return true;"><?= icon('upload', '', 15) ?> بارگذاری</button>
                            </div>
                            <?php if ($img !== ''): ?>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="event.preventDefault(); if(confirm('تصویر مدیر حذف و آیکون پیش‌فرض نمایش داده شود؟')) { document.getElementById('removeImageFlag').value='1'; document.getElementById('ownerImageInput').removeAttribute('required'); document.getElementById('imageForm').submit(); }"><?= icon('trash', '', 14) ?> حذف تصویر</button>
                            </div>
                            <?php endif; ?>
                            <div class="form-text">این تصویر در صفحهٔ «دربارهٔ ما»، کارت معریق مدیر در صفحهٔ اصلی و صفحهٔ ورود نمایش داده می‌شود. در صورت عدم بارگذاری، آیکون پیش‌فرض استفاده می‌شود.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== شبکه‌های اجتماعی ===== -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">📱 آیدی شبکه‌های اجتماعی (لینک‌های ارتباطی)</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">ایتا (Eitaa)</label>
                        <div class="input-group">
                            <span class="input-group-text" dir="ltr">eitaa.com/</span>
                            <input type="text" class="form-control <?= isset($errors['social_eitaa']) ? 'is-invalid' : '' ?>"
                                   name="social_eitaa" dir="ltr" value="<?= $v('social_eitaa', 'Derogaramin') ?>">
                        </div>
                        <?php if (!empty($errors['social_eitaa'])): ?><div class="invalid-feedback d-block"><?= implode('<br>', $errors['social_eitaa']) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">روبیکا (Rubika)</label>
                        <div class="input-group">
                            <span class="input-group-text" dir="ltr">rubika.ir/</span>
                            <input type="text" class="form-control <?= isset($errors['social_rubika']) ? 'is-invalid' : '' ?>"
                                   name="social_rubika" dir="ltr" value="<?= $v('social_rubika', 'Derogaramin') ?>">
                        </div>
                        <?php if (!empty($errors['social_rubika'])): ?><div class="invalid-feedback d-block"><?= implode('<br>', $errors['social_rubika']) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">بله (Bale)</label>
                        <div class="input-group">
                            <span class="input-group-text" dir="ltr">ble.ir/</span>
                            <input type="text" class="form-control <?= isset($errors['social_bale']) ? 'is-invalid' : '' ?>"
                                   name="social_bale" dir="ltr" value="<?= $v('social_bale', 'Derogaramin') ?>">
                        </div>
                        <?php if (!empty($errors['social_bale'])): ?><div class="invalid-feedback d-block"><?= implode('<br>', $errors['social_bale']) ?></div><?php endif; ?>
                        <div class="form-text mt-2">برای حذف یک شبکه، آیدی آن را خالی بگذارید تا لینکش نمایش داده نشود.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== مبالغ (فاز ۳/۶ نقشهٔ راه) ===== -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><?= icon('money', 'me-1') ?> مبالغ و هزینه‌ها</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">هزینهٔ ثبت‌نام (تومان)</label>
                        <input type="number" class="form-control <?= isset($errors['registration_fee_amount']) ? 'is-invalid' : '' ?>"
                               name="registration_fee_amount" min="0" max="1000000000" inputmode="numeric"
                               value="<?= (int)$v('registration_fee_amount', '0') ?>">
                        <div class="form-text">مبلغی که هنگام فعال‌شدن «درخواست پرداخت» به متقاضی نمایش داده می‌شود (۰ = بدون مبلغ مشخص).</div>
                        <?php if (!empty($errors['registration_fee_amount'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['registration_fee_amount']) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">هزینهٔ دریافت مجدد تصویر مجوز (تومان)</label>
                        <input type="number" class="form-control <?= isset($errors['license_reissue_fee_amount']) ? 'is-invalid' : '' ?>"
                               name="license_reissue_fee_amount" min="0" max="1000000000" inputmode="numeric"
                               value="<?= (int)$v('license_reissue_fee_amount', '0') ?>">
                        <div class="form-text">برای درخواست‌های دریافت مجدد تصویر مجوز (پس از بار اول) — فاز ۶ نقشهٔ راه.</div>
                        <?php if (!empty($errors['license_reissue_fee_amount'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['license_reissue_fee_amount']) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== قالب پیام اطلاع‌رسانی ===== -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">📨 قالب پیام اطلاع‌رسانی متقاضی</div>
                <div class="card-body">
                    <label class="form-label">متن پیامی که پس از ثبت‌نام به متقاضی نمایش داده می‌شود</label>
                    <textarea class="form-control <?= isset($errors['success_message_template']) ? 'is-invalid' : '' ?>"
                              name="success_message_template" rows="6" maxlength="2000" dir="rtl"><?= $v('success_message_template', "باسلام\n{name} هستم .\nدر سامانه مدارک رو بارگذاری کردم و کد رهگیری {code} را اخذ نمودم .\nبا تشکر {name}") ?></textarea>
                    <?php if (!empty($errors['success_message_template'])): ?><div class="invalid-feedback"><?= implode('<br>', $errors['success_message_template']) ?></div><?php endif; ?>
                    <div class="form-text">
                        جای‌نماها: <code dir="ltr">{name}</code> = نام و نام خانوادگی متقاضی،
                        <code dir="ltr">{code}</code> = کد رهگیری.
                        این متن به‌صورت تکمیل‌شده در صفحهٔ موفقیت (با دکمهٔ کپی) و در راهنما نمایش داده می‌شود
                        تا متقاضی آن را برای مدیر سامانه در ایتا/روبیکا/بله بفرستد.
                    </div>
                    <div class="msg-template-box mt-3">
                        <span class="msg-template-label">پیش‌نمایش با دادهٔ نمونه</span>
                        <div class="msg-text small" id="tplPreview"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== دربارهٔ ما ===== -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">📄 محتوای صفحهٔ «دربارهٔ ما»</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">عنوان صفحه</label>
                        <input type="text" class="form-control" name="about_title" maxlength="100" value="<?= $v('about_title', 'دربارهٔ ما') ?>">
                    </div>
                    <label class="form-label">متن صفحه</label>
                    <textarea class="form-control" name="about_content" rows="12" maxlength="8000"><?= $v('about_content') ?></textarea>
                    <div class="form-text">
                        قالب‌بندی ساده: پاراگراف‌ها با <strong>یک خط خالی</strong> جدا می‌شوند و خطوطی که با
                        <code dir="ltr">### </code> شروع شوند <strong>سرفصل</strong> می‌شوند.
                    </div>
                    <a href="<?= $base ?>about" target="_blank" class="btn btn-outline-info btn-sm mt-3">👁 پیش‌نمایش صفحهٔ دربارهٔ ما</a>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4 mb-2">
        <button type="submit" class="btn btn-success px-4" onclick="submitSettingsForm(event)">💾 ذخیرهٔ تنظیمات</button>
        <a href="<?= $base ?>admin/dashboard" class="btn btn-outline-secondary">بازگشت به داشبورد</a>
    </div>
</form>

<!-- ===== فرم اصلی ذخیرهٔ تنظیمات (مستقل) ===== -->
<form method="post" action="<?= $base ?>admin/settings/save" id="settingsForm" style="display:none;">
    <?= Session::csrfField() ?>
    <input type="hidden" name="site_name" id="f_site_name">
    <input type="hidden" name="owner_name" id="f_owner_name">
    <input type="hidden" name="owner_username" id="f_owner_username">
    <input type="hidden" name="social_eitaa" id="f_social_eitaa">
    <input type="hidden" name="social_rubika" id="f_social_rubika">
    <input type="hidden" name="social_bale" id="f_social_bale">
    <input type="hidden" name="registration_fee_amount" id="f_registration_fee_amount">
    <input type="hidden" name="license_reissue_fee_amount" id="f_license_reissue_fee_amount">
    <input type="hidden" name="success_message_template" id="f_success_message_template">
    <input type="hidden" name="about_title" id="f_about_title">
    <input type="hidden" name="about_content" id="f_about_content">
</form>

<script>
// پیش‌نمایش زندهٔ قالب پیام با دادهٔ نمونه
function updateTplPreview() {
    const ta = document.querySelector('textarea[name="success_message_template"]');
    const box = document.getElementById('tplPreview');
    if (!ta || !box) return;
    box.textContent = ta.value
        .replaceAll('{name}', 'علی محمدی')
        .replaceAll('{code}', 'HG-1A2B3C4D');
}
document.querySelector('textarea[name="success_message_template"]')?.addEventListener('input', updateTplPreview);
updateTplPreview();

// دکمهٔ «ذخیرهٔ تنظیمات» مقادیر فیلدها را در فرم مخفی کپی کرده و آن را submit می‌کند
function submitSettingsForm(event) {
    event.preventDefault();

    // کپی مقادیر از فرم تصویر به فرم مخفی ذخیرهٔ تنظیمات
    const fields = [
        'site_name', 'owner_name', 'owner_username',
        'social_eitaa', 'social_rubika', 'social_bale',
        'registration_fee_amount', 'license_reissue_fee_amount',
        'success_message_template', 'about_title', 'about_content'
    ];
    fields.forEach(function(name) {
        const src = document.querySelector('[name="' + name + '"]');
        const dst = document.getElementById('f_' + name);
        if (src && dst) {
            dst.value = src.value;
        }
    });

    // submit فرم مخفی
    document.getElementById('settingsForm').submit();
}
</script>
