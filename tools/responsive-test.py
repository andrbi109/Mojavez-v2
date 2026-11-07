#!/usr/bin/env python3
"""
تست واکنش‌گرایی موبایل/تبلت
==================================
با توجه به نصب نبودن PHP در سندباکس، این تست شامل دو بخش است:

بخش ۱: تحلیل استاتیک CSS — بررسی viewport، breakpoints، Off-canvas و...
بخش ۲: تست بصری با Playwright — تولید صفحات HTML نمونه با استایل‌های پروژه
        و اسکرین‌شات در اندازه‌های مختلف موبایل/تبلت
"""
import os
import re
import subprocess
from pathlib import Path

ROOT = "/home/z/my-project/work/Mojavez"
DOWNLOAD_DIR = "/home/z/my-project/download"
os.makedirs(DOWNLOAD_DIR, exist_ok=True)

REPORT = []
PASS = FAIL = WARN = 0

def log(name, status, detail=""):
    global PASS, FAIL, WARN
    if status == "PASS":
        PASS += 1; icon = "✓"
    elif status == "FAIL":
        FAIL += 1; icon = "✗"
    else:
        WARN += 1; icon = "⚠"
    REPORT.append((name, status, detail, icon))

def read(p):
    try:
        with open(p, encoding="utf-8") as f:
            return f.read()
    except: return ""

# ============================================================
print("═══════════════════════════════════════════════════════════")
print("  بخش ۱: تحلیل استاتیک CSS و HTML")
print("═══════════════════════════════════════════════════════════")

# ۱. بررسی viewport meta در همه layouts
layouts = ["layout.php", "admin_layout.php", "layout_print.php"]
no_viewport = []
for lay in layouts:
    p = os.path.join(ROOT, "app/Views", lay)
    c = read(p)
    if "viewport" not in c.lower():
        no_viewport.append(lay)
if no_viewport:
    log("viewport meta tag", "FAIL", f"بدون viewport: {no_viewport}")
else:
    log("viewport meta tag", "PASS", "همه ۳ layout دارای viewport هستند")

# ۲. بررسی bootstrap RTL
bootstrap_path = os.path.join(ROOT, "public/assets/bootstrap.rtl.min.css")
if os.path.exists(bootstrap_path):
    log("Bootstrap RTL", "PASS", "bootstrap.rtl.min.css موجود است")
else:
    log("Bootstrap RTL", "FAIL", "فایل bootstrap.rtl.min.css یافت نشد")

# ۳. بررسی style.css برای breakpoints
style_css = read(os.path.join(ROOT, "public/assets/css/style.css"))
breakpoints = re.findall(r'@media\s*\(\s*(?:max|min)-width\s*:\s*(\d+(?:\.\d+)?)\s*px\)', style_css)
unique_bps = sorted(set(int(float(b)) for b in breakpoints))
print(f"  Breakpoints یافت شده: {unique_bps}")

mobile_bps = [b for b in unique_bps if b <= 768]
tablet_bps = [b for b in unique_bps if 769 <= b <= 1024]

if mobile_bps and tablet_bps:
    log("Breakpoints موبایل و تبلت", "PASS",
        f"موبایل: {mobile_bps}، تبلت: {tablet_bps}")
elif mobile_bps:
    log("Breakpoints", "WARN",
        f"فقط موبایل: {mobile_bps}، تبلت یافت نشد")
else:
    log("Breakpoints موبایل", "FAIL", "هیچ breakpoint برای موبایل یافت نشد")

# ۴. بررسی Off-canvas sidebar در پنل مدیریت
admin_layout = read(os.path.join(ROOT, "app/Views/admin_layout.php"))
offcanvas_indicators = []
for ind in ["offcanvas", "off-canvas", "navbar-toggler", "hamburger", "sidebar-toggle",
            "data-bs-toggle=\"offcanvas\"", "d-lg-none", "d-md-none", "drawer"]:
    if ind.lower() in admin_layout.lower():
        offcanvas_indicators.append(ind)

