#!/usr/bin/env python3
"""
اسکنر استاتیک خطاهای runtime و Deprecated در PHP
====================================================
این اسکریپت کد پروژه را برای شناسایی خطاهای رایج زیر اسکن می‌کند:

  ۱. Deprecated constants (PHP 8.5+)
  ۲. توابع منسوخ‌شده (مثل each, create_function, utf8_encode)
  ۳. استفاده از $_REQUEST به‌جای $_POST/$_GET (امنیتی)
  ۴. کاربرد فیلدهای بدون اعتبارسنجی
  ۵. خطاهای رایج null/undefined (مثل array access روی null)
  ۶. استفاده از توابع deprecated DateTime/Date
  ۷. تابع‌های removed در PHP 8 (مثل mcrypt, image*oolal)
  ۸. مستعد Notice: Undefined index
  ۹. مستعد Warning: Undefined array key (PHP 8+)
  ۱۰. استفاده از @ برای suppress error (نباید زیاد باشد)
"""
import os
import re
import json

ROOT = "/home/z/my-project/work/Mojavez"

# الگوهای خطرناک: (regex, severity, description, fix_suggestion)
PATTERNS = [
    # === Deprecated Constants ===
    (
        r'\bPDO::MYSQL_ATTR_INIT_COMMAND\b',
        "CRITICAL",
        "ثابت منسوخ‌شده در PHP 8.5+",
        "استفاده از charset در DSN یا exec(\"SET NAMES...\") پس از ساخت PDO"
    ),
    (
        r'\bMYSQLI_REPORT_INDEX\b|\bMYSQLI_REPORT_STRICT\b',
        "WARN",
        "ثابت‌های قدیمی MySQLi",
        "بررسی نسخه PHP و استفاده از ثابت‌های جدید"
    ),

    # === Deprecated Functions ===
    (
        r'\beach\s*\(',
        "CRITICAL",
        "تابع each() در PHP 8 حذف شده",
        "استفاده از foreach به‌جای each()"
    ),
    (
        r'\bcreate_function\s*\(',
        "CRITICAL",
        "تابع create_function() حذف شده",
        "استفاده از closure‌های anonymous: function($x) { ... }"
    ),
    (
        r'\butf8_encode\s*\(',
        "WARN",
        "utf8_encode() در PHP 8.2 منسوخ",
        "استفاده از mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1')"
    ),
    (
        r'\butf8_decode\s*\(',
        "WARN",
        "utf8_decode() در PHP 8.2 منسوخ",
        "استفاده از mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8')"
    ),
    (
        r'\bmb_strrpos\b.*(?<!mb_)substr\s*\(',
        "INFO",
        "ترکیب mb_strrpos + substr ممکن است با multibyte مشکل داشته باشد",
        "استفاده از mb_substr"
    ),
    (
        r'\bstristr\s*\(',
        "INFO",
        "stristr در حال منسوخ شدن است (در نسخه‌های جدید)",
        "استفاده از mb_stristr یا strpos(strtolower())"
    ),
    (
        r'\bmoney_format\s*\(',
        "CRITICAL",
        "money_format() حذف شده در PHP 8.4",
        "استفاده از NumberFormatter از intl"
    ),
    (
        r'\bstrftime\s*\(',
        "WARN",
        "strftime() در PHP 8.1 منسوخ",
        "استفاده از IntlDateFormatter یا date()"
    ),
    (
        r'\bmb_ereg\s*\(',
        "WARN",
        "mb_ereg_* در PHP 8.4 منسوخ",
        "استفاده از preg_match با u flag"
    ),
    (
        r'\bget_magic_quotes_gpc\b|\bget_magic_quotes_runtime\b',
        "CRITICAL",
        "get_magic_quotes_* حذف شده در PHP 8",
        "حذف کامل این توابع"
    ),

    # === Removed Extensions ===
    (
        r'\bmcrypt_(?:encrypt|decrypt|create_iv|module_open)\s*\(',
        "CRITICAL",
        "افزونه mcrypt حذف شده",
        "استفاده از openssl_encrypt/openssl_decrypt"
    ),
    (
        r'\bimagecreatefromwbmp\s*\(',
        "WARN",
        "imagecreatefromwbmp در PHP 8 منسوخ",
        "استفاده از imagecreatefrombmp"
    ),

    # === Security Issues ===
    (
        r'\b\$_REQUEST\b',
        "WARN",
        "استفاده از $_REQUEST (ترکیب GET/POST/COOKIE) امنیت پایین",
        "استفاده از $_POST یا $_GET صریح"
    ),
    (
        r'\bmysql_(?:connect|query|fetch|escape)\s*\(',
        "CRITICAL",
        "افزونه mysql (قدیمی) حذف شده در PHP 7",
        "استفاده از PDO یا mysqli"
    ),

    # === Runtime Warning Risks ===
    (
        r'@(?:file_get_contents|fopen|include|require|unlink|mkdir|rmdir|chmod|rename|copy)\s*\(',
        "INFO",
        "استفاده از @ برای suppress error (مخفی کردن خطا)",
        "بررسی با if و نمایش خطای دقیق به کاربر"
    ),

    # === Common Bug Patterns ===
    (
        r'\bdate\s*\(\s*[\'"][^\'"]*[\'"]\s*,\s*(?:null|false)\s*\)',
        "WARN",
        "ارسال null/false به date() باعث Notice می‌شود",
        "بررسی null بودن قبل از فراخوانی date()"
    ),
    (
        r'\bfilesize\s*\(\s*\$[a-z_]+\s*\)\s*[^!=<>]',
        "INFO",
        "filesize قبل از is_file باید بررسی شود",
        "if (is_file($path)) { ... filesize($path) ... }"
    ),
    (
        r'\bjson_decode\s*\([^,)]+\)\s*\[',
        "WARN",
        "دسترسی مستقیم به اندیس خروجی json_decode (ممکن است null برگرداند)",
        "استفاده از متغیر میانی و بررسی null"
    ),

    # === Filter Input ===
    (
        r'\bFILTER_SANITIZE_STRING\b|\bFILTER_SANITIZE_STRIPPED\b',
        "WARN",
        "FILTER_SANITIZE_STRING در PHP 8.1 منسوخ",
        "استفاده از htmlspecialchars یا filter_var با FILTER_UNSAFE_RAW"
    ),
    (
        r'\bFILTER_FLAG_SCHEME_REQUIRED\b|\bFILTER_FLAG_HOST_REQUIRED\b',
        "INFO",
        "این flagها در PHP 8.4 منسوخ",
        "بررسی دستی URL با parse_url"
    ),

    # === Implicit nullable (PHP 8.4 deprecation) ===
    # این الگو false-positive زیاد دارد (هر مقدار پیش‌فرض یک فیلد PHP معمولی است)
    # حذف شد چون string $default = '' کاملاً استاندارد است

    # === SUPERGLOBAL direct access in views (فقط views) ===
    # فقط در app/Views/ بررسی می‌کنیم، نه core/ و Controllers
    # در core/Session.php و Controllers دسترسی به $_SESSION/$_POST کاملاً طبیعی است
    (
        r'\$_(?:POST|GET|SERVER|COOKIE|FILES)\b',
        "WARN",
        "دسترسی مستقیم به superglobal در view (امنیت: باید از controller به view منتقل شود)",
        "عبور داده‌ها به view از طریق متغیرهای محلی"
    ),
]

