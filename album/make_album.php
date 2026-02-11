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

// 用於記錄 shorturl 的清單
$shortUrlList = array();
$currentShortId = 0; // 全域 ID 計數器

// 縮圖配置 (尺寸由大到小)
$thumbConfigs = array(
    'thumbXL' => array('size' => 2048, 'quality' => 95),
    'thumbL'  => array('size' => 1600, 'quality' => 92),
    'thumbM'  => array('size' => 1024, 'quality' => 90),
    'thumb'   => array('size' => 800,  'quality' => 90),
    'thumbXS'  => array('size' => 320,  'quality' => 85)
);

// 引入樣板管理器 (用於生成 album.html)
require_once $baseDir . '/../PHP_LIB/TemplateManager.php';
$tm = new TemplateManager();
if (!file_exists($templateFile)) {
    die("Template file not found: $templateFile");
}
$tm->load($templateFile);

// ==========================================
// 輔助函式：產生符合規則的縮圖檔名
// ==========================================
function getThumbFilename($originalFilename, $prefix) {
    $info = pathinfo($originalFilename);
    return $info['filename'] . '_' . $prefix . '.' . $info['extension'];
}

// ==========================================
// 輔助函式：EXIF 讀取 (回傳陣列)
// ==========================================
function getExifData($file) {
    if (!function_exists('exif_read_data')) {
        return null;
    }
    $exif = @exif_read_data($file);
    if (!$exif) {
        return null;
    }

    return parseExifArray($exif);
}

// ==========================================
// 輔助函式：將 EXIF 分數格式轉為浮點數
// ==========================================
function exifToFloat($value) {
    $parts = explode('/', $value);
    if (count($parts) <= 0) return 0;
    if (count($parts) == 1) return (float)$parts[0];
    return (float)$parts[0] / (float)$parts[1];
}

// ==========================================
// 輔助函式：解析 EXIF 陣列為統一格式
// ==========================================
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
        'make' => $make,
        'model' => $model,
        'aperture' => $aperture,
        'shutter' => $shutter,
        'iso' => $iso,
        'focal' => $focal,
        'date' => $date,
        'gps' => $gps
    );
}

// ==========================================
// 輔助函式：生成單一縮圖 (ImageMagick 優先，GD 為備案)
// ==========================================
function generateThumbnail($src, $dest, $maxSize, $quality) {
    global $forceThumbnail;
    if (file_exists($dest) && !$forceThumbnail) return;

    // 嘗試使用 ImageMagick
    if (extension_loaded('imagick')) {
        try {
            $image = new Imagick($src);
            $image->setImageCompressionQuality($quality);
            
            // 保留 EXIF (Profiles)
            // 注意：某些縮圖可能不需要太大的 Profile，但要求保留則保留
            // $image->stripImage(); // 移除所有 Profile (若要極致瘦身可開)

            // 取得原始尺寸
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();

            // 如果原始圖小於目標尺寸，則直接複製 (或不處理)
            if ($width <= $maxSize && $height <= $maxSize) {
                // 如果需要轉檔或壓縮，還是走下面流程，這裡簡單起見直接 copy
                copy($src, $dest);
                echo "Copied (Small Original IM): " . basename($dest) . "\n";
                $image->clear();
                return;
            }

            // 計算新尺寸 (保持比例)
            $ratio = $width / $height;
            if ($width > $height) {
                $newWidth = $maxSize;
                $newHeight = $maxSize / $ratio;
            } else {
                $newHeight = $maxSize;
                $newWidth = $maxSize * $ratio;
            }

            // 縮放 (使用 Lanczos 濾鏡獲得最佳品質)
            $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
            
            // 寫入檔案
            $image->writeImage($dest);
            $image->clear();
            echo "Created thumbnail (Imagick): " . basename($dest) . "\n";
            return;

        } catch (Exception $e) {
            echo "Imagick failed: " . $e->getMessage() . ". Falling back to GD.\n";
        }
    }

    // GD Fallback
    list($width, $height, $type) = getimagesize($src);
    
    if ($width <= $maxSize && $height <= $maxSize) {
        copy($src, $dest);
        echo "Copied (Small Original GD): " . basename($dest) . "\n";
        return;
    }

    $ratio = $width / $height;
    if ($width > $height) {
        $newWidth = $maxSize;
        $newHeight = $maxSize / $ratio;
    } else {
        $newHeight = $maxSize;
        $newWidth = $maxSize * $ratio;
    }

    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    switch ($type) {
        case IMAGETYPE_JPEG: $source = imagecreatefromjpeg($src); break;
        case IMAGETYPE_PNG: $source = imagecreatefrompng($src); break;
        case IMAGETYPE_GIF: $source = imagecreatefromgif($src); break;
        default: return;
    }

    // GD: 保留 EXIF 需要額外處理 (copy exif data)，GD 本身 resize 會丟失 EXIF。
    // 若必須保留 EXIF 且 ImageMagick 不可用，需使用 pel 或 exif_read_data + iptcembed 等複雜操作。
    // 這裡 GD 版本暫時維持不保留 EXIF (這是 GD 的限制，除非引入額外函式庫)。
    
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagejpeg($thumb, $dest, $quality);
    imagedestroy($thumb);
    imagedestroy($source);
    echo "Created thumbnail (GD): " . basename($dest) . "\n";
}

