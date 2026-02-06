# Blog Project Roadmap & Feature Proposals (v2026.02.04)

This document organizes the discussions and evaluations regarding future feature expansions for the BaxerMux Photography Blog. It serves as a strategic reference for long-term development.

---

## 1. Core System Infrastructure

### 1.1 Server-side Pagination (Priority: High)
*   **Reasoning**: As the post count grows, loading all posts via a single JSON request will impact performance and UX.
*   **Recommendation**: Implement `limit` and `offset` in the DB/File APIs. Essential for both SPA and static list generation.

### 1.2 SQLite Support (Status: COMPLETED ✅)
*   **Reasoning**: Perfect balance between SQL power and file-system portability. Enables "Zero Configuration" deployment.
*   **Implementation**: Leveraged existing PDO architecture; implemented via `api_sqlitebase.php` and `DataManager`.

### 1.3 Advanced Database Export & Backup (Status: COMPLETED ✅)
*   **Reasoning**: Data is the soul of the blog. Dual-mode support allows for unique backup strategies.
*   **Implementation**: 
    *   One-click SQL dump and full-site ZIP (database + static resources).
    *   **Hot Backup**: Built-in "DB to File" and "File to DB" bidirectional migration tools.
    *   **Optimization**: Excluded heavy `pic/` directory from backups to reduce size.

### 1.4 Security Enhancements (Status: COMPLETED ✅)
*   **Implementation**: 
    *   **CSRF Protection**: Token-based validation for all data-changing actions.
    *   **Rate Limiting**: Login lockout mechanism via `attempts.log` (5 fails / 15 mins).
    *   **Session Hardening**: HttpOnly, SameSite=Strict, and ID regeneration.

### 1.5 Architecture Refactoring (Status: COMPLETED ✅)
*   **Implementation**:
    *   **API Centralization**: Moved `api_*.php` files to `api/` directory.
    *   **Static Output**: Generated static pages are now organized in `post/` directory.
    *   **Resource Paths**: Automated `../` relative path correction for static generation.
    *   **Static Generation Optimization**:
        *   **Single Source**: Rewrote `make_html.php` to generate all pages directly from `blog_template.html`.
        *   **Performance**: Replaced `DOMDocument` with RegEx parsing to support HTML5 `<template>` tags correctly and fix PHP version compatibility issues.
        *   **Path Fixes**: Corrected relative path injection for CSS/JS resources in subdirectories.

### 1.6 Draft System & Status Management (Status: COMPLETED ✅)
*   **Reasoning**: Users need to save work-in-progress content without publishing it immediately.
*   **Implementation**:
    *   **Storage**: 
        *   File Mode: Uses `.html.tmp` extension for drafts.
        *   DB Mode: Added `status` column to schema ('published' vs 'draft').
    *   **Visibility**: Frontend APIs and Static Generator automatically filter out drafts.
    *   **UI**: Dashboard badges, distinct "Save Draft" buttons, and stats breakdown.

### 1.7 Pure Static Mode (Status: COMPLETED ✅)
*   **Reasoning**: Hosting on static servers (like GitHub Pages or AWS S3) often fails with non-ASCII filenames (CJK characters) causing 404 errors.
*   **Implementation**: 
    *   **Single Source**: Consolidated all post data into a single `api/json/data.json` file.
    *   **Client-Side Logic**: Refactored `blog.js` to handle routing, filtering, and pagination on the client side without needing physical subdirectories.
    *   **Build Support**: Updated `make_html.php` with a `-json` flag to export the complete data package.

### 1.8 Smart Build System (Status: COMPLETED ✅)
*   **Reasoning**: Rebuilding thousands of pages for every minor configuration change is inefficient and slow.
*   **Implementation**: 
    *   **Granular Caching**: Implemented `TemplateManager` with modification time (mtime) checks.
    *   **Hash Detection**: Added content hash comparison to distinguish between 'Global' updates (header/footer changes requiring full rebuild) and 'Local' updates (requiring only index updates).

### 1.9 PHP 5.x Compatibility (Status: COMPLETED ✅)
*   **Reasoning**: Ensures the blog can run on legacy hosting environments frequently encountered in photography communities.
*   **Implementation**: 
    *   **Syntax Downgrade**: Replaced Null Coalescing operators (`??`) with `isset() ? :` and short array syntax (`[]`) with `array()`.
    *   **Fallbacks**: Added `random_bytes()` fallback for PHP versions older than 7.0.
    *   **Stability**: Systematic manual verification of core logic across all admin and API files.

---

## 2. Content Creation & Management

