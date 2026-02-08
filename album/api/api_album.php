<?php
header('Content-Type: application/json; charset=utf-8');

// 設定時區與編碼
date_default_timezone_set('Asia/Taipei');
mb_internal_encoding('UTF-8');

$action = isset($_GET['action']) ? $_GET['action'] : 'list_albums';
$collectionDir = __DIR__ . '/../Collection';
$baseUrl = 'Collection'; 

// 輔助函式：EXIF 讀取
function getExifData($file) {
    if (!function_exists('exif_read_data')) return null;
    $exif = @exif_read_data($file);
    if (!$exif) return null;

    $make = isset($exif['Make']) ? trim($exif['Make']) : '未知';
    $model = isset($exif['Model']) ? trim($exif['Model']) : '未知';
    $iso = isset($exif['ISOSpeedRatings']) ? (is_array($exif['ISOSpeedRatings']) ? $exif['ISOSpeedRatings'][0] : $exif['ISOSpeedRatings']) : '未知';
    $date = isset($exif['DateTimeOriginal']) ? $exif['DateTimeOriginal'] : (isset($exif['DateTime']) ? $exif['DateTime'] : '未知');

    $aperture = '未知';
    if (isset($exif['FNumber'])) {
        $p = explode('/', $exif['FNumber']);
        $val = (count($p) == 2 && $p[1] != 0) ? $p[0] / $p[1] : $exif['FNumber'];
        $aperture = 'f/' . round((float)$val, 1);
    }

    $shutter = '未知';
    if (isset($exif['ExposureTime'])) {
        $p = explode('/', $exif['ExposureTime']);
        if (count($p) == 2 && $p[0] != 0 && $p[1] != 0) {
            $val = $p[0] / $p[1];
            $shutter = ($val >= 1) ? $val . 's' : '1/' . round($p[1] / $p[0]) . 's';
        } else {
            $shutter = $exif['ExposureTime'] . 's';
        }
    }

    $focal = '未知';
    if (isset($exif['FocalLength'])) {
        $p = explode('/', $exif['FocalLength']);
        $val = (count($p) == 2 && $p[1] != 0) ? $p[0] / $p[1] : $exif['FocalLength'];
        $focal = round((float)$val, 1) . 'mm';
    }

    return array(
        'make' => $make, 'model' => $model, 'aperture' => $aperture,
        'shutter' => $shutter, 'iso' => $iso, 'focal' => $focal, 'date' => $date
    );
}

