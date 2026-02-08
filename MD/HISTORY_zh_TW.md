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
    - 保存長期記憶並更新 `gemini.md`：規定日誌更新必須使用 "Append" mode。
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

---

## 2026-02-02 (繁體中文)

### [23:11] 設定檔結構優化
- **任務**: 了解 config.php 定義方式與內容的變更，並同步更新 config.example.php。

### [23:18] 安裝程式升級
- **任務**: 更新 install.php 及其語系檔，全面支援 config.php 的新結構並實作正式安裝功能。

### [23:32] 靜態化路徑規範
- **任務**: 修改 make_html.php：將靜態文章輸出至 post/ 目錄，並實作資源路徑 (../) 的自動修正與 Sitemap 更新。

---

## 2026-02-03 (繁體中文)

### [00:05] API 結構重構
- **任務**: 將 api_*.php 移至 api/ 目錄，並更新前端 (blog.js) 與後端 (Config 引入、File/SQLite 路徑) 的相依性設定。

### [00:25] 多重 CSS 主題實作
- **任務**: 建立 blog-dark.css，更新設定檔結構，並修改模板以支援從 config.js 動態載入主題。

---

## 2026-02-04 (繁體中文)

### [12:00] 靜態生成管線翻新 (SSG Pipeline)
- **任務**: 優化靜態生成流程 (make_html.php)：改用 blog_template.html 為單一來源，移除 blog.html 中繼解析邏輯，統一改用雙大括號變數佔位符 {{variable}}。

### [14:05] 核心解析邏輯 Regex 化
- **任務**: 重構 make_html.php：完全移除 DOMDocument 依賴，改用 Regex 處理樣板解析與圖片優化，徹底解決 HTML5 標籤相容性問題。

---

## 2026-02-05 (繁體中文)

### [10:15] 前台多語系支援
- **任務**: 實作前台多語系支援與動態配置，建立 langs/template/ 目錄。

### [12:30] 純靜態 JSON API 模式
- **任務**: 實作 api_type: 'json' 選項，並開發客戶端路由邏輯與 data.json 生成機制。

### [13:30] 智慧建置快取 (Hash Cache)
- **任務**: 引入基於 Config 變數雜湊 (Hash) 與檔案修改時間 (mtime) 的增量建置機制。

---

## 2026-02-06 (繁體中文)

### [23:30] 語系結構扁平化
- **任務**: 將所有語系檔移至 langs/ 根目錄，移除子目錄結構以簡化載入邏輯。

### [23:45] PHP 5.x 全域相容性強化
- **任務**: 執行大規模語法降級 (?? 轉 isset, [] 轉 array) 並補全舊版回退函式。

---

## 2026-02-07 (繁體中文)

### [14:15] 文章內容 Script 標籤保護 (Script Tag Protection)
- **任務**: 防止文章內的 `<script>` 內容在網頁中執行，同時確保其在技術文章中的可見性。
- **實作**: 
    - **核心邏輯**: 在 `admin/system_helper.php` 實作 `protect_script_tags` 函式，將 `<script>` 標籤轉義為 `&lt;script&gt;`。
    - **優點**: 腳本標籤現在能以文字形式在技術文章中顯示，但不會被瀏覽器當作腳本執行，安全性與可讀性兼具。
    - **全域套用**: 整合至 `make_html.php` 與所有 `api/*.php` 檔案。

### [14:35] API 與系統穩定性修復
- **任務**: 解決後台與備份工具的 500 錯誤與安全性問題。
- **實作**:
    - **路徑修復**: 修正 `admin/lang_init.php` 語系路徑錯誤。
    - **備份修復**: 補齊 `tool_backup.php` 引用並修正 CSRF Token 驗證。
    - **同步過濾**: 強化 API 邏輯，僅回傳已存在靜態檔案的文章，確保前台顯示與實體同步。

