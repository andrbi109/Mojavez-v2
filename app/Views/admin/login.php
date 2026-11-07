<?php
/**
 * صفحهٔ ورود پنل مدیریت — طراحی «ایرانی مدرن» با برندینگ مالک سامانه
 */
?>
<div class="row justify-content-center align-items-center py-4 py-md-5">
    <div class="col-11 col-md-5 col-lg-4">
        <div class="card p-4 shadow-sm" style="border-top:4px solid var(--turquoise);">
            <div class="text-center mb-4">
                <div style="width:72px;height:72px;margin:0 auto .8rem;border-radius:20px;background:linear-gradient(135deg,var(--turquoise),var(--turquoise-dark));display:flex;align-items:center;justify-content:center;font-size:2rem;border:1px solid rgba(255,255,255,.2);box-shadow:0 6px 18px rgba(14,124,123,.3);">🏺</div>
                <h4 class="fw-bold mt-2 mb-1" style="color:var(--navy)">پنل مدیریت</h4>
                <p class="text-muted small mb-0"><?= htmlspecialchars(setting('site_name', 'سامانهٔ مجوزهای صنایع دستی')) ?></p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-end border-4 py-2 small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>admin/login">
                <?= Session::csrfField() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">نام کاربری</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus
                           autocomplete="username" maxlength="50" dir="ltr" style="text-align:left">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">رمز عبور</label>
                    <input type="password" class="form-control" id="password" name="password" required
                           autocomplete="current-password" dir="ltr" style="text-align:left">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">ورود به پنل</button>
            </form>

            <div class="text-center mt-3">
                <a href="<?= BASE_URL ?>" class="small text-muted text-decoration-none">← بازگشت به صفحهٔ اصلی</a>
            </div>
        </div>

        <div class="card p-3 mt-3 text-center" style="background:var(--saffron-light); border:1px dashed var(--saffron);">
            <small style="color:var(--saffron-dark)">
                🔒 پس از ۵ بار ورود ناموفق، دسترسی به‌طور موقت قفل می‌شود.
            </small>
        </div>

        <p class="text-center small mt-3 mb-0 text-muted">
            مدیریت سامانه: <strong style="color:var(--navy)"><?= htmlspecialchars(owner_name()) ?></strong>
            <span class="owner-chip ms-1" style="background:transparent;border-color:var(--card-border);color:var(--muted)">@<?= htmlspecialchars(setting('owner_username', 'Derogaramin')) ?></span>
        </p>
    </div>
</div>
