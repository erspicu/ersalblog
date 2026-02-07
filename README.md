# BaxerMux Photography Blog

A lightweight, hybrid photography blog system designed for speed, flexibility, and privacy.

---

### 🌟 Core Features

*   **Hybrid Engine**: High-performance Static Site Generation (SSG) combined with a flexible Single Page Application (SPA).
*   **Zero-Database Option**: Runs entirely on plain text files, while also supporting MySQL and SQLite 3 for scalability.
*   **Unified API Architecture**: A single, robust logic core powers API endpoints across all storage modes, ensuring consistency and ease of maintenance.
*   **Admin Power-ups**:
    *   **Selective SSG Build**: Trigger static page generation directly from the post editor or a dedicated build management interface.
    *   **Visual Editor**: Locally-hosted TinyMCE 6 for a seamless "What You See Is What You Get" writing experience.
    *   **Advanced Dashboard**: Real-time system health checks, database statistics, and static file status monitoring.
    *   **Smart Navigation**: Fixed sidebar layout with unified navigation for efficient management.
    *   **Post Pagination**: Server-side pagination (15 posts/page) for fast browsing of large archives.
*   **Security First**:
    *   **Script Protection**: Built-in HTML escaping for `<script>` tags to prevent execution in posts while keeping content readable.
    *   **Rate Limiting & CSRF**: Secure login with IP-based lockout and full CSRF protection for all admin actions.
*   **Deployment Ready**: Optimized Python minification script for JS/CSS assets and incremental build support.
*   **Multi-language**: Fully localized admin and frontend (T. Chinese/English).

---

### 🚀 Key Features Highlights

#### 1. SSG Pipeline Refactoring
The core build logic is now encapsulated in `PHPLib\StaticGenerator`, enabling consistent rendering across CLI and Web interfaces. It supports incremental builds based on file modification times.

#### 2. Advanced Post Management
Manage your content with ease through a paginated list and a visual editor. The system automatically detects missing static files and alerts you on the dashboard.

#### 3. Secure & Private
No external CDNs required for core functionality. TinyMCE, SweetAlert2, and Bootstrap are all bundled for maximum privacy and performance.

---

### 📂 Directory Structure Overview

*   `admin/`: Backend management system.
*   `api/`: Dynamic API endpoints (File/MySQL/SQLite).
*   `langs/`: Centralized i18n files for Admin and Templates.
*   `PHP_LIB/`: Core libraries including `StaticGenerator` and `TemplateManager`.
*   `contents/`: Your raw blog content (HTML fragments and index).
*   `post/`: Statically generated pages ready for production.
*   `static/`: Core frontend assets (JavaScript and Templates).

---

### 📚 Acknowledgments & Third-party Libraries

We respect open-source contributors. This project utilizes:
- **PHP Libraries**:
  - `Gajus\Dindent`: HTML indentation for beautiful static output.
  - `Html2Text`: Content conversion utility.
- **Frontend Assets**:
  - `TinyMCE 6`: Visual content editor.
  - `SweetAlert2`: Modern popup notifications.
  - `Bootstrap 5`: Responsive layout framework.
  - `Exif.js`: Photography metadata extraction.

---

### 🎨 Theming
Configurable in `config.js` via `theme_file`. Supports `blog.css` (Standard) and `blog-dark.css` (Dark Mode).

---

### 🛠 Automated Version Control
Version: `v2026.02.07.15.00` (UTC+8)
CLI Version: `0.27.3`
Model: `gemini-3-pro-preview`

---

## BaxerMux Photography Blog (繁體中文說明)

本專案是一個兼具 **靜態網頁生成 (SSG)** 與 **動態單頁應用 (SPA)** 優點的攝影部落格系統。

### 🌟 核心特色

*   **混合引擎**: 高效能靜態網頁生成與靈活的前端渲染 (SPA) 雙模式。
*   **無資料庫支援**: 可完全運行於純文字檔案，亦支援 MySQL 與 SQLite 3 提供擴充性。
*   **統一 API 架構**: 所有儲存模式的資料接口皆由單一核心邏輯驅動，大幅提升系統一致性與維護便利性。
*   **強大後台**:
    *   **選擇性建置**: 可直接從文章編輯器或專用的建置管理介面觸發靜態網頁生成。
    *   **視覺化編輯器**: 內建 TinyMCE 6，提供所見即所得的流暢寫作體驗。
    *   **進階儀表板**: 即時系統健康檢查、資料庫統計及靜態檔案狀態監測。
    *   **智慧導覽**: 固定式側邊欄佈局與統一的導覽組件，提升管理效率。
    *   **文章分頁**: 後台支援伺服器端分頁（每頁 15 篇），輕鬆應對大量文章。
*   **安全性強化**:
    *   **腳本防護**: 內建 `<script>` 標籤轉義機制，防止文章內的腳本執行，同時保留技術內容的可讀性。
    *   **安全防禦**: IP 登入鎖定機制與全站 CSRF 防護。
*   **部署優化**: 內建 Python 壓縮腳本與增量建置支援，顯著縮短部屬時間。
*   **多語系**: 完整的後台與前台語系支援（繁中/英文）。

---

### 🚀 重點功能摘要

#### 1. SSG 建置管線重構
核心建置邏輯封裝於 `PHPLib\StaticGenerator`，確保 CLI 與 Web 介面渲染結果一致，並支援基於檔案修改時間的增量建置。

#### 2. 進階文章管理
透過分頁列表與視覺化編輯器輕鬆管理內容。系統會自動偵測缺失的靜態檔案，並在儀表板即時提醒。

#### 3. 安全與隱私
核心功能不依賴外部 CDN。TinyMCE、SweetAlert2 與 Bootstrap 全數內建，確保極致的隱私與連線效能。

---

### 📂 目錄結構簡述

*   `admin/`: 後台管理系統。
*   `api/`: 各種模式的資料接口。
*   `langs/`: 集中管理的語系檔案。
*   `PHP_LIB/`: 核心邏輯類別庫 (StaticGenerator, TemplateManager)。
*   `contents/`: 文章原始內容與索引。
*   `post/`: 產出的靜態網頁。
*   `static/`: 前端核心邏輯與樣板原始碼。

---

### 🛠 自動化版本資訊
版本號: `v2026.02.07.15.00` (UTC+8)
CLI 版本: `0.27.3`
模型名稱: `gemini-3-pro-preview`