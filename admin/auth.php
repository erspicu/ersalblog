<?php
// admin/auth.php
session_start();

// 引入系統輔助函式
require_once __DIR__ . '/system_helper.php';

// 初始化多語系支援
require_once __DIR__ . '/lang_init.php';

// 引入上層的設定檔
require_once __DIR__ . '/../config.php';

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
                $dsn = "sqlite:" . __DIR__ . "/../" . $sqlite_path; // Assume relative to admin/ or absolute? config says "123.sqlite".Usually relative to entry point.
                // Let's try relative to web root (parent of admin)
                // config.php is in root.
                // If script is in admin/, then ../$sqlite_path
                
                // Better approach: use absolute path if possible or ensure path is correct.
                // In api_sqlitebase, we used $sqlite_path directly (assuming it's in same dir as api file).
                // Let's assume $sqlite_path is relative to Project Root.
                
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
        // Do not die here, allow login page to render
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
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $username;
        $_SESSION['admin_source'] = $dataSource; // Store the selected source
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
