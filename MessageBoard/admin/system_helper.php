<?php
/**
 * MessageBoard Admin System Helper
 */
session_start();

require_once __DIR__ . '/../config/config.php';

// 語系初始化
function mb_get_lang() {
    if (isset($_GET['lang'])) {
        $lang = ($_GET['lang'] === 'en_US') ? 'en_US' : 'zh_TW';
        $_SESSION['mb_lang'] = $lang;
        return $lang;
    }
    if (isset($_SESSION['mb_lang'])) return $_SESSION['mb_lang'];
    $lang = 'zh_TW'; // 預設
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $lang = (strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'zh') !== false) ? 'zh_TW' : 'en_US';
    }
    $_SESSION['mb_lang'] = $lang;
    return $lang;
}

// 取得所有可用的語系檔
function mb_get_available_langs() {
    $langs = [];
    $langPath = __DIR__ . '/../langs/';
    $files = glob($langPath . 'admin-*.php');
    
    foreach ($files as $file) {
        // 從檔名提取語系代碼，例如 admin-zh_TW.php -> zh_TW
        if (preg_match('/admin-([^.]+)\.php$/', basename($file), $m)) {
            $code = $m[1];
            $label = ($code === 'zh_TW') ? '繁體中文' : (($code === 'en_US') ? 'English' : $code);
            $langs[$code] = $label;
        }
    }
    return $langs;
}

$mb_lang_code = mb_get_lang();
$mb_lang_data = require __DIR__ . "/../langs/admin-" . $mb_lang_code . ".php";

function __mb($key) {
    global $mb_lang_data;
    return isset($mb_lang_data[$key]) ? $mb_lang_data[$key] : $key;
}

// 取得 config.js 中的預設模式
function mb_get_default_mode() {
    $js_file = __DIR__ . '/../config/config.js';
    if (file_exists($js_file)) {
        $content = file_get_contents($js_file);
        if (preg_match("/mode:\s*'([^']+)'/", $content, $m)) {
            return $m[1];
        }
    }
    return 'local';
}

function mb_require_login() {
    if (!isset($_SESSION['mb_admin_logged_in']) || $_SESSION['mb_admin_logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }
}

// 診斷環境狀態
function mb_check_status($mode) {
    $res = ['ok' => true, 'error' => ''];

    if ($mode === 'local') {
        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir)) {
            if (!mkdir($dataDir, 0777, true)) {
                return ['ok' => false, 'code' => 'DIR_NOT_WRITABLE', 'error' => '無法建立 data 目錄，請檢查權限'];
            }
        }
        if (!is_writable($dataDir)) {
            return ['ok' => false, 'code' => 'DIR_NOT_WRITABLE', 'error' => 'data 目錄不可寫入'];
        }
    } else {
        // GAS 模式檢查 ... (保持不變)
        $js_file = __DIR__ . '/../config/config.js';
        $content = file_exists($js_file) ? file_get_contents($js_file) : '';
        if (strpos($content, 'YOUR_GAS_ID') !== false || !preg_match("/gas_url:\s*'https:\/\/[^']+'/", $content)) {
            return ['ok' => false, 'code' => 'GAS_CONFIG_MISSING', 'error' => 'GAS URL 尚未設定'];
        }
    }
    return $res;
}

/**
 * 取得環境診斷資訊
 */
function mb_get_env_diagnostics() {
    $results = [];
    
    // 1. PHP 版本
    $results['php_version'] = [
        'label' => 'PHP 版本',
        'value' => PHP_VERSION,
        'pass' => version_compare(PHP_VERSION, '5.6.0', '>='),
        'hint' => '建議使用 PHP 7.4 或以上版本以獲得最佳效能與安全性。'
    ];

    // 2. PDO 支援
    $pdo_exists = extension_loaded('pdo');
    $results['pdo'] = [
        'label' => 'PDO 擴充功能',
        'value' => $pdo_exists ? '已安裝' : '未安裝',
        'pass' => $pdo_exists,
        'hint' => '這是連接所有資料庫的基礎。'
    ];

    // 3. SQLite 支援
    $sqlite_exists = extension_loaded('pdo_sqlite');
    $results['sqlite'] = [
        'label' => 'PDO SQLite 驅動',
        'value' => $sqlite_exists ? '已安裝' : '未安裝',
        'pass' => $sqlite_exists,
        'hint' => '使用本地儲存模式時必須啟用。'
    ];

    // 4. 目錄寫入權限
    $mb_root = realpath(__DIR__ . '/../');
    $is_writable = is_writable($mb_root);
    $results['writable'] = [
        'label' => '目錄寫入權限',
        'value' => $is_writable ? '可寫入' : '唯讀',
        'pass' => $is_writable,
        'hint' => '系統需要權限來建立資料庫檔案及目錄。'
    ];

    return $results;
}


