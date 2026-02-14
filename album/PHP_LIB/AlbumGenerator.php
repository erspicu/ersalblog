<?php
/**
 * AlbumGenerator - 核心相簿處理邏輯
 * 支援 CLI 與 Web 後台共用，具備狀態合併之即時進度回報功能
 */
class AlbumGenerator {
    private $baseDir;
    private $collectionDir;
    private $jsonDir;
    private $thumbConfigs;
    private $configMtime;
    private $existingShortUrls = array();
    private $shortUrlList = array();
    private $nextAvailableId = 0;
    private $progressFile = null;
    private $finishedAlbums = array();
    private $progressState = array(
        'status' => 'idle',
        'message' => '',
        'album_name' => '',
        'album_current' => 0,
        'album_total' => 0,
        'photo_name' => '',
        'photo_current' => 0,
        'photo_total' => 0,
        'percent' => 0
    );

    public function __construct($baseDir) {
        $this->baseDir = rtrim($baseDir, '/');
        $this->collectionDir = $this->baseDir . '/Collection';
        $this->jsonDir = $this->baseDir . '/api/json';
        if (!file_exists($this->jsonDir)) mkdir($this->jsonDir, 0777, true);

        $compressionFile = $this->baseDir . '/config/compression.json';
        if (file_exists($compressionFile)) {
            $this->thumbConfigs = json_decode(file_get_contents($compressionFile), true);
            $this->configMtime = filemtime($compressionFile);
        } else {
            $this->thumbConfigs = array(array('id' => 'thumb', 'width' => 800, 'quality' => 90, 'mode' => 'PreviewIcon'));
            $this->configMtime = 0;
        }
        $this->loadShortUrls();
    }

    public function setProgressId($id) {
        $id = preg_replace('/[^A-Za-z0-9]/', '', $id);
        $this->progressFile = $this->jsonDir . '/rebuild_progress_' . $id . '.json';
    }

    private function log($msg) {
        $logFile = $this->baseDir . '/api/json/generator.log';
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
    }

    private function updateProgress($data) {
        if (!$this->progressFile) return;
        
        // 合併進度狀態，確保各項計數在不同階段都能保留
        $this->progressState = array_merge($this->progressState, $data);
        $this->progressState['finished_albums'] = $this->finishedAlbums;
        
        $content = json_encode($this->progressState, JSON_UNESCAPED_UNICODE);
        $tmp = $this->progressFile . '.tmp';
        file_put_contents($tmp, $content);
        @rename($tmp, $this->progressFile);
    }

    public function cleanProgress() {
        if ($this->progressFile && file_exists($this->progressFile)) {
            @unlink($this->progressFile);
        }
    }

    private function loadShortUrls() {
        $shortUrlFile = $this->baseDir . '/shorturl.txt';
        $maxShortId = -1;
        if (file_exists($shortUrlFile)) {
            $lines = file($shortUrlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $parts = explode('|', $line);
                if (count($parts) >= 2) {
                    $id = (int)$parts[0]; $path = $parts[1];
                    $this->existingShortUrls[$path] = $id;
                    $this->shortUrlList[$id] = $path; 
                    if ($id > $maxShortId) $maxShortId = $id;
                }
            }
        }
        $this->nextAvailableId = $maxShortId + 1;
    }

    public function checkEnvironment() {
        $report = array('imagick' => extension_loaded('imagick'), 'exif' => function_exists('exif_read_data'), 'gd' => extension_loaded('gd'), 'warnings' => array());
        if (!$report['imagick']) $report['warnings'][] = "缺少 Imagick 擴充套件，縮圖生成將回退使用 GD。";
        if (!$report['exif']) $report['warnings'][] = "缺少 EXIF 擴充套件，無法讀取拍攝日期資訊。";
        if (!$report['imagick'] && !$report['gd']) $report['warnings'][] = "找不到圖像處理引擎 (Imagick/GD)，將無法產生縮圖！";
        return $report;
    }

