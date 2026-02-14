@echo off
:: BaxerMux Album - Clean Blazor Artifacts (Windows Batch)
cd /d %~dp0

set "BLAZOR_DIR=..\BlazorAlbumExplorer"

echo ==================================================
echo Cleaning Blazor Build Artifacts...
echo ==================================================

if exist "%BLAZOR_DIR%" (
    if exist "%BLAZOR_DIR%\bin" (
        rd /s /q "%BLAZOR_DIR%\bin"
        echo Removed bin/
    )
    if exist "%BLAZOR_DIR%\obj" (
        rd /s /q "%BLAZOR_DIR%\obj"
        echo Removed obj/
    )
    if exist "%BLAZOR_DIR%\publish" (
        rd /s /q "%BLAZOR_DIR%\publish"
        echo Removed publish/
    )
    echo Cleanup complete.
) else (
    echo [ERROR] Blazor directory not found.
)

echo ==================================================
pause
