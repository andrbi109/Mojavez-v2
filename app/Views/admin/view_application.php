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

// گام‌های نمایشی مسیر رسیدگی (بدون رویدادهای غیرخطی)
$pipeline = ['تکمیل اطلاعات', 'احراز نشانی', 'بررسی کارشناسی رشته', 'ثبت درخواست صدور',
    'بارگذاری مدارک به سازمان', 'تأیید اولیه', 'تأیید نهایی', 'صدور مجوز', 'تحویل مجوز'];

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
                        <?php foreach ($statuses as $st): ?>
                            <?php if (!$statusMeta[$st]['manual']) continue; ?>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= icon('money', 'me-1') ?> پرداخت هزینه</span>
                <form method="post" action="<?= $base ?>admin/request-payment">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="applicant_id" value="<?= (int)$applicant['id'] ?>">
                    <input type="hidden" name="enable" value="<?= $paymentRequired ? '0' : '1' ?>">
                    <button type="submit" class="btn btn-sm <?= $paymentRequired ? 'btn-outline-danger' : 'btn-gold' ?>"
                            onclick="return confirm('<?= $paymentRequired ? 'درخواست پرداخت غیرفعال شود؟' : 'پرداخت هزینهٔ ثبت‌نام (' . number_format((int)$feeAmount) . ' تومان) برای متقاضی فعال شود؟' ?>');">
                        <?= $paymentRequired ? icon('x', '', 14) . ' لغو درخواست پرداخت' : icon('money', '', 14) . ' درخواست پرداخت هزینه' ?>
                    </button>
                </form>
            </div>
            <?php if (!empty($payments)): ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($payments as $pay): ?>
                    <?php $pb = match ($pay['status']) { 'تأیید شده' => 'success', 'رد شده' => 'danger', default => 'warning text-dark' }; ?>
                    <li class="list-group-item small">
                        <div class="d-flex justify-content-between flex-wrap gap-1">
                            <span>
                                <strong><?= number_format((int)$pay['amount']) ?> تومان</strong>
                                — <?= htmlspecialchars($pay['purpose']) ?>
                                <?php if (!empty($pay['channel_title'])): ?><span class="text-muted">(<?= htmlspecialchars($pay['channel_title']) ?>)</span><?php endif; ?>
                            </span>
                            <span class="badge bg-<?= $pb ?>"><?= htmlspecialchars($pay['status']) ?></span>
                        </div>
                        <div class="text-muted" style="font-size:.72rem">
                            ثبت: <?= persianDate((string)$pay['created_at']) ?>
                            <?php if (!empty($pay['reference_code'])): ?> • پیگیری: <code><?= htmlspecialchars($pay['reference_code']) ?></code><?php endif; ?>
                            <?php if (!empty($pay['reject_reason'])): ?> • دلیل رد: <?= htmlspecialchars($pay['reject_reason']) ?><?php endif; ?>
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
</script>
