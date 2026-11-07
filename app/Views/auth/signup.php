<?php
/**
 * صفحهٔ ثبت‌نام کاربران
 */
$base = rtrim(BASE_URL, '/') . '/';
$errors = $errors ?? [];
$formData = $formData ?? [];
$err = fn($field) => !empty($errors[$field]) ? '<div class="invalid-feedback d-block">' . implode('<br>', $errors[$field]) . '</div>' : '';
$cls = fn($field) => !empty($errors[$field]) ? 'is-invalid' : '';
?>
<div class="row justify-content-center mt-4 mt-md-5">
    <div class="col-11 col-sm-10 col-md-8 col-lg-6">
        <div class="card shadow-sm border-0" style="border-radius: 20px; overflow: hidden;">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div style="width:64px;height:64px;margin:0 auto;border-radius:16px;background:linear-gradient(135deg,var(--saffron),#D97706);display:flex;align-items:center;justify-content:center;font-size:1.8rem;box-shadow:0 4px 16px rgba(244,163,0,.3);">📝</div>
                    <h4 class="fw-bold mt-3 mb-1" style="color:var(--navy)">ثبت‌نام در سامانه</h4>
                    <p class="text-muted small mb-0">حساب کاربری رایگان بسازید تا درخواست‌های خود را پیگیری کنید</p>
                </div>

                <?php if (!empty($errors['system'])): ?>
                    <div class="alert alert-danger py-2 small"><?php foreach ($errors['system'] as $e) echo htmlspecialchars($e); ?></div>
                <?php endif; ?>

                <form method="post" action="<?= $base ?>signup" novalidate>
                    <?= Session::csrfField() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">نام <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $cls('first_name') ?>" name="first_name"
                                   value="<?= htmlspecialchars($formData['first_name'] ?? '') ?>" required maxlength="50">
                            <?= $err('first_name') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $cls('last_name') ?>" name="last_name"
                                   value="<?= htmlspecialchars($formData['last_name'] ?? '') ?>" required maxlength="50">
                            <?= $err('last_name') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">کد ملی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= $cls('national_code') ?>" name="national_code"
                                   value="<?= htmlspecialchars($formData['national_code'] ?? '') ?>" required
                                   pattern="\d{10}" inputmode="numeric" maxlength="10" dir="ltr">
                            <?= $err('national_code') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">شماره موبایل <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control <?= $cls('mobile') ?>" name="mobile"
                                   value="<?= htmlspecialchars($formData['mobile'] ?? '') ?>" required
                                   placeholder="۰۹xxxxxxxxx" pattern="09\d{9}" inputmode="numeric" maxlength="11" dir="ltr">
                            <?= $err('mobile') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رمز عبور <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?= $cls('password') ?>" name="password"
                                   required minlength="6" autocomplete="new-password">
                            <?= $err('password') ?>
                            <small class="text-muted">حداقل ۶ کاراکتر</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تکرار رمز عبور <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?= $cls('password_confirm') ?>" name="password_confirm"
                                   required minlength="6" autocomplete="new-password">
                            <?= $err('password_confirm') ?>
                        </div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" required id="agreeTerms">
                        <label class="form-check-label small" for="agreeTerms">
                            <a href="<?= $base ?>about" target="_blank">قوانین و شرایط استفاده</a> را می‌پذیرم.
                        </label>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold">ثبت‌نام</button>
                    </div>
                </form>

                <hr class="my-4">
                <div class="text-center">
                    <span class="text-muted small">قبلاً ثبت‌نام کرده‌اید؟</span>
                    <a href="<?= $base ?>login" class="fw-bold text-decoration-none">وارد شوید</a>
                </div>
            </div>
        </div>
        <p class="text-center text-muted small mt-3">
            <a href="<?= $base ?>" class="text-decoration-none">← بازگشت به صفحهٔ اصلی</a>
        </p>
    </div>
</div>
