<div class="print-container">
    <!-- سربرگ -->
    <div class="print-header">
        <div class="row">
            <div class="col-6">
                <h3>سامانهٔ مجوزهای صنایع دستی</h3>
                <p>ادارهٔ کل میراث فرهنگی، گردشگری و صنایع دستی</p>
                <p class="small text-muted">مدیریت سامانه: <?= htmlspecialchars(owner_name()) ?> — <span dir="ltr">@<?= htmlspecialchars(setting('owner_username', 'Derogaramin')) ?></span> (ایتا / روبیکا / بله)</p>
            </div>
            <div class="col-6 text-left">
                <p><strong>تاریخ چاپ:</strong> <?= jdate('Y/m/d') ?></p>
                <p><strong>کد رهگیری:</strong> <?= htmlspecialchars($applicant['tracking_code']) ?></p>
            </div>
        </div>
        <hr>
    </div>

    <!-- اطلاعات هویتی -->
    <h4 class="section-title">۱. اطلاعات هویتی</h4>
    <table class="print-table avoid-break">
        <tr><td>نام و نام خانوادگی</td><td><?= htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']) ?></td></tr>
        <tr><td>کد ملی</td><td><?= htmlspecialchars($applicant['national_code']) ?></td></tr>
        <tr><td>کد پستی</td><td><?= htmlspecialchars($applicant['postal_code']) ?></td></tr>
        <tr><td>شماره همراه</td><td><?= htmlspecialchars($applicant['mobile']) ?></td></tr>
        <tr><td>تاریخ تولد</td><td><?= persianDate((string)$applicant['birth_date']) ?></td></tr>
        <tr><td>نام پدر</td><td><?= htmlspecialchars($applicant['father_name'] ?? '') ?></td></tr>
        <tr><td>وضعیت تأهل</td><td><?= htmlspecialchars($applicant['marital_status'] ?? '') ?> <?= $applicant['marital_status'] === 'متأهل' ? '(تعداد فرزندان: '.$applicant['children_count'].')' : '' ?></td></tr>
        <tr><td>وضعیت جسمانی</td><td><?= htmlspecialchars($applicant['physical_condition'] ?? '') ?><?= $applicant['physical_condition'] === 'معلولیت' ? ' - ' . htmlspecialchars($applicant['disability_details'] ?? '') : '' ?></td></tr>
        <tr><td>دهک درآمدی</td><td><?= $applicant['income_decile'] ?: '—' ?></td></tr>
        <tr><td>پرونده قضایی / وام / اقساط</td><td><?= htmlspecialchars($applicant['has_court_case']) ?> / <?= htmlspecialchars($applicant['has_active_loan']) ?> / <?= htmlspecialchars($applicant['has_overdue_installments']) ?></td></tr>
    </table>

    <!-- اطلاعات شغلی -->
    <h4 class="section-title">۲. اطلاعات شغلی</h4>
    <?php if ($jobDetail): ?>
    <table class="print-table avoid-break">
        <tr><td>عنوان مجوز</td><td><?= htmlspecialchars($jobDetail['license_title']) ?></td></tr>
        <tr><td>سابقه (ماه)</td><td><?= $jobDetail['experience_months'] ?></td></tr>
        <tr><td>نحوه آموزش</td><td><?= htmlspecialchars($jobDetail['training_method']) ?></td></tr>
    </table>
    <?php else: ?><p>ثبت نشده</p><?php endif; ?>

    <!-- اطلاعات ملک -->
    <h4 class="section-title">۳. اطلاعات ملک</h4>
    <?php if ($propertyDetail): ?>
    <table class="print-table avoid-break">
        <tr><td>وضعیت مالکیت</td><td><?= htmlspecialchars($propertyDetail['ownership_status']) ?></td></tr>
        <tr><td>آدرس کامل</td><td><?= htmlspecialchars(($propertyDetail['province'] ?? '') . ' - ' . ($propertyDetail['county'] ?? '') . ' - ' . ($propertyDetail['city'] ?? '') . ' - خیابان ' . ($propertyDetail['address_street'] ?? '') . ' - کوچه ' . ($propertyDetail['address_alley'] ?? '') . ' - پلاک ' . ($propertyDetail['address_plaque'] ?? '')) ?></td></tr>
        <tr><td>شناسه قبض برق</td><td><?= htmlspecialchars($propertyDetail['electricity_bill_id']) ?></td></tr>
    </table>
    <?php else: ?><p>ثبت نشده</p><?php endif; ?>

    <!-- فیلدهای سفارشی -->
    <?php if (!empty($dynValues)): ?>
    <h4 class="section-title">۳-۲. پاسخ به سؤالات تکمیلی پرونده</h4>
    <div class="avoid-break">
        <?php $dynMode = 'print'; require VIEW_PATH . '/_dynamic_values.php'; ?>
    </div>
    <?php endif; ?>

    <!-- تاریخچه وضعیت‌ها -->
    <h4 class="section-title">۴. تاریخچه وضعیت‌ها</h4>
    <table class="print-table avoid-break">
        <thead><tr><th>تاریخ</th><th>رویداد</th><th>توضیحات</th></tr></thead>
        <tbody>
        <?php foreach ($statusLogs as $log): ?>
            <tr>
                <td><?= persianDate((string)$log['created_at']) ?></td>
                <td><?= htmlspecialchars($log['status_name']) ?></td>
                <td><?= htmlspecialchars($log['description'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- مدارک -->
    <h4 class="section-title">۵. فهرست مدارک</h4>
    <ul>
    <?php foreach ($documents as $doc): ?>
        <li><?= htmlspecialchars($doc['original_name']) ?> (<?= $doc['doc_type'] ?>)</li>
    <?php endforeach; ?>
    </ul>

    <!-- درخواست‌های تغییر کد پستی -->
    <h4 class="section-title">۶. درخواست‌های تغییر کد پستی</h4>
    <?php if (!empty($postalChanges)): ?>
        <table class="print-table avoid-break">
            <thead><tr><th>تاریخ درخواست</th><th>کد قدیم</th><th>کد جدید</th><th>نتیجهٔ بررسی</th></tr></thead>
            <tbody>
            <?php foreach ($postalChanges as $pc): ?>
                <tr>
                    <td><?= persianDate((string)$pc['requested_at']) ?></td>
                    <td><?= htmlspecialchars($pc['old_postal_code']) ?></td>
                    <td><?= htmlspecialchars($pc['new_postal_code']) ?></td>
                    <td>
                        <?= htmlspecialchars($pc['status'] ?? 'در انتظار') ?>
                        <?php if (!empty($pc['reviewed_at'])): ?>
                            <br><small>(<?= persianDate((string)$pc['reviewed_at']) ?>)</small>
                        <?php endif; ?>
                        <?php if (!empty($pc['reject_reason'])): ?>
                            <br><small>دلیل رد: <?= htmlspecialchars($pc['reject_reason']) ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?><p>بدون تغییر</p><?php endif; ?>

    <!-- تأییدیه و امضا -->
    <div class="print-signature avoid-break">
        <div class="sign-box">امضای متقاضی</div>
        <div class="sign-box">امضا و مهر متصدی سامانه<br><small><?= htmlspecialchars(owner_name()) ?></small></div>
        <div class="sign-box">امضای مسئول صادرکننده</div>
    </div>
    <p class="text-center mt-4" style="font-size:.75rem;color:#888;">
        این برگه به‌صورت خودکار توسط سامانهٔ مجوزهای صنایع دستی تولید شده است —
        تاریخ تولید: <?= jdate('Y/m/d H:i') ?> — کد رهگیری: <?= htmlspecialchars($applicant['tracking_code']) ?>
    </p>
</div>