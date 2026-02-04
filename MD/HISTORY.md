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
    - Updated scanning logic in `admin/lang_init.php` to exclude `install_` prefixed files.
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
    - **Single Source Architecture**: Rewrote `make_html.php` to generate all outputs directly from `blog_template.html`, eliminating chain-dependencies and structure degradation.
    - **Regex-Based Parsing**: Replaced `DOMDocument` with high-performance Regex (`preg_match_all`, `preg_replace_callback`). This solved the "auto-corruption" of HTML5 `<template>` tags and fixed attribute URL-encoding issues (`%7B%7B`).
    - **Placeholder Standardization**: Unified all template variables to the `{{variable}}` format to prevent syntax collisions with CSS/JS.
    - **PHP 5.x Compatibility**: Maintained backward compatibility with PHP 5.x (using `array()` syntax and removing modern-only dependencies).

### [14:40] Automated Asset Compression Optimization
- **Task**: Refine the JS/CSS minification workflow.
- **Implementation**:
    - **Smart Directory Ignore**: Updated `mini.py` logic to correctly handle nested path matching (e.g., `admin/assets`), preventing accidental compression of third-party libraries.
    - **Expanded Exclusion List**: Added `langs` and `PHP_LIB` to the global ignore list.
    - **Auto-Cleanup Routine**: Implemented a cleanup function in `mini.py` to automatically detect and remove mistakenly generated `.min.js` / `.min.css` files.

### [15:15] Micro-Template Framework Development
- **Task**: Decouple template logic from business logic in the build script.
- **Implementation**:
    - **TemplateManager Class**: Created `PHP_LIB/TemplateManager.php` as a lightweight, reusable template engine supporting nested templates and list rendering.
    - **Script Simplification**: Refactored `make_html.php` to utilize the new manager, reducing boilerplate code and improving maintainability.
    - **Processing Pipeline**: Centralized "template stripping," "path correction," and "image optimization" into a unified `pipeline()` function.

### [15:45] File-MTIME Based Cache Mechanism
- **Task**: Implement incremental builds to reduce redundant rendering time.
- **Implementation**:
    - **Smart Dependency Tracking**: Added `checkCache()` logic to compare output timestamps against all source dependencies (Source HTML, Global Template, Config).
    - **Optimized Workflow**: The build script now only processes modified content, significantly speeding up the update process for large blogs.
    - **Forced Rebuild**: Added CLI support for `-f` / `--force` flags to bypass cache when necessary.

---

## 2026-01-30 (繁體中文)

### [12:05] 初步專案掃描與分析
- **任務**: 理解專案架構 (混合式 SSG + SPA)。
- **Prompt**: "再次掃描此專案目錄下的開發檔案 *.js *.css *.php *.py (不需要測試目錄)，以及 blog.html，以理解內容。稍後將進行修改。"

### [12:15] 資料庫遷移工具優化
- **任務**: 修改 `migrate_full.php` 以讀取中央設定檔 `config.php`。
- **Prompt**: "希望 migrate_full.php 內的資料庫連線設定可以改成讀取 config.php。"

### [12:20] Git 版本控制初始化 (.gitignore)
- **任務**: 建立完整的過濾規則，排除敏感資訊、大型相片資源與自動生成的 HTML 檔。
- **Prompts**: 
    - "之後想要用 git 管理，但在此之前，請 Gemini 幫我建立 git ignore 設定檔。首先，含有敏感資料的程式不要上傳。"
    - "除了 blog.html 樣板檔外，根目錄下其他生成的檔案都加入排除。"
    - "Google 驗證檔也移除。"
    - "preview 目錄下的所有圖片檔都排除。" (後續修正為移除 .jpg .png)
    - "移除 pic 目錄。"
    - "排除 category 目錄下除了 readme.md 以外的所有內容。"
    - "排除 contents 目錄下除了 readme.md 以外的所有內容。"
    - "排除 static 內的圖片檔。"

