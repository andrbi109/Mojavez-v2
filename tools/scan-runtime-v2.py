#!/usr/bin/env python3
"""
اسکنر پیشرفته خطاهای runtime PHP
===================================
تمرکز: خطاهایی که واقعاً در حین اجرا نمایش داده می‌شوند:
- Notice: Undefined variable
- Notice: Undefined index / array key (PHP 8)
- Warning: Trying to access array offset on null
- Warning: Undefined constant
- Warning: A non-numeric value encountered
- TypeError: روش‌های رایج
"""
import os
import re

ROOT = "/home/z/my-project/work/Mojavez"

PATTERNS = [
    # === Undefined array key (PHP 8+) ===
    # دسترسی به $_POST/$_GET بدون ?? یا isset
    (
        r'\$_(?:POST|GET)\[\'\w+\'\](?!\s*(?:\?\?|\)|\]\s*\?\?|\s*\]\s*\)))',
        "HIGH",
        "دسترسی به $_POST/$_GET بدون null coalescing",
        "افزودن ?? '' یا isset()"
    ),
    # دسترسی به $_FILES بدون بررسی
    (
        r'\$_FILES\[\'\w+\'\]\[\'\w+\'\](?!\s*(?:\?\?|\)|\]))',
        "HIGH",
        "دسترسی به $_FILES بدون null coalescing",
        "افزودن ?? null یا بررسی isset"
    ),

    # === Trying to access array offset on null ===
    (
        r'\$\w+\[\'\w+\'\]\[\'\w+\'\](?!\s*(?:\?\?|\)|\]))',
        "MEDIUM",
        "احتمال دسترسی به اندیس روی null (چندسطحی)",
        "استفاده از ?? یا optional chaining با isset()"
    ),

    # === A non-numeric value encountered ===
    (
        r'\(\d+\s*\*\s*\$',
        "INFO",
        "ضرب عدد در متغیر احتمالاً غیرعددی",
        "cast به (int) یا (float)"
    ),

    # === Division by zero ===
    (
        r'/\s*\$(?!cnt|count|total|n|num|size|len|divisor|i|j|k)',
        "LOW",
        "احتمال تقسیم بر صفر اگر متغیر 0 باشد",
        "بررسی قبل از تقسیم: if ($denominator > 0)"
    ),
]


def scan_file(filepath):
    findings = []
    try:
        with open(filepath, encoding="utf-8") as f:
            content = f.read()
    except Exception:
        return findings
    if "/assets/" in filepath or "/vendor/" in filepath:
        return findings

    lines = content.split("\n")
    for line_no, line in enumerate(lines, 1):
        if line.lstrip().startswith("*") or line.lstrip().startswith("//"):
            continue
        # حذف کامنت‌ها
        stripped = re.sub(r'//.*$', '', line)
        stripped = re.sub(r'#.*$', '', stripped)

        for pattern, severity, desc, fix in PATTERNS:
            if re.search(pattern, stripped):
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
    for fp in php_files:
        all_findings.extend(scan_file(fp))

    by_severity = {"HIGH": [], "MEDIUM": [], "LOW": [], "INFO": []}
    for f in all_findings:
        by_severity[f["severity"]].append(f)

    print(f"\n📊 نتایج اسکن پیشرفته:")
    print(f"  🔴 HIGH:   {len(by_severity['HIGH'])}")
    print(f"  🟠 MEDIUM: {len(by_severity['MEDIUM'])}")
    print(f"  🔵 LOW:    {len(by_severity['LOW'])}")
    print(f"  ⚪ INFO:   {len(by_severity['INFO'])}")
    print(f"  ────────────────")
    print(f"  مجموع: {len(all_findings)}")

    if by_severity["HIGH"]:
        print(f"\n🔴 موارد HIGH (احتمال بالای خطای runtime):")
        print("-" * 70)
        seen = set()
        for f in by_severity["HIGH"]:
            key = (f["file"], f["line"])
            if key in seen:
                continue
            seen.add(key)
            print(f"  📍 {f['file']}:L{f['line']}")
            print(f"     {f['code']}")
            print(f"     علت: {f['description']}")
            print(f"     راه‌حل: {f['fix']}")
            print()
            if len(seen) >= 15:
                break

    if by_severity["MEDIUM"]:
        print(f"\n🟠 موارد MEDIUM (احتمال متوسط):")
        print("-" * 70)
        seen = set()
        for f in by_severity["MEDIUM"]:
            key = (f["file"], f["line"])
            if key in seen:
                continue
            seen.add(key)
            print(f"  📍 {f['file']}:L{f['line']} → {f['code'][:80]}")
            print(f"     علت: {f['description']}")
            if len(seen) >= 10:
                break


if __name__ == "__main__":
    main()
