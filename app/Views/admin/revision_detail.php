<?php
/**
 * مقایسهٔ مقادیر فعلی و پیشنهادی یک درخواست بازبینی (فاز ۲)
 * ورودی‌ها: $rev, $applicant, $cmp (ردیف‌های مقایسه)، $newDocs, $docTypeLabels
 */
$base = rtrim(BASE_URL, '/') . '/';
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$isPending = $rev['status'] === 'در انتظار';
$changedCount = count(array_filter($cmp, fn($c) => $c['changed']));
?>

<div class="card p-3 p-md-4 mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><?= icon('eye', 'me-1') ?> بازبینی درخواست #<?= (int)$rev['id'] ?></h4>
            <p class="text-muted small mb-0">
                <?= $e($rev['first_name'] . ' ' . $rev['last_name']) ?> —
                <code><?= $e($rev['tracking_code']) ?></code> —
                وضعیت پرونده: <span class="badge bg-info"><?= $e($rev['current_status']) ?></span>
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-<?= $isPending ? 'warning text-dark' : ($rev['status'] === 'تأیید شده' ? 'success' : 'danger') ?> fs-6"><?= $e($rev['status']) ?></span>
            <a href="<?= $base ?>admin/view/<?= (int)$rev['applicant_id'] ?>" class="btn btn-sm btn-outline-primary"><?= icon('folder', '', 14) ?> پرونده</a>
            <a href="<?= $base ?>admin/revisions" class="btn btn-sm btn-outline-secondary">بازگشت</a>
        </div>
    </div>
    <?php if (!empty($rev['note'])): ?>
        <div class="alert alert-light border small mt-3 mb-0">📝 یادداشت متقاضی: <?= $e($rev['note']) ?></div>
    <?php endif; ?>
    <div class="small text-muted mt-2">
        ثبت درخواست: <?= persianDate((string)$rev['created_at']) ?> —
        <strong><?= $changedCount ?></strong> مورد تغییر + <strong><?= count($newDocs) ?></strong> مدرک جدید
    </div>
</div>

<div class="row g-4">
    <!-- مقایسهٔ فیلدها -->
    <div class="col-lg-<?= count($newDocs) ? '8' : '12' ?>">
        <div class="card">
            <div class="card-header">مقایسهٔ مقادیر — <span class="text-warning">موارد تغییریافته هایلایت شده‌اند</span></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th style="width:26%">فیلد</th><th style="width:37%">مقدار فعلی</th><th style="width:37%">مقدار پیشنهادی</th></tr></thead>
                        <tbody>
                        <?php foreach ($cmp as $c): ?>
                            <tr class="<?= $c['changed'] ? 'table-warning' : '' ?>">
                                <td class="small fw-bold"><?= $e($c['label']) ?></td>
                                <td class="small"><?= $c['current'] !== '' ? $e($c['current']) : '<span class="text-muted">—</span>' ?></td>
                                <td class="small">
                                    <?= $c['proposed'] !== '' ? $e($c['proposed']) : '<span class="text-muted">—</span>' ?>
                                    <?php if ($c['changed']): ?><span class="badge bg-warning text-dark ms-1">تغییر</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer small text-muted">کد ملی لنگر هویت پرونده است و تغییر نمی‌کند.</div>
        </div>
    </div>

    <!-- مدارک جدید + تصمیم -->
    <?php if (count($newDocs) || $isPending): ?>
    <div class="col-lg-<?= count($newDocs) ? '4' : '12' ?>">
        <?php if (count($newDocs)): ?>
        <div class="card mb-4">
            <div class="card-header"><?= icon('upload', 'me-1') ?> مدارک جدید پیشنهادی</div>
            <div class="card-body">
                <?php foreach ($newDocs as $nd): ?>
                <?php
                    // دفاع: اطمینان از وجود doc قبل از دسترسی به اندیس‌ها
                    $ndDoc = $nd['doc'] ?? null;
                    $ndDocId = (int)($ndDoc['id'] ?? 0);
                    $ndDocName = (string)($ndDoc['file_name'] ?? '');
                    $ndDocOrigName = (string)($ndDoc['original_name'] ?? '');
                    $ndType = (string)($nd['type'] ?? 'optional');
                    $ndExt = safe_file_extension($ndDocName);
                    $isImage = in_array($ndExt, ['jpg', 'jpeg', 'png'], true);
                ?>
                <div class="field-row">
                    <?php if ($isImage && $ndDocId > 0): ?>
                        <img src="<?= $base ?>document/thumb/<?= $ndDocId ?>" style="width:52px;height:40px;object-fit:cover;border-radius:6px;" alt="">
                    <?php else: ?>
                        <span style="color:var(--terracotta)"><?= icon('file-text', '', 22) ?></span>
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <strong class="small"><?= $e($docTypeLabels[$ndType] ?? $ndType) ?></strong>
                        <div class="small text-muted text-truncate" dir="ltr"><?= $e($ndDocOrigName) ?></div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary view-doc-btn" data-id="<?= $ndDocId ?>" data-applicant="<?= (int)$rev['applicant_id'] ?>"><?= icon('eye', '', 13) ?></button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isPending): ?>
        <div class="card">
            <div class="card-header"><?= icon('check-circle', 'me-1') ?> تصمیم مدیر</div>
            <div class="card-body d-grid gap-2">
                <form method="post" action="<?= $base ?>admin/revisions/approve/<?= (int)$rev['id'] ?>"
                      onsubmit="return confirm('همهٔ تغییرات پیشنهادی (<?= $changedCount ?> فیلد + <?= count($newDocs) ?> مدرک) روی پرونده اعمال شود؟');">
                    <?= Session::csrfField() ?>
                    <button type="submit" class="btn btn-success w-100"><?= icon('check', '', 16) ?> تأیید و اعمال تغییرات</button>
                </form>
                <form method="post" action="<?= $base ?>admin/revisions/reject/<?= (int)$rev['id'] ?>"
                      onsubmit="this.reject_reason.value = prompt('دلیل رد درخواست بازبینی:') || ''; return this.reject_reason.value !== '';">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="reject_reason" value="">
                    <button type="submit" class="btn btn-outline-danger w-100"><?= icon('x', '', 16) ?> رد درخواست (مدارک موقت پاک می‌شوند)</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- مودال پیش‌نمایش مدرک -->
<div class="modal fade" id="docModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><?= icon('eye', 'me-1') ?> پیش‌نمایش مدرک</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body text-center" id="docContent"></div>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.view-doc-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const docId = this.dataset.id;
        const box = document.getElementById('docContent');
        box.innerHTML = '<div class="py-5 text-muted">در حال بارگذاری…</div>';
        new bootstrap.Modal(document.getElementById('docModal')).show();

        const fileUrl = '<?= $base ?>document/inline/' + docId;

        // تلاش با img و fallback به iframe
        box.innerHTML = `
            <img src="${fileUrl}" class="img-fluid" style="max-height:75vh;"
                 onerror="this.style.display='none'; document.getElementById('pdfFallback_${docId}').style.display='block';">
            <div id="pdfFallback_${docId}" style="display:none;">
                <iframe src="${fileUrl}"
                        style="width:100%;height:75vh;border:0;"
                        title="پیش‌نمایش فایل">
                </iframe>
                <div class="mt-2 text-muted small">
                    💡 اگر فایل نمایش داده نشد،
                    <a href="${fileUrl}" target="_blank" class="btn btn-sm btn-outline-primary">در تب جدید باز کنید</a>
                </div>
            </div>`;
    });
});
</script>
