<?php
/**
 * انتخاب نوع خدمت پیش از شروع ویزارد (فاز ۹ نقشهٔ راه)
 */
$base = rtrim(BASE_URL, '/') . '/';
$serviceIcons = [1 => 'vase', 2 => 'home', 3 => 'users', 4 => 'map-pin', 5 => 'award'];
?>
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card p-4 mb-4 text-center">
            <h4 class="fw-bold mb-1"><?= icon('grid', 'me-1') ?> ابتدا نوع خدمت مورد نظر را انتخاب کنید</h4>
            <p class="text-muted small mb-0">مراحل، فیلدها و مدارک هر خدمت متناسب با همان خدمت نمایش داده می‌شوند.</p>
        </div>

        <div class="row g-3 mb-4">
            <?php foreach ($services as $i => $sv): ?>
            <div class="col-md-6">
                <a href="<?= $base ?>register/service/<?= (int)$sv['id'] ?>" class="text-decoration-none">
                    <div class="service-card text-start" style="min-height:100%">
                        <div class="d-flex align-items-start gap-3">
                            <div class="service-icon <?= $i % 2 ? 'gold' : '' ?>" style="width:52px;height:52px;font-size:1.4rem;"><?= icon($serviceIcons[(int)$sv['id']] ?? 'form', '', 22) ?></div>
                            <div>
                                <h6 class="fw-bold mb-1" style="color:var(--ink)"><?= htmlspecialchars($sv['title']) ?></h6>
                                <?php if (!empty($sv['authority'])): ?>
                                    <div class="small text-muted mb-1"><?= icon('shield', '', 13) ?> <?= htmlspecialchars($sv['authority']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($sv['description'])): ?>
                                    <p class="small mb-0" style="color:var(--muted)"><?= htmlspecialchars($sv['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <p class="text-center text-muted small">
            <?= icon('info', '', 14) ?>
            نمی‌دانید کدام خدمت؟ <a href="<?= $base ?>guide">راهنمای جامع</a> را ببینید یا به
            آیدی <strong dir="ltr">@<?= htmlspecialchars(setting('owner_username', 'Derogaramin')) ?></strong>
            در ایتا/روبیکا/بله پیام دهید.
        </p>
    </div>
</div>
