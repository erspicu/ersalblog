# Vibe Coding History

Recorded the development journey and original Prompt commands of this project through Vibe Coding with Gemini CLI.

---

## 2026-01-30 (English)

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

## 2026-01-31 (English)

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
- **Implementation**: Created `blog_categories` and `blog_post_categories` tables, updated `DataManager` and Category Management UI.

### [16:26] Redundant Field Cleanup and Logic Refactoring
- **Task**: Remove `post_categories` from `blog_posts` and refactor queries.
- **Prompt**: "Seems post_categories in blog_posts is no longer needed. Can I delete it from the DB? Do programs need corresponding changes?"
- **Implementation**: Refactored `getAllPosts`, `getPost`, etc., to use `GROUP_CONCAT`. Created `admin/db_drop_column.php` for safe deletion.

### [16:39] Documentation and Format Updates
- Integrated `reme.txt` content into `README.md` and updated third-party package info.
- Converted `gemini_log.txt` to `gemini_log.md` with forced UTF-8 BOM encoding.

---

## 2026-02-01 (English)

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
- **Task**: Uniformly calibrate all time stamps to correct UTC+8 evening period.
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
    - Added "Reload" macro command to `gemini.md` for syncing external edits via `git diff`.
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

## 2026-02-01 (English)

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
    - Updated `.gitignore` to exclude `/backup/*.zip` files.

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
    - Updated `create_backup` to package SQL file + static assets (`preview`, `pic`, `static/icon-192.png`) as `dbsqlbase-*.zip`.
    - Updated `restore_backup` to support `dbsqlbase` files, restoring database first then static files.

### [16:15] SQLite Database Backup & Restore
- **Task**: Extend backup tool to support SQLite database mode.
- **Implementation**:
    - Added logic to package active SQLite database file + static assets as `sqlitebase-*.zip`.
    - Added logic to restore SQLite DB file from ZIP to configured path.
    - Added Helper functions (`addStaticFilesToZip`, `restoreStaticFiles`, `cleanupTempDir`) to fix 500 errors and reuse code.

### [16:30] Backup List Filtering
- **Task**: Avoid confusion by showing only relevant backup files.
- **Implementation**:
    - Updated `admin/tool_backup.php` to filter list based on current mode:
        - DB Mode: Show only `dbsqlbase-*.zip`
        - SQLite Mode: Show only `sqlitebase-*.zip`
        - File Mode: Show only `filebase-*.zip`

### [19:30] All-in-One Installation Wizard
- **Task**: Design and implement a user-friendly system initialization wizard.
- **Implementation**:
    - Created `install.php` in root directory.
    - Features: Environment detection (PHP version & Unix permission fix), multi-mode database testing (MySQL/SQLite/File), admin setup, and frontend config generation.
    - i18n Support: Created `langs/admin/install_zh_TW.php` and `install_en_US.php` for isolated language management.
    - Integrated `admin/version_config.php` to display system version info.

### [22:15] WSL2 Development Environment & OS Detection
- **Task**: Automate LAMP setup in WSL2 and provide detailed OS info.
- **Implementation**:
    - **Env Setup**: Automated install of Apache2, MySQL 8.0, PHP 8.3 in WSL2 Ubuntu 24.04.
    - **Web Integration**: Configured Apache on port 8086 with symlink to Windows project directory.
    - **DB Init**: Created MySQL user and database matching project config.
    - **phpMyAdmin**: Automated install and integration on custom port.
    - **Permissions**: Fixed `install.php` to detect WSL2 NTFS mounts and skip invalid permission fixes.
    - **OS Detection**: Created `admin/system_helper.php` to provide detailed OS info (e.g., Ubuntu distro or Windows Build).
    - **UI Integration**: Displayed detailed OS environment in Dashboard and Installation Wizard.

### [23:20] Enhanced Windows OS Detection & UI Layout
- **Task**: Improve Windows version detection reliability and refine install UI.
- **Implementation**:
    - **Advanced OS Detection**: Added COM/WMI support in `admin/system_helper.php` as primary detection for native Windows PHP, providing exact product names (e.g., Windows 11 Pro).
    - **Encoding Fix**: Implemented forced CP950 to UTF-8 conversion for PowerShell output to prevent garbled text in T. Chinese environments.
    - **UI Optimization**: Refactored `install.php` system info into a 2x2 grid with double width for better readability of long OS strings.
    - **Git Strategy**: Updated `gemini.md` with WSL2-specific fallback rules, automatically invoking `git.exe` for push operations requiring credentials.

### [23:30] WSL2 Git Strategy Refinement
- **Task**: Standardize remote repo synchronization in WSL2.
- **Implementation**:
    - **Spec Update**: Updated `gemini.md` to explicitly state WSL2 handles local commits only; `git push` must be done manually in a credentialed environment (e.g., Windows Terminal).
    - **Macro Sync**: Adjusted "Update" keyword flow to automate up to local commit, adding a manual push reminder.

