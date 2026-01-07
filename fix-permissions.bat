@echo off
echo Исправление прав доступа для Laravel на Windows
echo ================================================

REM Даем полные права на директорию bootstrap/cache
icacls "bootstrap\cache" /grant Everyone:F /T
echo Права на bootstrap/cache установлены

REM Даем полные права на директорию storage
icacls "storage" /grant Everyone:F /T
echo Права на storage установлены

REM Проверяем результат
echo.
echo Проверка Laravel:
php artisan --version

pause