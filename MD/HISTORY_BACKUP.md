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
- **Implementation**: Created `blog_categories` and `blog_post_categories` 資料表，更新 `DataManager` 與分類管理 UI。

### [16:26] Redundant Field Cleanup and Logic Refactoring
- **Task**: Remove `post_categories` from `blog_posts` and refactor queries.
- **Prompt**: "Seems post_categories in blog_posts is no longer needed. Can I delete it from the DB? Do programs need corresponding changes?"
- **Implementation**: Refactored `getAllPosts`, `getPost`, etc., to use `GROUP_CONCAT`. Created `admin/db_drop_column.php` for safe deletion.

### [16:39] Documentation and Format Updates
- Integrated `reme.txt` content into `README.md` and updated third-party package info.
- `gemini_log.txt` 轉換為 `gemini_log.md` 並強制使用 UTF-8 BOM 編碼。

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

---

## 2026-02-07 (English)

### [14:15] Script Tag Protection
- **Task**: Prevent `<script>` tags in posts from executing while keeping them visible for technical articles.
- **Implementation**: Used `protect_script_tags` to escape script tags to `&lt;script&gt;`.

### [15:30] SSG Refactoring & Class Integration
- **Task**: Decouple post saving from static generation and unify build logic.
- **Implementation**: 
    - Created `PHPLib\StaticGenerator` class to encapsulate all build pipelines.
    - Updated `admin/post_save.php` to optionally trigger a targeted build.
    - Simplified `make_html.php` as a CLI wrapper for the Generator.

### [15:45] Stability & PHP 5.x Compatibility
- **Task**: Resolve 500 errors and ensure legacy environment support.
- **Implementation**: 
    - Fixed quoting and regex syntax in `StaticGenerator.php`.
    - Removed `$this` from closures for PHP 5.3 compatibility.
    - Downgraded language files from `[]` to `array()` syntax.

### [17:15] Build Management GUI & Layout Refactoring
- **Task**: Provide a manual build interface and improve navigation UX.
- **Implementation**:
    - Created `admin/build.php` with options for full/partial rebuilds and JSON API updates.
    - Unified navigation with `admin/sidebar_inc.php`. 
    - Implemented a **Fixed Sidebar** layout to keep menus accessible while scrolling.
    - Added "Static Page Missing" indicators across Dashboard and Post lists.

### [18:00] Server-side Pagination
- **Task**: Improve performance for post management with large datasets.
- **Implementation**: Implemented `getPostsPaged` in `DataManager` and added a pagination UI (15 posts per page) in `admin/posts.php`.

---

## 2026-02-07 (繁體中文)

### [14:15] 文章內容 Script 標籤保護 (Script Tag Protection)
- **任務**: 防止文章內的 `<script>` 內容在網頁中執行，同時確保其在技術文章中的可見性。
- **實作**: 
    - **核心邏輯**: 在 `admin/system_helper.php` 實作 `protect_script_tags` 函式，將 `<script>` 標籤轉義為 `&lt;script&gt;`。
    - **全域套用**: 整合至 `make_html.php` 與所有 `api/*.php` 檔案。

### [15:30] 靜態生成架構重構 (SSG Refactoring)
- **任務**: 解耦「資料發布」與「靜態網頁生成」，提供更靈活的建置管線。
- **實作**:
    - **邏輯封裝**: 建立 `PHP_LIB/StaticGenerator.php` 類別，統一管理 SSG 核心邏輯。
    - **後台整合**: 在 `admin/post_edit.php` 新增「儲存後立即重建」選項。
    - **相容性修復**: 修正 PHP 5.3 不支援 Closure 使用 `$this` 的限制，確保在舊版環境穩定執行。

### [15:45] SSG 穩定性修復與 PHP 5.x 相容性強化
- **任務**: 解決重構後出現的 500 錯誤並提升舊版 PHP 支援。
- **實作**: 
    - **語法修復**: 修正 `StaticGenerator.php` 內的引號轉義與 Regex 錯誤。
    - **相容性修復**: 針對 PHP 5.3 移除 Closure 中的 `$this` 使用，並將語系檔全面降級為 `array()` 語法。
    - **強健度提升**: 在 `post_save.php` 引入更全面的錯誤捕捉邏輯。

### [17:15] 後台建置管理頁面與導覽重構
- **任務**: 建立專用的建置管理介面並優化後台使用者體驗。
- **實作**:
    - **網站建置頁面**: 新增 `admin/build.php`，支援「強制重生」、「更新 JSON API」以及「選取特定文章建置」。
    - **導覽列組件化**: 建立 `admin/sidebar_inc.php` 並將後台選單統一化，實作 **Fixed Sidebar** 佈局，讓選單不隨頁面捲動消失。
    - **狀態偵測**: 在儀表板與文章列表中，加入「靜態網頁未建立」的即時偵測與紅色警告標籤。

### [18:00] 文章管理分頁功能
- **任務**: 解決文章量大時載入緩慢的問題。
- **實作**: 在 `DataManager` 實作 `getPostsPaged` 方法，並在 `admin/posts.php` 建立分頁導覽列（每頁 15 篇），顯著提升管理效率。