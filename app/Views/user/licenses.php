<?php $base=rtrim(BASE_URL,'/').'/';$lics=$licenses??[];$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$stateBadge=['جاری'=>'primary','صادر شده'=>'success','باطل شده'=>'danger','منقضی'=>'secondary'];
?>
<div class="card" style="border-color:var(--persian-green)">
    <div class="card-header fw-bold" style="color:var(--persian-green)"><?= icon('award','me-1',18) ?> مجوزهای من</div>
    <div class="card-body">
        <?php if(empty($lics)):?>
            <div class="text-center py-4"><div style="font-size:3rem;opacity:.2"><?= icon('award','',48) ?></div><p class="text-muted mt-2 mb-0">هنوز مجوز صادرشده‌ای ندارید</p></div>
        <?php else:?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>کد رهگیری</th><th>خدمت</th><th>وضعیت مجوز</th><th>اعتبار</th><th>تاریخ ثبت</th><th>اقدام</th></tr></thead>
                    <tbody>
                    <?php foreach($lics as $lic):
                        $daysLeft=null;
                        if(!empty($lic['license_valid_until'])){
                            $diff=strtotime($lic['license_valid_until'])-time();
                            $daysLeft=$diff>0?floor($diff/86400):0;
                        }
                    ?>
                        <tr>
                            <td><code dir="ltr"><?= $e($lic['tracking_code']) ?></code></td>
                            <td class="small"><?= $e($lic['service_title']??'مجوز صنایع دستی') ?></td>
                            <td><span class="badge bg-<?= $stateBadge[$lic['license_state']??'جاری']??'primary' ?>"><?= $e($lic['license_state']??'جاری') ?></span></td>
                            <td>
                                <?php if($daysLeft!==null):?>
                                    <?php if($daysLeft>0):?><span class="badge bg-info"><?= (int)$daysLeft ?> روز</span>
                                    <?php else:?><span class="badge bg-danger">منقضی</span><?php endif;?>
                                <?php else:?><span class="text-muted">—</span><?php endif;?>
                            </td>
                            <td class="small text-muted"><?= persianDate((string)$lic['created_at']) ?></td>
                            <td><a href="<?= $base ?>tracking/status/<?= rawurlencode($lic['tracking_code']) ?>" class="btn btn-sm btn-outline-primary"><?= icon('eye','',14) ?></a></td>
                        </tr>
                    <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        <?php endif;?>
    </div>
</div>
