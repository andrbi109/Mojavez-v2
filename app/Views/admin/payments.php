<?php
/**
 * درآمد و پرداخت‌ها (فاز ۴ نقشهٔ راه)
 * کارت‌های مالی + فیلتر + فهرست رسیدها با تأیید/رد + مدیریت کانال‌های پرداخت
 */
$base = rtrim(BASE_URL, '/') . '/';
$qs = fn(array $extra) => http_build_query(array_merge(array_filter($filters), $extra));
$curJMonth = jdate('Y/m');
?>

<!-- کارت‌های مالی -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="border-right:4px solid var(--persian-green);">
            <div class="stat-icon green"><?= icon('money', '', 24) ?></div>
            <div>
                <div class="stat-value" style="font-size:1.1rem"><?= number_format((int)$stats['approvedMonth']) ?></div>
                <div class="stat-label">درآمد تأییدشدهٔ <?= $curJMonth ?> (تومان)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon turquoise"><?= icon('chart', '', 24) ?></div>
            <div>
                <div class="stat-value" style="font-size:1.1rem"><?= number_format((int)$stats['approvedTotal']) ?></div>
                <div class="stat-label">کل درآمد تأییدشده (تومان)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="border-right:4px solid var(--saffron);">
            <div class="stat-icon gold"><?= icon('clock', '', 24) ?></div>
            <div>
                <div class="stat-value"><?= (int)$stats['pendingCount'] ?></div>
                <div class="stat-label">رسید در انتظار بررسی (<?= number_format((int)$stats['pendingSum']) ?> تومان)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="border-right:4px solid var(--terracotta);">
            <div class="stat-icon red"><?= icon('x-circle', '', 24) ?></div>
            <div>
                <div class="stat-value"><?= (int)$stats['rejectedCount'] ?></div>
                <div class="stat-label">رسیدهای ردشده</div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-info py-2"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- فهرست پرداخت‌ها -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><?= icon('list', 'me-1') ?> رسیدهای پرداخت (<?= (int)$total ?>)</span>
                <a class="btn btn-sm btn-success" href="<?= $base ?>admin/payments/csv?<?= $qs([]) ?>"><?= icon('download', '', 14) ?> خروجی CSV</a>
            </div>
            <div class="card-body pb-2">
                <form method="get" action="<?= $base ?>admin/payments" class="row g-2 align-items-end">
                    <div class="col-6 col-md-4">
                        <label class="form-label small mb-1">جستجو (کد رهگیری/ملی/نام/پیگیری)</label>
                        <input type="text" class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($filters['q']) ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">وضعیت</label>
                        <select class="form-select form-select-sm" name="status">
                            <?php foreach (['' => 'همه', 'در انتظار بررسی' => 'در انتظار', 'تأیید شده' => 'تأییدشده', 'رد شده' => 'ردشده'] as $k => $lbl): ?>
                                <option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">هدف پرداخت</label>
                        <select class="form-select form-select-sm" name="purpose">
                            <option value="">همه</option>
                            <option value="هزینهٔ ثبت‌نام" <?= $filters['purpose'] === 'هزینهٔ ثبت‌نام' ? 'selected' : '' ?>>هزینهٔ ثبت‌نام</option>
                            <option value="هزینهٔ دریافت مجدد تصویر مجوز" <?= $filters['purpose'] === 'هزینهٔ دریافت مجدد تصویر مجوز' ? 'selected' : '' ?>>دریافت مجدد تصویر مجوز</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <button class="btn btn-primary btn-sm w-100"><?= icon('search', '', 14) ?> فیلتر</button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th><th>متقاضی</th><th>هدف</th><th>مبلغ</th>
                                <th class="d-none d-md-table-cell">کانال</th>
                                <th class="d-none d-lg-table-cell">واریز</th>
                                <th>رسید</th><th>وضعیت</th><th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($payments as $p): ?>
                            <?php $pb = match ($p['status']) { 'تأیید شده' => 'success', 'رد شده' => 'danger', default => 'warning text-dark' }; ?>
                            <tr class="<?= $p['status'] === 'در انتظار بررسی' ? 'table-warning' : '' ?>">
                                <td><?= (int)$p['id'] ?></td>
                                <td class="text-nowrap">
                                    <a href="<?= $base ?>admin/view/<?= (int)$p['applicant_id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                                    </a>
                                    <div><code style="font-size:.68rem"><?= htmlspecialchars($p['tracking_code']) ?></code></div>
                                </td>
                                <td class="small"><?= htmlspecialchars($p['purpose']) ?></td>
                                <td class="text-nowrap"><?= number_format((int)$p['amount']) ?></td>
                                <td class="d-none d-md-table-cell small"><?= htmlspecialchars($p['channel_title'] ?? '—') ?></td>
                                <td class="d-none d-lg-table-cell small text-muted"><?= persianDate((string)($p['paid_at'] ?? '')) ?></td>
                                <td>
                                    <?php if (!empty($p['receipt_document_id'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 view-receipt-btn"
                                                data-id="<?= (int)$p['receipt_document_id'] ?>" data-applicant="<?= (int)$p['applicant_id'] ?>">
                                            <?= icon('eye', '', 14) ?>
                                        </button>
                                    <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $pb ?>"><?= htmlspecialchars($p['status']) ?></span>
                                    <?php if ($p['status'] !== 'در انتظار بررسی' && !empty($p['reviewed_at'])): ?>
                                        <div class="small text-muted"><?= persianDate((string)$p['reviewed_at']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($p['reject_reason'])): ?>
                                        <div class="small text-danger"><?= htmlspecialchars($p['reject_reason']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <?php if ($p['status'] === 'در انتظار بررسی'): ?>
                                        <form method="post" action="<?= $base ?>admin/payments/review" class="d-inline"
                                              onsubmit="return confirm('رسید پرداخت #<?= (int)$p['id'] ?> تأیید شود؟');">
                                            <?= Session::csrfField() ?>
                                            <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button class="btn btn-sm btn-success py-0"><?= icon('check', '', 13) ?> تأیید</button>
                                        </form>
                                        <button class="btn btn-sm btn-outline-danger py-0" data-bs-toggle="modal" data-bs-target="#rejectPay<?= (int)$p['id'] ?>"><?= icon('x', '', 13) ?> رد</button>
                                        <div class="modal fade" id="rejectPay<?= (int)$p['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <form method="post" action="<?= $base ?>admin/payments/review" class="modal-content">
                                                    <?= Session::csrfField() ?>
                                                    <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <div class="modal-header"><h5 class="modal-title">رد رسید پرداخت #<?= (int)$p['id'] ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                    <div class="modal-body">
                                                        <label class="form-label">دلیل رد (در صفحهٔ متقاضی نمایش داده می‌شود)</label>
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
                                        <span class="text-muted small">بررسی‌شده توسط <?= htmlspecialchars($p['reviewer_name'] ?? '—') ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">پرداختی ثبت نشده است.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- صفحه‌بندی -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center flex-wrap">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?<?= $qs(['page' => $page - 1]) ?>">قبلی</a></li>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= $qs(['page' => $i]) ?>"><?= $i ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="?<?= $qs(['page' => $page + 1]) ?>">بعدی</a></li>
        </ul></nav>
        <?php endif; ?>
    </div>

    <!-- ستون کناری: درآمد بر اساس کانال + مدیریت کانال‌ها -->
    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header"><?= icon('chart', 'me-1') ?> درآمد تأییدشده بر اساس کانال</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($byChannel as $bc): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center small">
                    <span><?= htmlspecialchars($bc['channel']) ?> <span class="badge bg-secondary"><?= (int)$bc['cnt'] ?></span></span>
                    <strong><?= number_format((int)$bc['total']) ?> تومان</strong>
                </li>
                <?php endforeach; ?>
                <?php if (empty($byChannel)): ?><li class="list-group-item text-muted small text-center">داده‌ای نیست</li><?php endif; ?>
            </ul>
        </div>

        <div class="card">
            <div class="card-header"><?= icon('settings', 'me-1') ?> کانال‌های پرداخت</div>
            <div class="card-body">
                <?php foreach ($channels as $ch): ?>
                <div class="field-row <?= (int)$ch['is_active'] ? '' : 'inactive' ?>">
                    <div class="flex-grow-1" style="min-width:150px">
                        <strong class="small"><?= htmlspecialchars($ch['title']) ?></strong>
                        <div><span class="field-type-badge"><?= htmlspecialchars($ch['channel_type']) ?></span>
                        <?php if (!(int)$ch['is_active']): ?><span class="badge bg-secondary">غیرفعال</span><?php endif; ?></div>
                        <div class="small text-muted" dir="ltr" style="text-align:right"><?= htmlspecialchars($ch['account_info'] ?? '') ?></div>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <form method="post" action="<?= $base ?>admin/payment-channels/delete"
                              onsubmit="return confirm('کانال «<?= htmlspecialchars(addslashes($ch['title'])) ?>» حذف شود؟');">
                            <?= Session::csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger py-0 px-1"><?= icon('trash', '', 13) ?></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>

                <hr class="my-3">
                <h6 class="fw-bold small"><?= icon('plus', '', 15) ?> افزودن/ویرایش کانال</h6>
                <form method="post" action="<?= $base ?>admin/payment-channels/save" id="channelForm">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="id" id="ch_id" value="0">
                    <div class="mb-2">
                        <label class="form-label small mb-1">عنوان</label>
                        <input type="text" class="form-control form-control-sm" name="title" id="ch_title" maxlength="100" required placeholder="مثلاً: کارت بانک ملت به نام ...">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small mb-1">نوع</label>
                            <select class="form-select form-select-sm" name="channel_type" id="ch_type">
                                <?php foreach (PaymentChannel::TYPES as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">ترتیب</label>
                            <input type="number" class="form-control form-control-sm" name="sort_order" id="ch_sort" value="100">
                        </div>
                    </div>
                    <div class="mb-2 mt-2">
                        <label class="form-label small mb-1">اطلاعات حساب/کارت/لینک</label>
                        <textarea class="form-control form-control-sm" name="account_info" id="ch_info" rows="2" maxlength="500" required></textarea>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="ch_active" name="is_active" value="1" checked>
                        <label class="form-check-label small" for="ch_active">فعال</label>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success w-100"><?= icon('save', '', 14) ?> ذخیرهٔ کانال</button>
                </form>
                <p class="text-muted mb-0 mt-2" style="font-size:.7rem">
                    برای ویرایش کانال موجود، از «ویرایش» کنار آن استفاده کنید (فرم بالا پر می‌شود).
                </p>
                <?php foreach ($channels as $ch): ?>
                <button type="button" class="btn btn-link btn-sm p-0 me-2 edit-ch"
                        data-id="<?= (int)$ch['id'] ?>"
                        data-title="<?= htmlspecialchars($ch['title']) ?>"
                        data-type="<?= htmlspecialchars($ch['channel_type']) ?>"
                        data-info="<?= htmlspecialchars($ch['account_info'] ?? '') ?>"
                        data-sort="<?= (int)$ch['sort_order'] ?>"
                        data-active="<?= (int)$ch['is_active'] ?>">ویرایش «<?= htmlspecialchars($ch['title']) ?>»</button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- مودال پیش‌نمایش رسید (مشترک با پرونده) -->
<div class="modal fade" id="docModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= icon('eye', 'me-1') ?> پیش‌نمایش رسید پرداخت</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
      </div>
      <div class="modal-body text-center" id="docContent"></div>
    </div>
  </div>
</div>

<script>
// پرکردن فرم کانال برای ویرایش
document.querySelectorAll('.edit-ch').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('ch_id').value = btn.dataset.id;
        document.getElementById('ch_title').value = btn.dataset.title;
        document.getElementById('ch_type').value = btn.dataset.type;
        document.getElementById('ch_info').value = btn.dataset.info;
        document.getElementById('ch_sort').value = btn.dataset.sort;
        document.getElementById('ch_active').checked = btn.dataset.active === '1';
        document.getElementById('channelForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
});
// پیش‌نمایش رسید
document.querySelectorAll('.view-receipt-btn').forEach(function (btn) {
    btn.addEventListener('click', async function () {
        const docId = this.dataset.id;
        const fd = new FormData();
        fd.append('document_id', docId);
        fd.append('applicant_id', this.dataset.applicant);
        fd.append('_csrf', document.querySelector('meta[name="csrf-token"]')?.content || '');
        const box = document.getElementById('docContent');
        box.innerHTML = '<div class="py-5 text-muted">در حال بارگذاری…</div>';
        try {
            const res = await fetch('<?= $base ?>document/view', { method: 'POST', body: fd });
            if (!res.ok) { box.innerHTML = '<div class="py-5 text-danger">خطا در بارگذاری</div>'; new bootstrap.Modal(document.getElementById('docModal')).show(); return; }
            const ct = res.headers.get('Content-Type') || '';
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);

            if (ct.startsWith('image/')) {
                box.innerHTML = `<img src="${url}" class="img-fluid" style="max-height:75vh;">`;
            } else if (ct === 'application/pdf') {
                URL.revokeObjectURL(url);
                box.innerHTML = `
                    <iframe src="<?= $base ?>document/inline/${docId}"
                            style="width:100%;height:75vh;border:0;"
                            title="پیش‌نمایش PDF">
                    </iframe>
                    <div class="mt-2 text-muted small">
                        💡 اگر PDF نمایش داده نشد،
                        <a href="<?= $base ?>document/inline/${docId}" target="_blank" class="btn btn-sm btn-outline-primary">در تب جدید باز کنید</a>
                    </div>`;
            } else {
                box.innerHTML = `<a href="${url}" download class="btn btn-primary">دانلود</a>`;
            }
            new bootstrap.Modal(document.getElementById('docModal')).show();
        } catch (e) { box.innerHTML = '<div class="py-5 text-danger">خطا در ارتباط با سرور</div>'; new bootstrap.Modal(document.getElementById('docModal')).show(); }
    });
});
</script>
