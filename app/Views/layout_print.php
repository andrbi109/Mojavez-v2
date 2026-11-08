<?php
/**
 * قالب مخصوص صفحهٔ چاپی پرونده — همسو با تم پروژه
 * --------------------------------------------------
 * از همان متغیرهای CSS، پالت رنگی و فونت پروژه استفاده می‌کند.
 * مدیریت هوشمند شکست صفحات (page-break) برای چاپ بدون بخش خالی.
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
        /* === متغیرهای تم (در صورت عدم بارگذاری style.css) === */
        :root {
            --turquoise: #0E7C7B;
            --turquoise-dark: #0A5F5E;
            --turquoise-light: #E6F3F2;
            --saffron: #D9A441;
            --saffron-dark: #B9862C;
            --saffron-light: #FBF3E0;
            --navy: #16283C;
            --navy-light: #22374E;
            --ink: #22303C;
            --terracotta: #B5493F;
            --persian-green: #3E8E5A;
            --cream: #FAF6EE;
            --card-border: #EAE3D6;
            --muted: #7A8899;
        }

        * { box-sizing: border-box; }
        body {
            background: var(--cream);
            font-family: 'Vazirmatn', 'Vazir', Tahoma, sans-serif;
            color: var(--ink);
            margin: 0;
            padding: 0;
            font-size: 14px;
            line-height: 1.6;
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
            border: 1px solid var(--card-border);
            border-radius: 50px;
            padding: .35rem .5rem;
            box-shadow: 0 4px 20px rgba(22,40,60,.12);
        }
        .print-toolbar .btn { border-radius: 50px; padding: .3rem 1rem; font-size: .82rem; }

        /* === برگهٔ A4 === */
        .print-container {
            max-width: 210mm;
            min-height: 297mm;
            margin: 1.5rem auto 5rem;
            background: #fff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(22,40,60,.08);
        }

        /* === سربرگ === */
        .doc-header {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            color: #fff;
            padding: 20px 10px 10px;
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
            background: radial-gradient(ellipse, rgba(217,164,65,.06) 0%, transparent 70%);
        }
        .doc-header h3 { margin: 0 0 3px; font-weight: 700; font-size: 1.15rem; }
        .doc-header .subtitle { margin: 0; font-size: .78rem; opacity: .85; }
        .doc-header .meta { font-size: .7rem; opacity: .6; margin-top: 2px; }
        .doc-header .tracking-box {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(217,164,65,.3);
            border-radius: 8px;
            padding: 6px 12px;
            text-align: center;
        }
        .doc-header .tracking-box .label { font-size: .65rem; opacity: .7; }
        .doc-header .tracking-box .code { font-weight: 700; color: var(--saffron); }

        /* === محتوای برگه === */
        .doc-body { padding: 20px 28px; }

        /* === عنوان بخش === */
        .doc-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .88rem;
            font-weight: 700;
            color: var(--turquoise-dark);
            margin: 22px 0 8px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--turquoise-light);
        }
        .doc-section-title:first-child { margin-top: 0; }
        .doc-section-title .num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px; height: 22px;
            background: var(--turquoise);
            color: #fff;
            border-radius: 5px;
            font-size: .72rem;
            font-weight: 700;
        }
        .doc-section-title .ornament { color: var(--saffron); font-size: .9rem; }

        /* === جدول اطلاعات === */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .info-table td {
            border: 1px solid var(--card-border);
            padding: 5px 9px;
            font-size: .82rem;
            vertical-align: top;
        }
        .info-table td:first-child {
            width: 32%;
            font-weight: 600;
            color: var(--navy);
            background: var(--turquoise-light);
        }
        .info-table td:last-child { color: var(--ink); }

        /* === جدول تاریخچه === */
        .log-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: .78rem; }
        .log-table thead th {
            background: var(--turquoise-light);
            border: 1px solid var(--card-border);
            padding: 5px 7px;
            font-weight: 700;
            color: var(--turquoise-dark);
            text-align: right;
        }
        .log-table tbody td {
            border: 1px solid var(--card-border);
            padding: 4px 7px;
            vertical-align: top;
        }
        .log-table tbody tr:nth-child(even) { background: #FBFAF7; }

        /* === فهرست مدارک === */
        .doc-list { list-style: none; padding: 0; margin: 0; }
        .doc-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 9px;
            border: 1px solid var(--card-border);
            border-radius: 6px;
            margin-bottom: 4px;
            font-size: .8rem;
            background: #FBFAF7;
        }
        .doc-list li .badge {
            font-size: .62rem;
            min-width: 65px;
            text-align: center;
        }

        /* === badge وضعیت === */
        .status-badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: .72rem;
            font-weight: 600;
        }
        .status-approved { background: #D1FAE5; color: #065F46; }
        .status-pending  { background: var(--saffron-light); color: var(--saffron-dark); }
        .status-rejected { background: #FEE2E2; color: var(--terracotta); }
        .status-review   { background: #DBEAFE; color: #1E40AF; }

        /* === کادر امضا === */
        .sign-area {
            margin-top: 36px;
            display: flex;
            justify-content: space-between;
            gap: 2rem;
        }
        .sign-box {
            flex: 1;
            border-top: 1.5px dashed var(--card-border);
            padding-top: 6px;
            text-align: center;
            font-size: .78rem;
            color: var(--muted);
        }
        .sign-box small { color: var(--muted); font-size: .72rem; }

        /* === پاورقی === */
        .doc-footer {
            padding: 10px 28px;
            background: #FBFAF7;
            border-top: 1px solid var(--card-border);
            text-align: center;
            font-size: .68rem;
            color: var(--muted);
        }

        /* === مدیریت شکست صفحات (page-break) === */
        /*
         * استراتژی: هر «بخش» (section-title + محتوای آن) باید با هم بمانند.
         * اگر یک بخش در انتهای صفحه جا نمی‌شود، کل بخش به صفحه بعد منتقل می‌شود.
         * این از ایجاد بخش‌های خالی (مثل عنوان تنها در یک صفحه) جلوگیری می‌کند.
         */
        .doc-section {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* کادر امضا حتماً در یک صفحه کامل باشد */
        .sign-area {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* اجباری به صفحه بعدی (در صورت نیاز) */
        .force-break {
            page-break-before: always;
            break-before: page;
        }

        /* === چاپ === */
        @media print {
            body { background: #fff; font-size: 12px; }
            .print-container {
                box-shadow: none;
                margin: 0;
                border: none;
                border-radius: 0;
                max-width: none;
                min-height: auto;
            }
            .print-toolbar { display: none !important; }
            .doc-header { border-radius: 0; }
            .doc-body { padding: 14px 0; }
            .info-table td { padding: 4px 7px; font-size: .78rem; }
            .log-table tbody td { padding: 3px 6px; }
            .doc-section-title { margin: 16px 0 6px; }
            @page { size: A4 portrait; margin: 14mm 12mm; }
        }

        /* === موبایل === */
        @media (max-width: 575.98px) {
            .print-container { margin: .5rem; border-radius: 6px; }
            .doc-header { padding: 14px 16px; }
            .doc-body { padding: 14px 16px; }
            .info-table td { padding: 4px 6px; font-size: .76rem; }
            .sign-area { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        <button onclick="window.print()" class="btn btn-primary"><?= icon('printer','',16) ?> چاپ</button>
        <button onclick="history.back()" class="btn btn-secondary"><?= icon('arrow-right','',16) ?> بازگشت</button>
    </div>

    <?= $content ?>
</body>
</html>
