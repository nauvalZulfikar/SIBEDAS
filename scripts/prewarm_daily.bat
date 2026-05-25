@echo off
REM Daily prewarm wrapper — runs prewarm_polygon_cache.py after ensuring
REM Laravel dev server + Docker containers are up. Triggered by Windows
REM Task Scheduler entry "SibedasPrewarmDaily".
REM
REM Logs to scripts\logs\prewarm-YYYYMMDD.log

setlocal
set "ROOT=D:\Downloads\coding project\_sibedas\Sibedas"
set "LOGDIR=%ROOT%\scripts\logs"
if not exist "%LOGDIR%" mkdir "%LOGDIR%"
set "LOG=%LOGDIR%\prewarm-%date:~10,4%%date:~4,2%%date:~7,2%.log"

echo === Sibedas Daily Prewarm %date% %time% === >> "%LOG%"

REM Ensure Docker containers up (idempotent — no-op if already running)
docker start sibedas_postgis sibedas_martin sibedas_redis >> "%LOG%" 2>&1

REM Probe Laravel — start if down (background, detached)
curl -s -o nul -w "%%{http_code}" http://127.0.0.1:8002/up > "%TEMP%\laravel_probe.txt" 2>nul
set /p HTTP=<"%TEMP%\laravel_probe.txt"
if not "%HTTP%"=="200" (
    echo Laravel down, starting... >> "%LOG%"
    start "" /B "C:\xampp\php\php.exe" -d output_buffering=0 "%ROOT%\artisan" serve --host=127.0.0.1 --port=8002 >> "%LOGDIR%\serve.log" 2>&1
    timeout /T 8 /NOBREAK > nul
)

REM Run prewarm
set "PYTHONIOENCODING=utf-8"
cd /D "%ROOT%"
echo --- polygon tiles --- >> "%LOG%"
python scripts\prewarm_polygon_cache.py >> "%LOG%" 2>&1
echo --- cluster aggregates --- >> "%LOG%"
python scripts\prewarm_clusters.py >> "%LOG%" 2>&1

echo === Done %date% %time% === >> "%LOG%"
endlocal
