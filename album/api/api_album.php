<?php
header('Content-Type: application/json; charset=utf-8');

// 設定
$itemsPerPage = 24;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$collectionDir = __DIR__ . '/../Collection';
$baseUrl = 'Collection'; 

$allAlbums = array();

if (is_dir($collectionDir)) {
    $dirs = scandir($collectionDir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        
        $albumPath = $collectionDir . '/' . $dir;
        if (is_dir($albumPath)) {
            $albumData = array(
                'name' => $dir,
                'desc' => '',
                'cover' => '',
                'count' => 0,
                'date' => '',
                'link' => 'view/' . $dir . '.html'
            );

            $photos = glob($albumPath . '/*.jpg');
            $albumData['count'] = count($photos);

            $commentFile = $albumPath . '/comment_album.txt';
            if (file_exists($commentFile)) {
                $content = file_get_contents($commentFile);
                $parts = explode('|', $content);
                if (isset($parts[0]) && !empty($parts[0])) $albumData['name'] = trim($parts[0]);
                if (isset($parts[1])) $albumData['desc'] = trim($parts[1]);
                if (isset($parts[2]) && !empty($parts[2])) $albumData['cover'] = trim($parts[2]);
                if (isset($parts[3])) $albumData['date'] = trim($parts[3]);
            }

            if (empty($albumData['cover']) && !empty($photos)) {
                $albumData['cover'] = basename($photos[0]);
            }

            if (!empty($albumData['cover'])) {
                $coverFilename = basename($albumData['cover']);
                $info = pathinfo($coverFilename);
                $newThumbName = $info['filename'] . '_thumb.' . $info['extension'];
                $thumbPath = $albumPath . '/Thumbnail/' . $newThumbName;
                if (file_exists($thumbPath)) {
                    $albumData['cover'] = $baseUrl . '/' . $dir . '/Thumbnail/' . $newThumbName;
                } else {
                    $albumData['cover'] = $baseUrl . '/' . $dir . '/' . $coverFilename;
                }
            }

            if (empty($albumData['date'])) {
                $albumData['date'] = date('Ymd', filemtime($albumPath));
            }

            $allAlbums[] = $albumData;
        }
    }
}

// 排序
usort($allAlbums, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

// 分頁邏輯
$totalItems = count($allAlbums);
$totalPages = ceil($totalItems / $itemsPerPage);
$offset = ($page - 1) * $itemsPerPage;
$pagedAlbums = array_slice($allAlbums, $offset, $itemsPerPage);

// 回傳結果
echo json_encode(array(
    'items' => $pagedAlbums,
    'pagination' => array(
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'itemsPerPage' => $itemsPerPage
    )
));
