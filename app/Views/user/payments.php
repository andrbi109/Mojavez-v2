<?php
$base=rtrim(BASE_URL,'/').'/';
$pays=$payments??[];
$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');

// فیلتر پرداخت‌های در انتظار رسید (نیازمند پرداخت)
$pendingPays = array_filter($pays, fn($p) => ($p['status']??'') === 'در انتظار رسید');
$otherPays = array_filter($pays, fn($p) => ($p['status']??'') !== 'در انتظار رسید');

// دریافت کانال‌های پرداخت
$channels = [];
try { $channels = (new PaymentChannel())->getActive(); } catch (\Throwable $e2) {}
?>
<style>
.pay-pending{background:linear-gradient(135deg,rgba(244,163,0,.08),rgba(244,163,0,.02));border:1px solid rgba(244,163,0,.25);border-radius:16px;padding:1.5rem;margin-bottom:1.5rem}
.pay-pending .amount{font-size:1.6rem;font-weight:800;color:var(--saffron-dark,#D97706)}
[data-bs-theme=dark] .pay-pending{background:rgba(244,163,0,.08);border-color:rgba(244,163,0,.25)}
[data-bs-theme=dark] .pay-pending .amount{color:var(--saffron,#E3B255)}
</style>

<!-- پرداخت‌های در انتظار -->
<?php if(!empty($pendingPays)):?>
<?php foreach($pendingPays as $pay):?>
<div class="pay-pending">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <span class="badge bg-warning text-dark mb-1">نیازمند پرداخت</span>
            <h5 class="fw-bold mb-0">پرداخت هزینهٔ ثبت‌نام</h5>
            <code class="small text-muted" dir="ltr"><?= $e($pay['tracking_code']??'') ?></code>
        </div>
        <div class="text-end">
            <div class="amount"><?= number_format((int)$pay['amount']) ?> تومان</div>
            <?php if(!empty($pay['final_amount']) && (int)$pay['final_amount'] !== (int)$pay['amount']):?>
                <small class="text-muted">مبلغ نهایی پس از تخفیف: <strong><?= number_format((int)$pay['final_amount']) ?></strong> ت</small>
            <?php endif;?>
        </div>
    </div>

    <!-- کانال‌های پرداخت -->
    <h6 class="fw-bold small mb-2"><?= icon('info','',14) ?> مبلغ را از یکی از کانال‌های زیر واریز کنید:</h6>
    <div class="row g-2 mb-3">
        <?php foreach($channels as $ch):?>
        <div class="col-md-6">
            <div style="background:var(--card-bg,#fff);border:1px solid var(--border-color,rgba(0,0,0,.06));border-radius:10px;padding:.6rem .8rem">
                <div class="fw-bold small"><?= $e($ch['title']) ?> <span class="badge bg-secondary"><?= $e($ch['channel_type']) ?></span></div>
                <div class="small text-muted" dir="ltr" style="text-align:right"><?= $e($ch['account_info']??'') ?></div>
            </div>
        </div>
        <?php endforeach;?>
    </div>
    <?php if(empty($channels)):?>
        <p class="text-muted small">در حال حاضر کانال پرداختی فعال نیست. با پشتیبانی تماس بگیرید.</p>
    <?php endif;?>

    <!-- فرم آپلود رسید -->
    <form method="post" action="<?= $base ?>tracking/payments/submit" enctype="multipart/form-data">
        <?= Session::csrfField() ?>
        <input type="hidden" name="tracking_code" value="<?= $e($pay['tracking_code']??'') ?>">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">کد پیگیری بانکی (اختیاری)</label>
                <input type="text" class="form-control form-control-sm" name="reference_code" dir="ltr">
            </div>
            <div class="col-md-3">
                <label class="form-label small">تاریخ واریز (شمسی)</label>
                <div class="row g-1">
                    <div class="col-4"><input type="number" class="form-control form-control-sm" name="paid_year" placeholder="سال" min="1400" max="1420"></div>
                    <div class="col-4"><input type="number" class="form-control form-control-sm" name="paid_month" placeholder="ماه" min="1" max="12"></div>
                    <div class="col-4"><input type="number" class="form-control form-control-sm" name="paid_day" placeholder="روز" min="1" max="31"></div>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small">تصویر رسید</label>
                <input type="file" class="form-control form-control-sm" name="receipt_file" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><?= icon('upload','',14) ?> ثبت رسید</button>
            </div>
        </div>
    </form>
</div>
<?php endforeach;?>
<?php endif;?>

<!-- سابقهٔ پرداخت‌ها -->
<div class="card">
    <div class="card-header fw-bold"><?= icon('money','me-1',18) ?> سابقهٔ پرداخت‌ها</div>
    <div class="card-body">
        <?php if(empty($otherPays) && empty($pendingPays)):?>
            <p class="text-muted text-center py-4 mb-0">هنوز پرداختی ثبت نشده است.</p>
        <?php else:?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>مبلغ</th><th>کد رهگیری</th><th>کانال</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
                    <tbody>
                    <?php foreach($pays as $pay):?>
                        <tr>
                            <td><strong><?= number_format((int)$pay['amount']) ?></strong> ت</td>
                            <td><code dir="ltr"><?= $e($pay['tracking_code']??'') ?></code></td>
                            <td class="small"><?= $e($pay['channel_title']??'—') ?></td>
                            <td><span class="badge bg-<?= $pay['status']==='تأیید شده'?'success':($pay['status']==='رد شده'?'danger':($pay['status']==='در انتظار رسید'?'info':'warning text-dark')) ?>"><?= $e($pay['status']) ?></span></td>
                            <td class="small text-muted"><?= persianDate((string)$pay['created_at']) ?></td>
                        </tr>
                    <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        <?php endif;?>
    </div>
</div>
