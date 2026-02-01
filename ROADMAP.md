# Blog Project Roadmap & Feature Proposals (v2026.02.01)

This document organizes the discussions and evaluations regarding future feature expansions for the BaxerMux Photography Blog. It serves as a strategic reference for long-term development.

---

## 1. Core System Infrastructure

### 1.1 Server-side Pagination (Priority: High)
*   **Reasoning**: As the post count grows, loading all posts via a single JSON request will impact performance and UX.
*   **Recommendation**: Implement `limit` and `offset` in the DB/File APIs. Essential for both SPA and static list generation.

### 1.2 SQLite Support (Status: COMPLETED ✅)
*   **Reasoning**: Perfect balance between SQL power and file-system portability. Enables "Zero Configuration" deployment.
*   **Implementation**: Leveraged existing PDO architecture; implemented via `api_sqlitebase.php` and `DataManager`.

### 1.3 Advanced Database Export & Backup (Status: COMPLETED ✅)
*   **Reasoning**: Data is the soul of the blog. Dual-mode support allows for unique backup strategies.
*   **Implementation**: 
    *   One-click SQL dump and full-site ZIP (database + static resources).
    *   **Hot Backup**: Built-in "DB to File" and "File to DB" bidirectional migration tools.

### 1.4 Security Enhancements (Status: COMPLETED ✅)
*   **Implementation**: 
    *   **CSRF Protection**: Token-based validation for all data-changing actions.
    *   **Rate Limiting**: Login lockout mechanism via `attempts.log` (5 fails / 15 mins).
    *   **Session Hardening**: HttpOnly, SameSite=Strict, and ID regeneration.

---

## 2. Content Creation & Management

### 2.1 Advanced Post Editor (Priority: Medium-High)
*   **Reasoning**: The current `textarea` requires manual HTML input, which is a barrier for non-technical photographers.
*   **Options**: 
    *   **Editor.js (Recommended)**: Modern, block-based UI that fits the minimalist aesthetic.
    *   **TinyMCE/Quill**: Full-featured WYSIWYG for a Word-like experience.

### 2.2 Media Management Library
*   **Reasoning**: Managing photos manually in folders is tedious.
*   **Proposal**: A dedicated UI to browse, search, and reuse uploaded images across different posts.

### 2.3 Drafts & Scheduling
*   **Proposal**: Support "Draft" status for posts and the ability to set future "Publish Dates."

---

## 3. Photography-Centric Features

### 3.1 Automatic Thumbnail Generation (Priority: High)
*   **Reasoning**: Directly loading original high-res photos kills performance (LCP).
*   **Proposal**: Use PHP (GD/Imagick) to generate WebP thumbnails/medium sizes upon upload. Display small versions in lists and full versions in post views.

### 3.2 Integrated Album Service System (Priority: Medium)
*   **Reasoning**: Expands the site from a "Blog" to a "Portfolio."
*   **Implementation**: Either a dedicated `Album` module or a specific "Gallery Post Type" with Grid layouts and Lightbox effects.

### 3.3 Geotagging Map Integration
*   **Proposal**: Visualize photo locations using the existing GPS EXIF data. Integrate Leaflet or OpenStreetMap to show a "Photo Map."

---

## 4. Cloud & External Integration

### 4.1 Flickr Integration (Priority: Medium)
*   **Reasoning**: Saves host bandwidth/storage and utilizes Flickr's global CDN.
*   **Implementation**: Use Flickr API to create a "Photo Picker" in the admin panel to import remote links directly into posts.

### 4.2 Google Sheets as Data Source
*   **Reasoning**: Provides a "Headless CMS" feel for writing on the go.
*   **Proposal**: Best used as an **Import Source** or a **Static Generation Source** (SSG) rather than a real-time database due to API latency.

### 4.3 Remote Database Sync (Cross-Host)
*   **Reasoning**: Conceptually attractive for easy site migration.
*   **Warning**: High risk due to firewall blocks (Port 3306) and security concerns.
*   **Better Alternative**: Implement a custom API-based transfer or stick to the "File Mode Sync" for robust cross-host migration.

---

## 5. UI/UX & Social Engagement

### 5.1 Style & Theme Settings (Priority: Medium)
*   **Reasoning**: Visual "Vibe" is critical for photographers.
*   **Proposal**: 
    *   **Dark Mode**: Essential for making photo colors pop.
    *   **Layout Toggle**: Switch between Grid (Visual) and List (Story) modes.
    *   **CSS Variables**: Use `:root` variables for easy theme customization without rewriting CSS.

### 5.2 Social Interaction
*   **Proposal**: 
    *   **Sharing Buttons**: High impact, low effort.
    *   **Comments**: Use 3rd-party systems like Disqus or Giscus to avoid the complexity of building a secure in-house comment engine.

