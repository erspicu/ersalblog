import codecs

# 12:30 Log (Restored)
log_1230_en = """### [12:30] SQLite 3 Database Support and Interface Optimization
- **Task**: Implement SQLite 3 support as an alternative to MySQL and the file system, and fix UI inconsistencies.
- **Implementation**:
    - Added SQLite support to `DataManager` in `admin/data_provider.php`.
    - Created `admin/sqlite_init.php` for database initialization and data migration (from file system or MySQL).
    - Created `api_sqlitebase.php` for frontend SQLite data access.
    - Updated `admin/login.php` and `admin/health_check.php` to support SQLite status detection and mode selection.
    - Fixed `GROUP_CONCAT` syntax compatibility issues between MySQL and SQLite.
    - Optimized Admin UI (Dashboard and Sidebar) to correctly identify and display SQLite connection details and file information.
    - Updated `.gitignore` and `config.example.php` for SQLite integration.
"""

log_1230_zh_escaped = "\n### [12:30] SQLite 3 \u8cc7\u6599\u5eab\u652f\u63f4\u8207\u4ecb\u9762\u986f\u793a\u512a\u5316\n- **\u4efb\u52d9**: \u5be6\u4f5c SQLite 3 \u652f\u63f4\u4f5c\u70ba MySQL \u8207\u6a94\u6848\u7cfb\u7d71\u5916\u7684\u7b2c\u4e09\u7a2e\u9078\u64c7\uff0c\u4e26\u4fee\u6b63\u4ecb\u9762\u986f\u793a\u4e0d\u4e00\u81f4\u7684\u554f\u984c\u3002\n- **\u5be6\u4f5c**:\n    - \u5728 `admin/data_provider.php` \u7684 `DataManager` \u4e2d\u65b0\u589e SQLite \u652f\u63f4\u3002\n    - \u5efa\u7acb `admin/sqlite_init.php` \u8ca0\u8cac\u8cc7\u6599\u5eab\u521d\u59cb\u5316\u8207\u8cc7\u6599\u9077\u79fb\uff08\u652f\u63f4\u5f9e\u6a94\u6848\u6216 MySQL \u532f\u5165\uff09\u3002\n    - \u5efa\u7acb `api_sqlitebase.php` \u4f9d\u524d\u7aef\u5b58\u53d6 SQLite \u8cc7\u6599\u3002\n    - \u66f4\u65b0 `admin/login.php` \u8207 `admin/health_check.php` \u652f\u63f4 SQLite \u72c0\u614b\u5075\u6e2c\u8207\u6a21\u5f0f\u5207\u63db\u3002\n    - \u4fee\u6b63 MySQL \u8207 SQLite \u9593 `GROUP_CONCAT` \u7684\u8a9e\u6cd5\u76f8\u5bb9\u6027\u554f\u984c\u3002\n    - \u512a\u5316\u5f8c\u53f0\u4ecb\u9762\uff08\u5100\u8868\u677f\u8207\u5074\u908a\u6b04\uff09\uff0c\u4f7f\u5176\u80fd\u6b63\u78ba\u8fae\u8b58\u4e26\u986f\u793a SQLite \u9023\u7dda\u8a73\u60c5\u8207\u6a94\u6848\u8cc7\u8a0a\u3002\n    - \u66f4\u65b0 `.gitignore` \u8207 `config.example.php` \u4ee5\u6574\u5408 SQLite \u8a2d\u5b9a\u3002\n"

# 13:45 Log (New)
log_1345_en = """
### [13:45] Comprehensive Data Migration System
- **Task**: Implement a robust two-way migration system supporting File System, MySQL, and SQLite.
- **Implementation**:
    - Enhanced `admin/tool_migrate.php` to support both Import (Pull) and Export (Push) operations in all modes.
    - Implemented `runDBMigration` to handle direct database-to-database data transfer (MySQL <-> SQLite).
    - Updated Admin UI to dynamically display available migration targets based on current mode and configuration.
    - Added strict environment checks for PDO extensions in `auth.php` and `health_check.php` to prevent fatal errors.
"""

log_1345_zh_escaped = "\n### [13:45] \u5168\u65b9\u4f4d\u8cc7\u6599\u9077\u79fb\u7cfb\u7d71\n- **\u4efb\u52d9**: \u5be6\u4f5c\u652f\u63f4\u6a94\u6848\u7cfb\u7d71\u3001MySQL \u8207 SQLite \u4e09\u65b9\u4e92\u8f49\u7684\u5f37\u5927\u9077\u79fb\u7cfb\u7d71\u3002\n- **\u5be6\u4f5c**:\n    - \u5f37\u5316 `admin/tool_migrate.php`\uff0c\u5728\u6240\u6709\u6a21\u5f0f\u4e0b\u7686\u652f\u63f4\u532f\u5165 (Pull) \u8207\u532f\u51fa (Push) \u64cd\u4f5c\u3002\n    - \u5be6\u4f5c `runDBMigration` \u51fd\u6578\uff0c\u8655\u7406\u8cc7\u6599\u5eab\u5c0d\u8cc7\u6599\u5eab (MySQL <-> SQLite) \u7684\u76f4\u63a5\u8cc7\u6599\u50b3\u8f38\u3002\n    - \u66f4\u65b0\u5fae\u53f0\u4ecb\u9762\uff0c\u6839\u64da\u7576\u524d\u6a21\u5f0f\u8207\u8a2d\u5b9a\u52d5\u614b\u986f\u793a\u53ef\u7528\u7684\u9077\u79fb\u76ee\u6a19\u3002\n    - \u5728 `auth.php` \u8207 `health_check.php` \u4e2d\u52a0\u5165\u56b4\u683c\u7684 PDO \u64f4\u5145\u6aa2\u67e5\uff0c\u9632\u6b62\u56e0\u74b0\u5883\u4e0d\u652f\u63f4\u5c0e\u81f4\u7684\u81f4\u547d\u932f\u8aa4\u3002\n"

try:
    with codecs.open('HISTORY.md', 'r', 'utf-8-sig') as f:
        content = f.read()

    # Find position of 2026-02-01
    start_pos = content.find('## 2026-02-01')
    if start_pos != -1:
        base_content = content[:start_pos]
        
        zh_1230 = log_1230_zh_escaped.encode('utf-8').decode('unicode-escape')
        zh_1345 = log_1345_zh_escaped.encode('utf-8').decode('unicode-escape')
        
        full_day_en = log_1230_en + log_1345_en
        full_day_zh = zh_1230 + zh_1345
        
        final_content = base_content + '## 2026-02-01\n\n' + full_day_en + '\n---\n\n## 2026-02-01 (繁體中文)\n' + full_day_zh + '\n'
        
        with codecs.open('HISTORY.md', 'w', 'utf-8-sig') as f:
            f.write(final_content)
        print("HISTORY.md recovered and updated.")
    else:
        print("Could not find date header.")

except Exception as e:
    print(f"Error: {e}")