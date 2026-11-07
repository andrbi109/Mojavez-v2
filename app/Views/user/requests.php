<?php $base=rtrim(BASE_URL,'/').'/';$apps=$applications??[];$sm=$statusMeta??[];$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold"><?= icon('folder','me-1',18) ?> درخواست‌های من</span>
        <a href="<?= $base ?>services" class="btn btn-sm btn-primary"><?= icon('plus','',14) ?> درخواست جدید</a>
    </div>
    <div class="card-body">
        <?php if(empty($apps)):?>
            <div class="text-center py-5"><div style="font-size:3rem;opacity:.2"><?= icon('folder','',48) ?></div><p class="text-muted mt-2 mb-3">هنوز درخواستی ثبت نکرده‌اید</p><a href="<?= $base ?>services" class="btn btn-primary"><?= icon('shopping-cart','',16) ?> شروع اولین درخواست</a></div>
        <?php else:?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>کد رهگیری</th><th>خدمت</th><th>وضعیت</th><th>تاریخ ثبت</th><th>اقدام</th></tr></thead>
                    <tbody>
                    <?php foreach($apps as $app):?>
                        <tr>
                            <td><code dir="ltr"><?= $e($app['tracking_code']) ?></code></td>
                            <td class="small"><?= $e($app['service_title']??'مجوز صنایع دستی') ?></td>
                            <td><span class="badge bg-<?= $sm[$app['current_status']]['color']??'secondary' ?>"><?= $e($app['current_status']) ?></span></td>
                            <td class="small text-muted"><?= persianDate((string)$app['created_at']) ?></td>
                            <td><a href="<?= $base ?>tracking/status/<?= rawurlencode($app['tracking_code']) ?>" class="btn btn-sm btn-outline-primary"><?= icon('eye','',14) ?></a></td>
                        </tr>
                    <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        <?php endif;?>
    </div>
</div>
