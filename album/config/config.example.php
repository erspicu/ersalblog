<?php
/**
 * Album Admin Configuration Example
 * Copy this file to config.php and modify settings.
 */

// --- 後台管理員設定 ---
$albumAdminConfig = array(
    'username' => 'admin',
    'password' => 'YOUR_PASSWORD_HERE', // 請修改此密碼
    'session_secret' => 'CHANGE_ME_TO_RANDOM_STRING_ALBUM'
);

//設定時區
date_default_timezone_set('Asia/Taipei');
?>
