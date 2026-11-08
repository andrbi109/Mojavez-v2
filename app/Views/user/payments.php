<?php
/**
 * پرداخت‌های من — پنل کاربری
 * --------------------------------------------------
 * نمایش:
 *  ۱) پرداخت‌های نیازمند رسید (در انتظار رسید / رد شده) → با فرم آپلود رسید کامل
 *  ۲) پرداخت‌های در انتظار بررسی → پیام «در حال بررسی»
 *  ۳) پرداخت‌های تأییدشده → پیام موفقیت
 *  ۴) سابقهٔ کامل پرداخت‌ها
 */
$base = rtrim(BASE_URL, '/') . '/';
$pays = $payments ?? [];
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$csrfField = Session::csrfField();

// دریافت کانال‌های پرداخت فعال
$channels = [];
try { $channels = (new PaymentChannel())->getActive(); } catch (\Throwable $ex) {}

// دسته‌بندی پرداخت‌ها
$needReceiptPays = array_filter($pays, fn($p) => in_array($p['status'] ?? '', ['در انتظار رسید', 'رد شده'], true));
$inReviewPays   = array_filter($pays, fn($p) => ($p['status'] ?? '') === 'در انتظار بررسی');
$approvedPays   = array_filter($pays, fn($p) => ($p['status'] ?? '') === 'تأیید شده');

