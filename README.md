# BaxerMux Photography Blog

A lightweight, high-performance blog system designed specifically for photographers. It features a unique hybrid architecture combining Static Site Generation (SSG) and Single Page Application (SPA) capabilities without requiring a traditional SQL database for its core operation.

## Key Features
- **Photography Focused**: Automatically extracts and displays EXIF metadata (Camera, Aperture, Shutter, ISO, GPS) from your photos using `exif.js`.
- **Hybrid Data Management**:
  - **Flat-file Mode**: Uses simple text files (`index_post.txt`) and directory structures for content management.
  - **Database Mode**: Supports MySQL/MariaDB and **SQLite 3** with a normalized schema (`blog_posts`, `blog_categories`) for robust data handling.
- **Dual-Mode Admin Panel**: A unified administration interface that allows you to switch between managing local files or the database seamlessly.
    - **Multi-language Support**: Fully localized admin interface (Traditional Chinese / English).
    - **Vibe Coding**: Developed with Gemini CLI & Gemini AI Models, featuring automated version tracking.
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

# BaxerMux 攝影部落格 (Photography Blog)

一個輕量、高效能，專為攝影師設計的部落格系統。採用獨特的混合架構，結合了靜態網站生成 (SSG) 與單頁應用程式 (SPA) 的優點，且核心運作不需要傳統的 SQL 資料庫。

## 核心特性 (Key Features)
- **攝影導向**：整合 `exif.js`，自動從您的照片中提取並顯示 EXIF 元數據（相機、光圈、快門、ISO、GPS）。
- **混合數據管理**：
  - **檔案模式 (Flat-file)**：使用簡單的文字檔 (`index_post.txt`) 與目錄結構進行內容管理。
  - **資料庫模式 (Database)**：支援 MySQL/MariaDB 與 **SQLite 3**，並採用正規化架構 (`blog_posts`, `blog_categories`) 處理更強大的數據需求。
- **雙模式管理後台**：統一的管理介面，讓您能在管理本地檔案或資料庫之間無縫切換。
    - **多語系支援**：完整的後台介面本地化（繁體中文 / 英文）。
    - **Vibe Coding**：由 Gemini CLI 與 Gemini AI 模型開發，具備自動化版本追蹤。
- **混合渲染**：內容可透過 SPA 動態呈現以獲得豐富互動，或預先渲染為靜態 HTML 頁面以提升 SEO 與載入速度。
- **效能優化**：內建圖片延遲載入 (Lazy Loading) 機制，並透過 Python 腳本自動壓縮 JS/CSS。

## 技術棧 (Tech Stack)
- **後端 (Backend)**: PHP 7.4+ (相容 PHP 8.x)
- **前端 (Frontend)**: 原生 JavaScript (Vanilla JS), HTML5, CSS3
- **工具 (Tools)**: Python 3 (用於資產壓縮)

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
