# History Logs - 2026 Week 07 (English)

## [2026-02-11]
### Added
- **Album Service: Win11 Theme (Native Blazor Migration)**: Replaced CSS simulation with full integration of the compiled Blazor WASM engine.
    - **Native Integration**: Published the Blazor project to the `dist/` folder and implemented automatic loading via `theme.js`.
    - **Built-in Watermark & Edge Browser**: Added "Activate Experimental Project" watermark and a virtual Edge browser to display technical documentation.
    - **Technical Whitepaper**: Included deep C# code explanations (DI, JS Interop, HttpClient) and future professional visions (Edge Image Processing, Encryption Containers) within the virtual browser.
- **Album Service: Theme Selector (`ThemeSelect.html`)**: Created a dedicated theme preview page with a modern card-based UI to dynamically showcase all available album styles.
- **Theme List API**: Implemented `api/api_themes.php` to automatically scan theme directories and return a JSON list.
- **Global Download Manager (`DownloadManager`)**: Implemented a resource manager with concurrency control in `album.js`.
    - **Concurrency Limit**: Restricts simultaneous download/load tasks to a maximum of 3 to optimize bandwidth and prevent browser blocking.
    - **Event-Driven EXIF**: Resolved race conditions in EXIF parsing under concurrency by implementing an event-listener mechanism, ensuring accurate technical info display.

### Fixed
- **Blazor Fix**: Cleaned up deeply nested directories in `BlazorAlbumExplorer` caused by recursive publishing.
- **Git Optimization**: Added .NET build artifact ignore rules to `.gitignore` to prevent repository bloat.
- **Path Resolution**: Fixed internal path depths for Blazor in Apache subdirectory environments (adjusted from 3 levels to 5 levels) to ensure JSON and images load correctly.
- **Environment Cleanup**: Removed temporary file `(backup)config.php` and test screenshots (`qa1.png`, `qa3.png`) from the project root.
- **Album Service: Configuration Centralization**: Created `album/config/` directory to group `config.php` and `config.js` for better organization, updating all internal loading paths.
- **Album Service: Advanced Installation Wizard (v2)**: Significantly upgraded the installer to support full parameter configuration for `config.php` and `config.js` (Admin creds, Theme, API mode, Pagination).
- **Environment Update**: Updated version to `v2026.02.12.00.11`.

## [2026-02-10]
### Added
- **Blazor WASM Album Explorer (Experimental)**: Created an experimental project using Blazor WebAssembly to simulate the Windows File Explorer and windowing system experience.

## [2026-02-09]
### Added
- **Album Service: Y2K Theme**: Created a retro 2000s style theme (`album-y2k.css`).
- **Album Service: Matrix Theme Enhancements**: Added a CSS-only "Digital Rain" effect.
- **Frontend Theme Plugin Mechanism**: Implemented dynamic loading of `{theme}.js`.