### [12:30] 目錄用途文件化 (READMEs)
- **任務**: 為主要目錄建立雙語說明文件並更新專案架構文件。
- **Prompts**: 
    - "請根據專案實作，幫我在 contents 目錄內寫入 readme.md 說明此目錄用途。先用英文表達，再用中文。"
    - "補充說明 tags 可以用逗號 (`,`) 分隔。"
    - "category 目錄也幫我一起建立。"
    - "分類描述可以拿現有的目錄和檔案當範例。"
    - "在 static 建立 readme.md，簡述其用途。"
    - "在 preview 也建立 readme.md 並說明其用途。"
    - "根據目前的目錄結構和排除內容，更新 ARCHITECTURE.md。"
    - "為此專案做一個簡單介紹，並在根目錄建立 readme.md。"

### [12:45] 儲存庫發佈 (GitHub)
- **任務**: 初始化 Git 並推送到 GitHub。
- "我想將此專案上傳到我的 GitHub https://github.com/erspicu/ersalblog"

### [12:50] 致謝與開源授權
- **任務**: 在 README 中註記使用的第三方 PHP 與 JS 函式庫以尊重原作者。
- **Prompt**: "我的 PHP_LIB 內有一些第三方的套件，還有 static 內有一個讀取 exif 的函式庫。將這些套件說明寫入根目錄 readme 以尊重原版本，然後 push 到 git。"

### [13:00] 資料庫 API 開發 (SQL-based)
- **任務**: 建立 `api_dbsqlbase.php` 以支援從 MySQL 讀取內容，同時維持與 SPA 格式的相容性。
- **Prompt**: "幫我改寫一個資料庫版本的 api_filebase.php。api_dbsqlbase.php 將處理 db 版本 api。"

### [13:10] 建置工具調整 (Python)
- **任務**: 優化壓縮腳本。
- **Prompts**: 
    - "執行 mini.py"
    - "改寫 mini.py，config.example.js 不需要壓縮。"

### [13:20] 後台管理系統建置
- **任務**: 建立包含登入、文章管理與分類管理的後台系統。
- **Prompts**: 
    - "需要建立一個後台管理機制。從資料庫版本開始。首先幫我修改 config.php 加入後台帳號密碼設定，並在移除敏感資訊後更新到 config.example.php。"
    - "建立一個 admin 目錄存放後台相關 PHP 代碼。首先建立登入驗證介面，登入後可以登出。後續再增加更多功能。"
    - "建立文章管理功能：可以發表新文章、編輯舊文章、刪除舊文章、編輯標籤與指派分類。"
    - "如果後台管理有使用第三方元件，請將它們打包進 admin 目錄，避免來源消失時遺失。"
    - "加入分類管理功能：可以新增、移除或重新命名分類。"
    - "儀表板應顯示 PHP 版本、DB 連線資訊、DB 剩餘大小、DB 佔用大小、總文章數等。"

### [13:45] UI/UX 打磨與版面統一
- **任務**: 將後台統一為側邊欄佈局，優化文章列表顯示，並引入 SweetAlert2。
- **Prompts**: 
    - "登入後的後台版面有點怪。希望點擊文章或分類管理後，版面能跟登入頁保持一致——左邊是側邊欄，右邊是管理介面，就像儀表板一樣。"
    - "在文章管理的文章列表中，將描述與標題放在檔案 URL 下方。如果太長則換行。"
    - "編輯和刪除操作垂直排列且顏色不同。這是刻意的設計嗎？"
    - "沒關係，保留它。但原生的 JS 刪除警告很醜。使用好看一點的提示視窗。如果要用第三方元件，請下載使用。"

---

## 2026-01-31 (繁體中文)

### [12:05] PHP 7.x 相容性檢查與修復
- **任務**: 確保程式碼能在 PHP 7.x 環境中執行。
- **Prompt**: "檢查是否有任何 PHP 檔案與 PHP 7 有相容性問題。如果有，請修復。"
- **修復**: 將 `str_ends_with()` 替換為 `substr()`，將 `match` 表達式替換為陣列與 `??` 邏輯。

### [12:15] 儀表板資料庫資訊強化
- **任務**: 顯示詳細的 MySQL/MariaDB 版本與連線資訊。
- **Prompts**: 
    - "在後台儀表板中，希望在連線資訊增加 mysql 版本。"
    - "希望能有 mysql 或 mariadb 這種詳細資訊。"

