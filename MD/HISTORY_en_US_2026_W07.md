# History Logs - 2026 Week 07 (English)

## [2026-02-12]
### Added
- **Album Service: Timezone Configuration**: Added timezone selection to the installer, automatically applied to the backend environment.
- **Security Hardening**: Implemented `install.php` presence detection on login and dashboard pages with high-risk warnings.
- **Theme Takeover Protocol**: Introduced `uiTakeover` mechanism, allowing full-screen themes to bypass redundant parent rendering for peak performance.
- **Cross-Frame Resource Bridge**: Established communication between Iframes and parent window to bring Blazor projects under global `DownloadManager` control.

## [2026-02-11]
### Added
- **Album Service: Win11 Theme (Native Blazor Migration)**: Integrated compiled Blazor WASM engine via full-screen embedding.
    - **Technical Integration**: Built-in **Edge browser simulator** to showcase the technical whitepaper.
    - **Native Watermark**: Implemented "Activate Experimental Project" watermark directly in Razor Layout.
- **Album Service: Theme Selector (`ThemeSelect.html`)**: Modern card-based UI for dynamic style previewing.
- **Compression Config System**: Created `config/compression.json` for multi-spec (3XL~XS) dynamic thumbnail generation and smart selection.
- **Global Download Manager (`DownloadManager`)**: Implemented resource manager with a 3-concurrency limit for all downloads and displays.

### Fixed
- **Path Resolution**: Fixed 5-level deep path issues for Blazor in Apache subdirectory environments.
- **Config Centralization**: Created `album/config/` to unify management of `config.php` and `config.js`.
- **Environment Cleanup**: Removed legacy nested directories and temporary test files.

## [2026-02-10]
### Added
- **Blazor WASM Album Explorer (Experimental)**: Created project to simulate Windows 11 desktop experience.
