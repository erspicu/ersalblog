# Vibe Coding 歷史紀錄

記錄本專案透過 Gemini CLI 進行 Vibe Coding 的開發歷程與原始 Prompt 指令。

---

## 2026-01-30 (繁體中文)

### [12:05] 初步專案掃描與分析
- **任務**: 了解專案架構 (混合 SSG + SPA)。
- **Prompt**: 
    > "Scan the development files *.js *.css *.php *.py in this project directory again (no need for the test directory), as well as blog.html, to understand the content. Modifications will follow."

### [12:15] 資料庫遷移工具優化
- **任務**: 將 `migrate_full.php` 的資料庫連線設定改為讀取中央設定檔 `config.php`。
- **Prompt**: 
    > "希望 migrate_full.php 裡面的資料庫連線設定能改成讀取 config.php 的設定。"

### [12:20] Git 版本控制初始化 (.gitignore)
- **任務**: 建立完善的過濾規則，排除敏感資訊、大型照片資源與自動生成的 HTML 檔案。
- **Prompts**: 
    - "稍後想用 git 管理，但在此之前，請 Gemini 幫我建立 git ignore 設定檔。先把包含敏感資料的程式排除上傳。"
    - "根目錄下除了 blog.html 樣板檔外，其他產生的檔案也都加入排除。"
    - "Google 驗證檔也移除。"
    - "預覽目錄下的所有圖檔排除。" (後更正為移除 preview 下的 .jpg .png)
    - "移除 pic 目錄。"
    - "category 目錄下除了 readme.md 外全部排除。"
    - "contents 目錄下除了 readme.md 外全部排除。"
    - "static 內的圖檔排除。"

### [12:30] 目錄用途說明文件化 (READMEs)
- **任務**: 為主要目錄建立雙語說明文件，並更新專案架構文件。
- **Prompts**: 
    - "幫我把 contents 目錄的用途寫入該目錄下的 readme.md 檔案，依據專案實作內容。先用英文表達，再用中文。"
    - "補充說明標籤可以用逗號 (`,`) 分隔。"
    - "是的，category 目錄也幫我一起建立。"
    - "分類說明可以用現有的目錄和檔案舉例。"
    - "static 內建立 readme.md，簡述用途。"
    - "preview 內也建立 readme.md 並說明用途。"
    - "根據目前的目錄結構和排除內容更新 ARCHITECTURE.md。"
    - "為本專案做一個簡單介紹，並在根目錄建立 readme.md。"

### [12:45] 儲存庫發佈 (GitHub)
- **任務**: 初始化 Git 並推送到 GitHub。
- **Prompt**: 
    > "我想把這專案上傳到我的 GitHub https://github.com/erspicu/ersalblog"

### [12:50] 致謝與開源庫聲明
- **任務**: 在 README 中註記使用的第三方 PHP 與 JS 函式庫，尊重原作者。
- **Prompt**: 
    > "我的 PHP_LIB 裡面有一些第三方的套件，還有 static 裡面有讀取 exif 的庫。把這些套件說明寫入根目錄 readme，尊重原版，然後 push 到 git。"

### [13:00] 資料庫 API 開發 (SQL-based)
- **任務**: 建立 `api_dbsqlbase.php` 以支援從 MySQL 讀取內容，同時保持與 SPA 格式的相容性。
- **Prompt**: 
    > "幫我改寫一個資料庫版本的 api_filebase.php。api_dbsqlbase.php 將會處理 db 版本的 api。"

### [13:10] 建置工具調整 (Python)
- **任務**: 優化壓縮腳本。
- **Prompts**: 
    - "執行 mini.py"
    - "改寫 mini.py，config.example.js 不需要壓縮。"

### [13:20] 後台管理介面建構
- **任務**: 建立包含登入、文章管理、分類管理的後台系統。
- **Prompts**: 
    - "需要建立後台管理機制。先從資料庫版本開始。首先幫我修改 config.php 加入後台帳號密碼設定，並在移除敏感資訊後更新到 config.example.php。"
    - "建立 admin 目錄存放後台相關 PHP 碼。先建立一個登入驗證介面，登入後可登出。功能之後再加。"
    - "建立文章管理功能：可發布新文章、編輯舊文章、刪除舊文章、編輯標籤與指定分類。"
    - "若後台管理有用到第三方元件，請將其 bundle 到 admin 目錄下，避免來源消失時遺失。"
    - "加入分類管理功能：可新增、移除或重新命名分類。"
    - "儀表板需顯示 PHP 版本、DB 連線資訊、DB 剩餘大小、DB 占用大小、總文章數等。"

