# BaxerMux Photography Blog Technical Architecture Analysis

This project is a photography blog system featuring a hybrid mode of **Static Site Generation (SSG)** and **Single Page Application (SPA)**. Its design core lies in the separation of data and logic, supporting content management through plain text files.

---

## 1. Core Architecture

### 1.1 Data Storage Layer
The project does not rely on a traditional database but utilizes the file system and plain text files:
*   **Post Index**: Located at `contents/index_post.txt` (Git only tracks `readme.md`), using Pipe (`|`) separators to record publication time, filename, title, tags, and description.
*   **Category System**: Located at `category/` (Git only tracks `readme.md`), utilizing directory structures to represent categories, containing empty files with names matching post filenames as indices.
*   **Original Content**: Located at `contents/post_files/`, storing the original HTML fragments of the articles.

### 1.2 Hybrid Rendering Mode
The system supports two operating modes:
1.  **Dynamic SPA Mode (`blog.html`)**: 
    *   User accesses `blog.html`.
    *   `static/blog.js` calls `api_filebase.php` via AJAX.
    *   The PHP backend reads the text file database and returns JSON.
    *   The frontend uses the `<template>` tag for client-side rendering.
2.  **Static Generation Mode (`make_html.php`)**:
    *   Executes a PHP script to read the database.
    *   Uses `blog.html` as the base template.
    *   Pre-renders all article pages (e.g., `2025xxxx.html`) and list pages (`blog_list.html`).
    *   **Note**: These generated `.html` files are excluded by `.gitignore` and not included in version control.

### 1.3 Configuration and Environment
*   **Sensitive Data Separation**: 
    *   `config.php` and `config.js` contain database passwords and API settings and are ignored by Git.
    *   Developers should copy `config.example.php` and `config.example.js` to create local settings.
*   **Initialization and Health Check**:
    *   **Login Check**: `admin/login.php` integrates `admin/health_check.php` to automatically verify database connection and file system integrity before login.
    *   **Database Initialization Wizard**: If the database is connected but missing tables, the system guides to `admin/db_init.php`, supporting import from files or creating sample data.
    *   **File System Initialization Wizard**: If the file structure is incomplete (e.g., missing directories), the system guides to `admin/file_init.php`, supporting reverse export from the database to rebuild the file structure.

---

## 2. Directory Structure Description

Listed below are key directories and file rules in the Git repository:

*   **Root Directory**:
    *   `blog.html`: Core SPA template and entry point.
    *   `index.html`: Site homepage redirection.
    *   `api_filebase.php`: Backend API providing JSON data.
    *   `make_html.php`: Static site generator.
    *   `migrate_full.php`: Database migration tool (supports MySQL import).
    *   `mini.py`: Python automation script for minification.

*   **`/contents`**:
    *   Core of blog content. `index_post.txt` and `post_files/` are ignored by default; only `readme.md` is kept for description.

*   **`/category`**:
    *   Category index directory. Actual category folders are ignored; only `readme.md` is kept for description.

*   **`/static`**:
    *   Contains `blog.js` (core logic), `blog.css` (styles), `exif.js` (EXIF parsing library), and icons.
    *   Image resources (`*.png`, `*.jpg`, `*.ico`) and minified resources (`*.min.*`) are ignored.

*   **`/preview`**:
    *   Contains article preview images and Open Graph images.
    *   Actual image files (`*.jpg`) are ignored; only `readme.md` is kept.

*   **`/pic`**:
    *   (Ignored) Stores a large number of original photos used within articles.

*   **`/PHP_LIB`**:
    *   Contains third-party PHP libraries like `html2text` and `dindent` used for formatting generated HTML.

---

## 3. Key Technical Features

*   **Photography Features**: Frontend integrates `exif.js` to automatically parse photo metadata (aperture, shutter, ISO, GPS) and dynamically display them in articles.
*   **Performance Optimization**: 
    *   `mini.py` automatically compresses JS/CSS.
    *   Image loading strategy: LCP (first image) Eager Loading, others Lazy Loading.
*   **Version Control**: Strictly distinguishes between "Code" and "Content/Artifacts" via `.gitignore`, ensuring a lightweight repository without sensitive data.

---
**Document Maintenance**: This document reflects the project architecture as of January 2026.

---

# BaxerMux 攝影 Blog 技術架構分析

本專案是一個結合了 **Static Site Generation (SSG)** 與 **Single Page Application (SPA)** 混合模式的攝影部落格系統。其設計核心在於資料與邏輯的分離，並支援透過純文字檔案管理內容。

---

## 1. 核心架構 (Core Architecture)

