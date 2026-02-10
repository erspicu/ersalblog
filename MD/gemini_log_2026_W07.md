# Gemini Log 2026_W07

- [2026-02-09 21:23:50] 幫我的相簿生成一個y2k風格的css,盡量模仿那時代還沒現代那麼精緻的css特效感
  Generated `album/static/album-y2k.css` with neon green/black theme, scanline effects, and distinct retro borders.
  Updated `album/config.example.js` to list the new theme.
  Created `album/config.js` to enable 'album-y2k' theme by default.
- [2026-02-09 21:26:20] 照片預覽不要綠色濾淨 但保留掃描縣特效
  Removed green grayscale/sepia filter from `.card-img` in `album-y2k.css`. Kept the scanline overlay and added a subtle brightness boost on hover.
- [2026-02-09 21:28:54] 後台設定 相簿主題 加上y2k選擇
  Added 'album-y2k' option to the theme selection dropdown in `album/admin/settings.php`.
- [2026-02-09 21:31:18] 駭客任務風格 背景加上 CSS-only Digital Rain 特效
  Added animated vertical text (0/1 and Katakana) using `::before` and `::after` on `body` in `album-matrix.css`.
- [2026-02-09 21:32:56] 修正 Matrix 數位雨特效不顯示問題
  Adjusted `z-index` to 100 and used `text-shadow` to clone multiple columns of falling text to ensure visibility over black backgrounds.
- [2026-02-09 21:33:43] 再次修正 Matrix 數位雨特效 (移動至 html 標籤並增加不透明度)
- [2026-02-09 21:36:43] 修正 Matrix 數位雨分布 (均勻分布至全螢幕寬度)
- [2026-02-09 21:38:31] 大幅強化 Matrix 數位雨 (增加字元多樣性、密度與雙層掉落特效)
- [2026-02-09 21:40:28] 擴增 Matrix 數位雨覆蓋範圍至 4K 螢幕
  Increased `text-shadow` offsets to ±2000px range to ensure full coverage on ultra-wide and 4K displays.
- [2026-02-09 21:41:49] 終極優化 Matrix 數位雨 (針對 4K/超寬螢幕滿版覆蓋)
  Reset anchor to `left: 0` and extended `text-shadow` range to 4500px to avoid gaps on the left side.
- [2026-02-09 21:43:33] 豪雨級 Matrix 數位雨升級 (三層特效 + 全量特殊符號庫)
  Added 3 layers of rain (`html::before`, `html::after`, `body::before`) with different speeds and font sizes.
  Integrated mathematical, Greek, and technical symbols (☢, ☣, Δ, Σ, █) into the character pool.
- [2026-02-09 21:46:46] 調整 y2k 風格 (去駭客化，改為土氣的早期網頁風格)
  Redesigned `album/static/album-y2k.css` to mimic 'clumsy' Y2K/GeoCities aesthetics.
  Features: Deep blue background, cyan/magenta accents, Verdana font, Windows 95 inset/outset borders, and dashed lines.
- [2026-02-09 21:48:50] 進階粗糙化 y2k 風格 (加入閃爍特效與背景圖磚)
  Added a tacky star-field SVG background tile and a 'blink' animation to the site title to enhance the retro/GeoCities feel.
- [2026-02-09 21:50:23] y2k 風格終極弄爛 (去圓角、跑馬燈、螢光撞色)
  Removed all `border-radius` using `!important`. Added a fake marquee animation to `.site-title`.
  Redesigned `album-header-box` with neon green background and magenta dashed borders.
- [2026-02-09 22:16:38] 建立前端主題插件機制與 y2k 實驗插件
  Modified `album_template.html` to dynamically load `{theme}.js`. Created `album-y2k.js` with retro effects: alert, prompt greeting, and a tacky music player interface.
- [2026-02-09 22:17:47] 執行重建 (php make_album.php)
  Regenerated album files and updated index.html with the new theme JS loader.
- [2026-02-09 22:19:24] 修正 album-y2k.js 語法錯誤 (換行符號問題)
- [2026-02-09 22:21:27] 升級 y2k 背景音樂為 YouTube 隱藏播放器模式
  Integrated YouTube IFrame Player API into `album-y2k.js`. The player is hidden (`0x0`) and controlled via custom retro UI buttons.
- [2026-02-09 22:22:06] 更新 y2k 背景音樂 YouTube ID (ax-efEg60yE)
- [2026-02-09 22:25:32] 增加 y2k 土味貼圖亂灑功能 (GeoCities 風格 GIF)
  Added `scatterTackyStickers()` to `album-y2k.js` which randomly places retro GIFs (flames, skulls, construction signs) on the screen edges.