### [13:45] UI/UX 打磨與版面統一
- **任務**: 統一後台為 Sidebar 佈局，優化文章列表顯示，並引入 SweetAlert2。
- **Prompts**: 
    - "登入後的後台版面有點怪。希望點擊文章或分類管理後，版面維持與登入頁面一致——左邊 Sidebar，右邊管理，像儀表板那樣。"
    - "文章管理的文章列表中，把描述與標題放在檔案 URL 下方。太長則換行。"
    - "編輯與刪除操作垂直排列且顏色不同。是故意的設計嗎？"
    - "沒關係，保留。但刪除的原生 JS alert 很醜。使用好看一點的提示視窗。若使用第三方元件，請下載使用。"

---

## 2026-01-31 (繁體中文)

### [12:05] PHP 7.x 相容性檢查與修復
- **任務**: 確保程式碼能在 PHP 7.x 環境執行。
- **Prompt**: "檢查是否有 PHP 檔案與 PHP 7 有相容性問題。若有，修復之。"
- **修復**: 將 `str_ends_with()` 替換為 `substr()`，將 `match` 表達式替換為陣列與 `??` 邏輯。

### [12:15] 儀表板資料庫資訊增強
- **任務**: 顯示詳細的 MySQL/MariaDB 版本與連線資訊。
- **Prompts**: 
    - "在後台儀表板，想在連線資訊中加入 mysql 版本。"
    - "希望能有 mysql 或 mariadb 等詳細資訊。"

### [12:30] 混合管理系統
- **任務**: 支援在登入時選擇「資料庫模式」或「檔案系統模式」，並提供統一介面。
- **Prompt**: "因為我的 blog 架構目前是檔案系統與資料庫模式並行，我希望使用者登入後可以選擇管理版本。若選擇非資料庫版本，介面基本相同，但內容從檔案系統管理。"
- **實作**: 建立 `admin/data_provider.php` 封裝 `DataManager` 類別，抽象化資料讀寫邏輯。

### [12:45] 日誌系統與開發規範優化
- **任務**: 修復日誌編碼問題並將開發規範寫入設定。
- **Prompts**: 
    - "gemini_log.md 打開時是亂碼。希望顯示正確的繁體中文。"
    - "我在 Win11；用記事本打開 UTF8 檔案仍是亂碼。修復它。"
    - "仍有問題。參考 gemini.md 記錄格式進行修復。"
    - "現在正常了。幫我把這個設定 (正確日誌記錄) 寫入 gemini.md。"
- **技術細節**: 強制使用 UTF-8 with BOM 編碼。

### [16:07] 登入健康檢查
- **任務**: 在登入前自動檢測 DB 連線與檔案目錄完整性。
- **Prompt**: "後台登入時，顯示 blog 資料庫環境/內容/連線是否正確，並檢查檔案結構 blog 所需資料檔/目錄是否存在。若否，顯示畫面提示並禁止登入不可用的系統。"
- **實作**: 建立 `admin/health_check.php` 進行即時狀態顯示並禁用無效模式。

### [16:14] 檔案轉資料庫匯入工具
- **任務**: 提供從檔案模式一鍵匯入資料到資料庫的功能。
- **Prompt**: "進入檔案模式後，想增加一個功能分類，用於將檔案內容匯入資料庫。參考 migrate_full.php 設計。"
- **實作**: 建立 `admin/tool_migrate.php` 並整合至後台側邊欄。

### [16:20] 資料庫結構正規化
- **任務**: 將分類欄位拆分為獨立資料表與關聯表。
- **Prompt**: "希望修正資料庫版本的分類架構。多建立兩個表：一個存分類名稱，一個存文章與分類的對應。修正受影響的程式與畫面；即分類管理應有新增功能。"
- **實作**: 建立 `blog_categories` 與 `blog_post_categories` 資料表，更新 `DataManager` 與分類管理 UI。

### [16:26] 冗餘欄位清理與邏輯重構
- **任務**: 移除 `blog_posts` 中的 `post_categories` 並重構查詢。
- **Prompt**: "看來 blog_posts 中的 post_categories 已不需要。我可以從 DB 刪除它嗎？程式需要對應修改嗎？"
- **實作**: 重構 `getAllPosts`、`getPost` 等，改用 `GROUP_CONCAT`。建立 `admin/db_drop_column.php` 進行安全刪除。

### [16:39] 文件與格式更新
- 整合 `reme.txt` 內容至 `README.md` 並更新第三方套件資訊。
- `gemini_log.txt` 轉換為 `gemini_log.md` 並強制使用 UTF-8 BOM 編碼。

---

## 2026-02-01 (繁體中文)

