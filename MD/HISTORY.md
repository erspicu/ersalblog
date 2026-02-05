# Vibe Coding History

Recorded the development journey and original Prompt commands of this project through Vibe Coding with Gemini CLI.

---

## 2026-01-30

### [12:05] Initial Project Scan and Analysis
- **Task**: Understand project architecture (Hybrid SSG + SPA).
- **Prompt**: 
    > "Scan the development files *.js *.css *.php *.py in this project directory again (no need for the test directory), as well as blog.html, to understand the content. Modifications will follow."

### [12:15] Optimization of Database Migration Tool
- **Task**: Change `migrate_full.php` to read from the central configuration `config.php`.
- **Prompt**: 
    > "Hope the database connection settings in migrate_full.php can be changed to read from config.php."

### [12:20] Git Version Control Initialization (.gitignore)
- **Task**: Establish comprehensive filtering rules to exclude sensitive information, large photo resources, and automatically generated HTML files.
- **Prompts**: 
    - "Want to manage with git later, but before that, ask Gemini to create a git ignore configuration file. First, remove programs containing sensitive data from uploading."
    - "Add all other generated files in the root directory except the blog.html template file."
    - "Remove Google verification files as well."
    - "Exclude all image files under the preview directory." (Later corrected to remove .jpg .png files in preview)
    - "Remove pic directory."
    - "Exclude everything under category directory except readme.md."
    - "Exclude everything under contents directory except readme.md."
    - "Exclude image files in static."

### [12:30] Documentation of Directory Purposes (READMEs)
- **Task**: Create bilingual instruction documents for main directories and update the project architecture document.
- **Prompts**: 
    - "Help write the purpose of the contents directory into the readme.md file within it based on the project implementation. Express in English first, then in Chinese."
    - "Add that tags can be separated by commas (`,`)."
    - "Yes, help me build it together." (For the category directory)
    - "Category descriptions can use existing directories and files as examples."
    - "Create a readme.md in static, briefly explaining its purpose."
    - "Create a readme.md in preview as well and explain its purpose."
    - "Update ARCHITECTURE.md according to the current directory structure and excluded content."
    - "Make a simple introduction for this project and create readme.md in the root directory."

### [12:45] Repository Publication (GitHub)
- **Task**: Initialize Git and push to GitHub.
- **Prompt**: 
    > "I want to upload this project to my GitHub https://github.com/erspicu/ersalblog"

### [12:50] Acknowledgments and Open Source Libraries
- **Task**: Note the third-party PHP and JS libraries used in README to respect the original authors.
- **Prompt**: 
    > "My PHP_LIB contains some third-party packages, and there's an exif reading library in static. Write these package descriptions into the root readme to respect original versions, then push to git."

### [13:00] Database API Development (SQL-based)
- **Task**: Create `api_dbsqlbase.php` to support reading content from MySQL while maintaining compatibility with the SPA format.
- **Prompt**: 
    > "Help rewrite a database version of api_filebase.php. api_dbsqlbase.php will handle the db version api."

### [13:10] Build Tool Adjustments (Python)
- **Task**: Optimize minification script.
- **Prompts**: 
    - "Execute mini.py"
    - "Rewrite mini.py, config.example.js does not need minification."

### [13:20] Admin Dashboard Construction
- **Task**: Build an admin system including login, post management, and category management.
- **Prompts**: 
    - "Need to establish an admin mechanism. Start with the database version. First, help modify config.php to add admin username and password settings, and update to config.example.php after removing sensitive info."
    - "Create an admin directory for admin-related PHP code. First, build a login verification interface where users can logout after logging in. Add more features later."
    - "Build post management: can post new articles, edit old ones, delete old ones, edit tags, and assign categories."
    - "If admin management uses third-party components, bundle them into the admin directory to avoid loss if the source disappears."
    - "Add category management: can add, remove, or rename categories."
    - "Dashboard should show PHP version, DB connection info, remaining DB size, DB occupied size, total posts, etc."

