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
    - Integrated `admin/version_config.php` display and versioning.

### [22:15] WSL2 Development Environment & OS Detection Enhancement
- **Task**: Automate LAMP stack setup in WSL2 and provide detailed OS distribution info.
- **Implementation**:
    - **Environment Setup**: Fully automated installation of Apache2, MySQL 8.0, and PHP 8.3 in WSL2 Ubuntu 24.04.
    - **Web Integration**: Configured Apache to listen on port 8086 and linked the Windows project directory via symlinks.
    - **Database Setup**: Initialized MySQL user and database matching `config.php`.
    - **phpMyAdmin**: Automated installation and integration with the custom port.
    - **Permission Fixes**: Enhanced `install.php` to detect WSL2 NTFS mounts and bypass incompatible `chmod` checks.
    - **OS Detection**: Created `admin/system_helper.php` for shared OS distribution and version detection (e.g., Ubuntu 24.04.1 LTS (WSL2) or Windows 11 Build info).
    - **Dashboard Update**: Integrated detailed OS info into `admin/index.php` and `install.php`.

### [23:20] Enhanced Windows OS Detection & UI Layout Optimization
- **Task**: Improve Windows version detection reliability and refine installation UI layout.
- **Implementation**:
    - **Advanced OS Detection**: Added COM/WMI support in `admin/system_helper.php` as the primary method for native Windows PHP environments, providing precise product names (e.g., Windows 11 Pro).
    - **Encoding Correction**: Implemented aggressive CP950 to UTF-8 conversion for PowerShell output to prevent garbled text in Traditional Chinese environments.
    - **UI Optimization**: Reorganized `install.php` system information into a 2x2 grid, doubling the display width for better readability of detailed OS strings.
    - **Git Strategy**: Updated `gemini.md` with WSL2-specific fallback rules to utilize `git.exe` for authenticated pushes (later refined to manual user push).

### [23:30] WSL2 Git Strategy Refinement
- **Task**: Standardize remote repository synchronization workflow in WSL2.
- **Implementation**:
    - **Policy Update**: Updated `gemini.md` to explicitly state that while code commits are handled within WSL2, the final `git push` action is to be performed manually by the user in an environment with proper credentials (e.g., Windows terminal).
    - **Macro Sync**: Adjusted the "Update" macro process to stop at the commit stage and provide a manual push reminder.

---

## 2026-02-03

### [19:30] Hybrid Draft System & Filename Normalization
- **Task**: Implement post draft mechanism and ensure consistent file naming.
- **Implementation**:
    - **Draft Mechanism**: 
        - File Mode: Drafts are saved as `.html.tmp`; published articles as `.html`.
        - DB/SQLite Mode: Dynamically added `status` column ('draft'/'published').
        - Frontend Filter: Updated all APIs and `make_html.php` to skip draft files.
    - **Filename Normalization**: 
        - Automated `YYYYMMDD-` prefixing based on post date.
        - Smart validation to support user-entered prefixes and avoid duplication.
    - **UI Updates**: Added "Save Draft" vs "Publish" buttons and status badges in Admin list and Dashboard.

### [19:40] Admin Settings GUI (config.js Management)
- **Task**: Provide a user-friendly interface to manage frontend configurations.
- **Implementation**:
    - Created `admin/settings.php` to manage `config.js`.
    - Supports GUI-based switching of API Source (File/DB/SQLite), Theme selection, and Google CSE ID.
    - Implemented regex-based config writing to preserve file formatting.

### [19:50] Advanced WYSIWYG Editor Integration
- **Task**: Upgrade the article editor from a raw textarea to a visual editor.
- **Implementation**:
    - **TinyMCE 6**: Fully deployed TinyMCE 6.8.2 locally (no CDN dependency).
    - **PageBreak Customization**: Customized the PageBreak plugin to use `<!--more-->` as the separator, matching existing frontend logic.
    - **i18n & UX**: Integrated dynamic language switching (zh_TW/en_US) and removed promotional branding for a cleaner interface.

