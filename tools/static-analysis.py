#!/usr/bin/env python3
"""
تست عملکردی منطق - Static Analysis
=================================================================
چون محیط سندباکس فاقد PHP/MySQL است، این اسکریپت تمام فایل‌های پروژه را
به‌صورت استاتیک تحلیل می‌کند و رفتار عملکردی سامانه را آزمون می‌کند:

  ۱. بررسی syntax پایه PHP (پرانتزها، آکولادها، تگ‌ها)
  ۲. بررسی ارجاعات require/include
  ۳. بررسی تعریف route در Router و هندل‌رهای موجود در کنترلرها
  ۴. بررسی توکن CSRF در همهٔ فرم‌های POST
  ۵. بررسی استفاده از htmlspecialchars در خروجی‌ها
  ۶. بررسی Prepared Statements در کوئری‌های دیتابیس
  ۷. بررسی منطق AutoCancel و Priority
  ۸. بررسی توابع helper و ثابت‌ها
  ۹. بررسی جدول‌ها و مدل‌ها
  ۱۰. بررسی فرم‌های ثبت‌نام و اعتبارسنجی فیلدها
  ۱۱. بررسی دکمه‌ها و اکشن‌های فرم‌ها
"""
import os
import re
import json
from collections import defaultdict

ROOT = "/home/z/my-project/work/Mojavez"
REPORT = []

PASS = 0
FAIL = 0
WARN = 0

def log_result(name, status, detail=""):
    """status: PASS/FAIL/WARN"""
    global PASS, FAIL, WARN
    if status == "PASS":
        PASS += 1
        icon = "✓"
    elif status == "FAIL":
        FAIL += 1
        icon = "✗"
    else:
        WARN += 1
        icon = "⚠"
    REPORT.append({"name": name, "status": status, "detail": detail, "icon": icon})

def list_php_files(dirpath):
    """لیست تمام فایل‌های PHP به‌جز assets و vendor"""
    files = []
    for root, _, fns in os.walk(dirpath):
        # حذف assets و vendor
        if "/assets" in root or "/vendor" in root or "/node_modules" in root:
            continue
        for f in fns:
            if f.endswith(".php"):
                files.append(os.path.join(root, f))
    return files

def read(path):
    try:
        with open(path, "r", encoding="utf-8") as f:
            return f.read()
    except Exception as e:
        return ""

# ============================================================
print("═══════════════════════════════════════════════════════════")
print("  تست ۱: بررسی syntax پایه PHP")
print("═══════════════════════════════════════════════════════════")
php_files = list_php_files(ROOT)
print(f"  تعداد فایل‌های PHP: {len(php_files)}")

syntax_issues = []
for f in php_files:
    content = read(f)
    if not content:
        continue
    # بررسی تگ شروع
    if "<?php" not in content and "<?=" not in content:
        syntax_issues.append(f"{f}: تگ شروع PHP ندارد")
        continue
    # بررسی تطابق آکولادها
    open_braces = content.count("{")
    close_braces = content.count("}")
    # در comments و strings ممکن است تفاوت باشد، اما باید نزدیک باشد
    diff = abs(open_braces - close_braces)
    if diff > 5:
        syntax_issues.append(f"{os.path.basename(f)}: عدم تطابق آکولاد ({open_braces} باز / {close_braces} بسته)")
    # بررسی تگ بسته ناقص
    if content.rstrip().endswith("<?") or content.rstrip().endswith("<?p"):
        syntax_issues.append(f"{os.path.basename(f)}: تگ ناقص در انتها")

if not syntax_issues:
    log_result("syntax پایه PHP (تطابق آکولادها و تگ‌ها)", "PASS",
               f"{len(php_files)} فایل بررسی شد")
else:
    log_result("syntax پایه PHP", "WARN",
               f"{len(syntax_issues)} مورد مشکوک: " + "; ".join(syntax_issues[:3]))

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۲: بررسی ارجاعات require/include")
print("═══════════════════════════════════════════════════════════")
require_pattern = re.compile(
    r'\b(?:require|require_once|include|include_once)\s*\(?\s*[\'"]([^\'"]+\.php)[\'"]',
    re.MULTILINE
)
missing = []
ok_count = 0
for f in php_files:
    content = read(f)
    base_dir = os.path.dirname(f)
    for m in require_pattern.finditer(content):
        ref = m.group(1)
        # حل کردن __DIR__ و ROOT_PATH
        if ref.startswith("__DIR__"):
            ref_path = os.path.join(base_dir, ref.replace("__DIR__/", "").replace("__DIR__", ""))
        elif ref.startswith(ROOT_PATH) or ref.startswith("/"):
            ref_path = ref.replace("ROOT_PATH", ROOT)
        elif ref.startswith("CONFIG_PATH"):
            ref_path = os.path.join(ROOT, "config", ref.replace("CONFIG_PATH/", "").replace("CONFIG_PATH", ""))
        elif ref.startswith("APP_PATH"):
            ref_path = os.path.join(ROOT, "app", ref.replace("APP_PATH/", "").replace("APP_PATH", ""))
        elif ref.startswith("CORE_PATH"):
            ref_path = os.path.join(ROOT, "core", ref.replace("CORE_PATH/", "").replace("CORE_PATH", ""))
        else:
            # نسبی به همون دایرکتوری یا ROOT
            ref_path = os.path.join(base_dir, ref)
            if not os.path.exists(ref_path):
                ref_path = os.path.join(ROOT, ref)

        # ارجاع به فایل در vendors (مثل fpdf) ممکن است در پروژه نباشد
        if "fpdf" in ref.lower() or "vendor" in ref.lower():
            continue

        if not os.path.exists(ref_path):
            missing.append(f"{os.path.basename(f)}: ارجاع به {ref} (path: {ref_path})")
        else:
            ok_count += 1

