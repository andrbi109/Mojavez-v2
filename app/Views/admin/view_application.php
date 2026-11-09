<?php
/**
 * جزئیات پروندهٔ متقاضی
 * --------------------------------------------------------------
 * - شبکهٔ اطلاعات (info-grid) به‌جای جداول طولانی
 * - نوار وضعیت عمودی (مسیر رسیدگی با گام‌های طی‌شده)
 * - گالری مدارک با پیش‌نمایش مودال
 * - تایم‌لاین رویدادها و سوابق کد پستی
 * ورودی‌ها: $applicant, $jobDetail, $propertyDetail, $documents, $statusLogs,
 *           $postalChanges, $statuses, $statusMeta, $dynValues
 */
$base = rtrim(BASE_URL, '/') . '/';
$statusMeta = $statusMeta ?? (require CONFIG_PATH . '/statuses.php')['statuses'];
$statuses = $statuses ?? array_keys($statusMeta);
$curStatus = $applicant['current_status'];
$curOrder = $statusMeta[$curStatus]['order'] ?? 0;
$isTerminal = in_array($curStatus, ['رد درخواست', 'لغو'], true);
$fullName = trim($applicant['first_name'] . ' ' . $applicant['last_name']);

// گام‌های نمایشی مسیر رسیدگی — پویا بر اساس نوع خدمت
$_pipelineBase = ['تکمیل اطلاعات', 'احراز نشانی', 'بررسی کارشناسی رشته', 'ثبت درخواست صدور',
    'بارگذاری مدارک به سازمان', 'تأیید اولیه', 'تأیید نهایی', 'صدور مجوز', 'تحویل مجوز'];
// اگر خدمت مشاغل خانگی مستقل است، مسیر اختصاصی را جایگزین کن
$_serviceId = (int)($applicant['service_type_id'] ?? 0);
if ($_serviceId === 2) {
    $_pipelineBase = ['تکمیل اطلاعات', 'احراز نشانی', 'تطبیق آدرس با ثبت احوال', 'ثبت درخواست صدور',
        'صدور مجوز', 'دریافت تصویر مجوز', 'تأیید اولیه در سامانه مشاغل خانگی',
        'تأیید نهایی دریافت کد رهگیری'];
}
$pipeline = $_pipelineBase;

// فیلتر وضعیت‌های قابل تغییر بر اساس نوع خدمت
$_availStatuses = [];
foreach ($statuses as $st) {
    if (!isset($statusMeta[$st]) || !$statusMeta[$st]['manual']) continue;
    // اگر services تعریف شده، فقط برای آن خدمت‌ها نمایش بده
    if (isset($statusMeta[$st]['services'])) {
        if (!in_array($_serviceId, $statusMeta[$st]['services'], true)) continue;
    }
    $_availStatuses[] = $st;
}

// نگاشت نوع مدرک → برچسب و آیکون
$docLabels = [
    'national_card' => ['کارت ملی', 'id-card'],
    'birth_cert' => ['شناسنامه', 'file-text'],
    'ownership_doc' => ['مدرک مالکیت/اجاره', 'home'],
    'lease_agreement' => ['اجاره‌نامه', 'file-text'],
    'supervisor_doc' => ['حکم سرپرستی', 'shield'],
    'vocational_cert' => ['گواهی فنی‌وحرفه‌ای', 'award'],
    'education_degree' => ['مدرک تحصیلی', 'book'],
    'product_photos' => ['تصویر محصولات', 'image'],
    'optional' => ['مدرک اختیاری', 'file-text'],
    'dynamic' => ['پیوست فیلد سفارشی', 'file-text'],
];
$ii = fn(string $label, string $value, string $ic = '', bool $mono = false) =>
    '<div class="info-item"><div class="info-label">' . ($ic ? icon($ic, '', 15) : '') . $label . '</div>'
    . '<div class="info-value' . ($mono ? ' mono' : '') . '">' . $value . '</div></div>';
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>