### 1.1 資料儲存層 (Data Layer)
專案不依賴傳統資料庫，而是使用檔案系統與純文字檔案：
*   **文章索引**: 位於 `contents/index_post.txt` (Git 僅追蹤 `readme.md`)，採用 Pipe (`|`) 分隔，記錄發布時間、檔名、標題、標籤及摘要。
*   **分類系統**: 位於 `category/` (Git 僅追蹤 `readme.md`)，利用資料夾結構代表分類，內含對應文章檔名的空檔案作為索引。
*   **原始內容**: 位於 `contents/post_files/`，儲存文章的原始 HTML 片段。

### 1.2 混合渲染模式 (Hybrid Rendering)
本系統支援兩種運作模式：
1.  **動態 SPA 模式 (`blog.html`)**: 
    *   使用者存取 `blog.html`。
    *   `static/blog.js` 透過 AJAX 呼叫 `api_filebase.php`。
    *   PHP 後端讀取文字檔資料庫，回傳 JSON。
    *   前端利用 `<template>` 標籤進行客戶端渲染。
2.  **靜態生成模式 (`make_html.php`)**:
    *   執行 PHP 腳本讀取資料庫。
    *   利用 `blog.html` 作為基底樣板。
    *   預先渲染所有文章頁面 (如 `2025xxxx.html`) 與列表頁 (`blog_list.html`)。
    *   **注意**: 這些生成的 `.html` 檔案已被 `.gitignore` 排除，不納入版本控制。

### 1.3 設定與環境 (Configuration)
*   **敏感資料分離**: 
    *   `config.php` 與 `config.js` 包含資料庫密碼與 API 設定，已被 Git 忽略。
    *   開發者應複製 `config.example.php` 與 `config.example.js` 來建立本地設定。
*   **初始化與健康檢查**:
    *   **登入檢查**: `admin/login.php` 整合 `admin/health_check.php`，在登入前自動驗證資料庫連線與檔案系統完整性。
    *   **資料庫初始化精靈**: 若資料庫已連線但缺少資料表，系統會引導至 `admin/db_init.php`，支援從檔案匯入或建立範例資料。
    *   **檔案系統初始化精靈**: 若檔案結構不完整（如缺少目錄），系統會引導至 `admin/file_init.php`，支援從資料庫反向匯出資料以重建檔案結構。

---

## 2. 目錄結構說明 (Directory Structure)

以下列出 Git 儲存庫中的關鍵目錄與檔案規則：

*   **根目錄**:
    *   `blog.html`: 核心 SPA 樣板與入口。
    *   `index.html`: 網站首頁導向。
    *   `api_filebase.php`: 提供 JSON 資料的後端 API。
    *   `make_html.php`: 靜態網站生成器。
    *   `migrate_full.php`: 資料庫遷移工具 (支援 MySQL 匯入)。
    *   `mini.py`: Python 自動化壓縮腳本 (Minify)。

*   **`/contents`**:
    *   部落格內容核心。`index_post.txt` 與 `post_files/` 預設被忽略，僅保留 `readme.md` 說明。

*   **`/category`**:
    *   分類索引目錄。實際分類資料夾被忽略，僅保留 `readme.md` 說明。

*   **`/static`**:
    *   存放 `blog.js` (核心邏輯)、`blog.css` (樣式)、`exif.js` (EXIF 解析庫) 與圖示。
    *   圖片資源 (`*.png`, `*.jpg`, `*.ico`) 與壓縮後的資源 (`*.min.*`) 已被忽略。

*   **`/preview`**:
    *   存放文章預覽圖與 Open Graph 圖片。
    *   實際圖檔 (`*.jpg`) 已被忽略，僅保留 `readme.md`。

*   **`/pic`**:
    *   (已忽略) 存放文章內使用的大量原始照片。

*   **`/PHP_LIB`**:
    *   包含 `html2text` 與 `dindent` 等第三方 PHP 函式庫，用於格式化生成的 HTML。

---

## 3. 關鍵技術特性

*   **攝影功能**: 前端整合 `exif.js` 自動解析照片元數據 (光圈、快門、ISO、GPS)，並在文章中動態展示。
*   **效能優化**: 
    *   `mini.py` 自動壓縮 JS/CSS。
    *   圖片載入策略：LCP (首張圖) Eager Loading，其餘 Lazy Loading。
*   **版本控制**: 透過 `.gitignore` 嚴格區分「程式碼」(Code) 與「內容/生成物」(Content/Artifacts)，確保儲存庫輕量且無敏感資料。

---
**文件維護**: 本文件反映 2026 年 1 月之專案架構。