### [13:45] UI/UX Polishing and Layout Unification
- **Task**: Unify admin to Sidebar layout, optimize post list display, and introduce SweetAlert2.
- **Prompts**: 
    - "Admin layout after login is a bit strange. Hope the layout remains consistent with the login page after clicking post or category management—sidebar on the left, management on the right, like the dashboard."
    - "In post list under post management, put description with title below the file URL. Wrap if too long."
    - "Edit and delete operations are vertically aligned with different colors. Is it a deliberate aesthetic design?"
    - "It's fine, keep it. But the native JS alert for deletion is ugly. Use a better-looking prompt window. If using third-party components, download them for use."

---

## 2026-01-31

### [12:05] PHP 7.x Compatibility Check and Fixes
- **Task**: Ensure code runs in PHP 7.x environment.
- **Prompt**: "Check if any PHP files have compatibility issues with PHP 7. If so, fix them."
- **Fixes**: Replaced `str_ends_with()` with `substr()`, replaced `match` expressions with array and `??` logic.

### [12:15] Dashboard Database Info Enhancement
- **Task**: Display detailed MySQL/MariaDB version and connection info.
- **Prompts**: 
    - "In admin dashboard, want to add mysql version to connection info."
    - "Hope to have detailed info like mysql or mariadb."

### [12:30] Hybrid Management System
- **Task**: Support choosing "Database Mode" or "File System Mode" at login with a unified interface.
- **Prompt**: "Because my blog architecture currently runs file system and database modes in parallel, I want users to choose the management version after logging in. If the non-database version is chosen, the interface is basically the same, but content is managed from the file system."
- **Implementation**: Created `admin/data_provider.php` to encapsulate `DataManager` class, abstracting data R/W logic.

### [12:45] Logging System and Dev Conventions Optimization
- **Task**: Fix log encoding issues and write dev conventions into config.
- **Prompts**: 
    - "gemini_log.md is garbled when I open it. Hope it shows correctly in Traditional Chinese."
    - "I'm on Win11; UTF8 files opened with Notepad are still garbled. Fix it."
    - "Still issues. Refer to gemini.md recording format for fix."
    - "Normal now. Help me write this configuration (correct logging) into gemini.md."
- **Technical Detail**: Enforced UTF-8 with BOM encoding.

### [16:07] Login Health Check
- **Task**: Automatically detect DB connection and file directory integrity before login.
- **Prompt**: "Admin login, show if the blog database environment/content/connection is correct, and check if file structure blog required data files/directories exist. If not, show screen prompt and forbid login to unusable systems."
- **Implementation**: Created `admin/health_check.php` for real-time status display and disabling invalid modes.

### [16:14] File-to-DB Import Tool
- **Task**: Provide one-click data import from file mode to database.
- **Prompt**: "After entering file mode, want to add a functional category for importing file content into the database. Refer to migrate_full.php design."
- **Implementation**: Created `admin/tool_migrate.php` integrated into the admin sidebar.

### [16:20] Database Schema Normalization
- **Task**: Split category field into independent tables and relation tables.
- **Prompt**: "Hope to fix the database version category architecture. Create two more tables: one for category names, one for which posts use which categories. Fix affected programs and screens; i.e., category management should have an add function."
- **Implementation**: Created `blog_categories` and `blog_post_categories` tables, updated `DataManager` and category management UI.

### [16:26] Redundant Field Cleanup and Logic Refactoring
- **Task**: Remove `post_categories` from `blog_posts` and refactor queries.
- **Prompt**: "Seems post_categories in blog_posts is no longer needed. Can I delete it from the DB? Do programs need corresponding changes?"
- **Implementation**: Refactored `getAllPosts`, `getPost`, etc., to use `GROUP_CONCAT`. Created `admin/db_drop_column.php` for safe deletion.

