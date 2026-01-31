# Contents Directory

This directory stores the core data of the blog system.

- `index_post.txt`: The primary metadata file (database) for all blog posts. Each line follows the format: `Date|Filename|Title|Tags|Description`. Multiple tags can be separated by a comma (`,`).
- `post_files/`: A subdirectory containing the original HTML content fragments for each blog post.

These files are used by `make_html.php` to generate full static HTML pages and by `api_filebase.php` to serve dynamic content via JSON.

---

# Contents 目錄

此目錄存放部落格系統的核心資料。

- `index_post.txt`: 所有部落格文章的主要元資料檔案（資料庫）。每行格式為：`日期|檔名|標題|標籤|描述`。多筆標籤可以使用逗號（`,`）分隔。
- `post_files/`: 包含每篇部落格文章原始 HTML 內容片段的子目錄。

這些檔案供 `make_html.php` 用於生成完整的靜態 HTML 頁面，並由 `api_filebase.php` 用於透過 JSON 提供動態內容。