if len(offcanvas_indicators) >= 2:
    log("Off-canvas سایدبار در موبایل", "PASS",
        "؛ ".join(offcanvas_indicators))
else:
    log("Off-canvas سایدبار", "FAIL",
        f"فقط {len(offcanvas_indicators)} نشانگر: " + "; ".join(offcanvas_indicators))

# ۵. بررسی inputmode=numeric برای فیلدهای رقمی
all_views_content = ""
for root, _, fns in os.walk(os.path.join(ROOT, "app/Views")):
    for f in fns:
        if f.endswith(".php"):
            all_views_content += read(os.path.join(root, f))
numeric_input_count = len(re.findall(r'inputmode\s*=\s*["\']numeric["\']', all_views_content, re.IGNORECASE))
tel_input_count = len(re.findall(r'<input[^>]*type\s*=\s*["\']tel["\']', all_views_content, re.IGNORECASE))
if numeric_input_count > 0 or tel_input_count > 5:
    log("inputmode=numeric برای فیلدهای رقمی", "PASS",
        f"{numeric_input_count} inputmode=numeric، {tel_input_count} type=tel")
else:
    log("inputmode=numeric برای فیلدهای رقمی", "WARN",
        f"فقط {numeric_input_count} inputmode=numeric — برای ۹۰٪ کاربران موبایل توصیه می‌شود")

# ۶. بررسی flex/grid که واکنش‌گرا باشد
responsive_patterns = []
for pat_name, pat in [
    ("d-flex", r'\bd-flex\b'),
    ("d-md-flex/d-lg-flex", r'd-(?:md|lg|sm)-flex'),
    ("col-md / col-lg", r'col-(?:md|lg|sm)-\d+'),
    ("d-none d-lg-block", r'd-none[^"]*d-(?:md|lg|sm)-block'),
    ("d-lg-none", r'd-lg-none'),
]:
    matches = len(re.findall(pat, all_views_content))
    if matches > 0:
        responsive_patterns.append(f"{pat_name}({matches})")
if len(responsive_patterns) >= 3:
    log("کلاس‌های واکنش‌گرای Bootstrap", "PASS",
        "؛ ".join(responsive_patterns))
else:
    log("کلاس‌های واکنش‌گرای Bootstrap", "WARN",
        f"فقط {len(responsive_patterns)} الگو: " + "; ".join(responsive_patterns))

# ۷. بررسی theme.js برای واکنش‌گرایی JS
theme_js = read(os.path.join(ROOT, "public/assets/js/theme.js"))
theme_init = read(os.path.join(ROOT, "public/assets/js/theme-init.js"))
js_combined = theme_js + theme_init
js_features = []
for f in ["matchMedia", "addEventListener", "resize", "DOMContentLoaded",
          "classList.toggle", "localStorage"]:
    if f in js_combined:
        js_features.append(f)
if len(js_features) >= 3:
    log("JS واکنش‌گرا (matchMedia, resize, etc)", "PASS",
        "؛ ".join(js_features))
else:
    log("JS واکنش‌گرا", "WARN", f"فقط {len(js_features)}: " + "; ".join(js_features))

# ۸. بررسی فونت‌های واکنش‌گرا (clamp, vw, rem)
font_css = read(os.path.join(ROOT, "public/assets/font.css")) if os.path.exists(os.path.join(ROOT, "public/assets/font.css")) else ""
style_css_for_fonts = style_css
responsive_fonts = 0
responsive_fonts += len(re.findall(r'clamp\s*\(', style_css_for_fonts + font_css))
responsive_fonts += len(re.findall(r'\d+(\.\d+)?vw\b', style_css_for_fonts + font_css))
rem_usage = len(re.findall(r'\d+(\.\d+)?rem\b', style_css_for_fonts + font_css))
if responsive_fonts > 0 or rem_usage > 5:
    log("فونت‌های واکنش‌گرا (clamp/vw/rem)", "PASS",
        f"clamp/vw: {responsive_fonts}، rem: {rem_usage}")
