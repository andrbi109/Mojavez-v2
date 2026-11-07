<?php
/**
 * کلاس خروجی CSV با پشتیبانی از کاراکترهای فارسی
 */
class CsvExporter
{
    /**
     * تولید و ارسال فایل CSV
     * @param string $filename نام فایل بدون پسوند
     * @param array $headers عناوین ستون‌ها
     * @param array $rows داده‌ها (آرایه‌ای از آرایه‌ها)
     */
    public static function export(string $filename, array $headers, array $rows): void
    {
        // پاکسازی بافر خروجی
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // افزودن BOM برای باز شدن صحیح در Excel
        fprintf($output, "\xEF\xBB\xBF");

        // نوشتن هدرها
        fputcsv($output, $headers);

        // نوشتن داده‌ها
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * تولید خروجی Excel با MIME مناسب (HTML table)
     */
    public static function exportAsExcelHtml(string $filename, array $headers, array $rows): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo "\xEF\xBB\xBF";
        echo '<table border="1">';
        echo '<tr><th>' . implode('</th><th>', array_map('htmlspecialchars', $headers)) . '</th></tr>';
        foreach ($rows as $row) {
            echo '<tr><td>' . implode('</td><td>', array_map('htmlspecialchars', $row)) . '</td></tr>';
        }
        echo '</table></body></html>';
        exit;
    }
}