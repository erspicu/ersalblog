<?php
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$baseDir = realpath(__DIR__ . '/../');

if ($action === 'list') {
    $relPath = isset($_GET['path']) ? $_GET['path'] : '';
    // 安全檢查：防止路徑遍歷攻擊，只允許在 blog 根目錄的上下幾層移動
    $targetPath = realpath($baseDir . '/' . $relPath);
    
    if (!$targetPath || strpos($targetPath, realpath($baseDir . '/../..')) === false) {
        $targetPath = $baseDir;
    }

    $items = array();
    $scan = scandir($targetPath);
    
    foreach ($scan as $item) {
        if ($item === '.') continue;
        $fullItemPath = $targetPath . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($fullItemPath)) {
            // 計算相對於 blog 根目錄的相對路徑
            $relative = '';
            // 由於 realpath 會處理符號連結與不同平台的斜線，我們手動計算相對關係
            // 這裡使用一個簡單的方法：直接回傳從根目錄計算的路徑
            
            // 取得與 baseDir 的相對關係
            $pathDiff = substr($fullItemPath, strlen($baseDir));
            $pathDiff = ltrim(str_replace('', '/', $pathDiff), '/');
            
            // 如果是在上層
            if (strpos($fullItemPath, $baseDir) === false) {
                // 處理平行目錄情況 (例如 ../album)
                $parentBase = realpath($baseDir . '/../');
                if (strpos($fullItemPath, $parentBase) !== false) {
                    $itemDiff = substr($fullItemPath, strlen($parentBase));
                    $pathDiff = '../' . ltrim(str_replace('', '/', $itemDiff), '/');
                }
            }

            if ($item === '..') {
                // 向上跳一層的邏輯
                $parentDir = dirname($targetPath);
                if (strpos($parentDir, realpath($baseDir . '/../..')) !== false) {
                    $items[] = array(
                        'name' => '.. [上層目錄]',
                        'path' => $relPath . '/..',
                        'is_parent' => true
                    );
                }
                continue;
            }

            $items[] = array(
                'name' => $item,
                'path' => $pathDiff ? $pathDiff . '/' : $item . '/',
                'is_dir' => true
            );
        }
    }

    echo json_encode(array(
        'current' => $relPath,
        'items' => $items
    ));
}
?>