---

## 2026-02-02 (English)

### [23:11] Configuration Schema Optimization
- **Task**: Restructured `config.php` and synchronized `config.example.php`.

### [23:18] Installation Wizard Upgrade
- **Task**: Updated `install.php` and its language files to support the new config structure.

### [23:32] Static Path Standardisation
- **Task**: Modified `make_html.php` to output static articles to `post/` directory with automated resource path correction.

---

## 2026-02-03 (English)

### [00:05] API Architecture Refactoring
- **Task**: Moved `api_*.php` to `api/` directory and updated frontend/backend dependencies.

### [00:25] Multi-CSS Theme Implementation
- **Task**: Created `blog-dark.css` and updated configuration to support dynamic theme loading.

---

## 2026-02-04 (English)

### [12:00] SSG Pipeline Revamp
- **Task**: Optimized `make_html.php` using `blog_template.html` as the single source of truth with `{{variable}}` placeholders.

### [14:05] Regex-based Template Parsing
- **Task**: Rewrote core parsing logic using Regex, eliminating `DOMDocument` dependencies for better HTML5 and PHP 5.x compatibility.

---

## 2026-02-05 (English)

### [10:15] Front-end Multi-language Support
- **Task**: Implemented frontend i18n with dynamic timezone and language configuration.

### [12:30] Pure Static JSON API Mode
- **Task**: Implemented client-side routing and unified `data.json` generation for serverless hosting.

### [13:30] Smart Build Cache (Hash-based)
- **Task**: Introduced incremental build mechanism using configuration hashing and file modification times.

---

## 2026-02-06 (English)

### [23:30] i18n Structure Flattening
- **Task**: Moved all language files to the `langs/` root to simplify loading logic.

### [23:45] Global PHP 5.x Compatibility
- **Task**: Executed large-scale syntax downgrading and implemented security polyfills for legacy environments.

---

## 2026-02-07 (English)

### [14:15] Script Tag Protection in Content
- **Task**: Prevent `<script>` content in articles from executing while keeping it visible in technical posts.
- **Implementation**: 
    - **Core Logic**: Implemented `protect_script_tags` function in `admin/system_helper.php` to escape `<script>` tags to `&lt;script&gt;`.
    - **Benefit**: Scripts are now displayed as text without execution, balancing security and readability.
    - **Global Application**: Integrated into `make_html.php` and all `api/*.php` files.

### [14:35] API & System Stability Fixes
- **Task**: Resolve 500 errors and security issues in admin and backup tools.
- **Implementation**:
    - **Path Fix**: Corrected language path error in `admin/lang_init.php`.
    - **Backup Fix**: Added missing references and fixed CSRF Token validation in `tool_backup.php`.
    - **Sync Filtering**: Enhanced API logic to return only posts with existing static files, ensuring frontend display matches physical files.

### [14:40] Comprehensive API Refactoring
- **Task**: Eliminate code duplication and unify API logic for all three modes (File/MySQL/SQLite).
- **Implementation**:
    - **Unified Logic**: Refactored `api_filebase.php`, `api_sqlitebase.php`, and `api_dbsqlbase.php` to use a single core logic `get_data()`.
    - **Code Reduction**: Eliminated 90% of duplicate logic and removed redundant `page()` entry points and helper functions.
    - **Compatibility**: Ensured refactored JSON output remains fully compatible with frontend `blog.js`.

### [15:30] SSG & Pagination Refactoring
- **Task**: Unify build logic and implement a high-performance hybrid pagination system.
- **Implementation**:
    - **Generator Class**: Created `PHP_LIB/StaticGenerator.php` and fixed hardcoded titles for i18n support.
    - **Hybrid Pagination**: Implemented Server-side (PHP API) and Client-side (JSON Mode) pagination logic, significantly improving performance for large datasets.
    - **UI/UX Enhancement**: Created a beautified pagination component with Prev/Next navigation and dark mode support.
    - **Admin Integration**: Added "Posts Per Page" management in admin settings.

### [15:55] Navigation Logic Fixes & Security Hardening
- **Task**: Resolve date filtering issues and continue stability optimizations.
- **Implementation**:
    - **Bugfix**: Fixed `date_range` logic to support both 4-digit (Year) and 6-digit (Year-Month) matching.
    - **Sync Filter**: Enhanced API to automatically exclude posts without physical static files.
    - **Stability**: Fixed CSRF validation and language path reference errors in the backup tool.

