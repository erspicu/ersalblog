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
    *   **Hybrid Pagination**: Implements server-side slicing for PHP modes (reducing IO load) and client-side slicing for Static JSON mode, using a unified `pagination` metadata structure.
    *   The PHP backend reads the data and returns JSON.
    *   The frontend uses the `<template>` tag for client-side rendering.
2.  **Static Generation Mode (`make_html.php`)**:
    *   Executes a PHP script to read the database.
    *   **Core Generator**: Powered by `PHPLib\StaticGenerator` class, encapsulating all build logic for reuse in CLI (`make_html.php`) and Admin (`admin/build.php`, `admin/post_save.php`).
    *   Uses `static/blog_template.html` as the base template.
    *   **Micro-Template Engine**: Utilizes `PHP_LIB/TemplateManager.php` for high-performance placeholder replacement and list rendering.
    *   **Regex Pipeline**: Employs Regex-based parsing instead of DOMDocument to ensure HTML5 compatibility and stability across different PHP versions (5.x+).
    *   **Incremental Builds**: Implements an `mtime`-based cache mechanism that skips rendering for unmodified articles or templates.
    *   **Selective Generation**: Supports building specific posts via filename filtering to optimize automated workflows.
    *   Pre-renders all article pages into the `post/` directory (e.g., `post/2025xxxx.html`).
    *   Generates list pages (`blog_list.html`) in the root directory.
    *   **Note**: These generated `.html` files (and the `post/` directory) are excluded by `.gitignore`.

### 1.3 Configuration and Environment
*   **Sensitive Data Separation**: 
    *   `config.php` and `config.js` contain database passwords and API settings and are ignored by Git.
*   **Theming System**:
    *   Configurable via `config.js` (`theme_file` option).
    *   Supports multiple CSS themes: `blog.css` (Standard), `blog-dark.css` (Dark Mode), `blog-pink.css` (Soft Pink), and `blog-matrix.css` (Hacker Style).
    *   **Frontend Theme Plugins**: Dynamic loading of `{theme}.js` for theme-specific logic (e.g., custom animations, interaction, or media players).

*   **Album Service**:
    *   Located in the `/album` directory as an independent service.
    *   Features a dedicated SSG and SPA architecture.
    *   Supports diverse visual themes including `album-matrix` (with Digital Rain) and `album-y2k` (Retro 2000s style).
    *   **Experimental Blazor Explorer**: A high-fidelity Windows 11 / WPF style explorer built with Blazor WebAssembly, providing an alternative immersive browsing experience.
    *   Integrates YouTube IFrame API for background music in specific themes.

*   **Initialization and Health Check**:
    *   **Installation Wizard**: `install.php` provides a user-friendly interface to initialize the system and check environment compatibility. Dedicated installation wizard for Album Service is also available at `album/install.php`.
    *   **Login Check**: `admin/login.php` integrates `admin/health_check.php` to automatically verify database connection and file system integrity.
*   **Backup & Recovery System**:
    *   **Multi-mode Backup**: `admin/tool_backup.php` creates ZIP archives for File, MySQL, or SQLite modes.
*   **Internationalization (i18n)**:
    *   **Template i18n**: Supports multi-language frontend via `langs/` (e.g., `template-zh_TW.php`).
    *   **Admin i18n**: Admin panel supports dynamic language switching.
*   **Pure Static JSON API Mode**:
    *   Supports `api_type: 'json'` in `config.js`.
    *   `make_html.php -json` exports all data into a single consolidated `api/json/data.json`.
    *   `static/blog.js` implements client-side routing and filtering for a 100% backend-less experience.
*   **Admin Settings GUI**:
    *   **Settings Page**: `admin/settings.php` allows graphical editing of `config.js` and `config.php` (Lang/Timezone) settings.
*   **Navigation & Layout**:
    *   **Unified Sidebar**: Components via `admin/sidebar_inc.php` for consistency.
    *   **Fixed Layout**: Sidebar remains fixed while content area is scrollable.
*   **Security Features**:
    *   **Script Tag Protection**: Implements `protect_script_tags` to neutralize `<script>` tags in article content by escaping them to `&lt;script&gt;`, preventing execution while maintaining visibility for technical posts.

---

## 2. Directory Structure Description

Listed below are key directories and file rules in the Git repository:

*   **Root Directory**:
    *   `blog.html`: Core SPA entry point.
    *   `make_html.php`: Optimized static site generator (CLI wrapper).
    *   `mini.py`: Python automation script for minification (with smart ignore logic).
    *   `install.php`: System installation wizard.

*   **`/api`**:
    *   Contains backend API endpoints (`api_filebase.php`, `api_dbsqlbase.php`, `api_sqlitebase.php`) sharing a unified architecture.
    *   `json/`: (Ignored) Contains pre-generated `data.json` for static mode.

*   **`/langs`**:
    *   Contains language packs for Admin, Templates, and Installer.

*   **`/PHP_LIB`**:
    *   Contains shared libraries, `StaticGenerator.php` core, and the `TemplateManager.php` micro-framework.

*   **`/post`**:
    *   (Ignored) Destination for statically generated article HTML files.

