#!/usr/bin/env bash
# =================================================================
# Mojavez — راه‌اندازی روی لینوکس (Apache + PHP + MySQL/MariaDB)
# -----------------------------------------------------------------
# این اسکریپت:
#   ۱) نصب پیش‌نیازها (Apache, PHP, MariaDB)
#   ۲) کپی فایل‌های پیکربندی
#   ۳) ساخت دیتابیس و اجرای schema.sql
#   ۴) فعال‌سازی ماژول‌ها و راه‌اندازی سرویس‌ها
# -----------------------------------------------------------------
# پشتیبانی از: Debian/Ubuntu, CentOS/RHEL/Fedora, Arch
# استفاده: sudo bash install.sh
# =================================================================
set -e

# رنگ‌ها
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
CYAN='\033[0;36m'
NC='\033[0m'

success() { echo -e "${GREEN}✔ $1${NC}"; }
fail()    { echo -e "${RED}✗ $1${NC}"; exit 1; }
info()    { echo -e "${CYAN}ℹ $1${NC}"; }
warn()    { echo -e "${YELLOW}⚠ $1${NC}"; }

echo -e "${CYAN}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║   راه‌اندازی Mojavec روی لینوکس (Apache + MariaDB)      ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════════╝${NC}"
echo

# --- بررسی root ---
if [ "$(id -u)" -ne 0 ]; then
    fail "این اسکریپت باید با sudo اجرا شود: sudo bash $0"
fi

# --- تشخیص توزیع ---
PKG_MANAGER=""
if command -v apt-get &>/dev/null; then
    PKG_MANAGER="apt"
elif command -v dnf &>/dev/null; then
    PKG_MANAGER="dnf"
elif command -v yum &>/dev/null; then
    PKG_MANAGER="yum"
elif command -v pacman &>/dev/null; then
    PKG_MANAGER="pacman"
else
    fail "توزیع لینوکس پشتیبانی نمی‌شود."
fi
info "توزیع: $PKG_MANAGER"

# --- مسیر پروژه ---
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
INSTALL_DIR="/var/www/Mojavez"

info "مسیر پروژه: $PROJECT_DIR"
info "مسیر نصب:   $INSTALL_DIR"
echo

# --- نصب پیش‌نیازها ---
echo "═══════════ نصب پیش‌نیازها ═══════════"
case $PKG_MANAGER in
    apt)
        apt-get update -qq
        DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
            apache2 mariadb-server php php-mysql php-mbstring php-gd php-curl php-xml \
            > /dev/null
        ;;
    dnf|yum)
        $PKG_MANAGER install -y -q httpd mariadb-server php php-mysqlnd php-mbstring php-gd php-curl php-xml
        systemctl enable --now mariadb
        systemctl enable --now httpd
        ;;
    pacman)
        pacman -Sy --noconfirm apache mariadb php php-gd
        systemctl enable --now mariadb
        systemctl enable --now httpd
        ;;
esac
success "پیش‌نیازها نصب شدند"

# --- کپی پروژه به /var/www ---
echo
echo "═══════════ کپی پروژه ═══════════"
mkdir -p "$INSTALL_DIR"
rsync -aq --delete \
    --exclude '.git' --exclude 'tests/tmp' --exclude '*.log' \
    --exclude 'deploy/docker' --exclude '.env' \
    "$PROJECT_DIR/" "$INSTALL_DIR/"
chown -R www-data:www-data "$INSTALL_DIR" 2>/dev/null || \
    chown -R apache:apache "$INSTALL_DIR" 2>/dev/null || \
    chown -R nginx:nginx "$INSTALL_DIR" 2>/dev/null || true
chmod -R 0755 "$INSTALL_DIR"
chmod -R 0775 "$INSTALL_DIR/uploads"
success "پروژه در $INSTALL_DIR کپی شد"

# --- ساخت دیتابیس ---
echo
echo "═══════════ ساخت پایگاه داده ═══════════"
read -rp "رمز عبور root برای MySQL (خالی برای بدون رمز): " -s MYSQL_ROOT_PASS
echo

if [ -z "$MYSQL_ROOT_PASS" ]; then
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS handicraft_license CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -u root handicraft_license < "$INSTALL_DIR/database/schema.sql"
    success "پایگاه داده ساخته شد (بدون رمز root)"
else
    mysql -u root -p"$MYSQL_ROOT_PASS" -e "CREATE DATABASE IF NOT EXISTS handicraft_license CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -u root -p"$MYSQL_ROOT_PASS" handicraft_license < "$INSTALL_DIR/database/schema.sql"
    success "پایگاه داده ساخته شد"
fi

# --- فعال‌سازی ماژول‌های Apache ---
echo
echo "═══════════ پیکربندی Apache ═══════════"
if command -v a2enmod &>/dev/null; then
    a2enmod rewrite headers deflate expires >/dev/null 2>&1
    success "ماژول‌های Apache فعال شدند"
fi

# --- نصب VirtualHost ---
if [ -d /etc/apache2/sites-available ]; then
    cp "$SCRIPT_DIR/../apache/mojavez.conf" /etc/apache2/sites-available/mojavez.conf
    a2dissite 000-default.conf >/dev/null 2>&1 || true
    a2ensite mojavez >/dev/null
    systemctl reload apache2
    success "Apache vhost فعال شد"
elif [ -d /etc/httpd/conf.d ]; then
    cp "$SCRIPT_DIR/../apache/mojavez.conf" /etc/httpd/conf.d/mojavez.conf
    systemctl reload httpd
    success "Apache vhost فعال شد"
fi

# --- راه‌اندازی مجدد سرویس‌ها ---
systemctl restart apache2 2>/dev/null || systemctl restart httpd 2>/dev/null || true
systemctl restart mariadb 2>/dev/null || systemctl restart mysqld 2>/dev/null || true

echo
echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  نصب با موفقیت کامل شد!                                    ${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"
echo
echo "  آدرس سامانه:   http://localhost/"
echo "  پنل مدیر:       http://localhost/admin/login"
echo "  نام کاربری:     admin"
echo "  رمز عبور:       admin123"
echo
echo -e "${YELLOW}  ⚠  پس از اولین ورود، رمز عبور را از پنل تغییر دهید.${NC}"
echo
info "برای دسترسی از راه دور، نام دامنه را در /etc/hosts یا DNS تنظیم کنید."
info "برای HTTPS، گواهی SSL را فعال کنید (در فایل mojavez.conf راهنمایی شده)."
