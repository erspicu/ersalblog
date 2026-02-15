# 部落格架構優化待辦事項 (TODO List)

此文件記錄了系統架構的改進建議與任務狀態。

---

## 1. 核心邏輯優化 (Core Logic)
*   [x] **API Consolidation**: 統一 File/SQLite/MySQL 核心邏輯。
*   [x] **Hybrid Pagination**: 實作伺服器端與客戶端混合分頁。
*   [x] **SSG Refactoring**: 類別化靜態生成器與路徑修復。

---

## 2. 媒體與社交服務 (Media & Social)
*   [x] **Album Service**: 獨立相簿服務、影像優化與地圖整合。
*   [x] **MessageBoard Service**: 
    *   實作雙模式適配器 (SQLite/GAS)。
    *   開發獨立管理後台與多語系支援。
    *   完成分頁、標題捕捉與帳號安全管理。

---

## 3. 安全性與管理 (Security & Admin)
*   [x] **Settings Refactor**: 分區管理配置與資料夾選擇器。
*   [x] **Script Protection**: 文章 Script 標籤轉義保護。
*   [x] **Backup Tools**: 修正備份工具與路徑問題。

---

## 4. 已完成細項 (History)
*   [x] **Template Decoupling**: 樣板讀取流程解耦。
*   [x] **Placeholder Standardization**: 雙大括號佔位符統一。
*   [x] **Theme System**: 擴充 Pink, Matrix, Dark 主題。
*   [x] **AOT Compilation**: 相簿總管 Blazor AOT 優化。

---

## 5. 待處理與未來規劃 (Future Improvements)
*   [ ] **Search Enhancement**: 改進本地端關鍵字搜尋 (目前依賴 Google CSE)。
*   [ ] **CSS Refactoring**: 整合主題 CSS 變數，減少重複程式碼。
*   [ ] **SEO Optimization**: 強化文章自動摘要生成與 Meta 標籤完整性。
*   [ ] **Automatic Backup Cleanup**: 定期清理過期的全站備份 ZIP。

---
**Last Updated**: 2026-02-16 (via Linux `date`)
**Recorded by**: Gemini CLI Discussion