*   **`/MD`**:
    *   Project documentation (`ARCHITECTURE.md`, `HISTORY.md`, `ROADMAP.md`, `gemini_log.md`).

*   **`/contents`**:
    *   Core of blog content. `index_post.txt` and `post_files/` are ignored by default.

*   **`/static`**:
    *   Contains `blog.js` (core logic) and `blog_template.html` (SPA template source).

*   **`/admin`**:
    *   Backend management system.
    *   `build.php`: Dedicated interface for manual site rebuilding.

---

## 3. Key Technical Features

*   **Photography Features**: Frontend integrates `exif.js` to automatically parse and display photo metadata.
*   **Template Decoupling**: Business logic is separated from HTML rendering via the `TemplateManager` class.
*   **Performance Optimization**: 
    *   `mini.py` automatically compresses JS/CSS while protecting vendor assets.
    *   Image loading strategy: LCP (first image) Eager Loading, others Lazy Loading.
    *   Static generation optimizes resource paths (`../`) for subdirectories.
    *   **Server-side Pagination**: Admin interface supports paginated list retrieval for large datasets.
*   **Version Control**: Strictly distinguishes between "Code" and "Content/Artifacts" via `.gitignore`.

---
**Document Maintenance**: This document reflects the project architecture as of February 2026.

---

# BaxerMux 攝影 Blog 技術架構分析

本專案是一個結合了 **Static Site Generation (SSG)** 與 **Single Page Application (SPA)** 混合模式的攝影部落格系統。其設計核心在於資料與邏輯的分離，並支援透過純文字檔案管理內容。

---

## 1. 核心架構 (Core Architecture)

### 1.1 資料儲存層 (Data Layer)
專案不依賴傳統資料庫，而是使用檔案系統與純文字檔案：
*   **文章索引**: 位於 `contents/index_post.txt` (Git 僅追蹤 `readme.md`)，採用 Pipe (`|`) 分隔，記錄發布時間、檔名、標題、標籤及摘要。
*   **分類系統**: 位於 `category/` (Git 僅追蹤 `readme.md`)，利用資料夾結構代表分類，內含對應文章檔名的空檔案作為索引。
*   **SQLite 3 資料庫**: 支援透過 config.php 中的 `$sqlite_path` 啟用輕量化關聯式儲存，與 MySQL 共享相同的資料表架構。
*   **原始內容**: 位於 `contents/post_files/`，儲存文章的原始 HTML 片段。

### 1.2 混合渲染模式 (Hybrid Rendering)
本系統支援兩種運作模式：
1.  **動態 SPA 模式 (`blog.html`)**: 
    *   使用者存取 `blog.html`。
    *   `static/blog.js` 透過 AJAX 呼叫 `api/api_filebase.php` (或 SQLite/MySQL 版本)。
    *   **統一 API 邏輯**: 所有 API 端點 (`api_*.php`) 共享統一的 `get_data` 核心邏輯，確保 File、SQLite 與 MySQL 模式間的行為一致性。
    *   **混合式分頁**: 實作 PHP 模式下的伺服器端切割（減少 IO 負擔）與靜態 JSON 模式下的客戶端切割，並使用統一的 `pagination` 元數據結構。
    *   後端讀取資料並回傳 JSON。
    *   前端利用 `<template>` 標籤進行客戶端渲染。
2.  **靜態生成模式 (`make_html.php`)**:
    *   執行 PHP 腳本讀取資料。
    *   **核心建置器**：由 `PHPLib\StaticGenerator` 類別驅動，封裝所有建置邏輯，供 CLI (`make_html.php`) 與後台管理介面 (`admin/build.php`, `admin/post_save.php`) 共同調用。
    *   利用 `static/blog_template.html` 作為基底樣板。
    *   **微樣板引擎**：使用 `PHP_LIB/TemplateManager.php` 進行高效能變數替換與列表渲染。
    *   **Regex 建置管線**：全面改用正規表達式取代 DOMDocument，解決 HTML5 相容性問題並支援 PHP 5.x+ 環境。
    *   **增量建置快取**：實作基於 `mtime` 的快取機制，自動比對來源檔與目標檔時間，僅重新渲染有變動的內容。
    *   **選擇性生成**：支援指定特定檔名進行建置，優化自動化流程效能。
    *   預先渲染所有文章頁面至 `post/` 目錄 (如 `post/2025xxxx.html`)。
    *   在根目錄生成列表頁 (`blog_list.html`)。
    *   **注意**: 這些生成的 `.html` 檔案與 `post/` 目錄已被 `.gitignore` 排除。

### 1.3 設定與環境 (Configuration)
*   **敏感資料分離**: `config.php` 與 `config.js` 已被 Git 忽略。
*   **主題系統 (Theming)**: 支援多重 CSS 主題，包括標準版、深色模式 (`blog-dark`)、粉柔風格 (`blog-pink`) 與駭客任務風格 (`blog-matrix`)，前端根據設定動態載入。
    *   **前端主題插件**: 支援動態載入 `{theme}.js`，用於實作特定主題的專屬邏輯（如：自訂動畫、互動特效或媒體播放器）。

