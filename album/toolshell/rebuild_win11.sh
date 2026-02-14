#!/bin/bash

# BaxerMux Album - Win11 Theme Rebuild Script (AOT optimized)
# Designed for Linux / WSL2 Environment

# 確保在腳本所在目錄執行
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
cd "$SCRIPT_DIR"

set -e

BLAZOR_DIR="../BlazorAlbumExplorer"
DIST_DIR="../static/themes/album-win11/dist/wwwroot"

echo "=================================================="
echo "🚀 Starting Win11 Theme Rebuild Process"
echo "=================================================="

# 1. 環境偵測 (Environment Detection)
if ! command -v dotnet &> /dev/null; then
    echo "❌ 錯誤：找不到 dotnet SDK。"
    echo "請先安裝 .NET 8.0 SDK: https://dotnet.microsoft.com/download"
    exit 1
fi

echo "✅ 偵測到 .NET SDK: $(dotnet --version)"

# 2. 工作負載檢查 (WASM Tools Check)
echo "🔍 檢查 WASM 工作負載..."
if ! dotnet workload list | grep -q "wasm-tools"; then
    echo "📦 正在安裝 wasm-tools (可能需要 sudo 權限)..."
    sudo dotnet workload install wasm-tools
else
    echo "✅ wasm-tools 已安裝。"
fi

# 3. 執行 AOT 編譯 (Build Process)
if [ ! -d "$BLAZOR_DIR" ]; then
    echo "❌ 錯誤：找不到原始碼目錄 $BLAZOR_DIR"
    exit 1
fi

echo "🛠️ 正在執行 AOT 編譯 (此過程較耗時，請耐心等候)..."
cd "$BLAZOR_DIR"
rm -rf publish
dotnet publish -c Release -o ./publish

# 4. 同步至主題目錄 (Deployment)
echo "📦 同步編譯產物至主題目錄..."
cd "$SCRIPT_DIR"
rm -rf "$DIST_DIR"/*
mkdir -p "$DIST_DIR"
cp -r "$BLAZOR_DIR/publish/wwwroot/"* "$DIST_DIR/"

# 5. 檔案清理與瘦身 (Cleanup)
echo "🧹 正在清理冗餘檔案 (_framework 減肥)..."
if [ -d "$DIST_DIR/_framework" ]; then
    cd "$DIST_DIR/_framework"
    rm -f *.gz *.br *.pdb *.pdb.gz *.pdb.br
    echo "✅ 清理完成。"
else
    echo "⚠️ 警告：找不到 _framework 目錄，跳過清理。"
fi

# 6. 完成報告
echo "=================================================="
echo "🎉 Win11 主題重建成功！"
echo "📍 發佈路徑: $DIST_DIR"
echo "📊 _framework 最終大小: $(du -sh "$DIST_DIR/_framework" 2>/dev/null | cut -f1 || echo '未知')"
echo "=================================================="
