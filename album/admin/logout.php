<?php
require_once 'auth.php';
// 毀掉目前這個 session_name (ALBUM_ADMIN_SESS) 下的所有資料
session_destroy();
header('Location: login.php');
exit;
