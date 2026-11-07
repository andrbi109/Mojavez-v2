# ============================================================
# Dockerfile — سامانهٔ Mojavez (PHP 8 خالص + Apache)
# ------------------------------------------------------------
# تصویر پایه: php:8.2-apache (آپاچی از پیش نصب و پیکربندی‌شده)
# ساختار: کد در /var/www/html — DocumentRoot در /var/www/html/public
# ============================================================

FROM php:8.2-apache

# ------------------------------------------------------------
# ۱) نصب وابستگی‌های سیستمی + افزونه‌های PHP در یک لایه
#    - pdo_mysql: ارتباط با MariaDB/MySQL
#    - mbstring:  پشتیبانی از رشته‌های چندبایتی (فارسی)
#    - fileinfo:  تشخیص نوع واقعی فایل‌های بارگذاری‌شده
#    - gd:        پردازش تصویر (thumbnail و ...)
#    - curl:      برای HEALTHCHECK
#    - default-mysql-client: برای entrypoint (mysqladmin ping و اجرای schema)
#    پروژه بدون Composer است؛ بنابراین zip/unzip لازم نیست.
# ------------------------------------------------------------
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        curl \
        default-mysql-client \
    ; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        fileinfo \
        gd \
    ; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------
# ۲) فعال‌سازی ماژول‌های آپاچی مورد نیاز سامانه
#    - rewrite:  برای routing در public/.htaccess
#    - headers:  برای هدرهای امنیتی
# ------------------------------------------------------------
RUN a2enmod rewrite headers

# ------------------------------------------------------------
# ۳) تنظیم DocumentRoot روی public/
#    در تصویر رسمی php:apache مسیر پیش‌فرض /var/www/html است.
#    ما DocumentRoot را در vhost پیش‌فرض به /var/www/html/public
#    تغییر می‌دهیم تا کد برنامه (config، core، app و ...) بیرون از
#    دسترس مستقیم وب قرار گیرد و فقط public/ سرو شود.
#
#    نکته: apache2.conf شامل یک بلوک <Directory /var/www/> با
#    AllowOverride None است. به‌جای تغییر آن، یک بلوک اختصاصی‌تر
#    برای public/ (با AllowOverride All) در مرحلهٔ بعد اضافه می‌کنیم
#    که به‌دلیل اختصاصی‌تر بودن، اولویت دارد و .htaccess را فعال می‌کند.
# ------------------------------------------------------------
RUN set -eux; \
    sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# اضافه‌کردن Directory اختصاصی برای public تا AllowOverride (.htaccess) کار کند
# این بلوک به‌دلیل اختصاصی‌تر بودنِ مسیر، روی <Directory /var/www/> غلبه می‌کند.
RUN printf '<Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/mojavez-public.conf \
    && a2enconf mojavez-public

# ------------------------------------------------------------
# ۴) کپی اسکریپت entrypoint به مسیر ثابت (قبل از کپی کد)
#    نکته: build context ریشهٔ پروژه است (docker-compose.yml این
#    تنظیم را اعمال می‌کند)، پس مسیرها نسبت به ریشهٔ پروژه هستند.
# ------------------------------------------------------------
COPY deploy/docker/entrypoint.sh /usr/local/bin/mojavez-entrypoint.sh
RUN chmod +x /usr/local/bin/mojavez-entrypoint.sh

# ------------------------------------------------------------
# ۵) کپی کل کد پروژه به داخل کانتینر
#    نکته: این Dockerfile فرض می‌کند build context ریشهٔ پروژه است
#    (یعنی: docker build -f deploy/docker/Dockerfile .. را از پوشهٔ
#    deploy/docker اجرا کنید یا context را ریشهٔ پروژه بدهید).
#    از .dockerignore در ریشه برای حذف فایل‌های غیرضروری استفاده می‌کنیم.
# ------------------------------------------------------------
COPY --chown=www-data:www-data . /var/www/html/

# ------------------------------------------------------------
# ۶) مالکیت و سطوح دسترسی
#    - کل پروژه به www-data تعلق دارد
#    - پوشهٔ uploads/ باید قابل نوشتن باشد (0775)
# ------------------------------------------------------------
RUN set -eux; \
    chown -R www-data:www-data /var/www/html; \
    mkdir -p /var/www/html/uploads; \
    chmod 0775 /var/www/html/uploads

# ------------------------------------------------------------
# ۷) متغیرهای محیطی پیش‌فرض (در زمان اجرا با .env بازنویسی می‌شوند)
# ------------------------------------------------------------
ENV APP_DEBUG=false \
    DB_HOST=db \
    DB_NAME=handicraft_license \
    DB_USER=mojavez \
    DB_PASS=secret \
    DB_PORT=3306 \
    INIT_DB=0

# ------------------------------------------------------------
# ۸) پورت exposure و HEALTHCHECK
# ------------------------------------------------------------
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -fsS http://localhost/ || exit 1

# ------------------------------------------------------------
# ۹) ENTRYPOINT و CMD
#    ENTRYPOINT ابتدا منتظر MySQL می‌ماند و در صورت نیاز schema را
#    می‌سازد، سپس CMD (apache2-foreground) را اجرا می‌کند.
# ------------------------------------------------------------
ENTRYPOINT ["mojavez-entrypoint.sh"]
CMD ["apache2-foreground"]