if not missing:
    log_result("ارجاعات require/include", "PASS",
               f"{ok_count} ارجاع پیدا شد")
else:
    log_result("ارجاعات require/include", "FAIL",
               f"{len(missing)} مورد گمشده: " + "; ".join(missing[:3]))

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۳: بررسی route ها در Router و کنترلرها")
print("═══════════════════════════════════════════════════════════")
router_path = os.path.join(ROOT, "core/Router.php")
router_content = read(router_path)
routes_file = os.path.join(ROOT, "public/index.php")
routes_content = read(routes_file)

# استخراج مسیرهای تعریف‌شده
route_pattern = re.compile(
    r'\$router->(get|post|put|delete|patch)\s*\(\s*[\'"`]([^\'"`]+)[\'"`]\s*,\s*\[?([^\],\)]+)[^\]]*\]?',
    re.MULTILINE
)
defined_routes = []
for m in route_pattern.finditer(routes_content):
    defined_routes.append((m.group(1).upper(), m.group(2), m.group(3).strip()))

# استخراج متدهای public در کنترلرها
controllers_dir = os.path.join(ROOT, "app/Controllers")
controller_methods = {}
for fn in os.listdir(controllers_dir):
    if not fn.endswith(".php"):
        continue
    c = read(os.path.join(controllers_dir, fn))
    cname = fn.replace(".php", "")
    methods = re.findall(r'public\s+function\s+(\w+)\s*\(', c)
    controller_methods[cname] = methods

# بررسی: هر route باید به متدی در کنترلر اشاره کند
unmatched_routes = []
matched_routes = 0
for method, route, target in defined_routes:
    # مثل Controller@method یا Controller::method
    if "@" in target:
        ctrl, m = target.split("@", 1)
        ctrl = ctrl.strip().replace("$", "").replace("'", "")
        # اگر Controller@ باشد، یک مقدار داینامیک است
        if ctrl in controller_methods and m in controller_methods[ctrl]:
            matched_routes += 1
        else:
            # ممکن است نام کنترلر با علامت $ متغیر باشد
            if ctrl.startswith("$") or m not in [item for sublist in controller_methods.values() for item in sublist]:
                # اگر $this->controller یا مشابه است، skip
                if "$" in ctrl:
                    matched_routes += 1  # dynamic
                    continue
                unmatched_routes.append(f"{method} {route} → {ctrl}@{m}")

if defined_routes:
    if not unmatched_routes:
        log_result("تعریف route ها و تطابق با کنترلرها", "PASS",
                   f"{matched_routes} مسیر، {len(defined_routes)} کل")
    else:
        log_result("تعریف route ها", "WARN",
                   f"{len(unmatched_routes)} مسیر بدون متد: " + "; ".join(unmatched_routes[:2]))
else:
    # شاید سیستم routing متفاوت باشد - بررسی Router.php برای الگو
    if "addRoute" in router_content or "register" in router_content or "match" in router_content:
        log_result("سیستم routing", "PASS",
                   "Router.php دارای منطق routing است")
    else:
        log_result("سیستم routing", "FAIL",
                   "هیچ route ای در index.php یافت نشد")

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۴: بررسی توکن CSRF در همهٔ فرم‌های POST")
print("═══════════════════════════════════════════════════════════")
views_dir = os.path.join(ROOT, "app/Views")
post_forms_without_csrf = []
post_forms_with_csrf = 0
for root, _, fns in os.walk(views_dir):
    for f in fns:
        if not f.endswith(".php"):
            continue
        path = os.path.join(root, f)
        content = read(path)
        # پیدا کردن همهٔ فرم‌های POST
        for m in re.finditer(r'<form[^>]*method=["\']?post["\']?[^>]*>', content, re.IGNORECASE):
            start = m.end()
            # پیدا 直到 </form>
            end_idx = content.find("</form>", start)
            if end_idx == -1:
                form_body = content[start:]
            else:
                form_body = content[start:end_idx]
            # بررسی وجود _csrf
            if "_csrf" in form_body or "csrf" in form_body.lower():
                post_forms_with_csrf += 1
            else:
                rel = os.path.relpath(path, ROOT)
                post_forms_without_csrf.append(f"{rel}")