---

# 部落格專案開發藍圖與功能提案 (v2026.02.01)

此文件整理了關於 BaxerMux 攝影部落格未來功能擴充的討論與評估，作為長期開發的戰略參考。

---

## 1. 核心系統架構

### 1.1 伺服器端分頁 (優先級：高)
*   **理由**：隨著文章數量增加，單次載入所有內容會影響效能與體驗。
*   **建議**：API 應支援 `limit` 與 `offset`。對 SPA 與靜態列表生成皆為必要。

### 1.2 SQLite 支援 (狀態：已完成 ✅)
*   **理由**：兼具 SQL 功能與檔案系統的便攜性，達成「零設定」佈署。
*   **實作**：已實作 `api_sqlitebase.php` 與 `DataManager` 支援，並提供初始化工具。

### 1.3 進階資料庫匯出與備份 (狀態：已完成 ✅)
*   **理由**：數據是部落格的靈魂。雙模架構提供了獨特的備份優勢。
*   **實作**：
    *   實作一鍵產生 SQL 備份與全站 ZIP（含資料庫與靜態資源）。
    *   **資料遷移**：實作了完善的資料庫與檔案系統雙向遷移與還原工具。

### 1.4 安全性強化 (狀態：已完成 ✅)
*   **實作**：
    *   **CSRF 防護**：所有資料變更操作均已加入 Token 驗證。
    *   **登入限制**：實作基於 IP 的 `attempts.log` 鎖定機制（5 次失敗鎖 15 分鐘）。
    *   **Session 強化**：強制 HttpOnly、SameSite=Strict 以及登入後 ID 重生。

---

## 2. 內容創作與管理

### 2.1 進階文章編輯器 (優先級：中高)
*   **理由**：現有的 `textarea` 門檻較高，對非技術背景攝影師不友善。
*   **選項**：
    *   **Editor.js (推薦)**：現代、乾淨的區塊式 UI，符合簡約美學。
    *   **TinyMCE/Quill**：全功能視覺化編輯器，提供類 Word 體驗。

### 2.2 媒體管理庫
*   **理由**：手動管理資料夾內的圖片較為繁瑣。
*   **提案**：建立統一介面瀏覽、搜尋及重複使用已上傳的照片。

### 2.3 草稿與預約發佈
*   **提案**：支援「草稿」狀態與設定未來發佈時間。

---

## 3. 攝影師專屬特性

### 3.1 自動縮圖生成 (優先級：高)
*   **理由**：直接讀取高解析度原圖會導致首屏載入過慢。
*   **提案**：上傳時自動生成 WebP 縮圖。列表顯示小圖，進入文章後才載入大圖。

### 3.2 整合相簿服務系統 (優先級：中)
*   **理由**：將部落格提升為「個人攝影作品集」。
*   **實作**：可作為獨立 `Album` 模組，或特殊的「相簿文章類型」，提供網格佈局與 Lightbox 效果。

### 3.3 地圖整合 (Geotagging)
*   **提案**：利用照片中的 GPS EXIF 資訊，在地圖上標示拍攝地點，建立「攝影地圖」。

---

## 4. 雲端與外部整合

### 4.1 Flickr 整合 (優先級：中)
*   **理由**：節省主機空間、利用全球 CDN 提升速度。
*   **實作**：透過 Flickr API 建立「相簿選擇器」，直接將 Flickr 照片連結匯入文章。

### 4.2 Google Sheets 作為資料來源
*   **理由**：提供「隨時隨地寫作」的便利性。
*   **提案**：適合作為「外部匯入源」或「SSG 的靜態生成源」，不建議作為實時查詢的核心庫。

### 4.3 遠端資料庫同步 (跨主機匯入)
*   **理由**：一鍵遷移網站極具吸引力。
*   **警示**：風險高（防火牆阻擋、安全性）。
*   **替代方案**：建議改用 API 傳輸，或維持現有的「檔案模式同步」作為最穩健的遷移手段。

---

## 5. UI/UX 與社群互動

### 5.1 樣式與主題設定 (優先級：中)
*   **理由**：視覺氛圍是攝影部落格的靈魂。
*   **提案**：
    *   **深色模式**：最能突顯照片色彩。
    *   **佈局切換**：提供網格流與列表流供使用者切換。
    *   **CSS 變數**：利用變數輕鬆更換品牌主題色。

### 5.2 社群互動
*   **提案**：
    *   **分享按鈕**：高回報、低成本的功能。
    *   **評論系統**：建議整合 Disqus 或 Giscus 等第三方系統以維持安全性。

---
**Document Created**: 2026-02-01
**Source**: Discussion between User & Gemini CLI
