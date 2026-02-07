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

### [15:30] Static Site Generation (SSG) Refactoring
- **Task**: Decouple "Data Publishing" from "Static Page Generation" to provide a flexible build pipeline.
- **Implementation**:
    - **Logic Encapsulation**: Created `PHP_LIB/StaticGenerator.php` class to unify SSG core logic.
    - **Admin Integration**: Added "Rebuild Immediately" option in `admin/post_edit.php`.
    - **i18n Support**: Replaced hardcoded title in `StaticGenerator.php` with dynamic language variable.
    - **Compatibility**: Fixed PHP 5.3 limitation regarding `$this` in closures to ensure stability in legacy environments.