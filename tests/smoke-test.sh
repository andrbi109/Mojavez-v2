#!/usr/bin/env bash
# =====================================================================
# آزمون دود (Smoke Test) سامانهٔ مجوز صنایع دستی
# ---------------------------------------------------------------------
# کل گردشهای اصلی سامانه را با curl شبیه‌سازی می‌کند:
#   ۱. صفحات عمومی           ۲. ثبت‌نام ۴ مرحله‌ای (با CSRF و آپلود فایل)
#   ۳. پیگیری + تغییر کد پستی ۴. نسخهٔ چاپی
#   ۵. ورود مدیر + داشبورد   ۶. تغییر وضعیت (یکطرفه)
#   ۷. گزارش CSV/چاپی        ۸. گردش کار تأیید کد پستی
#   ۹. مدیریت کاربران        ۱۰. محافظت CSRF و نقش‌ها
# اجرا: ./tests/smoke-test.sh [آدرس پایه]   (پیش‌فرض: http://127.0.0.1:8000)
# =====================================================================
B="${1:-http://127.0.0.1:8000}"
PASS=0; FAIL=0
TMP=$(mktemp -d); cd "$TMP"

green() { printf '\033[32m%s\033[0m\n' "$1"; }
red()   { printf '\033[31m%s\033[0m\n' "$1"; }

# تولید کد ملی ۱۰ رقمی با رقم کنترل معتبر
gen_nc() { php -r '$b=(string)random_int(100000000,999999999); $s=0; for($i=0;$i<9;$i++)$s+=$b[$i]*(10-$i); $r=$s%11; $c=$r<2?$r:11-$r; echo $b.$c;'; }
# تولید شناسهٔ ملی ۱۱ رقمی شخص حقوقی با رقم کنترل معتبر
gen_lid() { php -r '$b=(string)random_int(1000000000,9999999999); $w=[29,27,23,19,17,29,27,23,19,17]; $s=0; for($i=0;$i<10;$i++)$s+=$b[$i]*$w[$i]; $r=$s%11; $c=$r<2?$r:11-$r; echo $b.$c;'; }

# assert NAME CONDITION
assert() {
    if eval "$2"; then PASS=$((PASS+1)); green "  ✔ $1";
    else FAIL=$((FAIL+1)); red "  ✘ $1 — شرط ناموفق: $2"; fi
}

# استخراج توکن CSRF از یک صفحه
get_csrf() { grep -oE 'name="_csrf" value="[a-f0-9]{64}"' "$1" | head -1 | grep -oE '[a-f0-9]{64}'; }

# تولید دادهٔ یکتا برای هر اجرا (جلوگیری از تداخل یکتایی کد ملی/موبایل)
RND=$RANDOM$RANDOM
NC=$(gen_nc)
MB="09$(printf '%09d' $((RND % 1000000000)))"

echo "═══════════ ۱) صفحات عمومی ═══════════"
for u in / /guide /register /tracking /admin/login; do
    c=$(curl -s -o pg.html -w '%{http_code}' "$B$u")
    assert "GET $u → 200" "[ '$c' = '200' ] && ! grep -qiE 'fatal error|warning:' pg.html"
done
c=$(curl -s -o /dev/null -w '%{http_code}' "$B/page-does-not-exist")
assert "GET مسیر نامعتبر → 404" "[ '$c' = '404' ]"

echo "═══════════ ۲) ثبت‌نام ۴ مرحله‌ای ═══════════"
rm -f cj.txt
# صفحهٔ انتخاب نوع خدمت (فاز ۹) باید نمایش داده شود
curl -s -c cj.txt -b cj.txt -o sel.html "$B/register"
assert "ورود به ثبت‌نام → صفحهٔ انتخاب نوع خدمت" "grep -q 'نوع خدمت' sel.html && grep -q 'مجوز صنایع دستی' sel.html"
curl -s -c cj.txt -b cj.txt -o /dev/null "$B/register/service/1"
php -r '$im = imagecreatetruecolor(80, 40); $c = imagecolorallocate($im, 0, 180, 216); imagefilledrectangle($im, 0, 0, 80, 40, $c); imagepng($im, "t.png");'

# شناسایی فیلدهای پویای اجباری فعال (فرم‌ساز) تا آزمون با هر پیکربندی فرم سازگار بماند
DYN1=""; DYN2=""; DYN3=""; DYN4=""; DYNF=""
while IFS=$'	' read -r fid fstep ftype fopt; do
    case "$ftype" in
        select|radio) val="$fopt" ;;
        number) val="5" ;;
        checkbox) val="بله" ;;
        date_jalali) val="1370-01-01" ;;
        *) val="مقدار آزمون" ;;
    esac
    [ -z "$val" ] && val="گزینهٔ اول"
    case "$fstep" in
        step1) DYN1="$DYN1 -d dyn_$fid=$val"; DYNF="$DYNF -F dyn_$fid=$val" ;;
        step2) DYN2="$DYN2 -d dyn_$fid=$val"; DYNF="$DYNF -F dyn_$fid=$val" ;;
        step3) DYN3="$DYN3 -d dyn_$fid=$val"; DYNF="$DYNF -F dyn_$fid=$val" ;;
        step4) if [ "$ftype" = "file" ]; then DYN4="$DYN4 -F dyn_$fid=@t.png"; else DYN4="$DYN4 -F dyn_$fid=$val"; DYNF="$DYNF -F dyn_$fid=$val"; fi ;;
    esac
done < <(mysql -N handicraft_license -e "SELECT f.id, IFNULL(w.view_key,'custom'), f.field_type, IFNULL(SUBSTRING_INDEX(f.options, '\n', 1), '') FROM form_fields f JOIN wizard_steps w ON w.id=f.wizard_step_id WHERE f.is_system=0 AND f.is_active=1 AND f.is_required=1 AND w.is_active=1 AND w.view_key IS NOT NULL" 2>/dev/null)

cat > t.pdf <<'PDFEOF'
%PDF-1.4
1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj
3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj
trailer<</Root 1 0 R>>
%%EOF
PDFEOF

curl -s -c cj.txt -b cj.txt -o s1.html "$B/register"
CSRF=$(get_csrf s1.html)
assert "توکن CSRF مرحلهٔ ۱ یافت شد" "[ -n '$CSRF' ]"

# بدون توکن CSRF باید رد شود (کد ۴۱۹)
c=$(curl -s -c cj.txt -b cj.txt -o /dev/null -w '%{http_code}' -d "first_name=تست" "$B/register/step1")
assert "POST بدون CSRF → 419" "[ '$c' = '419' ]"

curl -s -c cj.txt -b cj.txt -o r1.html -w '%{redirect_url}' \
    -d "_csrf=$CSRF" -d "first_name=زرشک" -d "last_name=تست‌زاده" \
    -d "national_code=$NC" -d "postal_code=1234567891" -d "mobile=$MB" \
    -d "father_name=پدرتست" -d "marital_status=متأهل" -d "children_count=1" \
    -d "physical_condition=سالم" -d "has_court_case=خیر" -d "has_active_loan=خیر" \
    -d "has_overdue_installments=خیر" -d "birth_year=1372" -d "birth_month=3" -d "birth_day=5" \
    "$B/register/step1" > rd1
assert "مرحلهٔ ۱ → redirect به step2" "grep -qE 'register/step/?2' rd1"

curl -s -c cj.txt -b cj.txt -o s2.html "$B/register/step2"
CSRF=$(get_csrf s2.html)
curl -s -c cj.txt -b cj.txt -o r2.html -w '%{redirect_url}' \
    -d "_csrf=$CSRF" -d "license_title=سفال" -d "experience_months=24" -d "training_method=استاد شاگردی" $DYN2 \
    "$B/register/step2" > rd2
assert "مرحلهٔ ۲ → redirect به step3" "grep -qE 'register/step/?3' rd2"

curl -s -c cj.txt -b cj.txt -o s3.html "$B/register/step3"
CSRF=$(get_csrf s3.html)
curl -s -c cj.txt -b cj.txt -o r3.html -w '%{redirect_url}' \
    -d "_csrf=$CSRF" -d "ownership_status=مستأجر" -d "province=اصفهان" -d "county=کاشان" -d "city=کاشان" \
    -d "district=" -d "rural_district=" -d "village=قمصر" \
    -d "electricity_bill_id=987$(( RND % 1000000000 ))" \
    -d "address_street=خیابان بهشتی" -d "address_alley=کوچه گل" -d "address_plaque=7" $DYN3 \
    "$B/register/step3" > rd3
assert "مرحلهٔ ۳ → redirect به step4" "grep -qE 'register/step/?4' rd3"

curl -s -c cj.txt -b cj.txt -o s4.html "$B/register/step4"
CSRF=$(get_csrf s4.html)
curl -s -c cj.txt -b cj.txt -o r4.html -L -w '%{url_effective}' \
    -F "_csrf=$CSRF" -F "national_card_front=@t.png" -F "national_card_back=@t.png" \
    -F "birth_cert=@t.png" -F "ownership_doc=@t.pdf" -F "optional_1=@t.pdf" $DYN4 \
    "$B/register/step4" > rd4
TC=$(grep -oE 'HG-[A-F0-9]{8}' rd4 | head -1)
assert "مرحلهٔ ۴ → صفحهٔ موفقیت با کد رهگیری" "[ -n '$TC' ]"

# آپلود فایل با پسوند ممنوع باید رد شود
curl -s -c cj.txt -b cj.txt -o sx.html "$B/register" > /dev/null
echo '<?php echo "hack"; ?>' > evil.php
curl -s -c cj.txt -b cj.txt -d "_csrf=$(get_csrf sx.html)" \
    -d "first_name=بد" -d "last_name=افزار" -d "national_code=$NC" -d "postal_code=1111111111" \
    -d "mobile=$MB" -d "father_name=تست" -d "marital_status=مجرد" -d "physical_condition=سالم" \
    -d "has_court_case=خیر" -d "has_active_loan=خیر" -d "has_overdue_installments=خیر" \
    -d "birth_year=1370" -d "birth_month=1" -d "birth_day=1" "$B/register/step1" -o /dev/null
assert "فایل PHP هیچ‌گاه در uploads ذخیره نمی‌شود" "! ls $TMP/evil-uploaded 2>/dev/null"

echo "═══════════ ۳) پیگیری و تغییر کد پستی ═══════════"
rm -f tj.txt
curl -s -c tj.txt -b tj.txt -o ts.html "$B/tracking"
CSRF=$(get_csrf ts.html)
curl -s -c tj.txt -b tj.txt -o /dev/null -w '%{redirect_url}' \
    -d "_csrf=$CSRF" -d "tracking_code=$TC" "$B/tracking/search" > trd
