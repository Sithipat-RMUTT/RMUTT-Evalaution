@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion
title RMUTT Evaluation System - Secure Database Backup

echo =========================================================================
echo    RMUTT Evaluation System - Database Backup Utility
echo =========================================================================
echo.

if not exist "backups" mkdir "backups"

rem Parse .env for credentials if available
set "BACKUP_USER=rmutt_app"
set "BACKUP_PASS="
set "BACKUP_DB=rmutt"

if exist ".env" (
    for /f "usebackq tokens=1,* delims==" %%a in (".env") do (
        set "KEY=%%a"
        set "VAL=%%b"
        if "!KEY!"=="DB_USER" set "BACKUP_USER=!VAL!"
        if "!KEY!"=="DB_PASS" set "BACKUP_PASS=!VAL!"
        if "!KEY!"=="DB_NAME" set "BACKUP_DB=!VAL!"
    )
)

rem Build timestamp: YYYYMMDD_HHMMSS
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set "TIMESTAMP=%datetime:~0,8%_%datetime:~8,6%"
set "BACKUP_FILE=backups\rmutt_backup_%TIMESTAMP%.sql"

echo [*] Starting backup to: %BACKUP_FILE%

if not "%BACKUP_PASS%"=="" (
    docker exec rmutt_db mysqldump -u %BACKUP_USER% -p%BACKUP_PASS% %BACKUP_DB% > "%BACKUP_FILE%"
) else (
    echo [!] DB_PASS not found in .env, attempting using docker env...
    docker exec rmutt_db sh -c "mysqldump -u \$DB_USER -p\$DB_PASS \$DB_NAME" > "%BACKUP_FILE%"
)

if %ERRORLEVEL% EQU 0 (
    echo [OK] Database backup completed successfully.
    echo [*] Calculating SHA-256 Checksum...
    certutil -hashfile "%BACKUP_FILE%" SHA256 > "%BACKUP_FILE%.sha256"
    echo [OK] Checksum saved to: %BACKUP_FILE%.sha256
) else (
    echo [ERROR] Backup failed with exit code %ERRORLEVEL%
)

echo.
pause