### [16:39] Documentation and Format Updates
- Integrated `reme.txt` content into `README.md` and updated third-party package info.
- `gemini_log.txt` transitioned to `gemini_log.md` with mandatory UTF-8 BOM encoding.

---

## 2026-02-01

### [19:05] Automated Initialization System (Database Initialization)
- **Task**: Build automated DB table creation and data migration.
- **Implementation**: 
    - Created `admin/db_init.php` for one-click migration from file system to DB.
    - Modified `admin/login.php` to provide guidance links for "Connection set but tables missing" state.
- **Stability Optimization**: 
    - Resolved "No active transaction" error caused by MySQL DDL implicit commit.
    - Split SQL statements and enhanced Transaction state detection to improve initialization success rate.

### [20:20] File System Recovery Tool
- **Task**: Build file system structure repair and reverse DB export.
- **Implementation**:
    - Created `admin/file_init.php`, symmetric with DB initialization.
    - Supports reading content from MySQL to automatically rebuild indices in `contents/` and directory structure in `category/`.
    - Integrated file system health check on login page to guide users in repair.

### [00:05] Admin Version Control and Internationalization (i18n)
- **Task**: Implement admin version display and multi-language support (T. Chinese/English).
- **Implementation**:
    - **Version Mechanism**: Initial attempt at scanning file times, later changed to static version in `admin/version_config.php` (vYYYY.MM.DD.HH.MM).
    - **i18n Architecture**: Created `langs/admin/` directory, distinguishing languages by filename (`zh_TW.php`, `en_US.php`).
    - **Login Page Revamp**: `admin/login.php` added language dropdown and version display.

### [00:20] Full Admin Internationalization
- **Task**: Extend multi-language support to the entire admin system.
- **Implementation**:
    - Created `admin/lang_init.php` for loading languages and `__()` translation function.
    - Modified `admin/auth.php` for global i18n introduction.
    - Replaced hardcoded text in `index.php` (Dashboard), `posts.php` (Post Mgmt), `categories.php` (Category Mgmt), `post_edit.php` (Post Edit), and `tool_migrate.php` (Import Tool).
    - Support for frontend JS i18n: Created `zh_TW.js` / `en_US.js`, dynamically included via `admin/common_js_inc.php` for SweetAlert2 prompts.
    - Fixed `admin/health_check.php` to support multi-language status messages.

### [00:50] Vibe Coding Info Integration
- **Task**: Display AI tool versions used for development on the login page.
- **Implementation**:
    - **Automation**: Updated `gemini.md` "Update" macro to automatically query Gemini CLI version and AI model name.
    - **Config Write**: Wrote Runtime info (CLI v0.26.0, Model gemini-3-pro-preview) into `admin/version_config.php`.
    - **Login Page Display**: Showed "Vibe coded with Gemini CLI..." on `admin/login.php`.

### [01:10] Development Specification Refactoring
- **Task**: Optimize Prompt instruction documents.
- **Implementation**: Refactored `gemini.txt` into structured `gemini.md`, defining core guidelines, automated flows, and logging specs.

### [01:30] Environment and Timezone Specifications
- **Task**: Ensure timezone correctness for logs and versions and fix garbled text.
- **Implementation**:
    - Updated `gemini.md` to include UTC+8 timezone specs and Git Bash execution recommendations.
    - Fixed BOM (`ï»¿`) encoding issues in `gemini_log.md`.

### [21:30] System Time Calibration and Specification Strengthening
- **Task**: Uniformly calibrate all站 time stamps to correct UTC+8 evening period.
- **Implementation**:
    - Corrected time deviations in `HISTORY.md`, `admin/version_config.php`, and `gemini_log.md`.
    - Explicitly required all future records to use UTC+8 directly in `gemini.md`.

### [21:35] Execute Macro Instruction: Update
- **Task**: Execute document updates, version sync, and Git publishing per `gemini.md`.
- **Implementation**:
    - Updated version to v2026.01.31.21.35.
    - Synced Gemini CLI (v0.26.0) and model (gemini-3-flash-preview) info.

