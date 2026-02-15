<?php
/**
 * MessageBoard Admin System Helper - PHP 5.x Compatible
 */
require_once __DIR__ . '/../config/config.php';

// 取得當前語系代碼
function mb_get_lang() {
    if (isset($_GET['lang'])) {
        $lang = ($_GET['lang'] === 'en_US') ? 'en_US' : 'zh_TW';
        $_SESSION['mb_lang'] = $lang;
        return $lang;
    }
    if (isset($_SESSION['mb_lang'])) return $_SESSION['mb_lang'];
    $lang = 'zh_TW';
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $lang = (strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'zh') !== false) ? 'zh_TW' : 'en_US';
    }
    $_SESSION['mb_lang'] = $lang;
    return $lang;
}

// 載入語系包
$mb_lang_code = mb_get_lang();
$mb_lang_data = require __DIR__ . "/../langs/admin-" . $mb_lang_code . ".php";

function __mb($key) {
    global $mb_lang_data;
    return isset($mb_lang_data[$key]) ? $mb_lang_data[$key] : $key;
}

function mb_get_available_langs() {
    $langs = array();
    $files = glob(__DIR__ . '/../langs/admin-*.php');
    foreach ($files as $file) {
        if (preg_match('/admin-([^.]+)\.php$/', basename($file), $m)) {
            $code = $m[1];
            $langs[$code] = ($code === 'zh_TW') ? '繁體中文' : (($code === 'en_US') ? 'English' : $code);
        }
    }
    return $langs;
}

// 取得主機唯一識別碼 (Fingerprint)
function getMBSystemFingerprint() {
    $info = '';
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $info = getenv('COMPUTERNAME') . getenv('PROCESSOR_IDENTIFIER');
    } else {
        if (file_exists('/etc/machine-id')) {
            $info = file_get_contents('/etc/machine-id');
        } elseif (file_exists('/var/lib/dbus/machine-id')) {
            $info = file_get_contents('/var/lib/dbus/machine-id');
        } else {
            $info = php_uname('n') . PHP_OS;
        }
    }
    return hash('sha256', 'mb_ersalblog_' . trim($info));
}

// 隨機字元生成回退方案
if (!function_exists('random_bytes')) {
    function random_bytes($length) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($bytes !== false && $strong === true) return $bytes;
        }
        $bytes = '';
        for ($i = 0; $i < $length; $i++) $bytes .= chr(mt_rand(0, 255));
        return $bytes;
    }
}

// 診斷環境狀態
function mb_check_status($mode) {
    if ($mode === 'local') {
        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir)) {
            if (!mkdir($dataDir, 0777, true)) {
                return array('ok' => false, 'error' => 'Data dir not writable');
            }
        }
        if (!is_writable($dataDir)) {
            return array('ok' => false, 'error' => 'Data dir not writable');
        }
    } else {
        $js_file = __DIR__ . '/../config/config.js';
        if (file_exists($js_file)) {
            $content = file_get_contents($js_file);
            if (strpos($content, 'YOUR_GAS_ID') !== false || !preg_match("/gas_url:\s*'https:\/\/[^']+'/", $content)) {
                return array('ok' => false, 'error' => 'GAS URL not set');
            }
        }
    }
    return array('ok' => true);
}

function mb_get_env_diagnostics() {
    $results = array();
    $results['php_version'] = array(
        'label' => __mb('diag_php_ver'), 
        'value' => PHP_VERSION, 
        'pass' => version_compare(PHP_VERSION, '5.6.0', '>='), 
        'hint' => __mb('diag_hint_php')
    );
    $pdo_exists = extension_loaded('pdo');
    $results['pdo'] = array(
        'label' => __mb('diag_pdo'), 
        'value' => $pdo_exists ? __mb('diag_installed') : __mb('diag_not_installed'), 
        'pass' => $pdo_exists, 
        'hint' => __mb('diag_hint_pdo')
    );
    $sqlite_exists = extension_loaded('pdo_sqlite');
    $results['sqlite'] = array(
        'label' => __mb('diag_sqlite'), 
        'value' => $sqlite_exists ? __mb('diag_installed') : __mb('diag_not_installed'), 
        'pass' => $sqlite_exists, 
        'hint' => __mb('diag_hint_sqlite')
    );
    $mb_root = realpath(__DIR__ . '/../');
    $is_writable = is_writable($mb_root);
    $results['writable'] = array(
        'label' => __mb('diag_writable'), 
        'value' => $is_writable ? __mb('diag_writable_ok') : __mb('diag_writable_no'), 
        'pass' => $is_writable, 
        'hint' => __mb('diag_hint_writable')
    );
    return $results;
}