### [12:30] 混合式管理系統
- **任務**: 支援在登入時選擇「資料庫模式」或「檔案系統模式」，並提供統一介面。
- **Prompt**: "因為我的 blog 架構目前呈現檔案系統、資料庫系統併行，我希望在登入階段能夠讓 user 選擇進入後的管理版本。如果選擇非資料庫版本，登入後介面基本上跟資料庫版本一樣,但管理的內容從檔案 blog 的檔案系統而來。"
- **實作**: 建立 `admin/data_provider.php` 封裝 `DataManager` 類別，抽象化資料讀寫邏輯。

### [12:45] 日誌系統與開發規範優化
- **任務**: 修復日誌編碼問題並將開發規範寫入設定。
- **Prompts**: 
    - "gemini_log.md 我這邊打開看是亂碼，希望能夠讓它在繁體中文環境中正常顯示。"
    - "我這邊是 win11，utf8 檔案用筆記本開還是亂碼請修正。"
    - "還是有問題，請參考 gemini.md 的紀錄形式去修正。"
    - "這次正常了。幫我將這動作配置 (寫入正確紀錄) 寫到 gemini.md 內。"
- **技術細節**: 強制使用 UTF-8 with BOM 編碼。

### [16:07] 登入健康檢查
- **任務**: 在登入前自動偵測 DB 連線與檔案目錄完整性。
- **Prompt**: "後臺管理登入，顯示目前是否有正確的 blog 資料庫環境和資料內容與連線能力，也檢查是否有檔案架構 blog 所需之資料檔和目錄架構。如果沒有請做畫面提示，並且禁止登入無法使用的系統。"
- **實作**: 建立 `admin/health_check.php` 用於即時狀態顯示與禁用無效模式。

### [16:14] 檔案轉資料庫匯入工具
- **任務**: 提供從檔案模式一鍵匯入資料至資料庫的功能。
- **Prompt**: "登入檔案模式後，希望可以增加一個功能分類，用來將檔案內容匯入到資料庫內。請參考 migrate_full.php 的設計概念。"
- **實作**: 建立 `admin/tool_migrate.php` 並整合至後台側邊欄。

### [16:20] 資料庫架構正規化
- **任務**: 將分類欄位拆分為獨立資料表與關聯表。
- **Prompt**: "我希望可以修正資料庫版本的分類架構設計，請再建立兩個 table，一個紀錄分類名稱，一個紀錄有哪些文章使用哪個分類，並且修正相關受影響的程式和畫面，也就是說分類管理畫面要增加新增功能。"
- **實作**: 建立 `blog_categories` 與 `blog_post_categories` 資料表，更新 `DataManager` 與分類管理 UI。

### [16:26] 冗餘欄位清理與邏輯重構
- **任務**: 移除 `blog_posts` 中的 `post_categories` 並重構查詢。
- **Prompt**: "目前看起來 blog_posts 內已經不需要 post_categories 欄位？我可以在資料庫中直接刪除那個欄位嗎？程式有需要相對應修改嗎？"
- **實作**: 重構 `getAllPosts`、`getPost` 等方法改用 `GROUP_CONCAT`。建立 `admin/db_drop_column.php` 進行安全刪除。

### [16:39] 文件與格式更新
- 將 `reme.txt` 內容整合至 `README.md` 並更新第三方套件資訊。
- `gemini_log.txt` 轉換為 `gemini_log.md` 並強制使用 UTF-8 BOM 編碼。

### [19:05] 自動化初始化系統 (資料庫初始化)
- **任務**: 建立自動化 DB 資料表建立與資料遷移。
- **實作**: 
    - 建立 `admin/db_init.php` 支援從檔案系統一鍵遷移至 DB。
    - 修改 `admin/login.php` 為「已設定連線但缺資料表」的狀態提供引導連結。
- **穩定性優化**: 
    - 解決 MySQL DDL 隱式提交導致的 "No active transaction" 錯誤。
    - 拆分 SQL 語句並增強 Transaction 狀態偵測以提升初始化成功率。

