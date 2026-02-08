# Vibe Coding History

Recorded the development journey and original Prompt commands of this project through Vibe Coding with Gemini CLI.

---

## [v2026.02.08.20.36] - 2026-02-08 (English)

### Album Service Deep Integration
- **Path Flexibility**: Introduced `$album_path` in `config.php`, supporting flexible relative path settings (e.g., `album/` or `../album/`).
- **Health Detection**: Implemented album service health check logic and integrated it into the Blog Admin Dashboard for real-time status and access management.
- **Editor Integration**: Integrated the album picker into the post editor, supporting dynamic paths and cross-directory image display with automated API-side path correction.
- **Settings Page Overhaul**: Refactored `admin/settings.php` to separate backend core settings (config.php) and frontend interface settings (config.js) into independent forms and save buttons for better security.
- **Stability Fixes**:
  - Fixed division-by-zero error in the API caused by setting posts per page to 0, which previously led to frontend JSON parsing failures.
  - Corrected regex replacement logic using `${1}` syntax to prevent conflicts between numbers and backreferences that corrupted `config.php`.
  - Reinforced path correction in `StaticGenerator.php` to ensure correct image display in static articles across various deployment structures.

## [v2026.02.08.14.00] - 2026-02-08 (English)

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

## [v2026.02.08.03.15] - 2026-02-08 (English)

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

---

## 2026-01-30 (English)
... (omitted)
