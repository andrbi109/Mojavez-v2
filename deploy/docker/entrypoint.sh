#!/bin/bash
# ============================================================
# entrypoint.sh — اسکریپت ورود کانتینر اپلیکیشن Mojavez
# ------------------------------------------------------------
# وظایف:
#   ۱) منتظر ماندن تا MySQL/MariaDB در دسترس قرار گیرد
#   ۲) در صورت INIT_DB=1 اجرای فایل schema.sql روی دیتابیس
#   ۳) اجرای دستور اصلی (apache2-foreground) با exec
#
# نکته: این اسکریپت با bash اجرا می‌شود (shebang #!/bin/bash).
#       تصویر رسمی php:8.2-apache مبتنی بر Debian است و bash دارد.
# ============================================================
set -euo pipefail

# ----------------------------------------------------------
# متغیرهای محیطی (با مقادیر پیش‌فرض)
# ----------------------------------------------------------
DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-handicraft_license}"
DB_USER="${DB_USER:-mojavez}"
DB_PASS="${DB_PASS:-secret}"
INIT_DB="${INIT_DB:-0}"

SCHEMA_FILE="/var/www/html/database/schema.sql"

# رنگ‌ها برای خروجی لاگ (اختیاری)
log()  { printf '[entrypoint] %s\n' "$*"; }
warn() { printf '[entrypoint][WARNING] %s\n' "$*" >&2; }
err()  { printf '[entrypoint][ERROR] %s\n' "$*" >&2; }

# ----------------------------------------------------------
# ۱) تابع انتظار برای در دسترس شدن MySQL
# ----------------------------------------------------------
wait_for_mysql() {
    log "منتظر در دسترس شدن MySQL در ${DB_HOST}:${DB_PORT} ..."

    local max_attempts=60   # حداکثر ۶۰ تلاش
    local attempt=0
    local wait_sec=2        # فاصلهٔ هر تلاش: ۲ ثانیه (مجموعاً ~۲ دقیقه)

    # mysqladmin ping خروجی "mysqld is alive" می‌دهد و کد ۰ برمی‌گرداند
    # در صورت نبود رمز، از --password="" استفاده می‌کنیم تا از prompt جلوگیری شود.
    while :; do
        attempt=$((attempt + 1))
        if mysqladmin ping \
                --host="${DB_HOST}" \
                --port="${DB_PORT}" \
                --user="${DB_USER}" \
                --password="${DB_PASS}" \
                --silent 2>/dev/null; then
            log "MySQL پاسخ داد (پس از ${attempt} تلاش)."
            return 0
        fi

        if [ "${attempt}" -ge "${max_attempts}" ]; then
            err "MySQL پس از ${max_attempts} تلاش در دسترس نبود. ادامه نمی‌دهم."
            err "آیا سرویس db راه‌اندازی شده است؟ شبکه و نام میزبان را بررسی کنید."
            exit 1
        fi

        log "تلاش ${attempt}/${max_attempts}: MySQL هنوز آماده نیست. ${wait_sec}s صبر ..."
        sleep "${wait_sec}"
    done
}

# ----------------------------------------------------------
# ۲) اجرای schema.sql روی دیتابیس
# ----------------------------------------------------------
init_database() {
    if [ ! -f "${SCHEMA_FILE}" ]; then
        warn "فایل schema.sql در مسیر ${SCHEMA_FILE} پیدا نشد. از مقداردهی اولیه صرف‌نظر شد."
        return 0
    fi

    log "اجرای ${SCHEMA_FILE} روی دیتابیس ${DB_NAME} ..."

    # ----------------------------------------------------------------------
    # نکتهٔ مهم دربارهٔ امتیازها:
    #   فایل schema.sql با «CREATE DATABASE IF NOT EXISTS» شروع می‌شود.
    #   کاربر اپلیکیشن (DB_USER) امتیاز سراسری CREATE ندارد؛ بنابراین این
    #   خط به‌طور طبیعی خطای access-denied می‌دهد. اما چون دیتابیس از قبل
    #   توسط متغیر MYSQL_DATABASE در کانتینر db ساخته شده است، این دستور
    #   در عمل یک no-op است.
    #
    #   با پرچم --force، mysql در صورت بروز خطا ادامه می‌دهد؛ در نتیجه
    #   دستورات USE و CREATE TABLE IF NOT EXISTS با موفقیت اجرا می‌شوند
    #   (کاربر اپلیکیشن امتیاز کامل روی دیتابیس خودش را دارد).
    #
    #   مزیت این روش: نیازی به دادن امتیاز root به کانتینر اپلیکیشن نیست
    #   و حداقل‌دسترسی (least privilege) حفظ می‌شود.
    # ----------------------------------------------------------------------
    local err_log
    err_log="$(mktemp)"

    # به‌عمد با || true ادامه می‌دهیم — خطای CREATE DATABASE مورد انتظار است.
    mysql \
        --host="${DB_HOST}" \
        --port="${DB_PORT}" \
        --user="${DB_USER}" \
        --password="${DB_PASS}" \
        --default-character-set=utf8mb4 \
        --force \
        < "${SCHEMA_FILE}" 2>"${err_log}" || true

    # ----------------------------------------------------------------------
    # بررسی صحت اجرا: آیا جدول اصلی «users» ساخته شده است؟
    #   اگر بله → schema با موفقیت اعمال شده (خطای CREATE DATABASE،
    #   مورد انتظار و بی‌ضرر بوده).
    #   اگر خیر → خطای جدی رخ داده؛ خروجی خطا را نمایش و خارج می‌شویم.
    #
    #   نکته: از pipe استفاده نمی‌کنیم تا با set -o pipefail تداخل پیدا
    #   نکند؛ خروجی mysql را در متغیر می‌ریزیم و سپس مقایسه می‌کنیم.
    # ----------------------------------------------------------------------
    local verify_out=""
    verify_out="$(mysql \
            --host="${DB_HOST}" \
            --port="${DB_PORT}" \
            --user="${DB_USER}" \
            --password="${DB_PASS}" \
            --silent --skip-column-names \
            -e "SELECT 1 FROM information_schema.tables \
                WHERE table_schema='${DB_NAME}' AND table_name='users' LIMIT 1;" \
            2>/dev/null)" || verify_out=""

    if [ "${verify_out}" = "1" ]; then
        log "schema.sql با موفقیت اجرا شد (جدول users موجود است)."
        rm -f "${err_log}"
        return 0
    else
        err "اجرای schema.sql با خطای جدی مواجه شد. خروجی خطا:"
        cat "${err_log}" >&2
        rm -f "${err_log}"
        err "راهنما: متغیرهای DB_USER/DB_PASS/DB_NAME و در دسترس بودن MySQL را بررسی کنید."
        exit 1
    fi
}

# ----------------------------------------------------------
# بدنهٔ اصلی
# ----------------------------------------------------------
main() {
    log "شروع entrypoint کانتینر Mojavez."

    # ۱) منتظر MySQL
    wait_for_mysql

    # ۲) مقداردهی اولیهٔ دیتابیس (در صورت درخواست)
    if [ "${INIT_DB}" = "1" ]; then
        log "INIT_DB=1 → اجرای schema.sql."
        init_database
    else
        log "INIT_DB=${INIT_DB} → از مقداردهی اولیهٔ دیتابیس صرف‌نظر شد."
    fi

    # ۳) راه‌اندازی Apache (با exec تا PID ۱ را به Apache بسپاریم)
    log "راه‌اندازی Apache: $*"
    exec "$@"
}

main "$@"
