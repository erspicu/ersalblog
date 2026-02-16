# 開發歷史紀錄 (2026 第 08 週)

本週核心重點：全面強化子系統安全性、實作 Session 隔離機制、以及全站 PHP 5.x 相容性校準。

## 重大變更 (Major Changes)

### 1. 全域子系統安全強化
- **Session 隔離機制**：為部落格 (`BLOG_ADMIN_SESS`)、相簿 (`ALBUM_ADMIN_SESS`) 與留言板 (`MB_ADMIN_SESS`) 配置獨立的 Session 名稱。解決了以往登出其中一個子系統會導致全站同時登出的干擾問題。
- **認證引擎同步**：留言板後台已完整整合部落格主系統的「Bcrypt + 主機特徵碼」雜湊機制，並實作 IP 登入錯誤鎖定功能（Rate Limiting）。
- **相簿後台補強**：為相簿登入介面補上暴力破解防護，與主系統安全等級看齊。

### 2. PHP 5.x 深度相容性優化
- **語法降級**：全面掃描全站 PHP 代碼，將所有 PHP 7+ 專屬的「空接點運算子 (`??`)」替換為 `isset() ? :` 模式。
- **穩定性校驗**：執行 `php -l` 深度掃描，確保 `album/` 與 `MessageBoard/` 目錄下所有檔案均能於 PHP 5.4+ 環境穩定執行。
- **陣列語法標準化**：將關鍵路徑下的 `[]` 陣列宣告改回 `array()` 以求最高穩定性。

### 3. 資源在地化與 UI 深度同步
- **留言板資源脫鉤**：成功將 Bootstrap CSS/JS 與 Bootstrap Icons 抓取至 `MessageBoard/admin/assets/`。管理後台現在已 100% 脫離外部 CDN，確保在無網環境下視覺顯示依然完整。
- **視覺系統大一統**：重構留言板後台的所有頁面，採用與部落格主系統完全一致的「固定深色側邊欄 + 淺灰底色內容區」佈局。統一了卡片陰影、圓角、按鈕配色與 Badge 樣式，消除了子系統間的視覺割裂感。
- **語系化標籤完善**：補齊了側邊欄模式提示（管理模式: SQLite / GAS）等最後一批硬編碼字串，確保多語系體驗的完整性。

### 4. AI 輔助創作整合 (AI Assistant Integration)
- **API 整合**: 新增 `admin/api_ai_helper.php`，整合 Google Gemini API (v1beta)。
- **功能實作**: 支援自動生成文章標題、SEO 描述、關鍵字標籤，以及優化文章內容。
- **前端介面**: 於文章編輯器 (`admin/post_edit.php`) 新增 AI 輔助按鈕與操作介面 (`admin/assets/js/ai_helper.js`)。
- **模型回退機制**: 預設使用高效能模型，若失敗自動回退至 `gemini-3-flash-preview` 確保服務可用性。
- **動態模型清單**: 實作 `admin/settings.php` 的動態模型抓取功能，支援從 Google API 獲取最新模型列表並快取至 `static/ai_models_cache.json`。
- **設定頁面重構**: 
    - 全面導入 **AJAX** 更新機制與 **SweetAlert2** UI，提供流暢的儲存體驗。
    - **多語系支援**: 完成設定頁面所有 AJAX 動作與彈窗文字的 i18n 整合。
    - **舊版相容性**: 實作 `config.php` 自動結構補全功能，支援將 AI 設定自動寫入舊版設定檔。

## 技術優化 (Technical Optimizations)
- **重構設定生成邏輯**：修正了 `setup.php` 使用 Regex 替換 `config.php` 導致雜湊值損壞的問題，改為直接生成乾淨的 PHP 檔案內容。
- **標準化 auth.php 載入鏈**：規範所有行政頁面先配置 Session 參數後再啟動 `session_start()`，解決了 "Session settings cannot be changed when active" 的警告。
- **日誌優化**: AI API 呼叫日誌改寫入根目錄 `debug.txt` 並遮罩 API Key，方便除錯。

## 版本資訊
- **Version**: v2026.02.16.20.32
- **CLI**: 0.28.2
- **Model**: gemini-3-flash-preview