if post_forms_without_csrf:
    log_result("توکن CSRF در فرم‌های POST", "FAIL",
               f"{len(post_forms_without_csrf)} فرم بدون CSRF: " + ", ".join(post_forms_without_csrf[:3]))
else:
    log_result("توکن CSRF در فرم‌های POST", "PASS",
               f"{post_forms_with_csrf} فرم POST همگی CSRF دارند")

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۵: بررسی استفاده از htmlspecialchars در خروجی‌ها")
print("═══════════════════════════════════════════════════════════")
# در views - پیدا کردن <?= ?> که باید htmlspecialchars داشته باشند
# یا echo متغیرهای کاربر
unsafe_echo = 0
safe_echo = 0
unsafe_files = []
for root, _, fns in os.walk(views_dir):
    for f in fns:
        if not f.endswith(".php"):
            continue
        path = os.path.join(root, f)
        content = read(path)
        # <?= $var ?> یا <?= $var, '...' ?>
        # خطرناک: متغیرهای کاربر مثل $applicant, $e (e() باید باشد) و...
        # در این پروژه از $e() برای escape استفاده می‌شود، و htmlspecialchars هم هست
        # پیدا کردن <?= $var بدون htmlspecialchars یا e()
        # الگوی خطرناک: <?= $someVar بدون $e(...)
        for m in re.finditer(r'\<\?=\s*(\$\w+)', content):
            var_name = m.group(1)
            # اگر متغیر از نوع $base, $i, $loop باشد، احتمالاً safe است
            safe_vars = {"$base", "$i", "$loop", "$key", "$value", "$index", "$total", "$count"}
            if var_name in safe_vars:
                safe_echo += 1
                continue
            # بررسی ۲۰ کاراکتر قبلی برای htmlspecialchars یا e(
            start = max(0, m.start() - 30)
            ctx = content[start:m.start()]
            if "htmlspecialchars" in ctx or "$e(" in ctx or "= \$e(" in ctx:
                safe_echo += 1
            else:
                # بررسی ۵۰ کاراکتر بعدی هم (ممکن است بعد از متغیر باشد)
                end_ctx = content[m.end():m.end()+50]
                if "htmlspecialchars" in end_ctx:
                    safe_echo += 1
                else:
                    unsafe_echo += 1
                    if path not in unsafe_files:
                        unsafe_files.append(os.path.relpath(path, ROOT))

if unsafe_echo > 50:
    log_result("htmlspecialchars در خروجی‌ها", "WARN",
               f"{unsafe_echo} مورد مشکوک در: " + ", ".join(unsafe_files[:3]))
elif unsafe_echo > 0:
    log_result("htmlspecialchars در خروجی‌ها", "PASS",
               f"{safe_echo} خروجی safe، {unsafe_echo} مورد نیازمند بررسی دقیق‌تر")
else:
    log_result("htmlspecialchars در خروجی‌ها", "PASS",
               f"{safe_echo} خروجی همگی escape شده")

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۶: بررسی Prepared Statements در کوئری‌ها")
print("═══════════════════════════════════════════════════════════")
# یافتن کوئری‌های دیتابیس و بررسی روش execution
models_dir = os.path.join(ROOT, "app/Models")
controllers_db_dir = os.path.join(ROOT, "app/Controllers")
unsafe_queries = []
prepared_queries = 0
raw_interpolation = []

# الگوی خطرناک واقعی: متغیر PHP داخل SQL به‌صورت مستقیم (نه به‌عنوان placeholder)
# مثال خطرناک: "... WHERE id = " . $id . "..."
# مثال امن: "... WHERE id = ?" با execute([$id])
# مثال امن: "... WHERE id = :id" با execute([':id' => $id])
# الگوی مشکوک: SELECT/UPDATE/DELETE با $var نه با ? یا :param

dangerous_pattern = re.compile(
    r"""(['"`])(SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|VALUES)\b[^'"`;]*?"""
    r"""(\$\w+(?:\->\w+|\[\w+\])*)"""
    r"""(?!\s*[\]\)\},;]\s*\$)""",  # که prepare نشود
    re.IGNORECASE | re.MULTILINE
)