### 2.1 Advanced Post Editor (Status: COMPLETED ✅)
*   **Reasoning**: The previous `textarea` required manual HTML input, which was a barrier for non-technical photographers.
*   **Implementation**: 
    *   **TinyMCE 6**: Integrated the Community Edition locally (no CDN dependency).
    *   **Customization**: Added `<!--more-->` support via the PageBreak plugin.
    *   **Localization**: Dynamic UI language switching based on user login preference (zh_TW/en_US).

### 2.2 Media Management Library
*   **Reasoning**: Managing photos manually in folders is tedious.
*   **Proposal**: A dedicated UI to browse, search, and reuse uploaded images across different posts.

### 2.3 Filename Normalization (Status: COMPLETED ✅)
*   **Reasoning**: Ensure consistent URL structure and file organization.
*   **Implementation**: 
    *   Auto-prefixing filenames with `YYYYMMDD-` based on post date.
    *   Smart detection to avoid duplicate prefixes.
    *   Input sanitization in Admin UI.

### 2.4 Configuration Management (Status: COMPLETED ✅)
*   **Reasoning**: Editing `config.js` manually via FTP/Shell is user-unfriendly.
*   **Implementation**: 
    *   Added dedicated **Settings Page** in Admin Panel.
    *   GUI support for API Type switching, Theme selection, and Google CSE ID.

### 2.5 Minification Pipeline (Status: COMPLETED ✅)
*   **Reasoning**: Reduce file size for better performance without manual effort.
*   **Implementation**:
    *   `mini.py` script automates JS/CSS compression using `terser` and `clean-css-cli`.
    *   Smart exclusion logic protects third-party libraries (`admin/assets`, `exif.js`).
    *   Auto-cleanup features removes mistakenly generated minified files.

---

## 3. Photography-Centric Features

### 3.1 Automatic Thumbnail Generation (Priority: High)
*   **Reasoning**: Directly loading original high-res photos kills performance (LCP).
*   **Proposal**: Use PHP (GD/Imagick) to generate WebP thumbnails/medium sizes upon upload. Display small versions in lists and full versions in post views.

### 3.2 Integrated Album Service System (Priority: Medium)
*   **Reasoning**: Expands the site from a "Blog" to a "Portfolio."
*   **Implementation**: Either a dedicated `Album` module or a specific "Gallery Post Type" with Grid layouts and Lightbox effects.

### 3.3 Geotagging Map Integration
*   **Proposal**: Visualize photo locations using the existing GPS EXIF data. Integrate Leaflet or OpenStreetMap to show a "Photo Map."

---

## 4. Cloud & External Integration

### 4.1 Flickr Integration (Priority: Medium)
*   **Reasoning**: Saves host bandwidth/storage and utilizes Flickr's global CDN.
*   **Implementation**: Use Flickr API to create a "Photo Picker" in the admin panel to import remote links directly into posts.

### 4.2 Google Sheets as Data Source
*   **Reasoning**: Provides a "Headless CMS" feel for writing on the go.
*   **Proposal**: Best used as an **Import Source** or a **Static Generation Source** (SSG) rather than a real-time database due to API latency.

### 4.3 Remote Database Sync (Cross-Host)
*   **Reasoning**: Conceptually attractive for easy site migration.
*   **Warning**: High risk due to firewall blocks (Port 3306) and security concerns.
*   **Better Alternative**: Implement a custom API-based transfer or stick to the "File Mode Sync" for robust cross-host migration.

---

## 5. UI/UX & Social Engagement

### 5.1 Style & Theme Settings (Status: COMPLETED ✅)
*   **Reasoning**: Visual "Vibe" is critical for photographers.
*   **Implementation**: 
    *   **Theme Switcher**: Configurable via `config.js` (`theme_file`) and Admin UI.
    *   **Dark Mode**: Implemented `blog-dark.css` with high-contrast accessibility optimizations.
    *   **Installation**: Integrated theme selection into the installation wizard.

### 5.2 Social Interaction
*   **Proposal**: 
    *   **Sharing Buttons**: High impact, low effort.
    *   **Comments**: Use 3rd-party systems like Disqus or Giscus to avoid the complexity of building a secure in-house comment engine.

### 5.3 Front-end Internationalization (Status: COMPLETED ✅)
*   **Reasoning**: The blog needs to serve a global audience, matching the admin panel's capabilities.
*   **Implementation**: 
    *   **Template Logic**: Decoupled hardcoded text in `blog_template.html` using `{{variables}}`.
    *   **System**: Created `langs/template/` architecture for easy language expansion.
    *   **Dynamic Attributes**: Implemented dynamic `<html>` lang attributes and localized date formatting.

---

# 部落格專案開發藍圖與功能提案 (v2026.02.06)