### [19:05] 自動化初始化系統 (資料庫初始化)
- **任務**: 建構自動化的 DB 資料表建立與資料遷移。
- **實作**: 
    - 建立 `admin/db_init.php` 支援從檔案系統一鍵遷移至 DB。
    - 修改 `admin/login.php` 為「連線設定但無資料表」狀態提供引導連結。
- **穩定性優化**: 
    - 解決 MySQL DDL 隱式提交導致的 "No active transaction" 錯誤。
    - 拆分 SQL 語句並增強 Transaction 狀態偵測，提升初始化成功率。

### [20:20] 檔案系統修復工具
- **任務**: 建構檔案系統結構修復與反向 DB 匯出。
- **實作**:
    - 建立 `admin/file_init.php`，與 DB 初始化對稱。
    - 支援從 MySQL 讀取內容，自動重建 `contents/` 索引與 `category/` 目錄結構。
    - 在登入頁整合檔案系統健康檢查，引導使用者進行修復。

### [00:05] 後台版本控管與國際化 (i18n)
- **任務**: 實作後台版本顯示與多語系支援 (繁中/英文)。
- **實作**:
    - **版本機制**: 初期嘗試掃描檔案時間，後改為靜態版本號 `admin/version_config.php` (vYYYY.MM.DD.HH.MM)。
    - **i18n 架構**: 建立 `langs/admin/` 目錄，以檔名區分語系 (`zh_TW.php`, `en_US.php`)。
    - **登入頁改版**: `admin/login.php` 加入語言下拉選單與版本顯示。

### [00:20] 全後台國際化
- **任務**: 將多語系支援擴展至整個後台系統。
- **實作**:
    - 建立 `admin/lang_init.php` 處理載入與 `__()` 翻譯函式。
    - 修改 `admin/auth.php` 進行全域 i18n 引入。
    - 替換 `index.php` (儀表板), `posts.php` (文章管理), `categories.php` (分類管理), `post_edit.php` (文章編輯), `tool_migrate.php` (匯入工具) 中的硬編碼文字。
    - 前端 JS i18n 支援：建立 `zh_TW.js` / `en_US.js`，透過 `admin/common_js_inc.php` 動態引入，支援 SweetAlert2 提示。
    - 修正 `admin/health_check.php` 支援多語系狀態訊息。

### [00:50] Vibe Coding 資訊整合
- **任務**: 在登入頁顯示開發所使用的 AI 工具版本。
- **實作**:
    - **自動化**: 更新 `gemini.md` "Update" 巨集，自動查詢 Gemini CLI 版本與 AI 模型名稱。
    - **設定寫入**: 將 Runtime 資訊 (CLI v0.26.0, Model gemini-3-pro-preview) 寫入 `admin/version_config.php`。
    - **登入頁顯示**: 在 `admin/login.php` 顯示 "Vibe coded with Gemini CLI..."。

### [01:10] 開發規範重構
- **任務**: 優化 Prompt 指令文件。
- **實作**: 將 `gemini.txt` 重構為結構化的 `gemini.md`，定義核心準則、自動化流程與日誌規範。

### [01:30] 環境與時區規範
- **任務**: 確保日誌與版本的時區正確性並修復亂碼。
- **實作**:
    - 更新 `gemini.md` 納入 UTC+8 時區規範與 Git Bash 執行建議。
    - 修復 `gemini_log.md` 的 BOM (`ï»¿`) 編碼問題。

### [21:30] 系統時間校正與規範強化
- **任務**: 統一校正全站時間戳記至正確的 UTC+8 晚間時段。
- **實作**:
    - 修正 `HISTORY.md`、`admin/version_config.php` 與 `gemini_log.md` 的時間偏差。
    - 在 `gemini.md` 中明確規定未來記錄需直接使用 UTC+8。

### [21:35] 執行巨集指令：更新
- **任務**: 依照 `gemini.md` 執行文件更新、版本同步與 Git 發佈。
- **實作**:
    - 更新版本至 v2026.01.31.21.35。
    - 同步 Gemini CLI (v0.26.0) 與模型 (gemini-3-flash-preview) 資訊。

### [23:45] 日誌復原與機制強化
- **任務**: 救回被覆蓋的 `gemini_log.md` 並防止再犯。
- **實作**:
    - 從 Git 歷史找回遺失日誌並合併。
    - 保存長期記憶並更新 `gemini.md`：規定日誌更新必須使用 "Append" 模式。
    - 強化 `gemini.md`：要求詢問式 Prompt 需記錄回答摘要。

