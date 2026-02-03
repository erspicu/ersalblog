# 部落格架構優化待辦事項 (TODO List)

此文件記錄了關於系統架構的改進建議與待處理任務，主要聚焦於提升樣板生成的穩定性與代碼品質。

---

## 1. 樣板生成流程解耦 (Decoupling Template Generation)

### 當前問題 (Current Issue)
目前的 `make_html.php` 採用「鏈式生成」流程：
1. `static/blog_template.html` (原始碼) -> `blog.html` (SPA 入口/中間產物)
2. `blog.html` (中間產物) -> 解析後生成 -> `post/xxx.html` (最終產物)

這導致生成過程依賴於「中間產物」。若 `blog.html` 損壞或被意外修改，將會導致後續所有靜態頁面生成錯誤（即「影印本再影印」的問題）。

### 優化建議 (Proposed Solution)
*   **確立單一真理來源 (Single Source of Truth)**：修改 `make_html.php`，讓 `build()` 函式直接讀取原始碼 `static/blog_template.html`。
*   **平行生成**：讓 `blog.html` 與 `post/xxx.html` 成為平行的輸出關係，兩者皆直接由原始樣板生成。

---

## 2. 強化標記與切割邏輯 (Marker & Splitting Logic)

### 當前問題
目前使用 `explode('<!--post_load-->', $html)` 來切割 Header 與 Footer。這種「魔術註解」法雖然簡單，但若註解被刪除或移動，會導致程式崩潰。

### 優化建議
*   **改用佔位符變數**：在樣板中使用如 `{{SPA_CONTAINER}}` 或 `{{POST_CONTENT}}` 之類的自定義標籤。
*   **字串替換優於切割**：統一使用 `str_replace` 進行內容植入，而非物理切割 HTML 字串。

---

## 3. 提升樣板解析效能與精確度 (Template Parsing)

### 當前問題
目前使用 `DOMDocument` 來提取樣板內容。雖然功能強大，但對 HTML5 支援較舊，且會自動補全/修正 HTML 結構，有時會破壞原始排版。

### 優化建議
*   **改用正規表達式 (Regex)**：若僅需提取 `<template>` 區塊，使用正規表達式會更輕量且不會更動到非目標區塊的 HTML 原始排版。
*   **快取樣板物件**：在同一批生成任務中，樣板內容只需解析一次並快取於記憶體中。

---

## 4. 已完成項目 (Completed Items)

*   [x] **Draft System**：草稿暫存機制 (File/DB/SQLite)。
*   [x] **Filename Normalization**：自動補全日期前綴與副檔名清理。
*   [x] **Admin Settings GUI**：後台 `config.js` 設定頁面。
*   [x] **Dashboard Stats**：詳細區分已發布與草稿數量。
*   [x] **Advanced Editor**：整合 TinyMCE 6 (Local Host) 並支援 `<!--more-->`。
*   [x] **Translation**：完整的中英文語系支援 (含 TinyMCE)。

## 5. 其它建議項目

*   [ ] **Server-side Pagination**：為 API 增加 `limit` 與 `offset` 支援。
*   [ ] **Automatic WebP Conversion**：上傳圖片時自動轉換並生成縮圖。

---
**Last Updated**: 2026-02-03
**Recorded by**: Gemini CLI Discussion