此文件整理了關於 BaxerMux 攝影部落格未來功能擴充的討論與評估，作為長期開發的戰略參考。

---

## 1. 核心系統架構

### 1.1 伺服器端分頁 (優先級：高)
*   **理由**：隨著文章數量增加，單次載入所有內容會影響效能與體驗。
*   **建議**：API 應支援 `limit` 與 `offset`。對 SPA 與靜態列表生成皆為必要。

### 1.2 SQLite 支援 (狀態：已完成 ✅)
*   **理由**：兼具 SQL 功能與檔案系統的便攜性，達成「零設定」佈署。
*   **實作**：已實作 `api_sqlitebase.php` 與 `DataManager` 支援，並提供初始化工具。

### 1.3 進階資料庫匯出與備份 (狀態：已完成 ✅)
*   **理由**：數據是部落格的靈魂。雙模架構提供了獨特的備份優勢。
*   **實作**：
    *   實作一鍵產生 SQL 備份與全站 ZIP（含資料庫與靜態資源）。
    *   **資料遷移**：實作了完善的資料庫與檔案系統雙向遷移與還原工具。
    *   **優化**：備份時排除 `pic/` 目錄以減小檔案體積。

### 1.4 安全性強化 (狀態：已完成 ✅)
*   **實作**：
    *   **CSRF 防護**：所有資料變更操作均已加入 Token 驗證。
    *   **登入限制**：實作基於 IP 的 `attempts.log` 鎖定機制（5 次失敗鎖 15 分鐘）。
    *   **Session 強化**：強制 HttpOnly、SameSite=Strict 以及登入後 ID 重生。
    *   **漏洞修復**：實作了嚴格的 API 路徑遍歷防護 (`basename`) 與樣板 XSS 轉義機制。

### 1.5 架構重構 (狀態：已完成 ✅)
*   **實作**：
    *   **API 集中化**：將 `api_*.php` 檔案移至 `api/` 目錄。
    *   **靜態輸出**：靜態網頁現在會統一生成至 `post/` 目錄。
    *   **路徑修正**：實作了 `../` 相對路徑的自動修正機制。
    *   **靜態生成優化**：
        *   **單一來源**：重寫 `make_html.php`，統一由 `blog_template.html` 生成所有頁面。
        *   **效能提升**：移除 DOMDocument，改用 Regex 解析，解決了 HTML5 `<template>` 結構錯亂問題並提升了 PHP 版本相容性。
        *   **路徑修復**：修正了子目錄靜態頁面的 CSS/JS 資源引用路徑。

### 1.6 草稿系統與狀態管理 (狀態：已完成 ✅)
*   **理由**：使用者需要暫存未完成的文章，而不直接發佈。
*   **實作**：
    *   **儲存**：檔案模式使用 `.html.tmp` 副檔名；資料庫模式新增 `status` 欄位。
    *   **隱蔽性**：前台 API 與靜態生成器會自動過濾草稿，確保不外流。
    *   **UI**：後台提供「暫存草稿」按鈕、狀態標籤 (Badge) 與詳細統計數據。

### 1.7 純靜態模式 (狀態：已完成 ✅)
*   **理由**：靜態主機 (如 GitHub Pages) 常因中文檔名導致 404 錯誤。
*   **實作**：
    *   **單一資料源**：將所有文章數據整合為單一 `api/json/data.json` 檔案。
    *   **客戶端邏輯**：重構 `blog.js`，在前端處理路由與篩選，無需依賴實體子目錄。
    *   **建置支援**：`make_html.php` 新增 `-json` 參數以匯出完整資料包。

### 1.8 智慧建置系統 (狀態：已完成 ✅)
*   **理由**：每次修改設定都需重建上千個頁面，效率極低。
*   **實作**：
    *   **增量快取**：實作基於檔案修改時間 (mtime) 的 `TemplateManager` 快取。
    *   **雜湊偵測**：加入內容 Hash 比對，區分「全域更新」(需重建全站) 與「局部更新」(僅更新首頁)。

### 1.9 PHP 5.x 相容性強化 (狀態：已完成 ✅)
*   **理由**：確保部落格能在攝影社群常見的舊型主機環境中穩定運作。
*   **實作**：
    *   **語法降級**：將 Null Coalescing (`??`) 替換為 `isset() ? :`，並將短陣列語法 (`[]`) 替換為 `array()`。
    *   **回退方案**：針對 PHP 7.0 以下版本新增了 `random_bytes()` 的相容回退函式。
    *   **穩定性**：全面手動校閱後台與 API 核心邏輯，確保舊版 PHP 環境下的語法正確性。

---

## 2. 內容創作與管理

