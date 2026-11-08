<?php
$base=rtrim(BASE_URL,'/').'/';
$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$app=$applicant??[];
$code=$app['tracking_code']??'';
$dbt=$docsByType??[];

$docTypes = [
    'national_card_front'=>'تصویر کارت ملی (رو) *',
    'national_card_back'=>'تصویر کارت ملی (پشت)',
    'birth_cert'=>'تصویر شناسنامه *',
    'ownership_doc'=>'مدرک مالکیت/اجاره *',
    'vocational_cert'=>'گواهینامه فنی‌وحرفه‌ای',
    'education_degree'=>'مدرک تحصیلی',
    'product_photos'=>'تصاویر محصولات (چندفایلی)',
    'optional_1'=>'مدارک اختیاری ۱',
    'optional_2'=>'مدارک اختیاری ۲',
    'optional_3'=>'مدارک اختیاری ۳',
];
$success=Session::get('bulk_success');Session::remove('bulk_success');
?>
<div class="container-fluid py-4">
    <h3 class="fw-bold mb-3"><?= icon('upload','me-2',22) ?> بارگذاری مدارک: <code dir="ltr"><?= $e($code) ?></code></h3>
    <p class="text-muted small">متقاضی: <?= $e($app['first_name'].' '.$app['last_name']) ?> — کد ملی: <code dir="ltr"><?= $e($app['national_code']) ?></code></p>

    <?php if($success):?><div class="alert alert-success py-2 small"><?= $e($success) ?></div><?php endif;?>

    <form method="post" action="<?= $base ?>admin/bulk-import/documents/<?= rawurlencode($code) ?>/save" enctype="multipart/form-data">
        <?= Session::csrfField() ?>

        <div class="row g-3">
            <?php foreach($docTypes as $type=>$label):
                $existingCount=count($dbt[$type]??[]);?>
                <div class="col-md-6">
                    <div class="card h-100 <?= $existingCount>0?'border-success':'border-warning' ?>">
                        <div class="card-body">
                            <label class="form-label fw-bold small"><?= $e($label) ?></label>
                            <?php if($type==='product_photos'):?>
                                <input type="file" class="form-control form-control-sm" name="product_photos[]" accept=".jpg,.jpeg,.png,.pdf" multiple>
                            <?php else:?>
                                <input type="file" class="form-control form-control-sm" name="doc_<?= $e($type) ?>" accept=".jpg,.jpeg,.png,.pdf">
                            <?php endif;?>
                            <?php if($existingCount>0):?>
                                <small class="text-success"><?= $existingCount ?> مدرک موجود ✓</small>
                            <?php else:?>
                                <small class="text-warning">بدون مدرک</small>
                            <?php endif;?>
                        </div>
                    </div>
                </div>
            <?php endforeach;?>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-lg"><?= icon('save','',16) ?> ذخیرهٔ مدارک</button>
            <a href="<?= $base ?>admin/bulk-import/missing-docs" class="btn btn-outline-secondary btn-lg">بازگشت به لیست</a>
            <a href="<?= $base ?>admin/view/<?= (int)$app['id'] ?>" class="btn btn-outline-info btn-lg">مشاهدهٔ پرونده</a>
        </div>
    </form>
</div>
