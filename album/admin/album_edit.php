<?php
require_once 'auth.php';
requireAlbumLogin();

$id = isset($_GET['id']) ? $_GET['id'] : '';
$collectionDir = __DIR__ . '/../Collection';
$targetDir = $collectionDir . '/' . $id;

if (empty($id) || !is_dir($targetDir)) {
    die("Album not found.");
}

// Load current data
$displayName = $id;
$desc = '';
$date = date('Ymd', filemtime($targetDir));

$commentFile = $targetDir . '/comment_album.txt';
if (file_exists($commentFile)) {
    $content = file_get_contents($commentFile);
    $parts = explode('|', $content);
    if (isset($parts[0])) $displayName = $parts[0];
    if (isset($parts[1])) $desc = $parts[1];
    if (isset($parts[3]) && !empty(trim($parts[3]))) $date = trim($parts[3]);
}
?>
<!DOCTYPE html>
<html lang="<?php echo getWebLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('edit_album'); ?> - <?php echo htmlspecialchars($id); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>

    <div class="main-content flex-grow-1 bg-light">
        <div class="mb-4">
            <a href="albums.php" class="text-decoration-none">&larr; <?php echo __('back_to_list'); ?></a>
            <h2 class="mt-2"><?php echo __('edit_album'); ?>: <?php echo htmlspecialchars($id); ?></h2>
        </div>

        <div class="card shadow-sm col-md-8">
            <div class="card-body">
                <form action="album_actions.php" method="post">
                    <input type="hidden" name="action" value="update_album_info">
                    <input type="hidden" name="album_id" value="<?php echo htmlspecialchars($id); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('display_title'); ?></label>
                        <input type="text" name="display_name" class="form-control" value="<?php echo htmlspecialchars($displayName); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('album_desc'); ?></label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($desc); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('album_date'); ?></label>
                        <input type="text" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>" pattern="[0-9]{8}" title="YYYYMMDD 格式">
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="history.back()"><?php echo __('cancel'); ?></button>
                        <button type="submit" class="btn btn-primary px-4"><?php echo __('update_btn'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
