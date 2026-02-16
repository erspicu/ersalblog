<?php
/**
 * MessageBoard Admin Auth - 核心驗證引擎
 */

// 1. 設定安全的 Session (必須在 session_start 之前)
ini_set('session.cookie_httponly', 1);
session_name('MB_ADMIN_SESS');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// 2. 啟動 Session
session_start();

// 3. 載入輔助函式與設定
require_once __DIR__ . '/system_helper.php';

function getMBCSRFToken() {
    if (empty($_SESSION['mb_csrf_token'])) $_SESSION['mb_csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['mb_csrf_token'];
}

function verifyMBCSRFToken($token) {
    if (!isset($_SESSION['mb_csrf_token']) || empty($token)) return false;
    return ($_SESSION['mb_csrf_token'] === $token);
}

function validateMBCSRFRequest() {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyMBCSRFToken($token)) {
        header('HTTP/1.1 403 Forbidden');
        die("Invalid CSRF Token. Request denied.");
    }
}

function mb_is_logged_in() {
    return isset($_SESSION['mb_admin_logged_in']) && $_SESSION['mb_admin_logged_in'] === true;
}

/**
 * 從 config.js 獲取真實的運行模式
 */
function mb_get_real_mode() {
    $js_file = __DIR__ . '/../config/config.js';
    if (file_exists($js_file)) {
        $c = file_get_contents($js_file);
        if (preg_match("/mode:\s*'([^']+)'/", $c, $m)) return $m[1];
    }
    return 'local';
}

function mb_require_login() {
    global $mb_admin_pass;
    if (!mb_is_logged_in()) { header("Location: login.php"); exit; }
    
    // 同步真實模式到 Session，確保設定變更後立即生效
    $_SESSION['mb_admin_mode'] = mb_get_real_mode();
    
    // 強制修改 1234 弱密碼
    if ($mb_admin_pass === '1234' && basename($_SERVER['PHP_SELF']) !== 'setup.php') {
        header("Location: setup.php?msg=force_change"); exit;
    }
}

function mb_login($username, $password, $mode) {
    global $mb_admin_user, $mb_admin_pass;
    
    $isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
    $fingerprint = getMBSystemFingerprint();
    $authenticated = false;

    // 1. Localhost 通行證
    if ($isLocal && $password === '1234') $authenticated = true;
    
    // 2. 帳號密碼驗證
    if ($username === $mb_admin_user) {
        if ($mb_admin_pass === '1234') {
            if ($password === '1234') $authenticated = true;
        } elseif (substr($mb_admin_pass, 0, 4) === '$2y$') {
            // 已加密：密碼 + 主機特徵碼 比對
            if (password_verify($password . $fingerprint, $mb_admin_pass)) $authenticated = true;
        } else {
            // 明文
            if ($password === $mb_admin_pass) $authenticated = true;
        }
    }

    if ($authenticated) {
        session_regenerate_id(true);
        $_SESSION['mb_admin_logged_in'] = true;
        $_SESSION['mb_admin_user'] = $username;
        $_SESSION['mb_admin_mode'] = $mode;
        getMBCSRFToken();
        return true;
    }
    return false;
}
