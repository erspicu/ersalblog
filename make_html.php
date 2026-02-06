<?php
// make_html.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PHP_LIB/StaticGenerator.php';

use PHPLib\StaticGenerator;

// --- Config & Language Init ---
global $blog_lang; 
if (!isset($blog_lang)) $blog_lang = 'zh_TW'; 

// Load Language
$langFile = __DIR__ . "/langs/template-{$blog_lang}.php";
if (!file_exists($langFile)) $langFile = __DIR__ . "/langs/template-zh_TW.php";
$langData = file_exists($langFile) ? require $langFile : array();

// Prefix keys
$langVars = array();
foreach ($langData as $k => $v) {
    $langVars["lang_{$k}"] = $v;
}

// Config Array for Generator
$config = array(
    'blog_title' => $GLOBALS['blog_title'],
    'blog_description' => $GLOBALS['blog_description'],
    'blog_introduce' => $GLOBALS['blog_introduce'],
    'site_url' => $GLOBALS['site_url'],
    'blog_preview' => $GLOBALS['blog_preview']
);

// Instantiate Generator
$generator = new StaticGenerator(__DIR__, $langVars, $config, true);

// ==========================================
// 智慧快取判斷 (Smart Cache Strategy)
// ==========================================
$cacheHashFile = __DIR__ . "/contents/build_hash.json";
$storedHashes = file_exists($cacheHashFile) ? json_decode(file_get_contents($cacheHashFile), true) : array();

// 1. 全域影響參數
$globalConfigStr = $config['blog_title'] . 
                   $config['blog_introduce'] . 
                   $config['site_url'] . 
                   $GLOBALS['blog_lang'] . 
                   $GLOBALS['blog_timezone'] .
                   (file_exists($langFile) ? file_get_contents($langFile) : '');
$currentGlobalHash = md5($globalConfigStr);

// 2. 單頁影響參數
$indexConfigStr  = $config['blog_description'] . 
                   $config['blog_preview'];
$currentIndexHash = md5($indexConfigStr);

// 3. 判斷變更狀態
$configChangedGlobal = ($currentGlobalHash !== (isset($storedHashes['global']) ? $storedHashes['global'] : ''));
$configChangedIndex  = ($currentIndexHash !== (isset($storedHashes['index']) ? $storedHashes['index'] : ''));

if ($configChangedGlobal) {
    echo ">> [Config Change] Global settings changed. Rebuilding ALL pages.<br>\r\n";
} elseif ($configChangedIndex) {
    echo ">> [Config Change] Index settings changed. Rebuilding blog.html.<br>\r\n";
}

// CLI Args
$isForce = in_array('-f', $argv) || in_array('--force', $argv);
$isJson = in_array('-json', $argv); 

// Run Build
$generator->build($isForce, $isJson, $configChangedGlobal, $configChangedIndex, $langFile);

// Update Hash
file_put_contents($cacheHashFile, json_encode([
    'global' => $currentGlobalHash,
    'index'  => $currentIndexHash,
    'last_build' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT));
?>