### [20:20] 檔案系統修復工具
- **任務**: 建立檔案系統結構修復與反向 DB 匯出功能。
- **實作**:
    - 建立 `admin/file_init.php`，與 DB 初始化對稱。
    - 支援從 MySQL 讀取內容以自動重建 `contents/` 索引與 `category/` 目錄結構。
    - 在登入頁整合檔案系統健康檢查，引導使用者進行修復。

### [00:05] 後台版本控管與國際化 (i18n)
- **任務**: 實作後台版本顯示與多語系支援 (繁中/英文)。
- **實作**:
    - **版本機制**: 初步嘗試掃描檔案時間，後改為在 `admin/version_config.php` 中的靜態版本 (vYYYY.MM.DD.HH.MM)。
    - **i18n 架構**: 建立 `langs/admin/` 目錄，以檔名區分語言 (`zh_TW.php`, `en_US.php`)。
    - **登入頁改版**: `admin/login.php` 加入語言下拉選單與版本顯示。

### [00:20] 後台全面國際化
- **任務**: 將多語系支援擴展至整個後台系統。
- **實作**:
    - 建立 `admin/lang_init.php` 負責載入語言與 `__()` 翻譯函數。
    - 修改 `admin/auth.php` 進行全域 i18n 引入。
    - 替換 `index.php` (儀表板)、`posts.php` (文章管理)、`categories.php` (分類管理)、`post_edit.php` (文章編輯) 與 `tool_migrate.php` (匯入工具) 中的硬編碼文字。
    - 支援前端 JS i18n: 建立 `zh_TW.js` / `en_US.js`，透過 `admin/common_js_inc.php` 動態引入以支援 SweetAlert2 提示。
    - 修正 `admin/health_check.php` 以支援多語系狀態訊息。

### [00:50] Vibe Coding 資訊整合
- **任務**: 在登入頁顯示用於開發的 AI 工具版本。
- **實作**:
    - **自動化**: 更新 `gemini.md` "Update" 巨集以自動查詢 Gemini CLI 版本與 AI 模型名稱。
    - **設定寫入**: 將 Runtime 資訊 (CLI v0.26.0, Model gemini-3-pro-preview) 寫入 `admin/version_config.php`。
    - **登入頁顯示**: 在 `admin/login.php` 顯示 "Vibe coded with Gemini CLI..."。

### [01:10] 開發規範重構
- **任務**: 優化 Prompt 指令文件。
- **實作**: 將 `gemini.txt` 重構為結構化的 `gemini.md`，定義核心準則、自動化流程與日誌規範。

### [01:30] 環境與時區規範
- **任務**: 確保日誌與版本的時區正確性並修復亂碼。
- **實作**:
    - 更新 `gemini.md` 加入 UTC+8 時區規範與 Git Bash 執行建議。
    - 修復 `gemini_log.md` 的 BOM (`\xEF\xBB\xBF`) 編碼問題。

### [21:30] 系統時間校正與規範強化
- **任務**: 統一校正全站時間戳記至正確的 UTC+8 晚間時段。
- **實作**:
    - 校正 `HISTORY.md`、`admin/version_config.php` 與 `gemini_log.md` 的時間偏差。
    - 在 `gemini.md` 中明確要求未來所有紀錄直接使用 UTC+8。

### [21:35] 執行巨集指令: Update
- **任務**: 依照 `gemini.md` 執行文件更新、版本同步與 Git 發佈。
- **實作**:
    - 更新版本至 v2026.01.31.21.35。
    - 同步 Gemini CLI (v0.26.0) 與模型 (gemini-3-flash-preview) 資訊。

### [23:45] 日誌恢復與機制強化
- **任務**: 找回被覆蓋的 `gemini_log.md` 並防止重演。
- **實作**:
    - 從 Git 歷史中找回遺失的日誌紀錄並完成合併。
    - 儲存長期記憶並更新 `gemini.md`：規定日誌更新必須使用「追加」(Append) 模式。
    - 強化 `gemini.md`：要求詢問式 Prompt 需記錄回答摘要。

