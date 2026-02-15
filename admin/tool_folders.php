<?php
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$baseDir = realpath(__DIR__ . '/../');
$showFiles = isset($_GET['show_files']) && $_GET['show_files'] === '1';
$extFilter = isset($_GET['ext']) ? explode(',', $_GET['ext']) : array();

if ($action === 'list') {
    $relPath = isset($_GET['path']) ? $_GET['path'] : '';
    // 安全檢查：防止路徑遍歷
    $targetPath = realpath($baseDir . '/' . $relPath);
    
    if (!$targetPath || strpos($targetPath, realpath($baseDir . '/../..')) === false) {
        $targetPath = $baseDir;
    }

    $items = array();
    $scan = scandir($targetPath);
    
    // 處理上層目錄
    $parentDir = realpath($targetPath . '/..');
    if ($parentDir && strpos($parentDir, realpath($baseDir . '/../..')) !== false) {
        // 計算上層相對於根目錄的路徑
        $parentRel = '';
        if (strpos($parentDir, $baseDir) === 0) {
            $parentRel = ltrim(substr($parentDir, strlen($baseDir)), DIRECTORY_SEPARATOR);
        } else {
            $parentRel = '../' . ltrim(substr($parentDir, strlen(dirname($baseDir))), DIRECTORY_SEPARATOR);
        }
        $items[] = array(
            'name' => '.. [上層目錄]',
            'path' => $parentRel ? str_replace('\\', '/', $parentRel) : '',
            'is_parent' => true
        );
    }

    foreach ($scan as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullItemPath = $targetPath . DIRECTORY_SEPARATOR . $item;
        
        $relItemPath = ltrim(substr($fullItemPath, strlen($baseDir)), DIRECTORY_SEPARATOR);
        if (strpos($fullItemPath, $baseDir) === false) {
             $relItemPath = '../' . ltrim(substr($fullItemPath, strlen(dirname($baseDir))), DIRECTORY_SEPARATOR);
        }
        $relItemPath = str_replace('\\', '/', $relItemPath);

        if (is_dir($fullItemPath)) {
            $items[] = array(
                'name' => $item,
                'path' => $relItemPath,
                'is_dir' => true
            );
        } elseif ($showFiles) {
            $ext = pathinfo($item, PATHINFO_EXTENSION);
            if (empty($extFilter) || in_array($ext, $extFilter)) {
                $items[] = array(
                    'name' => $item,
                    'path' => $relItemPath,
                    'is_file' => true
                );
            }
        }
    }

    echo json_encode(array(
        'current' => str_replace('\\', '/', $relPath),
        'items' => $items
    ));
}
?>