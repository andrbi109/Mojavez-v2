<?php $base=rtrim(BASE_URL,'/').'/';$pays=$payments??[];$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');?>
<div class="card">
    <div class="card-header fw-bold"><?= icon('money','me-1',18) ?> پرداخت‌های من</div>
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
                            <td><strong><?= number_format((int)$pay['amount']) ?></strong> ت</td>
                            <td><code dir="ltr"><?= $e($pay['tracking_code']??'') ?></code></td>
                            <td class="small"><?= $e($pay['channel_title']??'—') ?></td>
                            <td><span class="badge bg-<?= $pay['status']==='تأیید شده'?'success':($pay['status']==='رد شده'?'danger':'warning text-dark') ?>"><?= $e($pay['status']) ?></span></td>
                            <td class="small text-muted"><?= persianDate((string)$pay['created_at']) ?></td>
                        </tr>
                    <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        <?php endif;?>
    </div>
</div>