### [23:58] 檔案格式統一與 i18n 同步
- **任務**: 統一 readme 檔名格式並實施全站雙語化。
- **實作**:
    - 將 `category/`, `contents/`, `preview/`, `static/` 下的 `readme.txt` 全部更名為 `readme.md`。
    - 完成核心根目錄文件 `ARCHITECTURE.md` 與 `HISTORY.md` 的英文化與同步。
    - 在 `gemini.md` 的 "Update" 流程加入強制規則：「所有文件必須維持中英文內容同步。」

### [00:20] README 同步與巨集精煉
- **任務**: 修復 README 內容落差並精煉 Gemini CLI 指令。
- **實作**:
    - 同步 `README.md` (中文版) 缺漏章節以匹配英文內容。
    - 在 `gemini.md` 新增 "Reload" (重讀) 巨集指令，用於同步 `git diff` 外部修改。
    - 清理 `gemini.md` 過多空行，提升閱讀性。

### [00:50] 開發評估與最終打磨
- **任務**: 進行部落格系統的綜合評估並執行最終巨集更新。
- **實作**:
    - 評估系統為「專業級、輕量化且高度客製」，給予架構與文件 5 星評價。
    - 執行 "Update" 巨集同步所有核心文件與版本資訊。

### [02:00] 可靠性增強與評估報告輸出
- **任務**: 強化日誌機制並輸出專案評估報告。
- **實作**:
    - 更新 `gemini.md` 要求詢問式 Prompt 需記錄摘要。
    - 新增 "Reload" 巨集同步外部編輯。
    - 改用 Python 處理 `.md` 檔案以解決中文亂碼問題 (`utf-8-sig`)。
    - 規範 `.md` 排版為單一空行。
    - 輸出完整的雙語專案評估報告至 `EVALUATION.md`。

### [02:30] 策略藍圖與未來規劃
- **任務**: 構思未來功能並建立長程開發藍圖。
- **實作**:
    - 深入分析高價值功能：伺服器端分頁、SQLite 支援、進階編輯器 (Editor.js) 與 Flickr/Google Sheets 整合。
    - 評估社交互動與技術基建的重要性。
    - 將所有提案、優先級與可行性分析彙整至結構化的 `ROADMAP.md` 文件。

---

## 2026-02-01 (繁體中文)

### [12:30] SQLite 3 資料庫支援與介面優化
- **任務**: 實作 SQLite 3 支援作為 MySQL 與檔案系統的替代方案，並修正 UI 不一致。
- **實作**:
    - 在 `admin/data_provider.php` 的 `DataManager` 加入 SQLite 支援。
    - 建立 `admin/sqlite_init.php` 用於資料庫初始化與資料遷移。
    - 建立 `api_sqlitebase.php` 供前端 SQLite 資料存取。
    - 更新 `admin/login.php` 與 `admin/health_check.php` 支援 SQLite 狀態偵測與模式選擇。
    - 修正 MySQL 與 SQLite 間 `GROUP_CONCAT` 語法的相容性問題。
    - 優化後台 UI (儀表板與側邊欄) 以正確識別並顯示 SQLite 連線詳情與檔案資訊。
    - 更新 `.gitignore` 與 `config.example.php` 以整合 SQLite。

### [13:45] 全方位資料遷移系統
- **任務**: 實作強大的雙向遷移系統，支援檔案系統、MySQL 與 SQLite。
- **實作**:
    - 增強 `admin/tool_migrate.php` 支援所有模式下的匯入 (Pull) 與匯出 (Push) 操作。
    - 實作 `runDBMigration` 處理資料庫對資料庫的直接傳輸 (MySQL <-> SQLite)。
    - 更新後台 UI，根據當前模式動態顯示可用的遷移目標。
    - 在 `auth.php` 與 `health_check.php` 加入嚴格的 PDO 擴充檢查以防止致命錯誤。

### [14:05] 國際化 (i18n) 補完
- **任務**: 補齊新模組的遺漏翻譯。
- **實作**:
    - 更新 `langs/admin/zh_TW.php` 與 `en_US.php`，補上遷移工具、SQLite 初始化與儀表板的缺漏鍵值。
    - 替換 `admin/tool_migrate.php`, `admin/sqlite_init.php`, `admin/index.php` 與 `admin/health_check.php` 中的硬編碼文字。
    - 確保所有新功能 (SQLite, Migration) 皆有完整的雙語支援。

### [14:45] 備份基礎建設
- **任務**: 建立專用的備份目錄。
- **實作**:
    - 建立 `/backup` 目錄並加入雙語 `readme.md`。
    - 更新 `.gitignore` 排除 `/backup/*.zip` 檔案。