<!-- ===== سربرگ پرونده ===== -->
<div class="card p-3 p-md-4 mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,var(--turquoise),var(--turquoise-dark));display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
                <?= icon('user', '', 26) ?>
            </div>
            <div>
                <h4 class="fw-bold mb-1"><?= $e($fullName) ?>
                    <?php if ($applicant['priority']): ?><span class="priority-badge align-middle" title="ثبت‌نام بیش از <?= PRIORITY_DAYS ?> روز">⭐ اولویت‌دار</span><?php endif; ?>
                </h4>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <code class="fs-6"><?= $e($applicant['tracking_code']) ?></code>
                    <span class="badge bg-<?= $statusMeta[$curStatus]['color'] ?? 'secondary' ?>"><?= $e($curStatus) ?></span>
                    <?php if (!empty($serviceType)): ?>
                        <span class="badge bg-light text-dark border" title="<?= $e($serviceType['authority'] ?? '') ?>"><?= icon('grid', '', 12) ?> <?= $e($serviceType['title']) ?></span>
                    <?php endif; ?>
                    <?php if (($applicant['license_state'] ?? 'جاری') !== 'جاری'): ?>
                        <span class="badge bg-<?= $applicant['license_state'] === 'صادر شده' ? 'success' : 'dark' ?>"><?= $e($applicant['license_state']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($applicant['license_valid_until'])): ?>
                        <small class="text-muted">اعتبار تا <?= persianDate((string)$applicant['license_valid_until']) ?></small>
                    <?php endif; ?>
                    <small class="text-muted">ثبت: <?= persianDate((string)$applicant['registration_completed_at']) ?></small>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (Session::get('admin_role') === 'admin'): ?>
                <a href="<?= $base ?>admin/edit/<?= (int)$applicant['id'] ?>" class="btn btn-warning btn-sm"><?= icon('edit', '', 15) ?> ویرایش</a>
            <?php endif; ?>
            <a href="<?= $base ?>admin/output/single/<?= $e(rawurlencode($applicant['tracking_code'])) ?>" target="_blank" class="btn btn-outline-primary btn-sm"><?= icon('print', '', 15) ?> نسخهٔ چاپی</a>
            <a href="<?= $base ?>admin/applications" class="btn btn-outline-secondary btn-sm"><?= icon('chevron-right', '', 15) ?> بازگشت</a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ===== ستون اصلی ===== -->
    <div class="col-lg-8">

        <!-- اطلاعات هویتی -->
        <div class="card mb-4">
            <div class="card-header"><?= icon('id-card', 'me-1') ?> اطلاعات هویتی</div>
            <div class="card-body">
                <div class="info-grid">
                    <?= $ii('کد ملی', $e($applicant['national_code']), 'shield', true) ?>
                    <?= $ii('شمارهٔ همراه', $e($applicant['mobile']), 'phone', true) ?>
                    <?= $ii('کد پستی', $e($applicant['postal_code']), 'map-pin', true) ?>
                    <?= $ii('تاریخ تولد', persianDate((string)$applicant['birth_date']), 'clock') ?>
                    <?= $ii('نام پدر', $e($applicant['father_name'] ?: '—'), 'user') ?>
                    <?= $ii('وضعیت تأهل', $e($applicant['marital_status']) . ($applicant['marital_status'] === 'متأهل' ? ' — ' . (int)$applicant['children_count'] . ' فرزند' : ''), 'users') ?>
                    <?= $ii('وضعیت جسمانی', $e($applicant['physical_condition']) . ($applicant['physical_condition'] === 'معلولیت' ? ' — ' . $e($applicant['disability_details'] ?? '') : ''), 'shield') ?>
                    <?= $ii('دهک درآمدی', $applicant['income_decile'] ? $e($applicant['income_decile']) : '—', 'chart') ?>
                    <?= $ii('پروندهٔ قضایی / وام / اقساط معوقه', $e($applicant['has_court_case']) . ' / ' . $e($applicant['has_active_loan']) . ' / ' . $e($applicant['has_overdue_installments']), 'alert') ?>
                </div>
            </div>
        </div>

        <!-- اطلاعات شخص حقوقی (فاز ۱۰) -->
        <?php if (($person['person_type'] ?? 'حقیقی') === 'حقوقی'): ?>
        <div class="card mb-4" style="border-color:var(--turquoise)">
            <div class="card-header"><?= icon('users', 'me-1') ?> شخص حقوقی</div>
            <div class="card-body">
                <div class="info-grid">
                    <?= $ii('نام شرکت/مؤسسه', $e($person['company_name'] ?? '—'), 'users') ?>
                    <?= $ii('شناسهٔ ملی حقوقی', $e($person['company_national_id'] ?? '—'), 'shield', true) ?>
                    <?= $ii('شمارهٔ ثبت', $e($person['reg_number'] ?? '—'), 'file-text', true) ?>
                    <?= $ii('نوع شخصیت', $e($person['company_type'] ?? '—'), 'grid') ?>
                    <?= $ii('تاریخ ثبت', persianDate((string)($person['reg_date'] ?? '')), 'clock') ?>
                    <?= $ii('کد اقتصادی', $e($person['economic_code'] ?? '—'), 'money', true) ?>
                </div>
                <p class="small text-muted mb-0 mt-2">اطلاعات هویتی بالا مربوط به <strong>نمایندهٔ مجاز</strong> است.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- اطلاعات شغلی و ملک -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><?= icon('vase', 'me-1') ?> اطلاعات شغلی</div>
                    <div class="card-body">
                        <?php if ($jobDetail): ?>
                        <div class="info-grid" style="grid-template-columns:1fr">
                            <?= $ii('عنوان مجوز', $e($jobDetail['license_title']), 'award') ?>
                            <?= $ii('سابقهٔ فعالیت', (int)$jobDetail['experience_months'] . ' ماه', 'clock') ?>
                            <?= $ii('نحوهٔ آموزش', $e($jobDetail['training_method']), 'book') ?>
                        </div>
                        <?php else: ?><p class="text-muted small mb-0">ثبت نشده</p><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><?= icon('home', 'me-1') ?> اطلاعات ملک</div>
                    <div class="card-body">
                        <?php if ($propertyDetail): ?>
                        <div class="info-grid" style="grid-template-columns:1fr">
                            <?= $ii('وضعیت مالکیت', $e($propertyDetail['ownership_status']), 'shield') ?>
                            <?= $ii('استان / شهرستان / شهر', $e(implode(' / ', array_filter([$propertyDetail['province'], $propertyDetail['county'], $propertyDetail['city']]))), 'map-pin') ?>
                            <?php if (array_filter([$propertyDetail['district'] ?? '', $propertyDetail['rural_district'] ?? '', $propertyDetail['village'] ?? ''])): ?>
                                <?= $ii('بخش / دهستان / روستا', $e(implode(' / ', array_filter([$propertyDetail['district'] ?? '', $propertyDetail['rural_district'] ?? '', $propertyDetail['village'] ?? '']))), 'map-pin') ?>
                            <?php endif; ?>
                            <?= $ii('آدرس', $e(implode('، ', array_filter([$propertyDetail['address_street'] ?? '', $propertyDetail['address_alley'] ?? '', $propertyDetail['address_plaque'] ? 'پلاک ' . $propertyDetail['address_plaque'] : '']))), 'map-pin') ?>
                            <?= $ii('شناسهٔ قبض برق', $e($propertyDetail['electricity_bill_id']), 'file-text', true) ?>
                        </div>
                        <?php else: ?><p class="text-muted small mb-0">ثبت نشده</p><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- فیلدهای سفارشی -->
        <?php if (!empty($dynValues)): ?>
        <div class="card mt-4">
            <div class="card-header"><?= icon('form', 'me-1') ?> پاسخ به سؤالات تکمیلی (فیلدهای سفارشی فرم)</div>
            <div class="card-body">
                <div class="info-grid">
                    <?php foreach ($dynValues as $dv): ?>
                        <div class="info-item">
                            <div class="info-label"><?= icon('check', '', 14) ?> <?= $e($dv['label']) ?></div>
                            <div class="info-value">
                                <?php if (str_starts_with((string)$dv['field_value'], 'doc:')): ?>
                                    <?php $docId = (int)substr($dv['field_value'], 4); ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary view-doc-btn" data-id="<?= $docId ?>" data-applicant="<?= (int)$applicant['id'] ?>">
                                        <?= icon('image', '', 14) ?> مشاهدهٔ فایل پیوست
                                    </button>
                                <?php elseif ($dv['field_value'] === '' || $dv['field_value'] === null): ?>
                                    <span class="text-muted">—</span>
                                <?php else: ?>
                                    <?= nl2br($e($dv['field_value'])) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- مدارک -->
        <?php $regularDocs = array_filter($documents, fn($d) => $d['doc_type'] !== 'product_photos'); ?>
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= icon('folder', 'me-1') ?> مدارک پرونده</span>
                <span class="badge bg-secondary"><?= count($documents) ?> فایل</span>
            </div>
            <div class="card-body">
                <?php if ($regularDocs): ?>
                <div class="row g-3">
                    <?php foreach ($regularDocs as $doc): ?>
                        <?php [$dl, $di] = $docLabels[$doc['doc_type']] ?? [$doc['doc_type'], 'file-text']; ?>
                        <div class="col-6 col-md-4 col-xl-3">
                            <div class="card h-100 p-2 text-center" style="border-style:dashed;">
                                <?php if (in_array(safe_file_extension((string)$doc['file_name']), ['jpg', 'jpeg', 'png'], true)): ?>
                                    <img src="<?= $base ?>document/thumb/<?= (int)$doc['id'] ?>" class="rounded mb-1" style="height:90px;width:100%;object-fit:cover;" alt="پیش‌نمایش مدرک">
                                <?php else: ?>
                                    <div class="py-3" style="color:var(--terracotta)"><?= icon('file-text', '', 34) ?></div>
                                <?php endif; ?>
                                <span class="badge bg-info mb-1"><?= $e($dl) ?></span>
                                <small class="text-muted text-truncate d-block" dir="ltr"><?= $e($doc['original_name']) ?></small>
                                <small class="text-muted" style="font-size:.68rem"><?= persianDate((string)$doc['uploaded_at']) ?></small>
                                <button class="btn btn-sm btn-outline-primary mt-auto view-doc-btn" data-id="<?= (int)$doc['id'] ?>" data-applicant="<?= (int)$applicant['id'] ?>">
                                    <?= icon('eye', '', 14) ?> مشاهده
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?><p class="text-muted small mb-2">مدرکی بارگذاری نشده.</p><?php endif; ?>

                <!-- افزودن مدرک -->
                <?php if (Session::get('admin_role') !== null): ?>
                <hr class="my-3">
                <form method="post" action="<?= $base ?>admin/add-document" enctype="multipart/form-data" class="row g-2 align-items-end">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">نوع مدرک</label>
                        <select class="form-select form-select-sm" name="doc_type">
                            <option value="optional">سایر</option>
                            <option value="national_card">کارت ملی</option>
                            <option value="birth_cert">شناسنامه</option>
                            <option value="ownership_doc">مدرک مالکیت</option>
                            <option value="vocational_cert">گواهی فنی‌وحرفه‌ای</option>
                            <option value="education_degree">مدرک تحصیلی</option>
                            <option value="product_photos">تصویر محصولات</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">فایل (jpg/png/pdf — حداکثر ۲MB)</label>
                        <input type="file" class="form-control form-control-sm" name="document_file" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="submit" class="btn btn-sm btn-success w-100"><?= icon('upload', '', 14) ?> افزودن مدرک</button>
                    </div>
                </form>
                <?php if (!empty($errors['document'])): ?><div class="alert alert-danger py-1 small mt-2 mb-0"><?= $e(is_array($errors['document']) ? implode(' ', $errors['document']) : $errors['document']) ?></div><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- تصاویر محصولات -->
        <?php $productPhotos = array_filter($documents, fn($d) => $d['doc_type'] === 'product_photos'); ?>
        <?php if ($productPhotos): ?>
        <div class="card mt-4">
            <div class="card-header"><?= icon('image', 'me-1') ?> تصاویر متقاضی با محصولات (<?= count($productPhotos) ?>)</div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach ($productPhotos as $doc): ?>
                        <div class="col-4 col-md-2">
                            <?php if (in_array(safe_file_extension((string)$doc['file_name']), ['jpg', 'jpeg', 'png'], true)): ?>
                                <img src="<?= $base ?>document/thumb/<?= (int)$doc['id'] ?>" class="img-thumbnail w-100" style="height:84px;object-fit:cover;cursor:pointer" alt="" onclick="this.nextElementSibling.click()">
                            <?php else: ?>
                                <div class="text-center py-3 border rounded"><?= icon('file-text', '', 24) ?></div>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-primary w-100 mt-1 view-doc-btn py-0" data-id="<?= (int)$doc['id'] ?>" data-applicant="<?= (int)$applicant['id'] ?>"><?= icon('eye', '', 13) ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== ستون کناری ===== -->
    <div class="col-lg-4">

        <!-- تغییر وضعیت + مسیر رسیدگی -->
        <div class="card mb-4">
            <div class="card-header"><?= icon('task', 'me-1') ?> مسیر رسیدگی و تغییر وضعیت</div>
            <div class="card-body">
                <ul class="status-track mb-3">
                    <?php foreach ($pipeline as $st): ?>
                        <?php
                        $o = $statusMeta[$st]['order'] ?? 0;
                        $cls = $st === $curStatus ? 'current' : (($o < $curOrder && !$isTerminal) ? 'done' : (($o < $curOrder && $isTerminal) ? 'done' : ''));
                        ?>
                        <li class="<?= $cls ?>"><?= $e($st) ?></li>
                    <?php endforeach; ?>
                    <?php if ($isTerminal): ?>
                        <li class="rejected"><?= $e($curStatus) ?></li>
                    <?php endif; ?>
                </ul>

                <form method="post" action="<?= $base ?>admin/change-status">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">
                    <label class="form-label small">انتقال به وضعیت:</label>
                    <select class="form-select form-select-sm mb-2" name="new_status" <?= $isTerminal ? 'disabled' : '' ?>>
                        <option value="">انتخاب کنید...</option>
                        <?php foreach ($_availStatuses as $st): ?>
                            <option value="<?= $e($st) ?>" <?= $st === $curStatus ? 'disabled' : '' ?>><?= $e($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm w-100" <?= $isTerminal ? 'disabled' : '' ?>>
                        <?= icon('check', '', 15) ?> اعمال تغییر
                    </button>
                    <?php if ($isTerminal): ?>
                        <div class="alert alert-danger py-1 px-2 small mt-2 mb-0">پرونده بسته شده است (<?= $e($curStatus) ?>) و وضعیت آن تغییر نمی‌کند.</div>
                    <?php endif; ?>
                    <p class="text-muted mb-0 mt-2" style="font-size:.72rem">مسیر یکطرفه است؛ فقط ارتقا به مراحل بعد یا «لغو»/«رد درخواست».</p>
                </form>
            </div>
        </div>

        <!-- تایم‌لاین -->
        <div class="card mb-4">
            <div class="card-header"><?= icon('clock', 'me-1') ?> تاریخچهٔ رویدادها</div>
            <div class="card-body" style="max-height:430px; overflow-y:auto;">
                <ul class="timeline">
                    <?php foreach (array_reverse($statusLogs) as $log): ?>
                        <?php
                        $class = '';
                        if (str_contains($log['status_name'], 'لغو') || str_contains($log['status_name'], 'رد')) $class = 'cancel';
                        elseif (str_contains($log['status_name'], 'صدور') || str_contains($log['status_name'], 'تأیید') || str_contains($log['status_name'], 'تحویل')) $class = 'success';
                        elseif (str_contains($log['status_name'], 'تغییر کد پستی') || str_contains($log['status_name'], 'چاپ')) $class = 'gold';
                        ?>
                        <li class="timeline-item <?= $class ?>">
                            <div class="time"><?= persianDate((string)$log['created_at']) ?></div>
                            <div><strong><?= $e($log['status_name']) ?></strong></div>
                            <?php if (!empty($log['description'])): ?>
                                <div class="text-muted small"><?= $e($log['description']) ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- سوابق کد پستی -->
        <?php if (!empty($postalChanges)): ?>
        <div class="card">
            <div class="card-header"><?= icon('mail', 'me-1') ?> سوابق تغییر کد پستی</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($postalChanges as $pc): ?>
                    <?php $pcBadge = match ($pc['status'] ?? 'در انتظار') { 'تأیید شده' => 'success', 'رد شده' => 'danger', default => 'warning text-dark' }; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <span class="small">
                            <code><?= $e($pc['old_postal_code']) ?></code> ← <code><?= $e($pc['new_postal_code']) ?></code>
                            <span class="text-muted d-block"><?= persianDate((string)$pc['requested_at']) ?></span>
                        </span>
                        <span class="badge bg-<?= $pcBadge ?>"><?= $e($pc['status'] ?? 'در انتظار') ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <!-- پرداخت‌ها -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><?= icon('money', 'me-1') ?> پرداخت هزینه</span>
                <?php
                // تعیین وضعیت نمایش دکمه‌ها:
                // - اگر payment_required = 0 → «درخواست پرداخت هزینه» (gold)
                // - اگر payment_required = 1 و پرداخت هنوز در انتظار → «لغو درخواست پرداخت» (outline-danger)
                // - اگر پرداخت تأیید شده → دکمه لغو مخفی، نمایش «درخواست پرداخت جدید» (gold)
                $hasApprovedPayment = false;
                foreach ($payments as $pay) {
                    if ($pay['status'] === 'تأیید شده') { $hasApprovedPayment = true; break; }
                }
                $showCancelButton = $paymentRequired && !$hasApprovedPayment;
                $showRequestButton = !$paymentRequired || $hasApprovedPayment;
                $requestLabel = $hasApprovedPayment ? 'درخواست پرداخت جدید (هزینهٔ اضافی)' : 'درخواست پرداخت هزینه';
                $requestConfirmAmount = number_format((int)$feeAmount);
                ?>

                <?php if ($showCancelButton): ?>
                    <!-- دکمه لغو درخواست پرداخت -->
                    <form method="post" action="<?= $base ?>admin/request-payment">
                        <?= Session::csrfField() ?>
                        <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">
                        <input type="hidden" name="enable" value="0">
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('درخواست پرداخت غیرفعال شود؟');">
                            <?= icon('x', '', 14) ?> لغو درخواست پرداخت
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($showRequestButton): ?>
                    <!-- دکمه باز کردن مودال درخواست پرداخت -->
                    <button type="button" class="btn btn-sm <?= $hasApprovedPayment ? 'btn-outline-primary' : 'btn-gold' ?>"
                            data-bs-toggle="modal" data-bs-target="#requestPaymentModal">
                        <?= icon('money', '', 14) ?> <?= $requestLabel ?>
                    </button>

                    <!-- مودال درخواست پرداخت با مبلغ دلخواه -->
                    <div class="modal fade" id="requestPaymentModal" tabindex="-1" aria-labelledby="requestPaymentModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form method="post" action="<?= $base ?>admin/request-payment">
                                    <?= Session::csrfField() ?>
                                    <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">
                                    <input type="hidden" name="enable" value="1">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="requestPaymentModalLabel"><?= icon('money','me-2',18) ?> درخواست پرداخت</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-muted small">مبلغ پیشنهادی سامانه: <strong><?= number_format((int)$feeAmount) ?></strong> تومان</p>
                                        <label class="form-label fw-bold">مبلغ مورد نظر (تومان) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-lg" name="custom_amount"
                                               value="<?= (int)$feeAmount ?>" min="1000" max="100000000" step="1000" required
                                               dir="ltr" style="text-align:right; font-size:1.2rem">
                                        <small class="text-muted">مبلغ به تومان وارد شود. حداقل: ۱٬۰۰۰ — حداکثر: ۱۰۰٬۰۰۰٬۰۰۰</small>
                                        <hr>
                                        <p class="small text-muted mb-0">پس از ارسال، به کاربر اعلان می‌رود و در «پرداخت‌های من» فرم رسید نمایش داده می‌شود.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                                        <button type="submit" class="btn btn-gold"><?= icon('check','',16) ?> ارسال درخواست پرداخت</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- اطلاعات محاسبهٔ هزینه -->
            <?php if (!empty($feeInfo) && !empty($feeInfo['group'])): ?>
            <div class="card-body bg-light py-2 border-bottom">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 small">
                    <span>
                        <span class="badge bg-info fs-6"><?= htmlspecialchars($feeInfo['group']['icon'] ?? '') ?> <?= htmlspecialchars($feeInfo['group']['name']) ?></span>
                        <?php if (!empty($feeInfo['is_rural'])): ?>
                            <span class="badge bg-success">معافیت روستایی</span>
                        <?php endif; ?>
                        <?php if (!empty($feeInfo['fallback_used'])): ?>
                            <span class="badge bg-warning text-dark">fallback (دهک خالی)</span>
                        <?php endif; ?>
                    </span>
                    <span class="text-muted"><?= htmlspecialchars($feeInfo['reason']) ?></span>
                    <span class="fw-bold fs-5"><?= number_format((int)$feeAmount) ?> تومان</span>
                </div>
                <?php if (!empty($feeInfo['suspicion_flags'])): ?>
                    <div class="alert alert-warning mt-2 mb-0 py-1 small">
                        <strong>⚠️ موارد مشکوک:</strong>
                        <?php foreach ($feeInfo['suspicion_flags'] as $flag): ?>
                            <span class="badge bg-warning text-dark me-1"><?= htmlspecialchars($flag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($payments)): ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($payments as $pay): ?>
                    <?php $pb = match ($pay['status']) { 'تأیید شده' => 'success', 'رد شده' => 'danger', default => 'warning text-dark' }; ?>
                    <li class="list-group-item small">
                        <div class="d-flex justify-content-between flex-wrap gap-1">
                            <span>
                                <strong><?= number_format((int)$pay['amount']) ?> تومان</strong>
                                <?php if (!empty($pay['final_amount']) && (int)$pay['final_amount'] !== (int)$pay['amount']): ?>
                                    <span class="text-success">(نهایی پس از تخفیف: <?= number_format((int)$pay['final_amount']) ?>)</span>
                                <?php endif; ?>
                                — <?= htmlspecialchars($pay['purpose']) ?>
                                <?php if (!empty($pay['channel_title'])): ?><span class="text-muted">(<?= htmlspecialchars($pay['channel_title']) ?>)</span><?php endif; ?>
                            </span>
                            <span class="badge bg-<?= $pb ?>"><?= htmlspecialchars($pay['status']) ?></span>
                        </div>
                        <div class="text-muted" style="font-size:.72rem">
                            ثبت: <?= persianDate((string)$pay['created_at']) ?>
                            <?php if (!empty($pay['reference_code'])): ?> • پیگیری: <code><?= htmlspecialchars($pay['reference_code']) ?></code><?php endif; ?>
                            <?php if (!empty($pay['reject_reason'])): ?> • دلیل رد: <?= htmlspecialchars($pay['reject_reason']) ?><?php endif; ?>
                            <?php if (!empty($pay['discount_reason'])): ?> • دلیل تخفیف: <?= htmlspecialchars($pay['discount_reason']) ?><?php endif; ?>
                            <?php if (!empty($pay['receipt_document_id'])): ?>
                                • <button type="button" class="btn btn-link btn-sm p-0 align-baseline view-doc-btn" data-id="<?= (int)$pay['receipt_document_id'] ?>" data-applicant="<?= (int)$applicant['id'] ?>">مشاهدهٔ رسید</button>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="card-body"><p class="text-muted small mb-0">پرداختی ثبت نشده است.</p></div>
            <?php endif; ?>

            <!-- کارت تیک خدمات (تنها در صورت وجود پرداخت با fee_group_id) -->
            <?php
            // یافتن آخرین پرداخت با fee_group_id برای نمایش تیک خدمات
            $tickPayment = null;
            foreach ($payments as $pay) {
                if (!empty($pay['fee_group_id'])) {
                    $tickPayment = $pay;
                    break; // آخرین (همان اول چون DESC مرتب شده)
                }
            }
            ?>
            <?php if ($tickPayment): ?>
                <?php
                $tickModel = new PaymentServiceTick();
                $auditModel = new PaymentAuditLog();
                $ticks = $tickModel->findByPaymentId((int)$tickPayment['id']);
                $sumAll = $tickModel->sumAll((int)$tickPayment['id']);
                $sumDone = $tickModel->sumDone((int)$tickPayment['id']);
                $untickedCount = $tickModel->countUnticked((int)$tickPayment['id']);
                $finalAmount = (int)($tickPayment['final_amount'] ?? $tickPayment['amount'] ?? 0);
                $originalAmount = (int)($tickPayment['original_amount'] ?? $tickPayment['amount'] ?? 0);
                $discountAmount = $originalAmount - $finalAmount;
                $auditLogs = $auditModel->findByPaymentId((int)$tickPayment['id']);
                $requiresReason = FeeCalculatorService::discountRequiresReason((int)$tickPayment['id']);
                $isExcessive = FeeCalculatorService::isDiscountExcessive((int)$tickPayment['id']);
                $isPendingPayment = $tickPayment['status'] === 'در انتظار بررسی';
                ?>
            <div class="card-body border-top">
                <h6 class="fw-bold mb-3"><?= icon('check-square', 'me-1') ?> خدمات انجام‌شده برای این پرونده</h6>

                <?php if ($isExcessive): ?>
                    <div class="alert alert-danger py-2 small">
                        ⚠️ <strong>تخفیف بیش از حد مجاز:</strong> مبلغ کسرشده از ۵۰٪ مبلغ اصلی بیشتر است.
                        برای تأیید نهایی نیازمند مجوز مدیر ارشد است.
                    </div>
                <?php endif; ?>

                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">انجام شد</th>
                            <th>خدمت</th>
                            <th style="width:140px" class="text-end">مبلغ (تومان)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ticks)): ?>
                            <tr><td colspan="3" class="text-muted text-center">هیچ تیک خدمتی برای این پرداخت وجود ندارد.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ticks as $t): ?>
                                <tr data-tick-id="<?= (int)$t['id'] ?>" data-payment-id="<?= (int)$tickPayment['id'] ?>">
                                    <td>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input tick-checkbox" type="checkbox"
                                                   <?= (int)$t['is_done'] === 1 ? 'checked' : '' ?>
                                                   data-tick-id="<?= (int)$t['id'] ?>"
                                                   data-payment-id="<?= (int)$tickPayment['id'] ?>"
                                                   data-amount="<?= (int)$t['original_amount'] ?>"
                                                   <?= !$isPendingPayment ? 'disabled' : '' ?>>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($t['item_label']) ?>
                                        <?php if (!empty($t['untick_reason'])): ?>
                                            <br><small class="text-muted">دلیل: <?= htmlspecialchars($t['untick_reason']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= number_format((int)$t['original_amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="2" class="text-end">مبلغ اصلی (همهٔ خدمات):</th>
                            <th class="text-end"><?= number_format($originalAmount) ?></th>
                        </tr>
                        <?php if ($discountAmount > 0): ?>
                        <tr class="table-warning">
                            <th colspan="2" class="text-end">کسر خدمات انجام‌نشده (<?= $untickedCount ?> مورد):</th>
                            <th class="text-end text-danger">- <?= number_format($discountAmount) ?></th>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-success">
                            <th colspan="2" class="text-end">مبلغ نهایی قابل‌قبول:</th>
                            <th class="text-end fs-5" id="finalAmountCell"><?= number_format($finalAmount) ?></th>
                        </tr>
                        <tr>
                            <th colspan="2" class="text-end">مبلغ واریزی متقاضی (از رسید):</th>
                            <th class="text-end <?= (int)$tickPayment['amount'] >= $finalAmount ? 'text-success' : 'text-danger' ?>">
                                <?= number_format((int)$tickPayment['amount']) ?>
                                <?php if ((int)$tickPayment['amount'] >= $finalAmount): ?>
                                    ✓
                                <?php else: ?>
                                    ⚠ کمتر از مبلغ نهایی
                                <?php endif; ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>

                <?php if ($requiresReason && $untickedCount > 0): ?>
                    <div class="mb-3">
                        <label class="form-label small">دلیل تخفیف (<?= $untickedCount ?> خدمت تیک‌برداشته — توضیح اجباری)</label>
                        <textarea class="form-control form-control-sm" id="discountReason"
                                  placeholder="مثلاً: تصویر مجوز هنوز بارگذاری نشده چون صادر نشده"><?= htmlspecialchars($tickPayment['discount_reason'] ?? '') ?></textarea>
                    </div>
                <?php endif; ?>

                <?php if (!$isPendingPayment): ?>
                    <div class="alert alert-info py-2 small mb-0">
                        ℹ️ این پرداخت در وضعیت «<?= htmlspecialchars($tickPayment['status']) ?>» است و تیک‌ها قابل تغییر نیستند.
                    </div>
                <?php endif; ?>

                <?php if (!empty($auditLogs)): ?>
                    <details class="mt-3">
                        <summary class="small text-muted cursor-pointer">تاریخچهٔ تغییرات (<?= count($auditLogs) ?> مورد)</summary>
                        <ul class="list-unstyled small mt-2" style="max-height:200px;overflow-y:auto">
                            <?php foreach ($auditLogs as $log): ?>
                                <li class="mb-1">
                                    <code><?= persianDate((string)$log['created_at']) ?> <?= date('H:i', strtotime($log['created_at'])) ?></code>
                                    — <strong><?= htmlspecialchars($log['action']) ?></strong>
                                    <?php if (!empty($log['note'])): ?>: <?= htmlspecialchars($log['note']) ?><?php endif; ?>
                                    <span class="text-muted">(<?= htmlspecialchars($log['display_name'] ?? $log['username'] ?? '?') ?>)</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- تصویر مجوز (فاز ۶) -->
        <div class="card mb-4" style="border-color:var(--persian-green)">
            <div class="card-header"><?= icon('award', 'me-1') ?> تصویر مجوز صادرشده</div>
            <div class="card-body">
                <?php if ($licenseFile && $licenseDoc): ?>
                    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                        <?php if (in_array(safe_file_extension((string)$licenseDoc['file_name']), ['jpg','jpeg','png'], true)): ?>
                            <img src="<?= $base ?>document/thumb/<?= (int)$licenseDoc['id'] ?>" style="width:88px;height:64px;object-fit:cover;border-radius:8px;" alt="">
                        <?php else: ?>
                            <span style="color:var(--persian-green)"><?= icon('file-text', '', 30) ?></span>
                        <?php endif; ?>
                        <div class="small">
                            <div>بارگذاری‌شده — دفعات دریافت: <strong><?= (int)$licenseFile['download_count'] ?></strong></div>
                            <div class="text-muted">اولین دریافت: <?= $licenseFile['first_accessed_at'] ? persianDate((string)$licenseFile['first_accessed_at']) : '—' ?></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary view-doc-btn ms-auto" data-id="<?= (int)$licenseDoc['id'] ?>" data-applicant="<?= (int)$applicant['id'] ?>"><?= icon('eye', '', 14) ?> مشاهده</button>
                    </div>
                <?php else: ?>
                    <p class="small text-muted">تصویر مجوز هنوز بارگذاری نشده. پس از «صدور مجوز»، تصویر را اینجا بارگذاری کنید تا لینک دریافت برای متقاضی فعال شود.</p>
                <?php endif; ?>

                <!-- وضعیت مجوز و سلسله‌مراتب (فاز ۷) -->
                <?php if (Session::get('admin_role') === 'admin'): ?>
                <hr class="my-3">
                <form method="post" action="<?= $base ?>admin/license-state" class="row g-2 align-items-end">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">
                    <div class="col-5">
                        <label class="form-label small mb-1">وضعیت مجوز</label>
                        <select class="form-select form-select-sm" name="license_state">
                            <?php foreach (['جاری', 'صادر شده', 'باطل شده', 'منقضی'] as $ls): ?>
                                <option value="<?= $ls ?>" <?= ($applicant['license_state'] ?? 'جاری') === $ls ? 'selected' : '' ?>><?= $ls ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-7">
                        <label class="form-label small mb-1">اعتبار تا (شمسی)</label>
                        <?php $vu = gregorian_to_jalali_array((string)($applicant['license_valid_until'] ?? '')); ?>
                        <div class="row g-1">
                            <div class="col-4"><select class="form-select form-select-sm" name="valid_year"><option value="">سال</option>
                                <?php $cy = currentPersianYear(); for ($y = $cy - 2; $y <= $cy + 10; $y++): ?><option value="<?= $y ?>" <?= (int)($vu['year'] ?? 0) === $y ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?>
                            </select></div>
                            <div class="col-4"><select class="form-select form-select-sm" name="valid_month"><option value="">ماه</option>
                                <?php $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                                foreach ($months as $mi => $mn): ?><option value="<?= $mi + 1 ?>" <?= (int)($vu['month'] ?? 0) === $mi + 1 ? 'selected' : '' ?>><?= $mn ?></option><?php endforeach; ?>
                            </select></div>
                            <div class="col-4"><select class="form-select form-select-sm" name="valid_day"><option value="">روز</option>
                                <?php for ($d = 1; $d <= 31; $d++): ?><option value="<?= $d ?>" <?= (int)($vu['day'] ?? 0) === $d ? 'selected' : '' ?>><?= $d ?></option><?php endfor; ?>
                            </select></div>
                        </div>
                    </div>
                    <div class="col-8">
                        <label class="form-label small mb-1">پروندهٔ والد (تمدید/ارتقا از)</label>
                        <select class="form-select form-select-sm" name="parent_application_id">
                            <option value="0">— بدون والد —</option>
                            <?php foreach (($siblingApps ?? []) as $sb): ?>
                                <option value="<?= (int)$sb['id'] ?>" <?= (int)($applicant['parent_application_id'] ?? 0) === (int)$sb['id'] ? 'selected' : '' ?>>
                                    <?= $e($sb['tracking_code']) ?> — <?= $e($sb['license_title'] ?? '') ?> (<?= $e($sb['current_status']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><?= icon('save', '', 14) ?> ذخیره</button>
                    </div>
                </form>
                <?php endif; ?>

                <?php if (in_array($applicant['current_status'], ['صدور مجوز', 'تحویل مجوز', 'تأیید نهایی'], true) || ($licenseFile && $licenseDoc)): ?>
                <form method="post" action="<?= $base ?>admin/license/upload" enctype="multipart/form-data" class="row g-2 align-items-end">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">
                    <div class="col-8">
                        <label class="form-label small mb-1"><?= ($licenseFile && $licenseDoc) ? 'جایگزینی تصویر مجوز' : 'بارگذاری تصویر مجوز' ?> (jpg/png/pdf)</label>
                        <input type="file" class="form-control form-control-sm" name="license_file" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-sm btn-success w-100"><?= icon('upload', '', 14) ?> بارگذاری</button>
                    </div>
                    <?php if (!empty($errors['license'])): ?><div class="col-12"><div class="alert alert-danger py-1 small mb-0"><?= htmlspecialchars(implode(' ', $errors['license'])) ?></div></div><?php endif; ?>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- سایر پرونده‌های شخص (چندمجوزی — فاز ۷) -->
        <?php if (!empty($siblingApps)): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <span><?= icon('users', 'me-1') ?> سایر پرونده‌های این شخص</span>
                <span class="badge bg-secondary"><?= count($siblingApps) ?></span>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($siblingApps as $sb): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2 flex-wrap small">
                    <span>
                        <a href="<?= $base ?>admin/view/<?= (int)$sb['id'] ?>" class="text-decoration-none fw-bold"><?= $e($sb['license_title'] ?? '—') ?></a>
                        <code class="ms-1" style="font-size:.68rem"><?= $e($sb['tracking_code']) ?></code>
                        <?php if ((int)($applicant['parent_application_id'] ?? 0) === (int)$sb['id']): ?><span class="badge bg-info">والد این پرونده</span><?php endif; ?>
                    </span>
                    <span>
                        <span class="badge bg-<?= ($statusMeta[$sb['current_status']]['color'] ?? 'secondary') ?>"><?= $e($sb['current_status']) ?></span>
                        <span class="badge bg-light text-dark border"><?= $e($sb['license_state'] ?? 'جاری') ?></span>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- مودال پیش‌نمایش مدرک -->
<div class="modal fade" id="docModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= icon('eye', 'me-1') ?> پیش‌نمایش مدرک</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
      </div>
      <div class="modal-body text-center" id="docContent"></div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.view-doc-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
        const formData = new FormData();
        formData.append('document_id', this.dataset.id);
        formData.append('applicant_id', this.dataset.applicant);
        // توکن CSRF از متاتگ قالب
        formData.append('_csrf', document.querySelector('meta[name="csrf-token"]')?.content || '');
        const box = document.getElementById('docContent');
        box.innerHTML = '<div class="py-5 text-muted">در حال بارگذاری…</div>';
        try {
            const response = await fetch('<?= $base ?>document/view', { method: 'POST', body: formData });
            if (!response.ok) { box.innerHTML = '<div class="py-5 text-danger">خطا در بارگذاری فایل</div>'; return; }
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const contentType = response.headers.get('Content-Type') || '';
            if (contentType.startsWith('image/')) {
                box.innerHTML = `<img src="${url}" class="img-fluid" style="max-height:75vh;">`;
            } else if (contentType === 'application/pdf') {
                box.innerHTML = `<embed src="${url}" type="application/pdf" width="100%" height="560px">`;
            } else {
                box.innerHTML = `<a href="${url}" class="btn btn-primary" download>دانلود فایل</a>`;
            }
            new bootstrap.Modal(document.getElementById('docModal')).show();
        } catch (e) {
            box.innerHTML = '<div class="py-5 text-danger">خطا در ارتباط با سرور</div>';
        }
    });
});

// =====================================================
// تیک خدمات پرداخت (AJAX)
// =====================================================
document.querySelectorAll('.tick-checkbox').forEach(cb => {
    cb.addEventListener('change', async function () {
        const tickId = this.dataset.tickId;
        const paymentId = this.dataset.paymentId;
        const done = this.checked ? '1' : '0';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // دریافت دلیل از textarea (اگر وجود دارد)
        let reason = '';
        const reasonEl = document.getElementById('discountReason');
        if (reasonEl && !this.checked) {
            reason = reasonEl.value.trim();
            if (!reason) {
                if (!confirm('بدون ذکر دلیل، تیک این خدمت برداشته شود؟')) {
                    this.checked = true;
                    return;
                }
            }
        }

        const formData = new FormData();
        formData.append('tick_id', tickId);
        formData.append('payment_id', paymentId);
        formData.append('done', done);
        formData.append('reason', reason);
        formData.append('_csrf', csrfToken);

        try {
            const response = await fetch('<?= $base ?>admin/payment/tick', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                // به‌روزرسانی مبلغ نهایی
                const finalCell = document.getElementById('finalAmountCell');
                if (finalCell) {
                    finalCell.textContent = new Intl.NumberFormat('fa-IR').format(data.final_amount);
                }
                // به‌روزرسانی رنگ ردیف
                const row = this.closest('tr');
                if (row) {
                    row.classList.toggle('table-warning', !data.is_done);
                }
                // اگر دلیل لازم است و داده نشده، هشدار
                if (!data.is_done && !reason) {
                    if (reasonEl) {
                        reasonEl.focus();
                        reasonEl.classList.add('is-invalid');
                    }
                }
                // به‌روزرسانی تودار شدن سطر
                const siblingTr = row?.querySelector('small.text-muted');
                if (!data.is_done && reason) {
                    if (!siblingTr) {
                        const small = document.createElement('small');
                        small.className = 'text-muted d-block';
                        small.textContent = 'دلیل: ' + reason;
                        row.querySelector('td:nth-child(2)').appendChild(small);
                    }
                }
            } else {
                alert('خطا: ' + (data.error || 'نامشخص'));
                this.checked = !this.checked; // برگرداندن
            }
        } catch (e) {
            alert('خطا در ارتباط با سرور');
            this.checked = !this.checked;
        }
    });
});
</script>