### [23:45] Log Recovery and Mechanism Strengthening
- **Task**: Recover the overwritten `gemini_log.md` and prevent recurrence.
- **Implementation**:
    - Retrieved missing log records from Git history and merged them.
    - Saved long-term memory and updated `gemini.md`: stipulated that log updates must use "Append" mode.
    - Enhanced `gemini.md`: required inquisitive Prompts to record a summary of the response.

### [23:58] File Format Unification and i18n Synchronization
- **Task**: Unify readme file formats and implement site-wide bilingualism.
- **Implementation**:
    - Renamed all `readme.txt` files under `category/`, `contents/`, `preview/`, and `static/` to `readme.md`.
    - Completed English translation and synchronization for core root documents `ARCHITECTURE.md` and `HISTORY.md`.
    - Added a mandatory rule to the "Update" process in `gemini.md`: "All documents must maintain synchronized Chinese and English content."

### [00:20] README Synchronization and Macro Refinement
- **Task**: Fix README content discrepancy and refine Gemini CLI instructions.
- **Implementation**:
    - Synchronized missing sections in `README.md` (Chinese version) to match English content.
    - Added "Reload" (重讀) macro command to `gemini.md` for syncing external edits via `git diff`.
    - Cleaned up excessive blank lines in `gemini.md` for better readability.

### [00:50] Development Evaluation and Final Polishing
- **Task**: Conduct a comprehensive evaluation of the blog system and perform a final macro update.
- **Implementation**:
    - Evaluated the system as "Professional-grade, lightweight, and highly customized" with a 5-star rating for architecture and documentation.
    - Executed the "Update" macro to synchronize all core documents and version info.

### [02:00] Reliability Enhancement and Evaluation Export
- **Task**: Strengthen logging mechanisms and export project evaluation.
- **Implementation**:
    - Updated `gemini.md` to require response summaries for inquisitive prompts.
    - Added "Reload" macro to synchronize external edits via `git diff`.
    - Fixed `.md` file corruption (garbled Chinese characters) by switching to Python-based processing with `utf-8-sig` encoding.
    - Standardized `.md` formatting by enforcing single blank line spacing.
    - Exported comprehensive project evaluation to a new bilingual file `EVALUATION.md`.

### [02:30] Strategic Roadmap and Future Planning
- **Task**: Conceptualize future features and establish a long-term development roadmap.
- **Implementation**:
    - Conducted in-depth analysis of high-value features: Server-side pagination, SQLite support, Advanced Editors (Editor.js), and Flickr/Google Sheets integration.
    - Evaluated the importance of social engagement vs. technical infrastructure.
    - Compiled all proposals, priority ratings, and feasibility analysis into a structured `ROADMAP.md` file.

---

## 2026-02-01

### [12:30] SQLite 3 Database Support and Interface Optimization
- **Task**: Implement SQLite 3 support as an alternative to MySQL and the file system, and fix UI inconsistencies.
- **Implementation**:
    - Added SQLite support to `DataManager` in `admin/data_provider.php`.
    - Created `admin/sqlite_init.php` for database initialization and data migration (from file system or MySQL).
    - Created `api_sqlitebase.php` for frontend SQLite data access.
    - Updated `admin/login.php` and `admin/health_check.php` to support SQLite status detection and mode selection.
    - Fixed `GROUP_CONCAT` syntax compatibility issues between MySQL and SQLite.
    - Optimized Admin UI (Dashboard and Sidebar) to correctly identify and display SQLite connection details and file information.
    - Updated `.gitignore` and `config.example.php` for SQLite integration.

### [13:45] Comprehensive Data Migration System
- **Task**: Implement a robust two-way migration system supporting File System, MySQL, and SQLite.
- **Implementation**:
    - Enhanced `admin/tool_migrate.php` to support both Import (Pull) and Export (Push) operations in all modes.
    - Implemented `runDBMigration` to handle direct database-to-database data transfer (MySQL <-> SQLite).
    - Updated Admin UI to dynamically display available migration targets based on current mode and configuration.
    - Added strict environment checks for PDO extensions in `auth.php` and `health_check.php` to prevent fatal errors.