### [23:58] 檔案格式統一與 i18n 同步
- **任務**: 統一 readme 檔案格式並實作全站雙語化。
- **實作**:
    - 將 `category/`、`contents/`、`preview/` 與 `static/` 下的所有 `readme.txt` 重新命名為 `readme.md`。
    - 完成核心根目錄文件 `ARCHITECTURE.md` 與 `HISTORY.md` 的英文翻譯與同步。
    - 在 `gemini.md` 的 "Update" 流程中加入強制規則：「所有文件必須維持中英文內容同步」。

### [00:20] README 同步與巨集優化
- **任務**: 修正 README 內容差異並優化 Gemini CLI 指令。
- **實作**:
    - 同步 `README.md` (中文版) 缺失的段落以符合英文內容。
    - 在 `gemini.md` 新增 "Reload" (重讀) 巨集指令，用於透過 `git diff` 同步外部編輯。
    - 清理 `gemini.md` 中過多的空行以提升可讀性。

### [00:50] 開發評價與最終打磨
- **任務**: 進行部落格系統的綜合評價並執行最終巨集更新。
- **實作**:
    - 評價系統為「專業級、輕量且高度客製化」，並給予架構與文件 5 星評價。
    - 執行 "Update" 巨集以同步所有核心文件與版本資訊。

### [02:00] 可靠性強化與評價匯出
- **任務**: 強化日誌機制並匯出專案評價。
- **實作**:
    - 更新 `gemini.md` 要求詢問式 Prompt 需記錄回答摘要。
    - 新增 "Reload" 巨集以透過 `git diff` 同步外部編輯。
    - 透過切換至 Python 處理 (`utf-8-sig` 編碼) 修復 `.md` 檔案損壞 (中文亂碼) 問題。
    - 強制 `.md` 格式規範 (單一空行)。
    - 將綜合專案評價匯出至新的雙語文件 `EVALUATION.md`。

### [02:30] 戰略藍圖與未來規劃
- **任務**: 構思未來功能並建立長期開發藍圖。
- **實作**:
    - 深入分析 high-value 功能：伺服器端分頁、SQLite 支援、進階編輯器 (Editor.js) 與 Flickr/Google Sheets 整合。
    - 評估社群互動與技術基礎建設的重要性。
    - 將所有提案、優先級評等與可行性分析彙整至結構化的 `ROADMAP.md` 文件。

---

## 2026-02-01 (繁體中文)

### [12:30] SQLite 3 資料庫支援與介面顯示優化
- **任務**: 實作 SQLite 3 支援作為 MySQL 與檔案系統外的第三種選擇，並修正介面顯示不一致的問題。
- **實作**:
    - 在 `admin/data_provider.php` 的 `DataManager` 中新增 SQLite 支援。
    - 建立 `admin/sqlite_init.php` 負責資料庫初始化與資料遷移（支援從檔案或 MySQL 匯入）。
    - 建立 `api_sqlitebase.php` 供前端存取 SQLite 資料。
    - 更新 `admin/login.php` 與 `admin/health_check.php` 支援 SQLite 狀態偵測與模式切換。
    - 修正 MySQL 與 SQLite 間 `GROUP_CONCAT` 的語法相容性問題。
    - 優化後台介面（儀表板與側邊欄），使其能正確辨識並顯示 SQLite 連線詳情與檔案資訊。
    - 更新 `.gitignore` 與 `config.example.php` 以整合 SQLite 設定。

### [13:45] 全方位資料遷移系統
- **任務**: 實作支援檔案系統、MySQL 與 SQLite 三方互轉的強大遷移系統。
- **實作**:
    - 強化 `admin/tool_migrate.php`，在所有模式下皆支援匯入 (Pull) 與匯出 (Push) 操作。
    - 實作 `runDBMigration` 函數，處理資料庫對資料庫 (MySQL <-> SQLite) 的直接資料傳輸。
    - 更新後台介面，根據當前模式與設定動態顯示可用的遷移目標。
    - 在 `auth.php` 與 `health_check.php` 中加入嚴格的 PDO 擴充檢查，防止因環境不支援導致的致命錯誤。

