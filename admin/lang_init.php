<?php
// admin/lang_init.php
// 負責載入語系設定與定義翻譯函式

// 取得語系檔根目錄
$langBaseDir = dirname(__DIR__) . '/langs';
$availableLangs = array();
$defaultLang = 'zh_TW';

// 掃描可用語系 (僅掃描 admin- 開頭的檔案)
if (is_dir($langBaseDir)) {
    $scan = scandir($langBaseDir);
    foreach ($scan as $f) {
        if ($f !== '.' && $f !== '..' && pathinfo($f, PATHINFO_EXTENSION) === 'php') {
            if (strpos($f, 'admin-') === 0) {
                // 檔名內部一律使用底線 (如 zh_TW)
                $code = str_replace('admin-', '', pathinfo($f, PATHINFO_FILENAME));
                $availableLangs[] = $code;
            }
        }
    }
}

// 偵測與設定語系 (同時支援 GET 切換與 Cookie 讀取)
// 外部參數若傳入連字號 (zh-TW)，此處會自動轉為底線 (zh_TW)
$rawLang = isset($_COOKIE['admin_lang']) ? $_COOKIE['admin_lang'] : $defaultLang;
if (isset($_GET['lang'])) {
    $rawLang = $_GET['lang'];
}
$currentLang = str_replace('-', '_', $rawLang);

// 驗證語系是否有效
if (!in_array($currentLang, $availableLangs)) {
    $currentLang = $defaultLang;
}

// 重新設定 Cookie，確保同步
if (!isset($_COOKIE['admin_lang']) || $_COOKIE['admin_lang'] !== $currentLang) {
    setcookie('admin_lang', $currentLang, time() + 86400 * 30, '/'); 
}

// 載入語系檔
$langFile = $langBaseDir . '/admin-' . $currentLang . '.php';
if (file_exists($langFile)) {
    $lang = require $langFile;
} else {
    $lang = require $langBaseDir . '/admin-' . $defaultLang . '.php';
}

/**
 * 翻譯函式
 */
function __($key) {
    global $lang;
    return isset($lang[$key]) ? $lang[$key] : $key;
}

/**
 * 獲取網頁顯示用的語系代碼 (例如 zh-TW)
 */
function getWebLang() {
    global $currentLang;
    return str_replace('_', '-', $currentLang);
}