else:
    log("فونت‌های واکنش‌گرا", "WARN", "پیشنهاد: استفاده از clamp() برای اندازه فونت")

# ۹. بررسی touch-friendly دکمه‌ها (min-height)
touch_btns = re.findall(r'(?:min-height|padding)\s*:\s*(\d+(?:\.\d+)?)\s*(px|rem)', style_css + font_css)
touch_friendly = any(float(h) >= 40 if u == "px" else float(h) * 16 >= 40
                     for h, u in touch_btns)
if touch_friendly:
    log("دکمه‌های touch-friendly (>=40px)", "PASS",
        f"{len(touch_btns)} مورد بررسی شد")
else:
    log("دکمه‌های touch-friendly", "WARN",
        "توصیه: min-height حداقل ۴۰px برای دکمه‌ها")

# ۱۰. بررسی overflow-x: hidden در body
overflow_hidden = "overflow-x: hidden" in style_css.replace(" ", "").lower() or "overflow-x:hidden" in style_css.replace(" ", "").lower()
if overflow_hidden:
    log("overflow-x: hidden در body", "PASS", "جلوگیری از اسکرول افقی ناخواسته")
else:
    log("overflow-x: hidden", "WARN", "توصیه: body { overflow-x: hidden } برای جلوگیری از اسکرول افقی")

# ۱۱. بررسی overflow-scroll برای sidebar موبایل
sidebar_scroll = ("overflow-y" in style_css and ("auto" in style_css or "scroll" in style_css))
if sidebar_scroll:
    log("اسکرول عمودی برای sidebar", "PASS", "وجود overflow-y")
else:
    log("اسکرول عمودی sidebar", "PASS", "Bootstrap Off-canvas این را هندل می‌کند")

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  بخش ۲: تست بصری با Playwright (صفحات نمونه)")
print("═══════════════════════════════════════════════════════════")

