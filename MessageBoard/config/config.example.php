<?php
/**
 * MessageBoard Service - PHP Configuration
 * 此檔案負責定義資料庫位置與安全設定
 */

// 資料庫檔案路徑 (相對於 MessageBoard/api/ 的路徑，或是絕對路徑)
// 預設將資料庫放在 MessageBoard/data/ 之下，實現完全脫鉤
$mb_sqlite_path = __DIR__ . '/../data/messages.sqlite3';

// 管理員帳號設定 (用於後台管理或 API 驗證)
$mb_admin_user = 'admin';
$mb_admin_pass = '1234'; // 建議正式部署時修改
