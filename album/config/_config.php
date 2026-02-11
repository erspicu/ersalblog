<?php
/**
 * Album Admin Configuration
 */

// --- 後台管理員設定 ---
$albumAdminConfig = array(
    'username' => 'admin',
    'password' => '123456', // 預設密碼 (開發用)
    'session_secret' => 'ALBUM_ADMIN_SECRET_KEY_123'
);

//設定時區
date_default_timezone_set('Asia/Taipei');
?>
