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
    - **狀態持久化**：利用 `localStorage` 實作登入狀態記憶，頁面重整後自動恢復身份。
    - **安全驗證**：實作前端 JWT 解析與後端 **Token 驗證 (tokeninfo)**，強制校準身分資料防止竄改。
    - **頭像儲存與顯示**：更新 SQLite 與 GAS 資料架構，支援儲存頭像網址與 Google 唯一識別碼 (`google_sub`)。
- **後台管理強化**：
    - **設定介面整合**：在留言板後台新增 Google Auth 與 GAS Web App URL 的配置介面。
    - **模式切換修正**：實作 `auth.php` 與 `login.php` 的自動同步邏輯，確保修改 `config.js` 模式後後台立即反應。

### 2. 全域子系統安全強化
- **Session 隔離機制**：為部落格、相簿與留言板配置獨立的 Session 名稱，避免跨子系統登出干擾。
- **認證引擎同步**：留言板後台已完整整合部落格主系統的加密機制。

### 3. PHP 5.x 深度相容性優化
- **語法降級**：全面替換 PHP 7+ 專屬運算子，確保在舊版 AppServ 環境下穩定執行。
- **舊版相容性**: 實作 `config.php` 自動結構補全功能。

### 4. AI 輔助創作整合
- **設定頁面重構**: 全面導入 **AJAX** 更新機制、**SweetAlert2** UI 與完整 **i18n** 多語系支援。

## 技術優化 (Technical Optimizations)
- **GAS 自動升級**: GAS 腳本現在具備自動偵測並補齊試算表欄位的功能。
- **日誌優化**: AI API 呼叫日誌改寫入根目錄 `debug.txt` 並遮罩 API Key。

## 版本資訊
- **Version**: v2026.02.16.22.13
- **CLI**: 0.28.2
- **Model**: gemini-3-flash-preview
