<#
.SYNOPSIS
    BaxerMux Album - Win11 Theme Rebuild Script (AOT optimized)
    Designed for Windows PowerShell
#>

$ErrorActionPreference = "Stop"

# 確保在腳本所在目錄執行
Set-Location $PSScriptRoot

$BLAZOR_DIR = "..\BlazorAlbumExplorer"
$DIST_DIR = "..\static\themes\album-win11\dist\wwwroot"

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "🚀 Starting Win11 Theme Rebuild Process (Windows)" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan

# 1. 環境偵測
if (-not (Get-Command "dotnet" -ErrorAction SilentlyContinue)) {
    Write-Error "❌ 錯誤：找不到 dotnet SDK。"
    Write-Host "請先安裝 .NET 8.0 SDK: https://dotnet.microsoft.com/download"
    exit 1
}

$dotnetVersion = dotnet --version
Write-Host "✅ 偵測到 .NET SDK: $dotnetVersion" -ForegroundColor Green

# 2. 工作負載檢查 (WASM Tools Check)
Write-Host "🔍 檢查 WASM 工作負載..."
$workloads = dotnet workload list
if ($workloads -notmatch "wasm-tools") {
    Write-Host "📦 正在安裝 wasm-tools (可能需要系統管理員權限)..." -ForegroundColor Yellow
    try {
        dotnet workload install wasm-tools
    }
    catch {
        Write-Error "❌ 安裝 wasm-tools 失敗。請以系統管理員身分執行此腳本。"
        exit 1
    }
}
else {
    Write-Host "✅ wasm-tools 已安裝。" -ForegroundColor Green
}

# 3. 執行 AOT 編譯 (Build Process)
if (-not (Test-Path $BLAZOR_DIR)) {
    Write-Error "❌ 錯誤：找不到原始碼目錄 $BLAZOR_DIR"
    exit 1
}

Write-Host "🛠️ 正在執行 AOT 編譯 (此過程較耗時，請耐心等候)..." -ForegroundColor Yellow
Push-Location $BLAZOR_DIR

if (Test-Path ".\publish") {
    Remove-Item ".\publish" -Recurse -Force
}

dotnet publish -c Release -o ./publish
if ($LASTEXITCODE -ne 0) {
    Pop-Location
    Write-Error "❌ 編譯失敗"
    exit 1
}
Pop-Location

# 4. 同步至主題目錄 (Deployment)
Write-Host "📦 同步編譯產物至主題目錄..."
if (Test-Path $DIST_DIR) {
    Remove-Item $DIST_DIR -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $DIST_DIR | Out-Null

Copy-Item -Path "$BLAZOR_DIR\publish\wwwroot\*" -Destination $DIST_DIR -Recurse -Force

# 5. 檔案清理與瘦身 (Cleanup)
Write-Host "🧹 正在清理冗餘檔案 (_framework 減肥)..."
$frameworkDir = Join-Path $DIST_DIR "_framework"

if (Test-Path $frameworkDir) {
    Get-ChildItem -Path $frameworkDir -Include *.gz,*.br,*.pdb,*.pdb.gz,*.pdb.br -Recurse | Remove-Item -Force
    Write-Host "✅ 清理完成。" -ForegroundColor Green
}
else {
    Write-Host "⚠️ 警告：找不到 _framework 目錄，跳過清理。" -ForegroundColor Yellow
}

# 6. 完成報告
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "🎉 Win11 主題重建成功！" -ForegroundColor Cyan
Write-Host "📍 發佈路徑: $(Resolve-Path $DIST_DIR)"
$size = (Get-ChildItem $frameworkDir -Recurse | Measure-Object -Property Length -Sum).Sum / 1MB
Write-Host "📊 _framework 最終大小: $([math]::Round($size, 2)) MB"
Write-Host "==================================================" -ForegroundColor Cyan
