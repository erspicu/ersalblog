<?php
require_once 'auth.php';
requireAlbumLogin();

$id = isset($_GET['id']) ? $_GET['id'] : '';
$collectionDir = __DIR__ . '/../Collection';
$targetDir = $collectionDir . '/' . $id;

if (empty($id) || !is_dir($targetDir)) {
    die("Album not found.");
}

$displayName = $id;
$desc = '';
$cover = '';
$date = '';

$metaFile = $targetDir . '/comment_album.txt';
if (file_exists($metaFile)) {
    $content = file_get_contents($metaFile);
    $parts = explode('|', $content);
    if (isset($parts[0])) $displayName = $parts[0];
    if (isset($parts[1])) $desc = $parts[1];
    if (isset($parts[2])) $cover = $parts[2];
    if (isset($parts[3])) $date = $parts[3];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯相簿 - <?php echo htmlspecialchars($id); ?></title>
    <link href="../../admin/assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>

    <div class="main-content flex-grow-1 bg-light">
        <div class="mb-4">
            <a href="albums.php" class="text-decoration-none">&larr; 返回列表</a>
            <h2 class="mt-2">編輯相簿：<?php echo htmlspecialchars($displayName); ?></h2>
        </div>

        <div class="card shadow-sm col-md-8">
            <div class="card-body">
                <form action="album_actions.php" method="post">
                    <input type="hidden" name="action" value="update_album_info">
                    <input type="hidden" name="album_id" value="<?php echo htmlspecialchars($id); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label">目錄名稱 (ID)</label>
                        <input type="text" name="new_dir_name" class="form-control" value="<?php echo htmlspecialchars($id); ?>" required pattern="[A-Za-z0-9_-]+" title="僅限英數字">
                        <div class="form-text text-danger">修改此欄位將會重新命名資料夾，請謹慎操作。</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">顯示標題</label>
                        <input type="text" name="display_name" class="form-control" value="<?php echo htmlspecialchars($displayName); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($desc); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">建立日期 (格式: YYYYMMDD)</label>
                        <input type="text" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>" placeholder="例如: 20260208">
                        <div class="form-text">若留空，列表將自動顯示目錄的檔案系統時間。</div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">儲存變更</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../../admin/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