### [14:05] Internationalization (i18n) Completion
- **Task**: Complete missing translations for new modules.
- **Implementation**:
    - Updated `langs/admin/zh_TW.php` and `en_US.php` with missing keys for Migration Tool, SQLite Init, and Dashboard.
    - Replaced hardcoded text in `admin/tool_migrate.php`, `admin/sqlite_init.php`, `admin/index.php` and `admin/health_check.php`.
    - Ensured full bilingual support across all new features (SQLite, Migration).

### [14:45] Backup Infrastructure
- **Task**: Establish a dedicated directory for backups.
- **Implementation**:
    - Created `/backup` directory and added a bilingual `readme.md`.
    - Updated `.gitignore` to exclude `/backup/*.zip` 檔案.

### [15:15] Backup Restore & Upload
- **Task**: Enhance backup tool with restore and upload capabilities.
- **Implementation**:
    - Implemented system restoration from local ZIP backups (overwrites contents).
    - Added file upload functionality for importing external backups, with file size limit checks and hints.
    - Integrated SweetAlert2 for operation confirmations (Delete, Restore, Create) and status messages (Success/Error).
    - Added loading overlays for time-consuming operations (Upload, Create, Restore).
    - Updated i18n support for all new backup features.

### [15:30] Backup Tool Optimization (PHP Config & Hints)
- **Task**: Add user guidance for handling large backup files.
- **Implementation**:
    - Added PHP configuration hints (upload_max_filesize, post_max_size, etc.) in `admin/tool_backup.php` upload section.
    - Provided example PHP.ini settings and FTP alternatives for large files.
    - Updated language files (`zh_TW.php`, `en_US.php`) with new hint text.

### [15:45] MySQL Database Backup & Restore
- **Task**: Extend backup tool to support MySQL database mode.
- **Implementation**:
    - Implemented `createMysqlDump` to generate SQL structure and data dumps.
    - Implemented `restoreMysqlDump` to parse and execute SQL dumps from ZIP.
    - Updated `create_backup` to pack SQL dump + static resources (`preview`, `pic`, `static/icon-192.png`) into `dbsqlbase-*.zip`.
    - Updated `restore_backup` to handle `dbsqlbase` files by restoring DB first, then static files.

### [16:15] SQLite Database Backup & Restore
- **Task**: Extend backup tool to support SQLite database mode.
- **Implementation**:
    - Added logic to pack the active SQLite database file + static resources into `sqlitebase-*.zip`.
    - Added logic to restore SQLite DB file from ZIP to the configured path.
    - Added helper functions (`addStaticFilesToZip`, `restoreStaticFiles`, `cleanupTempDir`) to fix 500 errors and reuse code.

### [16:30] Backup List Filtering
- **Task**: Avoid confusion by showing only relevant backup files.
- **Implementation**:
    - Updated `admin/tool_backup.php` to filter the backup list based on current mode:
        - DB Mode: Show only `dbsqlbase-*.zip`
        - SQLite Mode: Show only `sqlitebase-*.zip`
        - File Mode: Show only `filebase-*.zip`

### [19:30] Universal Installation Wizard
- **Task**: Design and implement a user-friendly initialization system.
- **Implementation**:
    - Created `install.php` in the root directory.
    - Features: Environment check (PHP version & Unix-like permission fixes), Multi-mode DB test (MySQL/SQLite/File), Admin setup, and Frontend config generation.
    - Multi-language Support: Created `langs/admin/install_zh_TW.php` and `install_en_US.php`.
    - Integrated `admin/version_config.php` 顯示系統版本資訊。

