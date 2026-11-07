<?php
/**
 * پنل مدیریت نرخ‌نامه و تعرفه‌ها
 * ---------------------------------------------------
 * - تنظیمات کلی (فعال‌سازی، تاریخ اعتبار، معافیت روستایی)
 * - گروه‌های قیمتی (CRUD)
 * - مبالغ آیتم‌های هر گروه
 * - تیک خدمات در صفحهٔ تأیید پرداخت (در view_application.php)
 */
$base = rtrim(BASE_URL, '/') . '/';
$s = $settings ?? [];
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><?= icon('wallet', 'me-2') ?>نرخ‌نامه و تعرفه‌ها</h3>
    </div>

    <?php if ($success = Session::get('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php Session::remove('success'); ?>
    <?php endif; ?>
    <?php if ($errors = Session::get('errors', [])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0"><?php foreach ((array)$errors as $e): ?><li><?= htmlspecialchars((string)$e) ?></li><?php endforeach; ?></ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php Session::remove('errors'); ?>
    <?php endif; ?>

    <!-- بخش ۱: تنظیمات کلی -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><?= icon('settings', 'me-1') ?> تنظیمات کلی نرخ‌نامه</h5>
        </div>
        <div class="card-body">
            <form method="post" action="<?= $base ?>admin/fee-tariff/settings">
                <?= Session::csrfField() ?>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="fee_tariff_active" value="1" id="fa" <?= $s['fee_tariff_active'] === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="fa">نرخ‌نامه فعال</label>
                            <small class="d-block text-muted">اگر خاموش باشد، مبلغ ثابت قبلی اعمال می‌شود.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="fee_rural_auto_eligible" value="1" id="fra" <?= $s['fee_rural_auto_eligible'] === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="fra">معافیت خودکار روستایی</label>
                            <small class="d-block text-muted">اگر village پر باشد، گروه الف اعمال شود.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="fee_modal_enabled" value="1" id="fme" <?= $s['fee_modal_enabled'] === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="fme">مودال پیام حمایتی</label>
                            <small class="d-block text-muted">نمایش پیام پس از کد رهگیری.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاریخ انقضای نرخ‌نامه (شمسی)</label>
                        <input type="text" class="form-control" name="fee_tariff_valid_until" value="<?= htmlspecialchars($s['fee_tariff_valid_until']) ?>" placeholder="1405/12/29">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">گروه fallback (دهک خالی)</label>
                        <select class="form-select" name="fee_fallback_group_code">
                            <?php foreach (['A' => 'الف — حمایتی', 'B' => 'ب — متوسط آسیب‌پذیر', 'C' => 'ج — متوسط (پیشنهادی)', 'D' => 'د — حرفه‌ای'] as $code => $label): ?>
                                <option value="<?= $code ?>" <?= $s['fee_fallback_group_code'] === $code ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">حداقل مبلغ نهایی (٪)</label>
                        <input type="number" class="form-control" name="fee_min_amount_percent" min="1" max="50" value="<?= (int)$s['fee_min_amount_percent'] ?>">
                        <small class="text-muted">حداقل ۱۰٪ مبلغ اصلی قابل تأیید است.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">حداکثر تخفیف بدون دلیل (تعداد خدمت)</label>
                        <input type="number" class="form-control" name="fee_max_discount_without_reason" min="0" max="12" value="<?= (int)$s['fee_max_discount_without_reason'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">حداکثر تخفیف با دلیل (٪)</label>
                        <input type="number" class="form-control" name="fee_max_discount_with_reason" min="10" max="100" value="<?= (int)$s['fee_max_discount_with_reason'] ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><?= icon('save', 'me-1') ?> ذخیرهٔ تنظیمات</button>
                </div>
            </form>
        </div>
    </div>

    <!-- بخش ۲: گروه‌های قیمتی -->
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= icon('grid', 'me-1') ?> گروه‌های قیمتی</h5>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newGroupModal"><?= icon('plus', 'me-1') ?> گروه جدید</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>کد</th>
                            <th>نام</th>
                            <th>آیکون</th>
                            <th>دهک</th>
                            <th>روستایی</th>
                            <th>مبلغ کل (تومان)</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $g): ?>
                            <tr id="group-<?= (int)$g['id'] ?>">
                                <td><span class="badge bg-info fs-6"><?= htmlspecialchars($g['code']) ?></span></td>
                                <td>
                                    <span class="fs-5"><?= htmlspecialchars($g['icon'] ?? '') ?></span>
                                    <?= htmlspecialchars($g['name']) ?>
                                </td>
                                <td><?= htmlspecialchars($g['icon'] ?? '') ?></td>
                                <td><?= (int)$g['decile_min'] ?> تا <?= (int)$g['decile_max'] ?></td>
                                <td>
                                    <?php if ((int)$g['is_rural_eligible'] === 1): ?>
                                        <span class="badge bg-success">بله</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= number_format((int)$g['total_amount']) ?></strong></td>
                                <td>
                                    <?php if ((int)$g['is_active'] === 1): ?>
                                        <span class="badge bg-success">فعال</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">غیرفعال</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editGroupModal<?= (int)$g['id'] ?>"><?= icon('edit', '', 14) ?> ویرایش</button>
                                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#itemsModal<?= (int)$g['id'] ?>"><?= icon('list', '', 14) ?> آیتم‌ها</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- بخش ۳: راهنمای منطق محاسبه -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><?= icon('info', 'me-1') ?> منطق محاسبهٔ خودکار</h5>
        </div>
        <div class="card-body">
            <ol class="mb-0">
                <li>اگر نرخ‌نامه غیرفعال باشد → مبلغ ثابت قبلی (۵۰۰,۰۰۰ تومان) اعمال می‌شود.</li>
                <li>اگر متقاضی روستایی باشد (فیلد <code>village</code> یا <code>rural_district</code> پر شده) → گروه الف اعمال می‌شود، حتی اگر دهک درآمدی بالاتر باشد.</li>
                <li>اگر روستایی نباشد → بر اساس دهک درآمدی، گروه متناسب انتخاب می‌شود.</li>
                <li>اگر دهک خالی یا نامعتبر باشد → گروه fallback (پیش‌فرض: ج) با flag <code>fallback_used</code>.</li>
                <li>تشخیص خودکار مشکوک بودن:
                                    <ul>
                                        <li><code>rural_address_mismatch</code>: روستا + شهر بزرگ (تهران/مشهد/...)</li>
                                        <li><code>decile_history_conflict</code>: دهک فعلی ۳+ کمتر از دهک قبلی شخص</li>
                                        <li><code>low_decile_urban</code>: دهک ۱-۲ + آدرس شهری بزرگ</li>
                                    </ul>
                </li>
                <li>پس از فعال‌سازی درخواست پرداخت، مدیر در صفحهٔ پرونده می‌تواند خدمات انجام‌نشده را تیک بردارد → مبلغ نهایی به‌صورت خودکار کسر می‌شود (با حداقل ۱۰٪ مبلغ اصلی).</li>
            </ol>
        </div>
    </div>
</div>

<!-- مودال: گروه جدید -->
<div class="modal fade" id="newGroupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= $base ?>admin/fee-tariff/group/save">
                <?= Session::csrfField() ?>
                <input type="hidden" name="id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title">گروه جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php $this->renderGroupForm([]); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال: ویرایش گروه‌ها -->
<?php foreach ($groups as $g): ?>
<div class="modal fade" id="editGroupModal<?= (int)$g['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= $base ?>admin/fee-tariff/group/save">
                <?= Session::csrfField() ?>
                <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">ویرایش گروه «<?= htmlspecialchars($g['name']) ?>»</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php $this->renderGroupForm($g); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال: آیتم‌های گروه -->
<div class="modal fade" id="itemsModal<?= (int)$g['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="post" action="<?= $base ?>admin/fee-tariff/items/save">
                <?= Session::csrfField() ?>
                <input type="hidden" name="group_id" value="<?= (int)$g['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">آیتم‌های «<?= htmlspecialchars($g['name']) ?>»</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $items = $groupedItems[$g['code']] ?? [];
                    if (empty($items)):
                    ?>
                        <div class="alert alert-warning">هیچ آیتمی برای این گروه تعریف نشده. می‌توانید از دکمهٔ زیر اضافه کنید.</div>
                    <?php else: ?>
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th style="width:60px">ردیف</th>
                                    <th>کلید</th>
                                    <th>عنوان خدمت</th>
                                    <th style="width:160px">مبلغ (تومان)</th>
                                    <th style="width:80px">ترتیب</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $i => $item): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><code><?= htmlspecialchars($item['item_key']) ?></code>
                                            <input type="hidden" name="items[<?= htmlspecialchars($item['item_key']) ?>][sort]" value="<?= (int)$item['sort_order'] ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="items[<?= htmlspecialchars($item['item_key']) ?>][label]" value="<?= htmlspecialchars($item['item_label']) ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" name="items[<?= htmlspecialchars($item['item_key']) ?>][amount]" value="<?= (int)$item['amount'] ?>" min="0">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" name="items[<?= htmlspecialchars($item['item_key']) ?>][sort]" value="<?= (int)$item['sort_order'] ?>" min="0">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th colspan="3" class="text-end">جمع کل:</th>
                                    <th id="sumGroup<?= (int)$g['id'] ?>">
                                        <?= number_format(array_sum(array_map(fn($x) => (int)$x['amount'], $items))) ?>
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">ذخیرهٔ مبالغ</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
// به‌روزرسانی زندهٔ جمع هر گروه در مودال آیتم‌ها
document.querySelectorAll('form[action*="fee-tariff/items/save"]').forEach(form => {
    form.addEventListener('input', () => {
        const inputs = form.querySelectorAll('input[name$="[amount]"]');
        let total = 0;
        inputs.forEach(i => total += parseInt(i.value) || 0);
        const sumEl = form.querySelector('tfoot th[id^="sumGroup"]');
        if (sumEl) sumEl.textContent = new Intl.NumberFormat('fa-IR').format(total);
    });
});
</script>
