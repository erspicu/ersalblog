# 部落格架構優化待辦事項 (TODO List)

此文件記錄了關於系統架構的改進建議與待處理任務，主要聚焦於提升樣板生成的穩定性與代碼品質。

---

## 1. 樣板生成流程解耦 (Decoupling Template Generation)

### 狀態 (Status)
**已完成 (COMPLETED)** ✅

### 解決方案 (Solution Implemented)
*   **單一真理來源**：已修改 `make_html.php`，現在所有頁面 (`blog.html`, `post/xxx.html`) 皆直接讀取並解析 `static/blog_template.html` 原始碼。
*   **平行生成**：移除了對 `blog.html` 生成結果的依賴，徹底解決了「影印本再影印」導致的結構劣化問題。

---

## 2. 強化標記與切割邏輯 (Marker & Splitting Logic)

### 狀態 (Status)
**已完成 (COMPLETED)** ✅

### 解決方案 (Solution Implemented)
*   **變數標準化**：全面將 `{xxx}` 佔位符替換為 `{{xxx}}`，避免與 CSS/JS 語法衝突。
*   **字串替換**：棄用了脆弱的 `explode` 切割法，改用 `str_replace` 針對預定義的佔位符進行精確替換。

---

## 3. 提升樣板解析效能與精確度 (Template Parsing)

### 狀態 (Status)
**已完成 (COMPLETED)** ✅

### 解決方案 (Solution Implemented)
*   **移除 DOMDocument**：鑑於 `DOMDocument` (libxml) 對 HTML5 `<template>` 標籤的巢狀結構支援不佳，已完全移除該依賴。
*   **導入 Regex 解析**：改用 `preg_match_all` 與 `preg_replace_callback` 處理樣板提取與圖片 Lazy Loading 優化，顯著提升了效能與 PHP 版本相容性 (支援 PHP 5.x+)。

---

## 4. 自動化壓縮與清理 (Compression & Cleanup)

### 狀態 (Status)
**已完成 (COMPLETED)** ✅

### 解決方案 (Solution Implemented)
*   **排除機制**：`mini.py` 新增了針對巢狀目錄 (`admin/assets`) 與特定檔案 (`exif.js`) 的排除清單。
*   **自動清理**：實作了清理邏輯，自動偵測並刪除誤生成的 `.min.js` / `.min.css` 檔案，保持專案目錄整潔。

---

## 5. 已完成項目 (Completed Items)

*   [x] **Draft System**：草稿暫存機制 (File/DB/SQLite)。
*   [x] **Filename Normalization**：自動補全日期前綴與副檔名清理。
*   [x] **Admin Settings GUI**：後台 `config.js` 設定頁面。
*   [x] **Dashboard Stats**：詳細區分已發布與草稿數量。
*   [x] **Advanced Editor**：整合 TinyMCE 6 (Local Host) 並支援 `<!--more-->`。
*   [x] **Translation**：完整的中英文語系支援 (含 TinyMCE)。
*   [x] **Error Handling**：修正後台登入錯誤訊息顯示 (補全語系檔)。
*   [x] **Front-end i18n**：前台樣板多語系支援與動態配置。
*   [x] **Static JSON Mode**：實作單一資料源 (`data.json`) 支援純靜態主機。
*   [x] **Smart Build Cache**：實作增量建置與內容雜湊比對。
*   [x] **Security Hardening**：API 路徑遍歷防護與樣板 XSS 轉義。

## 6. 其它建議項目

*   [ ] **Server-side Pagination**：為 API 增加 `limit` 與 `offset` 支援。
*   [ ] **Automatic WebP Conversion**：上傳圖片時自動轉換並生成縮圖。

---
**Last Updated**: 2026-02-06
**Recorded by**: Gemini CLI Discussion