if ($action === 'list_albums') {
    $allAlbums = array();

    if (is_dir($collectionDir)) {
        $dirs = scandir($collectionDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $dirUtf8 = mb_convert_encoding($dir, 'UTF-8', 'auto');
            $albumPath = $collectionDir . '/' . $dir;
            if (is_dir($albumPath)) {
                $albumData = array(
                    'id' => $dirUtf8,
                    'name' => $dirUtf8,
                    'desc' => '',
                    'cover' => '',
                    'count' => 0,
                    'date' => '',
                    'link' => '#album=' . urlencode($dirUtf8) 
                );

                $photos = glob($albumPath . '/*.jpg');
                $albumData['count'] = count($photos);

                // 預設日期為檔案系統時間
                $albumData['date'] = date('Ymd', filemtime($albumPath));

                $commentFile = $albumPath . '/comment_album.txt';
                if (file_exists($commentFile)) {
                    $content = file_get_contents($commentFile);
                    $parts = explode('|', $content);
                    if (isset($parts[0]) && !empty($parts[0])) $albumData['name'] = trim($parts[0]);
                    if (isset($parts[1])) $albumData['desc'] = trim($parts[1]);
                    if (isset($parts[2]) && !empty($parts[2])) $albumData['cover'] = trim($parts[2]);
                    if (isset($parts[3]) && !empty(trim($parts[3]))) $albumData['date'] = trim($parts[3]);
                }

                // --- 處理封面路徑 ---
                if (empty($albumData['cover'])) {
                    $photos = glob($albumPath . '/*.jpg');
                    if (!empty($photos)) $albumData['cover'] = basename($photos[0]);
                }

                if (!empty($albumData['cover'])) {
                    $coverFn = basename($albumData['cover']);
                    $info = pathinfo($coverFn);
                    
                    // 優先使用標準縮圖 (800px)
                    $stdThumbName = $info['filename'] . '_thumb.jpg';
                    if (file_exists($albumPath . '/Thumbnail/' . $stdThumbName)) {
                        $albumData['cover'] = $baseUrl . '/' . $dirUtf8 . '/Thumbnail/' . $stdThumbName;
                    } elseif (file_exists($albumPath . '/Thumbnail/' . $info['filename'] . '_thumbXS.jpg')) {
                        // 次之使用 XS 縮圖
                        $albumData['cover'] = $baseUrl . '/' . $dirUtf8 . '/Thumbnail/' . $info['filename'] . '_thumbXS.jpg';
                    } else {
                        // 最後使用原圖
                        $albumData['cover'] = $baseUrl . '/' . $dirUtf8 . '/' . $coverFn;
                    }
                } else {
                    $albumData['cover'] = 'https://via.placeholder.com/320x200?text=No+Photo';
                }

                $allAlbums[] = $albumData;
            }
        }
    }

    usort($allAlbums, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    // 一次全傳，分頁交給前端
    echo json_encode(array(
        'items' => $allAlbums
    ));

} elseif ($action === 'get_album') {
    $albumName = isset($_GET['album']) ? $_GET['album'] : '';
    if (empty($albumName) || !is_dir($collectionDir . '/' . $albumName)) {
        http_response_code(404);
        echo json_encode(array('error' => 'Album not found'));
        exit;
    }

    $albumPath = $collectionDir . '/' . $albumName;
    $thumbDir = $albumPath . '/Thumbnail';
    
    $displayAlbumName = $albumName;
    $albumDesc = '';
    $commentAlbumFile = $albumPath . '/comment_album.txt';
    if (file_exists($commentAlbumFile)) {
        $content = file_get_contents($commentAlbumFile);
        $parts = explode('|', $content);
        if (isset($parts[0]) && !empty($parts[0])) $displayAlbumName = trim($parts[0]);
        if (isset($parts[1])) $albumDesc = trim($parts[1]);
    }

    $photoMeta = array(); 
    $picCommentFile = $albumPath . '/comment_pic.txt';
    if (file_exists($picCommentFile)) {
        $lines = file($picCommentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $p = explode('|', $line);
            if (count($p) >= 1) {
                $fn = trim($p[0]);
                $photoMeta[$fn] = array(
                    'title' => isset($p[1]) && !empty($p[1]) ? trim($p[1]) : $fn,
                    'desc' => isset($p[2]) ? trim($p[2]) : ''
                );
            }
        }
    }

    $photos = glob($albumPath . '/*.jpg');
    $photoList = array();

    foreach ($photos as $index => $photoPath) {
        $filename = basename($photoPath);
        $meta = isset($photoMeta[$filename]) ? $photoMeta[$filename] : array('title' => $filename, 'desc' => '');
        
        $thumbName = pathinfo($filename, PATHINFO_FILENAME) . '_thumb.jpg';
        $thumbXSName = pathinfo($filename, PATHINFO_FILENAME) . '_thumbXS.jpg';
        $thumbLName = pathinfo($filename, PATHINFO_FILENAME) . '_thumbL.jpg';
        $thumbXLName = pathinfo($filename, PATHINFO_FILENAME) . '_thumbXL.jpg';

        $hasThumb = file_exists($thumbDir . '/' . $thumbName);
        $hasThumbXS = file_exists($thumbDir . '/' . $thumbXSName);
        $hasThumbL = file_exists($thumbDir . '/' . $thumbLName);
        $hasThumbXL = file_exists($thumbDir . '/' . $thumbXLName);

        $photoList[] = array(
            'filename' => $filename,
            'title' => $meta['title'],
            'desc' => $meta['desc'],
            'src' => $baseUrl . '/' . $albumName . '/' . $filename,
            'thumb' => $hasThumb ? $baseUrl . '/' . $albumName . '/Thumbnail/' . $thumbName : null,
            'thumbXS' => $hasThumbXS ? $baseUrl . '/' . $albumName . '/Thumbnail/' . $thumbXSName : null,
            'thumbL' => $hasThumbL ? $baseUrl . '/' . $albumName . '/Thumbnail/' . $thumbLName : null,
            'thumbXL' => $hasThumbXL ? $baseUrl . '/' . $albumName . '/Thumbnail/' . $thumbXLName : null,
            'exif' => getExifData($photoPath),
            'shortIdStart' => $index * 6
        );
    }

    // 一次全傳，分頁交給前端
    echo json_encode(array(
        'name' => $displayAlbumName,
        'desc' => $albumDesc,
        'photos' => $photoList
    ));
}