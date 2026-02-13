# 2026 W07 (2026-02-09 ~ 2026-02-15)

### Major Architecture Updates
- **Album Service Multi-language Architecture**:
  - Implemented a decoupled multi-language mechanism: PHP backend for static rendering, JS frontend for dynamic translation.
  - Created dedicated JS language packs (`zh_TW.js`, `en_US.js`) in `album/langs/`, auto-loaded via `<html lang>`.
  - Refactored `album.js` and `album_template.html` to remove all hardcoded HTML/text, achieving full UI/Logic separation.
  - Added multi-language comment support in `compression.json` (`comment-en_US`), enabling localized share menus.

### Performance & Optimization
- **Smart Thumbnail Generation**:
  - Updated `make_album.php`: Thumbnails are only generated if the original resolution exceeds the target spec. Redundant files are auto-cleaned.
  - Frontend `album.js` Smart Fallback: Automatically falls back to the original image if specific thumbnails are missing (due to small original size).
  - Enforced JSON Consistency: Fixed empty `sizes` fields to output as Object `{}` instead of Array `[]`, resolving parsing errors in strongly-typed languages.
- **Win11 Theme AOT Optimization**:
  - Enabled **AOT (Ahead-of-Time)** compilation for Blazor WASM, compiling C# directly to native WebAssembly.
  - Added automation script `album/rebuild_win11.sh` for one-click environment check, build, deployment, and cleanup.
  - Cleaned up publication artifacts (`.gz`, `.br`, `.pdb`), reducing size by 40% (28MB -> 17MB).
  - Significantly improved window dragging, resizing, and image processing performance.

### System Maintenance
- **Git Repository Slimming**:
  - Updated `.gitignore` and ran `git rm --cached` to stop tracking generated JSON caches and Win11 build artifacts (WASM/DLL), significantly reducing repo size.
  - Cleaned up Blazor project temporary directories (`bin/`, `obj/`, `publish/`), freeing up ~370MB disk space.
  - Removed the obsolete legacy release directory `album/view_blazor/`.

### Bug Fixes
- **Chinese Path Support**: Fixed Blazor `HttpClient` logic by adding `Uri.EscapeDataString` to correctly handle album paths with Chinese characters.
- **Dashboard**: Fixed an issue where the photo library size was displayed as unknown.
- **Initial Loading State**: Fixed `album.js` startup logic to correctly detect `document.readyState` during dynamic script loading.
