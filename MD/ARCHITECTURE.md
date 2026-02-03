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
    *   `static/blog.js` calls `api/api_filebase.php` via AJAX (path configurable in `config.js`).
    *   The PHP backend reads the text file database and returns JSON.
    *   The frontend uses the `<template>` tag for client-side rendering.
2.  **Static Generation Mode (`make_html.php`)**:
    *   Executes a PHP script to read the database.
    *   Uses `blog.html` (specifically `static/blog_template.html`) as the base template.
    *   Pre-renders all article pages into the `post/` directory (e.g., `post/2025xxxx.html`).
    *   Generates list pages (`blog_list.html`) in the root directory.
    *   **Note**: These generated `.html` files (and the `post/` directory) are excluded by `.gitignore`.

### 1.3 Configuration and Environment
*   **Sensitive Data Separation**: 
    *   `config.php` and `config.js` contain database passwords and API settings and are ignored by Git.
    *   Developers should copy `config.example.php` and `config.example.js` to create local settings.
*   **Theming System**:
    *   Configurable via `config.js` (`theme_file` option).
    *   Supports multiple CSS themes (e.g., `blog.css`, `blog-dark.css`).
    *   Frontend dynamically loads the appropriate stylesheet based on configuration.
*   **Initialization and Health Check**:
    *   **Installation Wizard**: `install.php` provides a user-friendly interface to initialize the system, check environment compatibility, choose themes, and generate config files.
    *   **Login Check**: `admin/login.php` integrates `admin/health_check.php` to automatically verify database connection and file system integrity.
    *   **Database Initialization**: `admin/db_init.php` supports import from files or creating sample data.
    -   **File System Initialization**: `admin/file_init.php` supports reverse export from the database to rebuild the file structure.
*   **System Environment Detection**:
    - **Detailed OS Info**: `admin/system_helper.php` provides granular detection of Linux and Windows environments.
    - **WSL2 Optimization**: Automatically identifies WSL2/NTFS environments to bypass incompatible permission checks.
*   **Backup & Recovery System**:
    - **Multi-mode Backup**: `admin/tool_backup.php` creates ZIP archives based on the current active mode.
    - **Optimization**: The `pic/` directory (original photos) is excluded from backups to reduce file size.
    - **Intelligent Restore**: Automatically identifies backup types and restores data and static resources.
*   **Draft & Status System**:
    - **File Mode**: Uses `.html.tmp` extension for hidden drafts.
    - **DB Mode**: Added `status` column to track 'draft' vs 'published'.
    - **Filtering**: Automatically hides drafts from public APIs and static generation.
*   **Admin Settings GUI**:
    - **Settings Page**: `admin/settings.php` allows graphical editing of `config.js` settings (API Type, Theme, Google CSE ID).

---

## 2. Directory Structure Description

Listed below are key directories and file rules in the Git repository:

*   **Root Directory**:
    *   `blog.html`: Core SPA entry point.
    *   `index.html`: Site homepage redirection.
    *   `make_html.php`: Static site generator.
    *   `mini.py`: Python automation script for minification (optimized to skip large directories).
    *   `install.php`: System installation wizard.
    *   `blog.css` / `blog-dark.css`: Main stylesheets (source).

*   **`/api`**:
    *   Contains backend API endpoints (`api_filebase.php`, `api_dbsqlbase.php`, `api_sqlitebase.php`).

*   **`/post`**:
    *   (Ignored) Destination for statically generated article HTML files.

*   **`/MD`**:
    *   Project documentation (`ARCHITECTURE.md`, `HISTORY.md`, `ROADMAP.md`, `gemini_log.md`).

*   **`/contents`**:
    *   Core of blog content. `index_post.txt` and `post_files/` are ignored by default.

*   **`/category`**:
    *   Category index directory. Actual category folders are ignored.

*   **`/static`**:
    *   Contains `blog.js` (core logic), `blog_template.html` (SPA template source), `exif.js`, and icons.
    *   Image resources and minified resources (`*.min.*`) are ignored.

*   **`/preview`**:
    *   Contains article preview images and Open Graph images.

*   **`/pic`**:
    *   (Ignored) Stores a large number of original photos used within articles.

*   **`/admin`**:
    *   Backend management system (`auth.php`, `index.php`, `posts.php`, etc.).

---

## 3. Key Technical Features

*   **Photography Features**: Frontend integrates `exif.js` to automatically parse and display photo metadata.
*   **Advanced Content Editing**: Integrated **TinyMCE 6** (Local Deployment) with custom `<!--more-->` PageBreak support and dynamic localization.
*   **Performance Optimization**: 
    *   `mini.py` automatically compresses JS/CSS.
    *   Image loading strategy: LCP (first image) Eager Loading, others Lazy Loading.
    *   Static generation optimizes resource paths (`../`) for subdirectories.
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
    *   `static/blog.js` 透過 AJAX 呼叫 `api/api_filebase.php` (路徑於 `config.js` 設定)。
    *   PHP 後端讀取文字檔資料庫，回傳 JSON。
    *   前端利用 `<template>` 標籤進行客戶端渲染。