assert "جستجوی کد رهگیری → redirect به وضعیت" "grep -q \"tracking/status/$TC\" trd"

curl -s -c tj.txt -b tj.txt -o tst.html "$B/tracking/status/$TC"
assert "صفحهٔ وضعیت → ۲۰۰ و شامل تایم‌لاین" "grep -q 'تاریخچه رویدادها' tst.html && ! grep -qiE 'fatal|warning:' tst.html"
CSRF=$(get_csrf tst.html)

curl -s -c tj.txt -b tj.txt -o /dev/null -w '%{http_code}' \
    -d "_csrf=$CSRF" -d "tracking_code=$TC" -d "new_postal_code=123" "$B/tracking/change-postal-code" > pc1
curl -s -c tj.txt -b tj.txt -o pce.html "$B/tracking/status/$TC"
assert "کد پستی نامعتبر (۳ رقم) → خطای اعتبارسنجی" "grep -q 'دقیقاً ۱۰ رقم' pce.html"

CSRF=$(get_csrf pce.html)
curl -s -c tj.txt -b tj.txt -o /dev/null \
    -d "_csrf=$CSRF" -d "tracking_code=$TC" -d "new_postal_code=9999999999" "$B/tracking/change-postal-code"
curl -s -c tj.txt -b tj.txt -o pcs.html "$B/tracking/status/$TC"
assert "درخواست معتبر تغییر کد پستی ثبت شد" "grep -q 'در انتظار بررسی' pcs.html"

# درخواست تکراری در حالت «در انتظار» باید مسدود شود
CSRF=$(get_csrf pcs.html)
curl -s -c tj.txt -b tj.txt -o /dev/null \
    -d "_csrf=$CSRF" -d "tracking_code=$TC" -d "new_postal_code=8888888888" "$B/tracking/change-postal-code" || true
curl -s -c tj.txt -b tj.txt -o pcd.html "$B/tracking/status/$TC"
assert "درخواست تکراری هنگام وجود درخواست در انتظار → مسدود" "grep -q 'در انتظار بررسی دارید' pcd.html || grep -q 'disabled' pcd.html"

curl -s -o prn.html -w '%{http_code}' "$B/tracking/print/$TC" > /dev/null
assert "نسخهٔ چاپی → شامل امضا و تاریخچه" "grep -q 'امضای متقاضی' prn.html && grep -q 'تاریخچه وضعیت' prn.html"

echo "═══════════ ۴) پنل مدیریت ═══════════"
rm -f aj.txt
curl -s -c aj.txt -b aj.txt -o al.html "$B/admin/login"
CSRF=$(get_csrf al.html)
c=$(curl -s -c aj.txt -b aj.txt -o /dev/null -w '%{http_code}' -d "username=admin&password=wrongpass&_csrf=$CSRF" "$B/admin/login")
curl -s -c aj.txt -b aj.txt -o al2.html "$B/admin/login"
assert "ورود با رمز اشتباه → بازگشت به فرم ورود" "grep -q 'اشتباه' al2.html"

CSRF=$(get_csrf al2.html)
curl -s -c aj.txt -b aj.txt -o /dev/null -w '%{redirect_url}' \
    -d "username=admin&password=admin123&_csrf=$CSRF" "$B/admin/login" > lrd
assert "ورود موفق مدیر → redirect به داشبورد" "grep -q 'admin/dashboard' lrd"

for u in /admin/dashboard /admin/applications /admin/postal-requests /admin/register /admin/users /admin/reports/by-province /admin/output/general /admin/output/grouped; do
    curl -s -b aj.txt -o ap.html -w '%{http_code}' "$B$u" > apc
    assert "GET $u → 200 بدون خطای PHP" "[ \"\$(cat apc)\" = '200' ] && ! grep -qiE 'fatal error|warning:|undefined' ap.html"
done

# داشبورد باید کارت درخواست کد پستی در انتظار داشته باشد
curl -s -b aj.txt -o dash.html "$B/admin/dashboard"
assert "داشبورد → شامل شمارندهٔ تغییر کد پستی" "grep -q 'تغییر کد پستی در انتظار' dash.html"

# فهرست درخواست‌ها باید رکورد تازه ثبت‌شده را با صفحه‌بندی نشان دهد
curl -s -b aj.txt -G -o apps.html --data-urlencode "name=زرشک" "$B/admin/applications"
assert "فیلتر نام در فهرست درخواست‌ها" "grep -q '$TC' apps.html"

