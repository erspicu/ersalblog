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
2. Copy `config.example.php` to `config.php` and configure your database settings.
3. Copy `config.example.js` to `config.js` and set your API type and Google Search Console ID.
4. Access `/admin`, select **Database Mode**. If the database is not initialized, follow the on-screen link to the **Initialization Wizard** to create tables and import data.
5. Alternatively, add posts in `contents/index_post.txt` for File mode.
6. Run `make_html.php` to generate static pages.

---

## 快速開始
1. 複製儲存庫。
2. 將 `config.example.php` 重新命名為 `config.php` 並設定資料庫資訊。
3. 將 `config.example.js` 重新命名為 `config.js` 並設定 API 類型與 Google 搜尋主控台 ID。
4. 進入 `/admin` 並選擇 **資料庫模式**。若尚未初始化，請點擊畫面提示進入 **初始化精靈** 以建立資料表並匯入資料。
5. 若選擇 **檔案模式** 且目錄結構缺失，系統亦會引導進入 **檔案建構精靈**，可選擇從資料庫還原內容。
6. 執行 `make_html.php` 以生成靜態頁面。

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