2.  **靜態生成模式 (`make_html.php`)**:
    *   執行 PHP 腳本讀取資料庫。
    *   利用 `static/blog_template.html` 作為基底樣板。
    *   預先渲染所有文章頁面至 `post/` 目錄 (如 `post/2025xxxx.html`)。
    *   在根目錄生成列表頁 (`blog_list.html`)。
    *   **注意**: 這些生成的 `.html` 檔案與 `post/` 目錄已被 `.gitignore` 排除，不納入版本控制。

### 1.3 設定與環境 (Configuration)
*   **敏感資料分離**: 
    *   `config.php` 與 `config.js` 包含資料庫密碼與 API 設定，已被 Git 忽略。
    *   開發者應複製 `config.example.php` 與 `config.example.js` 來建立本地設定。
*   **主題系統 (Theming)**:
    *   透過 `config.js` 中的 `theme_file` 選項進行設定。
    *   支援多重 CSS 主題 (如 `blog.css`, `blog-dark.css`)。
    *   前端根據設定動態載入對應的樣式表。
*   **初始化與健康檢查 (Initialization)**:
    *   **安裝精靈**: `install.php` 提供友善的介面協助使用者進行系統初始化、環境檢測、主題選擇並自動生成設定檔。
    *   **登入檢查**: `admin/login.php` 整合 `admin/health_check.php` 自動驗證系統完整性。
    *   **資料庫/檔案系統初始化**: 透過 `admin/db_init.php` 與 `admin/file_init.php` 支援雙向資料遷移與結構重建。
*   **系統環境偵測 (Environment Detection)**:
    - **詳細 OS 資訊**: 支援 Linux 發行版與 Windows 版本偵測。
    - **WSL2 優化**: 自動識別 WSL2 環境並跳過無效的權限修正步驟。
*   **備份與還原系統**:
    - **多模式備份**: 支援 File/MySQL/SQLite 模式備份集。
    - **優化**: `pic/` 目錄 (原始照片) 被排除在備份之外以縮減檔案大小。
    - **智慧還原**: 自動識別並還原資料與靜態資源。
*   **草稿與狀態系統**:
    - **檔案模式**: 利用 `.html.tmp` 副檔名達成草稿隱藏。
    - **資料庫模式**: 新增 `status` 欄位追蹤「草稿」與「發布」狀態。
    - **自動過濾**: 前台 API 與靜態生成器會自動排除未發布的文章。
*   **網站設定管理 (GUI)**:
    - **設定頁面**: `admin/settings.php` 提供圖形介面修改 `config.js` (API 模式、佈景主題、Google CSE ID)。

---

## 2. 目錄結構說明 (Directory Structure)

以下列出 Git 儲存庫中的關鍵目錄與檔案規則：

*   **根目錄**:
    *   `blog.html`: 核心 SPA 入口。
    *   `index.html`: 首頁導向。
    *   `make_html.php`: 靜態網站生成器。
    *   `mini.py`: Python 自動化壓縮腳本 (已優化掃描效能)。
    *   `install.php`: 系統安裝精靈。
    *   `blog.css` / `blog-dark.css`: 主要樣式表原始檔。

*   **`/api`**:
    *   存放後端 API 程式 (`api_filebase.php`, `api_dbsqlbase.php`, `api_sqlitebase.php`)。

*   **`/post`**:
    *   (已忽略) 靜態生成文章的輸出目錄。

*   **`/MD`**:
    *   專案文件 (`ARCHITECTURE.md`, `HISTORY.md`, `ROADMAP.md`, `gemini_log.md`)。

*   **`/contents`**:
    *   部落格內容核心。`index_post.txt` 與 `post_files/` 預設被忽略。

*   **`/category`**:
    *   分類索引目錄。實際分類資料夾被忽略。

*   **`/static`**:
    *   存放 `blog.js` (核心邏輯)、`blog_template.html` (SPA 樣板原始碼)、`exif.js` 與圖示。
    *   圖片資源與壓縮後的資源 (`*.min.*`) 已被忽略。

*   **`/preview`**:
    *   存放文章預覽圖與 Open Graph 圖片。

*   **`/pic`**:
    *   (已忽略) 存放文章內使用的大量原始照片。

*   **`/admin`**:
    *   後台管理系統核心程式 (`auth.php`, `index.php`, `posts.php` 等)。

---

## 3. 關鍵技術特性

*   **攝影功能**: 前端整合 `exif.js` 自動解析並展示照片元數據。
*   **進階內容編輯**: 整合 **TinyMCE 6** (本地化部署)，支援視覺化插入 `<!--more-->` 繼續閱讀標記與語系自動切換。
*   **效能優化**: 
    *   `mini.py` 自動壓縮 JS/CSS。
    *   圖片載入策略：LCP (首張圖) Eager Loading，其餘 Lazy Loading。
    *   靜態生成支援自動修正子目錄資源路徑 (`../`)。
*   **版本控制**: 透過 `.gitignore` 嚴格區分「程式碼」與「內容/生成物」。

---
**文件維護**: 本文件反映 2026 年 2 月之專案架構。
