<#
.SYNOPSIS
    BaxerMux Album - Clean Blazor Build Artifacts
    Designed for Windows PowerShell
#>

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$BLAZOR_DIR = "..\BlazorAlbumExplorer"

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "🧹 Cleaning BlazorAlbumExplorer Build Artifacts" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan

if (Test-Path $BLAZOR_DIR) {
    Write-Host "🔍 Cleaning directories in $BLAZOR_DIR..."

    $dirsToClean = @("bin", "obj", "publish")

    foreach ($dirName in $dirsToClean) {
        $targetPath = Join-Path $BLAZOR_DIR $dirName
        if (Test-Path $targetPath) {
            Remove-Item $targetPath -Recurse -Force
            Write-Host "✅ Removed $dirName/" -ForegroundColor Green
        }
    }
    
    Write-Host "✨ Cleanup complete for Blazor project." -ForegroundColor Green
}
else {
    Write-Error "❌ Error: $BLAZOR_DIR not found."
}

Write-Host "==================================================" -ForegroundColor Cyan
