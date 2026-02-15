# Development History (2026 Week 07)

Core Focus: Full implementation of the **MessageBoard Service**, deep integration with the Blog system, and enhancement of platform-wide scalability.

## Major Changes

### 1. MessageBoard Service Implementation
- **Architecture**:
    - Implemented the **Adapter Pattern**, supporting seamless switching between "Local Storage (PHP+SQLite)" and "Cloud Storage (GAS+Google Sheets)".
    - **Extreme Decoupling**: The service features independent `admin/`, `api/`, `config/`, `langs/`, and `data/` directories, allowing it to run entirely standalone.
    - **Multi-tenant Support**: Dynamically detects `og:site_name` or URL parameters to identify sites, enabling a single installation to serve infinite independent websites.
- **Features**:
    - **Threaded Discussion Mode**: Supports "Topic + Flattened Discussion" logic, optimizing the reading experience for mobile users.
    - **Optimistic UI**: Provides a loading spinner and container mask during submission to improve responsiveness under slow network conditions (e.g., GAS).
- **Admin Dashboard**:
    - Built a dedicated management interface with multi-language login, environment diagnostics, and site-specific message moderation/deletion.
    - **Auto-Initialization**: SQLite mode automatically distributes database files based on site and page names, enabling zero-config deployment.

### 2. Blog Main System Integration
- **Template Refactoring**: Updated `static/blog_template.html` with a hidden MessageBoard container that only displays after successful plugin initialization.
- **Dynamic Loader**: Implemented a plugin loader in `static/blog.js` that automatically fetches scripts and configurations based on `config.js` settings.
- **Cache Control**: Appended timestamps (`?v=timestamp`) to all dynamically loaded scripts and styles to prevent stale browser caches.
- **Admin Settings Integration**: Added a MessageBoard configuration section in `admin/settings.php` with a unified JS file picker UI.

### 3. User Experience & Admin Enhancements
- **Smart Pagination**: Implemented "20 topics per page" in the admin dashboard, optimizing CSS for layout stability and loading speed with large datasets.
- **Page Title Recognition**:
    - Frontend plugin automatically captures `<meta property="og:title">`.
    - **GAS Mode**: Stores the title in the Google Drive file's "Description" field.
    - **SQLite Mode**: Automatically creates a `page_meta` table to store titles.
    - The admin panel now displays the page title first, with the ID in parentheses, greatly improving readability.
- **Account Security**: Added "Change Admin Username & Password" directly in the settings panel, eliminating manual config file edits.
- **Full i18n Support**: Replaced all hardcoded text (diagnostic items, delete confirmations, GAS paste button) with dynamic language tags.
- **User-Friendly Tools**: Added a "Paste" button next to the GAS URL field for one-click input.
- **Installation Guides**: Created `INSTALL_zh_TW.md` and `INSTALL_en_US.md` for comprehensive deployment instructions.

## Technical Optimizations
- **i18n Framework Upgrade**: Established the `MessageBoard/langs/` directory, supporting independent language packs for both the admin panel and frontend plugin.
- **CSS Theming**: Created `guestbook-dark.css` and optimized blog theme CSS files to ensure perfect contrast across Standard, Matrix, Pink, and Dark modes.
- **GAS Performance**: Refactored the GAS backend script to optimize data storage into a "One Site per Folder, One Page per Spreadsheet" structure, significantly improving load times for large blogs.

## Version Info
- **Version**: v2026.02.16.01.55
- **CLI**: 0.28.2
- **Model**: gemini-3-pro-preview
