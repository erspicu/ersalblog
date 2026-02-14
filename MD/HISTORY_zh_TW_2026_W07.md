# BaxerMux Blog & Album 歷史紀錄 (2026_W07)

本週開發重點在於**相簿服務邏輯重構與 Web 後台整合**、**語系規範標準化**以及 **Blog SEO 功能強化**。

## 🌟 重大更新 (Highlights)

### 1. 相簿服務架構重構與 Web 整合
- **核心類別化**: 建立 `AlbumGenerator.php` 封裝所有掃描、縮圖生成與索引邏輯，實現 CLI 與 Web 共用。
- **維護系統 (Maintenance)**: 實作視覺化維護頁面，支援全站重建、增量更新與即時進度輪詢面板。
- **智慧引擎回退**: 實作 Imagick 與 GD 的自動切換機制，並提供詳盡的系統診斷功能。

### 2. 語系處理規範標準化
- **規範統一**: 制定「檔案底線 (`zh_TW`)、網頁連字號 (`zh-TW`)」標準。
- **自動轉換**: 在 `auth.php` 與 `lang_init.php` 實作自動處理邏輯，修復 W3C 代碼與開發命名之衝突。
- **多語系補齊**: 完成後台 JS 語系包隔離，補齊中英文診斷與維護文字。

### 3. Blog 編輯器與 SEO 強化
- **動態相簿挑選器**: 升級 `album_selector.js`，支援自動偵測縮圖規格與解析度資訊顯示。
- **SEO 預覽圖上傳**: 實作文章層級的 OG Image 上傳與轉製 (1200x630)。
- **Favicon 自定義**: 實作網站圖示路徑的變數化與後台設定功能。

### 4. 資訊架構 (IA) 研究
- **研討紀錄**: 建立並擴充 `MD/STUDY_IA_CATEGORIES_TAGS.md`，深度解析「分類」與「標籤」的設計差異，並新增 **Flickr 的管理哲學**（三層式結構、群組貢獻、CC 授權管理）作為專業攝影系統的開發參考。

---

## 🛠 詳細變更日誌 (Detailed Changes)

### 相簿服務 (Album Service)
- [New] 建立 `album/PHP_LIB/AlbumGenerator.php` 共用核心類別。
- [New] 建立 `album/admin/maintenance.php` 提供視覺化重建介面。
- [New] 實作任務 ID 進度隔離與原子化進度寫入機制。
- [Fix] 解決長任務導致的 Session 阻塞與進度輪詢卡死問題。
- [Optimized] 管理後台列表改用 `XS` 規格縮圖，顯著提升載入速度。
- [Tool] 建立 `album/toolshell/` 跨平台 (sh, ps1, bat) 管理腳本包。

### Blog 主系統
- [New] 文章編輯器新增 SEO 預覽圖 (OG Image) 上傳欄位。
- [New] 網站系統設定新增 Favicon 路徑配置。
- [Fix] 修正 `admin/login.php` 因方法誤植導致的 500 錯誤。
- [Fix] 修正 `langs/template-zh_TW.php` 中 `html_lang` 標籤錯誤 (zh-Hant -> zh-TW)。
- [SSG] 執行強迫完整重建，同步全站語系與技術更新。

### 語系與技術 (i18n & Tech)
- [Standard] 全站後台 PHP 頁面 HTML `lang` 屬性動態化。
- [i18n] 建立 `album/langs/admin-*.js` 前端專屬語系包。
- [AOT] 開啟 Win11 主題 Blazor AOT 編譯優化與產出物瘦身。

---
**版本控制**: v2026.02.14.15.27 (UTC+8)
**開發工具**: Gemini CLI
- 遷移文章 (20250131-20250131235411.html) 圖片素材至相簿服務 (album/Collection/Panasonic_LUMIX_S9_Unboxing)。
- Migrated remaining images from pic/20250205-1/ for post 20250131-20250131235411.html.
- Removed generator.log writing and health check in album service.
- Synchronized shorturl.txt when an album is deleted or renamed in album service.
- Updated album upload: automatically trigger album rebuild to update shorturls and generate thumbnails after upload.
- Fixed deleted albums still appearing on home page: added directory validation in generator and trigger index update on deletion.
- Added feature to change admin account and password in both Blog and Album admin settings.
- Implemented security hardening: system fingerprint hashing, forced initial password change, and localhost bypass (1234).
- Decoupled Album service from Blog core: created local system_helper.php and updated references for independent operation.
- Removed unused session_secret from Blog and Album configurations and install process.
- 實作管理者帳號密碼雜湊加密，結合主機特徵碼 (Machine ID) 加固憑證安全。
- 支援主機環境感知：提供 localhost (1234) 通行證並強制預設密碼安全性初始化。
- 徹底解耦相簿服務與部落格核心，支援相簿服務獨立運行佈署。
- 升級相簿照片上傳機制：AJAX 多檔逐一上傳、進度條顯示、上傳後自動觸發資料同步。
- 優化 StaticGenerator 資源映射，自動校正子目錄網頁的 album/ 與 pic/ 路徑。
- 移除全站未使用的 session_secret 設定與相關安裝欄位。
- 修正相簿刪除後首頁 index.json 未同步更新導致殘留連結的問題。
- Optimized and ran mini.py: expanded ignore list and fixed subprocess calls for better performance in WSL2.
