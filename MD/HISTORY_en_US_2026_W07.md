# History Logs - 2026 Week 07 (English)

## [2026-02-12]
### Added
- **Album Service: Win11 Theme (AOT Optimized)**: Successfully implemented **AOT (Ahead-of-Time)** compilation, translating C# directly into native WebAssembly instructions for peak performance.
- **Virtual Edge Browser Upgrade**: Added a dedicated **AOT Compilation Chapter** to the technical whitepaper, featuring project configuration snippets and performance metrics.
- **Immersive Slideshow Mode**: Enabled automatic taskbar hiding during slideshow playback to achieve true 100% screen coverage.
- **Security Hardening**: Implemented `install.php` presence detection on admin pages.
- **Album Service: Timezone Config**: Added timezone selection to the installer.

### Fixed
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
