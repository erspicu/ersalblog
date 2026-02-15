<?php
// album/admin/auth.php

// 設定安全的 Session Cookie
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_name('ALBUM_ADMIN_SESS');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();

// 引入相簿專屬系統輔助函式 (達成獨立運行)
require_once __DIR__ . '/system_helper.php';

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
    $currentLang = str_replace('-', '_', $_GET['lang']);
    $_SESSION['album_admin_lang'] = $currentLang;
} else {
    $rawLang = isset($_SESSION['album_admin_lang']) ? $_SESSION['album_admin_lang'] : (isset($album_lang) ? $album_lang : 'zh_TW');
    $currentLang = str_replace('-', '_', $rawLang);
}

// 2. 載入對應翻譯檔 (Prefix: admin-)
$langFile = __DIR__ . '/../langs/admin-' . $currentLang . '.php';
if (!file_exists($langFile)) {
    $langFile = __DIR__ . '/../langs/admin-zh_TW.php'; // Fallback
    $currentLang = 'zh_TW';
}
$L = include $langFile;

/**
 * 翻譯輔助函式
 */
function __($key, $default = '') {
    global $L;
    return isset($L[$key]) ? $L[$key] : ($default ? $default : $key);
}

/**
 * 獲取網頁顯示用的語系代碼 (例如 zh-TW)
 */
function getWebLang() {
    global $currentLang;
    return str_replace('_', '-', $currentLang);
}

/**
 * 獲取 JS 語系包路徑 (內部固定用底線檔名)
 */
function getAdminLangJs() {
    global $currentLang;
    $path = "../langs/admin-{$currentLang}.js";
    if (!file_exists(__DIR__ . '/' . $path)) {
        $path = "../langs/admin-zh_TW.js";
    }
    return $path;
}

/**
 * 動態獲取可用語系
 * @param string $prefix 檔案前綴 (例如 'admin-' 或 'install-')
 */
function getAvailableLangs($prefix) {
    $langs = [];
    $langDir = __DIR__ . '/../langs';
    if (is_dir($langDir)) {
        foreach (glob($langDir . '/' . $prefix . '*.php') as $file) {
            $langCode = str_replace([$prefix, '.php'], '', basename($file));
            // 簡單對應顯示名稱 (以後可以從語系檔內讀取一個特定 key)
            $names = [
                'zh_TW' => '繁體中文 (zh-TW)',
                'en_US' => 'English (en-US)'
            ];
            $langs[$langCode] = isset($names[$langCode]) ? $names[$langCode] : $langCode;
        }
    }
    return $langs;
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

    // 強制變更初始密碼
    global $albumAdminConfig;
    if ($albumAdminConfig['password'] === '1234' && basename($_SERVER['PHP_SELF']) !== 'change_password.php') {
        header('Location: change_password.php');
        exit;
    }
}

/**
 * 嘗試登入
 */
function albumLogin($username, $password) {
    global $albumAdminConfig;
    
    $isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
    $fingerprint = getSystemFingerprint();
    $authenticated = false;

    // 1. Localhost 通行證
    if ($isLocal && $password === '1234') {
        $authenticated = true;
    }

    // 2. 帳號密碼校驗
    if ($username === $albumAdminConfig['username']) {
        $storedPassword = $albumAdminConfig['password'];
        if ($storedPassword === '1234') {
            if ($password === '1234') $authenticated = true;
        } elseif (substr($storedPassword, 0, 4) === '$2y$') {
            if (password_verify($password . $fingerprint, $storedPassword)) {
                $authenticated = true;
            }
        } else {
            if ($password === $storedPassword) $authenticated = true;
        }
    }
    
    if ($authenticated) {
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