### [15:15] 備份還原與上傳
- **任務**: 增強備份工具，加入還原與上傳功能。
- **實作**:
    - 實作從本機 ZIP 備份還原系統 (覆寫 contents)。
    - 加入檔案上傳功能以匯入外部備份，並包含檔案大小限制檢查與提示。
    - 整合 SweetAlert2 進行操作確認 (刪除、還原、建立) 與狀態訊息 (成功/失敗)。
    - 為耗時操作 (上傳、建立、還原) 加入 Loading 遮罩。
    - 更新所有新備份功能的 i18n 支援。

### [15:30] 備份工具優化 (PHP 設定與提示)
- **任務**: 加入使用者引導以處理大型備份檔。
- **實作**:
    - 在 `admin/tool_backup.php` 上傳區塊加入 PHP 設定提示 (upload_max_filesize, post_max_size 等)。
    - 提供範例 PHP.ini 設定與 FTP 替代方案建議。
    - 更新語系檔 (`zh_TW.php`, `en_US.php`) 加入新的提示文字。

### [15:45] MySQL 資料庫備份與還原
- **任務**: 擴充備份工具以支援 MySQL 資料庫模式。
- **實作**:
    - 實作 `createMysqlDump` 生成 SQL 結構與資料轉儲。
    - 實作 `restoreMysqlDump` 解析並執行 ZIP 中的 SQL 轉儲。
    - 更新 `create_backup` 將 SQL 檔 + 靜態資源 (`preview`, `pic`, `static/icon-192.png`) 打包為 `dbsqlbase-*.zip`。
    - 更新 `restore_backup` 支援 `dbsqlbase` 檔案，先還原資料庫再還原靜態檔案。

### [16:15] SQLite 資料庫備份與還原
- **任務**: 擴充備份工具以支援 SQLite 資料庫模式。
- **實作**:
    - 新增邏輯將使用中的 SQLite 資料庫檔 + 靜態資源打包為 `sqlitebase-*.zip`。
    - 新增邏輯從 ZIP 中還原 SQLite DB 檔案至設定路徑。
    - 新增 Helper 函數 (`addStaticFilesToZip`, `restoreStaticFiles`, `cleanupTempDir`) 修復 500 錯誤並重用程式碼。

### [16:30] 備份列表過濾
- **任務**: 避免混淆，僅顯示相關的備份檔案。
- **實作**:
    - 更新 `admin/tool_backup.php` 根據當前模式過濾列表：
        - 資料庫模式: 僅顯示 `dbsqlbase-*.zip`
        - SQLite 模式: 僅顯示 `sqlitebase-*.zip`
        - 檔案模式: 僅顯示 `filebase-*.zip`

### [19:30] 全方位安裝引導精靈
- **任務**: 設計並實作友善的系統初始化系統。
- **實作**:
    - 在根目錄建立 `install.php`。
    - 特性：環境檢測 (PHP 版本與 Unix 權限修復)、多模式資料庫測試 (MySQL/SQLite/File)、管理員設定與前端配置生成。
    - 多語系支援：建立 `langs/admin/install_zh_TW.php` 與 `install_en_US.php` 並將語系獨立管理。
    - 整合 `admin/version_config.php` 顯示系統版本資訊。

### [22:15] WSL2 開發環境建置與 OS 偵測強化
- **任務**: 自動化 WSL2 中的 LAMP 環境配置並提供詳細 OS 資訊。
- **實作**:
    - **環境配置**: 自動在 WSL2 Ubuntu 24.04 安裝 Apache2, MySQL 8.0, PHP 8.3。
    - **網頁整合**: 設定 Apache 監聽 8086 埠並透過軟連結掛載 Windows 專案目錄。
    - **資料庫初始化**: 建立符合專案設定的 MySQL 使用者與資料庫。
    - **phpMyAdmin**: 自動安裝並整合至自訂埠號。
    - **權限處理**: 修正 `install.php` 偵測 WSL2 NTFS 掛載點並自動跳過無效的權限修正步驟。
    - **OS 偵測**: 建立 `admin/system_helper.php` 提供詳細 OS 資訊 (如 Ubuntu 發行版或 Windows Build 號)。
    - **介面整合**: 在儀表板與安裝精靈中同步顯示詳細作業系統環境。

