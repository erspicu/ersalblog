---

## 2026-02-07 (繁體中文)

### [14:15] 文章內容 Script 標籤保護 (Script Tag Protection)
- **任務**: 防止文章內的 `<script>` 內容在網頁中執行，同時確保其在技術文章中的可見性。
- **實作**: 
    - **核心邏輯**: 在 `admin/system_helper.php` 實作 `protect_script_tags` 函式，將 `<script>` 標籤轉義為 `&lt;script&gt;`。
    - **優點**: 腳本標籤現在能以文字形式在技術文章中顯示，但不會被瀏覽器當作腳本執行，安全性與可讀性兼具。
    - **全域套用**: 整合至 `make_html.php` 與所有 `api/*.php` 檔案。

### [14:35] API 與系統穩定性修復
- **任務**: 解決後台與備份工具的 500 錯誤與安全性問題。
- **實作**:
    - **路徑修復**: 修正 `admin/lang_init.php` 語系路徑錯誤。
    - **備份修復**: 補齊 `tool_backup.php` 引用並修正 CSRF Token 驗證。
    - **同步過濾**: 強化 API 邏輯，僅回傳已存在靜態檔案的文章，確保前台顯示與實體同步。

### [14:40] API 全面重構與精簡 (API Refactoring)
- **任務**: 消除重複代碼並統一三種模式 (File/MySQL/SQLite) 的 API 邏輯。
- **實作**:
    - **邏輯統一**: 重構 `api_filebase.php`, `api_sqlitebase.php`, `api_dbsqlbase.php`，採用單一核心邏輯 `get_data()`。
    - **代碼縮減**: 消除 90% 的重複邏輯，並移除冗餘的 `page()` 進入點與輔助函式。
    - **相容性保持**: 確保重構後的 JSON 輸出格式與前端 `blog.js` 完全相容。

### [15:30] 靜態生成架構重構 (SSG Refactoring)
- **任務**: 解耦「資料發布」與「靜態網頁生成」，提供更靈活的建置管線。
- **實作**:
    - **邏輯封裝**: 建立 `PHP_LIB/StaticGenerator.php` 類別，統一管理 SSG 核心邏輯。
    - **後台整合**: 在 `admin/post_edit.php` 新增「儲存後立即重建」選項。
    - **i18n 支援**: 修正 `StaticGenerator.php` 硬編碼標題，改用動態語系變數。
    - **相容性修復**: 修正 PHP 5.3 不支援 Closure 使用 `$this` 的限制，確保在舊版環境穩定執行。