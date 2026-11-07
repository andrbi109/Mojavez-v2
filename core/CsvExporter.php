<?php
/**
 * کلاس خروجی CSV با پشتیبانی از کاراکترهای فارسی
 *
 * نسخهٔ v0.0.5: افزودن sanitizeCsvCell برای مقابله با CSV/Formula Injection
 * (سلول‌هایی که با =, +, -, @, \t, \r, \n شروع می‌شوند پیشوند ' می‌گیرند).
 */
class CsvExporter
{
    /**
     * پاک‌سازی یک سلول CSV برای جلوگیری از اجرای فرمول در Excel/LibreOffice.
     * اگر مقدار با یکی از کاراکترهای خطرناک =, +, -, @, یا CR/LF/TAB شروع شود،
     * یک آپوستروف پیشوند ('‌) به ابتدای آن افزوده می‌شود تا Excel آن را به‌عنوان
     * متن خام در نظر بگیرد نه فرمول. آپوستروف در نمایش نهایی پنهان می‌شود.
     *
     * @param mixed $value
     * @return string
     */
    public static function sanitizeCell($value): string
    {
        // تبدیل به رشته (بدون از بین بردن null/false)
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        $str = (string)$value;

        // اگر رشته با کاراکترهای خطرناک فرمول شروع شود → پیشوند ' برای خاموش‌سازی
        // (پوشش CVE-2014-3524 / OWASP CSV Injection)
        if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r", "\n"], true)) {
            // تبصره: اعداد منفی (مثل -12) و اعداد علمی (مثل 1E5) که ممکن است با - یا + شروع شوند
            // اگر کل رشته یک عدد معتبر باشد، پیشوند ندهیم.
            if (!is_numeric($str)) {
                $str = "'" . $str;
            }
        }
        return $str;
    }

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

        // نوشتن هدرها (با sanitize برای جلوگیری از injection در عنوان ستون)
        fputcsv($output, array_map([self::class, 'sanitizeCell'], $headers));

        // نوشتن داده‌ها — هر سلول ابتدا sanitize می‌شود
        foreach ($rows as $row) {
            $sanitizedRow = [];
            foreach ($row as $cell) {
                $sanitizedRow[] = self::sanitizeCell($cell);
            }
            fputcsv($output, $sanitizedRow);
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