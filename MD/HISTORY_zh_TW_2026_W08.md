# 開發歷史紀錄 (2026 第 08 週)

本週核心重點：全面強化子系統安全性、實作 Session 隔離機制、以及全站 PHP 5.x 相容性校準。

## 重大變更 (Major Changes)

### 1. 全域子系統安全強化
- **Session 隔離機制**：為部落格 (`BLOG_ADMIN_SESS`)、相簿 (`ALBUM_ADMIN_SESS`) 與留言板 (`MB_ADMIN_SESS`) 配置獨立的 Session 名稱。解決了以往登出其中一個子系統會導致全站同時登出的干擾問題。
- **認證引擎同步**：留言板後台已完整整合部落格主系統的「Bcrypt + 主機特徵碼」雜湊機制，並實作 IP 登入錯誤鎖定功能（Rate Limiting）。
- **相簿後台補強**：為相簿登入介面補上暴力破解防護，與主系統安全等級看齊。

### 2. PHP 5.x 深度相容性優化
- **語法降級**：全面掃描全站 PHP 代碼，將所有 PHP 7+ 專屬的「空接點運算子 (`??`)」替換為 `isset() ? :` 模式。
- **穩定性校驗**：執行 `php -l` 深度掃描，確保 `album/` 與 `MessageBoard/` 目錄下所有檔案均能於 PHP 5.4+ 環境穩定執行。
- **陣列語法標準化**：將關鍵路徑下的 `[]` 陣列宣告改回 `array()` 以求最高穩定性。

### 3. 資源在地化 (Asset Localization)
- **留言板資源脫鉤**：成功抓取 Bootstrap CSS/JS 與 Bootstrap Icons 到 `MessageBoard/admin/assets/`。現在留言板後台管理介面已完全脫離對外部 CDN 的依賴，具備完全獨立運行的能力。

## 技術優化 (Technical Optimizations)
- **重構設定生成邏輯**：修正了 `setup.php` 使用 Regex 替換 `config.php` 導致雜湊值損壞的問題，改為直接生成乾淨的 PHP 檔案內容。
- **標準化 auth.php 載入鏈**：規範所有行政頁面先配置 Session 參數後再啟動 `session_start()`，解決了 "Session settings cannot be changed when active" 的警告。

## 版本資訊
- **Version**: v2026.02.16.20.30
- **CLI**: 0.28.2
- **Model**: gemini-3-pro-preview
