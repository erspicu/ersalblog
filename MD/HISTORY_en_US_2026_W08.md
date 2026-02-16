# Development History (2026 Week 08)

Core Focus: Comprehensive security hardening, Session isolation implementation, and global PHP 5.x compatibility calibration.

## Major Changes

### 1. MessageBoard Widgetization & Google Auth Integration
- **Widget Architecture Refactoring**: Transitioned MessageBoard from DOM injection to an **independent iframe-based Widget**.
    - Created `messageboard.html` as a standalone execution environment for better CSS/JS isolation.
    - Implemented a bootstrapper (`guestbook.js`) to dynamically generate iframes, supporting cross-site deployment and simplifying Google domain verification.
    - Implemented auto-height synchronization using `postMessage` and `ResizeObserver`.
- **Google Identity Services (GIS)**:
    - Integrated Google Sign-in for one-tap authentication.
    - Implemented client-side JWT decoding to retrieve user names and avatars.
    - **Avatar Storage & Display**: Updated SQLite schema and PHP API to store user avatar URLs and display them in the comment list.
- **Configurable Settings**: Integrated Google Client ID and toggle into `MessageBoard/config/config.js`.

### 2. Global Subsystem Security Hardening
- **Session Isolation**: Independent session names for Blog, Album, and MessageBoard to prevent cross-service logout interference.
- **Unified Auth Engine**: Synchronized authentication mechanisms across all sub-services.

### 3. Deep PHP 5.x Compatibility Optimization
- **Syntax Downgrading**: Ensured stable execution in legacy PHP 5.4+ environments.
- **Legacy Compatibility**: Automated structure completion for older `config.php` files.

### 4. AI Assistant Integration
- **Settings Refactoring**: Fully adopted **AJAX** for updates and **SweetAlert2** for enhanced user feedback.
- **Comprehensive i18n Support**: Completed localization for all AI-related UI across the admin panel.

## Technical Optimizations
- **Cleanup**: Removed the obsolete `BLOG AI.md` document.
- **Log Optimization**: AI API logs are now directed to `debug.txt` with masked keys.

## Version Info
- **Version**: v2026.02.16.21.52
- **CLI**: 0.28.2
- **Model**: gemini-3-flash-preview
