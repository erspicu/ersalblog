<?php
// 設定時區與編碼
date_default_timezone_set('Asia/Taipei');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// 定義路徑
$baseDir = __DIR__;
$collectionDir = $baseDir . '/Collection';
$staticDir = $baseDir . '/static';
$jsonDir = $baseDir . '/api/json';
$templateFile = $staticDir . '/album_template.html';

// 確保目錄存在
if (!file_exists($jsonDir)) mkdir($jsonDir, 0777, true);

// 解析 CLI 參數
$options = getopt("sfh", array("skip-thumb", "force", "help"));
if (isset($options['h']) || isset($options['help'])) {
    echo "Usage: php make_album.php [options]\n";
    echo "Options:\n";
    echo "  -s, --skip-thumb    Skip thumbnail generation\n";
    echo "  -f, --force         Force regeneration of existing thumbnails\n";
    echo "  -h, --help          Show this help message\n";
    exit(0);
}

$skipThumbnails = (isset($options['s']) || isset($options['skip-thumb']));
$forceThumbnail = (isset($options['f']) || isset($options['force']));

// --- 讀取壓縮配置 ---
$compressionFile = $baseDir . '/config/compression.json';
$thumbConfigs = array();
$configMtime = 0;
if (file_exists($compressionFile)) {
    $thumbConfigs = json_decode(file_get_contents($compressionFile), true);
    $configMtime = filemtime($compressionFile);
} else {
    $thumbConfigs = array(array('id' => 'thumb', 'width' => 800, 'quality' => 90, 'mode' => 'PreviewIcon'));
}

// --- 讀取現有 ShortURL 以維持 ID 穩定性 ---
$existingShortUrls = array();
$maxShortId = -1;
$shortUrlFile = $baseDir . '/shorturl.txt';
if (file_exists($shortUrlFile)) {
    $lines = file($shortUrlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode('|', $line);
        if (count($parts) >= 2) {
            $id = (int)$parts[0];
            $path = $parts[1];
            $existingShortUrls[$path] = $id;
            if ($id > $maxShortId) $maxShortId = $id;
        }
    }
}
$nextAvailableId = $maxShortId + 1;
$shortUrlList = array();

// 輔助函式：原子化寫入檔案
function safe_file_put_contents($path, $content) {
    $tmp = $path . '.tmp.' . uniqid();
    if (file_put_contents($tmp, $content) !== false) {
        if (rename($tmp, $path)) return true;
    }
    if (file_exists($tmp)) @unlink($tmp);
    return false;
}

// 輔助函式：根據 mode 獲取 ID
function getConfigIdByMode($configs, $mode) {
    foreach ($configs as $conf) {
        if ($conf['mode'] === $mode) return $conf['id'];
    }
    return null;
}

// 引入樣板管理器
require_once $baseDir . '/../PHP_LIB/TemplateManager.php';
$tm = new TemplateManager();
if (!file_exists($templateFile)) die("Template file not found: $templateFile");
$tm->load($templateFile);

// 輔助函式：產生符合規則的縮圖檔名
function getThumbFilename($originalFilename, $prefix) {
    $info = pathinfo($originalFilename);
    return $info['filename'] . '_' . $prefix . '.' . $info['extension'];
}

// 輔助函式：EXIF 讀取
function getExifData($file) {
    if (!function_exists('exif_read_data')) return null;
    $exif = @exif_read_data($file);
    if (!$exif) return null;
    return parseExifArray($exif);
}

function exifToFloat($value) {
    $parts = explode('/', $value);
    if (count($parts) <= 0) return 0;
    if (count($parts) == 1) return (float)$parts[0];
    return (float)$parts[0] / (float)$parts[1];
}

