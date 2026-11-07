#!/usr/bin/env python3
# ساخت پیش‌نمایش خودبسنده (self-contained) از صفحات رندرشدهٔ سامانه
import subprocess, re, base64, os

B = "http://127.0.0.1:8000"
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ASSETS = os.path.join(ROOT, "public", "assets")

def fetch(path, cookie=None):
    cmd = ["curl", "-s", B + path]
    if cookie:
        cmd += ["-b", cookie]
    return subprocess.run(cmd, capture_output=True, text=True).stdout

# فونت‌ها به data URI
weights = {"Vazirmatn-Regular": "400", "Vazirmatn-Medium": "500", "Vazirmatn-Bold": "700"}
font_css = ""
for name, w in weights.items():
    fp = os.path.join(ASSETS, "font", "VazirMatn", name + ".woff2")
    if os.path.exists(fp):
        b64 = base64.b64encode(open(fp, "rb").read()).decode()
        font_css += (f"@font-face{{font-family:'Vazirmatn';src:url(data:font/woff2;base64,{b64}) "
                     f"format('woff2');font-weight:{w};font-display:swap;}}\n")
# فونت تزئینی لاله‌زار برای تیترها
lal = os.path.join(ASSETS, "font", "Lalezar", "Lalezar-Regular.ttf")
if os.path.exists(lal):
    b64 = base64.b64encode(open(lal, "rb").read()).decode()
    font_css += (f"@font-face{{font-family:'Lalezar';src:url(data:font/ttf;base64,{b64}) "
                 f"format('truetype');font-weight:400;font-display:swap;}}\n")

bootstrap_css = open(os.path.join(ASSETS, "bootstrap.rtl.min.css"), encoding="utf-8").read()
style_css = open(os.path.join(ASSETS, "css", "style.css"), encoding="utf-8").read()
INLINE_HEAD = f"<style>{font_css}</style><style>{bootstrap_css}</style><style>{style_css}</style>"

def make_selfcontained(html):
    html = re.sub(r'<link rel="stylesheet" href="[^"]*">', '', html)
    html = html.replace("</head>", INLINE_HEAD + "</head>")
    html = re.sub(r'<script src="[^"]*"></script>', '', html)
    return html

# ورود مدیر برای صفحات پنل
subprocess.run(["curl", "-s", "-c", "/tmp/prev.txt", "-o", "/tmp/prev_login.html", B + "/admin/login"], capture_output=True)
m = re.search(r'[a-f0-9]{64}', open("/tmp/prev_login.html").read())
if m:
    subprocess.run(["curl", "-s", "-c", "/tmp/prev.txt", "-b", "/tmp/prev.txt", "-o", "/dev/null",
                    "-d", f"username=admin&password=admin123&_csrf={m.group(0)}", B + "/admin/login"], capture_output=True)

pages = [
    ("home", "صفحهٔ اصلی", "/", None),
    ("selectservice", "انتخاب نوع خدمت", "/register", None),
    ("register1", "ثبت‌نام — مرحلهٔ ۱", "/register/step/1", None),
    ("myrecords", "سوابق من (احراز هویت)", "/my-records", None),
    ("tracking", "جستجوی پیگیری", "/tracking", None),
    ("guide", "راهنمای جامع", "/guide", None),
    ("about", "دربارهٔ ما", "/about", None),
    ("login", "ورود مدیر", "/admin/login", None),
    ("dashboard", "داشبورد مدیر", "/admin/dashboard", "/tmp/prev.txt"),
    ("applications", "فهرست درخواست‌ها", "/admin/applications", "/tmp/prev.txt"),
    ("formfields", "فرم‌ساز پویا", "/admin/form-fields", "/tmp/prev.txt"),
    ("licenses", "مدیریت رشته‌ها", "/admin/license-titles", "/tmp/prev.txt"),
    ("settings", "تنظیمات سامانه", "/admin/settings", "/tmp/prev.txt"),
    ("postal", "درخواست‌های کد پستی", "/admin/postal-requests", "/tmp/prev.txt"),
    ("payments", "درآمد و پرداخت‌ها", "/admin/payments", "/tmp/prev.txt"),
    ("sms", "پیامک‌های وضعیت", "/admin/sms", "/tmp/prev.txt"),
    ("revisions", "بازبینی پرونده‌ها", "/admin/revisions", "/tmp/prev.txt"),
    ("wizardsteps", "مراحل ثبت‌نام", "/admin/wizard-steps", "/tmp/prev.txt"),
    ("servicetypes", "انواع خدمات", "/admin/service-types", "/tmp/prev.txt"),
    ("licensereq", "درخواست‌های تصویر مجوز", "/admin/license-requests", "/tmp/prev.txt"),
]

