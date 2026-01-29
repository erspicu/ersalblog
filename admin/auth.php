<?php
// admin/auth.php
session_start();

// 引入上層的設定檔
require_once __DIR__ . '/../config.php';

// 初始化資料庫連線 (供後台全域使用)
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("後台資料庫連線失敗: " . $e->getMessage());
}

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
function login($username, $password) {
    global $adminConfig;
    
    // 簡單的明文比對 (建議未來改用 password_verify)
    if ($username === $adminConfig['username'] && $password === $adminConfig['password']) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $username;
        return true;
    }
    return false;
}

/**
 * 登出
 */
function logout() {
    session_unset();
    session_destroy();
}
?>