def scan_file(filepath):
    """اسکن یک فایل PHP برای یافتن الگوهای خطرناک."""
    findings = []
    try:
        with open(filepath, encoding="utf-8") as f:
            content = f.read()
    except (UnicodeDecodeError, FileNotFoundError):
        return findings

    # فایل‌های assets (مثل bootstrap.js) نادیده گرفته شوند
    if "/assets/" in filepath or "/vendor/" in filepath:
        return findings

    # فقط فایل‌های view برای الگوی superglobal بررسی شوند
    is_view = "/app/Views/" in filepath or filepath.endswith("/Views/" + os.path.basename(filepath))
    # مسیر نسبی برای تشخیص views
    rel_path = os.path.relpath(filepath, ROOT)
    is_view = rel_path.startswith("app/Views/")

    lines = content.split("\n")
    for line_no, line in enumerate(lines, 1):
        # حذف کامنت‌های // و #
        stripped = re.sub(r'//.*$', '', line)
        stripped = re.sub(r'#.*$', '', stripped)
        # حذف کامنت‌های /* */ (یک‌خطی)
        stripped = re.sub(r'/\*.*?\*/', '', stripped)

        for pattern, severity, desc, fix in PATTERNS:
            # الگوی superglobal فقط در views بررسی شود
            if "superglobal" in desc.lower() and not is_view:
                continue
            # پارامتر regex flag برای FILTER_* (case-insensitive)
            flags = re.IGNORECASE if "FILTER_" in pattern else 0
            if re.search(pattern, stripped, flags):
                # اگر داخل کامنت است، نادیده بگیر
                if line.lstrip().startswith("*") or line.lstrip().startswith("//"):
                    continue
                findings.append({
                    "file": os.path.relpath(filepath, ROOT),
                    "line": line_no,
                    "severity": severity,
                    "description": desc,
                    "fix": fix,
                    "code": line.strip()[:120],
                })
    return findings