# کد رهگیری نمونه برای صفحهٔ وضعیت و چاپ
tc = subprocess.run(["mysql", "-N", "handicraft_license", "-e",
                     "SELECT tracking_code FROM applicants ORDER BY id DESC LIMIT 1"],
                    capture_output=True, text=True).stdout.strip()
if tc:
    aid = subprocess.run(["mysql", "-N", "handicraft_license", "-e",
                          f"SELECT id FROM applicants WHERE tracking_code='{tc}'"],
                         capture_output=True, text=True).stdout.strip()
    if aid:
        pages.append(("detail", "جزئیات پرونده (پنل)", f"/admin/view/{aid}", "/tmp/prev.txt"))
    pages.append(("status", f"وضعیت پرونده ({tc})", f"/tracking/status/{tc}", None))
    pages.append(("success", "صفحهٔ موفقیت + قالب پیام", f"/register/success/{tc}", None))
    pages.append(("print", "نسخهٔ چاپی پرونده", f"/tracking/print/{tc}", None))

out = []
for key, title, path, cookie in pages:
    html = fetch(path, cookie)
    # استخراج محتوای body (بدون تکثیر CSS در هر صفحه)
    m = re.search(r'<body[^>]*class="([^"]*)"[^>]*>(.*)</body>', html, re.DOTALL)
    if m:
        body_class, body = m.group(1), m.group(2)
    else:
        m = re.search(r'<body[^>]*>(.*)</body>', html, re.DOTALL)
        body_class, body = "", (m.group(1) if m else html)
    # حذف اسکریپت‌های خارجی (بوت‌استرپ در پیش‌نمایش لازم نیست)
    body = re.sub(r'<script src="[^"]*"></script>', '', body)
    out.append((key, title, body_class, body))

nav = ""
sections = ""
for i, (key, title, body_class, body) in enumerate(out):
    active = "active" if i == 0 else ""
    nav += f'<button class="tab-btn {active}" onclick="showTab(\'{key}\')" id="btn-{key}">{title}</button>\n'
    sections += (f'<div class="tab-page {active}" id="page-{key}">'
                 f'<div class="screen {body_class}">{body}</div></div>\n')

combined = f'''<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>پیش‌نمایش رابط کاربری سامانهٔ مجوز صنایع دستی</title>
<style>{font_css}</style>
<style>{bootstrap_css}</style>
<style>{style_css}</style>
<style>
body{{font-family:Vazirmatn,Tahoma,sans-serif;background:#0f172a;margin:0;padding:1rem;color:#fff}}
h1{{font-size:1.15rem;text-align:center;margin:.5rem 0 .3rem}}
.tabs{{display:flex;flex-wrap:wrap;gap:.4rem;justify-content:center;margin-bottom:1rem}}
.tab-btn{{font-family:inherit;background:#1e293b;color:#cbd5e1;border:1px solid #334155;padding:.45rem .9rem;border-radius:8px;cursor:pointer;font-size:.85rem}}
.tab-btn.active{{background:#00B4D8;color:#fff;border-color:#00B4D8}}
.tab-page{{display:none}} .tab-page.active{{display:block}}
.note{{text-align:center;color:#94a3b8;font-size:.8rem;margin-bottom:1rem}}
.screen{{background:#F0F2F5;border-radius:12px;overflow:hidden;min-height:600px;color:#1C1C2A}}
.screen.admin-body{{background:#F0F2F5}}
</style></head><body>
<h1>🏛️ پیش‌نمایش رابط کاربری سامانهٔ مجوز صنایع دستی — نسخهٔ v0.3.0</h1>
<p class="note">
  هر تب یک صفحهٔ واقعیِ رندرشده توسط سرور PHP است (فونت وزیرمتن + لاله‌زار، آیکون‌های SVG)
  &nbsp;•&nbsp; <button id="themeBtn" class="tab-btn" type="button">🌗 تغییر تم روز/شب</button>
</p>
<div class="tabs">{nav}</div>
{sections}
<script>
document.addEventListener('DOMContentLoaded', function () {{
    const saved = localStorage.getItem('preview-theme');
    if (saved === 'dark') document.documentElement.setAttribute('data-bs-theme', 'dark');
    document.getElementById('themeBtn').addEventListener('click', function () {{
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        if (dark) {{ document.documentElement.removeAttribute('data-bs-theme'); localStorage.setItem('preview-theme', 'light'); }}
        else {{ document.documentElement.setAttribute('data-bs-theme', 'dark'); localStorage.setItem('preview-theme', 'dark'); }}
    }});
}});
function showTab(k){{
  document.querySelectorAll('.tab-page').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('page-'+k).classList.add('active');
  document.getElementById('btn-'+k).classList.add('active');
}}
</script>
</body></html>'''

dest = os.path.join(os.path.dirname(ROOT), "ui-preview.html")
open(dest, "w", encoding="utf-8").write(combined)
print("written:", dest, os.path.getsize(dest), "bytes")