### [14:05] 國際化 (i18n) 完整支援
- **任務**: 補齊新模組缺失的翻譯。
- **實作**:
    - 更新 `langs/admin/zh_TW.php` 與 `en_US.php`，補上遷移工具、SQLite 初始化與儀表板相關的翻譯鍵值。
    - 替換 `admin/tool_migrate.php`、`admin/sqlite_init.php`、`admin/index.php` 與 `admin/health_check.php` 中的硬編碼文字。
    - 確保所有新功能 (SQLite, Migration) 皆支援完整雙語顯示。

### [14:45] 備份基礎建設
- **任務**: 建立專用的備份目錄。
- **實作**:
    - 建立 `/backup` 目錄並新增雙語 `readme.md`。
    - 更新 `.gitignore` 排除 `/backup/*.zip` 檔案。

### [15:15] 備份還原與上傳
- **任務**: 強化備份工具，新增還原與上傳功能。
- **實作**:
    - 實作從本地 ZIP 備份檔還原系統的功能 (覆蓋內容)。
    - 新增檔案上傳功能以匯入外部備份，並加入檔案大小限制檢查與提示。
    - 整合 SweetAlert2 處理操作確認 (刪除、還原、建立) 與狀態訊息 (成功/失敗)。
    - 加入 Loading 遮罩以處理負時操作 (上傳、建立、還原) 的等待狀態。
    - 更新所有新備份功能的 i18n 支援。

### [15:30] 備份工具優化 (PHP 設定提示)
- **任務**: 增加對大型備份檔處理的使用者引導。
- **實作**:
    - 在 `admin/tool_backup.php` 上傳區塊新增 PHP 設定提示 (upload_max_filesize, post_max_size 等)。
    - 提供 PHP.ini 參數範例與大檔案的 FTP 替代方案建議。
    - 更新語系檔 (`zh_TW.php`, `en_US.php`) 加入相關提示文字。

### [15:45] MySQL 資料庫備份與還原
- **任務**: 擴充備份工具以支援 MySQL 資料庫模式。
- **實作**:
    - 實作 `createMysqlDump` 產生 SQL 結構與資料備份。
    - 實作 `restoreMysqlDump` 解析並執行 ZIP 內的 SQL 備份。
    - 更新 `create_backup` 將 SQL 檔 + 靜態資源 (`preview`, `pic`, `static/icon-192.png`) 打包為 `dbsqlbase-*.zip`。
    - 更新 `restore_backup` 支援 `dbsqlbase` 檔案，先還原資料庫再還原靜態檔案。

### [16:15] SQLite 資料庫備份與還原
- **任務**: 擴充備份工具以支援 SQLite 資料庫模式。
- **實作**:
    - 新增邏輯將使用中的 SQLite 資料庫檔 + 靜態資源打包為 `sqlitebase-*.zip`。
    - 新增邏輯從 ZIP 中還原 SQLite DB 檔案至設定路徑。
    - 新增 Helper 函數 (`addStaticFilesToZip`, `restoreStaticFiles`, `cleanupTempDir`) 修復 500 錯誤並重用程式碼。

### [16:30] 備份列表過濾
- **任務**: 避免混淆，僅顯示與當前模式相關的備份檔。
- **實作**:
    - 更新 `admin/tool_backup.php` 根據當前模式過濾列表：
        - 資料庫模式: 僅顯示 `dbsqlbase-*.zip`
        - SQLite 模式: 僅顯示 `sqlitebase-*.zip`
        - 檔案模式: 僅顯示 `filebase-*.zip`

### [19:30] 全方位安裝引導精靈
- **任務**: 設計並實作友善的系統初始化系統。
- **實作**:
    - 在根目錄建立 `install.php`。
    - 特性：環境檢測 (PHP 版本與 Unix 權限修復)、多模式資料庫測試 (MySQL/SQLite/File)、管理員設定與前端配置生成。
    - 多語系支援：建立 `langs/admin/install_zh_TW.php` 與 `install_en_US.php` 並將語系獨立管理。
    - 整合 `admin/version_config.php` 顯示系統版本資訊。

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