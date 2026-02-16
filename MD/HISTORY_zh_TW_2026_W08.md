# 開發歷史紀錄 (2026 第 08 週)

本週核心重點：全面強化子系統安全性、實作 Session 隔離機制、以及全站 PHP 5.x 相容性校準。

## 重大變更 (Major Changes)

### 1. 留言板服務 Widget 化與 Google 登入整合
- **Widget 架構重構**：將留言板由 DOM 注入模式轉型為 **iframe 獨立 Widget 模式**。
    - 建立 `messageboard.html` 作為獨立執行環境，實現 CSS/JS 隔離。
    - 實作啟動器 (`guestbook.js`) 動態生成 iframe，支援跨站部署並解決 Google 認證網域限制。
    - 透過 `postMessage` 與 `ResizeObserver` 實作 iframe 高度自動同步。
- **Google 第三方登入 (GIS)**：
    - 整合 Google Identity Services，支援 Google 帳號一鍵登入。
    - 實作前端 JWT 解析，獲取使用者姓名與大頭貼。
    - **頭像儲存與顯示**：更新 SQLite 資料庫架構與 PHP API，支援儲存留言者的頭像網址，並在留言列表中顯示。
- **配置化管理**：將 Google Client ID 與開關整合至 `MessageBoard/config/config.js`。

### 2. 全域子系統安全強化
- **Session 隔離機制**：為部落格、相簿與留言板配置獨立的 Session 名稱，避免跨子系統登出干擾。
- **認證引擎同步**：留言板後台已完整整合部落格主系統的加密機制。

### 3. PHP 5.x 深度相容性優化
- **語法降級**：全面替換 PHP 7+ 專屬運算子，確保在舊版 AppServ 環境下穩定執行。
- **舊版相容性**: 實作 `config.php` 自動結構補全功能。

### 4. AI 輔助創作整合
- **設定頁面重構**: 全面導入 **AJAX** 更新機制與 **SweetAlert2** UI。
- **全站多語系化 (i18n)**: 完成設定頁面與文章編輯器中所有 AI 相關 UI 的中英文語系整合。

## 技術優化 (Technical Optimizations)
- **文件清理**: 移除過時的 `BLOG AI.md` 規劃文件。
- **日誌優化**: AI API 呼叫日誌改寫入根目錄 `debug.txt` 並遮罩 API Key。

## 版本資訊
- **Version**: v2026.02.16.21.52
- **CLI**: 0.28.2
- **Model**: gemini-3-flash-preview
