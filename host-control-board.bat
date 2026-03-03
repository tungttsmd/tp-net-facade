@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion
cls
for /f "tokens=*" %%a in ('echo prompt $E^| cmd') do set "ESC=%%a"
set "CYAN=%ESC%[36m"
set "GREEN=%ESC%[32m"
set "YELLOW=%ESC%[33m"
set "RED=%ESC%[31m"
set "RESET=%ESC%[0m"

set "ENV_FILE=%~dp0winsv_ftpsvn\.env"

echo.
echo %CYAN%  TP-Net Host Launcher%RESET%
echo  -------------------------------------------------------------------------------
echo.

:: -- Read .env file

if not exist "%ENV_FILE%" (
    echo  %RED%[ERROR]%RESET%  .env file not found.
    echo.
    echo  -------------------------------------------------------------------------------
    echo.
    echo    Expected at: %ENV_FILE%
    echo.
    echo  -------------------------------------------------------------------------------
    echo.
    pause
    exit /b 1
)

set "MQTT_PORT="
set "REDIS_PORT="

for /f "usebackq tokens=1,2 delims==" %%a in ("%ENV_FILE%") do (
    if "%%a"=="MQTT_PORT"  set "MQTT_PORT=%%b"
    if "%%a"=="REDIS_PORT" set "REDIS_PORT=%%b"
)

if "!MQTT_PORT!"=="" set "MQTT_PORT=1881"
if "!REDIS_PORT!"=="" set "REDIS_PORT=6379"

echo  %CYAN%[INFO]%RESET%  Config loaded from .env
echo.
echo  %GREEN%[OK]%RESET%    MQTT_PORT  : !MQTT_PORT!
echo  %GREEN%[OK]%RESET%    REDIS_PORT : !REDIS_PORT!
echo.
echo  -------------------------------------------------------------------------------
echo.
echo  %CYAN%[INFO]%RESET%  Checking required services...
echo.

:: -- Check Mosquitto

netstat -ano | findstr /r ":!MQTT_PORT! " >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo  %RED%[ERROR]%RESET%  Mosquitto is not running on port !MQTT_PORT!.
    echo.
    echo  -------------------------------------------------------------------------------
    echo.
    echo    Run winsv_ftpsvn\run.bat first before launching.
    echo.
    echo  -------------------------------------------------------------------------------
    echo.
    pause
    exit /b 1
)
echo  %GREEN%[OK]%RESET%    Mosquitto is running on port !MQTT_PORT!.
echo.

:: -- Check Redis

netstat -ano | findstr /r ":!REDIS_PORT! " >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo  %RED%[ERROR]%RESET%  Redis is not running on port !REDIS_PORT!.
    echo.
    echo  -------------------------------------------------------------------------------
    echo.
    echo    Run winsv_ftpsvn\run-redis.bat first before launching.
    echo.
    echo  -------------------------------------------------------------------------------
    echo.
    pause
    exit /b 1
)
echo  %GREEN%[OK]%RESET%    Redis is running on port !REDIS_PORT!.
echo.
echo  -------------------------------------------------------------------------------
echo.
set "PROJECT_DIR=%~dp0winsv_ftpsvn"

echo  %CYAN%[INFO]%RESET%  Starting all services...
echo.
echo  -------------------------------------------------------------------------------
echo.

:: [1/5] mqtt:start
echo  %CYAN%[1/5]%RESET%  Starting mqtt:start...
echo.
start "mqtt:start" /b cmd /c "cd /d "%PROJECT_DIR%" && php artisan mqtt:start"
timeout /t 5 /nobreak >nul
echo  %GREEN%[OK]%RESET%    mqtt:start launched.
echo.

:: [2/5] npm run dev
echo  %CYAN%[2/5]%RESET%  Starting npm run dev...
echo.
start "npm dev" /b cmd /c "cd /d "%PROJECT_DIR%" && npm run dev"
timeout /t 5 /nobreak >nul
echo  %GREEN%[OK]%RESET%    npm run dev launched.
echo.

:: [3/5] reverb:start
echo  %CYAN%[3/5]%RESET%  Starting reverb:start...
echo.
start "reverb:start" /b cmd /c "cd /d "%PROJECT_DIR%" && php artisan reverb:start"
timeout /t 5 /nobreak >nul
echo  %GREEN%[OK]%RESET%    reverb:start launched.
echo.

:: [4/5] queue:work
echo  %CYAN%[4/5]%RESET%  Starting queue:work...
echo.
start "queue:work" /b cmd /c "cd /d "%PROJECT_DIR%" && php artisan queue:work"
timeout /t 5 /nobreak >nul
echo  %GREEN%[OK]%RESET%    queue:work launched.
echo.

:: [5/5] serve
echo  %CYAN%[5/5]%RESET%  Starting php artisan serve...
echo.
start "serve" /b cmd /k "cd /d "%PROJECT_DIR%" && php artisan serve"
timeout /t 3 /nobreak >nul
echo  %GREEN%[OK]%RESET%    serve launched.
echo.

:: Done
echo  -------------------------------------------------------------------------------
echo.
echo  %GREEN%[DONE]%RESET%  All services launched.
echo.
echo  -------------------------------------------------------------------------------
echo.
pause
endlocal
