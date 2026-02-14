<?php
require_once 'auth.php';
requireAlbumLogin();

$id = isset($_GET['id']) ? $_GET['id'] : '';
$collectionDir = __DIR__ . '/../Collection';
$targetDir = $collectionDir . '/' . $id;

if (empty($id) || !is_dir($targetDir)) { die("Album not found."); }

// Load Album Meta
$displayName = $id; $currentCover = ''; $metaFile = $targetDir . '/comment_album.txt';
if (file_exists($metaFile)) {
    $parts = explode('|', file_get_contents($metaFile));
    if (isset($parts[0])) $displayName = $parts[0];
    if (isset($parts[2])) $currentCover = $parts[2];
}

// Load Photo Meta
$photoMeta = array(); $picCommentFile = $targetDir . '/comment_pic.txt';
if (file_exists($picCommentFile)) {
    $lines = file($picCommentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $p = explode('|', $line);
        if (count($p) >= 1) {
            $fn = trim($p[0]);
            $photoMeta[$fn] = array('title' => isset($p[1]) ? $p[1] : '', 'desc' => isset($p[2]) ? $p[2] : '');
        }
    }
}

// Get Photos
$photos = glob($targetDir . '/*.jpg'); $totalPhotos = count($photos); $baseUrl = '../Collection/' . $id;
$perPage = 40; $totalPages = ceil($totalPhotos / $perPage);
$page = isset($_GET['page']) ? max(1, min($totalPages, (int)$_GET['page'])) : 1;
if ($totalPages == 0) $page = 1;
$offset = ($page - 1) * $perPage;
$pagedPhotos = array_slice($photos, $offset, $perPage);

$photoList = array();
foreach ($pagedPhotos as $path) {
    $fn = basename($path);
    $meta = isset($photoMeta[$fn]) ? $photoMeta[$fn] : array('title' => $fn, 'desc' => '');
    $info = pathinfo($fn);
    $xsName = $info['filename'] . '_XS.' . $info['extension'];
    $sName = $info['filename'] . '_S.' . $info['extension'];
    if (file_exists($targetDir . '/Thumbnail/' . $xsName)) { $thumbUrl = $baseUrl . '/Thumbnail/' . $xsName; }
    elseif (file_exists($targetDir . '/Thumbnail/' . $sName)) { $thumbUrl = $baseUrl . '/Thumbnail/' . $sName; }
    else { $thumbUrl = $baseUrl . '/' . $fn; }
    $photoList[] = array('filename' => $fn, 'title' => $meta['title'], 'desc' => $meta['desc'], 'url' => $thumbUrl);
}
?>
<!DOCTYPE html>
<html lang="<?php echo getWebLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('manage_photos'); ?> - <?php echo htmlspecialchars($displayName); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="assets/js/sweetalert2.all.min.js"></script>
    <script src="<?php echo getAdminLangJs(); ?>"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .photo-card img { width: 100%; height: 150px; object-fit: contain; background-color: #f0f0f0; }
        .photo-card { transition: all 0.2s; }
        .photo-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
        .is-cover { border: 3px solid #198754; }
        @media (min-width: 1400px) { .col-custom-8 { flex: 0 0 auto; width: 12.5%; } }
    </style>
</head>
<body>
<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>
    <div class="main-content flex-grow-1 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div><a href="albums.php" class="text-decoration-none">&larr; <?php echo __('back_to_list'); ?></a><h2 class="mt-2"><?php echo htmlspecialchars($displayName); ?> <small class="text-muted fs-6">(<?php echo sprintf(__('total_count'), $totalPhotos); ?>)</small></h2></div>
            <div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal"><i class="bi bi-cloud-upload"></i> <?php echo __('upload_photos'); ?></button>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-gear-wide-connected"></i> <?php echo __('rebuild_album'); ?></button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="#" onclick="rebuildThisAlbum('quick')"><?php echo __('opt_quick_refresh'); ?></a></li>
                        <li><a class="dropdown-item" href="#" onclick="rebuildThisAlbum('force_json')"><?php echo __('opt_force_json'); ?></a></li>
                        <li><a class="dropdown-item" href="#" onclick="rebuildThisAlbum('force_thumb')"><?php echo __('opt_force_thumb'); ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="rebuildThisAlbum('force_all')"><?php echo __('f_rebuild_all'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row g-2 mb-4">
            <?php foreach ($photoList as $photo): $isCover = ($currentCover === 'Collection/' . $id . '/' . $photo['filename']) || ($currentCover === $photo['filename']); ?>
            <div class="col-6 col-md-4 col-lg-3 col-custom-8">
                <div class="card shadow-sm photo-card <?php echo $isCover ? 'is-cover' : ''; ?>">
                    <img src="<?php echo $photo['url']; ?>" class="card-img-top">
                    <div class="card-body p-2"><h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($photo['filename']); ?>" style="font-size: 0.85rem;"><?php echo htmlspecialchars($photo['filename']); ?></h6><p class="card-text small text-muted text-truncate mb-2" style="font-size: 0.75rem;"><?php echo $photo['title'] ? htmlspecialchars($photo['title']) : '(No Title)'; ?></p>
                        <div class="btn-group w-100">
                            <button class="btn btn-sm btn-outline-primary" onclick="editPhoto('<?php echo $photo['filename']; ?>', '<?php echo addslashes($photo['title']); ?>', '<?php echo addslashes($photo['desc']); ?>')"><i class="bi bi-pencil"></i></button>
                            <form action="photo_actions.php" method="post" class="d-inline" onsubmit="return confirm('<?php echo __('set_cover'); ?>?');"><input type="hidden" name="action" value="set_cover"><input type="hidden" name="album_id" value="<?php echo htmlspecialchars($id); ?>"><input type="hidden" name="filename" value="<?php echo htmlspecialchars($photo['filename']); ?>"><input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>"><button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-star<?php echo $isCover ? '-fill' : ''; ?>"></i></button></form>
                            <button class="btn btn-sm btn-outline-danger" onclick="deletePhoto('<?php echo $photo['filename']; ?>')"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
function rebuildThisAlbum(mode) {
    let pollingInterval = null;
    const pid = 'photo_' + Date.now();
    Swal.fire({
        title: adminLang.rebuilding_title,
        html: `
            <div class="task-list text-start" style="background: #f8f9fa; border-radius: 8px; padding: 15px; border: 1px solid #dee2e6;">
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
                    const photoVal = document.getElementById('panel-photo-val');
                    const textEl = document.getElementById('swal-progress-text');
                    if (data.status !== 'waiting') {
                        if (photoVal) photoVal.innerText = (data.photo_current) ? `${data.photo_current} / ${data.photo_total}` : '-- / --';
                        if (textEl) textEl.innerText = data.message || adminLang.processing_msg;
                    }
                });
            }, 1000);
        }
    });

    const formData = new FormData();
    formData.append('action', 'rebuild_album');
    formData.append('album_id', '<?php echo addslashes($id); ?>');
    formData.append('progress_id', pid);
    formData.append('csrf_token', '<?php echo getCSRFToken(); ?>');
    if (mode === 'force_json') formData.append('forceJson', 'on');
    if (mode === 'force_thumb') formData.append('forceThumb', 'on');
    if (mode === 'force_all') { formData.append('forceJson', 'on'); formData.append('forceThumb', 'on'); }

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