### 2.1 進階文章編輯器 (狀態：已完成 ✅)
*   **理由**：現有的 `textarea` 門檻較高，對非技術背景攝影師不友善。
*   **實作**：
    *   **TinyMCE 6**：已在本地端完整部署 (Local Host)，不依賴 CDN。
    *   **客製化**：支援 `<!--more-->` 分頁符號插入與視覺化顯示。
    *   **本地化**：支援後台介面語系 (中/英) 自動切換。

### 2.2 媒體管理庫
*   **理由**：手動管理資料夾內的圖片較為繁瑣。
*   **提案**：建立統一介面瀏覽、搜尋及重複使用已上傳的照片。

### 2.3 檔名標準化 (Status: 已完成 ✅)
*   **理由**：確保網址結構統一與檔案管理的一致性。
*   **實作**：
    *   系統自動檢查並補上 `YYYYMMDD-` 日期前綴。
    *   智慧偵測使用者輸入，避免產生重複前綴。
    *   後台欄位提示優化。

### 2.4 系統設定管理 (Status: 已完成 ✅)
*   **理由**：手動修改 `config.js` 容易出錯且不直覺。
*   **實作**：
    *   新增後台 **網站設定 (Settings)** 頁面。
    *   支援圖形化切換 API 模式、網站主題與設定 Google CSE ID。

### 2.5 自動化壓縮流程 (Status: 已完成 ✅)
*   **理由**：提升網站載入效能，同時簡化發布流程。
*   **實作**：
    *   優化 `mini.py` 腳本，自動調用 `terser` 與 `clean-css-cli`。
    *   加入智慧排除清單，保護第三方套件 (`admin/assets`, `exif.js`) 不被錯誤壓縮。
    *   自動清理機制，防止產生多餘的 `.min` 檔案。

---

## 3. 攝影師專屬特性

### 3.1 自動縮圖生成 (優先級：高)
*   **理由**：直接讀取高解析度原圖會導致首屏載入過慢。
*   **提案**：上傳時自動生成 WebP 縮圖。列表顯示小圖，進入文章後才載入大圖。

### 3.2 整合相簿服務系統 (優先級：中)
*   **理由**：將部落格提升為「個人攝影作品集」。
*   **實作**：可作為獨立 `Album` 模組，或特殊的「相簿文章類型」，提供網格佈局與 Lightbox 效果。

### 3.3 地圖整合 (Geotagging)
*   **提案**：利用照片中的 GPS EXIF 資訊，在地圖上標示拍攝地點，建立「攝影地圖」。

---

## 4. 雲端與外部整合

### 4.1 Flickr 整合 (優先級：中)
*   **理由**：節省主機空間、利用全球 CDN 提升速度。
*   **實作**：透過 Flickr API 建立「相簿選擇器」，直接將 Flickr 照片連結匯入文章。

### 4.2 Google Sheets 作為資料來源
*   **理由**：提供「隨時隨地寫作」的便利性。
*   **提案**：適合作為「外部匯入源」或「SSG 的靜態生成源」，不建議作為實時查詢的核心庫。

### 4.3 遠端資料庫同步 (跨主機匯入)
*   **理由**：一鍵遷移網站極具吸引力。
*   **警示**：風險高（防火牆阻擋、安全性）。
*   **替代方案**：建議改用 API 傳輸，或維持現有的「檔案模式同步」作為最穩健的遷移手段。

---

## 5. UI/UX 與社群互動

### 5.1 樣式與主題設定 (狀態：已完成 ✅)
*   **理由**：視覺氛圍是攝影部落格的靈魂。
*   **實作**：
    *   **主題切換**：透過 `config.js` (`theme_file`) 與後台 UI 進行配置。
    *   **深色模式**：實作 `blog-dark.css` 並針對無障礙對比度進行優化。
    *   **安裝整合**：已將主題選擇整合至安裝精靈中。

### 5.2 社群互動
*   **提案**：
    *   **分享按鈕**：高回報、低成本的功能。
    *   **評論系統**：建議整合 Disqus 或 Giscus 等第三方系統以維持安全性。

### 5.3 前端國際化 (狀態：已完成 ✅)
*   **理由**：部落格需服務全球讀者，前台應具備與後台同等的多語系能力。
*   **實作**：
    *   **樣板邏輯**：使用 `{{variables}}` 解耦 `blog_template.html` 內的硬編碼文字。
    *   **系統架構**：建立 `langs/template/` 架構，易於擴充新語系。
    *   **動態屬性**：實作動態 `<html>` lang 屬性與在地化日期格式。

---
**Document Created**: 2026-02-01
**Last Updated**: 2026-02-06
**Source**: Discussion between User & Gemini CLI