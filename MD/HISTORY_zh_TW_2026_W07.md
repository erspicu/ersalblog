# 2026 W07 (2026-02-09 ~ 2026-02-15)

### 重大架構更新 (Major Architecture Updates)
- **相簿服務多語系架構 (Album Service Multi-language)**: 
  - 實作前後端分離的多語系機制：後端 PHP 負責靜態頁面渲染，前端 JS 負責動態訊息翻譯。
  - 建立 `album/langs/` 下的 JS 專屬語系包 (`zh_TW.js`, `en_US.js`)，並透過 `<html lang>` 屬性自動載入。
  - 重構 `album.js` 與 `album_template.html`，移除所有硬編碼 HTML 與文字，實現 UI 與邏輯徹底解耦。
  - `compression.json` 支援多語系註解 (`comment-zh_TW`, `comment-en_US`)，讓分享選單也能隨語系切換。

### 效能與優化 (Performance & Optimization)
- **智慧縮圖生成 (Smart Thumbnails)**: 
  - 修改 `make_album.php`：僅在原圖解析度大於規格時才產生縮圖，並自動清理磁碟上的冗餘舊檔。
  - 前端 `album.js` 實作智慧回退機制：若指定規格縮圖不存在（因原圖過小），自動回退顯示原圖。
  - 強制同步 JSON 資料結構：修正空 `sizes` 欄位為 Object `{}` 而非 Array `[]`，解決強型別語言解析錯誤。
- **Win11 主題極致優化 (Win11 Theme AOT)**:
  - 啟用 Blazor WASM 的 **AOT (Ahead-of-Time)** 編譯模式，將 C# 代碼編譯為原生 WebAssembly 指令。
  - 新增自動化維護腳本 `album/rebuild_win11.sh`，一鍵完成環境偵測、編譯、部署與檔案瘦身。
  - 執行發佈目錄清理，移除不必要的 `.gz`, `.br` 與偵錯符號，將體積縮減 40% (28MB -> 17MB)。
  - 顯著提升虛擬視窗拖拽、縮放與影像處理的流暢度。

### 系統維護 (System Maintenance)
- **Git 版本庫瘦身**: 
  - 更新 `.gitignore` 並執行 `git rm --cached`，停止追蹤生成的 JSON 快取與 Win11 主題編譯產物 (WASM/DLL)，大幅減輕版本庫體積。
  - 清理 Blazor 專案下的開發暫存目錄 (`bin/`, `obj/`, `publish/`)，釋放約 370MB 磁碟空間。
  - 移除過時的舊版發佈目錄 `album/view_blazor/`。

### 錯誤修復 (Bug Fixes)
- **中文路徑支援**: 修正 Blazor `HttpClient` 請求邏輯，加入 `Uri.EscapeDataString` 以正確處理包含中文字元的相簿路徑。
- **儀表板顯示**: 修復儀表板照片庫大小顯示為未知的問題。
- **初始載入狀態**: 修正 `album.js` 啟動邏輯，確保在動態載入腳本時能正確偵測 `document.readyState`。
