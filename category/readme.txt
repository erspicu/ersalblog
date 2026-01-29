# Category Directory

This directory defines the categorization of blog posts.

- Each subdirectory represents a **Category Name**.
- To assign a post to a category, create an empty file inside the category folder with a name matching the post's filename.

### Current Examples:
- **`category/作品分享/`**: Contains post identifiers like `20240727223830`, meaning that specific post belongs to the "作品分享" (Work Sharing) category.
- **`category/開箱分享/`**: Contains `20240505195530`, categorizing it as "開箱分享" (Unboxing).

The `api_filebase.php` and `make_html.php` scripts scan these folders to determine post categories.

---

# Category 目錄

此目錄定義部落格文章的分類。

- 每個子目錄名稱代表一個 **文章分類名稱**。
- 若要將文章歸類，請在對應的分類資料夾內，建立一個與文章檔名一致的檔案。

### 實際範例：
- **`category/作品分享/`**: 內有 `20240727223830` 等檔案，代表該文章屬於「作品分享」分類。
- **`category/開箱分享/`**: 內有 `20240505195530` 等檔案，代表該文章屬於「開箱分享」分類。

`api_filebase.php` 與 `make_html.php` 腳本會掃描這些資料夾，以判斷每篇文章所屬的分類。