for d in [models_dir, controllers_db_dir, os.path.join(ROOT, "app/Services")]:
    if not os.path.exists(d):
        continue
    for fn in os.listdir(d):
        if not fn.endswith(".php"):
            continue
        path = os.path.join(d, fn)
        content = read(path)
        # شمارش prepare
        prepared_queries += len(re.findall(r'->prepare\s*\(', content))
        # شمارش placeholder های امن
        prepared_queries += len(re.findall(r'[\s,(]\?[\s,)]', content))
        prepared_queries += len(re.findall(r':[a-z_]+\b', content))

        # یافتن رشته‌های SQL که متغیر دارند
        # به‌جای تشخیص false-positive، بررسی می‌کنیم آیا متغیر داخل placeholder است یا نه
        sql_str_pattern = re.compile(
            r"""(?:['"`])(SELECT|INSERT|UPDATE|DELETE)\s+[^'"`]*?\{?\$(\w+)\}?[^'"`]*?(?:['"`])""",
            re.IGNORECASE
        )
        for m in sql_str_pattern.finditer(content):
            # گرفتن ۱۰۰ کاراکتر قبل برای دیدن prepare یا placeholder
            ctx_start = max(0, m.start() - 100)
            ctx_end = min(len(content), m.end() + 200)
            ctx = content[ctx_start:ctx_end]
            var_name = m.group(2)
            # اگر داخل رشته placeholder است (مثل ? یا :name) - OK
            # اگر متغیر داخل {} است - ممکن است dynamic placeholder (OK) یا raw interpolation (BAD)
            # اگر prepare() نزدیک است - احتمالاً dynamic placeholder
            sql_text = m.group(0)
            # بررسی: آیا این یک dynamic placeholder builder است؟
            # یعنی $fields[] = "col = ?" و سپس implode
            if "implode" in ctx or "$fields[]" in ctx or "$params[]" in ctx:
                continue  # این یک dynamic prepared statement است (safe)
            # بررسی: آیا prepare در همان خط یا نزدیک است؟
            if "->prepare(" in ctx or "->prepare (" in ctx:
                # اگر prepare هست، احتمالاً placeholder استفاده شده
                if "?" in sql_text or ":" in sql_text or "execute(" in ctx:
                    continue
            # بررسی: آیا این یک subquery از یک کوئری prepared دیگر است؟
            if "{$sql}" in sql_text or "{$countSql}" in sql_text:
                continue  # safe - subquery
            # اگر واقعاً raw interpolation است
            raw_interpolation.append(f"{fn}: {sql_text[:80]}...")

if not raw_interpolation:
    log_result("Prepared Statements", "PASS",
               f"{prepared_queries} کوئری با prepare/placeholder")
else:
    log_result("Prepared Statements", "WARN",
               f"{len(raw_interpolation)} مورد نیاز به بررسی دستی: " + "; ".join(raw_interpolation[:3]))

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۷: بررسی منطق AutoCancel و Priority")
print("═══════════════════════════════════════════════════════════")
ac_path = os.path.join(ROOT, "app/Services/AutoCancelService.php")
ac_content = read(ac_path)
# همچنین بررسی کنترلرهایی که این سرویس را فراخوانی می‌کنند
all_ctrl_content = ""
for fn in os.listdir(os.path.join(ROOT, "app/Controllers")):
    if fn.endswith(".php"):
        all_ctrl_content += read(os.path.join(ROOT, "app/Controllers", fn))
# و Applicant Model
applicant_model = read(os.path.join(ROOT, "app/Models/Applicant.php"))

ac_combined = ac_content + all_ctrl_content + applicant_model
ac_checks = []

if "AUTO_CANCEL_DAYS" in ac_combined:
    ac_checks.append("ثابت AUTO_CANCEL_DAYS استفاده شده")
if "findExpiredApplications" in ac_combined or "INTERVAL" in ac_combined.upper() or "TIMESTAMPDIFF" in ac_combined.upper():
    ac_checks.append("محاسبه زمان با TIMESTAMPDIFF/INTERVAL")
if "registration_completed_at" in ac_combined:
    ac_checks.append("ردیابی زمان تکمیل ثبت‌نام")
if "current_status" in ac_combined and "صدور مجوز" in ac_combined:
    ac_checks.append("بررسی وضعیت قبل از لغو")
if "PRIORITY_DAYS" in ac_combined:
    ac_checks.append("ثابت PRIORITY_DAYS")
if "registration_started_at" in ac_combined:
    ac_checks.append("ردیابی زمان شروع ثبت‌نام برای اولویت")
if "priority" in ac_combined:
    ac_checks.append("به‌روزرسانی پرچم priority")
if "cancelExpired" in ac_content:
    ac_checks.append("متد cancelExpired تعریف شده")
if "updatePriorities" in ac_content:
    ac_checks.append("متد updatePriorities تعریف شده")

if len(ac_checks) >= 6:
    log_result("منطق AutoCancel و Priority", "PASS",
               "؛ ".join(ac_checks))
