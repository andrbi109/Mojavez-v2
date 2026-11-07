<?php
/**
 * صفحهٔ موفقیت ثبت‌نام
 * ---------------------------------------------------------
 * ۱) نمایش کد رهگیری با امکان کپی و چاپ
 * ۲) نمایش قالب پیام اطلاع‌رسانی به مدیر سامانه (تکمیل‌شده با نام و کد)
 *    با دکمهٔ کپی و لینک مستقیم ایتا/روبیکا/بله
 */
$username = setting('owner_username', 'Derogaramin');
$links = social_links();
?>
<div class="row justify-content-center mt-3 mt-md-4">
    <div class="col-11 col-md-8 col-lg-7">

        <!-- کارت موفقیت و کد رهگیری -->
        <div class="card p-4 p-md-5 text-center mb-4">
            <div style="width:78px;height:78px;margin:0 auto 1rem;border-radius:50%;background:var(--turquoise-light);display:flex;align-items:center;justify-content:center;font-size:2.4rem;border:3px solid var(--turquoise);">✓</div>
            <h3 class="fw-bold mb-3" style="color:var(--turquoise-dark)">درخواست شما با موفقیت ثبت شد</h3>

            <div class="p-4 rounded-4 mb-3" style="background:var(--saffron-light); border:1.5px dashed var(--saffron);">
                <strong class="d-block mb-2" style="color:var(--saffron-dark)">کد رهگیری شما</strong>
                <span class="tracking-code-display" id="trackingCode"><?= htmlspecialchars($trackingCode) ?></span>
            </div>

            <div class="alert py-2 small text-start" style="background:var(--turquoise-light); color:var(--turquoise-dark);">
                ⚠️ این کد <strong>تنها راه دسترسی</strong> به پروندهٔ شماست (پیگیری، تغییر کد پستی و نسخهٔ چاپی).
                آن را یادداشت کنید یا همین صفحه را چاپ نمایید.
            </div>

            <div class="d-flex justify-content-center flex-wrap gap-2 no-print">
                <button type="button" class="btn btn-outline-primary" onclick="copyText(document.getElementById('trackingCode').textContent.trim(), 'کد رهگیری کپی شد ✔')"><?= icon('copy', '', 15) ?> کپی کد</button>
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><?= icon('print', '', 15) ?> چاپ</button>
                <a href="<?= BASE_URL ?>tracking/status/<?= htmlspecialchars(rawurlencode($trackingCode)) ?>" class="btn btn-outline-info"><?= icon('search', '', 15) ?> پیگیری وضعیت</a>
            </div>
        </div>

        <!-- اطلاع‌رسانی به مدیر سامانه -->
        <div class="card p-4 p-md-5 text-center" style="border:2px solid var(--saffron);">
            <div class="mb-2" style="color:var(--saffron)"><?= icon('megaphone', '', 36) ?></div>
            <h5 class="fw-bold mb-2" style="color:var(--saffron-dark)">گام بعدی: به مدیر سامانه پیام دهید</h5>
            <p class="small text-muted mb-3">
                برای اطلاع <strong><?= htmlspecialchars(owner_name()) ?></strong> از ثبت درخواست خود،
                متن آمادهٔ زیر را کپی کنید و به آیدی
                <strong dir="ltr">@<?= htmlspecialchars($username) ?></strong>
                در <strong>ایتا، روبیکا یا بله</strong> بفرستید:
            </p>

            <div class="msg-template-box text-start mb-3">
                <span class="msg-template-label">پیام آماده — با نام و کد شما تکمیل شده</span>
                <div class="msg-text" id="notifyMsg"><?= htmlspecialchars($notifyMessage ?? build_notify_message($applicantName ?? '', $trackingCode)) ?></div>
            </div>

            <div class="d-flex justify-content-center flex-wrap gap-2 no-print">
                <button type="button" class="btn btn-gold" onclick="copyText(document.getElementById('notifyMsg').textContent.trim(), 'پیام کپی شد ✔\nآن را در ایتا، روبیکا یا بله برای @<?= htmlspecialchars($username) ?> بفرستید.')">
                    <?= icon('copy', '', 16) ?> کپی متن پیام
                </button>
                <?php foreach ($links as $net => $link): ?>
                    <a class="social-chip <?= $net ?>" href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener">
                        <span class="dot"></span> پیام در <?= $link['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <p class="text-center text-muted small mt-3 mb-0 no-print">
            راهنمای کامل فرآیند: <a href="<?= BASE_URL ?>guide">صفحهٔ راهنما</a>
        </p>
    </div>
</div>

<script>
function copyText(text, successMsg) {
    const done = () => alert(successMsg || 'کپی شد ✔');
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(done);
    } else {
        const ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta); done();
    }
}
</script>
