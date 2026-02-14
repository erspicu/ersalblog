@echo off
SETLOCAL EnableDelayedExpansion

:: BaxerMux Album - Win11 Theme Rebuild (Windows Batch)
cd /d %~dp0

set "BLAZOR_DIR=..\BlazorAlbumExplorer"
set "DIST_DIR=..\static	hemes\album-win11\dist\wwwroot"

echo ==================================================
echo Starting Win11 Theme Rebuild Process (Batch)
echo ==================================================

:: 1. 環境偵測
where dotnet >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] dotnet SDK not found.
    pause
    exit /b 1
)

:: 2. 工作負載檢查
echo Checking WASM workload...
dotnet workload list | findstr "wasm-tools" >nul
if %ERRORLEVEL% NEQ 0 (
    echo [INFO] wasm-tools is missing. Attempting to install...
    echo [NOTE] This may require Administrator privileges.
    dotnet workload install wasm-tools
    if %ERRORLEVEL% NEQ 0 (
        echo [ERROR] Installation failed. Please run this .bat file as Administrator.
        pause
        exit /b 1
    )
    echo [SUCCESS] wasm-tools installed.
) else (
    echo [OK] wasm-tools is already installed.
)

:: 3. 執行 AOT 編譯
if not exist "%BLAZOR_DIR%" (
    echo [ERROR] Blazor directory not found: %BLAZOR_DIR%
    exit /b 1
)

echo Building Blazor project (AOT)...
pushd "%BLAZOR_DIR%"
if exist "publish" rd /s /q "publish"
dotnet publish -c Release -o ./publish
if %ERRORLEVEL% NEQ 0 (
    popd
    echo [ERROR] Build failed.
    exit /b 1
)
popd

:: 4. 同步至主題目錄
echo Syncing to theme directory...
if exist "%DIST_DIR%" rd /s /q "%DIST_DIR%"
mkdir "%DIST_DIR%"
xcopy /e /y /q "%BLAZOR_DIR%\publish\wwwroot\*" "%DIST_DIR%"

:: 5. 清理冗餘檔案
echo Cleaning _framework...
set "FRAMEWORK_DIR=%DIST_DIR%\_framework"
if exist "%FRAMEWORK_DIR%" (
    pushd "%FRAMEWORK_DIR%"
    del /s /q *.gz *.br *.pdb *.pdb.gz *.pdb.br >nul 2>&1
    popd
    echo Cleanup complete.
)

echo ==================================================
echo Win11 Theme Rebuild Success!
echo ==================================================
pause
