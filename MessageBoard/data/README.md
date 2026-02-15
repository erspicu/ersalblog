# Local Data Storage

此目錄用於存放本地模式產生的 SQLite 資料庫檔案。

## 目錄結構
系統會根據 `site_id` 自動建立子目錄，並在其中根據 `page_id` 建立資料庫檔案：
`data/[Site_Name]/[Page_Name].sqlite3`

**注意：此目錄已在 .gitignore 中排除，不應上傳資料。**