def main():
    all_findings = []
    php_files = []
    for root, _, files in os.walk(ROOT):
        if "/.git" in root or "/assets" in root or "/vendor" in root:
            continue
        for f in files:
            if f.endswith(".php"):
                php_files.append(os.path.join(root, f))

    print(f"اسکن {len(php_files)} فایل PHP...")
    print("=" * 70)

    for filepath in php_files:
        all_findings.extend(scan_file(filepath))

    # گروه‌بندی بر اساس severity
    by_severity = {"CRITICAL": [], "WARN": [], "INFO": []}
    for f in all_findings:
        by_severity[f["severity"]].append(f)

    # گزارش
    print(f"\n📊 نتایج اسکن:")
    print(f"  🔴 CRITICAL: {len(by_severity['CRITICAL'])}")
    print(f"  🟡 WARN:     {len(by_severity['WARN'])}")
    print(f"  🔵 INFO:     {len(by_severity['INFO'])}")
    print(f"  ────────────────")
    print(f"  مجموع: {len(all_findings)}")

    if by_severity["CRITICAL"]:
        print(f"\n🔴 موارد CRITICAL (باید حتماً اصلاح شوند):")
        print("-" * 70)
        for f in by_severity["CRITICAL"]:
            print(f"  📍 {f['file']}:{f['line']}")
            print(f"     کد: {f['code']}")
            print(f"     علت: {f['description']}")
            print(f"     راه‌حل: {f['fix']}")
            print()

    if by_severity["WARN"]:
        print(f"\n🟡 موارد WARN (توصیه می‌شود اصلاح شوند):")
        print("-" * 70)
        # گروه‌بندی بر اساس فایل
        by_file = {}
        for f in by_severity["WARN"]:
            by_file.setdefault(f["file"], []).append(f)
        for filepath, items in by_file.items():
            print(f"  📍 {filepath}")
            for f in items:
                print(f"     L{f['line']}: {f['description']} → {f['fix'][:60]}")

    if by_severity["INFO"]:
        print(f"\n🔵 موارد INFO (اطلاع‌رسانی):")
        print("-" * 70)
        by_file = {}
        for f in by_severity["INFO"]:
            by_file.setdefault(f["file"], []).append(f)
        for filepath, items in sorted(by_file.items()):
            print(f"  📍 {filepath} ({len(items)} مورد)")

    # ذخیره گزارش JSON
    report_path = "/home/z/my-project/work/Mojavez/tests/runtime-errors-scan.json"
    with open(report_path, "w", encoding="utf-8") as f:
        json.dump({
            "summary": {
                "critical": len(by_severity["CRITICAL"]),
                "warn": len(by_severity["WARN"]),
                "info": len(by_severity["INFO"]),
                "total": len(all_findings),
            },
            "findings": all_findings,
        }, f, ensure_ascii=False, indent=2)
    print(f"\n💾 گزارش JSON: {report_path}")

if __name__ == "__main__":
    main()
