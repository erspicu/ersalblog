# 專案環境設定 (Project Environment)
- **專案類型**: PHP 開發專案
- **互動語言**: 繁體中文 (Traditional Chinese)
- **程式相容性**: 程式碼必須相容 PHP 5.x 及以上版本。
- **執行環境**: 使用者將切換至 **WIN11上WSL2 Ubuntu 24.04** 環境。執行 Shell 指令時應優先考慮 Bash/Linux 相容性（如 `&&`, `grep`, `tail` 等），並確保輸出與寫入均採用 **UTF-8** 編碼以避免亂碼。
- **WSL2 Git 推送策略**: 考量到 WSL2 環境下 GitHub 憑證驗證 (Credentials) 的互動性限制，本環境僅負責完成本地端代碼的 `git add` 與 `git commit` 操作。**最終將變更推送 (Push) 至遠端儲存庫的動作，由使用者手動在其他適當環境 (如 Windows 終端機) 執行。** 這樣可避免自動化腳本因無法取得認證而中斷。
- **時區規範**: 所有時間記錄、日誌 (尤其是 `MD/gemini_log_YYYY_Www.md`) 與版本號必須統一使用 **UTC+8** 時區。在執行任何記錄動作前，應優先使用 Linux `date` 指令抓取當前系統時間 (包含週數格式 `date +"%G_W%V"`)，並務必確認並轉換為 UTC+8 時間。
- **後台開發**: 後台開發的部分都要考慮到多語支援，利用現在已經架構好得多語框架，不要把文字直接寫死在 code 裡。
- **相簿服務架構規範 (Album Service Architecture)**:
    - **定位與目錄**: 位於 `album/` 目錄，身為 Blog 的子專案，但開發上應視為獨立實體。
    - **獨立性原則 (Decoupling)**: 核心邏輯必須與 Blog 主系統完全脫鉤。相簿服務應具備「獨立運作」與「高度可移植性」，確保其能被單獨部署或移至其他環境運作。
    - **整合機制**: Blog 主服務僅在設定好相簿目錄位置的情況下存取該服務。若未設定或無相簿服務，兩者應互不干擾，維持系統穩健。
    - **開發方針**: 所有功能實作優先考慮相簿服務自身的完整性與自洽性，避免對 Blog 特定環境產生強依賴。
- **留言板服務架構規範 (MessageBoard Service Architecture)**:
    - **定位與目錄**: 位於 `MessageBoard/` 目錄，開發原則比照相簿服務。
    - **獨立性原則 (Decoupling)**: 核心邏輯必須與 Blog 主系統完全脫鉤，具備「獨立運作」與「高度可移植性」。
    - **混合存儲模式 (Hybrid Storage Mode)**: 
        - 支援 **PHP+SQLite/JSON** 本地存儲模式。
        - 支援 **Serverless (GAS + Google Sheets)** 雲端存儲模式，用於無後端能力的純靜態環境。
    - **極度脫鉤設計 (Extreme Decoupling)**: 留言板前端 UI 必須與存儲 API 完全分離。前端應透過 Adapter 介面與後端溝通，確保切換「本地存儲」或「雲端存儲」時，不需修改前端 UI 邏輯。
    - **整合機制**: Blog 僅作為該服務的客戶端。若未配置該服務，Blog 的評論區應能優雅降級或不顯示。
    - **開發方針**: 優先發展留言板服務的通用性與防垃圾機制（如 GAS 層的過濾邏輯）。

# 核心行為準則 (Core Guidelines)

## 1. 檔案掃描與分析 (File Analysis)
在分析專案結構時：
- **關注檔案**: `*.php`, `*.js`, `*.css`, `*.py`, `*.md`, `*.txt`。
- **忽略排除**:
    - 與開發無關的圖片資源。
    - admin/assets 目錄底下第三方套件所有相關檔案
    - 壓縮後的資源檔 (`*.min.js`, `*.min.css`)。
    - `make_html.php` 輸出的靜態網頁檔案。
    - `api/json/` 目錄下的預生成 JSON 檔案。
    - `contents/build_hash.json` 建置快取雜湊檔。
    - `sitemap.xml`。
    - Google Search Console 驗證用的 HTML 檔。

