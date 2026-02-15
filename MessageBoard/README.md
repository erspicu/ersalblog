# MessageBoard Service (留言板服務)

本專案為 Blog 的獨立子專案，旨在提供類似 Disqus 的留言評論功能。設計核心為「完全脫鉤」與「高度移植性」，支援在多種環境下獨立運作。

## 核心特色
1. **完全脫鉤**：不依賴 Blog 主系統，可獨立部署。
2. **混合存儲**：支援本地 SQLite 與雲端 Google Sheets。
3. **平台化設計**：單一插件支援多站點、多頁面管理。
4. **主題切換**：支援明亮 (Default) 與深色 (Dark) 主題。

## 目錄導覽
- `admin/`: 管理後台，負責留言審核與系統配置。
- `api/`: 本地模式 (SQLite) 的後端處理程式。
- `config/`: 服務設定檔 (PHP 與 JavaScript)。
- `data/`: 本地 SQLite 資料庫存放區 (Git 已忽略)。
- `gas/`: Google Apps Script 雲端後端原始碼。
- `langs/`: 多語系定義檔 (後台與插件 UI)。
- `static/`: 前端核心組件、樣式與儲存適配器。
