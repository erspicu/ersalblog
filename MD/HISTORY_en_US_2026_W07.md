# History Logs - 2026 Week 07 (English)

## [2026-02-10]
### Added
- **Blazor WASM Album Explorer (Experimental)**: Created a new experimental project under `album/BlazorAlbumExplorer` using Blazor WebAssembly.
- **WinForms/WPF UI Style**: Successfully simulated the Windows File Explorer and windowing system experience using HTML/CSS.
- **Features**:
    - **Desktop Environment**: Includes taskbar, start menu, system tray, and live clock.
    - **Window Management**: Implemented full window dragging, maximize/minimize, and foreground/background (Z-Index) switching.
    - **File Browsing**: Reads `api/json/` data to display album lists with Windows 11-style folder icons and previews.
    - **Photo Viewer**: Built-in independent photo viewer window supporting mouse (left/right click) and keyboard arrow navigation.
    - **Slideshow Mode**: Supports fullscreen slideshow playback with ESC key exit.
    - **Info Panel**: Integrated EXIF data and Google Maps embedding.
    - **Photo Edit Simulation**: Implemented clockwise/counter-clockwise rotation (CSS Transform).
- **Windows 11 Visual Enhancements**:
    - **Refined Folders**: Used CSS to draw layered, shadowed Win11-style folder icons with content previews.
    - **Rounded Corners**: Applied standard 8px rounded corners to windows and dialogs.
    - **MessageBox**: Implemented standard Windows message boxes for Start Menu info display.

## [2026-02-09]
### Added
- **Album Service: Y2K Theme**: Created a "clumsy" retro 2000s style theme (`album-y2k.css`) featuring deep blue backgrounds, cyan/magenta accents, and Windows 95-style UI elements.
- **Album Service: Matrix Theme Enhancements**: Added a CSS-only "Digital Rain" effect with multiple layers of falling characters (Japanese, symbols, and binary) optimized for 4K screens.
- **Frontend Theme Plugin Mechanism**: Implemented dynamic loading of `{theme}.js` in `album_template.html`, allowing each theme to have independent interactive logic.
- **Album Service: Y2K Plugin**: Created `album-y2k.js` featuring retro alerts, user greeting prompts, and a Windows 95-style music player.
- **Background Music Integration**: Integrated YouTube IFrame API into theme plugins to support hidden background music playback.
- **Admin Settings**: Added 'album-y2k' to the theme selection dropdown in the album admin panel.

### Changed
- **Git Configuration**: Updated `.gitignore` to explicitly allow tracking of all assets (GIF, JS, CSS) within the `album/static/` directory.
- **Asset Localization**: Downloaded and localized the classic "Under Construction" GIF to ensure stability and avoid broken external links.
- **Album Generator**: Updated `make_album.php` output to include the new theme plugin loader.
## [2026-02-11]
### Changed
- **Album Service: Removed Win11 Theme**: Completely removed the `album-win11` theme from the project, including CSS styles, JavaScript plugins, and related assets.
- **Frontend Configuration**: Restored the default theme in `config.js` to the standard `album` theme.
- **Code Cleanup**: Cleaned up residual Win11-related tags and CSS classes within `BlazorAlbumExplorer`.
- **Environment**: Removed temporary file `(backup)config.php` and test screenshots (`qa1.png`, `qa3.png`) from the project root.
- **Blazor Fix**: Cleaned up deeply nested directories in `BlazorAlbumExplorer` caused by recursive publishing and updated `.gitignore` with .NET ignore patterns.
- **Path Optimization**: Fixed resource loading paths for Blazor Explorer in Apache subdirectory environments (changed `../../../` to `../../`), resolving issues with missing images and JSON data.
- **Album Service: Win11 Theme (Native Blazor Migration)**: Moved beyond CSS simulation to full integration of the compiled Blazor WASM engine.
    - **Native Integration**: Published the Blazor project to the `dist/` folder and used `theme.js` for automatic full-page replacement.
    - **Built-in Watermark**: Implemented the "Activate Experimental Project" watermark directly in Razor Layout, explicitly mentioning the Blazor WASM technology.
    - **Path Resolution**: Fixed internal path depths to ensure consistent resource loading for JSON and images within the subdirectory structure.
- **Global Download Manager (`DownloadManager`)**: Implemented a downloader with concurrency control in `album.js`, restricting simultaneous downloads to a maximum of 3.
- **Album Service: Theme Selector (`ThemeSelect.html`)**: Created a dedicated theme preview page to dynamically showcase all available album styles.
- **Theme List API**: Implemented `api/api_themes.php` to automatically scan theme directories and return a JSON list.
- **Environment Update**: Updated version to `v2026.02.11.23.10`.
