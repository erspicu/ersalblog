# 專案環境設定 (Project Environment)
- **專案類型**: PHP 開發專案
- **互動語言**: 繁體中文 (Traditional Chinese)
- **程式相容性**: 程式碼必須相容 PHP 7.x 及以上版本。
- **執行環境**: 本次對話後，使用者將切換至 **Git Bash** 環境。執行 Shell 指令時應優先考慮 Unix 語法 (如 `&&` 串接指令)。
- **時區規範**: 所有時間記錄、日誌 (尤其是 `gemini_log.md`) 與版本號必須統一使用 **UTC+8** 時區。在執行任何記錄動作前，請務必確認並轉換為 UTC+8 時間。

# 核心行為準則 (Core Guidelines)

## 1. 檔案掃描與分析 (File Analysis)
在分析專案結構時：
- **關注檔案**: `*.php`, `*.js`, `*.css`, `*.py`, `*.md`, `*.txt`。
- **忽略排除**:
    - 與開發無關的圖片資源。
    - 壓縮後的資源檔 (`*.min.js`, `*.min.css`)。
    - `make_html.php` 輸出的靜態網頁檔案。
    - `sitemap.xml`。
    - Google Search Console 驗證用的 HTML 檔。

## 2. 自動化執行 (Automation)
- **直接執行**: 所有的檔案操作 (如 `write_file`, `replace`) 與 Shell 指令 (`run_shell_command`)，只要符合任務需求，請**預設直接執行**，不需詢問使用者同意或進行確認。

## 3. 輸入防呆 (Input Validation)
- 每次接收指令時，請自動檢查內容是否完整。若懷疑是不小心按到 Enter 導致指令中斷或不完整，請暫停執行並要求使用者確認。

## 4. 日誌記錄 (Logging)

- **目標檔案**: `gemini_log.md`

- **編碼格式**: 必須強制使用 **UTF-8 with BOM** (以確保 Windows Notepad 可正確讀取)。

- **記錄時機**: 每次執行 Prompt 動作後。

- **記錄格式**: 請使用 Markdown 列表格式，例如：

  `- [YYYY-MM-DD HH:MM:SS] (UTC+8) Prompt 內容`



# 關鍵詞指令定義 (Macro Commands)



## "更新" (Update)

當使用者輸入關鍵字 **"更新"** 時，請依序執行以下流程：

1.  **文件更新**: 掃描現況，更新 `ARCHITECTURE.md`、`HISTORY.md` 與 `README.md`。

2.  **版本控管**:

    - 呼叫 `cli_help` 查詢當前的 **Gemini CLI 版本** 與 **AI 模型名稱**。

    - 獲取當前 **UTC+8** 日期時間作為版本號 (格式：`vYYYY.MM.DD.HH.MM`)。

    - 將上述資訊寫入 `admin/version_config.php`。

3.  **發佈**: 執行 Git Commit 與 Push 操作。