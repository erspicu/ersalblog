# 部落格架構優化待辦事項 (TODO List)

此文件記錄了關於系統架構的改進建議與待處理任務，主要聚焦於提升系統穩定性、效能與使用者體驗。

---

## 1. 核心邏輯優化

### API 全面重構 (API Consolidation)
*   **狀態**: **已完成 (COMPLETED)** ✅
*   **成果**: 重構 `api_filebase.php`, `api_sqlitebase.php`, `api_dbsqlbase.php`。消除 90% 重複代碼，統一採用 `get_data` 核心，支援統一的分頁數據結構。

### 混合式分頁系統 (Hybrid Pagination)
*   **狀態**: **已完成 (COMPLETED)** ✅
*   **成果**: 實作了後端延遲讀取與前端客戶端切割兩種分頁模式，顯著提升載入大數據量文章時的效能。

### 靜態生成器類別化 (SSG Refactoring)
*   **狀態**: **已完成 (COMPLETED)** ✅
*   **成果**: 建立 `PHP_LIB/StaticGenerator.php` 封裝所有建置邏輯，修復了語系變數前綴錯誤，確保 CLI 與後台整合一致。

---

## 2. 安全性與穩定性

### 文章內容腳本保護 (Script Tag Protection)
*   **狀態**: **已完成 (COMPLETED)** ✅
*   **成果**: 實作 `protect_script_tags` 函式，防止文章內 `<script>` 被瀏覽器執行，同時維持文字內容的可見性。

### 備份工具強化 (Backup Fixes)
*   **狀態**: **已完成 (COMPLETED)** ✅
*   **成果**: 修復了備份工具的 CSRF 驗證錯誤與類別引用路徑問題。

---

## 3. 已完成項目總覽 (History)

*   [x] **Template Decoupling**: 樣板生成流程解耦，統一讀取 `blog_template.html`。
*   [x] **Placeholder Standardization**: 全面採用 `{{xxx}}` 雙大括號佔位符。
*   [x] **Regex Engine**: 移除 DOMDocument，改用 Regex 解析以提升 PHP 5.x 相容性。
*   [x] **Theme System**: 擴充 Pink 與 Matrix 主題，並強化佈局穩定性 (`clear: both`)。
*   [x] **Posts Per Page**: 後台設定支援自訂分頁文章數量。
*   [x] **Date Range Fix**: 支援 4 碼 (年份) 進行文章過濾。

---

## 4. 剩餘建議項目 (Future Improvements)

*   [ ] **Automatic WebP Conversion**: 上傳圖片時自動轉換並生成縮圖 (LCP 優化)。
*   [ ] **Media Manager**: 建立後台媒體庫介面，方便管理與重用照片資源。
*   [ ] **Search Enhancement**: 進階關鍵字搜尋優化 (目前依賴 Google CSE)。
*   [ ] **CSS Refactoring**: 進一步整合四款主題的共用 CSS 變數，減少重複定義。

---
**Last Updated**: 2026-02-07 (via Linux `date`)
**Recorded by**: Gemini CLI Discussion