### [23:20] 強化 Windows OS 偵測與 UI 佈局優化
- **任務**: 提升 Windows 版本偵測可靠性並精煉安裝介面佈局。
- **實作**:
    - **進階 OS 偵測**: 在 `admin/system_helper.php` 中加入 COM/WMI 支援，作為原生 Windows PHP 環境的第一優先偵測方式，提供精確的產品名稱（如 Windows 11 專業版）。
    - **編碼校正**: 針對 PowerShell 輸出實作了強制的 CP950 轉 UTF-8 轉換，防止在繁體中文環境下出現亂碼。
    - **UI 優化**: 將 `install.php` 系統資訊重構為 2x2 網格顯示，將顯示寬度提升一倍，以利閱讀長的作業系統版本字串。
    - **Git 策略**: 更新 `gemini.md` 加入 WSL2 專屬回退規則，自動調用 `git.exe` 處理需要憑證驗證的推送操作。

### [23:30] WSL2 Git 策略精煉
- **任務**: 規範 WSL2 下的遠端儲存庫同步流程。
- **實作**:
    - **規範更新**: 更新 `gemini.md` 明確規定 WSL2 僅負責完成 Commit，最終的 `git push` 由使用者於具備憑證的環境 (如 Windows 終端機) 手動執行。
    - **巨集同步**: 調整「更新」關鍵字指令的流程，自動化執行至本地 Commit 為止，並加入手動推送提醒。

---

## 2026-02-03 (繁體中文)

### [19:30] 混合式草稿系統與檔名標準化
- **任務**: 實作文章草稿機制並確保檔案命名的一致性。
- **實作**:
    - **草稿機制**: 
        - 檔案模式：草稿存為 `.html.tmp`，正式文章為 `.html`。
        - 資料庫模式：動態補齊 `status` 欄位 ('draft'/'published')。
        - 前台過濾：更新所有 API 與 `make_html.php` 自動跳過草稿檔案。
    - **檔名標準化**: 
        - 根據發文日期自動補上 `YYYYMMDD-` 前綴。
        - 智慧偵測使用者輸入，支援手動輸入前綴並避免重複處理。
    - **UI 更新**: 新增「暫存草稿」與「正式發布」按鈕，並在後台列表與儀表板加入狀態標籤。

### [19:40] 網站設定圖形化介面 (config.js 管理)
- **任務**: 提供友善的介面來管理前端配置。
- **實作**:
    - 建立 `admin/settings.php` 用於管理 `config.js`。
    - 支援透過 UI 切換資料來源 (File/DB/SQLite)、選擇主題以及設定 Google CSE ID。
    - 採用正規表達式寫入配置，保留檔案原始格式。

### [19:50] 進階視覺化編輯器整合
- **任務**: 將文章編輯器從純文字框升級為視覺化編輯器。
- **實作**:
    - **TinyMCE 6**: 在本地端完整部署 TinyMCE 6.8.2，不依賴外部 CDN。
    - **客製化分頁**: 修改 PageBreak 外掛使用 `<!--more-->` 作為分隔符號，完美相容現台邏輯。
    - **語系與體驗**: 整合動態語系切換 (繁中/英文)，並移除升級提示與品牌標記以簡化介面。

### [20:00] 後台語系架構重構
- **任務**: 清理語系檔案並實施更嚴格的載入規則。
- **實作**:
    - 將語系檔更名為 `admin-` 前綴 (如 `admin-zh_TW.php`)，以區別安裝程式檔案。
    - 更新 `admin/lang_init.php` 掃描邏輯，自動排除 `install_` 開頭的檔案。
    - 精簡語言下拉選單，僅顯示核心語系代碼。

### [23:00] 專案宣傳網站開發 (Vibe Coding 實驗)
- **任務**: 建立一個現代化、攝影導向的宣傳網站來展示 ErsalBlog。
- **實作**:
    - **技術棧**: React + Vite + Bootstrap 5 + Framer Motion。
    - **內容**: 詳細規劃了開發動機 (匠人初心)、演進歷程 (Vibe 飛躍)、技術架構與未來藍圖。
    - **4K 優化**: 為超寬螢幕實作了自定義容器邏輯與響應式字體，確保版面完美置中且視覺平衡。
    - **部署**: 設定 Vite 編譯路徑，確保與現有 Apache 環境無縫整合。
    - **驗證**: 成功驗證宣傳網站作為 Vibe Coding 開發效率的強力證明。

### [23:33] 模型切換與全域更新
- **任務**: 將開發模型切換回 Gemini 3 Pro 並同步所有專案文件。
- **實作**:
    - 更新 `admin/version_config.php` 以反映切換至 `gemini-3-pro-preview`。
    - 執行「更新」巨集同步 HISTORY.md、ARCHITECTURE.md 與 README.md。
    - 執行本地 Git Commit，將宣傳網站資產與技術優化內容入庫。

---

## 2026-02-04 (繁體中文)

