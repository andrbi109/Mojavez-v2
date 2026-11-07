<?php
/**
 * نمای گزارش‌های آماری گروه‌بندی‌شده
 * متغیرها: $label, $field, $stats
 * اصلاح: مدیریت نبودِ کلید $field و مقادیر NULL بدون صدور Warning
 */
$field = $field ?? 'label';
$totalSum = array_sum(array_map(fn($r) => (int)($r['cnt'] ?? 0), $stats));
?>
<div class="card p-3 p-md-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h4 class="fw-bold mb-0">📊 آمار درخواست‌ها بر اساس <?= htmlspecialchars($label) ?></h4>
        <span class="text-muted small">مجموع: <?= (int)$totalSum ?> درخواست</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:55%"><?= htmlspecialchars($label) ?></th>
                    <th style="width:15%">تعداد</th>
                    <th style="width:30%">سهم</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($stats as $row): ?>
                <?php
                $value = $row[$field] ?? null;
                $cnt = (int)($row['cnt'] ?? 0);
                $pct = $totalSum > 0 ? round($cnt * 100 / $totalSum, 1) : 0;
                ?>
                <tr>
                    <td><?php
                        if ($value === null || $value === '') {
                            echo '<span class="text-muted">— (ثبت نشده)</span>';
                        } elseif (in_array($field, ['reg_date', 'label'], true) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value)) {
                            // تاریخ‌های میلادی گزارش به شمسی تبدیل می‌شوند
                            echo persianDate((string)$value);
                        } else {
                            echo htmlspecialchars((string)$value);
                        }
                    ?></td>
                    <td><span class="badge bg-primary"><?= $cnt ?></span></td>
                    <td>
                        <div class="progress" style="height:10px">
                            <div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted"><?= $pct ?>٪</small>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($stats)): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">داده‌ای برای نمایش وجود ندارد.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