### [22:15] WSL2 開發環境建置與 OS 偵測強化
- **任務**: 自動化 WSL2 中的 LAMP 環境配置並提供詳細 OS 資訊。
- **實作**:
    - **環境配置**: 自動在 WSL2 Ubuntu 24.04 安裝 Apache2, MySQL 8.0, PHP 8.3。
    - **網頁整合**: 設定 Apache 監聽 8086 埠並透過軟連結掛載 Windows 專案目錄。
    - **資料庫初始化**: 建立符合專案設定的 MySQL 使用者與資料庫。
    - **phpMyAdmin**: 自動安裝並整合至自訂埠號。
    - **權限處理**: 修正 `install.php` 偵測 WSL2 NTFS 掛載點並自動跳過無效的權限修正步驟。
    - **OS 偵測**: 建立 `admin/system_helper.php` 提供詳細 OS 資訊 (如 Ubuntu 發行版或 Windows Build 號)。
    - **介面整合**: 在儀表板與安裝精靈中同步顯示詳細作業系統環境。

### [23:20] 強化 Windows OS 偵測與 UI 佈局優化
- **任務**: 提升 Windows 版本偵測可靠性並精煉安裝介面佈局。
- **實作**:
    - **進階 OS 偵測**: 在 `admin/system_helper.php` 中加入 COM/WMI 支援，作為原生 Windows PHP 環境的第一優先偵測方式，提供精確的產品名稱（如 Windows 11 專業版）。
    - **編碼校正**: 針對 PowerShell 輸出實作了強制的 CP950 轉 UTF-8 轉換，防止在繁體中文環境下出現亂碼。
    - **UI 優化**: 將 `install.php` 系統資訊重構為 2x2 網格顯示，將顯示寬度提升一倍，以利閱讀長的作業系統版本字串。
    - **Git 策略**: 更新 `gemini.md` 加入 WSL2 專屬回退規則，自動調用 `git.exe` 處理需要憑證驗證的推送操作。

### [23:30] WSL2 Git 策略精煉
- **任務**: 規範 WSL2 下的遠端儲存庫同步流程。
- **實作**:
    - **規範更新**: 更新 `gemini.md` 明確規定 WSL2 僅負責完成 Commit，最終的 `git push` 由使用者於具備憑證的環境 (如 Windows 終端機) 手動執行。
    - **巨集同步**: 調整「更新」關鍵字指令的流程，自動化執行至本地 Commit 為止，並加入手動推送提醒。

---

## 2026-02-03 (繁體中文)

### [19:30] 混合式草稿系統與檔名標準化
- **任務**: 實作文章草稿機制並確保檔案命名的一致性。
- **實作**:
    - **草稿機制**: 
        - 檔案模式：草稿存為 `.html.tmp`，正式文章為 `.html`。
        - 資料庫模式：動態補齊 `status` 欄位 ('draft'/'published')。
        - 前台過濾：更新所有 API 與 `make_html.php` 自動跳過草稿檔案。
    - **檔名標準化**: 
        - 根據發文日期自動補上 `YYYYMMDD-` 前綴。
        - 智慧偵測使用者輸入，支援手動輸入前綴並避免重複處理。
    - **UI 更新**: 新增「暫存草稿」與「正式發布」按鈕，並在後台列表與儀表板加入狀態標籤。

### [19:40] 網站設定圖形化介面 (config.js 管理)
- **任務**: 提供友善的介面來管理前端配置。
- **實作**:
    - 建立 `admin/settings.php` 用於管理 `config.js`。
    - 支援透過 UI 切換資料來源 (File/DB/SQLite)、選擇主題以及設定 Google CSE ID。
    - 採用正規表達式寫入配置，保留檔案原始格式。

### [19:50] 進階視覺化編輯器整合
- **任務**: 將文章編輯器從純文字框升級為視覺化編輯器。
- **實作**:
    - **TinyMCE 6**: 在本地端完整部署 TinyMCE 6.8.2，不依賴外部 CDN。
    - **客製化分頁**: 修改 PageBreak 外掛使用 `<!--more-->` 作為分隔符號，完美相容現台邏輯。
    - **語系與體驗**: 整合動態語系切換 (繁中/英文)，並移除升級提示與品牌標記以簡化介面。

