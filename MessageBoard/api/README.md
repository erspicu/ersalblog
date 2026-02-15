# Local API (SQLite)

此目錄負責處理本地儲存模式下的資料讀寫請求。

## 主要功能
- `message.php`: 核心 API 進入點。根據 `site_id` 與 `page_id` 動態路由至正確的資料庫檔案。
- `db_init.php`: 提供手動初始化或檢查資料庫結構的工具。
