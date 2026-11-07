<div class="row justify-content-center mt-4 mt-md-5">
    <div class="col-11 col-md-6 col-lg-5">
        <div class="card p-4 text-center">
            <div class="mb-3" style="color:var(--turquoise)"><?= icon('search', '', 52) ?></div>
            <h4 class="fw-bold mb-2">پیگیری درخواست</h4>
            <p class="text-muted small">کد رهگیری دریافتی هنگام ثبت‌نام را وارد کنید</p>
            <?php if (!empty($error)): ?>
                <div class="alert" style="background:#FEE2E2; color:var(--danger); border-right:4px solid var(--danger);"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="<?= BASE_URL ?>tracking/search">
                <?= Session::csrfField() ?>
                <div class="mb-3">
                    <input type="text" class="form-control form-control-lg text-center" name="tracking_code"
                           placeholder="مثال: HG-1A2B3C4D" required autofocus
                           pattern="[A-Za-z]{2}-[A-Za-z0-9]{6,12}" inputmode="latin" maxlength="20"
                           title="کد رهگیری با دو حرف انگلیسی و خط تیره شروع می‌شود">
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">جستجو</button>
            </form>
            <hr>
            <p class="small text-muted mb-0">
                کد رهگیری خود را گم کرده‌اید؟ به آیدی
                <strong dir="ltr">@<?= htmlspecialchars(setting('owner_username', 'Derogaramin')) ?></strong>
                در ایتا، روبیکا یا بله پیام دهید (همراه با کد ملی).
            </p>
        </div>
    </div>
</div>