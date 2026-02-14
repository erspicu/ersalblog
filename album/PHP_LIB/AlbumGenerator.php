<?php
/**
 * AlbumGenerator - 核心相簿處理邏輯
 * 支援 CLI 與 Web 後台共用
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

    public function __construct($baseDir) {
        $this->baseDir = rtrim($baseDir, '/');
        $this->collectionDir = $this->baseDir . '/Collection';
        $this->jsonDir = $this->baseDir . '/api/json';
        
        if (!file_exists($this->jsonDir)) mkdir($this->jsonDir, 0777, true);

        // 讀取縮圖配置
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
            $lat = $this->exifToFloat($exif['GPSLatitude'][0]) + ($this->exifToFloat($exif['GPSLatitude'][1]) / 60) + ($this->exifToFloat($exif['GPSLatitude'][2]) / 3600);
            $lng = $this->exifToFloat($exif['GPSLongitude'][0]) + ($this->exifToFloat($this->exifToFloat($exif['GPSLongitude'][1]) / 60)) + ($this->exifToFloat($this->exifToFloat($exif['GPSLongitude'][2]) / 3600));
            // 修正巢狀 exifToFloat
            $lat = $this->exifToFloat($exif['GPSLatitude'][0]) + ($this->exifToFloat($exif['GPSLatitude'][1]) / 60) + ($this->exifToFloat($exif['GPSLatitude'][2]) / 3600);
            $lng = $this->exifToFloat($exif['GPSLongitude'][0]) + ($this->exifToFloat($exif['GPSLongitude'][1]) / 60) + ($this->exifToFloat($exif['GPSLongitude'][2]) / 3600);
            
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

    private function exifToFloat($value) {
        if (strpos($value, '/') !== false) {
            $parts = explode('/', $value);
            if (count($parts) == 2 && $parts[1] != 0) return (float)$parts[0] / (float)$parts[1];
        }
        return (float)$value;
    }

    private function log($msg) {
        $logFile = $this->baseDir . '/api/json/generator.log';
        $time = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND);
    }

    public function checkEnvironment() {
        $report = array(
            'imagick' => extension_loaded('imagick'),
            'exif' => function_exists('exif_read_data'),
            'gd' => extension_loaded('gd'),
            'warnings' => array()
        );

        if (!$report['imagick']) {
            $report['warnings'][] = "缺少 Imagick 擴充套件，縮圖生成將受限或失敗。";
        }
        if (!$report['exif']) {
            $report['warnings'][] = "缺少 EXIF 擴充套件，照片拍攝資訊將無法讀取。";
        }
        if (!$report['imagick'] && !$report['gd']) {
            $report['warnings'][] = "完全找不到圖像處理引擎 (Imagick/GD)，將無法產生任何縮圖！";
        }

        return $report;
    }

    public function generateThumbnail($src, $dest, $maxSize, $quality, $force = false) {
        if (file_exists($dest) && !$force) {
            if (filemtime($dest) >= filemtime($src) && filemtime($dest) >= $this->configMtime) return true;
        }
        
        $this->log("Generating thumbnail: " . basename($dest) . " (Size: $maxSize)");
        $destDir = dirname($dest);
        if (!is_dir($destDir)) mkdir($destDir, 0777, true);

        // 優先使用 Imagick
        if (extension_loaded('imagick')) {
            try {
                $image = new Imagick($src); 
                $image->setImageCompressionQuality($quality);
                $w = $image->getImageWidth(); $h = $image->getImageHeight();
                if ($w <= $maxSize && $h <= $maxSize) { 
                    copy($src, $dest); $image->clear(); return true; 
                }
                $image->resizeImage($maxSize, $maxSize, Imagick::FILTER_LANCZOS, 1, true);
                $image->writeImage($dest); $image->clear();
                return true;
            } catch (Exception $e) { $this->log("Imagick Error: " . $e->getMessage()); }
        }
        
        // 回退使用 GD
        if (extension_loaded('gd')) {
            $this->log("Falling back to GD library.");
            $info = getimagesize($src);
            if (!$info) return false;
            $w = $info[0]; $h = $info[1];
            if ($w <= $maxSize && $h <= $maxSize) { copy($src, $dest); return true; }
            
            $ratio = min($maxSize / $w, $maxSize / $h);
            $newW = round($w * $ratio); $newH = round($h * $ratio);
            
            $source = imagecreatefromjpeg($src);
            $thumb = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagejpeg($thumb, $dest, $quality);
            imagedestroy($source); imagedestroy($thumb);
            return true;
        }

        $this->log("Error: No image engine available.");
        return false;
    }

    public function run($options = array()) {
        $targetAlbum = isset($options['targetAlbum']) ? $options['targetAlbum'] : null;
        $skipThumb = isset($options['skipThumb']) ? $options['skipThumb'] : false;
        $forceJson = isset($options['forceJson']) ? $options['forceJson'] : false;
        $forceThumb = isset($options['forceThumb']) ? $options['forceThumb'] : false;

        $allAlbumsList = array();
        $indexJsonFile = $this->jsonDir . '/index.json';
        if (file_exists($indexJsonFile)) {
            $existingIndex = json_decode(file_get_contents($indexJsonFile), true);
            if (isset($existingIndex['items'])) $allAlbumsList = $existingIndex['items'];
        }

        if (is_dir($this->collectionDir)) {
            $dirs = scandir($this->collectionDir);
            foreach ($dirs as $albumName) {
                if ($albumName === '.' || $albumName === '..' || $albumName === 'Thumbnail') continue;
                if ($targetAlbum && $albumName !== $targetAlbum) continue;
                
                $albumPath = $this->collectionDir . '/' . $albumName;
                if (!is_dir($albumPath)) continue;

                $this->processSingleAlbum($albumName, $albumPath, $allAlbumsList, $options);
            }
        }

        file_put_contents($this->jsonDir . '/index.json', json_encode(array('items' => $allAlbumsList), JSON_UNESCAPED_UNICODE));
        $this->saveShortUrls();
        return true;
    }

    private function processSingleAlbum($albumName, $albumPath, &$allAlbumsList, $options) {
        $jsonFile = $this->jsonDir . '/' . $albumName . '.json';
        $commentAlbumFile = $albumPath . '/comment_album.txt';
        $picCommentFile = $albumPath . '/comment_pic.txt';
        $forceJson = isset($options['forceJson']) ? $options['forceJson'] : false;
        $skipThumb = isset($options['skipThumb']) ? $options['skipThumb'] : false;
        $forceThumb = isset($options['forceThumb']) ? $options['forceThumb'] : false;

        $allAlbumsList = array_filter($allAlbumsList, function($item) use ($albumName) {
            return $item['id'] !== $albumName;
        });
        $allAlbumsList = array_values($allAlbumsList);

        $jsonCacheValid = false;
        if (file_exists($jsonFile) && !$forceJson) {
            $jtime = filemtime($jsonFile); $stime = filemtime($albumPath);
            if (file_exists($commentAlbumFile)) $stime = max($stime, filemtime($commentAlbumFile));
            if (file_exists($picCommentFile)) $stime = max($stime, filemtime($picCommentFile));
            if ($jtime >= $stime && $jtime >= $this->configMtime) $jsonCacheValid = true;
        }

                if ($jsonCacheValid) {

                    $this->log("Album: $albumName (JSON Cache Valid)");

                    $data = json_decode(file_get_contents($jsonFile), true);

                    foreach ($data['photos'] as $p) {

                        $photoPath = $albumPath . '/' . $p['filename'];

                        $sid = $p['shortIdStart'];

                        $this->shortUrlList[$sid] = $albumName . '/' . $p['filename'];

                        

                        $maxOrig = $this->getImageMaxSize($photoPath);

                        foreach ($this->thumbConfigs as $idx => $conf) {

                            $tName = $this->getThumbFilename($p['filename'], $conf['id']);

                            $tRel = $albumName . '/Thumbnail/' . $tName;

                            $tPath = $this->collectionDir . '/' . $tRel;

                            if ($maxOrig > $conf['width']) {

                                $this->shortUrlList[$sid + $idx + 1] = $tRel;

                                if (!$skipThumb) $this->generateThumbnail($photoPath, $tPath, $conf['width'], $conf['quality'], $forceThumb);

                            } elseif (file_exists($tPath)) { @unlink($tPath); }

                        }

                    }
            $allAlbumsList[] = array(
                'name' => $data['name'], 'id' => $albumName, 'desc' => isset($data['desc']) ? $data['desc'] : '', 
                'cover' => $this->getFinalCoverUrl($data, $albumName, $albumPath), 
                'count' => count($data['photos']), 'date' => isset($data['date']) ? $data['date'] : '', 
                'link' => '#album='.urlencode($albumName)
            );
            return;
        }

        $thumbDir = $albumPath . '/Thumbnail'; if (!is_dir($thumbDir)) mkdir($thumbDir, 0777, true);
        $photos = glob($albumPath . '/*.{jpg,JPG,jpeg,JPEG}', GLOB_BRACE);
        
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
        foreach ($photos as $photoPath) {
            $filename = basename($photoPath);
            $rel = $albumName . '/' . $filename;
            $sid = isset($this->existingShortUrls[$rel]) ? $this->existingShortUrls[$rel] : $this->nextAvailableId;
            if ($sid === $this->nextAvailableId) $this->nextAvailableId += (count($this->thumbConfigs) + 1);
            
            $this->shortUrlList[$sid] = $rel;
            $sizes = array();
            $maxOrig = $this->getImageMaxSize($photoPath);

            foreach ($this->thumbConfigs as $idx => $conf) {
                $tName = $this->getThumbFilename($filename, $conf['id']);
                $tPath = $thumbDir . '/' . $tName;
                if ($maxOrig > $conf['width']) {
                    if (!$skipThumb) $this->generateThumbnail($photoPath, $tPath, $conf['width'], $conf['quality'], $forceThumb);
                    $this->shortUrlList[$sid + $idx + 1] = $albumName . '/Thumbnail/' . $tName;
                    $sizes[$conf['id']] = 'Collection/' . $albumName . '/Thumbnail/' . $tName;
                } elseif (file_exists($tPath)) { @unlink($tPath); }
            }
            $albumPhotosJson[] = array(
                'filename' => $filename, 'src' => 'Collection/'.$rel, 'sizes' => (object)$sizes,
                'shortIdStart' => $sid, 'title' => $filename, 'desc' => '', 'exif' => $this->getExifData($photoPath)
            );
        }

        $singleData = array('name' => $displayAlbumName, 'desc' => $albumDesc, 'photos' => $albumPhotosJson, 'date' => $albumDate);
        file_put_contents($jsonFile, json_encode($singleData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $allAlbumsList[] = array(
            'name' => $displayAlbumName, 'id' => $albumName, 'desc' => $albumDesc, 
            'cover' => $this->getFinalCoverUrl($singleData, $albumName, $albumPath, $albumCover), 
            'count' => count($photos), 'date' => $albumDate, 'link' => '#album='.urlencode($albumName)
        );
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
}