### [14:05] 核心 SSG 建置管線重構 (Regex 轉型)
- **任務**: 全面翻新靜態頁面生成邏輯，提升穩定性與相容性。
- **實作**:
    - **單一真理來源**: 重寫 `make_html.php` 靜態網頁生成邏輯，徹底解決結構劣化與轉碼問題。
    - **Regex 解析**: 移除 `DOMDocument` 改用 Regex 處理樣板與圖片優化，大幅提升相容性與效能。
    - **PHP 5.x 相容**: 維持向後相容性，確保在 AppServ 等舊版環境正常運作。

### [14:40] 自動化資產壓縮優化
- **任務**: 精煉 JS/CSS 壓縮流程。
- **實作**: 修正 `mini.py` 目錄排除邏輯，並新增自動清理機制，保持專案目錄整潔。

### [15:15] 微樣板管理器與增量建置
- **任務**: 提升建置效率與維護性。
- **實作**: 建立 `TemplateManager` 類別封裝解析邏輯，並實作基於檔案修改時間 (mtime) 的快取機制，大幅縮短生成時間。

---

## 2026-02-05 (繁體中文)

### [10:15] 前台多語系與動態配置
- **任務**: 實作前台多語系支援與動態時區/語系配置。
- **實作**: 建立 `langs/template/` 架構，將樣板硬編碼文字替換為 `{{variable}}`，並更新建置腳本與後台設定。

### [12:00] 安全性強化 (Security Hardening)
- **任務**: 修復路徑遍歷與 XSS 漏洞。
- **實作**: 在 API 引入 `basename()` 過濾，並在樣板生成與前端渲染流程中全面實作 HTML 轉義防護。

### [13:00] 純靜態 JSON API 模式與優化
- **任務**: 實作無後端 JSON 模式並解決亂碼 404 問題。
- **實作**: 
    - **單一資料源**: 將原本分散的 JSON 合併為 `api/json/data.json`，避免中文字元檔名導致的存取錯誤。
    - **前端過濾**: 在 `blog.js` 實作客戶端路由與篩選邏輯，達成全靜態瀏覽體驗。
    - **建置支援**: `make_html.php` 新增 `-json` 參數，一鍵導出完整資料包。

### [13:30] 智慧建置快取 (Smart Build Cache)
- **任務**: 優化建置效能，精確偵測配置變更。
- **實作**: 實作雜湊雜湊 (Hash) 比對機制，將變數分為「全域」與「僅首頁」影響兩類，達成更細粒度的增量建置，減少不必要的檔案重產。

### [13:45] HTML 語系宣告動態化
- **任務**: 讓 `<html>` 標籤的 `lang` 屬性支援多語系切換。
- **實作**: 在樣板語系檔新增 `html_lang` 鍵值，並將 `blog_template.html` 內的硬編碼屬性改為變數佔位符，提升 SEO 與瀏覽器相容性。

### [14:00] 日期單位多語系修復
- **任務**: 修正側邊欄與歸檔清單中的日期單位顯示。
- **實作**: 將 `template_date_post_item` 內硬編碼的「日」替換為 `{{lang_day_suffix}}`，並在語系檔補齊相關定義，確保年月日顯示完整。

### [14:30] 建置腳本穩定性與快取強化
- **任務**: 修復 `make_html.php` 語法錯誤並強化快取檢查。
- **實作**: 修正 `build` 函式語法錯誤，並將語系檔內容納入全域雜湊計算與實體相依檢查，確保語系檔變更時能正確觸發所有受影響頁面的重建。

### [14:45] 英文語系單位遺漏修復
- **任務**: 解決英文模式下「日」單位不顯示的問題。
- **實作**: 補齊 `template-en_US.php` 中的 `day_suffix` 定義，優化英文歸檔標籤的可見性與一致性。

---

## 2026-02-06 (繁體中文)

### [23:30] 語系檔案結構重構 (i18n Refactoring)
- **任務**: 簡化語系檔案目錄結構，提升讀取效率。
- **實作**: 
    - 將 `langs/admin/` 與 `langs/template/` 下的檔案移至 `langs/` 根目錄。
    - 同步更新 `make_html.php`, `install.php`, `admin/lang_init.php` 等所有相關路徑。

### [23:45] PHP 5.x 全域相容性強化
- **任務**: 確保專案能在舊版 PHP 5.x 環境 (如 AppServ) 穩定執行。
- **實作**: 
    - **語法降級**: 將 `??` 替換為 `isset() ? :`，將 `[]` 替換為 `array()`。
    - **隨記數回退**: 在 `system_helper.php` 實作 `random_bytes` 回退方案。
    - **核心校閱**: 修正所有 `admin/` 與 `api/` 核心邏輯，確保相容性。

