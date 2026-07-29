@echo off
rem Drag one or more photos (or a whole folder) onto this file.
rem Converted .webp copies appear in a "webp" folder next to the originals.
"G:\Xampp\php\php.exe" "%~dp0convert_to_webp_local.php" %*
echo.
pause
