<?php
$blog_title = "Baxermux的攝影Blog";//Blog網站標題
$blog_description = ""; //Blog SEO描述屬性
$blog_introduce = "";//描述一下你的blog用途或是特色
$blog_preview = ""; //Blog 預覽圖網址
$site_url = "https://example.com/blog/"; // 網站網址
$blog_lang = "zh_TW"; // 部落格語系 (zh_TW, en_US)
$blog_timezone = "Asia/Taipei"; // 系統時區
$posts_per_page = 10; // 每頁文章數量 (SPA 模式)
$album_path = "album/"; // 相簿服務相對路徑 (相對於 Blog 根目錄)

$sqlite_path = "blog.sqlite3"; // SQLite 資料庫檔案名稱 (若使用 SQLite 模式)

$dbConfig = array(
    // --- 資料庫連線設定 ---
    'host'     => 'localhost',      // 資料庫主機 (通常是 localhost)
    'dbname'   => '?',   // 資料庫名稱 (請改成您的)
    'username' => '?',           // 資料庫帳號
    'password' => '?',       // 資料庫密碼 
    'charset'  => 'utf8mb4',        // 編碼 (建議用 utf8mb4 支援 Emoji)

    // --- 其他全域設定 ---
    'debug_mode' => true,           // true: 顯示錯誤訊息 (開發用), false: 隱藏 (上線用)
);

// --- 後台管理員設定 ---
$adminConfig = array(
    'username' => 'admin',
    'password' => '1234', // 預設密碼。首次登入後系統將強制要求修改，並結合主機特徵進行雜湊加密，加密後此處將存儲雜湊字串而非明文。
);

// --- AI 輔助功能設定 ---
$aiConfig = array(
    'enabled'    => false,              // 是否啟用 AI 功能 (true/false)
    'provider'   => 'gemini',           // 目前預設為 gemini
    'api_key'    => '',                 // Google AI Studio 申請的 API Key
    'model'      => 'gemini-3-flash-preview', // 使用的模型名稱 (Gemini 3 Flash Preview)
    'max_tokens' => 1000,               // 限制 AI 回傳的最大長度
);

//設定時區
date_default_timezone_set('Asia/Taipei');
?>