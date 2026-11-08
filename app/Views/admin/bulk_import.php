<?php $base=rtrim(BASE_URL,'/').'/';$e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');?>
<div class="container-fluid py-4">
    <h3 class="fw-bold mb-4"><?= icon('upload','me-2',22) ?> ایمپورت دسته‌جمعی کاربران</h3>
    <?php if(!empty($error)):?><div class="alert alert-danger py-2 small"><?= $e($error) ?></div><?php endif;?>

    <div class="card">
        <div class="card-body p-4">
            <form method="post" action="<?= $base ?>admin/bulk-import/upload" enctype="multipart/form-data">
                <?= Session::csrfField() ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">نوع خدمت <span class="text-danger">*</span></label>
                        <select class="form-select" name="service_type_id" required>
                            <option value="">انتخاب کنید...</option>
                            <?php foreach(($services??[]) as $sv):?>
                                <option value="<?= (int)$sv['id'] ?>"><?= $e($sv['title']) ?></option>
                            <?php endforeach;?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">رشته مجوز <span class="text-danger">*</span></label>
                        <select class="form-select" name="license_title" required>
                            <option value="">انتخاب کنید...</option>
                            <?php foreach(($licenseGroups??[]) as $cat => $titles):?>
                                <optgroup label="<?= $e($cat) ?>">
                                    <?php foreach($titles as $t):?>
                                        <?php if(!str_starts_with($t,'سایر')):?>
                                            <option value="<?= $e($t) ?>"><?= $e($t) ?></option>
                                        <?php endif;?>
                                    <?php endforeach;?>
                                </optgroup>
                            <?php endforeach;?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">فایل CSV <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                    <small class="text-muted">فرمت: CSV با هدر فارسی (نام، نام خانوادگی، کد ملی، موبایل، ...) — حداکثر ۵ مگابایت و ۱۰۰۰ ردیف</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">رمز عبور پیش‌فرض</label>
                    <div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="default_password" value="national_code" id="pw_nc" checked>
                            <label class="form-check-label" for="pw_nc">کد ملی هر کاربر</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="default_password" value="fixed" id="pw_fixed">
                            <label class="form-check-label" for="pw_fixed">رمز ثابت:</label>
                            <input type="text" class="form-control form-control-sm d-inline-block" name="fixed_password" style="width:200px" placeholder="مثلاً 123456">
                        </div>
                    </div>
                </div>

                <div class="alert alert-info py-2 small">
                    <strong>نمونهٔ فرمت CSV:</strong><br>
                    <code>نام کامل,کد ملی,موبایل,کد پستی,استان,شهر,روستا</code><br>
                    <code>علی احمدی,1234567890,09123456789,1234567890,فارس,شیراز,</code>
                </div>

                <button type="submit" class="btn btn-primary btn-lg"><?= icon('upload','',16) ?> آپلود و پیش‌نمایش</button>
                <a href="<?= $base ?>admin/bulk-import/missing-docs" class="btn btn-outline-secondary btn-lg">لیست نیازمند مدرک</a>
            </form>
        </div>
    </div>
</div>
