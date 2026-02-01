import codecs

en_part = """
### [13:45] Comprehensive Data Migration System
- **Task**: Implement a robust two-way migration system supporting File System, MySQL, and SQLite.
- **Implementation**:
    - Enhanced `admin/tool_migrate.php` to support both Import (Pull) and Export (Push) operations in all modes.
    - Implemented `runDBMigration` to handle direct database-to-database data transfer (MySQL <-> SQLite).
    - Updated Admin UI to dynamically display available migration targets based on current mode and configuration.
    - Added strict environment checks for PDO extensions in `auth.php` and `health_check.php` to prevent fatal errors.
"

zh_part = """
### [13:45] 全方位資料遷移系統
- **任務**: 實作支援檔案系統、MySQL 與 SQLite 三方互轉的強大遷移系統。
- **實作**:
    - 強化 `admin/tool_migrate.php`，在所有模式下皆支援匯入 (Pull) 與匯出 (Push) 操作。
    - 實作 `runDBMigration` 函數，處理資料庫對資料庫 (MySQL <-> SQLite) 的直接資料傳輸。
    - 更新後台介面，根據當前模式與設定動態顯示可用的遷移目標。
    - 在 `auth.php` 與 `health_check.php` 中加入嚴格的 PDO 擴充檢查，防止因環境不支援導致的致命錯誤。
"

try:
    with codecs.open('HISTORY.md', 'r', 'utf-8-sig') as f:
        content = f.read()

    if '### [13:45]' not in content:
        content = content.replace('## 2026-02-01\n\n', '## 2026-02-01\n' + en_part + '\n')
        content = content.replace('## 2026-02-01 (繁體中文)\n\n', '## 2026-02-01 (繁體中文)\n' + zh_part + '\n')

        with codecs.open('HISTORY.md', 'w', 'utf-8-sig') as f:
            f.write(content)
        print("HISTORY.md updated successfully.")
    else:
        print("HISTORY.md already up to date.")

except Exception as e:
    print(f"Error updating HISTORY.md: {e}")