### [14:40] API 全面重構與精簡 (API Refactoring)
- **任務**: 消除重複代碼並統一三種模式 (File/MySQL/SQLite) 的 API 邏輯。
- **實作**:
    - **邏輯統一**: 重構 `api_filebase.php`, `api_sqlitebase.php`, `api_dbsqlbase.php`，採用單一核心邏輯 `get_data()`。
    - **代碼縮減**: 消除 90% 的重複邏輯，並移除冗餘的 `page()` 進入點與輔助函式。
    - **相容性保持**: 確保重構後的 JSON 輸出格式與前端 `blog.js` 完全相容。

### [15:30] 靜態生成與分頁架構重構 (SSG & Pagination Refactoring)
- **任務**: 統一建置邏輯並實作高效能的混合式分頁系統。
- **實作**:
    - **建置封裝**: 建立 `PHP_LIB/StaticGenerator.php` 類別，並修正硬編碼標題以支援 i18n。
    - **混合分頁**: 實作伺服器端 (PHP API) 與客戶端 (JSON Mode) 分頁邏輯，大幅提升大數據量下的效能。
    - **UI/UX 美化**: 建立分頁按鈕組件，支援「上一頁/下一頁」導覽與深淺色主題切換。
    - **設定整合**: 在後台設定新增「每頁文章數量」管理功能。

### [15:55] 導覽邏輯修復與安全性強化
- **任務**: 解決日期篩選失效問題並持續優化穩定性。
- **實作**:
    - **Bug 修正**: 修正 `date_range` 篩選邏輯，支援 4 碼 (年份) 與 6 碼 (年月) 的動態匹配。
    - **同步過濾**: 強化 API 文章回傳機制，自動過濾尚未生成實體靜態檔的文章。
    - **穩定性修復**: 修復備份工具 CSRF 驗證與語系路徑引用錯誤。

### [16:35] 多元主題擴充與 UI 穩定化
- **任務**: 擴充網站視覺風格並強化版面佈局的穩定性。
- **實作**:
    - **粉柔主題 (Soft Pink)**: 新增 `blog-pink.css`，以櫻花粉與玫瑰色系為主，採用圓角化設計與柔和陰影。
    - **駭客主題 (The Matrix)**: 新增 `blog-matrix.css`，模擬終端機黑底綠字風格，並加入掃描線背景特效。
    - **佈局強化**: 為所有主題的側邊欄與分頁容器強制加入 `clear: both`，解決部分情況下版面錯位問題。
    - **微調**: 移除 Matrix 主題的圖片濾鏡以保留原色，並優化分頁按鈕的響應式體驗。

### [16:50] 穩定性修復與規範強化
- **任務**: 修正建置錯誤並強化開發流程規範。
- **實作**:
    - **錯誤修復**: 修正 `StaticGenerator.php` 中語系鍵值引用錯誤（加上 `lang_` 前綴），解決執行 `make_html.php` 時出現的 `Undefined index` 警告。
    - **規範更新**: 在 `gemini.md` 加入時間抓取準則，指定優先使用 Linux `date` 指令以確保紀錄準確性。

### [23:55] 相簿服務模組開發 (Album Service)
- **任務**: 建立一個獨立、無需資料庫、基於檔案系統的相簿服務。
- **核心架構**:
    - **生成器**: 開發 `album/make_album.php`，負責生成靜態 HTML、壓縮縮圖與提取 EXIF。
    - **API**: 開發 `album/api/api_album.php`，提供首頁相簿列表資料與分頁功能。
    - **前端**: 開發 `album/static/js/album.js` 與 `album_template.html`，實作 SPA 體驗與組件化渲染。
- **關鍵功能**:
    - **多規格縮圖**: 自動生成 `thumbXL` (2048px), `thumbL` (1600px), `thumbM` (1024px), `thumb` (800px)。
    - **EXIF 支援**: 後端自動讀取原始照片的 EXIF 資訊並渲染至靜態頁面 (光圈、快門、ISO 等)。
    - **靜態分頁**: 針對相簿內頁實作靜態分頁生成 (24張/頁)，優化載入效能。
    - **詳情頁互動**: 支援全螢幕 Modal 檢視、原始檔下載與社群連結分享功能。
    - **智慧導覽**: 實作智慧麵包屑與「返回列表」邏輯，能精確返回照片所屬的分頁。
