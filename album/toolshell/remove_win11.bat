@echo off
:: BaxerMux Album - Remove Win11 Theme (Windows Batch)
cd /d %~dp0

set "THEME_DIR=..\static	hemes\album-win11"

echo ==================================================
echo Removing Win11 Theme...
echo ==================================================

if exist "%THEME_DIR%" (
    rd /s /q "%THEME_DIR%"
    echo Successfully removed %THEME_DIR%
) else (
    echo Win11 theme directory not found.
)

echo ==================================================
echo Operation Completed.
pause
