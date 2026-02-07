# Album Static Assets

存放相簿服務的所有靜態資源與樣板。

## 目錄結構
- **css/**: 包含 Bootstrap 5、Bootstrap Icons 與自定義樣式。
- **js/**: 
    - `album.js`: 核心邏輯，包含 SPA 渲染、Modal 觸發、分頁控制與分享功能。
    - `exif.js`: 讀取照片元數據的第三方套件。
- **fonts/**: Bootstrap Icons 的字體檔案。

## 核心樣板
- **album_template.html**: 採用 `<template>` 標籤定義組件化結構，供 PHP 與 JS 共用渲染。