- **優化與規範**:
    - **CLI 支援**: `make_album.php` 支援 `-s` (跳過縮圖) 與 `-f` (強制重製) 參數。
    - **Git 管理**: 設定 `.gitignore` 排除所有生成檔案與原始照片，僅保留程式碼與設定檔。
    - **文件化**: 為相簿各層級目錄建立詳細的 `readme.md` 說明文件。

## [v2026.02.08.14.00] - 2026-02-08

### 相簿管理後台 (Album Admin Panel)
- **獨立後台建置**:
  - 建立 \`album/admin/\` 獨立管理介面，實作完整 CRUD 功能 (新增/編輯/刪除相簿與照片)。
  - 整合部落格後台儀表板，自動偵測並提供相簿管理入口。
- **功能特性**:
  - **設定管理**: 新增前端設定頁面，可直接修改 \`config.js\` (主題、API 模式、每頁筆數)。
  - **圖片管理**: 支援批次上傳、封面設定、檔名修改與標題描述編輯。
  - **介面優化**: 採用 8 欄位寬螢幕佈局，統一預覽圖為「等比例縮放 (Contain)」模式。
  - **效能優化**: 實作 \`thumbXS\` (320px) 極小縮圖，大幅提升後台載入速度。

### 相簿服務 (Album Service) 架構重構
- **SPA 全面轉型**:
  - 移除舊版靜態 HTML 生成邏輯 (\`view/\`)，全面轉向 JSON 資料驅動的 SPA 架構。
  - 實作前後端分離的分頁機制：\`json\` 模式由前端處理，\`api_filebase\` 模式由後端 API 動態切片。
- **系統優化**:
  - 修正 API 在處理 URL 編碼與非 UTF-8 目錄名稱時的路徑錯誤。
  - 統一 \`config.js\` 與 \`config.example.js\` 設定結構。
  - 改進日期讀取邏輯：優先讀取描述檔，若無則自動回退至檔案系統時間。

## [v2026.02.08.03.15] - 2026-02-08

### 相簿服務 (Album Service) 優化
- **短網址系統 (ShortURL)**:
  - 實作 \`album/shorturl.php\`，支援 Base62 編碼與 MIME Type 自動辨識回傳。
  - 升級混淆演算法：採用 **模乘雜湊 (Modular Multiplicative Hashing)** + XOR 掩碼，產生高度隨機的 5 碼 Slug。
  - 生成器整合：\`make_album.php\` 現在會自動生成 \`shorturl.txt\` (格式：\`ID|Path\`) 供反查使用。
- **前端重構 (De-Bootstrap)**:
  - **移除依賴**: 完全移除 Bootstrap CSS/JS 框架，改用原生 HTML/CSS/JS 實作。
  - **CSS Grid**: 重寫 \`album/static/album.css\`，採用現代 CSS Grid 進行響應式佈局。
  - **輕量化**: 整合並內聯 SVG 圖示，移除所有外部字體與多餘資源，大幅提升載入速度。
- **多重主題 (Theme System)**:
  - 建立主題切換機制，支援 \`album\` (預設), \`album-dark\`, \`album-pink\`, \`album-matrix\` 四種風格。
  - 新增 \`album/config.js\` 與 \`config.example.js\` 供使用者自訂預設主題。
- **功能增強**:
  - **原始檔下載**: 在詳情頁新增下載按鈕，直接連結至 Collection 下的高品質原圖。
  - **進階分享**: 實作多尺寸連結分享視窗，支援一鍵複製與「原始路徑/短網址」切換功能。
  - **導覽優化**: 統一全站分頁導覽列為置中顯示，修正標頭與內容寬度對齊問題。
  - **EXIF 顯示**: 前端移除 \`exif.js\`，改由後端 PHP 讀取並直接渲染至靜態 HTML，提升相容性與效能。
- **系統維護**:
  - 更新 \`.gitignore\` 排除敏感設定檔與生成數據。
  - 清理舊規則縮圖並強制重建所有靜態資源。
  - 更新 \`album/Collection/相簿1/readme.md\` 規範。
