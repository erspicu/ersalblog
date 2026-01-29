<?php
$dbConfig = [
    // --- 資料庫連線設定 ---
    'host'     => 'localhost',      // 資料庫主機 (通常是 localhost)
    'dbname'   => '?',   // 資料庫名稱 (請改成您的)
    'username' => '?',           // 資料庫帳號
    'password' => '?',       // 資料庫密碼 
    'charset'  => 'utf8mb4',        // 編碼 (建議用 utf8mb4 支援 Emoji)

    // --- 其他全域設定 ---
    'debug_mode' => true,           // true: 顯示錯誤訊息 (開發用), false: 隱藏 (上線用)
    'site_url'   => 'https://www.baxermux.org/ersalblog/' // 網站網址
];

//設定時區
date_default_timezone_set('Asia/Taipei');
?>