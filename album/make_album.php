<?php
// Baxermux Album Generator - CLI Entry Point
require_once __DIR__ . '/PHP_LIB/AlbumGenerator.php';
require_once __DIR__ . '/../PHP_LIB/TemplateManager.php';

$baseDir = __DIR__;

// 讀取設定 (包含時區)
$configPhpFile = $baseDir . '/config/config.php';
if (file_exists($configPhpFile)) {
    include $configPhpFile;
} else {
    date_default_timezone_set('Asia/Taipei');
}

// 解析 CLI 參數
$longopts = array("skip-thumb", "force", "force-json", "force-thumb", "only-html", "help", "album:");
$options = getopt("sfha:", $longopts);

if (isset($options['h']) || isset($options['help'])) {
    echo "Baxermux Album Generator\n";
    echo "Usage: php make_album.php [options]\n\n";
    echo "Options:\n";
    echo "  --only-html         僅更新 album.html 首頁檔案 (不處理 JSON 與縮圖)\n";
    echo "  -a, --album NAME    指定僅更新特定的相簿名稱 (Specific album only)\n";
    echo "  -s, --skip-thumb    建立 JSON 就好，完全不處理縮圖 (Skip thumbnails)\n";
    echo "  -f, --force         強制完整重跑：JSON 與 縮圖全部重新產生 (Force all)\n";
    echo "  --force-json        強制重新產生 JSON 資料\n";
    echo "  --force-thumb       強制重新產生所有規格的縮圖\n";
    echo "  -h, --help          顯示此說明文件\n";
    exit(0);
}

$onlyHtml = isset($options['only-html']);
$targetAlbum = isset($options['a']) ? $options['a'] : (isset($options['album']) ? $options['album'] : null);
$skipThumbnails = (isset($options['s']) || isset($options['skip-thumb']));
$forceAll = (isset($options['f']) || isset($options['force']));
$forceJson = ($forceAll || isset($options['force-json']));
$forceThumb = ($forceAll || isset($options['force-thumb']));

// 1. 生成 Shell (album.html)
$tm = new TemplateManager();
$templateFile = $baseDir . '/static/album_template.html';
if (!file_exists($templateFile)) die("Template file not found\n");
$tm->load($templateFile);

// 讀取配置
$album_title = "Baxermux的相簿"; $album_description = ""; $album_introduce = ""; $album_preview = ""; $album_site_url = ""; $album_lang = "zh_TW";
if (file_exists($configPhpFile)) include $configPhpFile;

$album_lang_code = str_replace('-', '_', $album_lang);
$langFile = $baseDir . "/langs/template-{$album_lang_code}.php";
if (!file_exists($langFile)) $langFile = $baseDir . "/langs/template-zh_TW.php";
$langVars = file_exists($langFile) ? require $langFile : array();

$indexBody = $tm->render($tm->getSubTemplate('tmpl_app_container'), $langVars);
$appVersion = defined('CURRENT_VERSION') ? CURRENT_VERSION : 'v1.0.0';

$renderVars = array_merge($langVars, array(
    'album_title' => $album_title, 'album_description' => $album_description, 'album_introduce' => $album_introduce,
    'album_preview' => $album_preview, 'album_site_url' => $album_site_url, 'album_lang' => str_replace('_', '-', $album_lang_code),
    'album_header' => '', 'content_body' => $indexBody, 'custom_scripts' => '', 'version' => $appVersion
));

file_put_contents($baseDir . '/album.html', $tm->render($tm->getSource(), $renderVars));
echo "Generated: album.html\n";

if ($onlyHtml) exit(0);

// 2. 執行核心處理
$gen = new AlbumGenerator($baseDir);
$gen->run(array(
    'targetAlbum' => $targetAlbum,
    'skipThumb' => $skipThumbnails,
    'forceJson' => $forceJson,
    'forceThumb' => $forceThumb
));

echo "Done.\n";
