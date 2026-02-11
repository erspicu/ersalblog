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
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>相簿後台 - 儀表板</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <?php require 'sidebar_inc.php'; ?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 bg-light">
        <?php if (file_exists(__DIR__ . '/../install.php')): ?>
            <div class="alert alert-danger shadow-sm mb-4 d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <div>
                    <strong>高風險安全警告：</strong> 偵測到 <code>album/install.php</code> 仍然存在於伺服器上。
                    為了您的資安，請立即手動刪除此檔案，或將其更名。
                </div>
            </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">歡迎回來，<?php echo htmlspecialchars($_SESSION['album_admin_user']); ?>！</h2>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card text-white bg-primary h-100">
                    <div class="card-body">
                        <h5 class="card-title">相簿總數</h5>
                        <h2 class="display-4"><?php echo $albumCount; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-white bg-success h-100">
                    <div class="card-body">
                        <h5 class="card-title">照片總數</h5>
                        <h2 class="display-4"><?php echo $photoCount; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <h4>快速操作</h4>
            <div class="d-flex gap-2">
                <a href="albums.php" class="btn btn-outline-primary">管理相簿</a>
                <!-- <a href="#" class="btn btn-outline-success">新增相簿 (To be implemented)</a> -->
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
