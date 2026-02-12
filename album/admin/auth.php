<?php
// album/admin/auth.php

// 設定安全的 Session Cookie
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();

// 引入設定檔
$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    die("Album configuration file (config.php) not found. Please copy config.example.php to config.php in the config/ directory.");
}
require_once $configFile;

// --- 多語系支援框架 ---

// 1. 決定目前語系
// 優先權: GET 參數 (切換) > Session (登入後) > Config (全域預設) > 預設繁中
if (isset($_GET['lang'])) {
    $currentLang = $_GET['lang'];
    $_SESSION['album_admin_lang'] = $currentLang;
} else {
    $currentLang = isset($_SESSION['album_admin_lang']) ? $_SESSION['album_admin_lang'] : (isset($album_lang) ? str_replace('-', '_', $album_lang) : 'zh_TW');
}

// 2. 載入對應翻譯檔 (Prefix: admin-)
$langFile = __DIR__ . '/../langs/admin-' . $currentLang . '.php';
if (!file_exists($langFile)) {
    $langFile = __DIR__ . '/../langs/admin-zh_TW.php'; // Fallback
}
$L = include $langFile;

/**
 * 翻譯輔助函式
 */
function __($key, $default = '') {
    global $L;
    return isset($L[$key]) ? $L[$key] : ($default ? $default : $key);
}

// 如果 config.php 內沒有正確設定時區，則設定預設值
if (!isset($album_timezone)) {
    date_default_timezone_set('Asia/Taipei');
}

// --- CSRF 防禦機制 ---

function getCSRFToken() {
    if (empty($_SESSION['album_csrf_token'])) {
        $_SESSION['album_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['album_csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['album_csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['album_csrf_token'], $token);
}

function validateCSRFRequest() {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : (isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '');
    if (!verifyCSRFToken($token)) {
        header('HTTP/1.1 403 Forbidden');
        die("Invalid CSRF Token.");
    }
}

/**
 * 檢查是否已登入
 */
function isAlbumAdminLoggedIn() {
    return isset($_SESSION['album_admin_logged_in']) && $_SESSION['album_admin_logged_in'] === true;
}

/**
 * 強制要求登入
 */
function requireAlbumLogin() {
    if (!isAlbumAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * 嘗試登入
 */
function albumLogin($username, $password) {
    global $albumAdminConfig;
    
    if ($username === $albumAdminConfig['username'] && $password === $albumAdminConfig['password']) {
        session_regenerate_id(true);
        $_SESSION['album_admin_logged_in'] = true;
        $_SESSION['album_admin_user'] = $username;
        getCSRFToken(); 
        return true;
    }
    return false;
}

/**
 * 登出
 */
function albumLogout() {
    // 僅清除 album 相關 session，避免影響 blog admin (如果共用 domain)
    unset($_SESSION['album_admin_logged_in']);
    unset($_SESSION['album_admin_user']);
    unset($_SESSION['album_csrf_token']);
}
?>
