<?php
/**
 * نمای بررسی درخواست‌های تغییر کد پستی
 * مدیر می‌تواند درخواست‌های «در انتظار» را تأیید (اعمال کد جدید) یا رد کند.
 */
$canReview = in_array(Session::get('admin_role'), ['admin', 'expert'], true);
$filters = [
    '' => 'همه',
    'در انتظار' => 'در انتظار بررسی',
    'تأیید شده' => 'تأیید شده',
    'رد شده' => 'رد شده',
];
?>
<div class="card p-3 p-md-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h4 class="fw-bold mb-0"><?= icon('mail', '', 20) ?> درخواست‌های تغییر کد پستی</h4>
        <?php if (($pendingCount ?? 0) > 0): ?>
            <span class="badge bg-warning text-dark fs-6"><?= (int)$pendingCount ?> درخواست در انتظار بررسی</span>
        <?php endif; ?>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- فیلتر وضعیت -->
    <div class="btn-group btn-group-sm mb-3 flex-wrap" role="group">
        <?php foreach ($filters as $value => $label): ?>
            <a href="<?= BASE_URL ?>admin/postal-requests?status=<?= rawurlencode($value) ?>"
               class="btn <?= ($statusFilter ?? '') === $value ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>متقاضی</th>
                    <th>کد رهگیری</th>
                    <th>کد فعلی</th>
                    <th>کد درخواستی</th>
                    <th>تاریخ درخواست</th>
                    <th>وضعیت</th>
                    <?php if ($canReview): ?><th>عملیات</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr class="<?= $r['status'] === 'در انتظار' ? 'table-warning' : '' ?>">
                    <td class="text-nowrap"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                    <td><code><?= htmlspecialchars($r['tracking_code']) ?></code></td>
                    <td><code><?= htmlspecialchars($r['old_postal_code']) ?></code></td>
                    <td><code class="fw-bold"><?= htmlspecialchars($r['new_postal_code']) ?></code></td>
                    <td class="small"><?= persianDate((string)$r['requested_at']) ?></td>
                    <td>
                        <?php
                        $badge = match ($r['status']) {
                            'در انتظار' => 'warning text-dark',
                            'تأیید شده' => 'success',
                            'رد شده' => 'danger',
                            default => 'secondary',
                        };
                        ?>
                        <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($r['status']) ?></span>
                        <?php if ($r['status'] !== 'در انتظار'): ?>
                            <div class="small text-muted">
                                توسط <?= htmlspecialchars($r['reviewer_name'] ?? '—') ?>
                                در <?= persianDate((string)($r['reviewed_at'] ?? '')) ?>
                            </div>
                            <?php if (!empty($r['reject_reason'])): ?>
                                <div class="small text-danger">دلیل رد: <?= htmlspecialchars($r['reject_reason']) ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <?php if ($canReview): ?>
                    <td class="text-nowrap">
                        <?php if ($r['status'] === 'در انتظار'): ?>
                            <!-- تأیید -->
                            <form method="post" action="<?= BASE_URL ?>admin/postal-requests/approve" class="d-inline"
                                  onsubmit="return confirm('کد پستی پروندهٔ <?= htmlspecialchars($r['tracking_code']) ?> به <?= htmlspecialchars($r['new_postal_code']) ?> تغییر کند؟');">
                                <?= Session::csrfField() ?>
                                <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                <button class="btn btn-sm btn-success" title="تأیید و اعمال کد پستی جدید"><?= icon('check', '', 14) ?> تأیید</button>
                            </form>
                            <!-- رد -->
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= (int)$r['id'] ?>" title="رد درخواست"><?= icon('x', '', 14) ?> رد</button>

                            <div class="modal fade" id="rejectModal<?= (int)$r['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form method="post" action="<?= BASE_URL ?>admin/postal-requests/reject" class="modal-content">
                                        <?= Session::csrfField() ?>
                                        <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">رد درخواست تغییر کد پستی</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label class="form-label">دلیل رد (اختیاری — در تاریخچهٔ پرونده ثبت می‌شود)</label>
                                            <textarea name="reject_reason" class="form-control" rows="2" maxlength="250"></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-danger">ثبت و رد درخواست</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">بررسی شده</span>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>admin/view/<?= (int)$r['applicant_id'] ?>" class="btn btn-sm btn-outline-primary" title="مشاهده پرونده"><?= icon('eye', '', 14) ?></a>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($requests)): ?>
                <tr><td colspan="<?= $canReview ? 7 : 6 ?>" class="text-center text-muted py-4">درخواستی در این بخش وجود ندارد.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="alert alert-light border small mb-0 mt-2">
        🔒 طبق نیازمندی سامانه، کد پستی متقاضی <strong>بلافاصله پس از درخواست تغییر نمی‌کند</strong>؛
        تنها پس از «تأیید» در این صفحه مقدار جدید روی پرونده اعمال می‌شود و تمام مراحل در تاریخچهٔ پرونده ثبت می‌گردد.
    </div>
</div>
