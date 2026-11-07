@echo off
REM =================================================================
REM Mojavez — راه‌اندازی سریع روی ویندوز (XAMPP یا Laragon)
REM -----------------------------------------------------------------
REM این اسکریپت:
REM   ۱) بررسی نصب بودن php و mysql
REM   ۲) ساخت پایگاه داده و اجرای schema.sql
REM   ۳) راه‌اندازی سرور توسعه PHP روی http://localhost:8000
REM -----------------------------------------------------------------
REM استفاده: install-and-run.bat
REM =================================================================
chcp 65001 >nul
setlocal enabledelayedexpansion

echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║          راه‌اندازی سامانهٔ Mojavec روی ویندوز               ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

REM --- تشخیص مسیر PHP ---
where php >nul 2>&1
if errorlevel 1 (
    echo [خطا] PHP در PATH پیدا نشد.
    echo اگر XAMPP نصب دارید: C:\xampp\php را به PATH اضافه کنید.
    echo اگر Laragon نصب دارید: C:\laragon\bin\php\php-x.x.x را به PATH اضافه کنید.
    pause
    exit /b 1
)

REM --- تشخیص مسیر MySQL ---
where mysql >nul 2>&1
if errorlevel 1 (
    echo [هشدار] MySQL در PATH پیدا نشد.
    echo اگر از MariaDB داکر استفاده می‌کنید، این مرحله را رد کنید.
    echo در غیر این صورت، مسیر bin\ MySQL را به PATH اضافه کنید.
    set MYSQL_AVAILABLE=0
) else (
    set MYSQL_AVAILABLE=1
)

REM --- پیکربندی ---
set DB_NAME=handicraft_license
set DB_USER=root
set /p DB_PASS="رمز عبور MySQL (خالی اگر ندارد، Enter بزنید): "
if "%DB_PASS%"=="" set DB_PASS_EMPTY=1

echo.
echo [۱/۳] بررسی نسخهٔ PHP...
php -v | findstr /C:"PHP"
echo.

if "%MYSQL_AVAILABLE%"=="1" (
    echo [۲/۳] ساخت پایگاه داده و اجرای schema.sql...
    if defined DB_PASS_EMPTY (
        mysql -u %DB_USER% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        mysql -u %DB_USER% %DB_NAME% < "%~dp0..\..\database\schema.sql"
    ) else (
        mysql -u %DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        mysql -u %DB_USER% -p%DB_PASS% %DB_NAME% < "%~dp0..\..\database\schema.sql"
    )
    echo پایگاه داده با موفقیت ساخته شد.
) else (
    echo [۲/۳] MySQL در دسترس نیست — بدون ساخت دیتابیس ادامه می‌دهیم.
)
echo.

echo [۳/۳] راه‌اندازی سرور توسعه PHP...
echo آدرس سامانه: http://localhost:8000
echo آدرس پنل مدیر: http://localhost:8000/admin/login
echo نام کاربری پیش‌فرض: admin
echo رمز عبور پیش‌فرض: admin123
echo.
echo برای توقف: Ctrl+C
echo.

php -S localhost:8000 -t "%~dp0..\..\public" "%~dp0..\..\public\router.php"

endlocal