### [20:00] Admin Internationalization Refactoring
- **Task**: Clean up language files and enforce stricter loading rules.
- **Implementation**:
    - Renamed language files to use `admin-` prefix (e.g., `admin-zh_TW.php`) to distinguish from installer files.
    - Updated `admin/lang_init.php` scanning logic to exclude `install_` prefixed files.
    - Simplified language dropdown to show only core language codes.

### [23:00] Promotional Website Development (Vibe Coding Experiment)
- **Task**: Create a modern, photography-focused promotional website to showcase ErsalBlog.
- **Implementation**:
    - **Technology**: React + Vite + Bootstrap 5 + Framer Motion.
    - **Content**: Detailed sections for Motivation (Hand-crafted origins), Evolution (The Vibe Leap), Technical Architecture, and Roadmap.
    - **4K Optimization**: Implemented custom container logic and responsive typography for ultra-wide screens, ensuring a centered and balanced layout.
    - **Deployment**: Configured Vite build with relative base paths for seamless integration with the existing Apache environment.
    - **Success Verification**: Successfully verified the promo site as a robust demonstration of Vibe Coding efficiency.

### [23:33] Model Switch & Global Update
- **Task**: Switch development model back to Gemini 3 Pro and sync all project documents.
- **Implementation**:
    - Updated `admin/version_config.php` to reflect the switch to `gemini-3-pro-preview`.
    - Executed the "Update" macro to synchronize HISTORY.md, ARCHITECTURE.md, and README.md.
    - Completed local Git commit to baseline the new promotional assets and technical refinements.

---

## 2026-02-04

### [14:05] Core SSG Pipeline Refactoring (Regex Transition)
- **Task**: Overhaul the static page generation logic for robustness and compatibility.
- **Implementation**:
    - **Single Source Architecture**: Rewrote `make_html.php`前頁生成邏輯，徹底解決結構劣化與轉碼問題。
    - **Regex 解析**: 移除 `DOMDocument` 改用 Regex 處理樣板與圖片優化，大幅提升相容性與效能。
    - **PHP 5.x 相容**: 維持向後相容性，確保在 AppServ 等舊版環境正常運作。

### [14:40] 自動化資產壓縮優化
- **任務**: 精煉 JS/CSS 壓縮流程。
- **實作**: 修正 `mini.py` 目錄排除邏輯，並新增自動清理機制，保持專案目錄整潔。

### [15:15] 微樣板管理器與增量建置
- **任務**: 提升建置效率與維護性。
- **實作**: 建立 `TemplateManager` 類別封裝解析邏輯，並實作基於檔案修改時間 (mtime) 的快取機制，大幅縮短生成時間。

---

## 2026-02-05 (繁體中文)

### [10:15] 前台多語系與動態配置
- **任務**: 實作前台多語系支援與動態時區/語系配置。
- **實作**: 建立 `langs/template/` 架構，將樣板硬編碼文字替換為 `{{variable}}`，並更新建置腳本與後台設定。

### [12:00] 安全性強化 (Security Hardening)
- **任務**: 修復路徑遍歷與 XSS 漏洞。
- **實作**: 在 API 引入 `basename()` 過濾，並在樣板生成與前端渲染流程中全面實作 HTML 轉義防護。

### [13:00] 純靜態 JSON API 模式與優化
- **任務**: 實作無後端 JSON 模式並解決亂碼 404 問題。
- **實作**: 
    - **單一資料源**: 將原本分散的 JSON 合併為 `api/json/data.json`，避免中文字元檔名導致的存取錯誤。
    - **前端過濾**: 在 `blog.js` 實作客戶端路由與篩選邏輯，達成全靜態瀏覽體驗。
    - **建置支援**: `make_html.php` 新增 `-json` 參數，一鍵導出完整資料包。