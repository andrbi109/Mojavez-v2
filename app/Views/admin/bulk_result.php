<?php $base=rtrim(BASE_URL,'/').'/';$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');$rs=$results??[];?>
<div class="container-fluid py-4">
    <h3 class="fw-bold mb-3"><?= icon('check-circle','me-2',22) ?> گزارش ایمپورت</h3>
    <div class="row g-2 mb-3">
        <div class="col-auto"><span class="badge bg-success fs-6">موفق: <?= (int)$successCount ?></span></div>
        <div class="col-auto"><span class="badge bg-warning text-dark fs-6">تکراری: <?= (int)$duplicateCount ?></span></div>
        <div class="col-auto"><span class="badge bg-danger fs-6">خطا: <?= (int)$errorCount ?></span></div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr><th>#</th><th>نام</th><th>کد ملی</th><th>موبایل</th><th>کد رهگیری</th><th>رمز عبور</th><th>وضعیت</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($rs as $i=>$r):?>
                            <tr class="<?= ($r['status']??'')==='success'?'table-success':(($r['status']??'')==='duplicate'?'table-warning':'table-danger') ?>">
                                <td><?= $i+1 ?></td>
                                <td class="small"><?= $e($r['name']??'') ?></td>
                                <td class="small"><code dir="ltr"><?= $e($r['national_code']??'') ?></code></td>
                                <td class="small"><code dir="ltr"><?= $e($r['mobile']??'') ?></code></td>
                                <td class="small"><code dir="ltr"><?= $e($r['tracking_code']??'—') ?></code></td>
                                <td class="small"><code dir="ltr"><?= $e($r['password']??'—') ?></code></td>
                                <td>
                                    <?php if(($r['status']??'')==='success'):?>
                                        <span class="badge bg-success">✅ موفق</span>
                                    <?php elseif(($r['status']??'')==='duplicate'):?>
                                        <span class="badge bg-warning text-dark">🟡 <?= $e($r['message']??'تکراری') ?></span>
                                    <?php else:?>
                                        <span class="badge bg-danger">🔴 خطا</span>
                                    <?php endif;?>
                                </td>
                            </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <a href="<?= $base ?>admin/bulk-import" class="btn btn-primary"><?= icon('upload','',14) ?> ایمپورت جدید</a>
        <a href="<?= $base ?>admin/bulk-import/missing-docs" class="btn btn-outline-info"><?= icon('folder','',14) ?> بارگذاری مدارک</a>
        <a href="<?= $base ?>admin/applications" class="btn btn-outline-secondary"><?= icon('list','',14) ?> فهرست پرونده‌ها</a>
    </div>
</div>