### [16:35] Multi-Theme Expansion & UI Stabilization
- **Task**: Expand visual styles and reinforce layout stability.
- **Implementation**:
    - **Soft Pink Theme**: Added `blog-pink.css`, featuring Sakura pink tones, rounded UI elements, and soft shadows.
    - **Matrix Theme**: Added `blog-matrix.css`, simulating terminal aesthetics with black/green palette and scanline effects.
    - **Layout Hardening**: Enforced `clear: both` on all sidebar and pagination containers to prevent layout breakage.
    - **Tweaks**: Removed image filters in Matrix theme to preserve original colors and optimized pagination responsiveness.

### [16:50] Stability Fixes & Process Reinforcement
- **Task**: Fix build-time errors and strengthen development guidelines.
- **Implementation**:
    - **Bugfix**: Corrected language key references in `StaticGenerator.php` (added `lang_` prefix) to resolve `Undefined index` warnings during SSG execution.
    - **Rule Update**: Updated `gemini.md` to specify the use of Linux `date` command for timestamping, ensuring accurate logs and versioning.

### [23:55] Album Service Module Development
- **Task**: Build a standalone, database-free, file-system-based album service.
- **Core Architecture**:
    - **Generator**: Developed `album/make_album.php` to handle static HTML generation, thumbnail compression, and EXIF extraction.
    - **API**: Created `album/api/api_album.php` to provide album list data and pagination.
    - **Frontend**: Developed `album/static/js/album.js` and `album_template.html` for SPA experience and component-based rendering.
- **Key Features**:
    - **Multi-size Thumbnails**: Automatically generates `thumbXL` (2048px), `thumbL` (1600px), `thumbM` (1024px), `thumb` (800px).
    - **EXIF Support**: Backend extracts EXIF info from original photos and renders it to static pages.
    - **Static Pagination**: Implemented static pagination generation (24 items/page) for album inner pages.
    - **Detail Interactions**: Supported fullscreen modal view, original file download, and social sharing links.
    - **Smart Navigation**: Implemented smart breadcrumbs and "Back to List" logic returning to the correct pagination.
- **Optimization & Standards**:
    - **CLI Support**: `make_album.php` supports `-s` (skip thumbs) and `-f` (force rebuild) flags.
    - **Git Management**: Configured `.gitignore` to exclude all generated files and original photos.
    - **Documentation**: Created detailed `readme.md` files for all album directories.

## [v2026.02.08.03.15] - 2026-02-08

### Album Service Optimization
- **ShortURL System**:
  - Implemented `album/shorturl.php` with Base62 encoding and auto MIME type detection.
  - Upgraded obfuscation algorithm: Modular Multiplicative Hashing + XOR mask for random-like 5-char slugs.
  - Generator Integration: `make_album.php` now auto-generates `shorturl.txt` for reverse lookup.
- **Frontend Refactoring (De-Bootstrap)**:
  - **Dependency Removal**: Removed Bootstrap, switched to native HTML/CSS/JS.
  - **CSS Grid**: Rewrote `album/static/album.css` using modern CSS Grid.
  - **Lightweight**: Integrated SVG icons, removed external fonts for speed.
- **Theme System**:
  - Added theme switching support (`album`, `album-dark`, `album-pink`, `album-matrix`).
  - Created `album/config.js` and `config.example.js` for user customization.
- **Enhancements**:
  - **Download**: Added download button for original high-quality images.
  - **Advanced Sharing**: Multi-size link sharing window with copy and "Original/Short URL" toggle.
  - **Nav Optimization**: Centered pagination, fixed header alignment.
  - **EXIF Display**: Backend PHP rendering replaces frontend `exif.js`.
- **Maintenance**:
  - Updated `.gitignore`, cleaned old thumbnails, forced static asset rebuild.
  - Updated `album/Collection/相簿1/readme.md` standards.

## [v2026.02.08.14.00] - 2026-02-08

### Album Admin Panel
- **Standalone Admin Interface**:
  - Established `album/admin/` with full CRUD capabilities (Create/Edit/Delete albums and photos).
  - Integrated with the Blog Admin Dashboard to automatically detect and link to the Album service.
- **Key Features**:
  - **Settings Management**: Added a frontend settings page to directly modify `config.js` (Theme, API Mode, Items Per Page).
  - **Photo Management**: Supported batch upload, cover setting, filename renaming, and metadata editing.
  - **UI Optimization**: Adopted an 8-column wide layout with unified "Contain" (proportional scaling) preview style.
  - **Performance**: Implemented `thumbXS` (320px) thumbnails for ultra-fast admin loading.

### Album Service Architecture Refactoring
- **Full SPA Transition**:
  - Removed legacy static HTML generation (`view/`) in favor of a JSON-driven SPA architecture.
  - Implemented decoupled pagination: Client-side for `json` mode, Server-side for `api_filebase` mode.
- **System Optimizations**:
  - Fixed API path resolution errors regarding URL encoding and non-UTF-8 directory names.
  - Synchronized configuration structures between `config.js` and `config.example.js`.
  - Improved date logic: Prioritizes metadata, falling back to file system timestamp if missing.