function parseExifArray($exif) {
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

    $gps = null;
    if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude']) && isset($exif['GPSLatitudeRef']) && isset($exif['GPSLongitudeRef'])) {
        $lat = exifToFloat($exif['GPSLatitude'][0]) + (exifToFloat($exif['GPSLatitude'][1]) / 60) + (exifToFloat($exif['GPSLatitude'][2]) / 3600);
        $lng = exifToFloat($exif['GPSLongitude'][0]) + (exifToFloat($exif['GPSLongitude'][1]) / 60) + (exifToFloat($exif['GPSLongitude'][2]) / 3600);
        if ($exif['GPSLatitudeRef'] == 'S') $lat = -$lat;
        if ($exif['GPSLongitudeRef'] == 'W') $lng = -$lng;
        $gps = array('lat' => round($lat, 6), 'lng' => round($lng, 6));
    }

    return array('make' => $make, 'model' => $model, 'aperture' => $aperture, 'shutter' => $shutter, 'iso' => $iso, 'focal' => $focal, 'date' => $date, 'gps' => $gps);
}

// 生成縮圖
function generateThumbnail($src, $dest, $maxSize, $quality) {
    global $forceThumbnail, $configMtime;
    if (file_exists($dest) && !$forceThumbnail) {
        $thumbMtime = filemtime($dest);
        if ($thumbMtime >= filemtime($src) && $thumbMtime >= $configMtime) return;
    }

    if (extension_loaded('imagick')) {
        try {
            $image = new Imagick($src);
            $image->setImageCompressionQuality($quality);
            $width = $image->getImageWidth(); $height = $image->getImageHeight();
            if ($width <= $maxSize && $height <= $maxSize) {
                copy($src, $dest); $image->clear(); return;
            }
            $ratio = $width / $height;
            if ($width > $height) { $newWidth = $maxSize; $newHeight = $maxSize / $ratio; }
            else { $newHeight = $maxSize; $newWidth = $maxSize * $ratio; }
            $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
            $image->writeImage($dest); $image->clear();
            echo "Created thumbnail (Imagick): " . basename($dest) . "\n";
            return;
        } catch (Exception $e) {}
    }

    list($width, $height, $type) = getimagesize($src);
    if ($width <= $maxSize && $height <= $maxSize) { copy($src, $dest); return; }
    $ratio = $width / $height;
    if ($width > $height) { $newWidth = $maxSize; $newHeight = $maxSize / $ratio; }
    else { $newHeight = $maxSize; $newWidth = $maxSize * $ratio; }
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    switch ($type) {
        case IMAGETYPE_JPEG: $source = imagecreatefromjpeg($src); break;
        case IMAGETYPE_PNG: $source = imagecreatefrompng($src); break;
        case IMAGETYPE_GIF: $source = imagecreatefromgif($src); break;
        default: return;
    }
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagejpeg($thumb, $dest, $quality);
    imagedestroy($thumb); imagedestroy($source);
    echo "Created thumbnail (GD): " . basename($dest) . "\n";
}

// ==========================================
// 1. 生成相簿首頁
// ==========================================
$indexBody = $tm->getSubTemplate('tmpl_app_container');
$appVersion = 'v1.0.0';
if (file_exists($baseDir . '/../admin/version_config.php')) {
    include $baseDir . '/../admin/version_config.php';
    if (defined('CURRENT_VERSION')) $appVersion = CURRENT_VERSION;
}
$indexHtml = $tm->render($tm->getSource(), array('path_to_static' => 'static/', 'path_to_config' => 'config/', 'page_title' => '相簿首頁', 'album_header' => '', 'content_body' => $indexBody, 'custom_scripts' => '', 'version' => $appVersion));
file_put_contents($baseDir . '/album.html', $indexHtml);
echo "Generated: album.html (SPA Shell)\n";

// ==========================================
// 2. 遍歷相簿
// ==========================================
$allAlbumsList = array();
$baseUrl = 'Collection'; 

