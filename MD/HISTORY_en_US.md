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