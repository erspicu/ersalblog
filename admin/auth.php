<?php
// admin/auth.php

// 設定安全的 Session Cookie
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
// PHP 7.3+ 支援 SameSite 屬性
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

session_start();

// 引入系統輔助函式
require_once __DIR__ . '/system_helper.php';

// 初始化多語系支援
require_once __DIR__ . '/lang_init.php';

// 引入上層的設定檔
require_once __DIR__ . '/../config.php';

// --- CSRF 防禦機制 ---

/**
 * 生成或獲取現有的 CSRF Token
 */
function getCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 驗證請求中的 CSRF Token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 快速驗證 POST 請求中的 CSRF
 */
function validateCSRFRequest() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($token)) {
        header('HTTP/1.1 403 Forbidden');
        die("Invalid CSRF Token. Request denied.");
    }
}

// 初始化資料庫連線 (供後台全域使用)
$pdo = null;
$dbConnectionError = null;

function connectAdminDB() {
    global $dbConfig, $sqlite_path, $pdo, $dbConnectionError;
    
    $source = getAdminSource();

    if (!extension_loaded('pdo')) {
        $dbConnectionError = "PHP PDO Extension missing";
        return;
    }
    
    try {
        if ($source === 'sqlite') {
            if (isset($sqlite_path) && !empty($sqlite_path)) {
                $target = __DIR__ . '/../' . $sqlite_path;
                $pdo = new PDO("sqlite:" . $target, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } else {
                throw new Exception("SQLite path not configured.");
            }
        } elseif ($source === 'db') {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
    } catch (Exception $e) {
        $dbConnectionError = $e->getMessage();
    }
}

// Attempt connection based on session (or default)
connectAdminDB();


/**
 * 檢查是否已登入
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * 強制要求登入，否則轉導至登入頁
 */
function requireLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * 嘗試登入
 */
function login($username, $password, $dataSource = 'db') {
    global $adminConfig;
    
    // 簡單的明文比對 (建議未來改用 password_verify)
    if ($username === $adminConfig['username'] && $password === $adminConfig['password']) {
        // 安全強化：登入成功後重生 Session ID
        session_regenerate_id(true);
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $username;
        $_SESSION['admin_source'] = $dataSource;
        // 登入後重置 CSRF Token 以增加安全性
        getCSRFToken(); 
        return true;
    }
    return false;
}

/**
 * 取得目前管理模式
 */
function getAdminSource() {
    return $_SESSION['admin_source'] ?? 'db';
}

/**
 * 登出
 */
function logout() {
    session_unset();
    session_destroy();
}
?>
