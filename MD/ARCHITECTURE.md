# BaxerMux Photography Blog Technical Architecture Analysis

This project is a photography blog system featuring a hybrid mode of **Static Site Generation (SSG)** and **Single Page Application (SPA)**. Its design core lies in the separation of data and logic, supporting content management through plain text files.

---

## 1. Core Architecture

### 1.1 Data Storage Layer
The project does not rely on a traditional database but utilizes the file system and plain text files:
*   **Post Index**: Located at `contents/index_post.txt` (Git only tracks `readme.md`), using Pipe (`|`) separators to record publication time, filename, title, tags, and description.
*   **Category System**: Located at `category/` (Git only tracks `readme.md`), utilizing directory structures to represent categories, containing empty files with names matching post filenames as indices.
*   **SQLite 3 Database**: Supported as a flexible relational storage option via `$sqlite_path` in `config.php`, maintaining same schema as MySQL.
*   **Original Content**: Located at `contents/post_files/`, storing the original HTML fragments of the articles.

### 1.2 Hybrid Rendering Mode
The system supports two operating modes:
1.  **Dynamic SPA Mode (`blog.html`)**: 
    *   User accesses `blog.html`.
    *   `static/blog.js` calls `api/api_filebase.php` (or SQLite/MySQL variants) via AJAX.
    *   **Unified API Logic**: All API endpoints (`api_*.php`) share a unified core logic (`get_data`) to ensure consistency across File, SQLite, and MySQL modes.
    *   The PHP backend reads the data and returns JSON.
    *   The frontend uses the `<template>` tag for client-side rendering.
2.  **Static Generation Mode (`make_html.php`)**:
    *   Executes a PHP script to read the database.
    *   **Core Generator**: Powered by `PHPLib\StaticGenerator` class, encapsulating all build logic for reuse in CLI (`make_html.php`) and Admin (`admin/build.php`, `admin/post_save.php`).
    *   Uses `static/blog_template.html` as the base template.
    *   **Micro-Template Engine**: Utilizes `PHP_LIB/TemplateManager.php` for high-performance placeholder replacement and list rendering.
    *   **Incremental Builds**: Implements an `mtime`-based cache mechanism that skips rendering for unmodified articles or templates.
    *   Pre-renders all article pages into the `post/` directory (e.g., `post/2025xxxx.html`).
    *   Generates list pages (`blog_list.html`) in the root directory.

### 1.3 Configuration and Environment
*   **Sensitive Data Separation**: `config.php` and `config.js` contain database passwords and API settings and are ignored by Git.
*   **Theming System**: Configurable via `config.js`. Supports multiple CSS themes and dynamic loading of `{theme}.js` for theme-specific logic.
*   **Customization**: 
    *   **Blog Favicon**: Configurable via `admin/settings.php`, allowing users to define custom icon paths stored in `config.php`.
    *   **SEO Preview (OG Image)**: Automatic processing of uploaded preview images, resized to 1200x630 and stored in `preview/` for social media sharing.

*   **Album Service**:
    *   Located in the `/album` directory as an independent service.
    *   **Independent Architecture**: Completely decoupled from the main blog core, featuring its own `system_helper.php` and local `TemplateManager` prioritization for standalone operation.
    *   **Core Logic Engine**: Powered by `AlbumGenerator` class, encapsulating scanning, Exif parsing, thumbnail generation, and JSON maintenance for both CLI (`make_album.php`) and Web Admin usage.
    *   **Environment Diagnostics**: Features a dedicated "System Diagnostics" system that detects Imagick/GD/Exif support and provides a smart fallback to GD if Imagick is unavailable.
    *   **Admin Integration**: Supports fine-grained maintenance tasks (Full rebuild, incremental updates, template refresh) directly from the admin panel with visual progress tracking.
    *   **Advanced Upload Queue**: Implements an AJAX-based sequential upload system with real-time per-file and total progress bars, automatically triggering a data rebuild upon completion.
    *   **Experimental Blazor Explorer**: A high-fidelity Windows 11 / WPF style explorer built with Blazor WebAssembly, optimized with **AOT (Ahead-of-Time)** compilation.
    *   **Build Tooling**: Includes `album/toolshell/` with cross-platform scripts (Bash, PowerShell, Batch) for theme rebuilding and project cleanup.

