# BaxerMux Album - ToolShell 腳本工具包

本目錄包含相簿服務開發與維護所需的自動化腳本，支援多種作業系統環境（Windows CMD, PowerShell, Linux/WSL2）。

## 📁 腳本清單與功能說明

### 1. Win11 主題重建 (Rebuild Win11 Theme)
此工具用於自動化編譯 Blazor 專案、執行 AOT 極致優化，並同步產出至主題目錄。
- **功能**: 環境檢查、自動安裝 WASM 工作負載、AOT 編譯、靜態檔案瘦身（刪除冗餘壓縮檔與偵錯符號）。
- **腳本**:
  - `rebuild_win11.sh` (Linux/WSL2/Git Bash)
  - `rebuild_win11.ps1` (PowerShell)
  - `rebuild_win11.bat` (Windows CMD)

### 2. 清理 Blazor 建置暫存 (Clean Blazor Artifacts)
在進行 Git 提交或專案打包前執行，可顯著縮減專案體積。
- **功能**: 遞迴刪除 `BlazorAlbumExplorer` 中的 `bin/`, `obj/`, `publish/` 目錄。
- **腳本**:
  - `clean_blazor.sh`
  - `clean_blazor.ps1`
  - `clean_blazor.bat`

### 3. 移除 Win11 主題 (Remove Win11 Theme)
完全移除已產出的主題靜態檔案。
- **功能**: 刪除 `static/themes/album-win11/` 目錄。
- **腳本**:
  - `remove_win11.sh`
  - `remove_win11.ps1`
  - `remove_win11.bat`

---

## 🚀 執行指南

### 在 Windows 環境下 (建議使用)
建議優先使用 **PowerShell** 版本以獲得最佳的視覺反饋與錯誤處理。
- **PowerShell**: 右鍵點擊腳本 -> `使用 PowerShell 執行`。
- **CMD**: 直接雙擊 `.bat` 檔案執行。
- **⚠️ 權限提示**: 若腳本需要安裝 `wasm-tools` 或存取受限目錄，請以「系統管理員身分」執行。

### 在 Linux / WSL2 環境下
1. 開啟終端機並進入目錄：`cd album/toolshell`
2. 賦予執行權限（初次使用）：`chmod +x *.sh`
3. 執行腳本：`./rebuild_win11.sh`
- **⚠️ 權限提示**: 部分指令會呼叫 `sudo`，執行時請留意密碼輸入提示。

---

## 🛠️ 開發環境需求
- **.NET SDK 8.0+**: 必須安裝於系統中。
- **wasm-tools 工作負載**: 腳本會嘗試自動偵測並安裝，但建議手動確認權限是否足夠。

## 📝 注意事項
- 所有腳本均採用**相對路徑**設計，請直接在 `toolshell/` 目錄下執行。
- 執行 `rebuild_win11` 腳本時，AOT 編譯過程可能耗時 3-10 分鐘，請耐心等候。