// ماه‌های شمسی برای فرم تاریخ
$months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
?>
<style>
.pay-card{border-radius:16px;padding:1.25rem 1.5rem;margin-bottom:1rem;border:1px solid var(--border-color,rgba(0,0,0,.08))}
.pay-pending{background:linear-gradient(135deg,rgba(244,163,0,.08),rgba(244,163,0,.02));border-color:rgba(244,163,0,.3)}
.pay-review{background:rgba(0,180,216,.06);border-color:rgba(0,180,216,.2)}
.pay-approved{background:rgba(40,167,69,.06);border-color:rgba(40,167,69,.2)}
.pay-rejected{background:rgba(220,53,69,.05);border-color:rgba(220,53,69,.2)}
.amount-big{font-size:1.5rem;font-weight:800;color:var(--saffron-dark,#D97706)}
[data-bs-theme=dark] .pay-pending{background:rgba(244,163,0,.08);border-color:rgba(244,163,0,.25)}
[data-bs-theme=dark] .amount-big{color:var(--saffron,#E3B255)}
.channel-card{background:var(--card-bg,#fff);border:1px solid var(--border-color,rgba(0,0,0,.06));border-radius:10px;padding:.6rem .8rem;cursor:pointer;transition:.2s}
.channel-card:hover{border-color:var(--turquoise);box-shadow:0 2px 8px rgba(0,180,216,.15)}
.channel-card.selected{border-color:var(--turquoise);background:rgba(0,180,216,.05)}
</style>

<?php
$flashErr = Session::get('payment_error');
$flashOk  = Session::get('payment_success');
Session::remove('payment_error');
Session::remove('payment_success');
?>
<?php if($flashErr): ?><div class="alert alert-danger"><?= $e($flashErr) ?></div><?php endif;?>
<?php if($flashOk):  ?><div class="alert alert-success"><?= $e($flashOk) ?></div><?php endif;?>

<!-- پرداخت‌های نیازمند رسید (در انتظار رسید یا رد شده) -->
<?php foreach($needReceiptPays as $pay):
    $isRejected = ($pay['status'] ?? '') === 'رد شده';
    $displayAmount = !empty($pay['final_amount']) ? (int)$pay['final_amount'] : (int)$pay['amount'];
?>
<div class="pay-card pay-pending <?= $isRejected ? 'pay-rejected' : '' ?>">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <span class="badge bg-<?= $isRejected ? 'danger' : 'warning text-dark' ?> mb-1">
                <?= $isRejected ? 'رد شده — مجدداً پرداخت کنید' : 'نیازمند پرداخت' ?>
            </span>
            <h5 class="fw-bold mb-0">پرداخت هزینهٔ ثبت‌نام</h5>
            <code class="small text-muted" dir="ltr"><?= $e($pay['tracking_code'] ?? '') ?></code>
        </div>
        <div class="text-end">
            <div class="amount-big"><?= number_format($displayAmount) ?> تومان</div>
            <?php if(!empty($pay['final_amount']) && (int)$pay['final_amount'] !== (int)$pay['amount']):?>
                <small class="text-muted">مبلغ اصلی: <s><?= number_format((int)$pay['amount']) ?></s> ت</small>
            <?php endif;?>
        </div>
    </div>

    <?php if($isRejected && !empty($pay['reject_reason'])):?>
        <div class="alert alert-danger py-2 small mb-3">
            <strong>دلیل رد:</strong> <?= $e($pay['reject_reason']) ?>
        </div>
    <?php endif;?>

    <?php if(!empty($channels)):?>
    <!-- کانال‌های پرداخت -->
    <h6 class="fw-bold small mb-2"><?= icon('info','',14) ?> مبلغ را به یکی از حساب‌های زیر واریز کنید:</h6>
    <div class="row g-2 mb-3">
        <?php foreach($channels as $ch):?>
        <div class="col-md-6">
            <div class="channel-card" data-channel-id="<?= (int)$ch['id'] ?>" onclick="selectChannel(this, <?= (int)$ch['id'] ?>)">
                <div class="fw-bold small"><?= $e($ch['title']) ?> <span class="badge bg-secondary"><?= $e($ch['channel_type']) ?></span></div>
                <div class="small text-muted" dir="ltr" style="text-align:right"><?= $e($ch['account_info'] ?? '') ?></div>
            </div>
        </div>
        <?php endforeach;?>
    </div>

    <!-- فرم کامل آپلود رسید -->
    <form method="post" action="<?= $base ?>tracking/payments/submit" enctype="multipart/form-data" id="payForm_<?= (int)$pay['id'] ?>">
        <?= $csrfField ?>
        <input type="hidden" name="tracking_code" value="<?= $e($pay['tracking_code'] ?? '') ?>">
        <input type="hidden" name="payment_channel_id" id="channel_input_<?= (int)$pay['id'] ?>" value="" required>
        <input type="hidden" name="amount" value="<?= $displayAmount ?>">

        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">کد پیگیری بانکی <span class="text-muted">(اختیاری)</span></label>
                <input type="text" class="form-control form-control-sm" name="reference_code" dir="ltr" placeholder="کد تراکنش">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">تاریخ واریز <span class="text-danger">*</span></label>
                <div class="row g-1">
                    <div class="col-4">
                        <select class="form-select form-select-sm" name="paid_year" required>
                            <option value="">سال</option>
                            <?php for($y = 1400; $y <= 1420; $y++): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor;?>
                        </select>
                    </div>
                    <div class="col-4">
                        <select class="form-select form-select-sm" name="paid_month" required>
                            <option value="">ماه</option>
                            <?php foreach($months as $i => $name): ?>
                                <option value="<?= $i + 1 ?>"><?= $name ?></option>
                            <?php endforeach;?>
                        </select>
                    </div>
                    <div class="col-4">
                        <select class="form-select form-select-sm" name="paid_day" required>
                            <option value="">روز</option>
                            <?php for($d = 1; $d <= 31; $d++): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endfor;?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">تصویر رسید <span class="text-danger">*</span></label>
                <input type="file" class="form-control form-control-sm" name="receipt_file" accept=".jpg,.jpeg,.png,.pdf" required>
                <small class="text-muted">jpg, png, pdf — حداکثر ۲ مگابایت</small>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100" onclick="return validatePayForm(<?= (int)$pay['id'] ?>)">
                    <?= icon('upload','',14) ?> ثبت رسید
                </button>
            </div>
        </div>
    </form>
    <script>
    function selectChannel(card, channelId) {
        // حذف انتخاب قبلی در همان فرم
        var form = card.closest('.pay-card');
        form.querySelectorAll('.channel-card').forEach(function(c) { c.classList.remove('selected'); });
        card.classList.add('selected');
        // تنظیم مقدار hidden input
        var payId = form.querySelector('input[name="payment_channel_id"]');
        if (payId) payId.value = channelId;
    }
    function validatePayForm(payId) {
        var channelInput = document.getElementById('channel_input_' + payId);
        if (!channelInput || !channelInput.value) {
            alert('لطفاً یک کانال پرداخت انتخاب کنید.');
            return false;
        }
        return true;
    }
    </script>
    <?php else:?>
        <div class="alert alert-warning py-2 small">در حال حاضر کانال پرداختی فعال نیست. با پشتیبانی تماس بگیرید.</div>
    <?php endif;?>
</div>
<?php endforeach;?>

<!-- پرداخت‌های در انتظار بررسی -->
<?php foreach($inReviewPays as $pay):?>
<div class="pay-card pay-review">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span class="badge bg-info text-dark mb-1">در حال بررسی</span>
            <h6 class="fw-bold mb-0">رسید شما در حال بررسی توسط مدیر است</h6>
            <code class="small text-muted" dir="ltr"><?= $e($pay['tracking_code'] ?? '') ?></code>
        </div>
        <div class="text-end">
            <strong><?= number_format((int)($pay['final_amount'] ?? $pay['amount'])) ?></strong> تومان
        </div>
    </div>
</div>
<?php endforeach;?>

<!-- پرداخت‌های تأییدشده -->
<?php foreach($approvedPays as $pay):?>
<div class="pay-card pay-approved">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span class="badge bg-success mb-1">✅ تأیید شده</span>
            <h6 class="fw-bold mb-0">پرداخت شما تأیید شد</h6>
            <code class="small text-muted" dir="ltr"><?= $e($pay['tracking_code'] ?? '') ?></code>
        </div>
        <div class="text-end">
            <strong><?= number_format((int)($pay['final_amount'] ?? $pay['amount'])) ?></strong> تومان
        </div>
    </div>
</div>
<?php endforeach;?>

<!-- سابقهٔ کامل پرداخت‌ها -->
<div class="card">
    <div class="card-header fw-bold"><?= icon('money','me-1',18) ?> سابقهٔ پرداخت‌ها</div>
    <div class="card-body">
        <?php if(empty($pays)):?>
            <p class="text-muted text-center py-4 mb-0">هنوز پرداختی ثبت نشده است.</p>
        <?php else:?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>مبلغ</th><th>کد رهگیری</th><th>کانال</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
                    <tbody>
                    <?php foreach($pays as $pay):?>
                        <tr>
                            <td><strong><?= number_format((int)($pay['final_amount'] ?? $pay['amount'])) ?></strong> ت</td>
                            <td><code dir="ltr"><?= $e($pay['tracking_code'] ?? '') ?></code></td>
                            <td class="small"><?= $e($pay['channel_title'] ?? '—') ?></td>
                            <td>
                                <?php $st = $pay['status'] ?? ''; ?>
                                <span class="badge bg-<?= $st === 'تأیید شده' ? 'success' : ($st === 'رد شده' ? 'danger' : ($st === 'در انتظار رسید' ? 'info' : 'warning text-dark')) ?>">
                                    <?= $e($st) ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?= persianDate((string)$pay['created_at']) ?></td>
                        </tr>
                    <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        <?php endif;?>
    </div>
</div>