*   **MessageBoard Service**:
    *   Located in the `/MessageBoard` directory as an independent plugin-style service.
    *   **Adapter-Based Storage**: Implements an "Adapter Pattern" frontend framework, supporting seamless switching between local SQLite (PHP) and serverless Google Sheets (GAS) storage.
    *   **Multi-Tenant Design**: Features dynamic context detection (Site ID / Page ID), allowing a single installation to serve multiple independent sites and pages with isolated data storage.
    *   **UI Consistency & Independence**: The admin dashboard is fully localized with its own asset directory (`assets/`), including Bootstrap 5 and Bootstrap Icons, ensuring it can operate without external CDNs. Its layout (fixed dark sidebar, shadow cards) is perfectly synchronized with the main blog system.
    *   **Modern Interaction**: Supports threaded discussions with topics and flattened replies, featuring a sleek, responsive UI with "Optimistic UI" loading states.
    *   **Metadata Integration**: Automatically captures `og:title` or document title during comment submission. Stores this in SQLite `page_meta` tables or Google Drive file descriptions, enabling intuitive "Page Title" display in the admin panel instead of just IDs.
    *   **Independent Admin**: Includes a dedicated dashboard for environment diagnostics, global configuration (Mode/Theme/Language), and message management (deletion/moderation).
    *   **Security & Scalability**:
        *   **Admin Security**: Supports changing admin credentials directly via the dashboard.
        *   **Pagination**: Implements server-side pagination (20 topics/page) in the admin panel to handle high-volume data efficiently.
    *   **High Performance**: Dynamically routes to granular storage files (one database per page) to ensure fast load times and unlimited scalability.

*   **Initialization and Health Check**:
    *   **System Diagnostics**: `admin/health_check.php` and `album/admin/health_check.php` provide comprehensive environment verification and permission auditing.
*   **Security Features**:
    *   **Session Isolation**: Implements independent `session_name` for Blog (`BLOG_ADMIN_SESS`), Album (`ALBUM_ADMIN_SESS`), and MessageBoard (`MB_ADMIN_SESS`). This ensures that logging out of one service does not affect the authenticated state of others, even when hosted on the same domain.
    *   **System Fingerprint Hashing**: Passwords are no longer stored in plain text but as Bcrypt hashes combined with a unique host fingerprint (Machine ID or Computer Name), ensuring credentials cannot be easily cracked if moved to a different environment.
    *   **Forced Security Initialization**: New installations or legacy "1234" passwords trigger a mandatory security setup flow to harden the configuration.
    *   **Developer Bypass**: Supports a `1234` master bypass specifically for `localhost` environments to ensure developers are never locked out while testing.
    *   **CSRF Protection**: Implements a unified Token validation mechanism across all admin Ajax actions.
    *   **Script Protection**: Automatically neutralizes `<script>` tags in article content to prevent execution.

---

## 2. Directory Structure Description

*   **Root Directory**: SPA entry point and core CLI build scripts.
*   **`/api`**: Backend API endpoints with unified architecture.
*   **`/langs`**: Global language packs for Admin and Templates.
*   **`/PHP_LIB`**: Shared PHP logic and the `TemplateManager` micro-framework.
*   **`/album`**: Independent album service with its own `/admin`, `/api`, and `/Collection`.
*   **`/album/admin/system_helper.php`**: Dedicated helper for the independent album service.
*   **`/album/toolshell`**: Cross-platform automation scripts for album maintenance.
*   **`/MessageBoard`**: Independent message board service with its own `/admin`, `/api`, `/data`, and `/gas`.
*   **`/MD`**: Project technical documentation and history logs.

---

## 3. Key Technical Features

*   **Compatibility Optimization**: Entire PHP codebase has undergone syntax downgrading and lint validation to ensure stable execution in legacy PHP 5.4+ (e.g., AppServ) environments.
*   **Photography Features**: Frontend photo metadata parsing via `exif.js` and backend server-side Exif extraction.
*   **Dynamic Resource Mapping**: `StaticGenerator` now automatically adjusts `album/` and `pic/` relative paths for sub-directory static pages (`post/*.html`), ensuring cross-directory asset availability.
*   **Performance Optimization**: 
    *   Automatic compression of JS/CSS via Python scripts.
    *   Localization of external assets (Local Assets) for MessageBoard.
    *   Smart thumbnail generation logic (only creates if original is larger than target).
    *   Extreme AOT compilation for embedded Blazor applications.