if (is_dir($collectionDir)) {
    $albums = scandir($collectionDir);
    foreach ($albums as $albumName) {
        if ($albumName === '.' || $albumName === '..') continue;
        $albumPath = $collectionDir . '/' . $albumName;
        if (!is_dir($albumPath)) continue;

        $jsonFile = $jsonDir . '/' . $albumName . '.json';
        $commentAlbumFile = $albumPath . '/comment_album.txt';
        $picCommentFile = $albumPath . '/comment_pic.txt';
        
        // 快取判斷
        $cacheValid = false;
        if (file_exists($jsonFile) && !$forceThumbnail) {
            $jsonMtime = filemtime($jsonFile);
            $sourceMtime = filemtime($albumPath);
            if (file_exists($commentAlbumFile)) $sourceMtime = max($sourceMtime, filemtime($commentAlbumFile));
            if (file_exists($picCommentFile)) $sourceMtime = max($sourceMtime, filemtime($picCommentFile));
            if ($jsonMtime >= $sourceMtime && $jsonMtime >= $configMtime) $cacheValid = true;
        }

        if ($cacheValid) {
            echo "Processing Album: $albumName... (Cache Valid)\n";
            $cachedData = json_decode(file_get_contents($jsonFile), true);
            foreach ($cachedData['photos'] as $p) {
                $sid = $p['shortIdStart'];
                $shortUrlList[$sid] = $albumName . '/' . $p['filename'];
                foreach ($thumbConfigs as $idx => $conf) {
                    $shortUrlList[$sid + $idx + 1] = $albumName . '/Thumbnail/' . getThumbFilename($p['filename'], $conf['id']);
                }
            }
            $photoCount = count($cachedData['photos']);
            $albumDate = ''; 
            if (file_exists($commentAlbumFile)) {
                $parts = explode('|', file_get_contents($commentAlbumFile));
                if (isset($parts[3])) $albumDate = trim($parts[3]);
            }
            if (empty($albumDate)) $albumDate = date('Ymd', filemtime($albumPath));
            $finalCoverUrl = '';
            if (!empty($cachedData['photos'])) {
                $firstPhoto = $cachedData['photos'][0];
                $previewId = getConfigIdByMode($thumbConfigs, 'PreviewIcon');
                $finalCoverUrl = ($previewId && isset($firstPhoto['sizes'][$previewId])) ? $firstPhoto['sizes'][$previewId] : $firstPhoto['src'];
            }
            $allAlbumsList[] = array('name' => $cachedData['name'], 'id' => $albumName, 'desc' => $cachedData['desc'], 'cover' => $finalCoverUrl, 'count' => $photoCount, 'date' => $albumDate, 'link' => '#album=' . urlencode($albumName));
            continue;
        }

        echo "Processing Album: $albumName...\n";
        $thumbDir = $albumPath . '/Thumbnail';
        if (!is_dir($thumbDir)) mkdir($thumbDir, 0777, true);
        $photos = glob($albumPath . '/*.jpg');
        $photoCount = count($photos);
        
        $displayAlbumName = $albumName; $albumDesc = ''; $albumCover = ''; $albumDate = '';
        if (file_exists($commentAlbumFile)) {
            $parts = explode('|', file_get_contents($commentAlbumFile));
            if (isset($parts[0]) && !empty($parts[0])) $displayAlbumName = trim($parts[0]);
            if (isset($parts[1])) $albumDesc = trim($parts[1]);
            if (isset($parts[2]) && !empty($parts[2])) $albumCover = trim($parts[2]);
            if (isset($parts[3])) $albumDate = trim($parts[3]);
        }
        if (empty($albumDate)) $albumDate = date('Ymd', filemtime($albumPath));
        $albumDescHtml = !empty($albumDesc) ? $tm->render($tm->getSubTemplate('tmpl_album_desc_inline'), array('desc' => $albumDesc)) : '';

        $photoMeta = array(); 
        if (file_exists($picCommentFile)) {
            $lines = file($picCommentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $p = explode('|', $line);
                if (count($p) >= 1) $photoMeta[trim($p[0])] = array('title' => isset($p[1]) && !empty($p[1]) ? trim($p[1]) : trim($p[0]), 'desc' => isset($p[2]) ? trim($p[2]) : '');
            }
        }

        $albumPhotosJson = array();
        foreach ($photos as $photoPath) {
            $filename = basename($photoPath);
            $meta = isset($photoMeta[$filename]) ? $photoMeta[$filename] : array('title' => $filename, 'desc' => '');
            $originalRelPath = $albumName . '/' . $filename;
            if (isset($existingShortUrls[$originalRelPath])) {
                $photoShortIdStart = $existingShortUrls[$originalRelPath];
            } else {
                $photoShortIdStart = $nextAvailableId;
                $nextAvailableId += (count($thumbConfigs) + 1);
            }
            $shortUrlList[$photoShortIdStart] = $originalRelPath;
            $sizes = array();
            foreach ($thumbConfigs as $idx => $conf) {
                $destName = getThumbFilename($filename, $conf['id']);
                $destPath = $thumbDir . '/' . $destName;
                if (!$skipThumbnails) generateThumbnail($photoPath, $destPath, $conf['width'], $conf['quality']);
                $tRelPath = $albumName . '/Thumbnail/' . $destName;
                $shortUrlList[$photoShortIdStart + $idx + 1] = $tRelPath;
                $sizes[$conf['id']] = $baseUrl . '/' . $tRelPath;
            }
            $albumPhotosJson[] = array('filename' => $filename, 'title' => $meta['title'], 'desc' => $meta['desc'], 'src' => $baseUrl . '/' . $originalRelPath, 'sizes' => $sizes, 'exif' => getExifData($photoPath), 'shortIdStart' => $photoShortIdStart);
        }

        // 清理孤立縮圖
        if (is_dir($thumbDir)) {
            $existingThumbs = glob($thumbDir . '/*.jpg');
            $activePhotoNames = array(); foreach ($photos as $p) { $activePhotoNames[] = basename($p); }
            foreach ($existingThumbs as $thumbPath) {
                $tName = basename($thumbPath); $foundOriginal = false;
                foreach ($thumbConfigs as $conf) {
                    $suffix = '_' . $conf['id'] . '.jpg';
                    if (strpos($tName, $suffix) !== false) {
                        $potentialOriginal = str_replace($suffix, '.jpg', $tName);
                        if (in_array($potentialOriginal, $activePhotoNames)) { $foundOriginal = true; break; }
                    }
                }
                if (!$foundOriginal) { unlink($thumbPath); echo "Removed orphaned thumbnail: $tName\n"; }
            }
        }

        if (empty($albumCover) && !empty($photos)) $albumCover = basename($photos[0]);
        $finalCoverUrl = '';
        if (!empty($albumCover)) {
            $coverFilename = basename($albumCover);
            $previewId = getConfigIdByMode($thumbConfigs, 'PreviewIcon');
            $thumbCoverName = $previewId ? getThumbFilename($coverFilename, $previewId) : getThumbFilename($coverFilename, $thumbConfigs[0]['id']);
            $finalCoverUrl = file_exists($thumbDir . '/' . $thumbCoverName) ? $baseUrl . '/' . $albumName . '/Thumbnail/' . $thumbCoverName : $baseUrl . '/' . $albumName . '/' . $coverFilename;
        }

        $singleAlbumData = array('name' => $displayAlbumName, 'desc' => $albumDesc, 'desc_html' => $albumDescHtml, 'photos' => $albumPhotosJson);
        safe_file_put_contents($jsonFile, json_encode($singleAlbumData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $allAlbumsList[] = array('name' => $displayAlbumName, 'id' => $albumName, 'desc' => $albumDesc, 'cover' => $finalCoverUrl, 'count' => $photoCount, 'date' => $albumDate, 'link' => '#album=' . urlencode($albumName));
    }
}

// 清理孤立 JSON
if (is_dir($jsonDir)) {
    foreach (glob($jsonDir . '/*.json') as $jsonPath) {
        $jName = basename($jsonPath, '.json');
        if ($jName !== 'index' && !is_dir($collectionDir . '/' . $jName)) { unlink($jsonPath); echo "Removed orphaned JSON: $jName.json\n"; }
    }
}

usort($allAlbumsList, function($a, $b) { return strcmp($b['date'], $a['date']); });
$indexJson = array('items' => $allAlbumsList, 'pagination' => array('currentPage' => 1, 'totalPages' => 1, 'totalItems' => count($allAlbumsList), 'itemsPerPage' => count($allAlbumsList)));
safe_file_put_contents($jsonDir . '/index.json', json_encode($indexJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Generated: api/json/index.json\n";

if (!empty($shortUrlList)) {
    ksort($shortUrlList);
    $shortUrlContent = "";
    foreach ($shortUrlList as $id => $path) { $shortUrlContent .= $id . "|" . $path . "\n"; }
    safe_file_put_contents($shortUrlFile, $shortUrlContent);
    echo "Generated: shorturl.txt (" . count($shortUrlList) . " entries)\n";
}

echo "Album generation complete!\n";
