# BaxerMux Blog & Album History (2026_W07)

This week's development focused on **Album Service refactoring and Web Admin integration**, **i18n standardization**, and **Blog SEO enhancement**.

## 🌟 Highlights

### 1. Album Service Refactoring & Admin Integration
- **Core Engine**: Created `AlbumGenerator.php` to encapsulate scanning, thumbnailing, and indexing logic for both CLI and Web usage.
- **Maintenance GUI**: Implemented a visual maintenance page with support for full rebuilds, incremental updates, and a real-time progress polling panel.
- **Smart Engine Fallback**: Implemented automatic switching between Imagick and GD, supported by a comprehensive System Diagnostics tool.

### 2. i18n Standardization
- **Naming Protocol**: Standardized "Underscores for files (`zh_TW`) and Hyphens for web tags (`zh-TW`)".
- **Auto-Conversion**: Implemented logic in `auth.php` and `lang_init.php` to resolve conflicts between W3C standards and developer naming conventions.
- **Full Localization**: Completed backend JS language packs and synchronized all diagnostic/maintenance strings in both T. Chinese and English.

### 3. Blog Editor & SEO Enhancements
- **Dynamic Photo Picker**: Upgraded `album_selector.js` to support auto-detection of thumbnail specs and resolution info display.
- **SEO Preview Uploads**: Implemented post-level OG Image uploads with automatic processing (1200x630).
- **Favicon Customization**: Parameterized the site favicon path with a new configuration field in the admin settings.

---

## 🛠 Detailed Changes

### Album Service
- [New] Created `album/PHP_LIB/AlbumGenerator.php` shared core class.
- [New] Created `album/admin/maintenance.php` for visual task management.
- [New] Implemented Progress ID isolation and atomic progress I/O.
- [Fix] Resolved long-running task blocking issues by releasing PHP Sessions.
- [Optimized] Switched admin list previews to `XS` thumbnails for faster loading.
- [Tool] Created `album/toolshell/` with cross-platform (sh, ps1, bat) maintenance scripts.

### Blog System
- [New] Added SEO Preview (OG Image) upload field to the post editor.
- [New] Added Favicon path configuration to system settings.
- [Fix] Resolved HTTP 500 error in `admin/login.php` caused by incorrect method calls.
- [Fix] Corrected `html_lang` tag in `langs/template-zh_TW.php` (zh-Hant -> zh-TW).
- [SSG] Performed forced full rebuild to synchronize all language and tech updates.

### i18n & Technical
- [Standard] Dynamic HTML `lang` attributes across all admin PHP pages.
- [i18n] Created standalone `album/langs/admin-*.js` language packs.
- [AOT] Enabled Blazor AOT compilation for the Win11 theme with artifact optimization.

---
**Version**: v2026.02.14.15.27 (UTC+8)
**Tool**: Gemini CLI