// ==========================================
// 1. 生成相簿首頁 (album.html) - SPA Shell
// ==========================================
// 內容容器，從樣板讀取
$indexBody = $tm->getSubTemplate('tmpl_app_container');

// 不再需要在這裡寫 loadAlbumList，改由 album.js 的 Router 處理
$indexScript = '';

// 引入版本資訊
$appVersion = 'v1.0.0';
if (file_exists($baseDir . '/../admin/version_config.php')) {
    include $baseDir . '/../admin/version_config.php';
    if (defined('APP_VERSION')) $appVersion = APP_VERSION;
}

$indexHtml = $tm->render($tm->getSource(), array(
    'path_to_static' => 'static/',
    'path_to_config' => 'config/',
    'page_title' => '相簿首頁',
    'album_header' => '',
    'content_body' => $indexBody,
    'custom_scripts' => $indexScript,
    'version' => $appVersion
));
// 移除多餘的 template 標籤
// $indexHtml = $tm->removeTags($indexHtml, 'template'); // Keep templates for SPA

file_put_contents($baseDir . '/album.html', $indexHtml);
echo "Generated: album.html (SPA Shell)\n";


// ==========================================
// 2. 遍歷相簿生成 JSON 資料與縮圖
// ==========================================
$allAlbumsList = array();
$baseUrl = 'Collection'; 

