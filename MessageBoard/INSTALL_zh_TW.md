# MessageBoard 留言板服務安裝指南

本指南將引導您完成 MessageBoard 的配置，包括本地 SQLite 模式與雲端 Google Apps Script (GAS) 模式的設定。

---

## 1. 快速開始 (本地 SQLite 模式)

如果您打算將留言存在自己的伺服器上，請執行以下步驟：

1.  **檢查權限**：確保 `MessageBoard/data/` 目錄具備寫入權限 (777)。
2.  **設定管理員**：修改 `MessageBoard/config/config.php` 設定後台登入的帳號與密碼。
3.  **配置插件**：編輯 `MessageBoard/config/config.js`：
    *   將 `mode` 設為 `'local'`。
4.  **完成**：系統會在有人第一次留言時自動建立資料庫檔案。

---

## 2. 雲端部署 (Google Apps Script 模式)

如果您希望在無後端環境（如 GitHub Pages）使用，或是想將資料存於 Google 試算表：

### 第一步：建立 GAS 腳本
1.  前往 [Google Apps Script 儀表板](https://script.google.com/home)。
2.  點擊「新專案」，將專案命名為 `MB_Backend`。
3.  將本專案 `MessageBoard/gas/Code.gs` 的內容完整貼上並儲存。

### 第二步：部署為網頁應用程式
1.  點擊右上角「**部署**」 > 「**新部署**」。
2.  選取類型為「**網頁應用程式**」。
3.  設定如下：
    *   **執行身分**：選取「**我**」(您的 Google 帳號)。
    *   **誰可以存取**：選取「**所有人**」(Anyone)。
4.  點擊部署，並在彈出的視窗中完成 Google 帳號授權（點擊進階 > 前往專案 > 允許）。
5.  **複製產生的「網頁應用程式網址」** (URL)。

### 第三步：更新設定
1.  編輯 `MessageBoard/config/config.js`：
    *   將 `mode` 設為 `'gas'`。
    *   將剛才複製的網址填入 `gas_url`。

---

## 3. 整合至部落格 (Blog Integration)

MessageBoard 已完美整合至本 Blog 系統：

1.  開啟 Blog 根目錄的 `config.js`。
2.  設定插件路徑：`guestbook_plugin: 'MessageBoard/static/guestbook.js'`。
3.  設定每頁筆數：`guestbook_per_page: 5`。
4.  執行部落格重建：在根目錄執行 `php make_html.php -f`。

---

## 4. 管理後台使用

訪問 `你的網址/MessageBoard/admin/`：

*   **登入**：輸入 `config.php` 中設定的帳密。
*   **模式選擇**：登入時可選擇要管理 SQLite 還是 GAS 資料。
*   **留言管理**：
    *   支援按「站點」與「頁面」篩選留言。
    *   **刪除功能**：在 SQLite 模式下可直接刪除；GAS 模式下建議直接開啟 Google 試算表編輯，或透過後台介面遠端刪除。
*   **系統設定**：可在後台直接調整外掛語系、主題（明亮/深色）與分頁筆數。

---

## 5. 常見問題排除 (Troubleshooting)

*   **看到 "Unexpected token '<'" 錯誤**：通常是因為 API 路徑錯誤回傳了 404 HTML。請檢查 `config.js` 中的 `api_url` 或 `gas_url` 是否正確。
*   **修改設定後沒反應**：瀏覽器快取了舊的 JS 檔。本系統已內建時間戳機制，若仍無效請嘗試 `Ctrl + F5` 強制重新整理。
*   **GAS 模式無法載入**：請確認 GAS 部署時是否選取了「所有人 (Anyone)」，並確保已在 GAS 編輯器中完成了權限授權。
