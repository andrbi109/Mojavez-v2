<?php
/**
 * فهرست درخواست‌های بازبینی پرونده (فاز ۲ نقشهٔ راه)
 */
$base = rtrim(BASE_URL, '/') . '/';
$filters = ['' => 'همه', 'در انتظار' => 'در انتظار', 'تأیید شده' => 'تأییدشده', 'رد شده' => 'ردشده'];
?>
<div class="card p-3 p-md-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h4 class="fw-bold mb-0"><?= icon('edit', 'me-1') ?> درخواست‌های بازبینی پرونده</h4>
        <div class="btn-group btn-group-sm">
            <?php foreach ($filters as $value => $label): ?>
                <a href="<?= $base ?>admin/revisions?status=<?= rawurlencode($value) ?>"
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
                <tr><th>#</th><th>متقاضی</th><th>کد رهگیری</th><th>وضعیت پرونده</th><th>یادداشت</th><th>تاریخ</th><th>بررسی</th><th>عملیات</th></tr>
            </thead>
            <tbody>
            <?php foreach ($revisions as $r): ?>
                <?php $rb = match ($r['status']) { 'تأیید شده' => 'success', 'رد شده' => 'danger', default => 'warning text-dark' }; ?>
                <tr class="<?= $r['status'] === 'در انتظار' ? 'table-warning' : '' ?>">
                    <td><?= (int)$r['id'] ?></td>
                    <td class="text-nowrap"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                    <td><code><?= htmlspecialchars($r['tracking_code']) ?></code></td>
                    <td class="small"><?= htmlspecialchars($r['current_status']) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars(mb_substr((string)($r['note'] ?? ''), 0, 50)) ?></td>
                    <td class="small text-muted"><?= persianDate((string)$r['created_at']) ?></td>
                    <td>
                        <span class="badge bg-<?= $rb ?>"><?= htmlspecialchars($r['status']) ?></span>
                        <?php if ($r['status'] !== 'در انتظار'): ?>
                            <div class="small text-muted"><?= htmlspecialchars($r['reviewer_name'] ?? '') ?> — <?= persianDate((string)($r['reviewed_at'] ?? '')) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($r['reject_reason'])): ?><div class="small text-danger"><?= htmlspecialchars($r['reject_reason']) ?></div><?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <a href="<?= $base ?>admin/revisions/view/<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><?= icon('eye', '', 14) ?> مقایسه</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($revisions)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">درخواست بازبینی وجود ندارد.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="alert alert-light border small mb-0 mt-2">
        <?= icon('info', '', 15) ?>
        طبق قاعدهٔ سامانه، متقاضی پس از ثبت نهایی امکان ویرایش مستقیم ندارد؛
        او «درخواست بازبینی کامل» ثبت می‌کند و تا <strong>تأیید شما</strong> هیچ تغییری در پروندهٔ رسمی اعمال نمی‌شود.
    </div>
</div>
