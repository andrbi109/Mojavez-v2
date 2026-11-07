@echo off
REM =================================================================
REM Mojavez — راه‌اندازی سریع روی XAMPP
REM -----------------------------------------------------------------
REM این اسکریپت فرض می‌کند که XAMPP در C:\xampp نصب است.
REM اگر در مسیر دیگری نصب است، متغیر XAMPP_DIR را تغییر دهید.
REM =================================================================
chcp 65001 >nul
setlocal

set XAMPP_DIR=C:\xampp
set PROJECT_DIR=%~dp0..\..

echo === راه‌اندازی روی XAMPP ===
echo.

REM --- بررسی نصب XAMPP ---
if not exist "%XAMPP_DIR%\apache\bin\httpd.exe" (
    echo [خطا] XAMPP در مسیر %XAMPP_DIR% پیدا نشد.
    echo مسیر نصب را اصلاح کنید یا از install-and-run.bat استفاده کنید.
    pause
    exit /b 1
)

REM --- کپی پروژه به htdocs (در صورت نیاز) ---
echo [۱/۴] بررسی وجود پروژه در htdocs...
if not exist "%XAMPP_DIR%\htdocs\Mojavez" (
    echo پوشهٔ Mojavez در htdocs نیست.
    echo دستی پروژه را در %XAMPP_DIR%\htdocs\Mojavez کپی کنید یا سیم‌لینک بسازید.
    echo یا با راه‌انداز مستقیم استفاده کنید: install-and-run.bat
    pause
    exit /b 1
)

REM --- راه‌اندازی MySQL و Apache ---
echo [۲/۴] راه‌اندازی Apache و MySQL از پنل XAMPP...
start "" "%XAMPP_DIR%\xampp-control.exe"
echo پس از فعال‌سازی Apache و MySQL در پنل XAMPP، Enter بزنید.
pause

REM --- ساخت دیتابیس ---
echo [۳/۴] ساخت پایگاه داده...
"%XAMPP_DIR%\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS handicraft_license CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
"%XAMPP_DIR%\mysql\bin\mysql.exe" -u root handicraft_license < "%PROJECT_DIR%\database\schema.sql"
echo پایگاه داده با موفقیت ساخته شد.
echo.

REM --- پیکربندی config.php ---
echo [۴/۴] اگر DB_PASS در config.php خالی است (پیش‌فرض XAMPP)، نیازی به تغییر نیست.
echo.

echo === راه‌اندازی کامل شد ===
echo آدرس: http://localhost/Mojavez/public/
echo پنل مدیر: http://localhost/Mojavez/public/admin/login
echo نام کاربری: admin
echo رمز عبور: admin123
echo.
pause
endlocal
