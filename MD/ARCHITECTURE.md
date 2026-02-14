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
    *   **Core Logic Engine**: Powered by `AlbumGenerator` class, encapsulating scanning, Exif parsing, thumbnail generation, and JSON maintenance for both CLI (`make_album.php`) and Web Admin usage.
    *   **Environment Diagnostics**: Features a dedicated "System Diagnostics" system that detects Imagick/GD/Exif support and provides a smart fallback to GD if Imagick is unavailable.
    *   **Admin Integration**: Supports fine-grained maintenance tasks (Full rebuild, incremental updates, template refresh) directly from the admin panel with visual progress tracking.
    *   **Experimental Blazor Explorer**: A high-fidelity Windows 11 / WPF style explorer built with Blazor WebAssembly, optimized with **AOT (Ahead-of-Time)** compilation.
    *   **Build Tooling**: Includes `album/toolshell/` with cross-platform scripts (Bash, PowerShell, Batch) for theme rebuilding and project cleanup.

*   **Initialization and Health Check**:
    *   **System Diagnostics**: `admin/health_check.php` and `album/admin/health_check.php` provide comprehensive environment verification and permission auditing.
*   **Security Features**:
    *   **CSRF Protection**: Implements a unified Token validation mechanism across all admin Ajax actions.
    *   **Script Protection**: Automatically neutralizes `<script>` tags in article content to prevent execution.

---

## 2. Directory Structure Description

*   **Root Directory**: SPA entry point and core CLI build scripts.
*   **`/api`**: Backend API endpoints with unified architecture.
*   **`/langs`**: Global language packs for Admin and Templates.
*   **`/PHP_LIB`**: Shared PHP logic and the `TemplateManager` micro-framework.
*   **`/album`**: Independent album service with its own `/admin`, `/api`, and `/Collection`.
*   **`/album/toolshell`**: Cross-platform automation scripts for album maintenance.
*   **`/MD`**: Project technical documentation and history logs.

---

## 3. Key Technical Features

*   **Photography Features**: Frontend photo metadata parsing via `exif.js` and backend server-side Exif extraction.
*   **Performance Optimization**: 
    *   Automatic compression of JS/CSS via Python scripts.
    *   Smart thumbnail generation logic (only creates if original is larger than target).
    *   Extreme AOT compilation for embedded Blazor applications.
*   **Version Control**: Strictly distinguishes between "Code" and "Content/Artifacts" via `.gitignore`.

---
**Document Maintenance**: Updated February 14, 2026.

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
    *   **核心處理引擎**: 由 `AlbumGenerator` 類別驅動，封裝掃描、Exif 解析、縮圖生成與 JSON 維護邏輯，達成 CLI 與 Web 後台邏輯統一。
    *   **環境診斷機制**: 內建系統診斷功能，自動偵測 Imagick/GD/Exif 支援度，並實作無縫的 GD 縮圖處理回退機制。
    *   **後台維護整合**: 支援於後台視覺化執行全站重建、增量更新與樣板重新渲染。
    *   **實驗性 Blazor 總管**: 高擬真 Win11 風格檔案總管，已啟用 **AOT** 編譯優化效能。
    *   **自動化工具**: 提供 `album/toolshell/` 目錄，內含支援 Bash, PowerShell 與 Batch 的跨平台管理腳本。

*   **初始化與健康檢查**:
    *   **系統診斷**: `admin/health_check.php` 與 `album/admin/health_check.php` 提供通用的伺服器配置檢查與權限審核。
*   **安全性 (Security)**:
    *   **CSRF 防禦**: 全面整合 AJAX Token 驗證機制。
    *   **文章腳本保護**: 自動轉義內容中的 `<script>` 標籤，防止意外執行。

---

## 2. 目錄結構說明 (Directory Structure)

*   **根目錄**: SPA 入口與核心 CLI 建置程式。
*   **`/api`**: 存放共享架構的後端 API。
*   **`/langs`**: 全域語系包。
*   **`/PHP_LIB`**: 共用邏輯類別與 `TemplateManager` 微框架。
*   **`/album`**: 獨立相簿服務，含其專屬 `/admin`, `/api` 與 `/Collection`。
*   **`/album/toolshell`**: 跨平台自動化維護腳本工具包。
*   **`/MD`**: 專案技術文件與週歷史紀錄檔。

---

## 3. 關鍵技術特性

*   **攝影功能**: 前後端協同的 Exif 元數據解析與展示。
*   **效能優化**: 
    *   JS/CSS 自動壓縮。
    *   智慧縮圖生成邏輯（僅在原圖大於規格時建立）。
    *   嵌入式 Blazor 應用程式極致 AOT 編譯。
*   **版本控制**: 嚴格區分代碼與生成產物。

---
**文件維護**: 2026 年 2 月 14 日更新。