### [14:35] 後台登入 500 錯誤修正
- **任務**: 修復因語系目錄結構變動導致的後台失效。
- **實作**: 修正 `admin/lang_init.php` 中的 `$langBaseDir` 路徑，使其正確指向扁平化後的 `langs/` 根目錄。

### [14:45] 備份工具 500 錯誤修正
- **任務**: 修復 `admin/tool_backup.php` 的運行錯誤。
- **實作**: 
    - 補齊缺失的 `data_provider.php` 引用。
    - 修正前端 JS 呼叫時的表單 ID 不匹配問題。
    - 優化變數定義流程以提升程式碼強健度。

### [14:50] 備份工具 CSRF 安全性修正
- **任務**: 解決刪除或還原備份時出現 "Invalid CSRF Token" 的錯誤。
- **實作**: 為備份清單中的所有操作表單補上 `csrf_token` 隱藏欄位，確保符合安全性檢查規範。

### [15:05] API 文章同步過濾強化
- **任務**: 確保動態列表僅顯示已成功生成靜態 HTML 的文章。
- **實作**: 在所有 API 接口 (File/MySQL/SQLite) 與靜態 JSON 生成邏輯中，加入對 `post/` 目錄下實體檔案的檢查。
- **優點**: 防止「已發布但尚未建置」的文章出現在前台列表中，確保網站顯示內容與實體檔案完全同步。

### [15:30] 靜態生成架構重構 (SSG Refactoring)
- **任務**: 解耦「資料發布」與「靜態網頁生成」，但提供可選的自動化整合。
- **實作**:
    - **邏輯封裝**: 建立 `PHP_LIB/StaticGenerator.php` 類別，統一管理建置邏輯。
    - **CLI 更新**: 重寫 `make_html.php` 為輕量級介面。
    - **後台整合**: 在 `admin/post_edit.php` 新增「立即重建」選項，並在 `post_save.php` 中實作自動建置觸發器。

### [15:45] SSG 穩定性修復與 PHP 5.x 相容性強化
- **任務**: 解決重構後出現的 500 錯誤並提升舊版 PHP 支援。
- **實作**: 
    - **語法修復**: 修正 `StaticGenerator.php` 內的引號轉義與 Regex 錯誤。
    - **相容性修復**: 針對 PHP 5.3 移除 Closure 中的 `$this` 使用，並將語系檔全面降級為 `array()` 語法。
    - **強健度提升**: 在 `post_save.php` 引入更全面的錯誤捕捉邏輯。

---

## 2026-02-07 (繁體中文)

### [14:15] 文章內容 Script 標籤保護 (Script Tag Protection)
- **任務**: 防止文章內的 `<script>` 內容在網頁中執行，同時確保其在技術文章中的可見性。
- **實作**: 
    - **核心邏輯**: 在 `admin/system_helper.php` 實作 `protect_script_tags` 函式，將 `<script>` 標籤轉義為 `&lt;script&gt;`。
    - **優點**: 腳本標籤現在能以文字形式在技術文章中顯示，但不會被瀏覽器當作腳本執行，安全性與可讀性兼具。
    - **全域套用**: 整合至 `make_html.php` 與所有 `api/*.php` 檔案。

### [15:30] 靜態生成架構重構 (SSG Refactoring)
- **任務**: 解耦「資料發布」與「靜態網頁生成」，提供更靈活的建置管線。
- **實作**:
    - **邏輯封裝**: 建立 `PHP_LIB/StaticGenerator.php` 類別，統一管理 SSG 核心邏輯。
    - **後台整合**: 在 `admin/post_edit.php` 新增「儲存後立即重建」選項。
    - **相容性修復**: 修正 PHP 5.3 不支援 Closure 使用 `$this` 的限制，確保在舊版環境穩定執行。

### [17:15] 後台建置管理頁面與導覽重構
- **任務**: 建立專用的建置管理介面並優化後台使用者體驗。
- **實作**:
    - **網站建置頁面**: 新增 `admin/build.php`，支援「強制重生」、「更新 JSON API」以及「選取特定文章建置」。
    - **導覽列組件化**: 建立 `admin/sidebar_inc.php` 並將後台選單統一化，實作 **Fixed Sidebar** 佈局，讓選單不隨頁面捲動消失。
    - **狀態偵測**: 在儀表板與文章列表中，加入「靜態網頁未建立」的即時偵測與紅色警告標籤。

### [18:00] 文章管理分頁功能
- **任務**: 解決文章量大時載入緩慢的問題。
- **實作**: 在 `DataManager` 實作 `getPostsPaged` 方法，並在 `admin/posts.php` 建立分頁導覽列（每頁 15 篇），顯著提升管理效率。