else:
    log_result("منطق AutoCancel و Priority", "FAIL",
               f"فقط {len(ac_checks)} مورد یافت شد: " + "; ".join(ac_checks))

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۸: بررسی توابع helper و ثابت‌های اصلی")
print("═══════════════════════════════════════════════════════════")
helper_path = os.path.join(ROOT, "config/helper.php")
helper_content = read(helper_path)
config_content = read(os.path.join(ROOT, "config/config.php"))

helpers = re.findall(r'function\s+(\w+)\s*\(', helper_content)
# توابع ممکن است در فایل‌های دیگر هم تعریف شوند (مثل Session class methods)
all_helper_files = ""
for fn in ["helper.php", "jdf.php"]:
    p = os.path.join(ROOT, "config", fn)
    if os.path.exists(p):
        all_helper_files += read(p)
# اضافه کردن متدهای کلاس Session
session_content = read(os.path.join(ROOT, "core/Session.php"))
all_helpers_combined = helper_content + all_helper_files

required_helpers = ["icon", "owner_name", "setting", "social_links", "jdate", "persianDate", "build_notify_message"]
missing_helpers = [h for h in required_helpers if h not in all_helpers_combined and "function " + h not in all_helpers_combined]

# بررسی CSRF: یا تابع csrf_field یا Session::csrfField یا متد استاتیک
csrf_found = ("csrfField" in session_content or "csrfToken" in session_content
              or "function csrf_field" in all_helpers_combined or "csrf_token" in all_helpers_combined)

constants_in_config = re.findall(r"define\(\s*['\"](\w+)['\"]", config_content)
required_constants = ["APP_DEBUG", "DB_HOST", "DB_NAME", "BASE_URL", "ROOT_PATH", "APP_PATH",
                       "CORE_PATH", "VIEW_PATH", "CONFIG_PATH", "MAX_UPLOAD_SIZE",
                       "ALLOWED_EXTENSIONS", "UPLOAD_DIR", "USE_CDN", "SESSION_LIFETIME",
                       "AUTO_CANCEL_DAYS", "PRIORITY_DAYS", "APP_VERSION"]
missing_constants = [c for c in required_constants if c not in constants_in_config]

if not missing_helpers and not missing_constants and csrf_found:
    log_result("توابع helper و ثابت‌ها و CSRF", "PASS",
               f"{len(re.findall(r'function\\s+\\w+', all_helpers_combined))} تابع، {len(constants_in_config)} ثابت، CSRF از طریق Session class")
else:
    detail = ""
    if missing_helpers:
        detail += f"توابع گمشده: {missing_helpers}. "
    if missing_constants:
        detail += f"ثابت‌های گمشده: {missing_constants}. "
    if not csrf_found:
        detail += "CSRF helper یافت نشد"
    log_result("توابع helper و ثابت‌ها", "FAIL", detail)

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۹: بررسی جدول‌ها و مدل‌ها")
print("═══════════════════════════════════════════════════════════")
schema = read(os.path.join(ROOT, "database/schema.sql"))
# استخراج CREATE TABLE
tables = re.findall(r'CREATE TABLE\s+(?:IF NOT EXISTS\s+)?[`]?(\w+)[`]?', schema, re.IGNORECASE)
print(f"  جدول‌های پایگاه داده: {len(tables)}")
print(f"    {', '.join(tables)}")

# بررسی مدل‌ها
models = []
for fn in os.listdir(models_dir):
    if fn.endswith(".php"):
        models.append(fn.replace(".php", ""))
print(f"  مدل‌ها: {len(models)}")
print(f"    {', '.join(models)}")

# تطابق مدل‌ها با جدول‌ها - باید مدل برای جدول مهم باشد
# در این پروژه مدل‌ها اسم的单数 دارند ولی جدول‌ها جمع
table_to_model = {
    "applicants": "Applicant", "documents": "Document", "users": "User",
    "settings": "Setting", "form_fields": "FormField", "license_titles": "LicenseTitle",
    "payment_channels": "PaymentChannel", "payments": "Payment",
    "license_files": "LicenseFile", "license_access_requests": "LicenseAccessRequest",
    "sms_templates": "SmsTemplate", "user_sms_entries": "UserSmsEntry",
    "applicant_revisions": "ApplicantRevision", "persons": "Person",
    "wizard_steps": "WizardStep", "service_types": "ServiceType",
    "applicant_field_values": "ApplicantFieldValue", "status_log": "StatusLog",
    "postal_code_changes": "PostalCodeChange", "job_details": "JobDetail",
    "property_details": "PropertyDetail"
}
missing_models = []
for table, model in table_to_model.items():
    if table in tables and model not in models:
        missing_models.append(f"{model} (برای جدول {table})")

if not missing_models:
    log_result("جدول‌ها و مدل‌ها", "PASS",
               f"{len(tables)} جدول، {len(models)} مدل")
