<?php
$base = rtrim(BASE_URL, '/') . '/';
$s = $stats ?? [];
$apps = $applications ?? [];
$pays = $payments ?? [];
$notifs = $notifications ?? [];
$latestApp = $latestApp ?? null;
$tl = $timeline ?? [];
$sm = $statusMeta ?? [];
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<style>
.ov-hero{background:linear-gradient(135deg,var(--navy) 0%,#1B3A5C 50%,#0E7490 100%);color:#fff;border-radius:20px;padding:2rem;margin-bottom:1.5rem;box-shadow:0 8px 32px rgba(26,42,79,.3);position:relative;overflow:hidden}
.ov-hero::before{content:'';position:absolute;top:-50%;inset-inline-end:-20%;width:60%;height:200%;background:radial-gradient(ellipse,rgba(244,163,0,.12) 0%,transparent 70%);pointer-events:none}
.ov-hero h4{color:#fff!important}
.ov-stat{font-size:2.2rem;font-weight:800;line-height:1;margin:0;color:#fff}
.ov-stat-lbl{font-size:.78rem;opacity:.7;margin:0;color:#B9C4CF}
.stat-mini{background:var(--card-bg,#fff);border:1px solid var(--border-color,rgba(0,0,0,.06));border-radius:14px;padding:1.2rem;display:flex;align-items:center;gap:.75rem;transition:transform .2s}
.stat-mini:hover{transform:translateY(-2px)}
.stat-mini .si{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}
.si.navy{background:linear-gradient(135deg,var(--navy),#2C3E66)}.si.turq{background:linear-gradient(135deg,var(--turquoise),var(--turquoise-dark))}
.si.green{background:linear-gradient(135deg,#10B981,#059669)}.si.gold{background:linear-gradient(135deg,var(--saffron),#D97706)}
.app-row{background:var(--card-bg,#fff);border:1px solid var(--border-color,rgba(0,0,0,.06));border-radius:14px;padding:1rem;transition:all .2s;text-decoration:none;color:inherit;display:flex;align-items:center;gap:.75rem}
.app-row:hover{border-color:var(--turquoise);box-shadow:0 4px 16px rgba(0,0,0,.08);text-decoration:none}
[data-bs-theme=dark] .stat-mini,[data-bs-theme=dark] .app-row{background:#1E293B;border-color:rgba(255,255,255,.08)}
</style>
<div class="ov-hero">
    <div class="row align-items-center g-3">
        <div class="col-md-8">
            <h4 class="fw-bold mb-1">خوش آمدید، <?= $e($user['first_name'] ?? '') ?> 👋</h4>
            <p class="mb-0 ov-stat-lbl">نمای کلی درخواست‌ها و فعالیت‌های شما</p>
            <div class="row g-3 mt-2">
                <div class="col-6 col-md-3"><p class="ov-stat"><?= (int)$s['total_apps'] ?></p><p class="ov-stat-lbl mb-0">کل درخواست‌ها</p></div>
                <div class="col-6 col-md-3"><p class="ov-stat"><?= (int)$s['pending_apps'] ?></p><p class="ov-stat-lbl mb-0">در حال رسیدگی</p></div>
                <div class="col-6 col-md-3"><p class="ov-stat"><?= (int)$s['issued_apps'] ?></p><p class="ov-stat-lbl mb-0">مجوز صادرشده</p></div>
                <div class="col-6 col-md-3"><p class="ov-stat"><?= number_format(array_sum(array_map(fn($p)=>$p['status']==='تأیید شده'?(int)$p['amount']:0,$pays))) ?><small> ت</small></p><p class="ov-stat-lbl mb-0">پرداخت‌شده</p></div>
            </div>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?= $base ?>services" class="btn btn-light fw-bold mb-1"><?= icon('plus', '', 16) ?> درخواست جدید</a><br>
            <a href="<?= $base ?>panel/notifications" class="btn btn-outline-light">اعلان‌ها<?php if($unreadCount??0>0):?> <span class="badge bg-danger"><?= $unreadCount ?></span><?php endif;?></a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="stat-mini"><div class="si navy"><?= icon('folder','',20) ?></div><div><div style="font-size:1.5rem;font-weight:800;color:var(--ink,#1A2A4F)"><?= (int)$s['total_apps'] ?></div><p class="mb-0" style="font-size:.78rem;color:var(--muted,#6B7280)">کل درخواست‌ها</p></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-mini"><div class="si turq"><?= icon('clock','',20) ?></div><div><div style="font-size:1.5rem;font-weight:800;color:var(--ink,#1A2A4F)"><?= (int)$s['pending_apps'] ?></div><p class="mb-0" style="font-size:.78rem;color:var(--muted,#6B7280)">در حال رسیدگی</p></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-mini"><div class="si green"><?= icon('check-circle','',20) ?></div><div><div style="font-size:1.5rem;font-weight:800;color:var(--ink,#1A2A4F)"><?= (int)$s['issued_apps'] ?></div><p class="mb-0" style="font-size:.78rem;color:var(--muted,#6B7280)">مجوز صادرشده</p></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-mini"><div class="si gold"><?= icon('bell','',20) ?></div><div><div style="font-size:1.5rem;font-weight:800;color:var(--ink,#1A2A4F)"><?= $unreadCount ?? 0 ?></div><p class="mb-0" style="font-size:.78rem;color:var(--muted,#6B7280)">اعلان جدید</p></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-bold"><?= icon('folder','me-1',18) ?> درخواست‌های اخیر</span><a href="<?= $base ?>panel/requests" class="btn btn-sm btn-outline-primary">همه</a></div>
            <div class="card-body">
                <?php if(empty($apps)):?>
                    <div class="text-center py-4"><div style="font-size:3rem;opacity:.2"><?= icon('folder','',48) ?></div><p class="text-muted mt-2 mb-3">هنوز درخواستی ثبت نکرده‌اید</p><a href="<?= $base ?>services" class="btn btn-primary"><?= icon('shopping-cart','',16) ?> شروع درخواست</a></div>
                <?php else:?>
                    <div class="d-grid gap-2">
                        <?php foreach(array_slice($apps,0,5) as $app):?>
                            <a href="<?= $base ?>tracking/status/<?= rawurlencode($app['tracking_code']) ?>" class="app-row">
                                <div style="width:40px;height:40px;border-radius:10px;background:var(--turquoise-light);color:var(--turquoise-dark);display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;font-size:.8rem"><?= $e(mb_substr($app['service_title']??'م',0,1)) ?></div>
                                <div class="flex-grow-1 overflow-hidden"><div class="fw-bold small text-truncate"><?= $e($app['service_title']??'مجوز صنایع دستی') ?></div><code class="small text-muted" dir="ltr"><?= $e($app['tracking_code']) ?></code></div>
                                <span class="badge bg-<?= $sm[$app['current_status']]['color']??'secondary' ?>"><?= $e($app['current_status']) ?></span>
                            </a>
                        <?php endforeach;?>
                    </div>
                <?php endif;?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <?php if(!empty($tl)):?>
        <div class="card h-100">
            <div class="card-header fw-bold"><?= icon('clock','me-1',18) ?> روند آخرین درخواست</div>
            <div class="card-body">
                <?php if($latestApp):?><div class="mb-2"><code dir="ltr"><?= $e($latestApp['tracking_code']) ?></code> <span class="badge bg-<?= $sm[$latestApp['current_status']]['color']??'secondary' ?>"><?= $e($latestApp['current_status']) ?></span></div><?php endif;?>
                <ul class="timeline">
                    <?php foreach(array_slice($tl,0,8) as $log):?>
                        <li class="timeline-item">
                            <span class="tl-status"><?= $e($log['status_name']) ?></span>
                            <?php if(!empty($log['description'])):?><span class="tl-desc"><?= $e($log['description']) ?></span><?php endif;?>
                            <span class="tl-date"><?= persianDate((string)$log['created_at']) ?> <?= date('H:i',strtotime($log['created_at'])) ?></span>
                        </li>
                    <?php endforeach;?>
                </ul>
            </div>
        </div>
        <?php else:?>
        <div class="card h-100"><div class="card-body text-center py-5 text-muted">هنوز روندی برای نمایش وجود ندارد</div></div>
        <?php endif;?>
    </div>
</div>

<?php if(!empty($notifs)):?>
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-bold"><?= icon('bell','me-1',18) ?> اعلان‌های اخیر</span><a href="<?= $base ?>panel/notifications" class="btn btn-sm btn-outline-primary">همه</a></div>
    <ul class="list-group list-group-flush">
        <?php foreach(array_slice($notifs,0,3) as $n):?>
            <li class="list-group-item small <?= (int)$n['is_read']===0?'fw-bold':'' ?>">
                <div class="d-flex justify-content-between"><span><?= $e($n['title']) ?></span><small class="text-muted"><?= persianDate((string)$n['created_at']) ?></small></div>
            </li>
        <?php endforeach;?>
    </ul>
</div>
<?php endif;?>
