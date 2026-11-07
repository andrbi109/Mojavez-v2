<?php
/**
 * صفحهٔ ورود کاربران
 */
$base = rtrim(BASE_URL, '/') . '/';
?>
<div class="row justify-content-center mt-5">
    <div class="col-11 col-sm-8 col-md-6 col-lg-4">
        <div class="card shadow-sm border-0" style="border-radius: 20px; overflow: hidden;">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div style="width:64px;height:64px;margin:0 auto;border-radius:16px;background:linear-gradient(135deg,var(--turquoise),var(--turquoise-dark));display:flex;align-items:center;justify-content:center;font-size:1.8rem;box-shadow:0 4px 16px rgba(0,180,216,.3);">🏛️</div>
                    <h4 class="fw-bold mt-3 mb-1" style="color:var(--navy)">ورود به سامانه</h4>
                    <p class="text-muted small mb-0">برای دسترسی به درخواست‌ها و پرداخت‌های خود وارد شوید</p>
                </div>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= $base ?>login">
                    <?= Session::csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">شماره موبایل</label>
                        <input type="tel" class="form-control form-control-lg" name="mobile"
                               value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>"
                               placeholder="۰۹xxxxxxxxx" pattern="09\d{9}" inputmode="numeric" required
                               autocomplete="tel" autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رمز عبور</label>
                        <input type="password" class="form-control form-control-lg" name="password"
                               placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold">ورود</button>
                    </div>
                </form>

                <hr class="my-4">
                <div class="text-center">
                    <span class="text-muted small">حساب کاربری ندارید؟</span>
                    <a href="<?= $base ?>signup" class="fw-bold text-decoration-none">ثبت‌نام کنید</a>
                </div>
            </div>
        </div>
        <p class="text-center text-muted small mt-3">
            <a href="<?= $base ?>" class="text-decoration-none">← بازگشت به صفحهٔ اصلی</a>
        </p>
    </div>
</div>
