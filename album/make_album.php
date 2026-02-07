<?php
// 設定時區與編碼
date_default_timezone_set('Asia/Taipei');
mb_internal_encoding('UTF-8');

// 定義路徑
$baseDir = __DIR__;
$collectionDir = $baseDir . '/Collection';
$staticDir = $baseDir . '/static';
$viewDir = $baseDir . '/view';
$templateFile = $staticDir . '/album_template.html';

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
    'thumb'   => array('size' => 800,  'quality' => 90)
);

// 確保目錄存在
if (!file_exists($viewDir)) mkdir($viewDir, 0777, true);

// 引入樣板管理器
require_once $baseDir . '/../PHP_LIB/TemplateManager.php';
$tm = new TemplateManager();
if (!file_exists($templateFile)) {
    die("Template file not found: $templateFile");
}
$tm->load($templateFile);

// ==========================================
// 輔助函式：產生符合規則的縮圖檔名
// 範例: IMG_123_thumb.jpg
// ==========================================
function getThumbFilename($originalFilename, $prefix) {
    $info = pathinfo($originalFilename);
    return $info['filename'] . '_' . $prefix . '.' . $info['extension'];
}

// ==========================================
// 輔助函式：EXIF 讀取與格式化
// ==========================================
function getExifHtml($file) {
    if (!function_exists('exif_read_data')) {
        return '<div class="col-12 text-muted small">環境未啟用 EXIF 模組</div>';
    }
    $exif = @exif_read_data($file);
    if (!$exif) {
        return '<div class="col-12 text-muted small">無 EXIF 資訊</div>';
    }

    $make = isset($exif['Make']) ? $exif['Make'] : '未知';
    $model = isset($exif['Model']) ? $exif['Model'] : '未知';
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

    return "
        <div class=\"col-6 col-md-4 exif-item\"><span class=\"text-muted small d-block\">相機機型</span><strong>{$make} {$model}</strong></div>
        <div class=\"col-6 col-md-4 exif-item\"><span class=\"text-muted small d-block\">光圈值</span><strong>{$aperture}</strong></div>
        <div class=\"col-6 col-md-4 exif-item\"><span class=\"text-muted small d-block\">快門速度</span><strong>{$shutter}</strong></div>
        <div class=\"col-6 col-md-4 exif-item\"><span class=\"text-muted small d-block\">感光度</span><strong>ISO {$iso}</strong></div>
        <div class=\"col-6 col-md-4 exif-item\"><span class=\"text-muted small d-block\">焦距</span><strong>{$focal}</strong></div>
        <div class=\"col-6 col-md-4 exif-item\"><span class=\"text-muted small d-block\">拍攝日期</span><strong>{$date}</strong></div>
    ";
}

// ==========================================
// 輔助函式：生成單一縮圖
// ==========================================
function createSingleThumbnail($src, $dest, $maxSize, $quality) {
    global $forceThumbnail;
    if (file_exists($dest) && !$forceThumbnail) return;

    list($width, $height, $type) = getimagesize($src);
    
    if ($width <= $maxSize && $height <= $maxSize) {
        copy($src, $dest);
        echo "Copied (Small Original): " . basename($dest) . "\n";
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

    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagejpeg($thumb, $dest, $quality);
    imagedestroy($thumb);
    imagedestroy($source);
    echo "Created thumbnail: " . basename($dest) . "\n";
}

// ==========================================
// 1. 生成相簿首頁 (album.html)
// ==========================================
$indexBody = '<div class="album-grid" id="album-list-container">
    <div class="text-center py-5" style="grid-column: 1/-1;">
        <p class="text-muted">載入相簿中...</p>
    </div>
</div>';

$indexScript = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    loadAlbumList("api/api_album.php");
});
</script>';

$indexHtml = $tm->render($tm->getSource(), array(
    'path_to_static' => 'static/',
    'path_to_config' => './',
    'page_title' => '首頁',
    'album_header' => '',
    'content_body' => $indexBody,
    'custom_scripts' => $indexScript
));
file_put_contents($baseDir . '/album.html', $indexHtml);
echo "Generated: album.html\n";

