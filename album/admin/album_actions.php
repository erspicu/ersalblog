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

// 釋放 Session 鎖定，讓併發的 Polling 請求可以順利執行
session_write_close();

$action = isset($_POST['action']) ? $_POST['action'] : '';
$collectionDir = __DIR__ . '/../Collection';

require_once __DIR__ . '/../PHP_LIB/AlbumGenerator.php';
$gen = new AlbumGenerator(__DIR__ . '/..');

ob_start();

switch ($action) {
    case 'get_rebuild_progress':
        if (ob_get_level()) ob_end_clean();
        $pid = isset($_POST['progress_id']) ? preg_replace('/[^\p{L}\p{N}]/u', '', $_POST['progress_id']) : '';
        $file = __DIR__ . '/../api/json/rebuild_progress_' . $pid . '.json';
        header('Content-Type: application/json');
        
        $content = '';
        if (!empty($pid) && file_exists($file)) {
            $content = file_get_contents($file);
        }
        
        if (empty($content)) {
            echo json_encode(['status' => 'waiting', 'message' => __('processing_msg')]);
        } else {
            echo $content;
        }
        exit;
        break;

    case 'rebuild_all':
        $pid = isset($_POST['progress_id']) ? $_POST['progress_id'] : 'all';
        $gen->setProgressId($pid);

        $forceJson = isset($_POST['forceJson']) && $_POST['forceJson'] === 'on';
        $forceThumb = isset($_POST['forceThumb']) && $_POST['forceThumb'] === 'on';
        $skipThumb = isset($_POST['skipThumb']) && $_POST['skipThumb'] === 'on';
        $onlyHtml = isset($_POST['onlyHtml']) && $_POST['onlyHtml'] === 'on';

        // 1. 生成樣板 Shell (優先使用相簿內部的 PHP_LIB)
        $tmPath = __DIR__ . '/../PHP_LIB/TemplateManager.php';
        if (!file_exists($tmPath)) $tmPath = __DIR__ . '/../../PHP_LIB/TemplateManager.php'; 
        
        if (file_exists($tmPath)) {
            require_once $tmPath;
            $tm = new TemplateManager();
            $baseDir = realpath(__DIR__ . '/..');
            $templateFile = $baseDir . '/static/album_template.html';
            
            if (file_exists($templateFile)) {
                $tm->load($templateFile);
                $album_title = "Baxermux的相簿"; $album_description = ""; $album_introduce = ""; $album_preview = ""; $album_site_url = ""; $album_lang = "zh_TW";
                $configPhpFile = $baseDir . '/config/config.php';
                if (file_exists($configPhpFile)) include $configPhpFile;

                $album_lang_code = str_replace('-', '_', $album_lang);
                $langFile = $baseDir . "/langs/template-{$album_lang_code}.php";
                if (!file_exists($langFile)) {
                    $langFile = $baseDir . "/langs/template-zh_TW.php";
                    $album_lang_code = 'zh_TW';
                }
                $langVars = file_exists($langFile) ? require $langFile : array();

                $indexBody = $tm->render($tm->getSubTemplate('tmpl_app_container'), $langVars);
                $appVersion = defined('CURRENT_VERSION') ? CURRENT_VERSION : 'v1.0.0';

                $renderVars = array_merge($langVars, array(
                    'album_title' => $album_title, 'album_description' => $album_description, 'album_introduce' => $album_introduce,
                    'album_preview' => $album_preview, 'album_site_url' => $album_site_url, 
                    'album_lang' => str_replace('_', '-', $album_lang_code),
                    'album_header' => '', 'content_body' => $indexBody, 'custom_scripts' => '', 'version' => $appVersion
                ));
                file_put_contents($baseDir . '/album.html', $tm->render($tm->getSource(), $renderVars));
            }
        }

        if ($onlyHtml) {
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => __('opt_only_html') . ' ' . __('success_save')]);
            exit;
        }

        // 2. 執行核心處理
        $env = $gen->checkEnvironment();
        $warningMsg = !empty($env['warnings']) ? "\n⚠️ " . __('notice') . "：\n" . implode("\n", $env['warnings']) : "";
        $gen->run(array('forceJson' => $forceJson, 'forceThumb' => $forceThumb, 'skipThumb' => $skipThumb));
        
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => __('success_rebuild_all') . $warningMsg]);
        exit;
        break;

    case 'rebuild_album':
        $id = isset($_POST['album_id']) ? trim($_POST['album_id']) : '';
        if (empty($id) || !is_dir($collectionDir . '/' . $id)) die("Album not found");

        $pid = isset($_POST['progress_id']) ? $_POST['progress_id'] : $id;
        $gen->setProgressId($pid);

        $forceJson = isset($_POST['forceJson']) && $_POST['forceJson'] === 'on';
        $forceThumb = isset($_POST['forceThumb']) && $_POST['forceThumb'] === 'on';
        $skipThumb = isset($_POST['skipThumb']) && $_POST['skipThumb'] === 'on';

        $env = $gen->checkEnvironment();
        $warningMsg = !empty($env['warnings']) ? "\n⚠️ " . __('notice') . "：\n" . implode("\n", $env['warnings']) : "";
        $gen->run(array('targetAlbum' => $id, 'forceJson' => $forceJson, 'forceThumb' => $forceThumb, 'skipThumb' => $skipThumb));
        
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => sprintf(__('success_rebuild_album'), $id) . $warningMsg]);
        exit;
        break;

    case 'create_album':
        ob_end_clean();
        $dirName = isset($_POST['dir_name']) ? trim($_POST['dir_name']) : '';
        $displayName = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';
        $desc = isset($_POST['description']) ? trim($_POST['description']) : '';
        if (empty($dirName) || !preg_match('/^[\p{L}\p{N}_-]+$/u', $dirName)) die("Invalid directory name.");
        $targetDir = $collectionDir . '/' . $dirName;
        if (is_dir($targetDir)) die("Album already exists.");
        mkdir($targetDir, 0777, true);
        mkdir($targetDir . '/Thumbnail', 0777, true);
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
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($targetDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $fileinfo) { $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink'); $todo($fileinfo->getRealPath()); }
            rmdir($targetDir);
            
            // 同步移除 shorturl.txt 中的項目
            $shortUrlFile = __DIR__ . '/../shorturl.txt';
            if (file_exists($shortUrlFile)) {
                $lines = file($shortUrlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $newLines = array();
                $targetPath = 'Collection/' . $id;
                foreach ($lines as $line) {
                    $parts = explode('|', $line);
                    if (count($parts) >= 2 && $parts[1] !== $targetPath) {
                        $newLines[] = $line;
                    }
                }
                file_put_contents($shortUrlFile, implode("\n", $newLines) . (empty($newLines) ? "" : "\n"));
            }
            
            // 主動觸發一次產生器更新 index.json
            $gen->run(array('skipThumb' => true));
            
            // 刪除該相簿對應的 JSON 檔案
            $jsonFile = __DIR__ . '/../api/json/' . $id . '.json';
            if (file_exists($jsonFile)) @unlink($jsonFile);
        }
        header('Location: albums.php');
        break;

    case 'update_album_info':
        ob_end_clean();
        $id = isset($_POST['album_id']) ? trim($_POST['album_id']) : '';
        $oldId = $id; // 紀錄舊 ID
        $newDirName = isset($_POST['new_dir_name']) ? trim($_POST['new_dir_name']) : ''; 
        $displayName = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';
        $desc = isset($_POST['description']) ? trim($_POST['description']) : '';
        $date = isset($_POST['date']) ? trim($_POST['date']) : '';
        if (empty($id) || !is_dir($collectionDir . '/' . $id)) die("Album not found");
        $targetDir = $collectionDir . '/' . $id;
        
        $renamed = false;
        if (!empty($newDirName) && $newDirName !== $id) {
             if (!preg_match('/^[\p{L}\p{N}_-]+$/u', $newDirName)) die("Invalid new directory name.");
             $newTargetDir = $collectionDir . '/' . $newDirName;
             if (is_dir($newTargetDir)) die("Target directory name already exists.");
             rename($targetDir, $newTargetDir);
             $targetDir = $newTargetDir; $id = $newDirName;
             $renamed = true;
        }
        
        // 如果有更名，同步更新 shorturl.txt
        if ($renamed) {
            $shortUrlFile = __DIR__ . '/../shorturl.txt';
            if (file_exists($shortUrlFile)) {
                $lines = file($shortUrlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $newLines = array();
                $oldPath = 'Collection/' . $oldId;
                $newPath = 'Collection/' . $id;
                foreach ($lines as $line) {
                    $parts = explode('|', $line);
                    if (count($parts) >= 2 && $parts[1] === $oldPath) {
                        $newLines[] = $parts[0] . '|' . $newPath;
                    } else {
                        $newLines[] = $line;
                    }
                }
                file_put_contents($shortUrlFile, implode("\n", $newLines) . (empty($newLines) ? "" : "\n"));
            }
        }

        $cover = ''; $metaFile = $targetDir . '/comment_album.txt';
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
        $jsContent = "const albumConfig = { theme: '" . addslashes($theme) . "', api_type: '" . addslashes($apiType) . "', items_per_page: " . $itemsPerPage . ", concurrent_downloads: " . $concurrentDownloads . " };\n";
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
        if (file_exists($phpFile)) include $phpFile;
        $phpContent = "<?php\n\$albumAdminConfig = " . var_export($albumAdminConfig, true) . ";\n\$album_title = \"" . addslashes($title) . "\";\n\$album_description = \"" . addslashes($description) . "\";\n\$album_introduce = \"" . addslashes($introduce) . "\";\n\$album_preview = \"" . addslashes($preview) . "\";\n\$album_site_url = \"" . addslashes($siteUrl) . "\";\n\$album_lang = \"" . addslashes($lang) . "\";\n\$album_timezone = \"" . addslashes($timezone) . "\";\ndate_default_timezone_set(\$album_timezone);\n?>";
        ob_clean();
        if (file_put_contents($phpFile, $phpContent) === false) die("Error writing to config.php");
        echo json_encode(['status' => 'success']);
        exit;
        break;

    case 'update_admin_account':
        $newUsername = isset($_POST['new_username']) ? trim($_POST['new_username']) : '';
        $newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
        
        if (empty($newUsername)) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Username cannot be empty.']);
            exit;
        }

        $phpFile = __DIR__ . '/../config/config.php';
        $albumAdminConfig = array();
        if (file_exists($phpFile)) include $phpFile;
        
        $albumAdminConfig['username'] = $newUsername;
        if (!empty($newPassword)) {
            $fingerprint = getSystemFingerprint();
            $albumAdminConfig['password'] = password_hash($newPassword . $fingerprint, PASSWORD_BCRYPT);
        }
        
        // 讀取其他現有的 PHP 變數以維持 config.php 完整性
        $album_title = isset($album_title) ? $album_title : "";
        $album_description = isset($album_description) ? $album_description : "";
        $album_introduce = isset($album_introduce) ? $album_introduce : "";
        $album_preview = isset($album_preview) ? $album_preview : "";
        $album_site_url = isset($album_site_url) ? $album_site_url : "";
        $album_lang = isset($album_lang) ? $album_lang : "zh-TW";
        $album_timezone = isset($album_timezone) ? $album_timezone : "Asia/Taipei";

        $phpContent = "<?php\n" .
                      "\$albumAdminConfig = " . var_export($albumAdminConfig, true) . ";\n" .
                      "\$album_title = \"" . addslashes($album_title) . "\";\n" .
                      "\$album_description = \"" . addslashes($album_description) . "\";\n" .
                      "\$album_introduce = \"" . addslashes($album_introduce) . "\";\n" .
                      "\$album_preview = \"" . addslashes($album_preview) . "\";\n" .
                      "\$album_site_url = \"" . addslashes($album_site_url) . "\";\n" .
                      "\$album_lang = \"" . addslashes($album_lang) . "\";\n" .
                      "\$album_timezone = \"" . addslashes($album_timezone) . "\";\n" .
                      "date_default_timezone_set(\$album_timezone);\n" .
                      "?>";

        ob_clean();
        if (file_put_contents($phpFile, $phpContent) === false) {
            echo json_encode(['status' => 'error', 'message' => 'Error writing to config.php']);
        } else {
            $_SESSION['album_admin_user'] = $newUsername;
            echo json_encode(['status' => 'success', 'message' => __('msg_account_updated')]);
        }
        exit;
        break;

    default:
        ob_end_clean();
        header('Location: albums.php');
}
?>
