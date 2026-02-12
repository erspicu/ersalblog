<?php
require_once 'auth.php';
requireAlbumLogin();

// 簡單統計
$collectionDir = __DIR__ . '/../Collection';
$albumCount = 0;
$photoCount = 0;

if (is_dir($collectionDir)) {
    $dirs = scandir($collectionDir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        if (is_dir($collectionDir . '/' . $dir)) {
            $albumCount++;
            $photos = glob($collectionDir . '/' . $dir . '/*.jpg');
            $photoCount += count($photos);
        }
    }
}

// 磁碟空間資訊
$diskTotal = disk_total_space("/");
$diskFree = disk_free_space("/");
$diskUsed = $diskTotal - $diskFree;
$diskUsagePercent = round(($diskUsed / $diskTotal) * 100, 2);

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// 計算 Collection 目錄大小
$collectionSizeStr = "計算中...";
$success = false;

if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' && function_exists('popen')) {
    $io = popen("du -sh " . escapeshellarg($collectionDir) . " 2>&1", "r");
    if ($io) {
        $res = stream_get_contents($io);
        pclose($io);
        if ($res) {
            $res = trim($res);
            $parts = explode("\t", $res);
            if (!empty($parts[0])) {
                $collectionSizeStr = $parts[0];
                $success = true;
            }
        }
    }
}

if (!$success) {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($collectionDir, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
        $size += $file->getSize();
    }
    $collectionSizeStr = formatBytes($size);
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('dashboard'); ?> - 相簿後台</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>

    <div class="main-content flex-grow-1 bg-light">
        <?php if (file_exists(__DIR__ . '/../install.php')): ?>
            <div class="alert alert-danger shadow-sm mb-4 d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <div>
                    <strong><?php echo __('security_warning'); ?></strong> <?php echo __('install_exists'); ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><?php echo __('welcome'); ?>，<?php echo htmlspecialchars($_SESSION['album_admin_user']); ?>！</h2>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card text-white bg-primary h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo __('albums'); ?></h5>
                        <h2 class="display-4"><?php echo $albumCount; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-white bg-success h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo __('photos'); ?></h5>
                        <h2 class="display-4"><?php echo $photoCount; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><?php echo __('disk_usage'); ?></h5>
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <p class="mb-1 text-muted"><?php echo __('collection_size'); ?> (Collection)</p>
                                <h4 class="mb-0 text-primary"><?php echo $collectionSizeStr; ?></h4>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small text-muted"><?php echo __('sys_disk_rate'); ?>: <?php echo formatBytes($diskUsed); ?> / <?php echo formatBytes($diskTotal); ?></span>
                                    <span class="small fw-bold"><?php echo $diskUsagePercent; ?>%</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar <?php echo $diskUsagePercent > 90 ? 'bg-danger' : ($diskUsagePercent > 70 ? 'bg-warning' : 'bg-info'); ?>" role="progressbar" style="width: <?php echo $diskUsagePercent; ?>%"></div>
                                </div>
                                <p class="small text-muted mt-1 mb-0"><?php echo __('free_space'); ?>: <?php echo formatBytes($diskFree); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <h4><?php echo __('quick_actions'); ?></h4>
            <div class="d-flex gap-2">
                <a href="albums.php" class="btn btn-outline-primary"><?php echo __('manage_albums'); ?></a>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
