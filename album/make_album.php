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
$longopts = array("skip-thumb", "force", "force-json", "force-thumb", "help");
$options = getopt("sfh", $longopts);

if (isset($options['h']) || isset($options['help'])) {
    echo "Baxermux Album Generator\n";
    echo "Usage: php make_album.php [options]\n\n";
    echo "Options:\n";
    echo "  -s, --skip-thumb    建立 JSON 就好，完全不處理縮圖 (Skip thumbnails)\n";
    echo "  -f, --force         強制完整重跑：JSON 與 縮圖全部重新產生 (Force all)\n";
    echo "  --force-json        強制重新產生 JSON 資料 (不影響縮圖快取判斷)\n";
    echo "  --force-thumb       強制重新產生所有規格的縮圖 (不影響 JSON 快取判斷)\n";
    echo "  -h, --help          顯示此說明文件\n";
    exit(0);
}

$skipThumbnails = (isset($options['s']) || isset($options['skip-thumb']));
$forceAll = (isset($options['f']) || isset($options['force']));
$forceJson = ($forceAll || isset($options['force-json']));
$forceThumb = ($forceAll || isset($options['force-thumb']));

// --- 讀取配置與現有資料 ---
$compressionFile = $baseDir . '/config/compression.json';
$thumbConfigs = array();
$configMtime = 0;
if (file_exists($compressionFile)) {
    $thumbConfigs = json_decode(file_get_contents($compressionFile), true);
    $configMtime = filemtime($compressionFile);
} else {
    $thumbConfigs = array(array('id' => 'thumb', 'width' => 800, 'quality' => 90, 'mode' => 'PreviewIcon'));
}

$existingShortUrls = array();
$maxShortId = -1;
$shortUrlFile = $baseDir . '/shorturl.txt';
if (file_exists($shortUrlFile)) {
    $lines = file($shortUrlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode('|', $line);
        if (count($parts) >= 2) {
            $id = (int)$parts[0]; $path = $parts[1];
            $existingShortUrls[$path] = $id;
            if ($id > $maxShortId) $maxShortId = $id;
        }
    }
}
$nextAvailableId = $maxShortId + 1;
$shortUrlList = array();

function safe_file_put_contents($path, $content) {
    $tmp = $path . '.tmp.' . uniqid();
    if (file_put_contents($tmp, $content) !== false) {
        if (rename($tmp, $path)) return true;
    }
    if (file_exists($tmp)) @unlink($tmp);
    return false;
}

function getConfigIdByMode($configs, $mode) {
    foreach ($configs as $conf) { if ($conf['mode'] === $mode) return $conf['id']; }
    return null;
}

require_once $baseDir . '/../PHP_LIB/TemplateManager.php';
$tm = new TemplateManager();
if (!file_exists($templateFile)) die("Template file not found\n");
$tm->load($templateFile);

function getThumbFilename($originalFilename, $prefix) {
    $info = pathinfo($originalFilename);
    return $info['filename'] . '_' . $prefix . '.' . $info['extension'];
}

function getExifData($file) {
    if (!function_exists('exif_read_data')) return null;
    $exif = @exif_read_data($file);
    if (!$exif) return null;
    $make = isset($exif['Make']) ? trim($exif['Make']) : '未知';
    $model = isset($exif['Model']) ? trim($exif['Model']) : '未知';
    $iso = isset($exif['ISOSpeedRatings']) ? (is_array($exif['ISOSpeedRatings']) ? $exif['ISOSpeedRatings'][0] : $exif['ISOSpeedRatings']) : '未知';
    $date = isset($exif['DateTimeOriginal']) ? $exif['DateTimeOriginal'] : (isset($exif['DateTime']) ? $exif['DateTime'] : '未知');
    return array('make' => $make, 'model' => $model, 'date' => $date, 'iso' => $iso); // 簡化處理
}

function generateThumbnail($src, $dest, $maxSize, $quality) {
    global $forceThumb, $configMtime;
    if (file_exists($dest) && !$forceThumb) {
        if (filemtime($dest) >= filemtime($src) && filemtime($dest) >= $configMtime) return;
    }
    if (extension_loaded('imagick')) {
        try {
            $image = new Imagick($src); $image->setImageCompressionQuality($quality);
            $w = $image->getImageWidth(); $h = $image->getImageHeight();
            if ($w <= $maxSize && $h <= $maxSize) { copy($src, $dest); $image->clear(); return; }
            $image->resizeImage($maxSize, $maxSize, Imagick::FILTER_LANCZOS, 1, true);
            $image->writeImage($dest); $image->clear();
            echo "Created (IM): " . basename($dest) . "\n"; return;
        } catch (Exception $e) {}
    }
    // GD Fallback (略)
    echo "Created (GD/Skipped): " . basename($dest) . "\n";
}

// 1. 生成 Shell
$indexBody = $tm->getSubTemplate('tmpl_app_container');
$appVersion = 'v1.0.0';
if (file_exists($baseDir . '/../admin/version_config.php')) {
    include $baseDir . '/../admin/version_config.php';
    if (defined('CURRENT_VERSION')) $appVersion = CURRENT_VERSION;
}

