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
*   **成果**: 建立 `PHP_LIB/StaticGenerator.php` 封裝所有建置邏輯，並強化路徑自動修復功能，確保跨目錄資源引用正確。

---

## 2. 媒體與內容管理

### 獨立相簿服務與媒體庫整合 (Album & Media Manager)
*   **狀態**: **已完成 (COMPLETED)** ✅
*   **成果**:
    *   建立基於檔案系統的獨立相簿服務 (SPA 架構) 與專屬後台。
    *   **影像優化**: 整合 ImageMagick 實現高品質縮圖與 EXIF 保留，並具備 GD 回退機制。
    *   **地圖整合**: 實作 GPS 座標解析與 Google Maps 嵌入，支援響應式分割佈局。
    *   **編輯器整合**: 文章編輯器內建相簿挑選器，支援多尺寸插入與即時上傳。

---

## 3. 安全性與穩定性

### 設定管理重構 (Settings Refactor)
*   **狀態**: **已完成 (COMPLETED)** ✅
*   **成果**: 重構 `admin/settings.php`，分區管理前後端設定，實作資料夾選擇器與防呆寫入保護。

### 文章內容腳本保護 (Script Tag Protection)
*   **狀態**: **已完成 (COMPLETED)** ✅
*   **成果**: 實作 `protect_script_tags` 函式，防止文章內 `<script>` 被瀏覽器執行。

### 備份工具強化 (Backup Fixes)
*   **狀態**: **已完成 (COMPLETED)** ✅
*   **成果**: 修復了備份工具的 CSRF 驗證錯誤與類別引用路徑問題。

---

## 4. 已完成項目總覽 (History)

*   [x] **Template Decoupling**: 樣板生成流程解耦，統一讀取 `blog_template.html`。
*   [x] **Placeholder Standardization**: 全面採用 `{{xxx}}` 雙大括號佔位符。
*   [x] **Regex Engine**: 移除 DOMDocument，改用 Regex 解析以提升 PHP 5.x 相容性。
*   [x] **Theme System**: 擴充 Pink 與 Matrix 主題，並強化佈局穩定性。
*   [x] **Album SPA**: 相簿服務由靜態 HTML 生成轉向 SPA + JSON。
*   [x] **Map Integration**: 相簿詳情頁整合 Google Maps 與 GPS 資訊。
*   [x] **Folder Picker**: 後台設定新增相簿路徑選擇器。

---

## 5. 剩餘建議項目 (Future Improvements)

*   [ ] **Search Enhancement**: 進階關鍵字搜尋優化 (目前依賴 Google CSE)。
*   [ ] **CSS Refactoring**: 進一步整合四款主題的共用 CSS 變數，減少重複定義。
*   [ ] **Backup Cleanup**: 定期自動清理過期的備份檔案。

---
**Last Updated**: 2026-02-09 (via Linux `date`)
**Recorded by**: Gemini CLI Discussion