- [2026-02-09 22:29:52] 修正 y2k 貼圖失效問題 (替換為穩定 GitHub 連結)
  Replaced broken Wayback Machine GIF links with stable direct URLs from GitHub repositories.
- [2026-02-09 22:32:04] 下載並本地化 y2k 貼圖素材
  Downloaded classic GIFs (construction, fire, welcome, skull, hot) to `album/static/album-y2k/` and updated `album-y2k.js` to use local paths.
- [2026-02-09 22:38:13] 簡化 y2k 特效：僅保留施工中貼圖並確保下載成功
  Confirmed successful download of `construction.gif` (6.1K). Updated `album-y2k.js` to remove other stickers and exclusively use the local construction icon.
- [2026-02-09 22:41:12] 修改 .gitignore 以包含 album/static/ 素材
  Updated .gitignore to explicitly include all assets (GIF, JPG, PNG, JS, CSS) within the `album/static/` directory.
- [2026-02-09 22:49:36] 實作網址 Hash 風格覆蓋功能 (#style=...)
  Modified `album_template.html` to prioritize the `style` parameter from URL hash for CSS and JS plugin loading. Regenerated `album.html`.
- [2026-02-09 23:34:26] 修正 mbstring 擴充功能相容性問題 (Fatal error: Call to undefined function mb_internal_encoding)
  Added `function_exists()` checks for mbstring functions and provided `safe_mb_convert` fallback in `api_album.php` and `make_album.php`.
- [2026-02-10 00:25:02] 同步相簿後台 Assets 並解除對 Blog 的依賴
  Copied Bootstrap CSS/JS, SweetAlert2, and album_selector.js to `album/admin/assets/`. Updated all admin PHP files to use local asset paths.
- [2026-02-10 00:29:06] 升級 Matrix 主題：改用 Canvas 實作數位雨
  Created `album-matrix.js` for high-performance Canvas rendering of the digital rain effect. Cleaned up obsolete CSS animations in `album-matrix.css`.
- [2026-02-10 00:34:19] 新增 Windows 3.1 復古主題 (album-win31)
  Created `album-win31.css` (Teal background, 3D borders) and `album-win31.js` (Startup sound, Draggable windows). Added option to Admin Settings.
- [2026-02-10 00:39:48] 優化 Win3.1 主題：修正麵包屑白線與強化視窗結構
  Fixed breadcrumb layout and refined `.main-content` to look like a true Windows 3.1 MDI window with grey borders and white workspace.
- [2026-02-10 00:41:30] 修正 Win3.1 主題視覺異常 (白色長方形)
  Removed universal min-height from `#album-header-section` and added `:empty { display: none; }` to prevent ghost white boxes on the home page.
- [2026-02-10 00:42:37] 徹底移除 Win3.1 主題圓角與優化對話框
  Added global `* { border-radius: 0 !important; }` and refined modal styling to match Windows 3.1 dialog specifications.
- [2026-02-10 00:44:54] 調整 Win3.1 照片/相簿排列為靠左對齊
  Changed `justify-content: center` to `flex-start` in `.album-grid` to match Windows 3.1 file manager behavior.
- [2026-02-10 00:48:03] 重構相簿主題架構 (Encapsulated Themes)
  Moved theme CSS/JS/Assets from `static/` to `static/themes/{theme_name}/`. Standardized naming to `theme.css` and `theme.js`. Updated loader in `album_template.html` and fixed asset paths.
- [2026-02-10 00:54:08] 新增 MS-DOS 復古主題 (album-dos)
  Created `theme.css` (Blue background, Monospace) and `theme.js` (BIOS POST simulation). Added option to Admin Settings.
- [2026-02-10 00:56:30] 修正 Win3.1 與 DOS 主題按鈕文字對齊問題
  Changed `.btn` to `display: inline-flex` with centered alignment in both themes to fix inconsistent layout between 'Download' and 'Share' buttons.
- [2026-02-10 00:57:50] 修正 Win3.1 與 DOS 主題分享視窗框線跑掉問題
  Added specific styles for `.share-item` and `.input-group` in both themes to ensure input boxes and buttons align correctly within the retro dialog boxes.
- [2026-02-10 01:00:16] 修正 DOS 主題 Modal Header 渲染問題
  Simplified ASCII decorations in `.modal-header` to prevent layout breaking. Changed to a side-bracket style (▐ ▌) for better compatibility with title text.
- [2026-02-10 01:03:21] 調整 DOS 主題相簿後綴為 <DIR>
  Changed `.ALB` to ` <DIR>` in `theme.css` to better mimic MS-DOS command line directory listings.
- [2026-02-10 01:07:13] 新增「藝術大師」主題 (album-art)
  Created `theme.css` (Gallery style, Cinzel/Playfair fonts, Golden borders) and `theme.js` (Staggered fade-in, Curator notes). Added to Admin Settings.
- [2026-02-10 01:11:08] 修正 DOS 主題分頁格式問題
  Redesigned `.pagination` to look like a classic TUI function key bar. Improved button spacing and added highlighted yellow state for the active page.
- [2026-02-10 01:14:19] 新增 GameBoy 掌機復古主題 (album-gameboy)
  Created `theme.css` (Classic 4-shade green, pixel font) and `theme.js` (Startup 'Ding' sound, falling logo animation). Added to Admin Settings.
- [2026-02-10 01:18:42] 透過 CSS 強制優化照片詳情頁佈局 (不需重建)
  Used `!important` to override hardcoded inline grid styles in the template. Increased map area and enforced button centering across all themes.
- [2026-02-10 01:20:50] 執行相簿完整重建以驗證佈局穩定性
  Ran `php make_album.php` to ensure core CSS optimizations (map layout, button centering) work correctly across all generated pages.
- [2026-02-10 01:22:56] 優化 GameBoy 主題佈局 (防止地圖壓縮)
  Increased `.main-content` max-width to 1200px and optimized grid column logic for the photo detail view in `album-gameboy/theme.css`.
- [2026-02-10 01:24:23] 調整 GameBoy 主題字體大小 (提升閱讀性)
  Increased base font-size to 14px and adjusted sizes for titles, descriptions, and buttons in `album-gameboy/theme.css`. Also enlarged grid items to accommodate larger text.
- [2026-02-10 01:30:57] y2k 主題新增模擬 IE 指令碼錯誤對話框
  Added `showIEScriptError()` to `theme.js` which triggers a classic 'An error has occurred in the script on this page' dialog 5 seconds after load.
- [2026-02-10 01:37:44] 新增 Windows 95 經典主題 (album-win95)
  Created `theme.css` (Classic Teal/Silver UI) and `theme.js` (Taskbar, Start Button, Startup Sound). Added BSOD easter egg.
- [2026-02-10 01:40:56] 升級 Win95 主題：實作「內容 (Properties)」屬性對話框
  Hidden default meta panel and implemented a tabbed 'Properties' dialog in `theme.js`. Added 'General' and 'Details' tabs to display photo/EXIF info separately.
- [2026-02-10 01:42:18] 加寬 Win95 屬性對話框以容納地圖
  Increased `props-dialog` width to 800px in `theme.js` to ensure Google Maps and EXIF data display correctly.
- [2026-02-10 01:44:19] 啟用 Win95 Help 選單：實作 About 對話框
  Made the 'Help' menu item functional. Clicking it triggers `showAboutDialog()` which displays site version and description in a retro Win95 style.
- [2026-02-10 01:52:06] 新增 Netscape Navigator 經典瀏覽器主題 (album-netscape)
  Created `theme.css` (Grey chrome, beveled toolbars) and `theme.js` (Toolbar buttons, Location bar, Pulsing 'N' logo loader). Added to Admin Settings.
- [2026-02-10 01:53:06] 恢復 Netscape 主題網站標題與描述
  Enabled `.header-bar` visibility and applied early 90s web styling (centered, Times New Roman, navy blue) within the simulated browser viewport.
- [2026-02-10 01:57:24] 新增 Terminal / ASCII 字符畫主題 (album-terminal)
  Implemented a real-time image-to-ASCII converter in `theme.js`. Created a monochromatic green terminal UI with CRT effects.
- [2026-02-10 02:00:26] 修正 Terminal 主題 ASCII 預覽不顯示問題
  Fixed logical errors in `convertToAscii` loop and updated CSS to ensure `.card-img` dimensions are available for Canvas processing. Added smooth fade-in for ASCII blocks.
- [2026-02-10 02:02:18] 新增 Synthwave 迷幻電子主題 (album-vapor)
  Created `theme.css` (Neon grid, glowing text, VHS scanlines) and `theme.js` (Lo-fi music, glitch effects). Added to Admin Settings.
- [2026-02-10 22:43:59] 技術評估：決定採用 Blazor WASM 實驗 WinForms 風格相簿介面。已安裝 .NET 8.0 SDK 並建立 BlazorAlbumExplorer 專案。
- [2026-02-10 22:46:33] Blazor WASM 開發進度：實作 WinForms/WPF 風格 UI (Desktop + File Explorer)。已完成資料對接邏輯，並成功發佈至 dist 目錄。