// 讀取相簿後台設定以獲取 Header 變數
$album_title = "Baxermux的相簿";
$album_description = "ersalblog的延伸子專案相簿服務。";
$album_introduce = "放一些Blog用到的素材照片.";
$album_preview = "";
$album_site_url = "";
$album_lang = "zh_TW";
$album_timezone = "Asia/Taipei";

$configPhpFile = $baseDir . '/config/config.php';
if (file_exists($configPhpFile)) {
    include $configPhpFile;
}

$indexHtml = $tm->render($tm->getSource(), array(
    'album_title' => $album_title,
    'album_description' => $album_description,
    'album_introduce' => $album_introduce,
    'album_preview' => $album_preview,
    'album_site_url' => $album_site_url,
    'album_lang' => $album_lang,
    'content_body' => $indexBody,
    'version' => $appVersion
));
file_put_contents($baseDir . '/album.html', $indexHtml);

// 2. 遍歷相簿
$allAlbumsList = array();
if (is_dir($collectionDir)) {
    foreach (scandir($collectionDir) as $albumName) {
        if ($albumName === '.' || $albumName === '..') continue;
        $albumPath = $collectionDir . '/' . $albumName;
        if (!is_dir($albumPath)) continue;

        $jsonFile = $jsonDir . '/' . $albumName . '.json';
        $commentAlbumFile = $albumPath . '/comment_album.txt';
        $picCommentFile = $albumPath . '/comment_pic.txt';
        
        $jsonCacheValid = false;
        if (file_exists($jsonFile) && !$forceJson) {
            $jtime = filemtime($jsonFile); $stime = filemtime($albumPath);
            if (file_exists($commentAlbumFile)) $stime = max($stime, filemtime($commentAlbumFile));
            if (file_exists($picCommentFile)) $stime = max($stime, filemtime($picCommentFile));
            if ($jtime >= $stime && $jtime >= $configMtime) $jsonCacheValid = true;
        }

        if ($jsonCacheValid) {
            echo "Album: $albumName (JSON Cache Valid)\n";
            $data = json_decode(file_get_contents($jsonFile), true);
            foreach ($data['photos'] as $p) {
                $sid = $p['shortIdStart'];
                $shortUrlList[$sid] = $albumName . '/' . $p['filename'];
                foreach ($thumbConfigs as $idx => $conf) {
                    $tRel = $albumName . '/Thumbnail/' . getThumbFilename($p['filename'], $conf['id']);
                    $shortUrlList[$sid + $idx + 1] = $tRel;
                    // 即使 JSON 快取，只要不 skip-thumb，就要檢查縮圖
                    if (!$skipThumbnails) generateThumbnail($albumPath . '/' . $p['filename'], $baseDir . '/Collection/' . $tRel, $conf['width'], $conf['quality']);
                }
            }
            $allAlbumsList[] = array('name' => $data['name'], 'id' => $albumName, 'desc' => $data['desc'], 'cover' => '', 'count' => count($data['photos']), 'date' => '', 'link' => '#album='.urlencode($albumName));
            continue;
        }

        echo "Album: $albumName (Processing...)\n";
        $thumbDir = $albumPath . '/Thumbnail'; if (!is_dir($thumbDir)) mkdir($thumbDir, 0777, true);
        $photos = glob($albumPath . '/*.jpg');
        $albumPhotosJson = array();
        
        foreach ($photos as $photoPath) {
            $filename = basename($photoPath);
            $rel = $albumName . '/' . $filename;
            $sid = isset($existingShortUrls[$rel]) ? $existingShortUrls[$rel] : $nextAvailableId;
            if ($sid === $nextAvailableId) $nextAvailableId += (count($thumbConfigs) + 1);
            
            $shortUrlList[$sid] = $rel;
            $sizes = array();
            foreach ($thumbConfigs as $idx => $conf) {
                $tName = getThumbFilename($filename, $conf['id']);
                if (!$skipThumbnails) generateThumbnail($photoPath, $thumbDir . '/' . $tName, $conf['width'], $conf['quality']);
                $shortUrlList[$sid + $idx + 1] = $albumName . '/Thumbnail/' . $tName;
                $sizes[$conf['id']] = 'Collection/' . $albumName . '/Thumbnail/' . $tName;
            }
            $albumPhotosJson[] = array('filename' => $filename, 'src' => 'Collection/'.$rel, 'sizes' => $sizes, 'shortIdStart' => $sid, 'title' => $filename, 'desc' => '');
        }

        $singleData = array('name' => $albumName, 'desc' => '', 'photos' => $albumPhotosJson);
        safe_file_put_contents($jsonFile, json_encode($singleData, JSON_UNESCAPED_UNICODE));
        $allAlbumsList[] = array('name' => $albumName, 'id' => $albumName, 'desc' => '', 'cover' => '', 'count' => count($photos), 'date' => date('Ymd'), 'link' => '#album='.urlencode($albumName));
    }
}

// 3. 清理與存檔
safe_file_put_contents($jsonDir . '/index.json', json_encode(array('items' => $allAlbumsList), JSON_UNESCAPED_UNICODE));
ksort($shortUrlList);
$shortUrlContent = "";
foreach ($shortUrlList as $id => $path) { $shortUrlContent .= $id . "|" . $path . "\n"; }
safe_file_put_contents($shortUrlFile, $shortUrlContent);
echo "Done.\n";
