# Development History (2026 Week 08)

Core Focus: Comprehensive security hardening, Session isolation implementation, and global PHP 5.x compatibility calibration.

## Major Changes

### 1. MessageBoard Widgetization & Google Auth Integration
- **Widget Architecture Refactoring**: Transitioned MessageBoard from DOM injection to an **independent iframe-based Widget**.
    - Created `messageboard.html` as a standalone environment for better CSS/JS isolation.
    - Implemented a bootstrapper (`guestbook.js`) to dynamically generate iframes, supporting cross-site deployment.
    - Implemented auto-height synchronization using `postMessage` and `ResizeObserver`.
- **Google Identity Services (GIS)**:
    - Integrated Google Sign-in with client-side JWT decoding and backend **Token verification (tokeninfo)**.
    - **Session Persistence**: Implemented `localStorage` to remember login states across page reloads.
    - **Avatar & UID Logging**: Updated SQLite and GAS schemas to store user avatar URLs and unique IDs (`google_sub`).
- **Enhanced Admin Dashboard**:
    - **Config UI**: Added settings for Google Auth (Client ID/Toggle) and GAS Web App URL.
    - **Mode Sync**: Fixed synchronization issues between `config.js` and active sessions for management modes.

### 2. Global Subsystem Security Hardening
- **Session Isolation**: Independent session names for Blog, Album, and MessageBoard to prevent logout interference.
- **Unified Auth**: Synchronized authentication mechanisms across all sub-services.

### 3. Deep PHP 5.x Compatibility Optimization
- **Syntax Downgrading**: Ensured stable execution in legacy PHP 5.4+ environments.
- **Legacy Compatibility**: Automated structure completion for older `config.php` files.

### 4. AI Assistant Integration
- **Settings Refactoring**: Fully adopted **AJAX** for updates, **SweetAlert2** for UI, and completed **i18n** localization.

## Technical Optimizations
- **GAS Auto-migration**: GAS scripts now automatically detect and add missing columns to Google Sheets.
- **Logging**: Consolidated AI API logs into `debug.txt` with masked credentials.

## Version Info
- **Version**: v2026.02.16.22.13
- **CLI**: 0.28.2
- **Model**: gemini-3-flash-preview
