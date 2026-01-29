# Vibe Coding History

紀錄本專案透過 Gemini CLI 進行 Vibe Coding 的開發歷程與關鍵指令。

---

## 2026-01-30

### 1. 專案初始化與分析
- **任務**: 掃描專案結構，理解現有的 `make_html.php` (靜態生成) 與 `blog.html` (SPA) 混合架構。
- **Prompt**:
    - "掃描這個專案開發目錄下 *.js *.css *.php *.py 等相關開發檔..."
    - "migrate_full.php 希望資料庫連線的設定可以改為從 config.php 讀取"

### 2. 版本控制設定 (Git Setup)
- **任務**: 建立完善的 `.gitignore`，隔離機敏設定 (`config.php`)、生成檔 (`*.html`, `*.min.*`) 與大型資源 (`pic/`)。
- **Prompt**:
    - "建立一個git忽略設定檔...首先先移除內有機敏資料的程式上傳"
    - "幫我再加入根目錄內除了blog.html樣板檔外其他所有的生成檔"
    - "preview目錄底下所有圖檔也排除"
    - "category目錄下除了readme.txt外,其他都排除" (保留結構說明)

### 3. 文件化 (Documentation)
- **任務**: 為各個關鍵目錄 (`contents`, `category`, `static`, `preview`) 建立 `readme.txt`，並更新 `ARCHITECTURE.md` 與根目錄 `readme.txt`。
- **Prompt**:
    - "在contents目錄中把這目錄的用途說明寫到裡面的readme.txt檔內..."
    - "依照現在目錄架構現況以及排除後的內容,更新一下 ARCHITECTURE.md"
    - "幫我為這個專案做一個簡單介紹..."

### 4. 資料庫 API 開發
- **任務**: 建立 `api_dbsqlbase.php`，將原本基於檔案系統 (`api_filebase.php`) 的邏輯改寫為基於 MySQL 資料庫，同時保持前端 API 格式相容。
- **Prompt**:
    - "幫我建立api_filebase.php的資料庫讀取版本改寫, api_dbsqlbase.php 用來負責db版本api"

### 5. 建置工具優化
- **任務**: 修改 `mini.py`，排除 `config.example.js` 不進行壓縮。
- **Prompt**:
    - "幫我改寫mini.py , config.example.js不需要壓縮"

### 6. 後台管理系統 (Admin Dashboard)
- **任務**: 建立完整的後台管理介面 (`admin/`)，包含登入驗證、文章 CRUD、分類管理與系統儀表板。
- **技術細節**:
    - 使用 `admin/auth.php` 進行 Session 驗證與 PDO 資料庫連線。
    - 下載並整合 Bootstrap 5 至 `admin/assets/` 以避免外部依賴。
    - 統一 Sidebar 版型設計。
- **Prompt**:
    - "需要建立後台管理機制...幫我修改 config.php 加上管理者帳號..."
    - "幫我建立admin目錄...建立一個登入驗證介面"
    - "幫我建立文章管理 ,可以貼新文章,可以修改舊文,可以刪除舊文..."
    - "後台管理如果有引用到第三方套件相關的東西,希望一起打包放入admin底下目錄..."
    - "移表板可以顯示php版本資訊.資料庫連線方式相關資訊...db佔用大小..."
    - "繼續新增分類管理功能..."