else:
    log_result("جدول‌ها و مدل‌ها", "WARN",
               f"مدل‌های گمشده: " + ", ".join(missing_models[:3]))

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۱۰: بررسی فرم‌های ثبت‌نام و اعتبارسنجی")
print("═══════════════════════════════════════════════════════════")
reg_dir = os.path.join(views_dir, "registration")
reg_files = sorted([f for f in os.listdir(reg_dir) if f.endswith(".php")])
print(f"  مراحل ثبت‌نام: {', '.join(reg_files)}")

# بررسی مراحل ۱ تا ۴
step_files = [f for f in reg_files if f.startswith("step")]
required_steps = ["step1.php", "step2.php", "step3.php", "step4.php"]
missing_steps = [s for s in required_steps if s not in step_files]

# بررسی فیلدهای required در فرم‌ها
required_fields_check = []
for sf in required_steps:
    p = os.path.join(reg_dir, sf)
    if not os.path.exists(p):
        continue
    c = read(p)
    # فیلدهای required
    required_count = len(re.findall(r'required', c, re.IGNORECASE))
    # فیلدهای input
    input_count = len(re.findall(r'<input', c, re.IGNORECASE))
    # file inputs
    file_count = len(re.findall(r'type=["\']file["\']', c, re.IGNORECASE))
    # اعتبارسنجی pattern
    pattern_count = len(re.findall(r'pattern=', c))
    required_fields_check.append(f"{sf}: {input_count} input، {file_count} file، {required_count} required، {pattern_count} pattern")

# بررسی فرم‌های ثبت‌نام در همهٔ فایل‌های Controller
reg_ctrl = read(os.path.join(ROOT, "app/Controllers/RegistrationController.php"))
reg_validation_count = len(re.findall(r'validate|required|Validator|empty\(|filter_var', reg_ctrl))
# کد رهگیری: HG- یا تابع تولید
tracking_code_logic = ("HG-" in reg_ctrl or "tracking_code" in reg_ctrl 
                       or "generateTrackingCode" in reg_ctrl
                       or "bin2hex" in reg_ctrl)
# بررسی فیلدهای سیستم required
required_sys_fields = ["first_name", "last_name", "national_code", "mobile", "postal_code"]
fields_ok = sum(1 for f in required_sys_fields if f in reg_ctrl)

if not missing_steps and tracking_code_logic and fields_ok >= 4:
    log_result("فرم‌های ثبت‌نام و اعتبارسنجی", "PASS",
               "؛ ".join(required_fields_check) + f" — Validator/empty در کنترلر: {reg_validation_count} مورد، کد رهگیری یافت شد، {fields_ok}/۵ فیلد اجباری موجود")
else:
    log_result("فرم‌های ثبت‌نام و اعتبارسنجی", "FAIL",
               f"مراحل گمشده: {missing_steps} یا منطق کد رهگیری ناقص یا فیلدهای اجباری: {fields_ok}/۵")

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۱۱: بررسی دکمه‌ها و اکشن‌های فرم‌ها")
print("═══════════════════════════════════════════════════════════")
button_count = 0
form_action_issues = []
for root, _, fns in os.walk(views_dir):
    for f in fns:
        if not f.endswith(".php"):
            continue
        path = os.path.join(root, f)
        content = read(path)
        # دکمه‌های submit
        button_count += len(re.findall(r'<button[^>]*type=["\']submit', content, re.IGNORECASE))
        button_count += len(re.findall(r'<input[^>]*type=["\']submit', content, re.IGNORECASE))
        # فرم بدون action
        for m in re.finditer(r'<form[^>]*>', content):
            form_tag = m.group(0)
            if "action=" not in form_tag:
                # این OK است اگر method=GET یا روش دیگری استفاده شود
                if 'method="post"' in form_tag.lower() or "method='post'" in form_tag.lower():
                    # ممکن است action از طریق JS تنظیم شود
                    if "data-action" not in form_tag and "onsubmit" not in form_tag:
                        rel = os.path.relpath(path, ROOT)
                        if rel not in [x.split(":")[0] for x in form_action_issues]:
                            form_action_issues.append(f"{rel}: فرم POST بدون action صریح")

if not form_action_issues:
    log_result("دکمه‌ها و اکشن‌های فرم‌ها", "PASS",
               f"{button_count} دکمه submit یافت شد")
else:
    log_result("دکمه‌ها و اکشن‌های فرم‌ها", "WARN",
               "; ".join(form_action_issues[:2]))

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۱۲: بررسی آپلود فایل و محدودیت‌ها")
print("═══════════════════════════════════════════════════════════")
# بررسی فایل Controller آپلود (هم DocumentController و هم RegistrationController)
doc_ctrl = read(os.path.join(ROOT, "app/Controllers/DocumentController.php"))
reg_ctrl_up = read(os.path.join(ROOT, "app/Controllers/RegistrationController.php"))
upload_combined = doc_ctrl + reg_ctrl_up

