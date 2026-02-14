<?php
require_once 'auth.php';
requireAlbumLogin();

$collectionDir = __DIR__ . '/../Collection';
$baseUrl = '../Collection'; 

$albums = array();
if (is_dir($collectionDir)) {
    $dirs = scandir($collectionDir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        $albumPath = $collectionDir . '/' . $dir;
        if (is_dir($albumPath)) {
            $date = date('Ymd', filemtime($albumPath));
            $displayName = $dir; $desc = ''; $cover = '';
            $commentFile = $albumPath . '/comment_album.txt';
            if (file_exists($commentFile)) {
                $parts = explode('|', file_get_contents($commentFile));
                if (isset($parts[0]) && !empty($parts[0])) $displayName = $parts[0];
                if (isset($parts[1])) $desc = $parts[1];
                if (isset($parts[2]) && !empty($parts[2])) $cover = $parts[2];
                if (isset($parts[3]) && !empty(trim($parts[3]))) $date = trim($parts[3]);
            }
            $coverUrl = '';
            if (empty($cover)) {
                $photos = glob($albumPath . '/*.jpg');
                if (!empty($photos)) $cover = basename($photos[0]);
            }
            if (!empty($cover)) {
                $coverFn = basename($cover);
                $info = pathinfo($coverFn);
                $xsName = $info['filename'] . '_XS.' . $info['extension'];
                $sName = $info['filename'] . '_S.' . $info['extension'];
                if (file_exists($albumPath . '/Thumbnail/' . $xsName)) {
                    $coverUrl = $baseUrl . '/' . $dir . '/Thumbnail/' . $xsName;
                } elseif (file_exists($albumPath . '/Thumbnail/' . $sName)) {
                    $coverUrl = $baseUrl . '/' . $dir . '/Thumbnail/' . $sName;
                } else {
                    $coverUrl = $baseUrl . '/' . $dir . '/' . $coverFn;
                }
            }
            $albums[] = array('id' => $dir, 'name' => $displayName, 'desc' => $desc, 'date' => $date, 'coverUrl' => $coverUrl);
        }
    }
}
usort($albums, function($a, $b) { return strcmp($b['date'], $a['date']); });
$perPage = 40; $totalAlbums = count($albums); $totalPages = ceil($totalAlbums / $perPage);
$page = isset($_GET['page']) ? max(1, min($totalPages, (int)$_GET['page'])) : 1;
if ($totalPages == 0) $page = 1;
$offset = ($page - 1) * $perPage;
$pagedAlbums = array_slice($albums, $offset, $perPage);
?>
<!DOCTYPE html>
<html lang="<?php echo getWebLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('manage_albums'); ?> - 相簿列表</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="assets/js/sweetalert2.all.min.js"></script>
    <script src="<?php echo getAdminLangJs(); ?>"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .album-card img { width: 100%; height: 150px; object-fit: contain; background-color: #f0f0f0; }
        .album-card { transition: all 0.2s; position: relative; }
        .album-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
        .album-badge { position: absolute; top: 5px; right: 5px; font-size: 0.7rem; padding: 2px 6px; background: rgba(0,0,0,0.6); color: white; border-radius: 4px; }
        @media (min-width: 1400px) { .col-custom-8 { flex: 0 0 auto; width: 12.5%; } }
    </style>
</head>
<body>
<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>
    <div class="main-content flex-grow-1 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?php echo __('manage_albums'); ?> <small class="text-muted fs-6">(<?php echo sprintf(__('total_count'), $totalAlbums); ?>)</small></h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAlbumModal"><i class="bi bi-plus-lg"></i> <?php echo __('create_album'); ?></button>
        </div>
        <div class="row g-2 mb-4">
            <?php foreach ($pagedAlbums as $album): ?>
            <div class="col-6 col-md-4 col-lg-3 col-custom-8">
                <div class="card shadow-sm album-card h-100">
                    <img src="<?php echo $album['coverUrl'] ? $album['coverUrl'] : 'https://via.placeholder.com/320x200?text=No+Photo'; ?>" class="card-img-top">
                    <span class="album-badge"><?php echo $album['date']; ?></span>
                    <div class="card-body p-2 d-flex flex-column">
                        <h6 class="card-title text-truncate mb-1" title="<?php echo htmlspecialchars($album['name']); ?>" style="font-size: 0.85rem;"><?php echo htmlspecialchars($album['name']); ?></h6>
                        <p class="card-text small text-muted text-truncate mb-2" style="font-size: 0.75rem; flex-grow: 1;"><?php echo $album['desc'] ? htmlspecialchars($album['desc']) : ''; ?></p>
                        <div class="btn-group w-100 mt-auto">
                            <a href="album_photos.php?id=<?php echo urlencode($album['id']); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('manage_photos'); ?>"><i class="bi bi-images"></i></a>
                            <a href="album_edit.php?id=<?php echo urlencode($album['id']); ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('edit_info'); ?>"><i class="bi bi-pencil"></i></a>
                            <button class="btn btn-sm btn-outline-warning" onclick="rebuildAlbum('<?php echo htmlspecialchars($album['id']); ?>')" title="<?php echo __('rebuild_album'); ?>"><i class="bi bi-arrow-clockwise"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('<?php echo htmlspecialchars($album['id']); ?>')"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="createAlbumModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="album_actions.php" method="post" class="modal-content">
            <input type="hidden" name="action" value="create_album"><input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
            <div class="modal-header"><h5 class="modal-title"><?php echo __('create_album'); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label"><?php echo __('dir_name'); ?></label><input type="text" name="dir_name" class="form-control" required title="<?php echo __('dir_name_hint'); ?>"><small class="text-muted"><?php echo __('dir_name_hint'); ?></small></div>
                <div class="mb-3"><label class="form-label"><?php echo __('display_title'); ?></label><input type="text" name="display_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label"><?php echo __('album_desc'); ?></label><textarea name="description" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button><button type="submit" class="btn btn-primary"><?php echo __('create_btn'); ?></button></div>
        </form>
    </div>
</div>

<form id="deleteForm" action="album_actions.php" method="post" style="display:none;"><input type="hidden" name="action" value="delete_album"><input type="hidden" name="album_id" id="deleteAlbumId"><input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>"></form>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id) {
    if(confirm('<?php echo sprintf(__('confirm_delete_album'), "' + id + '"); ?>')) {
        document.getElementById('deleteAlbumId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function rebuildAlbum(id) {
    let pollingInterval = null;
    const pid = 'album_' + Date.now();
    Swal.fire({
        title: adminLang.rebuilding_title,
        html: `
            <div class="task-list text-start" style="background: #f8f9fa; border-radius: 8px; padding: 15px; border: 1px solid #dee2e6;">
                <div style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: bold; color: #495057;">${adminLang.task_panel_album}</span>
                    <span id="panel-album-val" style="color: #0d6efd; font-family: monospace;">-- / --</span>
                </div>
                <div style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: bold; color: #495057;">${adminLang.task_panel_photo}</span>
                    <span id="panel-photo-val" style="color: #0d6efd; font-family: monospace;">-- / --</span>
                </div>
                <div class="mt-2 small">
                    <span style="font-weight:bold; color:#495057; display:block; mb-1">${adminLang.task_panel_current}</span>
                    <div id="swal-progress-text" class="text-muted border-start ps-2" style="min-height: 1.2em;">${adminLang.initializing}</div>
                </div>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
            pollingInterval = setInterval(() => {
                const fd = new FormData();
                fd.append('action', 'get_rebuild_progress');
                fd.append('progress_id', pid);
                fd.append('csrf_token', '<?php echo getCSRFToken(); ?>');
                fd.append('t', Date.now());
                fetch('album_actions.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    const albumVal = document.getElementById('panel-album-val');
                    const photoVal = document.getElementById('panel-photo-val');
                    const textEl = document.getElementById('swal-progress-text');
                    if (data.status !== 'waiting') {
                        if (albumVal) albumVal.innerText = (data.album_current) ? `${data.album_current} / ${data.album_total || 1}` : '-- / --';
                        if (photoVal) photoVal.innerText = (data.photo_current) ? `${data.photo_current} / ${data.photo_total}` : '-- / --';
                        if (textEl) textEl.innerText = data.message || adminLang.processing_msg;
                    }
                    if (data.status === 'done') clearInterval(pollingInterval);
                }).catch(e => {});
            }, 1000);
        }
    });

    const formData = new FormData();
    formData.append('action', 'rebuild_album');
    formData.append('album_id', id);
    formData.append('progress_id', pid);
    formData.append('csrf_token', '<?php echo getCSRFToken(); ?>');

    fetch('album_actions.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        clearInterval(pollingInterval);
        Swal.fire(data.status === 'success' ? adminLang.success : adminLang.error, data.message, data.status).then(() => location.reload());
    })
    .catch(error => {
        clearInterval(pollingInterval);
        Swal.fire(adminLang.error, adminLang.error_network, 'error');
    });
}
</script>
</body>
</html>
