@echo off
chcp 65001 >nul

set APP_DIR=%~dp0
set PORT=8080
set URL=http://localhost:%PORT%

:: Check PHP is available
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo PHP не найден в PATH.
    echo Скачай PHP для Windows: https://windows.php.net/download/
    echo Распакуй в C:\php и добавь C:\php в переменную PATH.
    echo.
    pause
    exit /b 1
)

:: Check if port already in use
netstat -ano | find ":%PORT% " | find "LISTENING" >nul 2>&1
if %errorlevel%==0 (
    echo Сервер уже запущен на порту %PORT%
    start "" "%URL%"
    exit /b
)

:: Create desktop shortcut on first run
if not exist "%USERPROFILE%\Desktop\SEO Ассистент.lnk" (
    powershell -NoProfile -Command ^
      "$ws = New-Object -ComObject WScript.Shell; $s = $ws.CreateShortcut('%USERPROFILE%\Desktop\SEO Ассистент.lnk'); $s.TargetPath = '%APP_DIR%start.bat'; $s.WorkingDirectory = '%APP_DIR%'; $s.WindowStyle = 7; $s.IconLocation = 'shell32.dll,14'; $s.Description = 'SEO Ассистент'; $s.Save()"
    echo Ярлык создан на рабочем столе.
)

:: Start PHP server in background
start /B php -S localhost:%PORT% -t "%APP_DIR%"

:: Wait a moment then open browser
timeout /t 2 /nobreak >nul
start "" "%URL%"

echo SEO Ассистент запущен: %URL%
echo Закрой это окно чтобы остановить сервер.
pause >nul