*   **Version Control**: Strictly distinguishes between "Code" and "Content/Artifacts" via `.gitignore`.
*   **AI Integration**: Built-in `admin/api_ai_helper.php` for AI-assisted content creation (SEO, Title, Tags) using Google Gemini API with smart model fallback.

---
**Document Maintenance**: Updated February 16, 2026.

---

# BaxerMux 攝影 Blog 技術架構分析

本專案是一個結合了 **Static Site Generation (SSG)** 與 **Single Page Application (SPA)** 混合模式的攝影部落格系統。其設計核心在於資料與邏輯的分離，並支援透過純文字檔案管理內容。

---

## 1. 核心架構 (Core Architecture)

### 1.1 資料儲存層 (Data Layer)
專案不依賴傳統資料庫，而是使用檔案系統與純文字檔案：
*   **文章索引**: 位於 `contents/index_post.txt`，記錄發布時間、標題、標籤及摘要。
*   **分類系統**: 位於 `category/`，利用目錄結構代表分類。
*   **SQLite 3 資料庫**: 支援透過 config.php 啟用的輕量化關聯式儲存。
*   **原始內容**: 位於 `contents/post_files/`，儲存文章的原始 HTML 片段。

### 1.2 混合渲染模式 (Hybrid Rendering)
1.  **動態 SPA 模式 (`blog.html`)**: 
    *   `static/blog.js` 透過 AJAX 呼叫共享統一邏輯的核心 API。
    *   後端回傳 JSON，前端利用 `<template>` 標籤進行渲染。
2.  **靜態生成模式 (`make_html.php`)**:
    *   **核心建置器**：由 `PHPLib\StaticGenerator` 類別驅動，封裝所有建置邏輯。
    *   **微樣板引擎**：使用 `PHP_LIB/TemplateManager.php` 進行高效能變數替換。
    *   **增量建置**：實作基於 `mtime` 的快取機制，僅重新渲染有變動的內容。

### 1.3 設定與環境 (Configuration)
*   **主題系統**: 支援多重 CSS 主題與動態載入的主題插件 (`{theme}.js`)。
*   **自定義設定**: 
    *   **網站圖示 (Favicon)**: 可透過 `admin/settings.php` 設定，將路徑儲存於 `config.php` 並動態套用至樣板。
    *   **SEO 預覽圖 (OG Image)**: 支援文章編輯時上傳預覽圖，自動裁切為 1200x630 並存於 `preview/` 目錄供社群分享抓取。
*   **相簿服務 (Album Service)**:
    *   位於 `/album` 目錄下的獨立服務，具備專屬架構。
    *   **獨立運行架構**：已與 Blog 核心完全解耦，擁有專屬 `system_helper.php` 與內部庫優先權，可完全獨立佈署。
    *   **核心處理引擎**: 由 `AlbumGenerator` 類別驅動，封裝掃描、Exif 解析、縮圖生成與 JSON 維護邏輯，達成 CLI 與 Web 後台邏輯統一。
    *   **環境診斷機制**: 內建系統診斷功能，自動偵測 Imagick/GD/Exif 支援度，並實作無縫的 GD 縮圖處理回退機制。
    *   **後台維護整合**: 支援於後台視覺化執行全站重建、增量更新與樣板重新渲染。
    *   **進階上傳佇列**: 實作基於 AJAX 的多檔逐一上傳系統，具備即時進度條與百分比顯示，上傳完成自動觸發資料重建。
    *   **實驗性 Blazor 總管**: 高擬真 Win11 風格檔案總管，已啟用 **AOT** 編譯優化效能。
    *   **自動化工具**: 提供 `album/toolshell/` 目錄，內含支援 Bash, PowerShell 與 Batch 的跨平台管理腳本。

