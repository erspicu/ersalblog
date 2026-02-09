<?php

$blog_title = "Baxermux的攝影Blog";//Blog網站標題
$blog_description = "BaxerMux的攝影Blog，主要是介紹攝影器材開箱心得與創作分享。"; //Blog SEO描述屬性
$blog_introduce = "這個blog就拿來放置一些攝影的作品集好了.";//描述一下你的blog用途或是特色
$blog_preview = "https://www.baxermux.org/ersalblog/preview/BaxerMuxBlog.jpg";
$site_url = "https://www.baxermux.org/ersalblog/"; // 網站網址
$blog_lang = "zh_TW"; // 部落格語系
$blog_timezone = "Asia/Taipei"; // 系統時區
$posts_per_page = 12; // 每頁文章數量
$album_path = "album/"; // 相簿服務相對路徑 (相對於 Blog 根目錄)

$sqlite_path = "123456.sqlite3"; //sqlite檔名與路徑位置為機敏資訊,如果此變數有填入資訊非空白,則後臺會擴展sqlite使用相關選項

$dbConfig = array(
    // --- 資料庫連線設定 ---
    'host'     => 'localhost',      // 資料庫主機 (通常是 localhost)
    'dbname'   => '',   // 資料庫名稱 (請改成您的)
    'username' => '',           // 資料庫帳號 (AppServ 預設通常是 root)
    'password' => '',       // 資料庫密碼 (請填入您 AppServ 設定的密碼)
    'charset'  => 'utf8mb4',        // 編碼 (建議用 utf8mb4 支援 Emoji)

    // --- 其他全域設定 ---
    'debug_mode' => true,           // true: 顯示錯誤訊息 (開發用), false: 隱藏 (上線用)
    
);

// --- 後台管理員設定 ---
$adminConfig = array(
    'username' => 'admin',           // 管理員帳號
    'password' => '22345678',          // 管理員密碼 (建議使用 Hash，此處為明文示範，請自行修改)
    'session_secret' => 'ersalblog_secret_key_2026' // Session 驗證密鑰
);

//設定時區
date_default_timezone_set($blog_timezone);
?>