### [20:00] 後台語系架構重構
- **任務**: 清理語系檔案並實施更嚴格的載入規則。
- **實作**:
    - 將語系檔更名為 `admin-` 前綴 (如 `admin-zh_TW.php`)，以區別安裝程式檔案。
    - 更新 `admin/lang_init.php` 掃描邏輯，自動排除 `install_` 開頭的檔案。
    - 精簡語言下拉選單，僅顯示核心語系代碼。

### [23:00] 專案宣傳網站開發 (Vibe Coding 實驗)
- **任務**: 建立一個現代化、攝影導向的宣傳網站來展示 ErsalBlog。
- **實作**:
    - **技術棧**: React + Vite + Bootstrap 5 + Framer Motion。
    - **內容**: 詳細規劃了開發動機 (匠人初心)、演進歷程 (Vibe 飛躍)、技術架構與未來藍圖。
    - **4K 優化**: 為超寬螢幕實作了自定義容器邏輯與響應式字體，確保版面完美置中且視覺平衡。
    - **部署**: 設定 Vite 編譯路徑，確保與現有 Apache 環境無縫整合。
    - **驗證**: 成功驗證宣傳網站作為 Vibe Coding 開發效率的強力證明。

### [23:33] 模型切換與全域更新
- **任務**: 將開發模型切換回 Gemini 3 Pro 並同步所有專案文件。
- **實作**:
    - 更新 `admin/version_config.php` 以反映切換至 `gemini-3-pro-preview`。
    - 執行「更新」巨集同步 HISTORY.md、ARCHITECTURE.md 與 README.md。
    - 執行本地 Git Commit，將宣傳網站資產與技術優化內容入庫。

---

## 2026-02-04 (繁體中文)

### [14:05] 核心 SSG 建置管線重構 (Regex 轉型)
- **任務**: 全面翻新靜態頁面生成邏輯，提升穩定性與相容性。
- **實作**:
    - **單一真理來源**: 重寫 `make_html.php`，讓所有頁面直接從 `blog_template.html` 生成，徹底解決結構劣化問題。
    - **Regex 解析**: 移除 `DOMDocument` 改用高效能 Regex，解決 HTML5 標籤相容性、屬性自動轉碼 (`%7B%7B`) 及內容巢狀錯誤。
    - **變數標準化**: 統一所有樣板變數為 `{{variable}}` 格式，防止與 CSS/JS 語法衝突。
    - **PHP 5.x 相容**: 維持對舊版 PHP 的相容性，移除現代專屬語法依賴。

### [14:40] 自動化資產壓縮優化
- **任務**: 精煉 JS/CSS 壓縮流程。
- **實作**:
    - **智慧路徑排除**: 修正 `mini.py` 目錄排除邏輯，正確處理巢狀路徑 (如 `admin/assets`)，保護第三方套件不被誤壓縮。
    - **清理機制**: 新增自動清理功能，執行時自動偵測並刪除誤生成的 `.min.js` / `.min.css` 檔案。

### [15:15] 微樣板管理器 (TemplateManager) 開發
- **任務**: 將樣板處理邏輯與業務邏輯解耦。
- **實作**:
    - **TemplateManager**: 建立 `PHP_LIB/TemplateManager.php` 輕量級類別，封裝解析、變數替換與列表渲染。
    - **建置管線化**: 建立 `pipeline()` 函式統一管理路徑修正與圖片優化，大幅提升 `make_html.php` 的可讀性。

### [15:45] 基於修改時間 (mtime) 的快取機制
- **任務**: 實作增量建置 (Incremental Build) 以大幅縮短生成時間。
- **實作**:
    - **智慧相依偵測**: 新增 `checkCache()`，比對目標檔案與所有來源檔案（原始文章、母樣板、設定檔）的修改時間。
    - **自動跳過**: 僅針對有變動的內容進行渲染，針對大規模部落格顯著提升效率。
    - **強制重產**: 支援 CLI 參數 `-f` / `--force` 繞過快取。