// ==========================================
// 2. 遍歷相簿生成靜態頁
// ==========================================
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
        $commentAlbumFile = $albumPath . '/comment_album.txt';
        if (file_exists($commentAlbumFile)) {
            $content = file_get_contents($commentAlbumFile);
            $parts = explode('|', $content);
            if (isset($parts[0]) && !empty($parts[0])) $displayAlbumName = trim($parts[0]);
            if (isset($parts[1])) $albumDesc = trim($parts[1]);
        }

        $photoMeta = array(); // Map: filename => array('title' => ..., 'desc' => ...)
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

        // --- 生成相簿內頁 (靜態分頁) ---
        $photosPerPage = 24;
        $totalPhotoPages = ceil($photoCount / $photosPerPage);
        if ($totalPhotoPages == 0) $totalPhotoPages = 1;

        $photoIdMap = array(); // filename => startId

        for ($p = 1; $p <= $totalPhotoPages; $p++) {
            $offset = ($p - 1) * $photosPerPage;
            $pagePhotos = array_slice($photos, $offset, $photosPerPage);
            $pageFilename = ($p == 1) ? $albumName . '.html' : $albumName . '_page' . $p . '.html';
            
            $albumBodyHeader = '
            <div class="d-flex align-center justify-between mb-4">
                <nav class="breadcrumb">
                    <div class="breadcrumb-item"><a href="../album.html">首頁</a></div>
                    <div class="breadcrumb-item active">' . htmlspecialchars($albumName) . '</div>
                </nav>
                <div class="d-flex align-center gap-2">
                    <span class="badge"><i class="bi bi-image"></i> ' . $photoCount . ' 張相片</span>
                    <a href="../album.html" class="btn btn-outline"><i class="bi bi-house-door"></i> 返回首頁</a>
                </div>
            </div>';

            $photoListHtml = '';
            foreach ($pagePhotos as $photoPath) {
                $filename = basename($photoPath);
                $meta = isset($photoMeta[$filename]) ? $photoMeta[$filename] : array('title' => $filename, 'desc' => '');
                
                // 記錄此照片的起始 ID
                $photoIdMap[$filename] = $currentShortId;

                // --- ShortURL ID 計算 (Grid View 雖不顯示分享但仍需佔位以保持 ID 同步) ---
                // 此處為了保持與詳情頁的 ID 計算一致，我們必須模擬一次完整的 ID 分配過程
                // 順序: Original -> thumbXL -> thumbL -> thumbM -> thumb
                
                // 1. Original
                $shortUrlList[] = $albumName . '/' . $filename;
                $currentShortId++; 

                // 2. Thumbnails
                foreach ($thumbConfigs as $prefix => $conf) {
                    $destName = getThumbFilename($filename, $prefix);
                    $destPath = $thumbDir . '/' . $destName;
                    
                    if (!$skipThumbnails) {
                        createSingleThumbnail($photoPath, $destPath, $conf['size'], $conf['quality']);
                    }
                    
                    $shortUrlList[] = $albumName . '/Thumbnail/' . $destName;
                    $currentShortId++;
                }

                $gridThumbFile = getThumbFilename($filename, 'thumb');
                $gridImgSrc = file_exists($thumbDir . '/' . $gridThumbFile) 
                    ? '../Collection/' . $albumName . '/Thumbnail/' . $gridThumbFile 
                    : '../Collection/' . $albumName . '/' . $filename;

                $photoListHtml .= $tm->render($tm->getSubTemplate('tmpl_album_photo_item'), array(
                    'photoPageLink' => $albumName . '/' . $filename . '.html',
                    'imgSrc' => $gridImgSrc,
                    'filename' => $filename,
                    'photoDesc' => htmlspecialchars($meta['title'])
                ));
            }

            // 分頁導覽
            $paginationHtml = '';
            if ($totalPhotoPages > 1) {
                $paginationHtml = '<div id="pagination-container"><div class="pagination">';
                
                $prevLink = ($p <= 2) ? $albumName . '.html' : $albumName . '_page' . ($p - 1) . '.html';
                $paginationHtml .= '<span class="page-item ' . (($p <= 1) ? 'disabled' : '') . '"><a class="page-link" href="' . $prevLink . '"><i class="bi bi-chevron-left"></i></a></span>';

                for ($i = 1; $i <= $totalPhotoPages; $i++) {
                    $link = ($i == 1) ? $albumName . '.html' : $albumName . '_page' . $i . '.html';
                    $activeClass = ($i == $p) ? 'active' : '';
                    $paginationHtml .= '<span class="page-item ' . $activeClass . '"><a class="page-link" href="' . $link . '">' . $i . '</a></span>';
                }

                $nextLink = $albumName . '_page' . ($p + 1) . '.html';
                $paginationHtml .= '<span class="page-item ' . (($p >= $totalPhotoPages) ? 'disabled' : '') . '"><a class="page-link" href="' . $nextLink . '"><i class="bi bi-chevron-right"></i></a></span>';
                $paginationHtml .= '</div></div>';
            }

            // 生成相簿標頭 HTML
            $albumHeaderHtml = '
            <div class="album-header-box">
                <h2 class="fw-bold mb-2" style="font-size:1.25rem">' . htmlspecialchars($displayAlbumName) . '</h2>
                ' . (!empty($albumDesc) ? '<p class="text-muted small mb-0">' . htmlspecialchars($albumDesc) . '</p>' : '') . '
            </div>';

            $albumViewHtml = $tm->render($tm->getSource(), array(
                'path_to_static' => '../static/',
                'path_to_config' => '../',
                'page_title' => $displayAlbumName . ($p > 1 ? " (頁 $p)" : ""),
                'album_header' => $albumHeaderHtml,
                'content_body' => $albumBodyHeader . '<div class="album-grid">' . $photoListHtml . '</div>' . $paginationHtml,
                'custom_scripts' => ''
            ));
            $albumViewHtml = $tm->removeTags($albumViewHtml, 'template');
            file_put_contents($viewDir . '/' . $pageFilename, $albumViewHtml);
        }

        // --- 生成照片詳情頁 ---
        $photoViewDir = $viewDir . '/' . $albumName;
        if (!file_exists($photoViewDir)) mkdir($photoViewDir, 0777, true);
        $photoList = array_values(array_map('basename', $photos));
        $totalPhotos = count($photoList);

        foreach ($photoList as $index => $filename) {
            // 計算所屬頁碼
            $belongPage = floor($index / $photosPerPage) + 1;
            $backLink = ($belongPage == 1) ? '../' . $albumName . '.html' : '../' . $albumName . '_page' . $belongPage . '.html';

            $meta = isset($photoMeta[$filename]) ? $photoMeta[$filename] : array('title' => $filename, 'desc' => '');
            
            $mainThumbFile = getThumbFilename($filename, 'thumbL');
            $mainImgSrc = file_exists($thumbDir . '/' . $mainThumbFile)
                ? '../../Collection/' . $albumName . '/Thumbnail/' . $mainThumbFile
                : '../../Collection/' . $albumName . '/' . $filename;

            $xlThumbFile = getThumbFilename($filename, 'thumbXL');
            $xlImgSrc = file_exists($thumbDir . '/' . $xlThumbFile)
                ? '../../Collection/' . $albumName . '/Thumbnail/' . $xlThumbFile
                : '../../Collection/' . $albumName . '/' . $filename;

            $prevLink = $photoList[($index - 1 + $totalPhotos) % $totalPhotos] . '.html';
            $nextLink = $photoList[($index + 1) % $totalPhotos] . '.html';

            $exifHtml = getExifHtml($collectionDir . '/' . $albumName . '/' . $filename);
            
            // 獲取 ShortID
            $shortIdStart = isset($photoIdMap[$filename]) ? $photoIdMap[$filename] : 0;

            $photoBody = $tm->render($tm->getSubTemplate('tmpl_photo_detail_view'), array(
                'pathToHome' => '../../',
                'albumName' => $albumName,
                'filename' => $filename,
                'prevLink' => $prevLink,
                'nextLink' => $nextLink,
                'imgSrc' => $mainImgSrc,
                'imgSrcXL' => $xlImgSrc,
                'imgSrcOriginal' => '../../Collection/' . $albumName . '/' . $filename,
                'shortIdStart' => $shortIdStart,
                'photoTitle' => htmlspecialchars($meta['title']),
                'photoDesc' => htmlspecialchars($meta['desc']),
                'exif_info' => $exifHtml
            ));
            
            // 替換返回連結
            $defaultBackLink = '../' . $albumName . '.html';
            if ($backLink !== $defaultBackLink) {
                $photoBody = str_replace('href="' . $defaultBackLink . '"', 'href="' . $backLink . '"', $photoBody);
            }

            $photoBody = str_replace('imgSrc', 'id="photo-main-viewer" src', $photoBody);
            $photoScript = '';

            $photoPageHtml = $tm->render($tm->getSource(), array(
                'path_to_static' => '../../static/',
                'path_to_config' => '../../',
                'page_title' => $filename . ' - ' . $albumName,
                'album_header' => '', 
                'content_body' => $photoBody,
                'custom_scripts' => $photoScript
            ));
            $photoPageHtml = $tm->removeTags($photoPageHtml, 'template');
            file_put_contents($photoViewDir . '/' . $filename . '.html', $photoPageHtml);
        }
    }
}

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