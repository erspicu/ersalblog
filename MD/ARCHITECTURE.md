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
    *   Uses `static/blog_template.html` as the base template.
    *   **Micro-Template Engine**: Utilizes `PHP_LIB/TemplateManager.php` for high-performance placeholder replacement and list rendering.
    *   **Regex Pipeline**: Employs Regex-based parsing instead of DOMDocument to ensure HTML5 compatibility and stability across different PHP versions (5.x+).
    *   **Incremental Builds**: Implements an `mtime`-based cache mechanism that skips rendering for unmodified articles or templates.
    *   Pre-renders all article pages into the `post/` directory (e.g., `post/2025xxxx.html`).
    *   Generates list pages (`blog_list.html`) in the root directory.
    *   **Note**: These generated `.html` files (and the `post/` directory) are excluded by `.gitignore`.

### 1.3 Configuration and Environment
*   **Sensitive Data Separation**: 
    *   `config.php` and `config.js` contain database passwords and API settings and are ignored by Git.
*   **Theming System**:
    *   Configurable via `config.js` (`theme_file` option).
    *   Supports multiple CSS themes (e.g., `blog.css`, `blog-dark.css`).
*   **Initialization and Health Check**:
    *   **Installation Wizard**: `install.php` provides a user-friendly interface to initialize the system and check environment compatibility.
    *   **Login Check**: `admin/login.php` integrates `admin/health_check.php` to automatically verify database connection and file system integrity.
*   **Backup & Recovery System**:
    *   **Multi-mode Backup**: `admin/tool_backup.php` creates ZIP archives for File, MySQL, or SQLite modes.
*   **Internationalization (i18n)**:
    *   **Template i18n**: Supports multi-language frontend via `langs/template/` (e.g., `zh_TW.php`, `en_US.php`).
    *   **Admin i18n**: Admin panel supports dynamic language switching.
*   **Pure Static JSON API Mode**:
    *   Supports `api_type: 'json'` in `config.js`.
    *   `make_html.php -json` exports all data into a single consolidated `api/json/data.json`.
    *   `static/blog.js` implements client-side routing and filtering for a 100% backend-less experience.
*   **Admin Settings GUI**:
    *   **Settings Page**: `admin/settings.php` allows graphical editing of `config.js` and `config.php` (Lang/Timezone) settings.

---

## 2. Directory Structure Description

Listed below are key directories and file rules in the Git repository:

*   **Root Directory**:
    *   `blog.html`: Core SPA entry point.
    *   `make_html.php`: Optimized static site generator.
    *   `mini.py`: Python automation script for minification (with smart ignore logic).
    *   `install.php`: System installation wizard.

*   **`/api`**:
    *   Contains backend API endpoints (`api_filebase.php`, `api_dbsqlbase.php`, `api_sqlitebase.php`).
    *   `json/`: (Ignored) Contains pre-generated `data.json` for static mode.

*   **`/langs`**:
    *   Contains language packs for Admin (`admin/`) and Frontend templates (`template/`).

*   **`/PHP_LIB`**:
    *   Contains shared libraries and the `TemplateManager.php` micro-framework.

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

---

## 3. Key Technical Features

*   **Photography Features**: Frontend integrates `exif.js` to automatically parse and display photo metadata.
*   **Template Decoupling**: Business logic is separated from HTML rendering via the `TemplateManager` class.
*   **Performance Optimization**: 
    *   `mini.py` automatically compresses JS/CSS while protecting vendor assets.
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
    *   `static/blog.js` 透過 AJAX 呼叫 `api/api_filebase.php`。
    *   前端利用 `<template>` 標籤進行客戶端渲染。
2.  **靜態生成模式 (`make_html.php`)**:
    *   執行 PHP 腳本讀取資料庫。
    *   利用 `static/blog_template.html` 作為基底樣板。
    *   **微樣板引擎**：使用 `PHP_LIB/TemplateManager.php` 進行高效能變數替換與列表渲染。
    *   **Regex 建置管線**：全面改用正規表達式取代 DOMDocument，解決 HTML5 相容性問題並支援 PHP 5.x+ 環境。
    *   **增量建置快取**：實作基於 `mtime` 的快取機制，自動比對來源檔與目標檔時間，僅重新渲染有變動的內容。
    *   預先渲染所有文章頁面至 `post/` 目錄 (如 `post/2025xxxx.html`)。
    *   在根目錄生成列表頁 (`blog_list.html`)。
    *   **注意**: 這些生成的 `.html` 檔案與 `post/` 目錄已被 `.gitignore` 排除。

### 1.3 設定與環境 (Configuration)
*   **敏感資料分離**: `config.php` 與 `config.js` 已被 Git 忽略。
*   **主題系統 (Theming)**: 支援多重 CSS 主題 (如 `blog.css`, `blog-dark.css`)，前端根據設定動態載入。
*   **初始化與健康檢查**:
    *   **安裝精靈**: `install.php` 協助使用者進行系統初始化與環境檢測。
    *   **登入檢查**: `admin/login.php` 整合 `admin/health_check.php` 自動驗證系統完整性。
*   **備份與還原系統**: 支援 File/MySQL/SQLite 多模式備份集製作與還原。
*   **國際化支援 (i18n)**:
    *   **前台多語系**: 透過 `langs/template/` 提供多語系樣板渲染支援。
    *   **後台多語系**: 管理介面支援動態切換語言。
*   **純靜態 JSON API 模式**:
    *   支援 `config.js` 中的 `api_type: 'json'` 設定。
    *   `make_html.php -json` 可將所有索引與統計導出至單一 `api/json/data.json`。
    *   `static/blog.js` 實作客戶端路由與過濾，達成 100% 無後端運作。
*   **網站設定管理 (GUI)**:
    *   `admin/settings.php` 提供圖形介面修改網站配置 (包含語系與時區)。

---

## 2. 目錄結構說明 (Directory Structure)

以下列出 Git 儲存庫中的關鍵目錄與檔案規則：

*   **根目錄**:
    *   `blog.html`: 核心 SPA 入口。
    *   `make_html.php`: 優化後的靜態網站生成器。
    *   `mini.py`: Python 自動化壓縮腳本 (具備智慧排除邏輯)。
    *   `install.php`: 系統安裝精靈。

*   **`/api`**:
    *   存放後端 API 程式 (`api_filebase.php`, `api_dbsqlbase.php`, `api_sqlitebase.php`)。
    *   `json/`: (已忽略) 存放預生成的 `data.json` 靜態資料包。

*   **`/langs`**:
    *   存放後台 (`admin/`) 與前台樣板 (`template/`) 的語系包。

*   **`/PHP_LIB`**:
    *   存放共用函式庫與 `TemplateManager.php` 微框架。

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

---

## 3. 關鍵技術特性

*   **攝影功能**: 前端整合 `exif.js` 自動解析並展示照片元數據。
*   **樣板解耦**：透過 `TemplateManager` 類別將業務邏輯與 HTML 渲染分離，提升代碼維護性。
*   **效能優化**: 
    *   `mini.py` 自動壓縮 JS/CSS 並保護第三方套件。
    *   圖片載入策略：LCP (首張圖) Eager Loading，其餘 Lazy Loading。
    *   靜態生成支援自動修正子目錄資源路徑 (`../`)。
*   **版本控制**: 透過 `.gitignore` 嚴格區分「程式碼」與「內容/生成物」。

---
**文件維護**: 本文件反映 2026 年 2 月之專案架構。