---

## 2026-02-05 (繁體中文)

### [10:15] 前台多語系與動態配置 (i18n & Config)
- **任務**: 實作前台多語系支援，並讓時區與語系設定動態化。
- **實作**:
    - **i18n 樣板架構**: 建立 `langs/template/` 目錄存放 `zh_TW.php` 與 `en_US.php`。將 `static/blog_template.html` 內的硬編碼文字替換為 `{{variable}}` 變數。
    - **動態配置**: 更新 `make_html.php` 讀取 `config.js` 的語系 (`blog_lang`) 與時區 (`timezone`) 設定，並動態載入對應語系檔進行替換。
    - **後台設定**: 強化 `admin/settings.php`，支援透過 GUI 設定部落格語系與時區。
    - **安裝精靈**: 更新 `install.php`，在初始設定時詢問語系與時區，確保 `config.php` 與 `config.js` 初始化正確。
    - **翻譯覆蓋**: 更新 `langs/admin/*.php` 補齊所有新 UI 字串的翻譯。

### [10:45] 配置邏輯修正 (Config Correction)
- **任務**: 將語系與時區設定移回 `config.php` 以符合 SSG 邏輯。
- **實作**:
    - **配置歸位**: 將 `blog_lang` 與 `blog_timezone` 從 `config.js` 移回 `config.php`。
    - **SSG 純化**: 更新 `make_html.php` 直接從 `config.php` 讀取變數，移除對 `config.js` 的解析。
    - **管理更新**: 更新後台儲存與安裝邏輯，確保設定能正確分配到 PHP 與 JS 檔案。

### [11:15] 靜態頁面與分類相容性修正
- **任務**: 解決語系佔位符失效與舊式分類標記失效問題。
- **實作**:
    - **變數傳遞**: 更新 `make_html.php` 在渲染標籤與分類容器時合併語系變數，解決 `{{lang_post_tags_title}}` 顯示問題。
    - **分類相容**: 優化 `matchCategories`，同時比對完整與無副檔名檔名，確保舊文章能正確歸類。

### [11:30] 樣板渲染完整化與分類統計優化
- **任務**: 補齊所有語系佔位符替換，並修正分類文章統計數量。
- **實作**:
    - **全域變數補完**: 更新 `make_html.php` 確保所有子樣板（`tmpl_post_main`, `tmpl_blog_list_container`）渲染時皆傳入語系變數，解決 `{{lang_back_to_top}}` 顯示問題。
    - **精確統計**: 更新 `scanCategories` 與 `api_filebase.php` 的統計邏輯，改為同時偵測實體檔案與 `.html` 版本，並過濾不存在的文章，確保首頁分類計數準確。
    - **雙向相容**: 強化 `api_filebase.php` 的分類比對邏輯，全面支援含副檔名與不含副檔名的檔案格式。

### [12:00] 安全性強化 (Security Hardening)
- **任務**: 執行全面的安全性審計與漏洞修復。
- **實作**:
    - **路徑遍歷修復**: 修正 `api/api_filebase.php` 中的 `get_Category_index` 函式，對輸入的分類參數強制執行 `basename()` 過濾，防止 `../` 攻擊。
    - **靜態生成 XSS 防護**: 更新 `make_html.php`，在資料傳入樣板前使用 `htmlspecialchars` 對標題、描述、標籤與分類名稱進行轉義。
    - **DOM XSS 防護**: 修正 `static/blog.js`，新增 `escapeHtml` 輔助函式，並在前端渲染 JSON 資料（如分類名、標籤名、文章標題）時強制轉義。
    - **後台防護確認**: 驗證 `admin/post_edit.php` 與 `admin/posts.php` 已具備正確的輸出轉義機制。