## 2. 自動化執行 (Automation)
- **直接執行**: 所有的檔案操作 (如 `write_file`, `replace`) 與 Shell 指令 (`run_shell_command`)，只要符合任務需求，請**預設直接執行**，不需詢問使用者同意或進行確認。

## 3. 輸入防呆 (Input Validation)
- 每次接收指令時，請自動檢查內容是否完整。若懷疑是不小心按到 Enter 導致指令中斷或不完整，請暫停執行並要求使用者確認。

## 4. 日誌記錄 (Logging)
- **分週管理**: 日誌檔案應以「週」為單位進行切割與儲存。
- **目標檔案**: `MD/gemini_log_YYYY_Www.md` (WW 為 ISO 週數，如 `2026_W06`)。
- **編碼格式**: 必須強制使用 **UTF-8 with BOM** (以確保 Windows Notepad 可正確讀取)。
- **記錄時機**: 每次執行 Prompt 動作後。
- **記錄格式**: 
    - 一般指令：`- [YYYY-MM-DD HH:MM:SS] Prompt 內容`
    - 詢問式 Prompt：`- [YYYY-MM-DD HH:MM:SS] Prompt 內容`，並在下方另起一行記錄回答內容摘要。
- **更新方式**: 必須先讀取現有內容並將新日誌追加在後方，若檔案不存在則自動建立，不可直接覆蓋（write_file），以確保當週歷史紀錄完整。

## 5. 排版規範 (Formatting)
- **簡潔空行**: 避免在 `.md` 檔案中產生連續多個空行（超過一個空行）。所有條目應緊湊排列，或僅保留一個空行作為區隔。
- **編碼安全**: 修改或讀取 `.md` 檔案時，必須確保以 **UTF-8 with BOM** 編碼進行，防止中文字元損壞。在執行自動化腳本或大規模替換時，應顯式指定編碼。

# 關鍵詞指令定義 (Macro Commands)

## "更新" (Update)
當使用者輸入關鍵字 **"更新"** 時，請依序執行以下流程：
1. **文件更新**: 掃描現況，更新 `MD/ARCHITECTURE.md`、`README.md`，以及**當週**歷史紀錄檔 `MD/HISTORY_zh_TW_YYYY_Www.md` (繁中) 與 `MD/HISTORY_en_US_YYYY_Www.md` (英文)。**所有文件必須維持中英文內容同步。**
2. **版本控管**:
    - 呼叫 `cli_help` 查詢當前的 **Gemini CLI 版本** 與 **AI 模型名稱**。
    - 獲取當前 **UTC+8** 日期時間作為版本號 (格式：`vYYYY.MM.DD.HH.MM`)。
    - 將上述資訊寫入 `admin/version_config.php`。
3. **發佈**: 執行本地 Git Commit 操作。完成後，提示使用者手動在適當環境中執行 `git push`。

## "簡單更新" (Simple Update)
當使用者輸入關鍵字 **"簡單更新"** 時，僅執行純粹的 Git 同步流程：
1. **自動 Commit**: 直接執行 `git add .` 並進行本地 Commit。
2. **訊息規範**: Commit 訊息格式應為 `Simple Update: vYYYY.MM.DD.HH.MM` (使用當前 UTC+8 時間)。
3. **提醒**: 完成後，提示使用者手動在適當環境中執行 `git push`。

## "重讀" (Reload)
當使用者輸入關鍵字 **"重讀"** 時，請執行以下流程以同步外部編輯器的修改：
1. **差異分析**: 使用 `git diff` 檢查工作區中所有相對於上次 Commit 的變更，並找出被修改、新增或刪除的檔案清單。
2. **內容理解**: 針對所有變更過的檔案，重新讀取其內容並詳細分析新的邏輯、結構或功能變動，以精確更新 AI 的上下文理解 (Context)。
3. **記錄與回饋**: 在日誌中記錄偵測到的外部變更摘要，並根據修改內容判斷是否需要更新相關技術文件。
