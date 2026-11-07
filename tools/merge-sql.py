#!/usr/bin/env python3
"""
ادغام تمام فایل‌های .sql موجود در database/ در یک فایل واحد schema.sql
ترتیب اجرا: schema.sql سپس update-v0.0.2 تا update-v0.0.14 به ترتیب نسخه
"""
import os
import re

DB_DIR = "/home/z/my-project/work/Mojavez/database"
OUT_FILE = os.path.join(DB_DIR, "schema.sql")

# ترتیب اجرای فایل‌ها
ORDER = ["schema.sql"] + [f"update-v0.0.{v}.sql" for v in range(2, 15)]

HEADER = """-- =================================================================
-- سامانهٔ مدیریت ثبت‌نام و پیگیری صدور مجوز صنایع دستی
-- اسکیمای یکپارچهٔ پایگاه داده - نسخهٔ v0.0.5 (ادغام‌شده)
-- MySQL/MariaDB — نصب تازه با اجرای همین یک فایل انجام می‌شود
-- -----------------------------------------------------------------
-- این فایل از ادغام schema.sql و update-v0.0.2 تا update-v0.0.14
-- تولید شده است. ترتیب اجرا برای جلوگیری از تداخل FOREIGN KEY
-- و وابستگی ستون‌ها (ALTER TABLE) به‌دقت حفظ شده است.
-- -----------------------------------------------------------------
-- طراح و توسعه‌دهنده: امین دروگر بزدی (Derogaramin@)
-- =================================================================

"""

DIVIDER = "\n-- ================================================================\n"
DIVIDER += "-- {name}\n"
DIVIDER += "-- ================================================================\n\n"

out = [HEADER]

# حذف USE handicraft_license تکراری از فایل‌های به‌روزرسانی (فقط در فایل اول نگه داشته می‌شود)
seen_use = False

for fname in ORDER:
    fpath = os.path.join(DB_DIR, fname)
    if not os.path.exists(fpath):
        print(f"⚠️  فایل پیدا نشد: {fname}")
        continue

    with open(fpath, "r", encoding="utf-8") as f:
        content = f.read().strip()

    # حذف بنر اولیهٔ schema.sql (در header اصلی آمده)
    if fname == "schema.sql":
        # حذف خطوط تکراری بنر اولیه
        content = re.sub(
            r"-- =+\n-- سامانه مدیریت ثبت‌نام و پیگیری صدور مجوز صنایع دستی\n-- اسکیمای پایگاه داده - MySQL/MariaDB\n-- =+\n+",
            "",
            content,
            count=1,
        )

    # حذف USE handicraft_license های تکراری
    lines = content.split("\n")
    cleaned = []
    for line in lines:
        stripped = line.strip()
        if stripped.upper() == "USE handicraft_license;":
            if not seen_use:
                cleaned.append(line)
                seen_use = True
            continue
        cleaned.append(line)
    content = "\n".join(cleaned).strip()

    out.append(DIVIDER.format(name=fname))
    out.append(content)
    out.append("\n")

final = "\n".join(out)

with open(OUT_FILE, "w", encoding="utf-8") as f:
    f.write(final)

print(f"✅ فایل ادغام‌شده: {OUT_FILE}")
print(f"   حجم: {os.path.getsize(OUT_FILE):,} بایت")
print(f"   خطوط: {final.count(chr(10)):,}")

# حذف فایل‌های update قدیمی
removed = 0
for fname in ORDER[1:]:  # به‌جز schema.sql
    fpath = os.path.join(DB_DIR, fname)
    if os.path.exists(fpath):
        os.remove(fpath)
        removed += 1
        print(f"   🗑️  حذف: {fname}")

print(f"\nفایل‌های حذف‌شده: {removed}")
print(f"فایل‌های باقی‌مانده در database/:")
for f in sorted(os.listdir(DB_DIR)):
    if f.endswith(".sql"):
        size = os.path.getsize(os.path.join(DB_DIR, f))
        print(f"  • {f} ({size:,} بایت)")
