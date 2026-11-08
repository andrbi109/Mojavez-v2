<?php
$base=rtrim(BASE_URL,'/').'/';
$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$vr=$rows??[];
$hds=$headers??[];
$am=$autoMapping??[];
$st=$serviceTypeId??0;
$lt=$licenseTitle??'';
$services=$services??[];
$lg=$licenseGroups??[];

// فیلدهای قابل نگاشت
$mapFields = [
    ''=>'—', 'full_name'=>'نام کامل', 'first_name'=>'نام', 'last_name'=>'نام خانوادگی',
    'national_code'=>'کد ملی', 'mobile'=>'موبایل', 'postal_code'=>'کد پستی',
    'province'=>'استان', 'city'=>'شهر', 'village'=>'روستا', 'father_name'=>'نام پدر',
    'birth_date'=>'تاریخ تولد', 'income_decile'=>'دهک درآمدی',
];

$validCount=count(array_filter($vr,fn($r)=>($r['status']??'')==='valid'));
$dupCount=count(array_filter($vr,fn($r)=>($r['status']??'')==='duplicate'));
$invalidCount=count(array_filter($vr,fn($r)=>($r['status']??'')==='invalid'));
?>
<div class="container-fluid py-4">
    <h3 class="fw-bold mb-3"><?= icon('list','me-2',22) ?> پیش‌نمایش ایمپورت</h3>
    <div class="row g-2 mb-3">
        <div class="col-auto"><span class="badge bg-success">🟢 معتبر: <?= $validCount ?></span></div>
        <div class="col-auto"><span class="badge bg-warning text-dark">🟡 تکراری: <?= $dupCount ?></span></div>
        <div class="col-auto"><span class="badge bg-danger">🔴 نامعتبر: <?= $invalidCount ?></span></div>
        <div class="col-auto"><span class="badge bg-info">کل: <?= count($vr) ?></span></div>
    </div>

    <form method="post" action="<?= $base ?>admin/bulk-import/execute">
        <?= Session::csrfField() ?>

        <!-- پارامترهای ایمپورت -->
        <input type="hidden" name="service_type_id" value="<?= (int)$st ?>">
        <input type="hidden" name="license_title" value="<?= $e($lt) ?>">
        <input type="hidden" name="default_password" value="<?= $e(Session::get('bulk_default_password','national_code')) ?>">
        <input type="hidden" name="fixed_password" value="<?= $e(Session::get('bulk_fixed_password','')) ?>">

        <!-- نگاشت ستون‌ها -->
        <div class="card mb-3">
            <div class="card-header fw-bold">نگاشت ستون‌ها</div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach($hds as $i=>$h):?>
                        <div class="col-md-3">
                            <label class="form-label small">ستون <?= $i+1 ?>: <?= $e($h) ?></label>
                            <select class="form-select form-select-sm" name="mapping[<?= $i ?>]">
                                <?php foreach($mapFields as $key=>$label):?>
                                    <option value="<?= $e($key) ?>" <?= ($am[$i]??'')===$key?'selected':'' ?>><?= $e($label) ?></option>
                                <?php endforeach;?>
                            </select>
                        </div>
                    <?php endforeach;?>
                </div>
            </div>
        </div>

        <!-- جدول پیش‌نمایش -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">ردیف‌ها</span>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="selectAllValid()">انتخاب همهٔ معتبر</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">لغو همه</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="sticky-top">
                            <tr>
                                <th style="width:40px">✓</th>
                                <th>نام</th>
                                <th>نام خانوادگی</th>
                                <th>کد ملی</th>
                                <th>موبایل</th>
                                <th>وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($vr as $r):
                                $d=$r['data']??[];?>
                                <tr class="<?= $r['status']==='invalid'?'table-danger':($r['status']==='duplicate'?'table-warning':'') ?>">
                                    <td>
                                        <?php if($r['status']==='valid'||$r['status']==='duplicate'):?>
                                            <input type="checkbox" name="selected_rows[]" value="<?= (int)$r['idx'] ?>" <?= $r['status']==='valid'?'checked':'' ?>>
                                        <?php endif;?>
                                    </td>
                                    <td class="small"><?= $e($d['first_name']??'') ?></td>
                                    <td class="small"><?= $e($d['last_name']??'') ?></td>
                                    <td class="small"><code dir="ltr"><?= $e($d['national_code']??'') ?></code></td>
                                    <td class="small"><code dir="ltr"><?= $e($d['mobile']??'') ?></code></td>
                                    <td>
                                        <?php if($r['status']==='valid'):?>
                                            <span class="badge bg-success">🟢 معتبر</span>
                                        <?php elseif($r['status']==='duplicate'):?>
                                            <span class="badge bg-warning text-dark">🟡 <?= $e(implode(', ',$r['errors'])) ?></span>
                                        <?php else:?>
                                            <span class="badge bg-danger">🔴 <?= $e(implode(', ',$r['errors'])) ?></span>
                                        <?php endif;?>
                                    </td>
                                </tr>
                            <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-lg"><?= icon('check','',16) ?> تأیید و ایمپورت</button>
            <a href="<?= $base ?>admin/bulk-import" class="btn btn-outline-secondary btn-lg">انصراف</a>
        </div>
    </form>
</div>

<script>
function selectAllValid(){document.querySelectorAll('input[name="selected_rows[]"]').forEach(function(c){c.checked=true;})}
function deselectAll(){document.querySelectorAll('input[name="selected_rows[]"]').forEach(function(c){c.checked=false;})}
</script>