*   **相簿服務 (Album Service)**:
    *   位於 `/album` 目錄下的獨立服務，具備專屬的 SSG 與 SPA 架構。
    *   提供多樣化視覺主題，包含 `album-matrix` (具備數位雨特效) 與 `album-y2k` (復古 2000 年代風格)。
    *   **實驗性 Blazor 總管**: 使用 Blazor WebAssembly 實作的高擬真 Windows 11 / WPF 風格檔案總管，提供沉浸式的相簿瀏覽體驗。
    *   特定主題整合 YouTube IFrame API 提供背景音樂功能。
    *   **資源管理**: 於 `album.js` 實作全域 `DownloadManager`，具備併發控制（同時最多 3 個任務），優化頻寬分配與瀏覽器載入效能。
    *   **主題瀏覽**: 提供 `ThemeSelect.html` 與專用 `api/api_themes.php`，支援動態偵測並預覽相簿風格。

*   **初始化與健康檢查**:
    *   **安裝精靈**: `install.php` 協助使用者進行系統初始化與環境檢測。相簿服務亦具備獨立的 `album/install.php` 提供專屬引導。
    *   **登入檢查**: `admin/login.php` 整合 `admin/health_check.php` 自動驗證系統完整性。
*   **備份與還原系統**: 支援 File/MySQL/SQLite 多模式備份集製作與還原。
*   **國際化支援 (i18n)**:
    *   **前台多語系**: 透過 `langs/` 提供多語系樣板渲染支援 (如 `template-zh_TW.php`)。
    *   **後台多語系**: 管理介面支援動態切換語言。
*   **純靜態 JSON API 模式**:
    *   支援 `config.js` 中的 `api_type: 'json'` 設定。
    *   `make_html.php -json` 可將所有索引與統計導出至單一 `api/json/data.json`。
    *   `static/blog.js` 實作客戶端路由與過濾，達成 100% 無後端運作。
*   **網站設定管理 (GUI)**:
    *   `admin/settings.php` 提供圖形介面修改網站配置 (包含語系與時區)。
*   **導覽與佈局**:
    *   **統一側邊欄**: 透過 `admin/sidebar_inc.php` 達成所有頁面導覽的一致性。
    *   **固定佈局**: 實作固定選單 (Fixed Sidebar)，確保操作項始終可見。
*   **安全性特性 (Security)**:
    *   **文章腳本保護**: 實作 `protect_script_tags` 機制，自動將文章內容中的 `<script>` 標籤轉義為 `&lt;script&gt;`，防止腳本被意外執行，同時確保在技術文章中能正確顯示文字內容。

---

## 2. 目錄結構說明 (Directory Structure)

以下列出 Git 儲存庫中的關鍵目錄與檔案規則：

*   **根目錄**:
    *   `blog.html`: 核心 SPA 入口。
    *   `make_html.php`: 優化後的靜態網站生成器 (CLI 包裝器)。
    *   `mini.py`: Python 自動化壓縮腳本 (具備智慧排除邏輯)。
    *   `install.php`: 系統安裝精靈。

*   **`/api`**:
    *   存放後端 API 程式 (`api_filebase.php`, `api_dbsqlbase.php`, `api_sqlitebase.php`)，共享統一架構。
    *   `json/`: (已忽略) 存放預生成的 `data.json` 靜態資料包。

*   **`/langs`**:
    *   存放後台、前台樣板與安裝程式的語系包。

*   **`/PHP_LIB`**:
    *   存放共用函式庫、`StaticGenerator.php` 核心與 `TemplateManager.php` 微框架。

*   **`/post`**:
    *   (已忽略) 靜態生成文章的輸出目錄。

*   **`/MD`**:
    *   專案文件 (`ARCHITECTURE.md`, `HISTORY.md`, `ROADMAP.md`, `gemini_log.md`)。

*   **`/contents`**:
    *   部落格內容核心。`index_post.txt` 與 `post_files/` 預設被忽略。

*   **`/static`**:
    *   存放 `blog.js` (核心邏輯) 與 `blog_template.html` (SPA 樣板原始碼)。

*   **`/admin`**:
    *   後台管理系統核心程式。
    *   `build.php`: 專用的手動網站建置介面。

---

## 3. 關鍵技術特性

*   **攝影功能**: 前端整合 `exif.js` 自動解析並展示照片元數據。
*   **樣板解耦**：透過 `TemplateManager` 類別將業務邏輯與 HTML 渲染分離，提升代碼維護性。
*   **效能優化**: 
    *   `mini.py` 自動壓縮 JS/CSS 並保護第三方套件。
    *   圖片載入策略：LCP (首張圖) Eager Loading，其餘 Lazy Loading。
    *   靜態生成支援自動修正子目錄資源路徑 (`../`)。
    *   **伺服器端分頁**：後台文章列表支援分頁讀取，顯著提升大數據量下的效能。
*   **版本控制**: 透過 `.gitignore` 嚴格區分「程式碼」與「內容/生成物」。

---
**文件維護**: 本文件反映 2026 年 2 月之專案架構。