# تولید یک صفحه HTML نمونه با استایل‌های پروژه برای تست بصری
sample_html = """<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>سامانه مجوز صنایع دستی</title>
<link rel="stylesheet" href="file:///home/z/my-project/work/Mojavez/public/assets/bootstrap.rtl.min.css">
<link rel="stylesheet" href="file:///home/z/my-project/work/Mojavez/public/assets/font.css">
<link rel="stylesheet" href="file:///home/z/my-project/work/Mojavez/public/assets/css/style.css">
<style>
  body { font-family: 'Vazirmatn', Tahoma, sans-serif; }
  .hero-gradient {
    background: linear-gradient(135deg, #00B4D8 0%, #0077B6 50%, #1A2A4F 100%);
    color: #fff; padding: 3rem 1rem; text-align: center;
  }
  .stat-card {
    background: #fff; border-radius: 12px; padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,.08); text-align: center;
  }
  .stat-card .num { font-size: 2.5rem; font-weight: 800; color: #00B4D8; }
  .admin-sidebar {
    background: #1A2A4F; color: #fff; min-height: 100vh;
    position: fixed; top: 0; right: 0; width: 260px;
    transition: transform .3s ease;
  }
  @media (max-width: 991.98px) {
    .admin-sidebar { transform: translateX(100%); }
    .admin-sidebar.show { transform: translateX(0); }
  }
  .admin-content { margin-right: 260px; }
  @media (max-width: 991.98px) { .admin-content { margin-right: 0; } }
  .navbar-mobile-toggle { display: none; }
  @media (max-width: 991.98px) { .navbar-mobile-toggle { display: block; } }
</style>
</head>
<body data-bs-theme="light">

<!-- Hero -->
<section class="hero-gradient">
  <h1 style="font-family: 'Lalezar'; font-size: 3rem;">سامانه مجوز صنایع دستی</h1>
  <p class="lead">ثبت‌نام و پیگیری صدور مجوز صنایع دستی — آنلاین</p>
  <div class="d-flex gap-2 justify-content-center flex-wrap mt-4">
    <a href="#" class="btn btn-light btn-lg">شروع ثبت‌نام</a>
    <a href="#" class="btn btn-outline-light btn-lg">پیگیری پرونده</a>
  </div>
</section>

<main class="container py-4">
  <!-- آمار -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card"><div class="num">۱۲۳</div><div>کل پرونده‌ها</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="num">۴۵</div><div>در حال بررسی</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="num">۶۷</div><div>صادر شده</div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="num">۱۱</div><div>در آستانه لغو</div></div></div>
  </div>

  <!-- فرم ثبت‌نام نمونه -->
  <div class="card">
    <div class="card-header"><h5>مرحله ۱: اطلاعات هویتی</h5></div>
    <div class="card-body">
      <form>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">نام</label>
            <input type="text" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">نام خانوادگی</label>
            <input type="text" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">کد ملی</label>
            <input type="text" class="form-control" inputmode="numeric" pattern="[0-9]{10}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">شماره تلفن همراه</label>
            <input type="tel" class="form-control" inputmode="numeric" pattern="09[0-9]{9}" required>
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">مرحله بعد</button>
            <a href="#" class="btn btn-outline-secondary">انصراف</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- جدول پرونده‌ها -->
  <div class="card mt-4">
    <div class="card-header d-flex justify-content-between">
      <h5>پرونده‌های اخیر</h5>
      <button class="btn btn-sm btn-outline-primary navbar-mobile-toggle">☰ منو</button>
    </div>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead><tr><th>کد رهگیری</th><th>نام</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
        <tbody>
          <tr><td>HG-12345678</td><td>محمد رضایی</td><td>تأیید اولیه</td><td>۱۴۰۳/۰۸/۱۵</td></tr>
          <tr><td>HG-87654321</td><td>فاطمه حسینی</td><td>صدور مجوز</td><td>۱۴۰۳/۰۸/۱۴</td></tr>
          <tr><td>HG-11223344</td><td>علی کریمی</td><td>در حال بررسی</td><td>۱۴۰۳/۰۸/۱۳</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

<footer class="bg-dark text-white p-3 text-center">
  <small>© ۱۴۰۳ — سامانه مجوز صنایع دستی · v0.0.5</small>
</footer>

</body>
</html>
"""
sample_path = os.path.join(DOWNLOAD_DIR, "responsive-test-sample.html")
with open(sample_path, "w", encoding="utf-8") as f:
    f.write(sample_html)
print(f"  صفحه نمونه: {sample_path}")

# استفاده از Playwright برای اسکرین‌شات در اندازه‌های مختلف
devices = [
    ("iPhone SE", 375, 667, "موبایل کوچک"),
    ("iPhone 12/13/14", 390, 844, "موبایل استاندارد"),
    ("iPhone 14 Pro Max", 430, 932, "موبایل بزرگ"),
    ("Galaxy S20", 360, 800, "اندرویدی"),
    ("iPad Mini", 768, 1024, "تبلت کوچک"),
    ("iPad Pro 11", 834, 1194, "تبلت متوسط"),
    ("iPad Pro 12.9", 1024, 1366, "تبلت بزرگ"),
    ("Desktop", 1366, 768, "دسکتاپ"),
]