    public function getExifData($file) {
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
            } else { $shutter = $exif['ExposureTime'] . 's'; }
        }
        $focal = '未知';
        if (isset($exif['FocalLength'])) {
            $p = explode('/', $exif['FocalLength']);
            $val = (count($p) == 2 && $p[1] != 0) ? $p[0] / $p[1] : $exif['FocalLength'];
            $focal = round((float)$val, 1) . 'mm';
        }
        $gps = null;
        if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude']) && isset($exif['GPSLatitudeRef']) && isset($exif['GPSLongitudeRef'])) {
            $lat = $this->exifToFloat($exif['GPSLatitude'][0]) + ($this->exifToFloat($exif['GPSLatitude'][1]) / 60) + ($this->exifToFloat($exif['GPSLatitude'][2]) / 3600);
            $lng = $this->exifToFloat($exif['GPSLongitude'][0]) + ($this->exifToFloat($exif['GPSLongitude'][1]) / 60) + ($this->exifToFloat($exif['GPSLongitude'][2]) / 3600);
            if ($exif['GPSLatitudeRef'] == 'S') $lat = -$lat;
            if ($exif['GPSLongitudeRef'] == 'W') $lng = -$lng;
            $gps = array('lat' => round($lat, 6), 'lng' => round($lng, 6));
        }
        return array('make' => $make, 'model' => $model, 'aperture' => $aperture, 'shutter' => $shutter, 'iso' => $iso, 'focal' => $focal, 'date' => $date, 'gps' => $gps);
    }

    private function exifToFloat($value) {
        if (strpos($value, '/') !== false) {
            $parts = explode('/', $value);
            if (count($parts) == 2 && $parts[1] != 0) return (float)$parts[0] / (float)$parts[1];
        }
        return (float)$value;
    }

    public function generateThumbnail($src, $dest, $maxSize, $quality, $force = false) {
        if (file_exists($dest) && !$force) {
            if (filemtime($dest) >= filemtime($src) && filemtime($dest) >= $this->configMtime) return true;
        }
        $destDir = dirname($dest);
        if (!is_dir($destDir)) mkdir($destDir, 0777, true);
        if (extension_loaded('imagick')) {
            try {
                $image = new Imagick($src); $image->setImageCompressionQuality($quality);
                $w = $image->getImageWidth(); $h = $image->getImageHeight();
                if ($w <= $maxSize && $h <= $maxSize) { copy($src, $dest); $image->clear(); return true; }
                $image->resizeImage($maxSize, $maxSize, Imagick::FILTER_LANCZOS, 1, true);
                $image->writeImage($dest); $image->clear(); return true;
            } catch (Exception $e) { $this->log("Imagick Error: " . $e->getMessage()); }
        }
        if (extension_loaded('gd')) {
            $info = @getimagesize($src);
            if (!$info) return false;
            $w = $info[0]; $h = $info[1];
            if ($w <= $maxSize && $h <= $maxSize) { copy($src, $dest); return true; }
            $ratio = min($maxSize / $w, $maxSize / $h);
            $newW = round($w * $ratio); $newH = round($h * $ratio);
            $source = @imagecreatefromjpeg($src);
            if (!$source) return false;
            $thumb = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagejpeg($thumb, $dest, $quality);
            imagedestroy($source); imagedestroy($thumb); return true;
        }
        return false;
    }

    private function getMsg($key, $default) {
        if (function_exists('__')) return __($key);
        return $default;
    }

    private function getImageMaxSize($path) {
        if (extension_loaded('imagick')) {
            try { $img = new Imagick($path); $m = max($img->getImageWidth(), $img->getImageHeight()); $img->clear(); return $m; } catch(Exception $e) {}
        }
        $info = @getimagesize($path); return $info ? max($info[0], $info[1]) : 0;
    }

    private function getThumbFilename($filename, $id) {
        $info = pathinfo($filename); return $info['filename'] . '_' . $id . '.' . (isset($info['extension']) ? $info['extension'] : 'jpg');
    }

    private function getFinalCoverUrl($data, $albumName, $albumPath, $albumCover = '') {
        if (empty($albumCover)) {
            if (!empty($data['photos'])) {
                $photos = is_array($data['photos']) ? $data['photos'] : array();
                if (isset($photos[0])) $albumCover = $photos[0]['filename'];
            }
        }
        if (empty($albumCover)) return '';
        $coverFn = basename($albumCover);
        $previewId = $this->getConfigIdByMode('PreviewIcon');
        $tName = $this->getThumbFilename($coverFn, $previewId ? $previewId : $this->thumbConfigs[0]['id']);
        return file_exists($albumPath . '/Thumbnail/' . $tName) ? 'Collection/' . $albumName . '/Thumbnail/' . $tName : 'Collection/' . $albumName . '/' . $coverFn;
    }

    private function getConfigIdByMode($mode) {
        foreach ($this->thumbConfigs as $conf) { if ($conf['mode'] === $mode) return $conf['id']; }
        return null;
    }

    private function saveShortUrls() {
        ksort($this->shortUrlList);
        $content = "";
        foreach ($this->shortUrlList as $id => $path) { $content .= $id . "|" . $path . "\n"; }
        file_put_contents($this->baseDir . '/shorturl.txt', $content);
    }

    public function run($options = array()) {
        $targetAlbum = isset($options['targetAlbum']) ? $options['targetAlbum'] : null;
        $skipThumb = isset($options['skipThumb']) ? $options['skipThumb'] : false;
        $forceJson = isset($options['forceJson']) ? $options['forceJson'] : false;
        $forceThumb = isset($options['forceThumb']) ? $options['forceThumb'] : false;

        $this->finishedAlbums = array();
        $this->updateProgress(['status' => 'scanning', 'message' => $this->getMsg('initializing', '正在掃描目錄...'), 'album_current' => 0, 'album_total' => 0, 'photo_current' => 0, 'photo_total' => 0]);

        $allAlbumsList = array();
        $indexJsonFile = $this->jsonDir . '/index.json';
        if (file_exists($indexJsonFile)) {
            $existingIndex = json_decode(file_get_contents($indexJsonFile), true);
            if (isset($existingIndex['items'])) $allAlbumsList = $existingIndex['items'];
        }

        if (is_dir($this->collectionDir)) {
            $dirs = scandir($this->collectionDir);
            $albumDirs = array();
            foreach ($dirs as $d) {
                if ($d !== '.' && $d !== '..' && $d !== 'Thumbnail' && is_dir($this->collectionDir . '/' . $d)) {
                    if (!$targetAlbum || $d === $targetAlbum) $albumDirs[] = $d;
                }
            }
            $totalAlbums = count($albumDirs);
            foreach ($albumDirs as $index => $albumName) {
                $albumPath = $this->collectionDir . '/' . $albumName;
                $this->updateProgress(['status' => 'processing_album', 'message' => $this->getMsg('proc_album', '正在處理相簿：') . $albumName, 'album_name' => $albumName, 'album_current' => $index + 1, 'album_total' => $totalAlbums]);
                $this->processSingleAlbum($albumName, $albumPath, $allAlbumsList, $options);
                $this->finishedAlbums[] = $albumName;
            }
        }

        $this->updateProgress(['status' => 'saving', 'message' => $this->getMsg('saving_index', '正在儲存索引與連結...'), 'album_current' => isset($totalAlbums) ? $totalAlbums : 0, 'album_total' => isset($totalAlbums) ? $totalAlbums : 0]);
        file_put_contents($this->jsonDir . '/index.json', json_encode(array('items' => $allAlbumsList), JSON_UNESCAPED_UNICODE));
        $this->saveShortUrls();
        $this->updateProgress(['status' => 'done', 'message' => $this->getMsg('success_save', '重建完成')]);
        usleep(1500000);
        $this->cleanProgress();
        return true;
    }

    private function processSingleAlbum($albumName, $albumPath, &$allAlbumsList, $options) {
        $jsonFile = $this->jsonDir . '/' . $albumName . '.json';
        $commentAlbumFile = $albumPath . '/comment_album.txt';
        $picCommentFile = $albumPath . '/comment_pic.txt';
        $forceJson = isset($options['forceJson']) ? $options['forceJson'] : false;
        $skipThumb = isset($options['skipThumb']) ? $options['skipThumb'] : false;
        $forceThumb = isset($options['forceThumb']) ? $options['forceThumb'] : false;

        $allAlbumsList = array_filter($allAlbumsList, function($item) use ($albumName) { return $item['id'] !== $albumName; });
        $allAlbumsList = array_values($allAlbumsList);

        $jsonCacheValid = false;
        if (file_exists($jsonFile) && !$forceJson) {
            $jtime = filemtime($jsonFile); $stime = filemtime($albumPath);
            if (file_exists($commentAlbumFile)) $stime = max($stime, filemtime($commentAlbumFile));
            if (file_exists($picCommentFile)) $stime = max($stime, filemtime($picCommentFile));
            if ($jtime >= $stime && $jtime >= $this->configMtime) $jsonCacheValid = true;
        }

        if ($jsonCacheValid) {
            $data = json_decode(file_get_contents($jsonFile), true);
            $totalPhotos = count($data['photos']);
            foreach ($data['photos'] as $pIdx => $p) {
                $this->updateProgress(['status' => 'processing_photos', 'message' => "[$albumName] " . $this->getMsg('proc_verify', '正在校驗照片：') . ($pIdx + 1) . "/$totalPhotos", 'album_name' => $albumName, 'photo_current' => $pIdx + 1, 'photo_total' => $totalPhotos]);
                $photoPath = $albumPath . '/' . $p['filename'];
                $sid = $p['shortIdStart']; $this->shortUrlList[$sid] = $albumName . '/' . $p['filename'];
                $maxOrig = $this->getImageMaxSize($photoPath);
                foreach ($this->thumbConfigs as $idx => $conf) {
                    $tName = $this->getThumbFilename($p['filename'], $conf['id']);
                    $tRel = $albumName . '/Thumbnail/' . $tName; $tPath = $this->collectionDir . '/' . $tRel;
                    if ($maxOrig > $conf['width']) {
                        if (!$skipThumb) $this->generateThumbnail($photoPath, $tPath, $conf['width'], $conf['quality'], $forceThumb);
                        if (file_exists($tPath)) $this->shortUrlList[$sid + $idx + 1] = $tRel;
                    } elseif (file_exists($tPath)) { @unlink($tPath); }
                }
            }
            $allAlbumsList[] = array('name' => $data['name'], 'id' => $albumName, 'desc' => isset($data['desc']) ? $data['desc'] : '', 'cover' => $this->getFinalCoverUrl($data, $albumName, $albumPath), 'count' => count($data['photos']), 'date' => isset($data['date']) ? $data['date'] : '', 'link' => '#album='.urlencode($albumName));
            return;
        }

        $thumbDir = $albumPath . '/Thumbnail'; if (!is_dir($thumbDir)) mkdir($thumbDir, 0777, true);
        $photos = glob($albumPath . '/*.{jpg,JPG,jpeg,JPEG}', GLOB_BRACE);
        $totalPhotos = count($photos);
        $displayAlbumName = $albumName; $albumDesc = ''; $albumCover = ''; $albumDate = '';
        if (file_exists($commentAlbumFile)) {
            $parts = explode('|', file_get_contents($commentAlbumFile));
            if (isset($parts[0]) && !empty($parts[0])) $displayAlbumName = trim($parts[0]);
            if (isset($parts[1])) $albumDesc = trim($parts[1]);
            if (isset($parts[2]) && !empty($parts[2])) $albumCover = trim($parts[2]);
            if (isset($parts[3])) $albumDate = trim($parts[3]);
        }
        if (empty($albumDate)) $albumDate = date('Ymd', filemtime($albumPath));

        $albumPhotosJson = array();
        foreach ($photos as $pIdx => $photoPath) {
            $filename = basename($photoPath);
            $this->updateProgress(['status' => 'processing_photos', 'message' => "[$albumName] " . $this->getMsg('proc_photo', '正在處理照片：') . ($pIdx + 1) . "/$totalPhotos", 'album_name' => $albumName, 'photo_current' => $pIdx + 1, 'photo_total' => $totalPhotos]);
            $rel = $albumName . '/' . $filename;
            $sid = isset($this->existingShortUrls[$rel]) ? $this->existingShortUrls[$rel] : $this->nextAvailableId;
            if ($sid === $this->nextAvailableId) $this->nextAvailableId += (count($this->thumbConfigs) + 1);
            $this->shortUrlList[$sid] = $rel;
            $sizes = array(); $maxOrig = $this->getImageMaxSize($photoPath);
            foreach ($this->thumbConfigs as $idx => $conf) {
                $tName = $this->getThumbFilename($filename, $conf['id']);
                $tPath = $thumbDir . '/' . $tName;
                if ($maxOrig > $conf['width']) {
                    if (!$skipThumb) $this->generateThumbnail($photoPath, $tPath, $conf['width'], $conf['quality'], $forceThumb);
                    if (file_exists($tPath)) { $this->shortUrlList[$sid + $idx + 1] = $albumName . '/Thumbnail/' . $tName; $sizes[$conf['id']] = 'Collection/' . $albumName . '/Thumbnail/' . $tName; }
                } elseif (file_exists($tPath)) { @unlink($tPath); }
            }
            $albumPhotosJson[] = array('filename' => $filename, 'src' => 'Collection/'.$rel, 'sizes' => (object)$sizes, 'shortIdStart' => $sid, 'title' => $filename, 'desc' => '', 'exif' => $this->getExifData($photoPath));
        }
        $singleData = array('name' => $displayAlbumName, 'desc' => $albumDesc, 'photos' => $albumPhotosJson, 'date' => $albumDate);
        file_put_contents($jsonFile, json_encode($singleData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $allAlbumsList[] = array('name' => $displayAlbumName, 'id' => $albumName, 'desc' => $albumDesc, 'cover' => $this->getFinalCoverUrl($singleData, $albumName, $albumPath, $albumCover), 'count' => count($photos), 'date' => $albumDate, 'link' => '#album='.urlencode($albumName));
    }
}
