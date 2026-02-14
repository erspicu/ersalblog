<#
.SYNOPSIS
    BaxerMux Album - Remove Win11 Theme Script
    Designed for Windows PowerShell
#>

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$THEME_DIR = "..\static\themes\album-win11"

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "🗑️  Removing Win11 Theme" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan

if (Test-Path $THEME_DIR) {
    Remove-Item $THEME_DIR -Recurse -Force
    Write-Host "✅ Successfully removed $THEME_DIR" -ForegroundColor Green
}
else {
    Write-Host "ℹ️  Win11 theme directory not found. Nothing to do." -ForegroundColor Yellow
}

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "✨ Operation Completed" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
