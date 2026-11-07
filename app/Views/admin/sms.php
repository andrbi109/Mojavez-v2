<?php
/**
 * مدیریت پیامک‌ها (فاز ۵ نقشهٔ راه)
 * بخش ۱: الگوهای پیامک (CRUD) — بخش ۲: بررسی ورودی‌های ثبت‌شدهٔ متقاضیان
 */
$base = rtrim(BASE_URL, '/') . '/';
$fd = $formData ?: null;
$err = $errors ?? [];
?>
<?php if (!empty($message)): ?>
    <div class="alert alert-info py-2"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- ============ الگوهای پیامک ============ -->
    <div class="col-xl-5">
        <div class="card mb-4">
            <div class="card-header"><?= icon('form', 'me-1') ?> الگوهای پیامک</div>
            <div class="card-body py-2">
                <?php foreach ($templates as $t): ?>
                <div class="field-row <?= (int)$t['is_active'] ? '' : 'inactive' ?>">
                    <div class="flex-grow-1" style="min-width:160px">
                        <strong class="small"><?= htmlspecialchars($t['title']) ?></strong>
                        <?php if ((int)$t['requires_code']): ?><span class="field-type-badge">کد الزامی</span><?php endif; ?>
                        <?php if (!(int)$t['is_active']): ?><span class="badge bg-secondary">غیرفعال</span><?php endif; ?>
                        <div class="small text-muted">وضعیت مقصد: <span class="badge bg-info"><?= htmlspecialchars($t['target_status']) ?></span></div>
                        <?php if (!empty($t['sample_text'])): ?><div class="small text-muted">نمونه: <?= htmlspecialchars(mb_substr($t['sample_text'], 0, 60)) ?>…</div><?php endif; ?>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="<?= $base ?>admin/sms?edit=<?= (int)$t['id'] ?>#tplForm" class="btn btn-sm btn-outline-primary py-0"><?= icon('edit', '', 13) ?></a>
                        <form method="post" action="<?= $base ?>admin/sms/delete-template"
                              onsubmit="return confirm('الگوی «<?= htmlspecialchars(addslashes($t['title'])) ?>» حذف/غیرفعال شود؟');">
                            <?= Session::csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger py-0"><?= icon('trash', '', 13) ?></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- فرم ایجاد/ویرایش الگو -->
        <div class="card" id="tplForm">
            <div class="card-header"><?= icon('plus', 'me-1') ?> <?= $editing ? 'ویرایش الگو' : 'افزودن الگوی پیامک' ?></div>
            <div class="card-body">
                <form method="post" action="<?= $base ?>admin/sms/save-template">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
                    <div class="mb-2">
                        <label class="form-label small mb-1">عنوان الگو <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm <?= isset($err['title']) ? 'is-invalid' : '' ?>"
                               name="title" maxlength="120" required
                               value="<?= htmlspecialchars($fd['title'] ?? $editing['title'] ?? '') ?>" placeholder="مثلاً: پیامک صدور مجوز">
                        <?php if (!empty($err['title'])): ?><div class="invalid-feedback"><?= implode('<br>', $err['title']) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">نمونهٔ متن پیامک (راهنمای متقاضی)</label>
                        <textarea class="form-control form-control-sm" name="sample_text" rows="2" maxlength="500"><?= htmlspecialchars($fd['sample_text'] ?? $editing['sample_text'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label small mb-1">وضعیت مقصد پس از تأیید <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm <?= isset($err['target_status']) ? 'is-invalid' : '' ?>" name="target_status" required>
                                <?php foreach ($statusMeta as $st => $meta): ?>
                                    <?php if (!$meta['manual'] || $st === 'مشاهده و چاپ') continue; ?>
                                    <option value="<?= htmlspecialchars($st) ?>" <?= ($fd['target_status'] ?? $editing['target_status'] ?? '') === $st ? 'selected' : '' ?>><?= htmlspecialchars($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($err['target_status'])): ?><div class="invalid-feedback"><?= implode('<br>', $err['target_status']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-5">
                            <label class="form-label small mb-1">ترتیب</label>
                            <input type="number" class="form-control form-control-sm" name="sort_order" value="<?= (int)($fd['sort_order'] ?? $editing['sort_order'] ?? 100) ?>">
                        </div>
                    </div>
                    <div class="d-flex gap-3 mt-2 flex-wrap">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="reqCode" name="requires_code" value="1"
                                   <?= (int)($fd['requires_code'] ?? $editing['requires_code'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="reqCode">ورود کد پیامک الزامی</label>
                        </div>
                        <?php if ($editing): ?>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="tplActive" name="is_active" value="1"
                                   <?= (int)$editing['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="tplActive">فعال</label>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success mt-3 w-100"><?= icon('save', '', 14) ?> ذخیرهٔ الگو</button>
                    <?php if ($editing): ?><a href="<?= $base ?>admin/sms" class="btn btn-sm btn-outline-secondary mt-2 w-100">انصراف از ویرایش</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- ============ ورودی‌های ثبت‌شده ============ -->
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><?= icon('mail', 'me-1') ?> پیامک‌های ثبت‌شده توسط متقاضیان</span>
                <div class="btn-group btn-group-sm">
                    <?php foreach (['' => 'همه', 'در انتظار' => 'در انتظار', 'تأیید شده' => 'تأییدشده', 'رد شده' => 'ردشده'] as $k => $lbl): ?>
                        <a class="btn <?= ($statusFilter ?? '') === $k ? 'btn-primary' : 'btn-outline-primary' ?>"
                           href="<?= $base ?>admin/sms?status=<?= rawurlencode($k) ?>"><?= $lbl ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr>
                            <th>متقاضی</th><th>نوع پیامک</th><th>کد/متن</th>
                            <th class="d-none d-md-table-cell">دریافت</th><th>وضعیت</th><th>عملیات</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($entries as $e): ?>
                            <?php $eb = match ($e['status']) { 'تأیید شده' => 'success', 'رد شده' => 'danger', default => 'warning text-dark' }; ?>
                            <tr class="<?= $e['status'] === 'در انتظار' ? 'table-warning' : '' ?>">
                                <td class="text-nowrap small">
                                    <a href="<?= $base ?>admin/view/<?= (int)$e['applicant_id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?>
                                    </a>
                                    <div><code style="font-size:.65rem"><?= htmlspecialchars($e['tracking_code']) ?></code></div>
                                </td>
                                <td class="small">
                                    <?= htmlspecialchars($e['template_title']) ?>
                                    <div class="text-muted" style="font-size:.68rem">→ <?= htmlspecialchars($e['target_status']) ?></div>
                                </td>
                                <td class="small">
                                    <?php if (!empty($e['sms_code'])): ?><code><?= htmlspecialchars($e['sms_code']) ?></code><br><?php endif; ?>
                                    <span class="text-muted"><?= htmlspecialchars(mb_substr((string)($e['sms_text'] ?? ''), 0, 60)) ?><?= mb_strlen((string)($e['sms_text'] ?? '')) > 60 ? '…' : '' ?></span>
                                </td>
                                <td class="d-none d-md-table-cell small text-muted"><?= persianDate((string)($e['received_at'] ?? '')) ?></td>
                                <td>
                                    <span class="badge bg-<?= $eb ?>"><?= htmlspecialchars($e['status']) ?></span>
                                    <?php if ($e['status'] === 'تأیید شده'): ?>
                                        <div class="small text-muted"><?= (int)$e['applied_status'] ? 'وضعیت اعمال شد' : 'بدون تغییر وضعیت' ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($e['reject_reason'])): ?><div class="small text-danger"><?= htmlspecialchars($e['reject_reason']) ?></div><?php endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <?php if ($e['status'] === 'در انتظار'): ?>
                                        <form method="post" action="<?= $base ?>admin/sms/review-entry" class="d-inline"
                                              onsubmit="return confirm('پیامک تأیید و وضعیت پرونده به «<?= htmlspecialchars($e['target_status']) ?>» ارتقا یابد؟ (در صورت عقب‌تر نبودن)');">
                                            <?= Session::csrfField() ?>
                                            <input type="hidden" name="entry_id" value="<?= (int)$e['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button class="btn btn-sm btn-success py-0 px-1"><?= icon('check', '', 13) ?></button>
                                        </form>
                                        <form method="post" action="<?= $base ?>admin/sms/review-entry" class="d-inline"
                                              onsubmit="this.reason.value = prompt('دلیل رد:') || ''; return this.reason.value !== '';">
                                            <?= Session::csrfField() ?>
                                            <input type="hidden" name="entry_id" value="<?= (int)$e['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="reject_reason" value="">
                                            <button class="btn btn-sm btn-outline-danger py-0 px-1"><?= icon('x', '', 13) ?></button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:.7rem"><?= htmlspecialchars($e['reviewer_name'] ?? '') ?> — <?= persianDate((string)($e['reviewed_at'] ?? '')) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($entries)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">پیامکی ثبت نشده است.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="alert alert-light border small mt-3 mb-0">
            <?= icon('info', '', 15) ?>
            متقاضی پیامک دریافتی از سازمان را در صفحهٔ پیگیری ثبت می‌کند؛ پس از <strong>تأیید شما</strong>،
            وضعیت پرونده به «وضعیت مقصد» الگو ارتقا می‌یابد — با رعایت مسیر یکطرفه (اگر پرونده جلوتر باشد، وضعیت تغییر نمی‌کند).
        </div>
    </div>
</div>
