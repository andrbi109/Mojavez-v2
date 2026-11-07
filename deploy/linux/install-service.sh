#!/usr/bin/env bash
# =================================================================
# Mojavez — نصب به‌عنوان سرویس systemd روی لینوکس
# -----------------------------------------------------------------
# این اسکریپت یک unit برای سرور داخلی PHP می‌سازد تا به‌صورت
# سرویس پس‌زمینه اجرا شود (مناسب برای توسعه یا محیط‌های کوچک
# بدون Apache/Nginx).
# -----------------------------------------------------------------
# استفاده: sudo bash install-service.sh
# =================================================================
set -e

GREEN='\033[0;32m'
YELLOW='\033[0;33m'
CYAN='\033[0;36m'
NC='\033[0m'

if [ "$(id -u)" -ne 0 ]; then
    echo "این اسکریپت باید با sudo اجرا شود"
    exit 1
fi

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SERVICE_NAME="mojavez"
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"

# پیدا کردن مسیر php
PHP_BIN="$(command -v php)"
if [ -z "$PHP_BIN" ]; then
    echo "PHP نصب نیست. ابتدا PHP 8 را نصب کنید."
    exit 1
fi

# یافتن کاربری که Apache با آن اجرا می‌شود
RUN_USER="www-data"
id -u www-data >/dev/null 2>&1 || RUN_USER="apache"
id -u apache >/dev/null 2>&1 || RUN_USER="nginx"

cat > "$SERVICE_FILE" <<EOF
[Unit]
Description=Mojavez — Handicraft License Management System
After=network.target mysql.service mariadb.service mysqld.service

[Service]
Type=simple
User=$RUN_USER
Group=$RUN_USER
WorkingDirectory=$PROJECT_DIR
ExecStart=$PHP_BIN -S 0.0.0.0:8000 -t $PROJECT_DIR/public $PROJECT_DIR/public/router.php
Restart=on-failure
RestartSec=5
StandardOutput=journal
StandardError=journal

# امنیت
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=full
ProtectHome=true
ReadWritePaths=$PROJECT_DIR/uploads

# محیط
Environment=APP_DEBUG=false
Environment=MOJAVEZ_ENV=production

[Install]
WantedBy=multi-user.target
EOF

# مطمئن شو uploads قابل نوشتن است
mkdir -p "$PROJECT_DIR/uploads"
chown -R "$RUN_USER":"$RUN_USER" "$PROJECT_DIR/uploads"

systemctl daemon-reload
systemctl enable "$SERVICE_NAME"
systemctl restart "$SERVICE_NAME"

echo -e "${GREEN}✔ سرویس ${SERVICE_NAME} نصب و فعال شد${NC}"
echo
echo "  آدرس:          http://localhost:8000/"
echo "  پنل مدیر:       http://localhost:8000/admin/login"
echo
echo "دستورات مفید:"
echo "  systemctl status  $SERVICE_NAME"
echo "  systemctl restart $SERVICE_NAME"
echo "  journalctl -u $SERVICE_NAME -f"
