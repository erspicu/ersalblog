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
- **研討紀錄**: 建立 `MD/STUDY_IA_CATEGORIES_TAGS.md`，深入解析「分類」與「標籤」在 Blog 與相簿系統中的設計差異、平台特色及未來優化方向。

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
