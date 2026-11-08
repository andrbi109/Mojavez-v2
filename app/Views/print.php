<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="print-container">
    <!-- === سربرگ === -->
    <div class="doc-header avoid-break">
        <div class="row align-items-center">
            <div class="col-7">
                <h3>سامانهٔ مجوزهای صنایع دستی</h3>
                <p class="subtitle">ادارهٔ کل میراث فرهنگی، گردشگری و صنایع دستی</p>
                <p class="meta">مدیریت سامانه: <?= $e(owner_name()) ?> — <span dir="ltr">@<?= $e(setting('owner_username', 'Derogaramin')) ?></span> (ایتا / روبیکا / بله)</p>
            </div>
            <div class="col-5">
                <div class="row g-2 text-end">
                    <div class="col-6">
                        <div class="tracking-box">
                            <div class="label">کد رهگیری</div>
                            <div class="code" dir="ltr"><?= $e($applicant['tracking_code']) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="tracking-box">
                            <div class="label">تاریخ چاپ</div>
                            <div class="code" dir="ltr"><?= jdate('Y/m/d') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- === محتوای اصلی === -->
    <div class="doc-body">

    <!-- ۱. اطلاعات هویتی -->
    <div class="section-title avoid-break">
        <span class="num">۱</span> اطلاعات هویتی
    </div>
    <table class="info-table avoid-break">
        <tr><td>نام و نام خانوادگی</td><td><?= $e($applicant['first_name'] . ' ' . $applicant['last_name']) ?></td></tr>
        <tr><td>کد ملی</td><td dir="ltr"><?= $e($applicant['national_code']) ?></td></tr>
        <tr><td>کد پستی</td><td dir="ltr"><?= $e($applicant['postal_code']) ?></td></tr>
        <tr><td>شماره همراه</td><td dir="ltr"><?= $e($applicant['mobile']) ?></td></tr>
        <tr><td>تاریخ تولد</td><td><?= persianDate((string)$applicant['birth_date']) ?></td></tr>
        <tr><td>نام پدر</td><td><?= $e($applicant['father_name'] ?? '—') ?></td></tr>
        <tr><td>وضعیت تأهل</td><td><?= $e($applicant['marital_status'] ?? '—') ?><?= ($applicant['marital_status'] ?? '') === 'متأهل' ? ' (تعداد فرزندان: ' . (int)$applicant['children_count'] . ')' : '' ?></td></tr>
        <tr><td>وضعیت جسمانی</td><td><?= $e($applicant['physical_condition'] ?? '—') ?><?= ($applicant['physical_condition'] ?? '') === 'معلولیت' ? ' — ' . $e($applicant['disability_details'] ?? '') : '' ?></td></tr>
        <tr><td>دهک درآمدی</td><td><?= $applicant['income_decile'] ?: '—' ?></td></tr>
        <tr><td>پرونده قضایی / وام / اقساط</td><td><?= $e($applicant['has_court_case']) ?> / <?= $e($applicant['has_active_loan']) ?> / <?= $e($applicant['has_overdue_installments']) ?></td></tr>
    </table>

    <!-- ۲. اطلاعات شغلی -->
    <div class="section-title avoid-break">
        <span class="num">۲</span> اطلاعات شغلی
    </div>
    <?php if ($jobDetail): ?>
    <table class="info-table avoid-break">
        <tr><td>عنوان مجوز</td><td><?= $e($jobDetail['license_title']) ?></td></tr>
        <tr><td>سابقه فعالیت</td><td><?= (int)$jobDetail['experience_months'] ?> ماه</td></tr>
        <tr><td>نحوه آموزش</td><td><?= $e($jobDetail['training_method']) ?></td></tr>
    </table>
    <?php else: ?>
    <p class="text-muted small">ثبت نشده</p>
    <?php endif; ?>

    <!-- ۳. اطلاعات ملک -->
    <div class="section-title avoid-break">
        <span class="num">۳</span> اطلاعات ملک
    </div>
    <?php if ($propertyDetail): ?>
    <table class="info-table avoid-break">
        <tr><td>وضعیت مالکیت</td><td><?= $e($propertyDetail['ownership_status']) ?></td></tr>
        <tr><td>استان / شهرستان</td><td><?= $e(($propertyDetail['province'] ?? '') . ' / ' . ($propertyDetail['county'] ?? '')) ?></td></tr>
        <tr><td>شهر / روستا</td><td><?= $e(($propertyDetail['city'] ?? '') . ($propertyDetail['village'] ? ' / ' . $propertyDetail['village'] : '')) ?></td></tr>
        <tr><td>آدرس کامل</td><td>خیابان <?= $e($propertyDetail['address_street'] ?? '—') ?>، کوچه <?= $e($propertyDetail['address_alley'] ?? '—') ?>، پلاک <?= $e($propertyDetail['address_plaque'] ?? '—') ?></td></tr>
        <tr><td>شناسه قبض برق</td><td dir="ltr"><?= $e($propertyDetail['electricity_bill_id']) ?></td></tr>
    </table>
    <?php else: ?>
    <p class="text-muted small">ثبت نشده</p>
    <?php endif; ?>

    <!-- ۴. سؤالات تکمیلی (فیلدهای پویا) -->
    <?php if (!empty($dynValues)): ?>
    <div class="section-title avoid-break">
        <span class="num">۴</span> سؤالات تکمیلی پرونده
    </div>
    <div class="avoid-break">
        <?php $dynMode = 'print'; require VIEW_PATH . '/_dynamic_values.php'; ?>
    </div>
    <?php endif; ?>

    <!-- ۵. تاریخچه وضعیت‌ها -->
    <div class="section-title avoid-break">
        <span class="num"><?= !empty($dynValues) ? '۵' : '۴' ?></span> تاریخچه وضعیت‌ها
    </div>
    <table class="log-table avoid-break">
        <thead><tr><th style="width:18%">تاریخ</th><th style="width:25%">رویداد</th><th>توضیحات</th></tr></thead>
        <tbody>
        <?php foreach ($statusLogs as $log): ?>
            <tr>
                <td><?= persianDate((string)$log['created_at']) ?></td>
                <td><?= $e($log['status_name']) ?></td>
                <td><?= $e($log['description'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($statusLogs)): ?>
            <tr><td colspan="3" class="text-muted text-center">بدون رویداد</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- ۶. فهرست مدارک -->
    <div class="section-title avoid-break">
        <span class="num"><?= !empty($dynValues) ? '۶' : '۵' ?></span> فهرست مدارک (<?= count($documents) ?> فایل)
    </div>
    <?php if (!empty($documents)): ?>
    <ul class="doc-list avoid-break">
        <?php
        $docLabels = [
            'national_card' => 'کارت ملی', 'birth_cert' => 'شناسنامه',
            'ownership_doc' => 'مدرک مالکیت', 'vocational_cert' => 'گواهی فنی',
            'education_degree' => 'مدرک تحصیلی', 'product_photos' => 'تصویر محصول',
            'optional' => 'سایر', 'payment_receipt' => 'رسید پرداخت',
            'avatar' => 'آواتار', 'revision_doc' => 'بازبینی',
        ];
        foreach ($documents as $doc):
            $label = $docLabels[$doc['doc_type']] ?? $doc['doc_type'];
        ?>
        <li>
            <span class="badge bg-info"><?= $e($label) ?></span>
            <span dir="ltr"><?= $e($doc['original_name']) ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="text-muted small">مدرکی ثبت نشده است.</p>
    <?php endif; ?>

    <!-- ۷. درخواست‌های تغییر کد پستی -->
    <?php if (!empty($postalChanges)): ?>
    <div class="section-title avoid-break">
        <span class="num"><?= !empty($dynValues) ? '۷' : '۶' ?></span> درخواست‌های تغییر کد پستی
    </div>
    <table class="log-table avoid-break">
        <thead><tr><th>تاریخ</th><th>کد قدیم</th><th>کد جدید</th><th>وضعیت</th></tr></thead>
        <tbody>
        <?php foreach ($postalChanges as $pc):
            $st = $pc['status'] ?? 'در انتظار';
            $stClass = $st === 'تأیید شده' ? 'status-approved' : ($st === 'رد شده' ? 'status-rejected' : 'status-pending');
        ?>
        <tr>
            <td><?= persianDate((string)$pc['requested_at']) ?></td>
            <td dir="ltr"><?= $e($pc['old_postal_code']) ?></td>
            <td dir="ltr"><?= $e($pc['new_postal_code']) ?></td>
            <td>
                <span class="status-badge <?= $stClass ?>"><?= $e($st) ?></span>
                <?php if (!empty($pc['reviewed_at'])): ?><br><small><?= persianDate((string)$pc['reviewed_at']) ?></small><?php endif; ?>
                <?php if (!empty($pc['reject_reason'])): ?><br><small class="text-danger">دلیل: <?= $e($pc['reject_reason']) ?></small><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- === کادر امضا === -->
    <div class="sign-area avoid-break">
        <div class="sign-box">امضای متقاضی</div>
        <div class="sign-box">امضا و مهر متصدی سامانه<br><small><?= $e(owner_name()) ?></small></div>
        <div class="sign-box">امضای مسئول صادرکننده</div>
    </div>

    </div><!-- /.doc-body -->

    <!-- === پاورقی === -->
    <div class="doc-footer">
        این برگه به‌صورت خودکار توسط سامانهٔ مجوزهای صنایع دستی تولید شده است —
        تاریخ تولید: <?= jdate('Y/m/d H:i') ?> — کد رهگیری: <?= $e($applicant['tracking_code']) ?>
    </div>
</div>