if (is_dir($collectionDir)) {
    $albums = scandir($collectionDir);
    foreach ($albums as $albumName) {
        if ($albumName === '.' || $albumName === '..') continue;
        $albumPath = $collectionDir . '/' . $albumName;
        if (!is_dir($albumPath)) continue;

        echo "Processing Album: $albumName...\n";
        $thumbDir = $albumPath . '/Thumbnail';
        if (!is_dir($thumbDir)) mkdir($thumbDir, 0777, true);

        $photos = glob($albumPath . '/*.jpg');
        $photoCount = count($photos);
        
        // 讀取相簿資訊 (comment_album.txt)
        $displayAlbumName = $albumName;
        $albumDesc = '';
        $albumCover = '';
        $albumDate = '';

        $commentAlbumFile = $albumPath . '/comment_album.txt';
        if (file_exists($commentAlbumFile)) {
            $content = file_get_contents($commentAlbumFile);
            $parts = explode('|', $content);
            if (isset($parts[0]) && !empty($parts[0])) $displayAlbumName = trim($parts[0]);
            if (isset($parts[1])) $albumDesc = trim($parts[1]);
            if (isset($parts[2]) && !empty($parts[2])) $albumCover = trim($parts[2]);
            if (isset($parts[3])) $albumDate = trim($parts[3]);
        }

        if (empty($albumDate)) {
            $albumDate = date('Ymd', filemtime($albumPath));
        }

        // 渲染相簿描述 HTML (SPA 使用)
        $albumDescHtml = '';
        if (!empty($albumDesc)) {
            $albumDescHtml = $tm->render($tm->getSubTemplate('tmpl_album_desc_inline'), array('desc' => $albumDesc));
        }

        // 讀取照片註解
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

        // 處理照片與縮圖
        $albumPhotosJson = array();
        
        foreach ($photos as $photoPath) {
            $filename = basename($photoPath);
            $meta = isset($photoMeta[$filename]) ? $photoMeta[$filename] : array('title' => $filename, 'desc' => '');
            
            // --- ShortURL ID 計算 ---
            // 1. Original
            $shortUrlList[] = $albumName . '/' . $filename;
            $photoShortIdStart = $currentShortId; // 記錄此照片起始 ID
            $currentShortId++; 

            // 2. Thumbnails
            foreach ($thumbConfigs as $prefix => $conf) {
                $destName = getThumbFilename($filename, $prefix);
                $destPath = $thumbDir . '/' . $destName;
                
                if (!$skipThumbnails) {
                    generateThumbnail($photoPath, $destPath, $conf['size'], $conf['quality']);
                }
                
                $shortUrlList[] = $albumName . '/Thumbnail/' . $destName;
                $currentShortId++;
            }

            // EXIF
            $exifData = getExifData($photoPath);

            // 準備 JSON 資料
            $thumbName = getThumbFilename($filename, 'thumb');
            $thumbLName = getThumbFilename($filename, 'thumbL');
            $thumbXLName = getThumbFilename($filename, 'thumbXL');

            $albumPhotosJson[] = array(
                'filename' => $filename,
                'title' => $meta['title'],
                'desc' => $meta['desc'],
                'src' => $baseUrl . '/' . $albumName . '/' . $filename,
                'thumb' => $baseUrl . '/' . $albumName . '/Thumbnail/' . $thumbName,
                'thumbL' => $baseUrl . '/' . $albumName . '/Thumbnail/' . $thumbLName,
                'thumbXL' => $baseUrl . '/' . $albumName . '/Thumbnail/' . $thumbXLName,
                'exif' => $exifData,
                'shortIdStart' => $photoShortIdStart
            );
        }

        // 決定封面圖
        if (empty($albumCover) && !empty($photos)) {
            $albumCover = basename($photos[0]);
        }
        $finalCoverUrl = '';
        if (!empty($albumCover)) {
            $coverFilename = basename($albumCover);
            $info = pathinfo($coverFilename);
            $thumbCoverName = $info['filename'] . '_thumb.' . $info['extension'];
            // 檢查縮圖是否存在 (雖然剛剛應該已經生成了)
            if (file_exists($thumbDir . '/' . $thumbCoverName)) {
                $finalCoverUrl = $baseUrl . '/' . $albumName . '/Thumbnail/' . $thumbCoverName;
            } else {
                $finalCoverUrl = $baseUrl . '/' . $albumName . '/' . $coverFilename;
            }
        }

        // 產生單一相簿 JSON
        $singleAlbumData = array(
            'name' => $displayAlbumName,
            'desc' => $albumDesc,
            'desc_html' => $albumDescHtml,
            'photos' => $albumPhotosJson
        );
        file_put_contents($jsonDir . '/' . $albumName . '.json', json_encode($singleAlbumData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // 加入總表
        $allAlbumsList[] = array(
            'name' => $displayAlbumName, // Display Name
            'id' => $albumName, // Directory Name (used for ID/Hash)
            'desc' => $albumDesc,
            'cover' => $finalCoverUrl,
            'count' => $photoCount,
            'date' => $albumDate,
            'link' => '#album=' . urlencode($albumName)
        );
    }
}

// 排序總表
usort($allAlbumsList, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

// 生成 index.json
// 模擬 API 格式
$indexJson = array(
    'items' => $allAlbumsList,
    'pagination' => array(
        'currentPage' => 1,
        'totalPages' => 1,
        'totalItems' => count($allAlbumsList),
        'itemsPerPage' => count($allAlbumsList) // Client side handle pagination or show all
    )
);

file_put_contents($jsonDir . '/index.json', json_encode($indexJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Generated: api/json/index.json\n";


// ==========================================
// 4. 儲存 ShortURL 紀錄
// ==========================================
if (!empty($shortUrlList)) {
    $shortUrlContent = "";
    foreach ($shortUrlList as $index => $path) {
        $shortUrlContent .= $index . "|" . $path . "\n";
    }
    file_put_contents($baseDir . '/shorturl.txt', $shortUrlContent);
    echo "Generated: shorturl.txt (" . count($shortUrlList) . " entries)\n";
}

echo "Album generation complete!\n";