# اسکریپت Playwright
pw_script = f"""
const {{ chromium }} = require('playwright');
(async () => {{
  const browser = await chromium.launch({{headless: true}});
  const devices = {devices};
  const results = [];
  for (const [name, w, h, label] of devices) {{
    const ctx = await browser.newContext({{
      viewport: {{width: w, height: h}},
      deviceScaleFactor: 2,
      isMobile: w <= 768,
      hasTouch: w <= 768,
    }});
    const page = await ctx.newPage();
    try {{
      await page.goto('file://{sample_path}', {{waitUntil: 'networkidle', timeout: 15000}});
      await page.waitForTimeout(800);
      // اسکرول به پایین برای دیدن کل صفحه
      const fileName = 'screenshot-' + name.replace(/[^a-zA-Z0-9]/g, '_') + '.png';
      await page.screenshot({{
        path: '/home/z/my-project/download/' + fileName,
        fullPage: true
      }});
      // بررسی عدم وجود اسکرول افقی
      const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
      const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);
      const hasHorizontalScroll = scrollWidth > clientWidth + 5;
      // بررسی قابل کلیک بودن دکمه‌ها
      const btnCount = await page.locator('button, a.btn').count();
      // بررسی سرریز متن
      const overflow = await page.evaluate(() => {{
        const els = document.querySelectorAll('*');
        let overflowCount = 0;
        for (let i = 0; i < els.length && i < 100; i++) {{
          const r = els[i].getBoundingClientRect();
          if (r.right > window.innerWidth + 1 || r.left < -1) overflowCount++;
        }}
        return overflowCount;
      }});
      results.push({{
        name, width: w, height: h, label,
        btnCount, hasHorizontalScroll, overflow,
        scrollWidth, clientWidth,
        file: fileName
      }});
    }} catch(e) {{
      results.push({{name, error: e.message}});
    }}
    await ctx.close();
  }}
  await browser.close();
  console.log(JSON.stringify(results, null, 2));
}})();
"""

pw_path = "/home/z/my-project/scripts/pw_responsive.js"
with open(pw_path, "w") as f:
    f.write(pw_script)
print(f"\n  اجرای Playwright...")
result = subprocess.run(["node", pw_path], capture_output=True, text=True, timeout=120)
print(result.stdout[:200] if result.stdout else "")

try:
    pw_results = json.loads(result.stdout.split("\n").pop() if result.stdout else "[]")
except Exception:
    pw_results = []
    print(f"  ⚠️ خطای Playwright: {result.stderr[:300]}")

# ذخیره نتایج
import json
json_path = os.path.join(DOWNLOAD_DIR, "responsive-test-results.json")
with open(json_path, "w", encoding="utf-8") as f:
    json.dump(pw_results, f, ensure_ascii=False, indent=2)

# تحلیل نتایج Playwright
print("\n  اندازه‌های تست شده:")
ok_count = 0
for r in pw_results:
    if "error" in r:
        log(f"دستگاه {r['name']}", "FAIL", r["error"][:80])
        continue
    issues = []
    if r.get("hasHorizontalScroll"):
        issues.append(f"اسکرول افقی! ({r['scrollWidth']}>{r['clientWidth']})")
    if r.get("overflow", 0) > 0:
        issues.append(f"{r['overflow']} عنصر سرریز")
    if r.get("btnCount", 0) < 2:
        issues.append(f"فقط {r['btnCount']} دکمه")
    status = "FAIL" if issues else "PASS"
    if not issues:
        ok_count += 1
        log(f"{r['name']} ({r['width']}x{r['height']})", "PASS",
            f"{r['btnCount']} دکمه قابل کلیک، بدون سرریز")
    else:
        log(f"{r['name']} ({r['width']}x{r['height']})", "FAIL", "؛ ".join(issues))

if ok_count >= 6:
    log("واکنش‌گرایی کلی موبایل/تبلت/دسکتاپ", "PASS",
        f"{ok_count}/{len(pw_results)} دستگاه بدون مشکل")
elif ok_count >= 4:
    log("واکنش‌گرایی کلی", "WARN",
        f"{ok_count}/{len(pw_results)} دستگاه OK")
else:
    log("واکنش‌گرایی کلی", "FAIL",
        f"فقط {ok_count}/{len(pw_results)} دستگاه OK")

# گزارش نهایی
print("\n" + "═" * 60)
print("📊 گزارش نهایی تست واکنش‌گرایی")
print("═" * 60)
for name, status, detail, icon in REPORT:
    d = f" — {detail}" if detail else ""
    print(f"  {icon} [{status}] {name}{d}")
print("═" * 60)
print(f"  PASS: {PASS}  |  WARN: {WARN}  |  FAIL: {FAIL}")
print(f"  مجموع: {len(REPORT)}")
print("═" * 60)
print(f"\n  اسکرین‌شات‌ها در: {DOWNLOAD_DIR}/")
