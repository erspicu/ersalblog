<?php
// admin/lang_init.php
// 負責載入語系設定與定義翻譯函式

// 取得語系檔根目錄
$langBaseDir = dirname(__DIR__) . '/langs/admin';
$availableLangs = [];
$defaultLang = 'zh_TW';

// 掃描可用語系
if (is_dir($langBaseDir)) {
    $scan = scandir($langBaseDir);
    foreach ($scan as $f) {
        if ($f !== '.' && $f !== '..' && pathinfo($f, PATHINFO_EXTENSION) === 'php') {
            $availableLangs[] = pathinfo($f, PATHINFO_FILENAME);
        }
    }
}

// 偵測與設定語系 (同時支援 GET 切換與 Cookie 讀取)
// 如果在登入頁面已經設定 Cookie，這裡會直接讀取
$currentLang = $_COOKIE['admin_lang'] ?? $defaultLang;

// 如果網址帶有 ?lang=xxx 且該語系有效，則更新 Cookie (支援登入後切換)
if (isset($_GET['lang']) && in_array($_GET['lang'], $availableLangs)) {
    $currentLang = $_GET['lang'];
    // 重新設定 Cookie，確保同步
    setcookie('admin_lang', $currentLang, time() + 86400 * 30, '/'); 
}

// 載入語系檔
$langFile = $langBaseDir . '/' . $currentLang . '.php';
if (file_exists($langFile)) {
    $lang = require $langFile;
} else {
    $lang = require $langBaseDir . '/' . $defaultLang . '.php';
}

/**
 * 翻譯函式
 * @param string $key 語系檔中的 Key
 * @return string 翻譯後的文字，若找不到則回傳 Key
 */
function __($key) {
    global $lang;
    return $lang[$key] ?? $key;
}
