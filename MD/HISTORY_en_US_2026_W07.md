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
