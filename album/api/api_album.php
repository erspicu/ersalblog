<?php
header('Content-Type: application/json; charset=utf-8');

// 設定時區與編碼
date_default_timezone_set('Asia/Taipei');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

/**
 * 簡易的 mb_convert_encoding 替代方案 (僅處理 UTF-8)
 */
function safe_mb_convert($str) {
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($str, 'UTF-8', 'auto');
    }
    // 如果沒有 mbstring，假設來源已經是 UTF-8 或嘗試 iconv
    if (function_exists('iconv')) {
        return @iconv('UTF-8', 'UTF-8//IGNORE', $str);
    }
    return $str;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list_albums';
$collectionDir = __DIR__ . '/../Collection';
$baseUrl = 'Collection'; 

// --- 讀取壓縮配置 ---
$compressionFile = __DIR__ . '/../config/compression.json';
$thumbConfigs = [];
if (file_exists($compressionFile)) {
    $thumbConfigs = json_decode(file_get_contents($compressionFile), true);
}

function getConfigIdByMode($configs, $mode) {
    foreach ($configs as $conf) {
        if ($conf['mode'] === $mode) return $conf['id'];
    }
    return null;
}

// 輔助函式：產生符合規則的縮圖檔名
function getThumbFilename($originalFilename, $prefix) {
    $info = pathinfo($originalFilename);
    return $info['filename'] . '_' . $prefix . '.' . $info['extension'];
}

// 輔助函式：將 EXIF 分數格式轉為浮點數
function exifToFloat($value) {
    $parts = explode('/', $value);
    if (count($parts) <= 0) return 0;
    if (count($parts) == 1) return (float)$parts[0];
    return (float)$parts[0] / (float)$parts[1];
}

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

    // GPS 處理
    $gps = null;
    if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude']) && isset($exif['GPSLatitudeRef']) && isset($exif['GPSLongitudeRef'])) {
        $lat = exifToFloat($exif['GPSLatitude'][0]) + (exifToFloat($exif['GPSLatitude'][1]) / 60) + (exifToFloat($exif['GPSLatitude'][2]) / 3600);
        $lng = exifToFloat($exif['GPSLongitude'][0]) + (exifToFloat($exif['GPSLongitude'][1]) / 60) + (exifToFloat($exif['GPSLongitude'][2]) / 3600);
        if ($exif['GPSLatitudeRef'] == 'S') $lat = -$lat;
        if ($exif['GPSLongitudeRef'] == 'W') $lng = -$lng;
        $gps = array('lat' => round($lat, 6), 'lng' => round($lng, 6));
    }

    return array(
        'make' => $make, 'model' => $model, 'aperture' => $aperture,
        'shutter' => $shutter, 'iso' => $iso, 'focal' => $focal, 'date' => $date,
        'gps' => $gps
    );
}

if ($action === 'list_albums') {
    $allAlbums = array();

    if (is_dir($collectionDir)) {
        $dirs = scandir($collectionDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $dirUtf8 = safe_mb_convert($dir);
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
                    
                    // 根據配置動態決定封面路徑
                    $previewId = getConfigIdByMode($thumbConfigs, 'PreviewIcon');
                    $stdThumbName = getThumbFilename($coverFn, $previewId ?: 'thumb');
                    
                    if (file_exists($albumPath . '/Thumbnail/' . $stdThumbName)) {
                        $albumData['cover'] = $baseUrl . '/' . $dirUtf8 . '/Thumbnail/' . $stdThumbName;
                    } else {
                        // 備案：嘗試使用第一個配置的 ID
                        $fallbackId = !empty($thumbConfigs) ? $thumbConfigs[0]['id'] : 'thumb';
                        $fallbackName = getThumbFilename($coverFn, $fallbackId);
                        if (file_exists($albumPath . '/Thumbnail/' . $fallbackName)) {
                            $albumData['cover'] = $baseUrl . '/' . $dirUtf8 . '/Thumbnail/' . $fallbackName;
                        } else {
                            $albumData['cover'] = $baseUrl . '/' . $dirUtf8 . '/' . $coverFn;
                        }
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
        
        $sizes = array();
        foreach ($thumbConfigs as $conf) {
            $id = $conf['id'];
            $tName = getThumbFilename($filename, $id);
            if (file_exists($thumbDir . '/' . $tName)) {
                $sizes[$id] = $baseUrl . '/' . $albumName . '/Thumbnail/' . $tName;
            }
        }

        $photoList[] = array(
            'filename' => $filename,
            'title' => $meta['title'],
            'desc' => $meta['desc'],
            'src' => $baseUrl . '/' . $albumName . '/' . $filename,
            'sizes' => $sizes,
            'exif' => getExifData($photoPath),
            'shortIdStart' => $index * (count($thumbConfigs) + 1)
        );
    }

    // 一次全傳，分頁交給前端
    echo json_encode(array(
        'name' => $displayAlbumName,
        'desc' => $albumDesc,
        'photos' => $photoList
    ));
}