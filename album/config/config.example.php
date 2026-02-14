<?php
/**
 * Baxermux Album Configuration Example
 * Copy this file to config.php and modify settings.
 */

// --- 後台管理員設定 ---
$albumAdminConfig = array(
    'username' => 'admin',
    'password' => '1234', // 預設密碼。首次登入後系統將強制要求修改，並結合主機特徵進行雜湊加密，加密後此處將存儲雜湊字串而非明文。
);

// --- 全域相簿設定 (SEO 與 Header 使用) ---
$album_title = "Baxermux的相簿";
$album_description = "ersalblog的延伸子專案相簿服務。";
$album_introduce = "放一些Blog用到的素材照片.";
$album_preview = "https://www.baxermux.org/ersalblog/album/BaxerMuxAlbum.jpg"; 
$album_site_url = "https://www.baxermux.org/ersalblog/album/"; 

// 網站語言 (對外 HTML 標籤使用，建議格式: zh-TW, en-US)
$album_lang = "zh-TW";

// 系統時區 (對內 PHP 運算使用)
$album_timezone = "Asia/Taipei";

// 設定系統時區
date_default_timezone_set($album_timezone);
?>
