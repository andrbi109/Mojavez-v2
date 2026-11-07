<?php
/**
 * قالب مخصوص صفحهٔ چاپی پرونده
 * --------------------------------------------------
 * - نوار ابزار شناور (چاپ / بازگشت) که در حالت پرینت محو می‌شود
 * - استایل A4 با حاشیهٔ استاندارد و مدیریت شکست صفحات
 * - فونت وزیرمتن برای خوانایی فارسی روی کاغذ
 */
$base = rtrim(BASE_URL, '/') . '/';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'پرونده چاپی') ?></title>
    <script>/* صفحهٔ چاپ همیشه روشن است */ document.documentElement.setAttribute('data-bs-theme', 'light');</script>
    <link rel="stylesheet" href="<?= $base ?>assets/font.css">
    <?php if (USE_CDN): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="<?= $base ?>assets/bootstrap.rtl.min.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
    <style>
        body { background: #E8EAED; font-family: 'Vazirmatn', Tahoma, sans-serif; }

        /* برگهٔ A4 روی صفحهٔ نمایش */
        .print-container {
            max-width: 210mm;
            min-height: 297mm;
            margin: 1rem auto 5rem;
            padding: 15mm 14mm;
            background: #fff;
            box-shadow: 0 2px 16px rgba(0,0,0,.12);
            border-radius: 4px;
        }

        .print-header { margin-bottom: 18px; }
        .section-title {
            background: #eef3f6;
            padding: 6px 12px;
            border-radius: 6px;
            margin-top: 26px;
            font-weight: 700;
            border-right: 4px solid var(--primary, #00B4D8);
        }
        .print-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .print-table td, .print-table th { border: 1px solid #999; padding: 6px 8px; font-size: .92rem; }
        .print-table td:first-child { width: 32%; font-weight: 600; background: #f9f9f9; }
        .print-table thead th { background: #eef3f6; }

        /* شکست هوشمند صفحات */
        .page-break { page-break-before: always; break-before: page; }
        .avoid-break { page-break-inside: avoid; break-inside: avoid; }

        /* نشانهٔ تأیید در پایین هر برگه چاپی */
        .print-signature {
            margin-top: 34px;
            display: flex;
            justify-content: space-between;
            gap: 2rem;
        }
        .print-signature .sign-box {
            flex: 1;
            border-top: 1px dashed #999;
            padding-top: 6px;
            text-align: center;
            font-size: .85rem;
            color: #444;
        }

        @media print {
            body { background: #fff; }
            .print-container { box-shadow: none; margin: 0; padding: 0; max-width: none; min-height: auto; border-radius: 0; }
            .print-toolbar { display: none !important; }
            @page { size: A4 portrait; margin: 15mm 12mm; }
        }

        /* موبایل: حاشیهٔ کمتر برای مرور راحت‌تر */
        @media (max-width: 575.98px) {
            .print-container { margin: .5rem; padding: 14px 12px 4rem; min-height: auto; }
            .print-table td, .print-table th { padding: 5px 6px; font-size: .84rem; }
        }
    </style>
</head>
<body>
    <!-- نوار ابزار شناور -->
    <div class="print-toolbar no-print">
        <button onclick="window.print()" class="btn btn-sm btn-primary">🖨️ چاپ پرونده</button>
        <button onclick="history.back()" class="btn btn-sm btn-secondary">↩ بازگشت</button>
    </div>

    <?= $content ?>
</body>
</html>
