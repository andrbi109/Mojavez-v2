<?php
/**
 * بررسی درخواست‌های دریافت تصویر مجوز (فاز ۶ نقشهٔ راه)
 * تأیید رایگان / تأیید مشروط به پرداخت / رد با دلیل
 */
$base = rtrim(BASE_URL, '/') . '/';
$statuses = ['' => 'همه', 'در انتظار' => 'در انتظار', 'در انتظار پرداخت' => 'در انتظار پرداخت', 'تأیید شده' => 'تأیید شده', 'رد شده' => 'رد شده', 'منقضی' => 'منقضی'];
?>
<div class="card p-3 p-md-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h4 class="fw-bold mb-0"><?= icon('award', 'me-1') ?> درخواست‌های تصویر مجوز</h4>
        <div class="btn-group btn-group-sm flex-wrap" role="group">
            <?php foreach ($statuses as $value => $label): ?>
                <a href="<?= $base ?>admin/license-requests?status=<?= rawurlencode($value) ?>"
                   class="btn <?= ($statusFilter ?? '') === $value ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>متقاضی</th><th>کد رهگیری</th><th>نوع</th><th>تاریخ</th>
                    <th>وضعیت</th><th class="d-none d-md-table-cell">پرداخت</th><th>عملیات</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <?php
                $badge = match ($r['status']) {
                    'تأیید شده' => 'success',
                    'رد شده' => 'danger',
                    'در انتظار پرداخت' => 'info',
                    'منقضی' => 'secondary',
                    default => 'warning text-dark',
                };
                ?>
                <tr class="<?= $r['status'] === 'در انتظار' ? 'table-warning' : '' ?>">
                    <td class="text-nowrap"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                    <td><code><?= htmlspecialchars($r['tracking_code']) ?></code>
                        <a href="<?= $base ?>admin/view/<?= (int)$r['applicant_id'] ?>" class="btn btn-sm btn-outline-primary py-0 ms-1" title="پرونده"><?= icon('eye', '', 13) ?></a>
                    </td>
                    <td class="small"><?= (int)$r['is_first_access'] ? '<span class="badge bg-success">دریافت اول</span>' : 'دریافت مجدد' ?></td>
                    <td class="small text-muted"><?= persianDate((string)$r['created_at']) ?></td>
                    <td>
                        <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($r['status']) ?></span>
                        <?php if ($r['status'] === 'تأیید شده' && !empty($r['expires_at'])): ?>
                            <div class="small text-muted">اعتبار تا <?= persianDate((string)$r['expires_at']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($r['reject_reason'])): ?>
                            <div class="small text-danger"><?= htmlspecialchars($r['reject_reason']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-md-table-cell small">
                        <?php if ((int)$r['requires_payment']): ?>
                            <?= number_format((int)$r['amount']) ?> تومان
                            <?php if (!empty($r['payment_id'])): ?>
                                <?php
                                $pay = (new Payment())->findById((int)$r['payment_id']);
                                $pb = $pay ? match ($pay['status']) { 'تأیید شده' => 'success', 'رد شده' => 'danger', default => 'warning text-dark' } : 'secondary';
                                ?>
                                <span class="badge bg-<?= $pb ?>"><?= htmlspecialchars($pay['status'] ?? '—') ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <?php if ($r['status'] === 'در انتظار' && !(int)$r['is_first_access']): ?>
                            <form method="post" action="<?= $base ?>admin/license-requests/review" class="d-inline"
                                  onsubmit="return confirm('درخواست دریافت مجدد به‌صورت رایگان تأیید و لینک ۷۲ ساعته صادر شود؟');">
                                <?= Session::csrfField() ?>
                                <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="decision" value="approve_free">
                                <button class="btn btn-sm btn-success py-0"><?= icon('check', '', 13) ?> تأیید رایگان</button>
                            </form>
                            <button class="btn btn-sm btn-outline-info py-0" data-bs-toggle="modal" data-bs-target="#paidModal<?= (int)$r['id'] ?>">
                                <?= icon('money', '', 13) ?> تأیید با پرداخت
                            </button>
                            <button class="btn btn-sm btn-outline-danger py-0" data-bs-toggle="modal" data-bs-target="#rejectLic<?= (int)$r['id'] ?>">
                                <?= icon('x', '', 13) ?> رد
                            </button>

                            <!-- مودال تأیید مشروط به پرداخت -->
                            <div class="modal fade" id="paidModal<?= (int)$r['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form method="post" action="<?= $base ?>admin/license-requests/review" class="modal-content">
                                        <?= Session::csrfField() ?>
                                        <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="decision" value="approve_paid">
                                        <div class="modal-header"><h5 class="modal-title">تأیید مشروط به پرداخت</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <label class="form-label">مبلغ مورد نیاز (تومان)</label>
                                            <input type="number" class="form-control" name="amount" min="1" required
                                                   value="<?= (int)($reissueFee ?? 0) ?: 100000 ?>">
                                            <div class="form-text">پس از ثبت و تأیید رسید پرداخت توسط شما، لینک دریافت ۷۲ ساعته صادر می‌شود.</div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-info">تأیید و تعیین مبلغ</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- مودال رد -->
                            <div class="modal fade" id="rejectLic<?= (int)$r['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form method="post" action="<?= $base ?>admin/license-requests/review" class="modal-content">
                                        <?= Session::csrfField() ?>
                                        <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="decision" value="reject">
                                        <div class="modal-header"><h5 class="modal-title">رد درخواست دریافت مجدد</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <label class="form-label">دلیل رد</label>
                                            <textarea name="reject_reason" class="form-control" rows="2" maxlength="250" required></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-danger">ثبت و رد</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">
                                <?= (int)$r['is_first_access'] ? 'خودکار' : 'بررسی‌شده' ?>
                                <?= !empty($r['reviewer_name']) ? ' توسط ' . htmlspecialchars($r['reviewer_name']) : '' ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($requests)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">درخواستی وجود ندارد.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="alert alert-light border small mb-0 mt-2">
        <?= icon('info', '', 15) ?>
        <strong>قاعدهٔ یک‌بار دریافت:</strong> دریافت اول تصویر مجوز خودکار و رایگان است؛
        پس از آن لینک قبلی غیرفعال می‌شود و هر دریافت مجدد نیازمند درخواست، تأیید مدیر
        و (در صورت تعیین مبلغ) پرداخت و تأیید رسید است. لینک‌ها توکن تصادفی ۷۲ ساعته دارند.
    </div>
</div>