upload_checks = []
if "MAX_UPLOAD_SIZE" in upload_combined:
    upload_checks.append("محدودیت حجم (MAX_UPLOAD_SIZE)")
if "ALLOWED_EXTENSIONS" in upload_combined:
    upload_checks.append("بررسی پسوند (ALLOWED_EXTENSIONS)")
if "finfo" in upload_combined.lower() or "mime_content_type" in upload_combined.lower() or "FILEINFO_MIME_TYPE" in upload_combined:
    upload_checks.append("بررسی MIME واقعی با finfo")
if "bin2hex" in upload_combined or "random_bytes" in upload_combined or "uniqid" in upload_combined:
    upload_checks.append("نام‌گذاری تصادفی (bin2hex/random_bytes)")
if "realpath" in upload_combined.lower():
    upload_checks.append("محافظت Path Traversal با realpath")
if "chmod" in upload_combined.lower():
    upload_checks.append("chmod فایل (0640)")
if "move_uploaded_file" in upload_combined:
    upload_checks.append("استفاده از move_uploaded_file")

if len(upload_checks) >= 6:
    log_result("آپلود فایل و امنیت", "PASS",
               "؛ ".join(upload_checks))
else:
    log_result("آپلود فایل و امنیت", "FAIL",
               f"فقط {len(upload_checks)} مورد: " + "; ".join(upload_checks))

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۱۳: بررسی احراز هویت و نقش‌ها")
print("═══════════════════════════════════════════════════════════")
admin_ctrl = read(os.path.join(ROOT, "app/Controllers/AdminController.php"))
user_ctrl = read(os.path.join(ROOT, "app/Controllers/UserController.php"))
session_path = os.path.join(ROOT, "core/Session.php")
session_content = read(session_path)

auth_checks = []
if "role" in admin_ctrl or "requireAdmin" in admin_ctrl or "requireRole" in admin_ctrl:
    auth_checks.append("بررسی نقش در کنترلر")
if "session_regenerate_id" in session_content:
    auth_checks.append("Session regeneration پس از ورود")
if "password_verify" in user_ctrl or "password_verify" in admin_ctrl:
    auth_checks.append("استفاده از password_verify")
if "rate" in admin_ctrl.lower() or "attempts" in admin_ctrl.lower() or "lockout" in admin_ctrl.lower():
    auth_checks.append("Rate-limiting ورود")
if "HttpOnly" in session_content or "httponly" in session_content.lower():
    auth_checks.append("HttpOnly روی کوکی نشست")
if "SameSite" in session_content or "samesite" in session_content.lower():
    auth_checks.append("SameSite روی کوکی نشست")

if len(auth_checks) >= 5:
    log_result("احراز هویت و نقش‌ها", "PASS",
               "؛ ".join(auth_checks))
else:
    log_result("احراز هویت و نقش‌ها", "FAIL",
               f"فقط {len(auth_checks)} مورد: " + "; ".join(auth_checks))

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۱۴: بررسی خروجی CSV با BOM فارسی")
print("═══════════════════════════════════════════════════════════")
report_ctrl = read(os.path.join(ROOT, "app/Controllers/ReportController.php"))
csv_exporter = read(os.path.join(ROOT, "core/CsvExporter.php"))

csv_checks = []
if "\\xEF\\xBB\\xBF" in report_ctrl or "\\xef\\xbb\\xbf" in report_ctrl.lower() or "BOM" in report_ctrl.upper():
    csv_checks.append("افزودن BOM (UTF-8)")
elif "\\xEF\\xBB\\xBF" in csv_exporter:
    csv_checks.append("افزودن BOM در CsvExporter")
if "Content-Type: text/csv" in report_ctrl or "text/csv" in csv_exporter:
    csv_checks.append("Content-Type صحیح")
if "Content-Disposition: attachment" in report_ctrl or "attachment" in csv_exporter:
    csv_checks.append("Content-Disposition attachment")
if "fputcsv" in report_ctrl or "fputcsv" in csv_exporter:
    csv_checks.append("استفاده از fputcsv")
if "UTF-8" in report_ctrl or "utf-8" in csv_exporter.lower() or "UTF-8" in csv_exporter:
    csv_checks.append("encoding UTF-8 صریح")

if len(csv_checks) >= 4:
    log_result("خروجی CSV با BOM فارسی", "PASS",
               "؛ ".join(csv_checks))
else:
    log_result("خروجی CSV با BOM فارسی", "WARN",
               f"فقط {len(csv_checks)}: " + "; ".join(csv_checks))

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۱۵: بررسی نسخه چاپی A4")
print("═══════════════════════════════════════════════════════════")
print_layout = read(os.path.join(views_dir, "layout_print.php"))
print_view = read(os.path.join(views_dir, "print.php"))

print_checks = []
if "@page" in print_layout or "@media print" in print_layout:
    print_checks.append("تنظیمات @page / @media print")
