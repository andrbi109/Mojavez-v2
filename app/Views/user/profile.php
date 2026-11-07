<?php
$base = rtrim(BASE_URL, '/') . '/';
$p = $person ?? [];
$prop = $property ?? [];
$u = $user ?? [];
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$avatarUrl = '';
if (!empty($u['avatar_document_id'])) {
    $avatarUrl = $base . 'document/view?document_id=' . (int)$u['avatar_document_id'] . '&applicant_id=0';
}
?>
<style>
.profile-hero{background:linear-gradient(135deg,var(--turquoise),var(--turquoise-dark));border-radius:20px;padding:2rem;text-align:center;color:#fff;margin-bottom:1.5rem;position:relative;overflow:hidden}
.profile-hero::before{content:'';position:absolute;top:-50%;inset-inline-end:-20%;width:60%;height:200%;background:radial-gradient(ellipse,rgba(255,255,255,.1) 0%,transparent 70%)}
.avatar-wrap{position:relative;display:inline-block;margin-bottom:1rem}
.avatar-img{width:100px;height:100px;border-radius:50%;object-fit:cover;border:4px solid rgba(255,255,255,.3);box-shadow:0 4px 20px rgba(0,0,0,.2)}
.avatar-placeholder{width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:700;color:#fff;border:4px solid rgba(255,255,255,.3)}
.avatar-btn{position:absolute;bottom:0;left:0;background:#fff;color:var(--turquoise-dark);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.2);cursor:pointer;border:none}
.info-card{background:var(--card-bg,#fff);border:1px solid var(--border-color,rgba(0,0,0,.06));border-radius:16px;overflow:hidden}
.info-card .card-header{background:linear-gradient(135deg,var(--turquoise-light),rgba(0,180,216,.05));font-weight:700}
.info-row{display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid var(--border-color,rgba(0,0,0,.04))}
.info-row:last-child{border-bottom:none}
.info-row .lbl{color:var(--muted,#6B7280);font-size:.82rem}.info-row .val{font-weight:600;font-size:.88rem;color:var(--ink,#1A2A4F)}
[data-bs-theme=dark] .info-card{background:#1E293B;border-color:rgba(255,255,255,.08)}
[data-bs-theme=dark] .info-card .card-header{background:rgba(0,180,216,.1)}
[data-bs-theme=dark] .info-row .val{color:#E2E8F0}
[data-bs-theme=dark] .info-row .lbl{color:#94A3B8}
</style>

<div class="profile-hero">
    <form id="avatarForm" method="post" action="<?= $base ?>panel/avatar" enctype="multipart/form-data" style="display:none">
        <?= Session::csrfField() ?>
        <input type="file" name="avatar" id="avatarInput" accept=".jpg,.jpeg,.png" onchange="document.getElementById('avatarForm').submit()">
    </form>
    <div class="avatar-wrap">
        <?php if($avatarUrl):?>
            <img src="<?= $avatarUrl ?>" class="avatar-img" alt="پروفایل">
        <?php else:?>
            <div class="avatar-placeholder"><?= $e(mb_substr($u['first_name']??'؟',0,1)) ?></div>
        <?php endif;?>
        <button type="button" class="avatar-btn" onclick="document.getElementById('avatarInput').click()" title="تغییر تصویر">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
        </button>
    </div>
    <h4 class="fw-bold mb-0"><?= $e($p['first_name'] ?? $u['first_name'] ?? '') ?> <?= $e($p['last_name'] ?? $u['last_name'] ?? '') ?></h4>
    <small class="opacity-75" dir="ltr"><?= $e($u['mobile'] ?? '') ?></small>
</div>

<?php if(!empty($success)):?><div class="alert alert-success py-2 small"><?= $e($success) ?></div><?php endif;?>
<?php if(!empty($error)):?><div class="alert alert-danger py-2 small"><?= $e($error) ?></div><?php endif;?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="info-card">
            <div class="card-header"><?= icon('user','me-1',16) ?> اطلاعات هویتی</div>
            <div class="card-body">
                <div class="info-row"><span class="lbl">کد ملی</span><span class="val" dir="ltr"><?= $e($p['national_code'] ?? $u['national_code'] ?? '') ?></span></div>
                <div class="info-row"><span class="lbl">نام پدر</span><span class="val"><?= $e($p['father_name'] ?? '—') ?></span></div>
                <div class="info-row"><span class="lbl">تاریخ تولد</span><span class="val"><?= !empty($p['birth_date'])?persianDate($p['birth_date']):'—' ?></span></div>
                <div class="info-row"><span class="lbl">نوع شخص</span><span class="val"><?= $e($p['person_type'] ?? 'حقیقی') ?></span></div>
                <?php if(($p['person_type']??'')==='حقوقی'):?>
                    <div class="info-row"><span class="lbl">نام شرکت</span><span class="val"><?= $e($p['company_name'] ?? '—') ?></span></div>
                    <div class="info-row"><span class="lbl">شناسه ملی</span><span class="val" dir="ltr"><?= $e($p['company_national_id'] ?? '—') ?></span></div>
                    <div class="info-row"><span class="lbl">شماره ثبت</span><span class="val" dir="ltr"><?= $e($p['reg_number'] ?? '—') ?></span></div>
                <?php endif;?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="info-card">
            <div class="card-header"><?= icon('home','me-1',16) ?> اطلاعات تماس و آدرس</div>
            <div class="card-body">
                <div class="info-row"><span class="lbl">موبایل</span><span class="val" dir="ltr"><?= $e($u['mobile'] ?? '') ?></span></div>
                <div class="info-row"><span class="lbl">کد پستی</span><span class="val" dir="ltr"><?= $e($p['postal_code'] ?? $prop['postal_code'] ?? '—') ?></span></div>
                <?php if($prop):?>
                    <div class="info-row"><span class="lbl">استان</span><span class="val"><?= $e($prop['province'] ?? '—') ?></span></div>
                    <div class="info-row"><span class="lbl">شهرستان</span><span class="val"><?= $e($prop['county'] ?? '—') ?></span></div>
                    <div class="info-row"><span class="lbl">شهر</span><span class="val"><?= $e($prop['city'] ?? '—') ?></span></div>
                    <?php if(!empty($prop['village'])):?><div class="info-row"><span class="lbl">روستا</span><span class="val"><?= $e($prop['village']) ?></span></div><?php endif;?>
                    <div class="info-row"><span class="lbl">وضعیت مالکیت</span><span class="val"><?= $e($prop['ownership_status'] ?? '—') ?></span></div>
                    <?php if(!empty($prop['address_street'])):?>
                        <div class="info-row"><span class="lbl">آدرس</span><span class="val text-end small"><?= $e($prop['address_street'] ?? '') ?> <?= $e($prop['address_alley'] ?? '') ?> پلاک <?= $e($prop['address_plaque'] ?? '') ?></span></div>
                    <?php endif;?>
                <?php else:?>
                    <p class="text-muted small mb-0">اطلاعات آدرس پس از اولین ثبت‌نام تکمیل می‌شود.</p>
                <?php endif;?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header fw-bold"><?= icon('settings','me-1',16) ?> تنظیمات حساب</div>
    <div class="card-body">
        <form method="post" action="<?= $base ?>panel/profile">
            <?= Session::csrfField() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small">ایمیل (اختیاری)</label>
                    <input type="email" class="form-control" name="email" value="<?= $e($u['email'] ?? '') ?>" dir="ltr">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><?= icon('save','',14) ?> ذخیره</button>
                </div>
            </div>
        </form>
    </div>
</div>
