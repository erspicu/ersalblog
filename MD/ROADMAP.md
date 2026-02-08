# Blog Project Roadmap & Feature Proposals (v2026.02.08)

This document organizes the discussions and evaluations regarding future feature expansions for the BaxerMux Photography Blog. It serves as a strategic reference for long-term development.

---

## 1. Core System Infrastructure

### 1.1 Hybrid Pagination System (Status: COMPLETED ✅)
*   **Implementation**: 
    *   **Server-side (PHP)**: Implemented `page` parameter in `api_*.php` to slice data and lazy-load content files.
    *   **Client-side (JSON Mode)**: Implemented browser-side slicing in `blog.js` for 100% backend-less environments.

### 1.2 Standalone Album Service & Media Library (Status: COMPLETED ✅)
*   **Reasoning**: Decoupling photography assets from blog posts while providing a unified management experience.
*   **Implementation**: 
    *   **SPA Architecture**: Built a database-free, JSON-driven standalone album service.
    *   **Multi-spec Thumbnails**: Automated generation of XL, L, M, Standard, and XS JPG thumbnails for optimized loading and previews.
    *   **Integrated Selector**: Real-time "Album Picker" within the blog post editor, supporting multi-size insertion and direct uploads.
    *   **Admin Panel**: Independent management interface for album/photo CRUD and settings.

### 1.3 SQLite & Database Tools (Status: COMPLETED ✅)
*   **Implementation**: PDO-based SQLite 3 support, one-click SQL dumps, and bidirectional "File <-> DB" migration.

### 1.4 Security & Stability (Status: COMPLETED ✅)
*   **Implementation**: CSRF protection, Login rate limiting, Script Tag protection (XSS neutralization), and strict path traversal filtering.

### 1.5 Architecture & API Refactoring (Status: COMPLETED ✅)
*   **Implementation**: Unified API core logic, encapsulated `StaticGenerator` class, and implemented hash-based incremental builds.

### 1.6 Pure Static Mode & PHP 5.x (Status: COMPLETED ✅)
*   **Implementation**: Single `data.json` source for static hosting and systematic syntax downgrading for legacy compatibility.

---

## 2. Content Creation & Management

### 2.1 Advanced Post Editor (Status: COMPLETED ✅)
*   **Implementation**: TinyMCE 6 integration with custom PageBreak and i18n.

### 2.2 Configuration Management (Status: COMPLETED ✅)
*   **Implementation**: Admin **Settings Page** for both Blog and Album services.

---

## 3. Future & Social Engagement

### 3.1 Geotagging Map Integration (Priority: Low)
*   **Proposal**: Visualize photo locations using GPS EXIF data on an interactive map.

### 3.2 Search Enhancement (Priority: Medium)
*   **Proposal**: Advanced keyword search optimization (currently relying on Google CSE).

---

# 部落格專案開發藍圖與功能提案 (v2026.02.08)

---

## 1. 核心系統架構

### 1.1 混合式分頁系統 (狀態：已完成 ✅)
*   **實作**：支援伺服器端 (PHP) 與客戶端 (JSON) 兩種切片模式，優化載入效能。

### 1.2 獨立相簿服務與媒體庫整合 (狀態：已完成 ✅)
*   **核心價值**：攝影素材獨立管理，並與文章編輯器深度整合。
*   **實作**：
    *   **SPA 架構**：建立無需資料庫、JSON 驅動的相簿服務。
    *   **自動縮圖系統**：自動生成五種規格 (XL, L, M, Std, XS) 的 JPG 縮圖，兼顧相容性與效能。
    *   **編輯器整合**：在文章編輯器實作「相簿挑選器」，支援多尺寸插入與 Modal 內即時上傳。
    *   **獨立後台**：專屬的相簿管理介面 (CRUD、封面設定、前端設定)。

### 1.3 SQLite 支援與資料工具 (狀態：已完成 ✅)
*   **實作**：實作零設定 SQLite 部署、全站 ZIP 備份與雙向資料遷移工具。

### 1.4 安全性與穩定性 (狀態：已完成 ✅)
*   **實作**：CSRF 防護、登入頻率限制、以及**文章 Script 標籤轉義保護 (防 XSS)**。

### 1.5 API 與架構重構 (狀態：已完成 ✅)
*   **實作**：統一 API 核心邏輯，並支援增量建置與雜湊比對。

### 1.6 PHP 5.x 相容性 (狀態：已完成 ✅)
*   **實作**：全站語法降級，確保在 AppServ 等舊版環境穩定執行。

---

## 2. 內容創作與管理

### 2.1 視覺化文章編輯器 (狀態：已完成 ✅)
*   **實作**：整合 TinyMCE 6 並實作「相簿挑選器」擴充。

### 2.2 系統設定圖形化 (狀態：已完成 ✅)
*   **實作**：部落格與相簿服務皆具備圖形化設定頁面。

---

## 3. 未來規劃與社交互動

### 3.1 照片地圖整合 (優先級：低)
*   **提案**：利用照片 EXIF 中的 GPS 資訊，在互動式地圖上標註拍攝地點。

### 3.2 搜尋功能優化 (優先級：中)
*   **提案**：改善目前依賴 Google CSE 的現狀，實作更精確的本地端搜尋。

---
**Last Updated**: 2026-02-08 (via Linux `date`)
**Recorded by**: Gemini CLI Discussion