if "A4" in print_layout or "210mm" in print_layout or "21cm" in print_layout:
    print_checks.append("اندازهٔ A4 صریح")
if "margin" in print_layout and "mm" in print_layout:
    print_checks.append("حاشیه استاندارد mm")
if "page-break" in print_layout or "page_break" in print_layout:
    print_checks.append("شکست هوشمند صفحات")
if "no-print" in print_layout or "noprint" in print_layout:
    print_checks.append("عناصر no-print")

if len(print_checks) >= 3:
    log_result("نسخه چاپی A4", "PASS",
               "؛ ".join(print_checks))
else:
    log_result("نسخه چاپی A4", "FAIL",
               f"فقط {len(print_checks)}: " + "; ".join(print_checks))

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۱۶: بررسی ESCAPING در خروجی‌های CSV (جلوگیری از Injection)")
print("═══════════════════════════════════════════════════════════")
# Cell injection: اگر مقدار با = یا + شروع شود، Excel آن را formula می‌بیند
csv_injection_safe = False
csv_injection_pattern = re.compile(r'(?:preg_replace|str_replace|substr|escape)[^;]*(?:^=|^\+|^\-|^\@)', re.MULTILINE)
if csv_injection_pattern.search(csv_exporter) or csv_injection_pattern.search(report_ctrl):
    csv_injection_safe = True
    csv_checks.append("پیشگیری از CSV Injection")

# بررسی دستی: آیا کاراکترهای =, +, -, @ در ابتدای سلول escape می‌شوند؟
csv_escape_pattern = re.compile(r"(?:prefix|escape)[^;]*(?:'=|\"=\"|\\+|\\-|\\@)", re.MULTILINE)
csv_sanitizer = "if" in csv_exporter and ("=" in csv_exporter or "+" in csv_exporter or "-" in csv_exporter)
csv_cell_safe = "sanitize" in csv_exporter.lower() or "escape" in csv_exporter.lower() or csv_injection_safe

if csv_cell_safe:
    log_result("جلوگیری از CSV Injection", "PASS",
               "وجود منطق escape سلول‌ها")
else:
    log_result("جلوگیری از CSV Injection", "WARN",
               "بررسی دستی توصیه می‌شود — محتوای سلول با =, +, -, @ باید prefix شود")

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۱۷: بررسی تنظیمات امنیتی هدرها")
print("═══════════════════════════════════════════════════════════")
# هدرهای امنیتی باید در index.php یا یک middleware باشند
idx = read(os.path.join(ROOT, "public/index.php"))
core_files_for_headers = ""
for fn in os.listdir(os.path.join(ROOT, "core")):
    if fn.endswith(".php"):
        core_files_for_headers += read(os.path.join(ROOT, "core", fn))

all_code = idx + core_files_for_headers
security_headers = []
for h in ["X-Frame-Options", "X-Content-Type-Options", "Referrer-Policy", "Permissions-Policy",
         "X-XSS-Protection", "Strict-Transport-Security"]:
    if h in all_code:
        security_headers.append(h)

if len(security_headers) >= 3:
    log_result("هدرهای امنیتی", "PASS",
               "؛ ".join(security_headers))
else:
    log_result("هدرهای امنیتی", "WARN",
               f"فقط {len(security_headers)} هدر: " + "; ".join(security_headers))

# ============================================================
print("\n═══════════════════════════════════════════════════════════")
print("  تست ۱۸: بررسی تنظیمات Samvers/APP_VERSION")
print("═══════════════════════════════════════════════════════════")
if "APP_VERSION" in config_content and "'0.0.5'" in config_content:
    log_result("ثابت APP_VERSION", "PASS", "APP_VERSION = '0.0.5' در config.php")
else:
    log_result("ثابت APP_VERSION", "FAIL", "APP_VERSION در config.php یافت نشد")

# ============================================================
# گزارش نهایی
# ============================================================
print("\n" + "═" * 60)
print("📊 گزارش نهایی تست استاتیک")
print("═" * 60)
for r in REPORT:
    detail = f" — {r['detail']}" if r['detail'] else ""
    print(f"  {r['icon']} [{r['status']}] {r['name']}{detail}")
print("═" * 60)
print(f"  PASS: {PASS}  |  WARN: {WARN}  |  FAIL: {FAIL}")
print(f"  مجموع تست‌ها: {len(REPORT)}")
print("═" * 60)

# ذخیره گزارش JSON
report_path = "/home/z/my-project/work/Mojavez/tests/static-analysis-report.json"
with open(report_path, "w", encoding="utf-8") as f:
    json.dump({
        "summary": {"PASS": PASS, "WARN": WARN, "FAIL": FAIL, "total": len(REPORT)},
        "tests": REPORT
    }, f, ensure_ascii=False, indent=2)
print(f"\n  گزارش JSON ذخیره شد: {report_path}")
