# 開發歷史紀錄 (2026 第 08 週)

本週核心重點：全面強化子系統安全性、實作 Session 隔離機制、以及全站 PHP 5.x 相容性校準。

## 重大變更 (Major Changes)

### 1. 全域子系統安全強化
- **Session 隔離機制**：為部落格 (`BLOG_ADMIN_SESS`)、相簿 (`ALBUM_ADMIN_SESS`) 與留言板 (`MB_ADMIN_SESS`) 配置獨立的 Session 名稱。解決了以往登出其中一個子系統會導致全站同時登出的干擾問題。
- **認證引擎同步**：留言板後台已完整整合部落格主系統的「Bcrypt + 主機特徵碼」雜湊機制，並實作 IP 登入錯誤鎖定功能（Rate Limiting）。

### 2. PHP 5.x 深度相容性優化
- **語法降級**：全面掃描全站 PHP 代碼，將所有 PHP 7+ 專屬的「空接點運算子 (`??`)」替換為 `isset() ? :` 模式。
- **陣列語法標準化**：將關鍵路徑下的 `[]` 陣列宣告改回 `array()` 以求最高穩定性。

### 3. AI 輔助創作整合 (AI Assistant Integration)
- **API 整合**: 新增 `admin/api_ai_helper.php`，整合 Google Gemini API (v1beta)。
- **設定頁面重構**: 
    - 全面導入 **AJAX** 更新機制與 **SweetAlert2** UI，提供流暢的儲存體驗。
    - **舊版相容性**: 實作 `config.php` 自動結構補全功能，支援將 AI 設定自動寫入舊版設定檔。
- **全站多語系化 (i18n)**: 
    - 完成設定頁面所有 AJAX 動作、彈窗文字與 AI 模型抓取流程的多語系支援。
    - 補齊文章編輯器中 AI 輔助視窗 (AI Assistant Modal) 的所有硬編碼字串翻譯。

### 4. 系統優化與清理
- **資源在地化**: 留言板後台管理介面已 100% 脫離外部 CDN。
- **文件清理**: 移除過時的 `BLOG AI.md` 規劃文件。

## 技術優化 (Technical Optimizations)
- **重構設定生成邏輯**：修正了 `setup.php` 雜湊值損壞問題。
- **標準化 auth.php 載入鏈**：規範 Session 配置與啟動順序。
- **日誌優化**: AI API 呼叫日誌改寫入根目錄 `debug.txt` 並遮罩 API Key。

## 版本資訊
- **Version**: v2026.02.16.20.35
- **CLI**: 0.28.2
- **Model**: gemini-3-flash-preview
