# Album Service (相簿服務)

本目錄是 Baxermux 部落格系統的獨立相簿服務模組。

## 目錄功能概述
- **Collection/**: 存放原始照片與結構化描述檔的核心數據庫。
- **api/**: 提供前端所需的 JSON 接口。
- **static/**: 存放 HTML 樣板、CSS 樣式、JavaScript 腳本及第三方套件。
- **view/**: (Git 忽略) 存放生成器輸出的靜態網頁檔案。
- **admin/**: 相簿專用的後台管理介面 (開發中)。
- **langs/**: 多語系支援設定。
- **PHP_LIB/**: 存放相簿服務共用的 PHP 類別庫。

## 核心檔案
- **make_album.php**: 靜態生成器 (SSG)，負責產生 HTML 分頁、詳情頁與各規格縮圖。
- **album.html**: (Git 忽略) 相簿服務的首頁入口。
- **comment.txt**: 整個相簿服務的標題與總體描述。
