<?php $base=rtrim(BASE_URL,'/').'/';$notifs=$notifications??[];$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold"><?= icon('bell','me-1',18) ?> اعلان‌ها</span>
        <?php if($unreadCount??0>0):?>
            <form method="post" action="<?= $base ?>panel/notifications/read" class="d-inline">
                <?= Session::csrfField() ?>
                <button type="submit" class="btn btn-sm btn-outline-primary">علامت‌گذاری همه به‌عنوان خوانده‌شده</button>
            </form>
        <?php endif;?>
    </div>
    <div class="card-body p-0">
        <?php if(empty($notifs)):?>
            <div class="text-center py-5"><div style="font-size:3rem;opacity:.2"><?= icon('bell','',48) ?></div><p class="text-muted mt-2 mb-0">اعلانی وجود ندارد</p></div>
        <?php else:?>
            <ul class="list-group list-group-flush">
                <?php foreach($notifs as $n):?>
                    <li class="list-group-item <?= (int)$n['is_read']===0?'fw-bold border-end-4':'opacity-75' ?>" style="<?= (int)$n['is_read']===0?'border-right:4px solid var(--turquoise)':'' ?>">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="flex-grow-1">
                                <div class="fw-<?= (int)$n['is_read']===0?'bold':'normal' ?> small"><?= $e($n['title']) ?></div>
                                <?php if(!empty($n['body'])):?><div class="small text-muted mt-1"><?= $e($n['body']) ?></div><?php endif;?>
                                <?php if(!empty($n['tracking_code'])):?><code class="small text-muted" dir="ltr"><?= $e($n['tracking_code']) ?></code><?php endif;?>
                            </div>
                            <small class="text-muted text-nowrap"><?= persianDate((string)$n['created_at']) ?></small>
                        </div>
                    </li>
                <?php endforeach;?>
            </ul>
        <?php endif;?>
    </div>
</div>