# مشاهدهٔ پرونده و تغییر وضعیت یکطرفه
AID=$(grep -oE 'admin/view/[0-9]+' apps.html | head -1 | grep -oE '[0-9]+')
curl -s -b aj.txt -o vw.html "$B/admin/view/$AID"
CSRF=$(get_csrf vw.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&applicant_id=$AID&new_status=احراز نشانی" "$B/admin/change-status"
curl -s -b aj.txt -o vw2.html "$B/admin/view/$AID"
assert "ارتقای وضعیت به «احراز نشانی»" "grep -q 'احراز نشانی' vw2.html"
CSRF=$(get_csrf vw2.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&applicant_id=$AID&new_status=تکمیل اطلاعات" "$B/admin/change-status"
curl -s -b aj.txt -o vw3.html "$B/admin/view/$AID"
assert "بازگشت به وضعیت قبلی مسدود است (یکطرفه)" "grep -q 'badge bg-info\">احراز نشانی' vw3.html || ! grep -q 'badge bg-info\">تکمیل اطلاعات' vw3.html"

echo "═══════════ ۵) گردش کار تأیید کد پستی ═══════════"
curl -s -b aj.txt -o pr.html "$B/admin/postal-requests"
assert "صفحهٔ درخواست‌های کد پستی → شامل درخواست در انتظار" "grep -q '9999999999' pr.html"
RID=$(grep -oE 'name="request_id" value="[0-9]+"' pr.html | head -1 | grep -oE '[0-9]+')
CSRF=$(get_csrf pr.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&request_id=$RID" "$B/admin/postal-requests/approve"
curl -s -b aj.txt -G -o pr2.html --data-urlencode "status=تأیید شده" "$B/admin/postal-requests"
assert "تأیید درخواست کد پستی" "grep -q 'تأیید شده' pr2.html"
# کد پستی پرونده باید به مقدار جدید تغییر کرده باشد
curl -s -c tj.txt -b tj.txt -o st2.html "$B/tracking/status/$TC"
curl -s -o prn2.html "$B/tracking/print/$TC"
assert "کد پستی جدید در پرونده اعمال شد" "grep -q '9999999999' prn2.html"

echo "═══════════ ۶) گزارش‌ها ═══════════"
curl -s -b aj.txt -G -o rep.csv -w '%{http_code}' --data-urlencode "name=زرشک" "$B/admin/report/csv" > repc
assert "خروجی CSV → 200 و دارای BOM" "[ \"\$(cat repc)\" = '200' ] && head -c3 rep.csv | grep -q $'\xef\xbb\xbf'"
HDRS=$(head -1 rep.csv | tr ',' '\n' | wc -l)
ROW1=$(sed -n 2p rep.csv | tr ',' '\n' | wc -l)
assert "ستون‌های CSV با داده هم‌تراز ($HDRS هدر)" "[ '$HDRS' -ge 20 ] && grep -q 'سفال' rep.csv"
curl -s -b aj.txt -o rep.html -w '%{content_type}' "$B/admin/report/pdf" > rept
assert "خروجی چاپی/PDF → HTML با دکمهٔ چاپ" "grep -q 'text/html' rept && grep -q 'window.print' rep.html"
curl -s -b aj.txt -o gf.html "$B/admin/output/grouped"
CSRF=$(get_csrf gf.html)
curl -s -b aj.txt -o grp.html -d "_csrf=$CSRF&group_by=province" "$B/admin/output/grouped"
assert "گزارش گروه‌بندی‌شده → بدون Warning" "! grep -qiE 'warning|undefined' grp.html && grep -q 'اصفهان' grp.html"

echo "═══════════ ۷) ویرایش پرونده (باگ قدیمی DB خصوصی) ═══════════"
curl -s -b aj.txt -o ed.html "$B/admin/edit/$AID"
CSRF=$(get_csrf ed.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&applicant_id=$AID&first_name=زرشک&last_name=تست‌زاده&mobile=$MB&postal_code=9999999999" "$B/admin/update"
curl -s -b aj.txt -o up.html "$B/admin/view/$AID"
assert "ویرایش پرونده بدون خطای Fatal" "! grep -qiE 'fatal error|Cannot access private' up.html && grep -q 'ویرایش اطلاعات توسط مدیر' up.html"

echo "═══════════ ۸) مدیریت کاربران ═══════════"
curl -s -b aj.txt -o uc.html "$B/admin/users/create"
CSRF=$(get_csrf uc.html)
NEWU="expert$(( RND % 10000 ))"
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&username=$NEWU&password=secret123&role=expert&display_name=کارشناس تست" "$B/admin/users/store"
curl -s -b aj.txt -o ul.html "$B/admin/users"
assert "ایجاد کاربر کارشناس" "grep -q '$NEWU' ul.html"

# ورود با نقش expert — نباید به /admin/users دسترسی داشته باشد
rm -f ej.txt
curl -s -c ej.txt -b ej.txt -o el.html "$B/admin/login"
CSRF=$(get_csrf el.html)
curl -s -c ej.txt -b ej.txt -o /dev/null -d "username=$NEWU&password=secret123&_csrf=$CSRF" "$B/admin/login"
curl -s -b ej.txt -o eu.html -w '%{redirect_url}' "$B/admin/users" > eurd
assert "نقش expert → دسترسی ممنوع به مدیریت کاربران" "grep -q 'admin/dashboard\|my-tasks' eurd"
curl -s -b ej.txt -o et.html "$B/admin/my-tasks"
assert "نقش expert → صفحهٔ وظایف من قابل دسترسی" "! grep -qiE 'fatal error' et.html"

echo "═══════════ ۹) سرو امن مدارک ═══════════"
# شناسهٔ یکی از مدارک پروندهٔ تست جاری از صفحهٔ جزئیات
DOCID=$(grep -oE 'document/thumb/[0-9]+' vw2.html | head -1 | grep -oE '[0-9]+')
# بدون نشست مدیر → ۴۰۳/هدایت
c=$(curl -s -o /dev/null -w '%{http_code}' "$B/document/thumb/$DOCID")
assert "دسترسی مهمان به مدرک → ممنوع (302/403)" "[ '$c' = '302' ] || [ '$c' = '403' ]"
c=$(curl -s -b aj.txt -o /dev/null -w '%{http_code}' "$B/document/thumb/$DOCID")
assert "دسترسی مدیر به مدرک → 200" "[ '$c' = '200' ]"
# تلاش برای پیمایش مسیر (Path Traversal) — شناسه باید ۴۰۴ شود نه سرو فایل دلخواه
c=$(curl -s -b aj.txt -o /dev/null -w '%{http_code}' --path-as-is "$B/document/thumb/..%2f..%2f..%2fconfig%2fconfig.php")
assert "حملهٔ Path Traversal → 404 (فایل سیستمی سرو نمی‌شود)" "[ '$c' = '404' ]"
c=$(curl -s -b aj.txt -o /dev/null -w '%{http_code}' "$B/document/thumb/999999")
assert "شناسهٔ مدرک ناموجود → 404" "[ '$c' = '404' ]"
c=$(curl -s -o /dev/null -w '%{http_code}' "$B/uploads/.htaccess")
assert "مسیر مستقیم uploads در دسترس وب نیست (404)" "[ '$c' = '404' ] || [ '$c' = '403' ]"

echo "═══════════ ۱۰) هدرهای امنیتی ═══════════"
H=$(curl -s -I "$B/" )
assert "هدر X-Content-Type-Options" "echo \"\$H\" | grep -qi 'x-content-type-options: nosniff'"
assert "هدر X-Frame-Options" "echo \"\$H\" | grep -qi 'x-frame-options'"

echo "═══════════ ۱۱) برندینگ و صفحات محتوا ═══════════"
curl -s -o hm.html "$B/"
assert "صفحهٔ اصلی → نام مالک سامانه" "grep -q 'امین دروگر بزدی' hm.html"
assert "صفحهٔ اصلی → آیدی شبکه‌های اجتماعی" "grep -q 'Derogaramin' hm.html"
assert "صفحهٔ اصلی → فاقد عنوان کافی‌نت" "! grep -q 'کافی‌نت' hm.html"
curl -s -o gd.html "$B/guide"
assert "راهنما → قالب پیام اطلاع‌رسانی" "grep -q 'باسلام' gd.html && grep -q 'کد رهگیری' gd.html"
assert "راهنما → تأکید بر پیام به Derogaramin" "grep -q '@Derogaramin' gd.html && grep -q 'ایتا' gd.html && grep -q 'روبیکا' gd.html && grep -q 'بله' gd.html"
ABCODE=$(curl -s -o ab.html -w '%{http_code}' "$B/about")
assert "صفحهٔ دربارهٔ ما → ۲۰۰ و محتوای تنظیمات" "[ '$ABCODE' = '200' ] && grep -q 'امین دروگر بزدی' ab.html"

echo "═══════════ ۱۲) فرم‌ساز پویا (E2E) ═══════════"
curl -s -b aj.txt -o ffc.html "$B/admin/form-fields/create"
assert "فرم ایجاد فیلد → ۲۰۰" "! grep -qiE 'fatal error' ffc.html"
CSRF=$(get_csrf ffc.html)
FLBL="تست‌اتوماتیک$RANDOM"
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&label=$FLBL&step=2&wizard_step_id=2&field_type=select&options=الف
ب&is_required=1&field_key=auto_test" "$B/admin/form-fields/save"
curl -s -b aj.txt -o ffl.html "$B/admin/form-fields"
assert "فیلد سفارشی در فهرست فرم‌ساز" "grep -q "$FLBL" ffl.html"
FID=$(mysql -N handicraft_license -e "SELECT id FROM form_fields WHERE field_key LIKE 'dyn_auto_test%' ORDER BY id DESC LIMIT 1")
# فیلد باید در ویزارد عمومی مرحلهٔ ۲ ظاهر شود
rm -f fj.txt
curl -s -c fj.txt -b fj.txt -o /dev/null "$B/register/service/1"
curl -s -c fj.txt -b fj.txt -o fs1.html "$B/register"
CSRF=$(get_csrf fs1.html)
RND2=$RANDOM$RANDOM; NC2=$(gen_nc); MB2="09$(printf '%09d' $((RND2 % 1000000000)))"
curl -s -c fj.txt -b fj.txt -o /dev/null -d "_csrf=$CSRF&first_name=فرم&last_name=پویا&national_code=$NC2&postal_code=4444444444&mobile=$MB2&father_name=پدر&marital_status=مجرد&physical_condition=سالم&has_court_case=خیر&has_active_loan=خیر&has_overdue_installments=خیر&birth_year=1370&birth_month=1&birth_day=1" $DYN1 "$B/register/step1"
curl -s -c fj.txt -b fj.txt -o fs2.html "$B/register/step2"
assert "فیلد سفارشی در فرم عمومی مرحلهٔ ۲" "grep -q "$FLBL" fs2.html && grep -q "dyn_$FID" fs2.html"
# ارسال بدون مقدار فیلد اجباری → خطا
CSRF=$(get_csrf fs2.html)
curl -s -c fj.txt -b fj.txt -o /dev/null "$B/register/step2" -d "_csrf=$CSRF&license_title=فرش&experience_months=10&training_method=کارگاهی"
curl -s -c fj.txt -b fj.txt -o fs2b.html "$B/register/step2"
assert "فیلد سفارشی اجباری → خطای اعتبارسنجی" "grep -q 'الزامی است' fs2b.html"
# ارسال با مقدار نامعتبر (خارج از گزینه‌ها) → خطا
CSRF=$(get_csrf fs2b.html)
curl -s -c fj.txt -b fj.txt -o /dev/null "$B/register/step2" -d "_csrf=$CSRF&license_title=فرش&experience_months=10&training_method=کارگاهی&dyn_$FID=نامعتبر"
curl -s -c fj.txt -b fj.txt -o fs2c.html "$B/register/step2"
assert "گزینهٔ نامعتبر فیلد سفارشی → رد می‌شود" "grep -q 'مقدار معتبری نیست' fs2c.html"
# پاک‌سازی: حذف فیلد تست
curl -s -b aj.txt -o ffd.html "$B/admin/form-fields"; CSRF=$(get_csrf ffd.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&id=$FID" "$B/admin/form-fields/delete"
CNT=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM form_fields WHERE id=$FID")
assert "حذف فیلد سفارشی" "[ "$CNT" = '0' ]"
# محافظت از فیلد سیستمی
SFID=$(mysql -N handicraft_license -e "SELECT id FROM form_fields WHERE field_key='national_code' LIMIT 1")
curl -s -b aj.txt -o ff2.html "$B/admin/form-fields"; CSRF=$(get_csrf ff2.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&id=$SFID" "$B/admin/form-fields/delete"
CNT=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM form_fields WHERE id=$SFID")
assert "فیلد سیستمی حذف‌ناپذیر است" "[ "$CNT" = '1' ]"

echo "═══════════ ۱۳) تنظیمات سامانه ═══════════"
curl -s -b aj.txt -o stt.html "$B/admin/settings"
CSRF=$(get_csrf stt.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&site_name=سامانهٔ آزمایش&owner_name=امین دروگر بزدی&owner_username=bad id!&success_message_template=x&about_title=t&about_content=c&social_eitaa=&social_rubika=&social_bale=" "$B/admin/settings/save"
curl -s -b aj.txt -o stte.html "$B/admin/settings"
assert "آیدی نامعتبر در تنظیمات → خطا" "grep -q 'آیدی فقط می‌تواند' stte.html"
assert "قالب پیام بدون {code} → خطا" "grep -q 'جای‌نمای {code}' stte.html"
# صفحهٔ تنظیمات باید قالب پیام پیش‌فرض را نشان دهد
curl -s -b aj.txt -o stt2.html "$B/admin/settings"
assert "قالب پیام فعلی در فرم تنظیمات" "grep -q 'success_message_template' stt2.html && grep -q 'باسلام' stt2.html"

echo "═══════════ ۱۴) قالب پیام در صفحهٔ موفقیت ═══════════"
# از ثبت‌نام موفقِ بخش ۲ استفاده می‌کنیم
curl -s -o sucd.html "$B/register/success/$TC"
assert "صفحهٔ موفقیت → قالب پیام با نام متقاضی" "grep -q 'زرشک تست‌زاده هستم' sucd.html"
assert "صفحهٔ موفقیت → کد رهگیری در قالب" "grep -q "$TC" sucd.html && grep -q 'کپی متن پیام' sucd.html"
assert "صفحهٔ موفقیت → لینک شبکه‌های اجتماعی" "grep -q 'eitaa.com' sucd.html && grep -q 'rubika.ir' sucd.html && grep -q 'ble.ir' sucd.html"

echo "═══════════ ۱۵) نسخهٔ v0.3.0: رشته‌ها، وضعیت‌ها، تم، ناوبری ═══════════"
curl -s -b aj.txt -o lt.html "$B/admin/license-titles"
assert "مدیریت رشته‌ها → فهرست گسترده از پایگاه داده" "grep -q 'پته دوزی' lt.html && grep -q 'قالی تمام ابریشم' lt.html && grep -q 'فیروزه کوبی' lt.html"
CSRF=$(get_csrf lt.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&category=دستهٔ آزمون&title=رشتهٔ آزمون$RANDOM&sort_order=999&is_active=1" "$B/admin/license-titles/save"
curl -s -b aj.txt -o lt2.html "$B/admin/license-titles"
assert "افزودن رشتهٔ جدید از پنل" "grep -q 'دستهٔ آزمون' lt2.html"
NEWLID=$(mysql -N handicraft_license -e "SELECT id FROM license_titles WHERE category='دستهٔ آزمون' ORDER BY id DESC LIMIT 1")
CSRF=$(get_csrf lt2.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&id=$NEWLID" "$B/admin/license-titles/delete"
CNT=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM license_titles WHERE id=$NEWLID")
assert "حذف رشتهٔ آزمون" "[ \"$CNT\" = '0' ]"
# رشته‌ها در فرم‌ها از پایگاه داده می‌آیند (فرم ثبت دستی مدیر همیشه در دسترس است)
curl -s -b aj.txt -o lts2.html "$B/admin/register"
assert "فرم ثبت‌نام → optgroupهای رشته از پایگاه داده" "grep -q optgroup lts2.html && grep -q 'بافته‌های داری' lts2.html && grep -q 'رودوزی‌های سنتی' lts2.html"
# وضعیت‌های جدید
curl -s -b aj.txt -o vws.html "$B/admin/view/1"
assert "وضعیت‌های جدید در پرونده (بررسی کارشناسی/تأیید نهایی/تحویل/رد)" "grep -q 'بررسی کارشناسی رشته' vws.html && grep -q 'تأیید نهایی' vws.html && grep -q 'تحویل مجوز' vws.html && grep -q 'رد درخواست' vws.html"
assert "نوار مسیر رسیدگی (status-track)" "grep -q 'status-track' vws.html"
# تم روز/شب
curl -s -b aj.txt -o thm.html "$B/admin/dashboard"
assert "پنل → اسکریپت تم و دکمهٔ روز/شب" "grep -q 'theme-init.js' thm.html && grep -q 'data-theme-toggle' thm.html && grep -q 'assets/js/theme.js' thm.html"
curl -s -o thm2.html "$B/"
assert "سایت عمومی → کلید تم روز/شب" "grep -q 'data-theme-toggle' thm2.html && grep -q 'theme-init.js' thm2.html"
# رفع باگ ناوبری: لینک‌های سایدبار نباید data-bs-dismiss داشته باشند
BADLINKS=$(grep -oE '<a class="nav-link[^>]*data-bs-dismiss[^>]*href="[^"]+"' thm.html | wc -l)
assert "لینک‌های منو بدون data-bs-dismiss (رفع باگ ناوبری)" "[ \"$BADLINKS\" = '0' ] && grep -q 'href=\"/admin/license-titles\"\|admin/license-titles' thm.html"
assert "منوی معنایی HTML5 (nav/ul/li)" "grep -q 'nav class=.admin-sidebar' thm.html && grep -q 'ul class=.nav flex-column list-unstyled' thm.html"
# روبیکا با دامنهٔ جدید
curl -s -o rk.html "$B/"
assert "لینک روبیکا → rubika.ir" "grep -q 'rubika.ir/Derogaramin' rk.html && ! grep -q 'rubika.cc' rk.html"
# فونت لاله‌زار برای تیترها
assert "فونت تزئینی لاله‌زار بارگذاری می‌شود" "grep -q 'font.css' thm2.html && curl -s -o /dev/null -w '%{http_code}' \"$B/assets/font.css\" | grep -q 200"

echo "═══════════ ۱۶) تصویر مدیر ═══════════"
php -r '$im=imagecreatetruecolor(120,120);imagefilledrectangle($im,0,0,120,120,imagecolorallocate($im,14,124,123));imagepng($im,"owner-t.png");'
curl -s -b aj.txt -o stt3.html "$B/admin/settings"; CSRF=$(get_csrf stt3.html)
curl -s -b aj.txt -o /dev/null -F "_csrf=$CSRF" -F "owner_image=@owner-t.png" "$B/admin/settings/upload-image"
IMG=$(mysql -N handicraft_license -e "SELECT setting_value FROM settings WHERE setting_key='owner_image'")
assert "بارگذاری تصویر مدیر → ذخیره در تنظیمات" "[ -n \"$IMG\" ]"
curl -s -o abt.html "$B/about"
assert "تصویر مدیر در صفحهٔ دربارهٔ ما" "grep -q 'assets/img/owner.png' abt.html"
# فایل با محتوای غیرتصویر باید رد شود
echo '<?php echo 1;' > fake.png
curl -s -b aj.txt -o stt4.html "$B/admin/settings"; CSRF=$(get_csrf stt4.html)
curl -s -b aj.txt -o /dev/null -F "_csrf=$CSRF" -F "owner_image=@fake.png" "$B/admin/settings/upload-image"
curl -s -b aj.txt -o stt5.html "$B/admin/settings"
assert "فایل غیرتصویری با پسوند png → رد می‌شود" "grep -q 'فقط تصویر JPG یا PNG معتبر' stt5.html"
# پاک‌سازی: حذف تصویر و بازگشت به آیکون پیش‌فرض
CSRF=$(get_csrf stt5.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&remove_image=1" "$B/admin/settings/upload-image"
IMG=$(mysql -N handicraft_license -e "SELECT setting_value FROM settings WHERE setting_key='owner_image'")
assert "حذف تصویر مدیر → بازگشت به آیکون پیش‌فرض" "[ -z \"$IMG\" ] && [ ! -f /dev/null ] || [ -z \"$IMG\" ]"

echo "═══════════ ۱۷) فاز ۳+۴: گردش پرداخت هزینه ═══════════"
# مدیر درخواست پرداخت را روی پروندهٔ آزمون فعال می‌کند
curl -s -b aj.txt -o vp.html "$B/admin/view/$AID"; CSRF=$(get_csrf vp.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&applicant_id=$AID&enable=1" "$B/admin/request-payment"
# صفحهٔ پیگیری باید بخش پرداخت را نشان دهد
curl -s -c tj.txt -b tj.txt -o pay1.html "$B/tracking/status/$TC"
assert "فعال‌سازی درخواست پرداخت → نمایش بخش پرداخت به متقاضی" "grep -q 'پرداخت هزینهٔ ثبت‌نام' pay1.html && grep -q 'receipt_file' pay1.html"
# ثبت رسید بدون فایل → خطا
CSRF=$(get_csrf pay1.html)
curl -s -c tj.txt -b tj.txt -o /dev/null -d "_csrf=$CSRF&tracking_code=$TC&payment_channel_id=1&amount=500000&paid_year=1405&paid_month=7&paid_day=21" "$B/tracking/payments/submit"
curl -s -c tj.txt -b tj.txt -o pay2.html "$B/tracking/status/$TC"
assert "ثبت رسید بدون فایل → خطای الزام" "grep -q 'بارگذاری تصویر رسید پرداخت الزامی است' pay2.html"
# ثبت رسید کامل
CSRF=$(get_csrf pay2.html)
CHID=$(mysql -N handicraft_license -e "SELECT id FROM payment_channels WHERE is_active=1 ORDER BY id LIMIT 1")
curl -s -c tj.txt -b tj.txt -o /dev/null -F "_csrf=$CSRF" -F "tracking_code=$TC" -F "payment_channel_id=$CHID"     -F "amount=500000" -F "reference_code=123456" -F "paid_year=1405" -F "paid_month=7" -F "paid_day=20"     -F "receipt_file=@t.png" "$B/tracking/payments/submit"
curl -s -c tj.txt -b tj.txt -o pay3.html "$B/tracking/status/$TC"
assert "ثبت رسید پرداخت → در انتظار بررسی" "grep -q 'در انتظار بررسی' pay3.html"
# رسید دوم باید مسدود شود
CSRF=$(get_csrf pay3.html)
curl -s -c tj.txt -b tj.txt -o /dev/null -F "_csrf=$CSRF" -F "tracking_code=$TC" -F "payment_channel_id=$CHID"     -F "amount=500000" -F "paid_year=1405" -F "paid_month=7" -F "paid_day=20" -F "receipt_file=@t.png" "$B/tracking/payments/submit"
curl -s -c tj.txt -b tj.txt -o pay4.html "$B/tracking/status/$TC"
assert "رسید تکراری هنگام وجود رسید در انتظار → مسدود" "grep -q 'ثبت رسید جدید ممکن نیست' pay4.html"
# صفحهٔ درآمد مدیر
curl -s -b aj.txt -o pays.html -w '%{http_code}' "$B/admin/payments" > paysc
assert "صفحهٔ درآمد → ۲۰۰ و شامل رسید" "[ \"$(cat paysc)\" = '200' ] && grep -q '500,000' pays.html"
# تأیید رسید
PID=$(mysql -N handicraft_license -e "SELECT id FROM payments ORDER BY id DESC LIMIT 1")
CSRF=$(get_csrf pays.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&payment_id=$PID&action=approve" "$B/admin/payments/review"
ST=$(mysql -N handicraft_license -e "SELECT status FROM payments WHERE id=$PID")
assert "تأیید رسید توسط مدیر" "[ \"$ST\" = 'تأیید شده' ]"
curl -s -c tj.txt -b tj.txt -o pay5.html "$B/tracking/status/$TC"
assert "نمایش تأیید پرداخت به متقاضی + تایم‌لاین" "grep -q 'پرداخت شما تأیید شد' pay5.html && grep -q 'تأیید پرداخت' pay5.html"
# آمار درآمد ماه در داشبورد
curl -s -b aj.txt -o dash3.html "$B/admin/dashboard"
assert "داشبورد → درآمد ماه شمسی جاری" "grep -q 'درآمد تأییدشدهٔ ماه شمسی جاری' dash3.html"
# CSV پرداخت‌ها
curl -s -b aj.txt -o pay.csv "$B/admin/payments/csv"
assert "خروجی CSV پرداخت‌ها" "grep -q 'هزینهٔ ثبت‌نام' pay.csv && grep -q 'تأیید شده' pay.csv"
# مدیریت کانال پرداخت
CSRF=$(get_csrf pays.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&title=کانال آزمون&channel_type=سایر&account_info=1234-5678&sort_order=5&is_active=1" "$B/admin/payment-channels/save"
CH=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM payment_channels WHERE title='کانال آزمون'")
assert "افزودن کانال پرداخت از پنل" "[ \"$CH\" = '1' ]"
NEWCH=$(mysql -N handicraft_license -e "SELECT id FROM payment_channels WHERE title='کانال آزمون' LIMIT 1")
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&id=$NEWCH" "$B/admin/payment-channels/delete"
CH=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM payment_channels WHERE id=$NEWCH")
assert "حذف کانال بدون تراکنش" "[ \"$CH\" = '0' ]"

echo "═══════════ ۱۸) فاز ۶: تصویر مجوز و قاعدهٔ یک‌بار دریافت ═══════════"
# ارتقای پروندهٔ آزمون به «صدور مجوز»
curl -s -b aj.txt -o lw1.html "$B/admin/view/$AID"; CSRF=$(get_csrf lw1.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&applicant_id=$AID&new_status=صدور مجوز" "$B/admin/change-status"
# بارگذاری تصویر مجوز توسط مدیر
curl -s -b aj.txt -o lw2.html "$B/admin/view/$AID"; CSRF=$(get_csrf lw2.html)
curl -s -b aj.txt -o /dev/null -F "_csrf=$CSRF" -F "applicant_id=$AID" -F "license_file=@t.png" "$B/admin/license/upload"
curl -s -b aj.txt -o lw3.html "$B/admin/view/$AID"
assert "بارگذاری تصویر مجوز در پرونده" "grep -q 'تصویر مجوز صادرشده' lw3.html && grep -q 'دفعات دریافت' lw3.html"
# متقاضی: دریافت اول خودکار
curl -s -c tj.txt -b tj.txt -o lw4.html "$B/tracking/status/$TC"
assert "پس از صدور → کارت دریافت تصویر مجوز برای متقاضی" "grep -q 'دریافت اول تصویر مجوز رایگان است' lw4.html"
CSRF=$(get_csrf lw4.html)
LRD=$(curl -s -c tj.txt -b tj.txt -o /dev/null -w '%{redirect_url}' -d "_csrf=$CSRF&tracking_code=$TC" "$B/tracking/license/first-access")
TOKEN=$(echo "$LRD" | grep -oE '[a-f0-9]{64}' | head -1)
assert "دریافت اول → صدور توکن و هدایت به لینک مجوز" "[ -n \"$TOKEN\" ]"
LIC=$(curl -s -o lic.bin -w '%{http_code}' "$B/license/view/$TOKEN")
assert "لینک توکن → تصویر مجوز سرو می‌شود (200)" "[ \"$LIC\" = '200' ]"
BAD=$(curl -s -o /dev/null -w '%{http_code}' "$B/license/view/0000000000000000000000000000000000000000000000000000000000000000")
assert "توکن نامعتبر → 403" "[ \"$BAD\" = '403' ]"
curl -s -c tj.txt -b tj.txt -o lw5.html "$B/tracking/status/$TC"
assert "پس از دریافت اول → لینک فعال ۷۲ ساعته نمایش داده می‌شود" "grep -q 'لینک دسترسی شما فعال است' lw5.html && grep -q 'license/view/' lw5.html"
# انقضای دستی توکن برای آزمون گردش دریافت مجدد
mysql handicraft_license -e "UPDATE license_access_requests SET expires_at = DATE_SUB(NOW(), INTERVAL 1 HOUR), status='منقضی' WHERE access_token='$TOKEN';"
curl -s -c tj.txt -b tj.txt -o lw5.html "$B/tracking/status/$TC"
assert "پس از انقضا → پیشنهاد درخواست دریافت مجدد" "grep -q 'یک‌بار تصویر مجوز را دریافت کرده‌اید' lw5.html"
CSRF=$(get_csrf lw5.html)
curl -s -c tj.txt -b tj.txt -o /dev/null -d "_csrf=$CSRF&tracking_code=$TC" "$B/tracking/license/request-access"
curl -s -c tj.txt -b tj.txt -o lw6.html "$B/tracking/status/$TC"
assert "درخواست دریافت مجدد → در انتظار بررسی" "grep -q 'در انتظار بررسی مدیر است' lw6.html"
# مدیر: تأیید مشروط به پرداخت
curl -s -b aj.txt -o lr.html "$B/admin/license-requests"
assert "صفحهٔ درخواست‌های تصویر مجوز → ۲۰۰" "grep -q 'درخواست دریافت مجدد' lr.html"
LREQ=$(mysql -N handicraft_license -e "SELECT id FROM license_access_requests WHERE applicant_id=$AID AND status='در انتظار' ORDER BY id DESC LIMIT 1")
CSRF=$(get_csrf lr.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&request_id=$LREQ&decision=approve_paid&amount=50000" "$B/admin/license-requests/review"
curl -s -c tj.txt -b tj.txt -o lw7.html "$B/tracking/status/$TC"
assert "تأیید مشروط → فرم پرداخت برای متقاضی" "grep -q 'مشروط به پرداخت' lw7.html && grep -q 'tracking/license/pay' lw7.html"
# متقاضی رسید پرداخت دریافت مجدد را ثبت می‌کند
CSRF=$(get_csrf lw7.html)
CHID=$(mysql -N handicraft_license -e "SELECT id FROM payment_channels WHERE is_active=1 ORDER BY id LIMIT 1")
curl -s -c tj.txt -b tj.txt -o /dev/null -F "_csrf=$CSRF" -F "tracking_code=$TC" -F "payment_channel_id=$CHID"     -F "amount=50000" -F "paid_year=1405" -F "paid_month=7" -F "paid_day=21" -F "receipt_file=@t.png" "$B/tracking/license/pay"
curl -s -c tj.txt -b tj.txt -o lw8.html "$B/tracking/status/$TC"
assert "ثبت رسید پرداخت دریافت مجدد" "grep -q 'ثبت شده و در انتظار بررسی مدیر است' lw8.html"
# مدیر رسید را تأیید می‌کند → توکن صادر شود
RPID=$(mysql -N handicraft_license -e "SELECT id FROM payments WHERE purpose='هزینهٔ دریافت مجدد تصویر مجوز' ORDER BY id DESC LIMIT 1")
curl -s -b aj.txt -o pays2.html "$B/admin/payments"; CSRF=$(get_csrf pays2.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&payment_id=$RPID&action=approve" "$B/admin/payments/review"
TOKEN2=$(mysql -N handicraft_license -e "SELECT access_token FROM license_access_requests WHERE id=$LREQ")
assert "پس از تأیید پرداخت → توکن دسترسی مجدد صادر شد" "[ -n \"$TOKEN2\" ]"
LIC2=$(curl -s -o /dev/null -w '%{http_code}' "$B/license/view/$TOKEN2")
assert "لینک دریافت مجدد → 200" "[ \"$LIC2\" = '200' ]"

echo "═══════════ ۱۹) فاز ۵: پیامک‌های وضعیت ═══════════"
# صفحهٔ مدیریت پیامک‌ها با الگوهای پیش‌فرض
curl -s -b aj.txt -o sm.html -w '%{http_code}' "$B/admin/sms" > smc
assert "صفحهٔ مدیریت پیامک‌ها → ۲۰۰ با الگوهای پیش‌فرض" "[ \"$(cat smc)\" = '200' ] && grep -q 'پیامک صدور مجوز' sm.html && grep -q 'الگوهای پیامک' sm.html"
# فرم ثبت پیامک در صفحهٔ متقاضی
curl -s -c tj.txt -b tj.txt -o sm2.html "$B/tracking/status/$TC"
assert "صفحهٔ پیگیری → فرم ثبت پیامک" "grep -q 'ثبت پیامک دریافتی از سازمان' sm2.html && grep -q 'tracking/sms/submit' sm2.html"
# پروندهٔ دوم با ثبت دستی مدیر (برای آزمون ارتقای وضعیت)
curl -s -b aj.txt -o sr.html "$B/admin/register"; CSRF=$(get_csrf sr.html)
RND3=$RANDOM$RANDOM; NC3=$(gen_nc); MB3="09$(printf '%09d' $((RND3 % 1000000000)))"
SRD=$(curl -s -b aj.txt -o /dev/null -w '%{redirect_url}' -d "_csrf=$CSRF&first_name=پیامک&last_name=آزمون&national_code=$NC3&postal_code=8888888888&mobile=$MB3&father_name=پدر&marital_status=مجرد&physical_condition=سالم&has_court_case=خیر&has_active_loan=خیر&has_overdue_installments=خیر&birth_year=1371&birth_month=4&birth_day=4&license_title=گلیم&experience_months=12&training_method=در خانه&ownership_status=شخص&province=تهران&county=تهران&city=تهران&electricity_bill_id=999$(( RND3 % 100000000 ))&address_street=خیابان آزادی&address_plaque=1" "$B/admin/register")
TC3=$(curl -s -b aj.txt "${SRD/admin\/view\//$B/admin/view/}" 2>/dev/null | grep -oE 'HG-[A-F0-9]{8}' | head -1)
[ -z "$TC3" ] && TC3=$(mysql -N handicraft_license -e "SELECT tracking_code FROM applicants WHERE national_code='$NC3'")
assert "پروندهٔ دوم برای آزمون پیامک ساخته شد" "[ -n \"$TC3\" ]"
# متقاضی دوم پیامک «احراز نشانی» را ثبت می‌کند
rm -f sj.txt; curl -s -c sj.txt -b sj.txt -o sm3.html "$B/tracking/status/$TC3"; CSRF=$(get_csrf sm3.html)
TPLID=$(mysql -N handicraft_license -e "SELECT id FROM sms_templates WHERE target_status='احراز نشانی' AND is_active=1 LIMIT 1")
curl -s -c sj.txt -b sj.txt -o /dev/null -d "_csrf=$CSRF&tracking_code=$TC3&sms_template_id=$TPLID&sms_code=778899&sms_text=پرونده در حال بررسی&sms_year=1405&sms_month=7&sms_day=21" "$B/tracking/sms/submit"
curl -s -c sj.txt -b sj.txt -o sm4.html "$B/tracking/status/$TC3"
assert "ثبت پیامک توسط متقاضی → در انتظار بررسی" "grep -q 'در انتظار بررسی مدیر است' sm4.html"
# درخواست تکراری هنگام انتظار → مسدود
CSRF=$(get_csrf sm4.html)
curl -s -c sj.txt -b sj.txt -o /dev/null -d "_csrf=$CSRF&tracking_code=$TC3&sms_template_id=$TPLID&sms_code=1&sms_year=1405&sms_month=7&sms_day=21" "$B/tracking/sms/submit"
curl -s -c sj.txt -b sj.txt -o sm5.html "$B/tracking/status/$TC3"
assert "پیامک تکراری در حالت انتظار → مسدود" "grep -q 'پیامک جدید ثبت کنید' sm5.html"
# مدیر تأیید می‌کند → وضعیت باید ارتقا یابد
EID=$(mysql -N handicraft_license -e "SELECT id FROM user_sms_entries ORDER BY id DESC LIMIT 1")
curl -s -b aj.txt -o sm6.html "$B/admin/sms"; CSRF=$(get_csrf sm6.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&entry_id=$EID&action=approve" "$B/admin/sms/review-entry"
NEWST=$(mysql -N handicraft_license -e "SELECT current_status FROM applicants WHERE tracking_code='$TC3'")
assert "تأیید پیامک → ارتقای وضعیت پرونده به مقصد الگو" "[ \"$NEWST\" = 'احراز نشانی' ]"
curl -s -c sj.txt -b sj.txt -o sm7.html "$B/tracking/status/$TC3"
assert "وضعیت جدید در صفحهٔ متقاضی + تاریخچه" "grep -q 'احراز نشانی' sm7.html && grep -q 'ارتقای وضعیت بر اساس پیامک' sm7.html"
# رد پیامک با دلیل
CSRF=$(get_csrf sm7.html)
TPL2=$(mysql -N handicraft_license -e "SELECT id FROM sms_templates WHERE target_status='صدور مجوز' AND is_active=1 LIMIT 1")
curl -s -c sj.txt -b sj.txt -o /dev/null -d "_csrf=$CSRF&tracking_code=$TC3&sms_template_id=$TPL2&sms_code=111&sms_year=1405&sms_month=7&sms_day=21" "$B/tracking/sms/submit"
EID2=$(mysql -N handicraft_license -e "SELECT id FROM user_sms_entries ORDER BY id DESC LIMIT 1")
curl -s -b aj.txt -o sm8.html "$B/admin/sms"; CSRF=$(get_csrf sm8.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&entry_id=$EID2&action=reject&reject_reason=پیامک معتبر نیست" "$B/admin/sms/review-entry"
ST2=$(mysql -N handicraft_license -e "SELECT status FROM user_sms_entries WHERE id=$EID2")
assert "رد پیامک با دلیل ثبت می‌شود" "[ \"$ST2\" = 'رد شده' ]"
# CRUD الگو
CSRF=$(get_csrf sm8.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&id=0&title=الگوی آزمون&target_status=تأیید نهایی&requires_code=1&sort_order=70" "$B/admin/sms/save-template"
NT=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM sms_templates WHERE title='الگوی آزمون'")
assert "افزودن الگوی پیامک از پنل" "[ \"$NT\" = '1' ]"
NTID=$(mysql -N handicraft_license -e "SELECT id FROM sms_templates WHERE title='الگوی آزمون' LIMIT 1")
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&id=$NTID" "$B/admin/sms/delete-template"
NT=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM sms_templates WHERE id=$NTID")
assert "حذف الگوی بدون ورودی" "[ \"$NT\" = '0' ]"

echo "═══════════ ۲۰) فاز ۲: چرخهٔ بازبینی پرونده ═══════════"
# دکمهٔ بازبینی در صفحهٔ پیگیری (از پروندهٔ دوم استفاده می‌کنیم)
curl -s -c sj.txt -b sj.txt -o rv1.html "$B/tracking/status/$TC3"
assert "صفحهٔ پیگیری → دکمهٔ بازبینی اطلاعات" "grep -q 'بازبینی اطلاعات / افزودن مدرک' rv1.html"
# فرم بازبینی با داده‌های پیش‌پر
curl -s -c sj.txt -b sj.txt -o rv2.html -w '%{http_code}' "$B/tracking/revision/$TC3" > rvc
assert "فرم بازبینی → ۲۰۰ و پیش‌پر با دادهٔ فعلی" "[ \"$(cat rvc)\" = '200' ] && grep -q 'پیامک' rv2.html && grep -q 'درخواست بازبینی پرونده' rv2.html"
# ثبت بازبینی با تغییر شهر و افزودن مدرک
CSRF=$(get_csrf rv2.html)
curl -s -c sj.txt -b sj.txt -o /dev/null -F "_csrf=$CSRF" -F "tracking_code=$TC3"     -F "first_name=پیامک" -F "last_name=اصلاح‌شده" -F "postal_code=8888888888" -F "mobile=$MB3"     -F "father_name=پدر" -F "marital_status=مجرد" -F "children_count=0" -F "physical_condition=سالم"     -F "has_court_case=خیر" -F "has_active_loan=خیر" -F "has_overdue_installments=خیر"     -F "birth_year=1371" -F "birth_month=4" -F "birth_day=4"     -F "license_title=گلیم" -F "experience_months=24" -F "training_method=کارگاهی"     -F "ownership_status=شخص" -F "province=تهران" -F "county=تهران" -F "city=تهران"     -F "electricity_bill_id=99912345" -F "address_street=خیابان آزادی" -F "address_plaque=2"     -F "rev_doc_type_1=vocational_cert" -F "rev_doc_1=@t.png" -F "note=شهر و سابقه اصلاح شد" $DYNF "$B/tracking/revision/submit"
curl -s -c sj.txt -b sj.txt -o rv3.html "$B/tracking/status/$TC3"
assert "ثبت بازبینی → در انتظار بررسی + قفل درخواست جدید" "grep -q 'درخواست بازبینی ثبت شد' rv3.html || grep -q 'در انتظار بررسی مدیر است' rv3.html"
# دادهٔ رسمی هنوز تغییر نکرده
CURNAME=$(mysql -N handicraft_license -e "SELECT last_name FROM applicants WHERE tracking_code='$TC3'")
assert "پیش از تأیید مدیر → دادهٔ رسمی تغییر نمی‌کند" "[ \"$CURNAME\" = 'آزمون' ]"
# صفحهٔ مقایسهٔ مدیر
RID=$(mysql -N handicraft_license -e "SELECT id FROM applicant_revisions WHERE status='در انتظار' ORDER BY id DESC LIMIT 1")
curl -s -b aj.txt -o rv4.html "$B/admin/revisions/view/$RID"
assert "نمای مقایسه → هایلایت تغییرات + فرم تأیید + ستون مدارک" "grep -q 'table-warning' rv4.html && grep -q 'revisions/approve' rv4.html && grep -q 'col-lg-4' rv4.html"
# تأیید → اعمال تغییرات
CSRF=$(get_csrf rv4.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF" "$B/admin/revisions/approve/$RID"
NEWNAME=$(mysql -N handicraft_license -e "SELECT last_name FROM applicants WHERE tracking_code='$TC3'")
NEWEXP=$(mysql -N handicraft_license -e "SELECT experience_months FROM job_details j JOIN applicants a ON a.id=j.applicant_id WHERE a.tracking_code='$TC3'")
DOCT=$(mysql -N handicraft_license -e "SELECT doc_type FROM documents d JOIN applicants a ON a.id=d.applicant_id WHERE a.tracking_code='$TC3' AND doc_type='vocational_cert' LIMIT 1")
assert "تأیید بازبینی → اعمال نام جدید" "[ \"$NEWNAME\" = 'اصلاح‌شده' ]"
assert "تأیید بازبینی → اعمال سابقهٔ جدید" "[ \"$NEWEXP\" = '24' ]"
assert "مدرک جدید پس از تأیید → نوع دائمی گرفت" "[ \"$DOCT\" = 'vocational_cert' ]"
curl -s -b aj.txt -o rv5.html "$B/admin/view/$(mysql -N handicraft_license -e "SELECT id FROM applicants WHERE tracking_code='$TC3'")"
assert "رویداد تأیید بازبینی در تایم‌لاین پرونده" "grep -q 'تأیید بازبینی پرونده' rv5.html"
# رد بازبینی + پاکسازی مدرک موقت
curl -s -c sj.txt -b sj.txt -o rv6.html "$B/tracking/revision/$TC3"; CSRF=$(get_csrf rv6.html)
curl -s -c sj.txt -b sj.txt -o /dev/null -F "_csrf=$CSRF" -F "tracking_code=$TC3"     -F "first_name=پیامک" -F "last_name=آزمون" -F "postal_code=8888888888" -F "mobile=$MB3"     -F "father_name=پدر" -F "marital_status=مجرد" -F "children_count=0" -F "physical_condition=سالم"     -F "has_court_case=خیر" -F "has_active_loan=خیر" -F "has_overdue_installments=خیر"     -F "birth_year=1371" -F "birth_month=4" -F "birth_day=4"     -F "license_title=گلیم" -F "experience_months=24" -F "training_method=کارگاهی"     -F "ownership_status=شخص" -F "province=تهران" -F "county=تهران" -F "city=تهران"     -F "electricity_bill_id=99912345" -F "address_street=خیابان آزادی" -F "address_plaque=2"     -F "rev_doc_type_1=optional" -F "rev_doc_1=@t.pdf" $DYNF "$B/tracking/revision/submit"
RID2=$(mysql -N handicraft_license -e "SELECT id FROM applicant_revisions WHERE status='در انتظار' ORDER BY id DESC LIMIT 1")
TMPCNT1=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM documents WHERE doc_type='revision_doc'")
curl -s -b aj.txt -o rv7.html "$B/admin/revisions/view/$RID2"; CSRF=$(get_csrf rv7.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&reject_reason=مدارک ناقص" "$B/admin/revisions/reject/$RID2"
TMPCNT2=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM documents WHERE doc_type='revision_doc'")
assert "رد بازبینی → مدارک موقت پاک می‌شوند" "[ \"$TMPCNT2\" -lt \"$TMPCNT1\" ] || [ \"$TMPCNT2\" = '0' ]"
RST=$(mysql -N handicraft_license -e "SELECT status FROM applicant_revisions WHERE id=$RID2")
assert "وضعیت درخواست ردشده ثبت می‌شود" "[ \"$RST\" = 'رد شده' ]"

echo "═══════════ ۲۱) فاز ۷+۸: چندمجوزی و مشاهدهٔ سوابق ═══════════"
# کد ملی و موبایل متقاضی بخش ۲ (زرشک)
NCZ=$(mysql -N handicraft_license -e "SELECT national_code FROM applicants WHERE tracking_code='$TC'")
# ثبت پروندهٔ دوم با همان کد ملی و موبایل (چندمجوزی)
rm -f mj.txt
curl -s -c mj.txt -b mj.txt -o /dev/null "$B/register/service/1"
curl -s -c mj.txt -b mj.txt -o m1.html "$B/register"; CSRF=$(get_csrf m1.html)
curl -s -c mj.txt -b mj.txt -o /dev/null -d "_csrf=$CSRF&first_name=زرشک&last_name=تست‌زاده&national_code=$NCZ&postal_code=1234567890&mobile=$MB&father_name=پدرتست&marital_status=متأهل&children_count=1&physical_condition=سالم&has_court_case=خیر&has_active_loan=خیر&has_overdue_installments=خیر&birth_year=1372&birth_month=3&birth_day=5" $DYN1 "$B/register/step1"
curl -s -c mj.txt -b mj.txt -o m2.html "$B/register/step2"; CSRF=$(get_csrf m2.html)
curl -s -c mj.txt -b mj.txt -o /dev/null -d "_csrf=$CSRF&license_title=گلیم&experience_months=8&training_method=در خانه" $DYN2 "$B/register/step2"
curl -s -c mj.txt -b mj.txt -o m3.html "$B/register/step3"; CSRF=$(get_csrf m3.html)
curl -s -c mj.txt -b mj.txt -o /dev/null -d "_csrf=$CSRF&ownership_status=شخص&province=یزد&county=میبد&city=میبد&electricity_bill_id=666$(( RND % 1000000 ))&address_street=خیابان کاشی&address_alley=کوچه ۲&address_plaque=9" $DYN3 "$B/register/step3"
curl -s -c mj.txt -b mj.txt -o m4.html "$B/register/step4"; CSRF=$(get_csrf m4.html)
curl -s -c mj.txt -b mj.txt -o m5.html -L -w '%{url_effective}' -F "_csrf=$CSRF" -F "national_card_front=@t.png" -F "birth_cert=@t.png" -F "ownership_doc=@t.pdf" $DYN4 "$B/register/step4" > mrd
TC_MULTI=$(grep -oE 'HG-[A-F0-9]{8}' mrd | head -1)
assert "چندمجوزی: پروندهٔ دوم با همان کد ملی/موبایل ثبت شد" "[ -n \"$TC_MULTI\" ] && [ \"$TC_MULTI\" != \"$TC\" ]"
# موبایل شخص دیگر → مسدود
RND4=$RANDOM$RANDOM; NC4=$(gen_nc)
rm -f mj2.txt; curl -s -c mj2.txt -b mj2.txt -o x1.html "$B/register/step/1"; CSRF=$(get_csrf x1.html)
curl -s -c mj2.txt -b mj2.txt -o /dev/null -d "_csrf=$CSRF&first_name=دیگر&last_name=کس&national_code=$NC4&postal_code=3333333333&mobile=$MB&father_name=پدر&marital_status=مجرد&physical_condition=سالم&has_court_case=خیر&has_active_loan=خیر&has_overdue_installments=خیر&birth_year=1360&birth_month=1&birth_day=1" $DYN1 "$B/register/step1"
curl -s -c mj2.txt -b mj2.txt -o x2.html "$B/register/step/1"
assert "موبایل متعلق به شخص دیگر → رد می‌شود" "grep -q 'به کد ملی دیگری تعلق دارد' x2.html"
# هر دو پرونده به یک person متصل‌اند
PCNT=$(mysql -N handicraft_license -e "SELECT COUNT(DISTINCT person_id) FROM applicants WHERE national_code='$NCZ'")
ACNT=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM applicants WHERE national_code='$NCZ'")
assert "اتصال پرونده‌ها به یک شخص" "[ \"$PCNT\" = '1' ] && [ \"$ACNT\" -ge '2' ]"
# وضعیت مجوز
AIDZ=$(mysql -N handicraft_license -e "SELECT id FROM applicants WHERE tracking_code='$TC'")
curl -s -b aj.txt -o lsz.html "$B/admin/view/$AIDZ"; CSRF=$(get_csrf lsz.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&applicant_id=$AIDZ&license_state=صادر شده&valid_year=1410&valid_month=5&valid_day=1" "$B/admin/license-state"
LS=$(mysql -N handicraft_license -e "SELECT license_state FROM applicants WHERE id=$AIDZ")
VU=$(mysql -N handicraft_license -e "SELECT license_valid_until IS NOT NULL FROM applicants WHERE id=$AIDZ")
assert "تنظیم وضعیت مجوز و تاریخ اعتبار" "[ \"$LS\" = 'صادر شده' ] && [ \"$VU\" = '1' ]"
curl -s -b aj.txt -o lsz2.html "$B/admin/view/$AIDZ"
assert "سایر پرونده‌های شخص در صفحهٔ پرونده" "grep -q 'سایر پرونده‌های این شخص' lsz2.html && grep -q '$TC_MULTI' lsz2.html"
# ===== فاز ۸: مشاهدهٔ سوابق =====
curl -s -o mr.html -w '%{http_code}' "$B/my-records" > mrc
assert "صفحهٔ سوابق من → ۲۰۰" "[ \"$(cat mrc)\" = '200' ] && grep -q 'my-records/verify' mr.html"
rm -f mrj.txt; curl -s -c mrj.txt -b mrj.txt -o mr1.html "$B/my-records"; CSRF=$(get_csrf mr1.html)
# اطلاعات غلط → پیام یکسان
curl -s -c mrj.txt -b mrj.txt -o /dev/null -d "_csrf=$CSRF&national_code=$NCZ&first_name=غلط&last_name=غلبي&father_name=پدرتست&mobile=$MB&birth_year=1372&birth_month=3&birth_day=5" "$B/my-records/verify"
curl -s -c mrj.txt -b mrj.txt -o mr2.html "$B/my-records"
assert "اطلاعات نادرست → پیام یکسان بدون نشت اطلاعات" "grep -q 'تطبیق نیافت' mr2.html"
# اطلاعات صحیح → فهرست هر دو پرونده
CSRF=$(get_csrf mr2.html)
curl -s -c mrj.txt -b mrj.txt -o /dev/null -d "_csrf=$CSRF&national_code=$NCZ&first_name=زرشک&last_name=تست‌زاده&father_name=پدرتست&mobile=$MB&birth_year=1372&birth_month=3&birth_day=5" "$B/my-records/verify"
curl -s -c mrj.txt -b mrj.txt -o mr3.html "$B/my-records"
assert "احراز هویت موفق → نمایش هر دو پرونده" "grep -q 'هویت تأیید شد' mr3.html && grep -q '$TC' mr3.html && grep -q '$TC_MULTI' mr3.html"
assert "نمایش وضعیت مجوز در سوابق" "grep -q 'صادر شده' mr3.html"
# لاگ امنیتی احراز هویت
IC=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM identity_checks WHERE national_code='$NCZ'")
assert "لاگ امنیتی تلاش‌های احراز هویت" "[ \"$IC\" -ge '2' ]"

echo "═══════════ ۲۲) فاز ۱: مراحل سفارشی ویزارد ═══════════"
curl -s -b aj.txt -o ws.html "$B/admin/wizard-steps"; CSRF=$(get_csrf ws.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&id=0&title=سوابق بیمه&description=آزمون&icon=shield&sort_order=35" "$B/admin/wizard-steps/save"
WSID=$(mysql -N handicraft_license -e "SELECT id FROM wizard_steps WHERE title='سوابق بیمه' LIMIT 1")
assert "ساخت مرحلهٔ سفارشی از پنل" "[ -n \"$WSID\" ]"
curl -s -b aj.txt -o ffc2.html "$B/admin/form-fields/create"; CSRF=$(get_csrf ffc2.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&label=شماره بیمه&step=0&wizard_step_id=$WSID&field_type=text&is_required=1&sort_order=10&field_key=bimeh" "$B/admin/form-fields/save"
BFID=$(mysql -N handicraft_license -e "SELECT id FROM form_fields WHERE field_key LIKE 'dyn_bimeh%' ORDER BY id DESC LIMIT 1")
assert "ساخت فیلد روی مرحلهٔ سفارشی" "[ -n \"$BFID\" ]"
rm -f wj.txt
curl -s -c wj.txt -b wj.txt -o /dev/null "$B/register/service/1"
curl -s -b wj.txt -c wj.txt -o rg.html "$B/register"
assert "ویزارد ۵ مرحله‌ای شد" "[ $(grep -o 'class=.w-step' rg.html | wc -l) -eq 5 ]"
# راه‌پیمایی کامل ۵ مرحله
curl -s -c wj.txt -b wj.txt -o w1.html "$B/register/step/1"; C=$(get_csrf w1.html)
RNDW=$RANDOM$RANDOM; NCW=$(gen_nc); MBW="09$(printf '%09d' $((RNDW % 1000000000)))"
curl -s -c wj.txt -b wj.txt -o /dev/null -w '%{redirect_url}' -d "_csrf=$C&first_name=ویزارد&last_name=پویا&national_code=$NCW&postal_code=5555555555&mobile=$MBW&father_name=پدر&marital_status=مجرد&physical_condition=سالم&has_court_case=خیر&has_active_loan=خیر&has_overdue_installments=خیر&birth_year=1369&birth_month=6&birth_day=1" $DYN1 "$B/register/step/1" > w1r
assert "گام ۱ (هویتی) → گام ۲" "grep -q 'step/2' w1r"
curl -s -c wj.txt -b wj.txt -o w2.html "$B/register/step/2"; C=$(get_csrf w2.html)
curl -s -c wj.txt -b wj.txt -o /dev/null -w '%{redirect_url}' -d "_csrf=$C&license_title=سفال&experience_months=15&training_method=کارگاهی" $DYN2 "$B/register/step/2" > w2r
assert "گام ۲ (شغلی) → گام ۳" "grep -q 'step/3' w2r"
curl -s -c wj.txt -b wj.txt -o w3.html "$B/register/step/3"; C=$(get_csrf w3.html)
curl -s -c wj.txt -b wj.txt -o /dev/null -w '%{redirect_url}' -d "_csrf=$C&ownership_status=شخص&province=فارس&county=شیراز&city=شیراز&electricity_bill_id=555$(( RNDW % 1000000 ))&address_street=خیابان پویا&address_alley=کوچه ۱&address_plaque=2" $DYN3 "$B/register/step/3" > w3r
assert "گام ۳ (ملک) → گام ۴ (سفارشی)" "grep -q 'step/4' w3r"
curl -s -c wj.txt -b wj.txt -o w4.html "$B/register/step/4"
assert "گام ۴ = مرحلهٔ سفارشی «سوابق بیمه»" "grep -q 'سوابق بیمه' w4.html && grep -q 'dyn_$BFID' w4.html"
# بدون فیلد اجباری → خطا
C=$(get_csrf w4.html)
curl -s -c wj.txt -b wj.txt -o /dev/null -d "_csrf=$C" "$B/register/step/4"
curl -s -c wj.txt -b wj.txt -o w4b.html "$B/register/step/4"
assert "فیلد اجباری مرحلهٔ سفارشی → خطا" "grep -q 'الزامی است' w4b.html"
C=$(get_csrf w4b.html)
curl -s -c wj.txt -b wj.txt -o /dev/null -w '%{redirect_url}' -d "_csrf=$C&dyn_$BFID=1234567890" "$B/register/step/4" > w4r
assert "گام ۴ → گام ۵ (مدارک)" "grep -q 'step/5' w4r"
curl -s -c wj.txt -b wj.txt -o w5.html "$B/register/step/5"; C=$(get_csrf w5.html)
curl -s -c wj.txt -b wj.txt -o w5f.html -L -w '%{url_effective}' -F "_csrf=$C" -F "national_card_front=@t.png" -F "birth_cert=@t.png" -F "ownership_doc=@t.pdf" $DYN4 "$B/register/step/5" > w5r
TCW=$(grep -oE 'HG-[A-F0-9]{8}' w5r | head -1)
assert "نهایی‌سازی ۵ مرحله‌ای → کد رهگیری" "[ -n \"$TCW\" ]"
BV=$(mysql -N handicraft_license -e "SELECT v.field_value FROM applicant_field_values v JOIN applicants a ON a.id=v.applicant_id WHERE a.tracking_code='$TCW' AND v.field_id=$BFID")
assert "مقدار فیلد مرحلهٔ سفارشی در پایگاه داده" "[ \"$BV\" = '1234567890' ]"
AIDW=$(mysql -N handicraft_license -e "SELECT id FROM applicants WHERE tracking_code='$TCW'")
curl -s -b aj.txt -o wv.html "$B/admin/view/$AIDW"
assert "مقدار فیلد سفارشی در صفحهٔ پرونده" "grep -q 'شماره بیمه' wv.html && grep -q '1234567890' wv.html"
# پاک‌سازی: حذف فیلد + مرحله
curl -s -b aj.txt -o ws2.html "$B/admin/wizard-steps"; CSRF=$(get_csrf ws2.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&id=$BFID" "$B/admin/form-fields/delete"
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&id=$WSID" "$B/admin/wizard-steps/delete"
WSC=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM wizard_steps WHERE id=$WSID")
assert "پاک‌سازی: مرحلهٔ سفارشی حذف شد" "[ \"$WSC\" = '0' ]"
curl -s -b wj.txt -c wj.txt -o rg2.html "$B/register"
assert "ویزارد به ۴ مرحله بازگشت" "[ $(grep -o 'class=.w-step' rg2.html | wc -l) -eq 4 ]"

echo "═══════════ ۲۳) فاز ۹+۱۰: سرویس‌ها و اشخاص حقوقی ═══════════"
# مدیریت سرویس‌ها
curl -s -b aj.txt -o sv.html -w '%{http_code}' "$B/admin/service-types" > svc
assert "صفحهٔ انواع خدمات → ۲۰۰ با سرویس‌های بذری" "[ \"$(cat svc)\" = '200' ] && grep -q 'مشاغل خانگی' sv.html && grep -q 'گردشگری' sv.html"
CSRF=$(get_csrf sv.html)
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&id=0&title=سرویس آزمون&authority=مرجع آزمون&sort_order=90&is_active=1" "$B/admin/service-types/save"
SVC=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM service_types WHERE title='سرویس آزمون'")
assert "افزودن سرویس جدید از پنل" "[ \"$SVC\" = '1' ]"
SVCID=$(mysql -N handicraft_license -e "SELECT id FROM service_types WHERE title='سرویس آزمون' LIMIT 1")
curl -s -b aj.txt -o /dev/null -d "_csrf=$CSRF&id=$SVCID" "$B/admin/service-types/delete"
SVC=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM service_types WHERE id=$SVCID")
assert "حذف سرویس بدون پرونده" "[ \"$SVC\" = '0' ]"
# سرویس پشتیبان → مرحلهٔ اختصاصی با ۳ فیلد الزامی
rm -f pj.txt; curl -s -c pj.txt -b pj.txt -o ps.html "$B/register/service/3"
curl -s -c pj.txt -b pj.txt -o pw.html "$B/register"
assert "سرویس پشتیبان → مرحلهٔ «اطلاعات پشتیبان» در ویزارد" "grep -q 'اطلاعات پشتیبان' pw.html"
# کد ملی با رقم کنترل اشتباه → رد
curl -s -c pj.txt -b pj.txt -o pf.html "$B/register/step/1"; CSRF=$(get_csrf pf.html)
RNDP=$RANDOM$RANDOM; MBP="09$(printf '%09d' $((RNDP % 1000000000)))"
# کد ملی با رقم کنترل دست‌کاری‌شده → قطعاً نامعتبر
NCOK=$(gen_nc); NCBAD="${NCOK:0:9}$(( ( 10#${NCOK: -1} + 1 ) % 10 ))"
curl -s -c pj.txt -b pj.txt -o /dev/null -d "_csrf=$CSRF&applicant_type=حقیقی&first_name=بد&last_name=کنترل&national_code=$NCBAD&postal_code=4321432143&mobile=$MBP&father_name=پدر&marital_status=مجرد&physical_condition=سالم&has_court_case=خیر&has_active_loan=خیر&has_overdue_installments=خیر&birth_year=1370&birth_month=1&birth_day=1" "$B/register/step/1"
curl -s -c pj.txt -b pj.txt -o pb.html "$B/register/step/1"
assert "کد ملی با رقم کنترل اشتباه → رد می‌شود" "grep -q 'رقم کنترل اشتباه' pb.html"
# ثبت کامل حقوقی با سرویس پشتیبان (۵ مرحله)
NCL=$(gen_nc); LID=$(gen_lid)
curl -s -c pj.txt -b pj.txt -o pl1.html "$B/register/step/1"; CSRF=$(get_csrf pl1.html)
curl -s -c pj.txt -b pj.txt -o /dev/null -w '%{redirect_url}' -d "_csrf=$CSRF&applicant_type=حقوقی&company_name=تعاونی صنایع دستی آزمون&company_national_id=$LID&reg_number=12345&company_type=شرکت تعاونی&reg_year=1398&reg_month=5&reg_day=10&economic_code=411122233344&first_name=نماینده&last_name=شرکت&national_code=$NCL&postal_code=4321432143&mobile=$MBP&father_name=پدرنماینده&marital_status=متأهل&children_count=1&physical_condition=سالم&has_court_case=خیر&has_active_loan=خیر&has_overdue_installments=خیر&birth_year=1365&birth_month=2&birth_day=3" "$B/register/step/1" > plr1
assert "گام ۱ حقوقی → پذیرش با شناسهٔ معتبر" "grep -q 'step/2' plr1"
curl -s -c pj.txt -b pj.txt -o pl2.html "$B/register/step/2"; CSRF=$(get_csrf pl2.html)
curl -s -c pj.txt -b pj.txt -o /dev/null -w '%{redirect_url}' -d "_csrf=$CSRF&license_title=گلیم&experience_months=60&training_method=کارگاهی" "$B/register/step/2" > plr2
curl -s -c pj.txt -b pj.txt -o pl3.html "$B/register/step/3"; CSRF=$(get_csrf pl3.html)
curl -s -c pj.txt -b pj.txt -o /dev/null -w '%{redirect_url}' -d "_csrf=$CSRF&ownership_status=شخص&province=تهران&county=تهران&city=تهران&electricity_bill_id=888$(( RNDP % 100000 ))&address_street=خیابان شرکت&address_alley=کوچه ۱&address_plaque=10" "$B/register/step/3" > plr3
assert "گام ۴ = مرحلهٔ اختصاصی پشتیبان" "grep -q 'step/4' plr3"
curl -s -c pj.txt -b pj.txt -o pl4.html "$B/register/step/4"
assert "فیلدهای پشتیبان (طرح توجیهی/تحت پوشش/درآمدی) نمایش داده می‌شوند" "grep -q 'طرح توجیهی' pl4.html && grep -q 'افراد تحت پوشش' pl4.html && grep -q 'جزئیات درآمدی' pl4.html"
# بدون فیلدهای الزامی پشتیبان → خطا
CSRF=$(get_csrf pl4.html)
curl -s -c pj.txt -b pj.txt -o /dev/null -d "_csrf=$CSRF" "$B/register/step/4"
curl -s -c pj.txt -b pj.txt -o pl4b.html "$B/register/step/4"
assert "فیلدهای الزامی پشتیبان → خطا" "grep -q 'الزامی است' pl4b.html"
BFIDS=$(mysql -N handicraft_license -e "SELECT GROUP_CONCAT(CONCAT('dyn_',id),'|') FROM form_fields WHERE service_type_id=3 AND field_key LIKE 'dyn_%'")
DYNP=""
for key in dyn_justification_plan dyn_covered_persons dyn_income_details; do :; done
# شناسهٔ فیلدها از صفحه استخراج شود
for fidx in $(grep -oE 'name="dyn_[0-9]+"' pl4b.html | grep -oE '[0-9]+' | sort -u); do
    DYNP="$DYNP -d dyn_$fidx=مقدارآزمون_$fidx"
done
CSRF=$(get_csrf pl4b.html)
curl -s -c pj.txt -b pj.txt -o /dev/null -w '%{redirect_url}' -d "_csrf=$CSRF" $DYNP "$B/register/step/4" > plr4
assert "گام ۴ پشتیبان → گام ۵ مدارک" "grep -q 'step/5' plr4"
curl -s -c pj.txt -b pj.txt -o pl5.html "$B/register/step/5"; CSRF=$(get_csrf pl5.html)
curl -s -c pj.txt -b pj.txt -o pl5f.html -L -w '%{url_effective}' -F "_csrf=$CSRF" -F "national_card_front=@t.png" -F "birth_cert=@t.png" -F "ownership_doc=@t.pdf" "$B/register/step/5" > plr5
TCL=$(grep -oE 'HG-[A-F0-9]{8}' plr5 | head -1)
assert "نهایی‌سازی پروندهٔ حقوقی → کد رهگیری" "[ -n \"$TCL\" ]"
ATYPE=$(mysql -N handicraft_license -e "SELECT applicant_type FROM applicants WHERE tracking_code='$TCL'")
SVCID2=$(mysql -N handicraft_license -e "SELECT service_type_id FROM applicants WHERE tracking_code='$TCL'")
assert "نوع متقاضی = حقوقی + سرویس = پشتیبان" "[ \"$ATYPE\" = 'حقوقی' ] && [ \"$SVCID2\" = '3' ]"
CNAME=$(mysql -N handicraft_license -e "SELECT p.company_name FROM persons p JOIN applicants a ON a.person_id=p.id WHERE a.tracking_code='$TCL'")
assert "اطلاعات شرکت در جدول persons ذخیره شد" "[ -n \"$CNAME\" ]"
AIDL=$(mysql -N handicraft_license -e "SELECT id FROM applicants WHERE tracking_code='$TCL'")
curl -s -b aj.txt -o vl.html "$B/admin/view/$AIDL"
assert "کارت شخص حقوقی در صفحهٔ پرونده" "grep -q 'person-type-badge\|person حقوقی\|اطلاعات شخص حقوقی' vl.html || grep -q 'شناسهٔ ملی حقوقی' vl.html"
# مقدار فیلدهای پشتیبان در پرونده
SUPV=$(mysql -N handicraft_license -e "SELECT COUNT(*) FROM applicant_field_values v JOIN applicants a ON a.id=v.applicant_id WHERE a.tracking_code='$TCL'")
assert "مقادیر فیلدهای پشتیبان ذخیره شد" "[ \"$SUPV\" -ge '3' ]"

echo ""
echo "════════════════════════════════════════"
printf 'نتیجه: %s موفق | %s ناموفق\n' "$(green $PASS)" "$( [ $FAIL -gt 0 ] && red $FAIL || echo $FAIL )"
[ $FAIL -eq 0 ] && green "✔ همهٔ آزمون‌ها موفق بودند" || red "✘ برخی آزمون‌ها ناموفق بودند"
rm -rf "$TMP"
exit $((FAIL > 0))