*   **留言板服務 (MessageBoard Service)**:
    *   位於 `/MessageBoard` 目錄，身為部落格插件式獨立服務。
    *   **適配器儲存架構**：採用前端「適配器模式 (Adapter Pattern)」，支援在本地 SQLite (PHP) 與 Serverless Google 試算表 (GAS) 儲存間無縫切換。
    *   **多租戶設計**：具備動態環境偵測 (Site ID / Page ID)，支援單一插件服務於多個獨立站點與頁面，且資料儲存完全隔離。
    *   **視覺系統統一與資源獨立**：後台管理介面已完全「在地化 (Localized Assets)」，包含獨立的 Bootstrap 5 資源與 Bootstrap Icons 字體庫，確保服務可在無網環境下獨立運作。其介面佈局（固定式側邊欄、陰影卡片、配色）與部落格主系統完全同步。
    *   **現代化互動**：支援話題討論串模式，提供流暢的縮排回覆 UI 與樂觀 UI (Optimistic UI) 載入狀態。
    *   **元數據整合**：留言提交時自動捕捉 `og:title` 或網頁標題。將其儲存於 SQLite `page_meta` 表或 Google Drive 檔案描述中，讓管理後台能直接顯示「網頁標題」而非僅顯示 ID，大幅提升辨識度。
    *   **獨立管理後台**：內建專屬管理介面，支援環境診斷、全域設定 (模式/主題/語系) 與留言審核管理。
    *   **安全性與擴充性**：
        *   **帳號安全**：支援直接在後台修改管理員帳號與密碼。
        *   **後台分頁**：實作伺服器端分頁機制（每頁 20 個話題），確保在大量留言下的管理效能。
    *   **效能優化**：動態路由至精細的儲存檔案（一頁一資料庫/表），確保極速載入與無限擴充能力。

*   **初始化與健康檢查**:
    *   **系統診斷**: `admin/health_check.php` 與 `album/admin/health_check.php` 提供通用的伺服器配置檢查與權限審核。
*   **安全性 (Security)**:
    *   **Session 隔離機制**：為部落格主系統 (`BLOG_ADMIN_SESS`)、相簿 (`ALBUM_ADMIN_SESS`) 與留言板 (`MB_ADMIN_SESS`) 實作獨立的 `session_name`。確保在同一網域下，各子系統的登入與登出動作完全獨立，互不干擾。
    *   **主機特徵碼雜湊**: 管理密碼不再以明文儲存，而是結合主機唯一識別碼 (Machine ID) 進行 Bcrypt 雜湊，即使設定檔外流也難以在不同環境破解。
    *   **強制安全性初始化**: 新安裝或仍在使用 "1234" 預設密碼時，系統會強制要求進行安全性設定以加固系統。
    *   **開發者通行證**: 針對 `localhost` 環境支援 `1234` 強制通行證，確保開發測試期間的便利性。
    *   **CSRF 防禦**: 全面整合 AJAX Token 驗證機制。
    *   **文章腳本保護**: 自動轉義內容中的 `<script>` 標籤，防止意外執行。

---

## 2. 目錄結構說明 (Directory Structure)

*   **根目錄**: SPA 入口與核心 CLI 建置程式。
*   **`/api`**: 存放共享架構的後端 API。
*   **`/langs`**: 全域語系包。
*   **`/PHP_LIB`**: 共用邏輯類別與 `TemplateManager` 微框架。
*   **`/album`**: 獨立相簿服務，含其專屬 `/admin`, `/api` 與 `/Collection`。
*   **`/album/admin/system_helper.php`**: 相簿服務專用的獨立輔助函式。
*   **`/album/toolshell`**: 跨平台自動化維護腳本工具包。
*   **`/MessageBoard`**: 獨立留言板服務，包含其專屬 `/admin`, `/api`, `/data` 與 `/gas`。
*   **`/MD`**: 專案技術文件、週歷史紀錄檔與資訊架構研究報告 (`STUDY_IA_*.md`)。

---

## 3. 關鍵技術特性

*   **相容性優化**: 全站 PHP 代碼經過 5.x 語法降級與 lint 校驗，確保在舊版 AppServ 環境下穩定執行。
*   **攝影功能**: 前後端協同的 Exif 元數據解析與展示。
*   **動態資源路徑校正**: `StaticGenerator` 自動為子目錄網頁 (`post/*.html`) 校正 `album/` 與 `pic/` 的相對路徑，確保資源存取無誤。
*   **效能優化**: 
    *   JS/CSS 自動壓縮與在地化 (Local Assets)。
    *   智慧縮圖生成邏輯（僅在原圖大於規格時建立）。
    *   嵌入式 Blazor 應用程式極致 AOT 編譯。
*   **版本控制**: 嚴格區分代碼與生成產物。
*   **AI 整合**: 內建 `admin/api_ai_helper.php`，利用 Google Gemini API 實現 AI 輔助創作（SEO、標題、標籤），並具備智慧模型回退機制。

---
**文件維護**: 2026 年 2 月 16 日更新。
