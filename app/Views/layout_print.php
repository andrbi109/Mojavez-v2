<?php
/**
 * قالب مخصوص صفحهٔ چاپی پرونده — بازطراحی‌شده
 * --------------------------------------------------
 * طراحی مدرن و منسجم برای نمایش و چاپ A4
 */
$base = rtrim(BASE_URL, '/') . '/';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'پرونده چاپی') ?></title>
    <script>document.documentElement.setAttribute('data-bs-theme', 'light');</script>
    <link rel="stylesheet" href="<?= $base ?>assets/font.css">
    <?php if (USE_CDN): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="<?= $base ?>assets/bootstrap.rtl.min.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background: #E8EAED;
            font-family: 'Vazirmatn', Tahoma, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* === نوار ابزار شناور === */
        .print-toolbar {
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: .5rem;
            z-index: 9999;
            background: #fff;
            border-radius: 50px;
            padding: .4rem .5rem;
            box-shadow: 0 4px 24px rgba(0,0,0,.15);
        }
        .print-toolbar .btn { border-radius: 50px; padding: .35rem 1.1rem; font-size: .85rem; }

        /* === برگهٔ A4 === */
        .print-container {
            max-width: 210mm;
            min-height: 297mm;
            margin: 1.5rem auto 6rem;
            padding: 0;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0,0,0,.1);
            border-radius: 8px;
            overflow: hidden;
        }

        /* === سربرگ === */
        .doc-header {
            background: linear-gradient(135deg, #00B4D8 0%, #0077B6 100%);
            color: #fff;
            padding: 24px 32px;
            position: relative;
            overflow: hidden;
        }
        .doc-header::before {
            content: '';
            position: absolute;
            top: -40%;
            inset-inline-end: -10%;
            width: 50%;
            height: 180%;
            background: radial-gradient(ellipse, rgba(255,255,255,.08) 0%, transparent 70%);
        }
        .doc-header h3 { margin: 0 0 4px; font-weight: 800; font-size: 1.35rem; }
        .doc-header .subtitle { margin: 0; font-size: .82rem; opacity: .9; }
        .doc-header .meta { font-size: .75rem; opacity: .75; margin-top: 2px; }
        .doc-header .tracking-box {
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 10px;
            padding: 8px 14px;
            text-align: center;
            backdrop-filter: blur(4px);
        }
        .doc-header .tracking-box .label { font-size: .68rem; opacity: .8; }
        .doc-header .tracking-box .code { font-size: 1.15rem; font-weight: 800; letter-spacing: 1px; }

        /* === محتوای برگه === */
        .doc-body { padding: 24px 32px; }

        /* === عنوان بخش === */
        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .95rem;
            font-weight: 700;
            color: #0077B6;
            margin: 28px 0 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #E0F4F8;
        }
        .section-title .num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background: #00B4D8;
            color: #fff;
            border-radius: 6px;
            font-size: .78rem;
            font-weight: 700;
        }
        .section-title:first-child { margin-top: 0; }

        /* === جدول اطلاعات === */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .info-table td {
            border: 1px solid #E5E7EB;
            padding: 7px 10px;
            font-size: .87rem;
            vertical-align: top;
        }
        .info-table td:first-child {
            width: 32%;
            font-weight: 600;
            color: #374151;
            background: #F9FAFB;
        }
        .info-table td:last-child { color: #1F2937; }

        /* === جدول تاریخچه === */
        .log-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: .82rem; }
        .log-table thead th {
            background: #F3F4F6;
            border: 1px solid #E5E7EB;
            padding: 6px 8px;
            font-weight: 700;
            color: #374151;
            text-align: right;
        }
        .log-table tbody td {
            border: 1px solid #E5E7EB;
            padding: 5px 8px;
            vertical-align: top;
        }
        .log-table tbody tr:nth-child(even) { background: #FAFAFA; }

        /* === فهرست مدارک === */
        .doc-list { list-style: none; padding: 0; margin: 0; }
        .doc-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: .85rem;
            background: #FAFAFA;
        }
        .doc-list li .badge {
            font-size: .65rem;
            min-width: 70px;
            text-align: center;
        }

        /* === کادر امضا === */
        .sign-area {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            gap: 2rem;
        }
        .sign-box {
            flex: 1;
            border-top: 1.5px dashed #9CA3AF;
            padding-top: 8px;
            text-align: center;
            font-size: .8rem;
            color: #4B5563;
        }
        .sign-box small { color: #6B7280; }

        /* === پاورقی === */
        .doc-footer {
            margin-top: 24px;
            padding: 12px 32px;
            background: #F9FAFB;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            font-size: .72rem;
            color: #9CA3AF;
        }

        /* === رنگ‌های وضعیت === */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: .75rem;
            font-weight: 600;
        }
        .status-approved { background: #D1FAE5; color: #065F46; }
        .status-pending  { background: #FEF3C7; color: #92400E; }
        .status-rejected { background: #FEE2E2; color: #991B1B; }
        .status-review   { background: #DBEAFE; color: #1E40AF; }

        /* === شکست هوشمند صفحات === */
        .page-break { page-break-before: always; break-before: page; }
        .avoid-break { page-break-inside: avoid; break-inside: avoid; }

        /* === چاپ === */
        @media print {
            body { background: #fff; }
            .print-container { box-shadow: none; margin: 0; border-radius: 0; max-width: none; min-height: auto; }
            .print-toolbar { display: none !important; }
            .doc-header { border-radius: 0; }
            @page { size: A4 portrait; margin: 12mm 10mm; }
            .doc-body { padding: 16px 0; }
        }

        /* === موبایل === */
        @media (max-width: 575.98px) {
            .print-container { margin: .5rem; border-radius: 4px; }
            .doc-header { padding: 16px; }
            .doc-body { padding: 14px; }
            .info-table td { padding: 5px 6px; font-size: .8rem; }
            .sign-area { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <!-- نوار ابزار شناور -->
    <div class="print-toolbar no-print">
        <button onclick="window.print()" class="btn btn-primary"><?= icon('printer','',16) ?> چاپ پرونده</button>
        <button onclick="history.back()" class="btn btn-secondary"><?= icon('arrow-right','',16) ?> بازگشت</button>
    </div>

    <?= $content ?>
</body>
</html>
