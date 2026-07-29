@echo off
setlocal
rem Two ways to use this:
rem  1. Drag photos or a folder onto this .bat file's icon in Explorer.
rem  2. Double-click it, then drag a photo/folder into the window and press Enter.

if not "%~1"=="" (
    "G:\Xampp\php\php.exe" "%~dp0convert_to_webp_local.php" %*
    goto done
)

echo ============================================
echo   WebP Converter
echo ============================================
echo.
echo Drag a photo or a folder of photos into this
echo window, then press Enter.
echo.
set "target="
set /p target=^>
if "%target%"=="" (
    echo Nothing entered - closing.
    goto done
)
"G:\Xampp\php\php.exe" "%~dp0convert_to_webp_local.php" %target%

:done
echo.
pause
