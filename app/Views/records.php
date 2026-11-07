<?php
/**
 * صفحهٔ مشاهدهٔ سوابق (فاز ۷+۸ نقشهٔ راه)
 * احراز هویت شش‌عاملی → فهرست همهٔ پرونده‌ها/مجوزهای شخص
 */
$base = rtrim(BASE_URL, '/') . '/';
$results = $results ?? null;
$personName = $personName ?? '';
$error = $error ?? '';
$old = $old ?? [];
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$statusMeta = (require CONFIG_PATH . '/statuses.php')['statuses'];
$stateBadge = ['جاری' => 'primary', 'صادر شده' => 'success', 'باطل شده' => 'danger', 'منقضی' => 'secondary'];
?>
<div class="row justify-content-center">
    <div class="col-xl-10">

        <div class="card p-4 mb-4 text-center">
            <div style="color:var(--turquoise)" class="mb-2"><?= icon('users', '', 44) ?></div>
            <h4 class="fw-bold mb-1">مشاهدهٔ سوابق پرونده‌ها و مجوزها</h4>
            <p class="text-muted small mb-0">
                برای مشاهدهٔ همهٔ پرونده‌های خود (جاری، صادرشده، باطل و منقضی)،
                شش عامل هویتی زیر را <strong>دقیقاً مطابق آخرین ثبت‌نام</strong> وارد کنید.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 text-center"><?= $e($error) ?></div>
        <?php endif; ?>

        <?php if (!$results): ?>
        <!-- فرم احراز هویت -->
        <div class="card p-4">
            <form method="post" action="<?= $base ?>my-records/verify">
                <?= Session::csrfField() ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">کد ملی <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="national_code" maxlength="10" pattern="\d{10}" inputmode="numeric" required dir="ltr" value="<?= $e($old['national_code'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نام <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="first_name" maxlength="50" required value="<?= $e($old['first_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="last_name" maxlength="50" required value="<?= $e($old['last_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">نام پدر <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="father_name" maxlength="50" required value="<?= $e($old['father_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">شمارهٔ موبایل <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="mobile" maxlength="11" pattern="09\d{9}" inputmode="numeric" required dir="ltr" value="<?= $e($old['mobile'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاریخ تولد <span class="text-danger">*</span></label>
                        <div class="row g-1">
                            <div class="col-4"><select class="form-select" name="birth_year" required><option value="">سال</option>
                                <?php $cy = currentPersianYear(); for ($y = 1300; $y <= $cy; $y++): ?><option value="<?= $y ?>"><?= $y ?></option><?php endfor; ?>
                            </select></div>
                            <div class="col-4"><select class="form-select" name="birth_month" required><option value="">ماه</option>
                                <?php $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                                foreach ($months as $mi => $mn): ?><option value="<?= $mi + 1 ?>"><?= $mn ?></option><?php endforeach; ?>
                            </select></div>
                            <div class="col-4"><select class="form-select" name="birth_day" required><option value="">روز</option>
                                <?php for ($d = 1; $d <= 31; $d++): ?><option value="<?= $d ?>"><?= $d ?></option><?php endfor; ?>
                            </select></div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-4 w-100 py-2"><?= icon('search', '', 17) ?> احراز هویت و مشاهدهٔ سوابق</button>
            </form>
            <div class="alert alert-light border small mt-3 mb-0">
                🔒 حریم خصوصی: اطلاعات فقط برای تطبیق استفاده می‌شود و پیش از احراز کامل، هیچ داده‌ای نمایش داده نمی‌شود.
                پاسخ خطا برای همهٔ حالت‌ها یکسان است. تلاش‌های ناموفق پیاپی باعث قفل موقت می‌شود.
            </div>
        </div>

        <?php else: ?>
        <!-- نتایج سوابق -->
        <div class="card p-4 mb-4" style="border-right:5px solid var(--persian-green);">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1"><?= icon('check-circle', 'me-1') ?> هویت تأیید شد</h5>
                    <p class="text-muted small mb-0">سوابق <strong><?= $e($personName) ?></strong> — <?= count($results) ?> پرونده</p>
                </div>
                <a href="<?= $base ?>my-records" class="btn btn-outline-secondary btn-sm"><?= icon('search', '', 14) ?> جستجوی جدید</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <?php foreach ($results as $app): ?>
            <div class="col-md-6">
                <div class="card h-100 p-3" style="<?= ($app['license_state'] ?? 'جاری') === 'باطل شده' ? 'border-right:4px solid var(--terracotta)' : (($app['license_state'] ?? '') === 'صادر شده' ? 'border-right:4px solid var(--persian-green)' : 'border-right:4px solid var(--turquoise)') ?>">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                        <div>
                            <strong><?= $e($app['license_title'] ?? '—') ?></strong>
                            <div><code style="font-size:.75rem"><?= $e($app['tracking_code']) ?></code></div>
                        </div>
                        <div class="d-flex flex-column gap-1 align-items-end">
                            <span class="badge bg-<?= $stateBadge[$app['license_state'] ?? 'جاری'] ?? 'secondary' ?>"><?= $e($app['license_state'] ?? 'جاری') ?></span>
                            <span class="badge bg-<?= $statusMeta[$app['current_status']]['color'] ?? 'secondary' ?>"><?= $e($app['current_status']) ?></span>
                        </div>
                    </div>
                    <ul class="small text-muted mb-2 ps-0 list-unstyled">
                        <li><?= icon('map-pin', '', 13) ?> <?= $e(trim(($app['province'] ?? '') . ' — ' . ($app['city'] ?? ''), ' —')) ?></li>
                        <li><?= icon('clock', '', 13) ?> ثبت: <?= persianDate((string)($app['registration_completed_at'] ?? $app['created_at'])) ?></li>
                        <?php if (!empty($app['license_valid_until'])): ?>
                            <li><?= icon('award', '', 13) ?> اعتبار مجوز تا: <?= persianDate((string)$app['license_valid_until']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($app['parent_application_id'])): ?>
                            <li><?= icon('folder', '', 13) ?> تمدید/ارتقای پروندهٔ #<?= (int)$app['parent_application_id'] ?></li>
                        <?php endif; ?>
                    </ul>
                    <a href="<?= $base ?>tracking/status/<?= $e(rawurlencode($app['tracking_code'])) ?>" class="btn btn-sm btn-outline-primary mt-auto">
                        <?= icon('eye', '', 14) ?> مشاهدهٔ وضعیت و تایم‌لاین
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
