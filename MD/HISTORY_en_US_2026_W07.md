# History Logs - 2026 Week 07 (English)

## [2026-02-12]
### Added
- **Dashboard Disk Information**: Added real-time disk space monitoring to the admin dashboard, showing system total/free space, usage percentage, and actual storage size of the photo collection.
- **Album Data Caching**: 	 `make_album.php` now includes JSON generation caching. If the album content, metadata, and compression settings remain unchanged, the system reuses existing JSON files, significantly speeding up the build process for large collections.
- **Thumbnail Caching Mechanism**: `make_album.php` now checks file modification times; if a thumbnail is newer than the original, regeneration is skipped unless forced, significantly improving build speeds.
- **Orphaned Thumbnail Cleanup**: Automatically detects and removes thumbnail files in the `Thumbnail` directory that no longer have a corresponding original image.
- **Dynamic Thumbnail Config Loading**: The share modal now dynamically reads definitions from `compression.json` to generate size options, labels (Comments), and short URL IDs, eliminating the need for hard-coded values in JS.
- **Album Service: Win11 Theme (AOT Optimized)**: Successfully implemented **AOT (Ahead-of-Time)** compilation, translating C# directly into native WebAssembly instructions for peak performance.
- **Virtual Edge Browser Upgrade**: Added a dedicated **AOT Compilation Chapter** to the technical whitepaper, featuring project configuration snippets and performance metrics.
- **Immersive Slideshow Mode**: Enabled automatic taskbar hiding during slideshow playback to achieve true 100% screen coverage.
- **Security Hardening**: Implemented `install.php` presence detection on admin pages.
- **Album Service: Timezone Config**: Added timezone selection to the installer.

### Fixed
- **ShortURL Service Precision Fix**: Fixed precision loss in `shorturl.php` caused by `fmod` on large numbers, resolving the issue where sharing links always redirected to original images.
- **Share Link Config Sync**: Synchronized `album.js` and `admin/assets/js/album_selector.js` with the latest `compression.json` suffixes and offsets.
- **Window System Optimization**:
    - **Unified Stacking**: Refactored `z-index` management to resolve window layering conflicts.
    - **Drag Stability**: Implemented relative displacement logic to eliminate window jumping caused by Blazor re-renders.
- **Path Resolution**: Fixed 5-level deep resource path issues for subdirectory deployments.
- **Config Centralization**: Unified management of `config.php` and `config.js` into `album/config/`.

## [2026-02-11]
### Added
- **Album Service: Win11 Theme (Native Blazor Migration)**: Full-screen embedding of the Blazor WASM engine.
- **Album Service: Theme Selector (`ThemeSelect.html`)**: Modern card-based style explorer.
- **Compression Config System**: Dynamic multi-spec (3XL~XS) thumbnail generation.
- **Global Download Manager (`DownloadManager`)**: Resource manager with a 3-concurrency limit.
