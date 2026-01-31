# BaxerMux Photography Blog

A lightweight, high-performance blog system designed specifically for photographers. It features a unique hybrid architecture combining Static Site Generation (SSG) and Single Page Application (SPA) capabilities without requiring a traditional SQL database for its core operation.

## Key Features
- **Photography Focused**: Automatically extracts and displays EXIF metadata (Camera, Aperture, Shutter, ISO, GPS) from your photos using `exif.js`.
- **Hybrid Data Management**:
  - **Flat-file Mode**: Uses simple text files (`index_post.txt`) and directory structures for content management.
  - **Database Mode**: Supports MySQL/MariaDB with a normalized schema (`blog_posts`, `blog_categories`) for robust data handling.
- **Dual-Mode Admin Panel**: A unified administration interface that allows you to switch between managing local files or the database seamlessly.
- **Hybrid Rendering**: Serve content dynamically as a SPA for rich interaction or pre-render static HTML pages for SEO and speed.
- **Performance Optimized**: Built-in lazy loading for images and automatic JS/CSS minification via Python scripts.

## Tech Stack
- **Backend**: PHP 7.4+ (Compatible with PHP 8.x)
- **Frontend**: Vanilla JavaScript, HTML5, CSS3
- **Tools**: Python 3 (for asset minification)

## Quick Start
1. Clone the repository.
2. Copy `config.example.php` to `config.php` and configure your database settings (if using Database mode).
3. Copy `config.example.js` to `config.js` and set your API type and Google Search Console ID.
4. Add your posts in `contents/index_post.txt` and `contents/post_files/` (for File mode).
5. Run `make_html.php` to generate static pages.
6. Access `/admin` to log in and manage your content.

## Acknowledgments & Third-Party Libraries
This project makes use of the following open-source libraries. We gratefully acknowledge their authors:

### Core & Frontend
- **[Dindent](https://github.com/gajus/dindent)** by Gajus Kuizinas:
  Used in `make_html.php` to beautify and indent the generated HTML output.
- **[html2text](https://github.com/soundasleep/html2text)** by Jevon Wright:
  Used to convert HTML content into plain text for metadata processing.
- **[exif-js](https://github.com/exif-js/exif-js)**:
  Used in the frontend (`static/exif.js`) to extract and display EXIF metadata from images.

### Admin Dashboard
- **[Bootstrap 5](https://getbootstrap.com/)**:
  The administration interface is built using the Bootstrap 5 framework for a responsive and modern UI.
- **[SweetAlert2](https://sweetalert2.github.io/)**:
  Used for beautiful, responsive, and customizable replacement for JavaScript's popup boxes.

---

# BaxerMux 攝影部落格

專為攝影愛好者設計的輕量化、高效能部落格系統。其核心特色在於結合了靜態網站生成 (SSG) 與單頁應用程式 (SPA) 的混合架構，且在基本運作下無需傳統 SQL 資料庫。

## 主要特色
- **攝影導向**: 自動透過 `exif.js` 解析並顯示照片的 EXIF 元資料（相機、光圈、快門、ISO、GPS 資訊）。
- **混合資料管理**:
  - **檔案模式**: 使用簡單的文字檔 (`index_post.txt`) 與資料夾結構進行內容管理。
  - **資料庫模式**: 支援 MySQL/MariaDB，具備正規化的資料表結構 (`blog_posts`, `blog_categories`) 以提供更強大的資料處理能力。
- **雙模式後台**: 統一的後台管理介面，允許您無縫切換管理本地檔案或資料庫內容。
- **混合渲染**: 支援 SPA 動態互動模式，亦可預先生成靜態 HTML 頁面以優化 SEO 與載入速度。
- **效能優化**: 內建圖片懶載入 (Lazy Loading) 策略，並提供 Python 腳本自動壓縮 JS/CSS。

## 技術棧
- **後端**: PHP 7.4+ (相容 PHP 8.x)
- **前端**: 原生 JavaScript, HTML5, CSS3
- **工具**: Python 3 (用於資產壓縮)

## 快速開始
1. 複製儲存庫。
2. 將 `config.example.php` 重新命名為 `config.php` 並設定資料庫資訊（若需使用資料庫模式）。
3. 將 `config.example.js` 重新命名為 `config.js` 並設定 API 類型與 Google 搜尋主控台 ID。
4. 在 `contents/index_post.txt` 與 `contents/post_files/` 加入您的文章（檔案模式）。
5. 執行 `make_html.php` 以生成靜態頁面。
6. 進入 `/admin` 登入後台管理系統。

## 致謝與第三方函式庫
本專案使用了以下開源函式庫，感謝原作者的貢獻：

### 核心與前台
- **[Dindent](https://github.com/gajus/dindent)** (Gajus Kuizinas):
  用於 `make_html.php` 中，將生成的 HTML 原始碼進行排版美化。
- **[html2text](https://github.com/soundasleep/html2text)** (Jevon Wright):
  用於將 HTML 內容轉換為純文字，以便處理元資料。
- **[exif-js](https://github.com/exif-js/exif-js)**:
  用於前端 (`static/exif.js`)，從圖片中讀取並顯示 EXIF 資訊。

### 後台管理系統
- **[Bootstrap 5](https://getbootstrap.com/)**:
  後台介面採用 Bootstrap 5 框架構建，提供響應式且現代化的 UI。
- **[SweetAlert2](https://sweetalert2.github.io/)**:
  用於替代 JavaScript 原生彈出視窗，提供美觀且高度可客製化的提示訊息。