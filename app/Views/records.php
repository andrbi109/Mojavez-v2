<?php
/**
 * صفحهٔ سوابق من (بازنویسی‌شده برای مدل فروشگاهی)
 * -----------------------------------------------------------------
 * به‌جای احراز هویت ۶ عاملی، کاربر با نشست خود وارد می‌شود
 * و مستقیماً تمام پرونده‌ها و پرداخت‌هایش را می‌بیند.
 */
$base = rtrim(BASE_URL, '/') . '/';
$apps = $applications ?? [];
$pays = $payments ?? [];
$u = $user ?? [];
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$statusMeta = (require CONFIG_PATH . '/statuses.php')['statuses'];
$stateBadge = ['جاری' => 'primary', 'صادر شده' => 'success', 'باطل شده' => 'danger', 'منقضی' => 'secondary'];
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-10">

        <!-- بخش خلاصه -->
        <div class="card mb-4 border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
            <div class="card-body p-4" style="background: linear-gradient(135deg, var(--navy), #1B3A5C); color: #fff;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="fw-bold mb-1 text-white"><?= icon('folder', 'me-2', 22) ?> سوابق من</h4>
                        <p class="mb-0 opacity-75 small"><?= $e($u['first_name'] ?? '') ?> <?= $e($u['last_name'] ?? '') ?> — <?= count($apps) ?> درخواست</p>
                    </div>
                    <a href="<?= $base ?>services" class="btn btn-light btn-sm"><?= icon('plus', '', 14) ?> درخواست جدید</a>
                </div>
            </div>
        </div>

        <!-- فهرست پرونده‌ها -->
        <div class="card mb-4">
            <div class="card-header fw-bold"><?= icon('folder', 'me-1', 18) ?> پرونده‌ها و مجوزها</div>
            <div class="card-body">
                <?php if (empty($apps)): ?>
                    <div class="text-center py-4">
                        <div style="font-size: 3rem; opacity: .3"><?= icon('folder', '', 48) ?></div>
                        <p class="text-muted mt-2 mb-3">هنوز درخواستی ثبت نکرده‌اید</p>
                        <a href="<?= $base ?>services" class="btn btn-primary">شروع اولین درخواست</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>کد رهگیری</th>
                                    <th>خدمت</th>
                                    <th>وضعیت</th>
                                    <th>وضعیت مجوز</th>
                                    <th>تاریخ ثبت</th>
                                    <th>اقدام</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apps as $app): ?>
                                    <tr>
                                        <td><code dir="ltr"><?= $e($app['tracking_code']) ?></code></td>
                                        <td><?= $e($app['service_title'] ?? 'مجوز صنایع دستی') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $statusMeta[$app['current_status']]['color'] ?? 'secondary' ?>">
                                                <?= $e($app['current_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($app['license_state'])): ?>
                                                <span class="badge bg-<?= $stateBadge[$app['license_state']] ?? 'secondary' ?>">
                                                    <?= $e($app['license_state']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= persianDate((string)$app['created_at']) ?></td>
                                        <td>
                                            <a href="<?= $base ?>tracking/status/<?= rawurlencode($app['tracking_code']) ?>" class="btn btn-sm btn-outline-primary">
                                                <?= icon('eye', '', 14) ?> مشاهده
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- فهرست پرداخت‌ها -->
        <div class="card mb-4">
            <div class="card-header fw-bold"><?= icon('money', 'me-1', 18) ?> پرداخت‌ها</div>
            <div class="card-body">
                <?php if (empty($pays)): ?>
                    <p class="text-muted text-center py-3 mb-0">هنوز پرداختی ثبت نشده است.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>مبلغ</th>
                                    <th>کد رهگیری</th>
                                    <th>کانال</th>
                                    <th>وضعیت</th>
                                    <th>تاریخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pays as $pay): ?>
                                    <tr>
                                        <td><strong><?= number_format((int)$pay['amount']) ?></strong> ت</td>
                                        <td><code dir="ltr"><?= $e($pay['tracking_code'] ?? '') ?></code></td>
                                        <td class="small"><?= $e($pay['channel_title'] ?? '—') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $pay['status'] === 'تأیید شده' ? 'success' : ($pay['status'] === 'رد شده' ? 'danger' : 'warning text-dark') ?>">
                                                <?= $e($pay['status']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted"><?= persianDate((string)$pay['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
