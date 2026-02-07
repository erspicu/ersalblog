# Blog Project Roadmap & Feature Proposals (v2026.02.07)

This document organizes the discussions and evaluations regarding future feature expansions for the BaxerMux Photography Blog. It serves as a strategic reference for long-term development.

---

## 1. Core System Infrastructure

### 1.1 Hybrid Pagination System (Status: COMPLETED ✅)
*   **Reasoning**: As the post count grows, loading all posts via a single JSON request impacts performance. Essential for both SPA and static JSON environments.
*   **Implementation**: 
    *   **Server-side (PHP)**: Implemented `page` parameter in `api_*.php` to slice data and lazy-load content files, drastically reducing IO load.
    *   **Client-side (JSON Mode)**: Implemented browser-side slicing in `blog.js` for 100% backend-less environments.
    *   **UI**: Added a beautified pagination component with Prev/Next navigation and active state styling.

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
    *   **Script Tag Protection**: Neutralizes `<script>` tags in user-generated content to prevent XSS while maintaining visibility for technical posts.
    *   **Vulnerability Fixes**: Implemented strict `basename` filtering for path traversal protection.

### 1.5 Architecture & API Refactoring (Status: COMPLETED ✅)
*   **Implementation**:
    *   **Unified API Logic**: Refactored `api_filebase.php`, `api_sqlitebase.php`, and `api_dbsqlbase.php` to share a single core logic, eliminating 90% of duplicate code.
    *   **Generator Class**: Encapsulated build logic in `PHPLib\StaticGenerator` for consistent rendering across CLI and Admin UI.
    *   **Incremental Builds**: Implemented `mtime`-based cache and content hash detection to speed up site rebuilding.

### 1.6 Draft System & Status Management (Status: COMPLETED ✅)
*   **Implementation**: 
    *   Dual-mode storage support (.html.tmp vs status column). 
    *   Integrated visibility filtering across all APIs and build tools.

### 1.7 Pure Static Mode (Status: COMPLETED ✅)
*   **Implementation**: 
    *   Single consolidated `data.json` source. 
    *   Client-side routing and pagination in `blog.js`.

### 1.8 PHP 5.x Compatibility (Status: COMPLETED ✅)
*   **Implementation**: Systematic syntax downgrading and polyfills (e.g., `random_bytes`) to ensure stability on legacy shared hosting.

---

## 2. Content Creation & Management

### 2.1 Advanced Post Editor (Status: COMPLETED ✅)
*   **Implementation**: Locally hosted TinyMCE 6 with custom PageBreak and dynamic i18n support.

### 2.2 Media Management Library (Priority: Medium)
*   **Reasoning**: Managing photos manually in folders is tedious.
*   **Proposal**: A dedicated UI to browse, search, and reuse uploaded images across different posts.

### 2.3 Filename Normalization (Status: COMPLETED ✅)
*   **Implementation**: Automatic `YYYYMMDD-` prefixing with smart duplicate detection.

### 2.4 Configuration Management (Status: COMPLETED ✅)
*   **Implementation**: Admin **Settings Page** for graphical editing of `config.js` and `config.php` (Lang/Timezone/Pagination).

---

## 3. Photography-Centric Features

### 3.1 Automatic Thumbnail Generation (Priority: High)
*   **Proposal**: Use PHP (GD/Imagick) to generate WebP thumbnails/medium sizes upon upload. Essential for improving LCP on photo-heavy pages.

### 3.2 Geotagging Map Integration (Priority: Low)
*   **Proposal**: Visualize photo locations using existing GPS EXIF data on an interactive map.

---

## 4. UI/UX & Social Engagement

### 4.1 Theme System Expansion (Status: COMPLETED ✅)
*   **Implementation**: 
    *   **Standard**: High-contrast light mode.
    *   **Dark**: Accessibility-optimized dark mode.
    *   **Pink**: Soft & elegant theme for life-style/feminine content.
    *   **Matrix**: Hacker-style terminal aesthetic with scanline effects.

### 4.2 Front-end Internationalization (Status: COMPLETED ✅)
*   **Implementation**: Fully decoupled template variables, localized date formatting, and dynamic HTML lang attributes.

---

# 部落格專案開發藍圖與功能提案 (v2026.02.07)

---

## 1. 核心系統架構

### 1.1 混合式分頁系統 (狀態：已完成 ✅)
*   **實作**：
    *   **伺服器端 (PHP)**：優化 API 核心邏輯，支援 `page` 參數進行延遲加載文章內容，大幅節省伺服器資源。
    *   **客戶端 (JSON)**：在全靜態模式下由前端 `blog.js` 執行切割，模擬 API 行為。
    *   **UI/UX**：建立美化分頁按鈕組件，支援上一頁/下一頁導覽與深淺色主題切換。

### 1.2 SQLite 支援 (狀態：已完成 ✅)
*   **實作**：實作 `DataManager` 支援與初始化工具，達成零設定資料庫部署。

### 1.3 進階備份與資料遷移 (狀態：已完成 ✅)
*   **實作**：支援全站 ZIP 備份、SQL 轉儲以及「檔案 <-> 資料庫」雙向遷移功能。

### 1.4 安全性強化 (狀態：已完成 ✅)
*   **實作**：實作 CSRF 防護、登入嘗試頻率限制、API 路徑遍濾過濾，以及**文章 Script 標籤轉義保護**。

### 1.5 API 與架構重構 (狀態：已完成 ✅)
*   **實作**：
    *   **API 統一化**：全面重構 `api/*.php`，消除 90% 重複代碼，統一採用 `get_data` 核心邏輯。
    *   **建置器封裝**：建立 `StaticGenerator` 類別統一處理 SSG 邏輯，並支援增量建置與雜湊比對。

### 1.6 PHP 5.x 相容性 (狀態：已完成 ✅)
*   **實作**：完成全站語法降級與 Polyfills 實作，確保在 AppServ 等舊版環境穩定執行。

---

## 2. 內容創作與管理

### 2.1 視覺化文章編輯器 (狀態：已完成 ✅)
*   **實作**：本地端部署 TinyMCE 6，支援視覺化分頁符號與多語系切換。

### 2.2 媒體管理庫 (優先級：中)
*   **提案**：建立統一的圖片管理介面，支援搜尋與跨文章重複使用已上傳的照片。

### 2.3 系統設定圖形化 (狀態：已完成 ✅)
*   **實作**：新增後台設定頁面，可直接修改資料來源、語系、時區、主題及分頁數量。

---

## 3. 攝影師專屬特性

### 3.1 自動縮圖生成 (優先級：高)
*   **提案**：上傳時自動生成 WebP 縮圖，提升首頁載入速度 (LCP 優化)。

---

## 4. UI/UX 與風格

### 4.1 多元主題系統 (狀態：已完成 ✅)
*   **實作**：實作了標準、深色 (`Dark`)、粉柔 (`Pink`) 與駭客任務 (`Matrix`) 四款主題，支援後台即時切換。

### 4.2 前端多語系 (狀態：已完成 ✅)
*   **實作**：實作樣板變數解耦與在地化日期格式，支援前台完整雙語顯示。

---
**Last Updated**: 2026-02-07
**Source**: Discussion between User & Gemini CLI