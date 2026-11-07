<?php
/**
 * جزء مشترک: نمایش مقادیر فیلدهای پویای یک پرونده
 * -------------------------------------------------------
 * ورودی: $dynValues — خروجی ApplicantFieldValue::findByApplicantId
 *        $dynMode   — 'admin' (لینک مدرک) یا 'print' (متن ساده)
 * مقادیر فایلی به شکل «doc:{id}» ذخیره شده‌اند و به لینک/ارجاع مدرک تبدیل می‌شوند.
 */
$dynValues = $dynValues ?? [];
$dynMode = $dynMode ?? 'admin';
if (empty($dynValues)) return;
?>
<table class="table table-borderless mb-0">
    <?php foreach ($dynValues as $dv): ?>
        <tr>
            <th style="width:35%"><?= htmlspecialchars($dv['label']) ?></th>
            <td>
                <?php if (str_starts_with((string)$dv['field_value'], 'doc:')): ?>
                    <?php $docId = (int)substr($dv['field_value'], 4); ?>
                    <?php if ($dynMode === 'admin'): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary view-doc-btn"
                                data-id="<?= $docId ?>" data-applicant="<?= (int)($applicant['id'] ?? 0) ?>">
                            📎 مشاهدهٔ فایل پیوست
                        </button>
                    <?php else: ?>
                        <span class="text-muted small">📎 فایل پیوست (شناسهٔ مدرک: <?= $docId ?>)</span>
                    <?php endif; ?>
                <?php elseif ($dv['field_value'] === '' || $dv['field_value'] === null): ?>
                    <span class="text-muted">—</span>
                <?php else: ?>
                    <?= nl2br(htmlspecialchars((string)$dv['field_value'])) ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
