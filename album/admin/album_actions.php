<?php
require_once 'auth.php';
requireAlbumLogin();

// 避免長任務超時與輸出干擾
set_time_limit(600);
if (!defined('CLI_TEST')) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: albums.php');
    exit;
}

if (!defined('CLI_TEST')) {
    validateCSRFRequest();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$collectionDir = __DIR__ . '/../Collection';

require_once __DIR__ . '/../PHP_LIB/AlbumGenerator.php';
$gen = new AlbumGenerator(__DIR__ . '/..');

// 開始攔截任何可能的警告輸出
ob_start();

switch ($action) {
    case 'rebuild_all':
        $forceJson = isset($_POST['forceJson']) && $_POST['forceJson'] === 'on';
        $forceThumb = isset($_POST['forceThumb']) && $_POST['forceThumb'] === 'on';
        $skipThumb = isset($_POST['skipThumb']) && $_POST['skipThumb'] === 'on';
        $onlyHtml = isset($_POST['onlyHtml']) && $_POST['onlyHtml'] === 'on';

        // 1. 生成樣板 Shell (邏輯與 make_album.php 一致)
        $tmPath = __DIR__ . '/../../PHP_LIB/TemplateManager.php';
        if (!file_exists($tmPath)) $tmPath = __DIR__ . '/../PHP_LIB/TemplateManager.php'; 
        
        if (file_exists($tmPath)) {
            require_once $tmPath;
            $tm = new TemplateManager();
            $baseDir = realpath(__DIR__ . '/..');
            $templateFile = $baseDir . '/static/album_template.html';
            
            if (file_exists($templateFile)) {
                $tm->load($templateFile);
                
                // 讀取設定
                $album_title = "Baxermux的相簿"; $album_description = ""; $album_introduce = ""; $album_preview = ""; $album_site_url = ""; $album_lang = "zh_TW";
                $configPhpFile = $baseDir . '/config/config.php';
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
            }
        }

        if ($onlyHtml) {
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => '樣板 Shell (album.html) 已成功更新。']);
            exit;
        }

        // 2. 環境檢查
        $env = $gen->checkEnvironment();
        $warningMsg = !empty($env['warnings']) ? "\n⚠️ 注意：\n" . implode("\n", $env['warnings']) : "";

        // 3. 執行核心處理
        $gen->run(array(
            'forceJson' => $forceJson,
            'forceThumb' => $forceThumb,
            'skipThumb' => $skipThumb
        ));
        
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => '維護任務已成功完成。' . $warningMsg]);
        exit;
        break;

    case 'rebuild_album':
        $id = isset($_POST['album_id']) ? trim($_POST['album_id']) : '';
        if (empty($id) || !is_dir($collectionDir . '/' . $id)) die("Album not found");

        $forceJson = isset($_POST['forceJson']) && $_POST['forceJson'] === 'on';
        $forceThumb = isset($_POST['forceThumb']) && $_POST['forceThumb'] === 'on';
        $skipThumb = isset($_POST['skipThumb']) && $_POST['skipThumb'] === 'on';

        // 環境檢查
        $env = $gen->checkEnvironment();
        $warningMsg = !empty($env['warnings']) ? "\n⚠️ 注意：\n" . implode("\n", $env['warnings']) : "";

        // 單一相簿重建時，如果沒有特別指定，預設還是走智慧快取判斷，除非使用者在介面選了「強制更新 JSON」
        $gen->run(array(
            'targetAlbum' => $id, 
            'forceJson' => $forceJson,
            'forceThumb' => $forceThumb,
            'skipThumb' => $skipThumb
        ));
        
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => "相簿 $id 已成功更新。" . $warningMsg]);
        exit;
        break;

    case 'create_album':
        ob_end_clean(); // 這些一般頁面不需要 ob
        $dirName = isset($_POST['dir_name']) ? trim($_POST['dir_name']) : '';
        $displayName = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';
        $desc = isset($_POST['description']) ? trim($_POST['description']) : '';

        if (empty($dirName) || !preg_match('/^[A-Za-z0-9_-]+$/', $dirName)) {
            die("Invalid directory name.");
        }

        $targetDir = $collectionDir . '/' . $dirName;
        if (is_dir($targetDir)) {
            die("Album already exists.");
        }

        mkdir($targetDir, 0777, true);
        mkdir($targetDir . '/Thumbnail', 0777, true);

        // Save metadata
        $metaContent = "$displayName|$desc||" . date('Ymd');
        file_put_contents($targetDir . '/comment_album.txt', $metaContent);

        header('Location: albums.php');
        break;

    case 'delete_album':
        ob_end_clean();
        $id = isset($_POST['album_id']) ? trim($_POST['album_id']) : '';
        if (empty($id) || strpos($id, '..') !== false) die("Invalid ID");

        $targetDir = $collectionDir . '/' . $id;
        if (is_dir($targetDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($targetDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }
            rmdir($targetDir);
        }
        header('Location: albums.php');
        break;

    case 'update_album_info':
        ob_end_clean();
        $id = isset($_POST['album_id']) ? trim($_POST['album_id']) : '';
        $newDirName = isset($_POST['new_dir_name']) ? trim($_POST['new_dir_name']) : ''; 
        $displayName = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';
        $desc = isset($_POST['description']) ? trim($_POST['description']) : '';
        $date = isset($_POST['date']) ? trim($_POST['date']) : '';
        
        if (empty($id) || !is_dir($collectionDir . '/' . $id)) die("Album not found");

        $targetDir = $collectionDir . '/' . $id;

        if (!empty($newDirName) && $newDirName !== $id) {
             if (!preg_match('/^[A-Za-z0-9_-]+$/', $newDirName)) die("Invalid new directory name.");
             $newTargetDir = $collectionDir . '/' . $newDirName;
             if (is_dir($newTargetDir)) die("Target directory name already exists.");
             
             rename($targetDir, $newTargetDir);
             $targetDir = $newTargetDir;
             $id = $newDirName;
        }

        $cover = '';
        $metaFile = $targetDir . '/comment_album.txt';
        if (file_exists($metaFile)) {
            $parts = explode('|', file_get_contents($metaFile));
            if (isset($parts[2])) $cover = $parts[2];
        }

        $newContent = "$displayName|$desc|$cover|$date";
        file_put_contents($targetDir . '/comment_album.txt', $newContent);

        header('Location: albums.php');
        break;

    case 'update_settings':
        $theme = isset($_POST['theme']) ? trim($_POST['theme']) : 'album';
        $apiType = isset($_POST['api_type']) ? trim($_POST['api_type']) : 'json';
        $itemsPerPage = isset($_POST['items_per_page']) ? (int)$_POST['items_per_page'] : 24;
        $concurrentDownloads = isset($_POST['concurrent_downloads']) ? (int)$_POST['concurrent_downloads'] : 3;

        $configFile = realpath(__DIR__ . '/../config/config.js');
        if (!$configFile) $configFile = __DIR__ . '/../config/config.js';

        $jsContent = "/**\n * Baxermux Album Configuration\n * Automatically generated by Admin Panel\n */\n";
        $jsContent .= "const albumConfig = {\n";
        $jsContent .= "    theme: '" . addslashes($theme) . "',\n";
        $jsContent .= "    api_type: '" . addslashes($apiType) . "',\n";
        $jsContent .= "    items_per_page: " . $itemsPerPage . ",\n";
        $jsContent .= "    concurrent_downloads: " . $concurrentDownloads . "\n";
        $jsContent .= "};\n";

        ob_clean();
        if (file_put_contents($configFile, $jsContent) === false) die("Error writing to config.js");
        echo json_encode(['status' => 'success']);
        exit;
        break;

    case 'update_backend_settings':
        $title = isset($_POST['album_title']) ? trim($_POST['album_title']) : '';
        $description = isset($_POST['album_description']) ? trim($_POST['album_description']) : '';
        $introduce = isset($_POST['album_introduce']) ? trim($_POST['album_introduce']) : '';
        $preview = isset($_POST['album_preview']) ? trim($_POST['album_preview']) : '';
        $siteUrl = isset($_POST['album_site_url']) ? trim($_POST['album_site_url']) : '';
        $lang = isset($_POST['album_lang']) ? trim($_POST['album_lang']) : 'zh-TW';
        $timezone = isset($_POST['album_timezone']) ? trim($_POST['album_timezone']) : 'Asia/Taipei';

        $phpFile = __DIR__ . '/../config/config.php';
        
        $albumAdminConfig = array();
        if (file_exists($phpFile)) {
            include $phpFile;
        }

        $phpContent = "<?php\n/**\n * Baxermux Album Configuration\n * Automatically generated by Admin Panel\n */\n\n";
        $phpContent .= "// --- 後台管理員設定 ---\n";
        $phpContent .= "\$albumAdminConfig = " . var_export($albumAdminConfig, true) . ";\n\n";
        $phpContent .= "// --- 全域相簿設定 ---\n";
        $phpContent .= "\$album_title = \"" . addslashes($title) . "\";\n";
        $phpContent .= "\$album_description = \"" . addslashes($description) . "\";\n";
        $phpContent .= "\$album_introduce = \"" . addslashes($introduce) . "\";\n";
        $phpContent .= "\$album_preview = \"" . addslashes($preview) . "\";\n";
        $phpContent .= "\$album_site_url = \"" . addslashes($siteUrl) . "\";\n";
        $phpContent .= "\$album_lang = \"" . addslashes($lang) . "\";\n";
        $phpContent .= "\$album_timezone = \"" . addslashes($timezone) . "\";\n\n";
        $phpContent .= "// 設定時區\n";
        $phpContent .= "date_default_timezone_set(\$album_timezone);\n";
        $phpContent .= "?>";

        ob_clean();
        if (file_put_contents($phpFile, $phpContent) === false) die("Error writing to config.php");
        echo json_encode(['status' => 'success']);
        exit;
        break;

    default:
        ob_end_clean();
        header('Location: albums.php');
}
?>
