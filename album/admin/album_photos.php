<?php
require_once 'auth.php';
requireAlbumLogin();

$id = isset($_GET['id']) ? $_GET['id'] : '';
$collectionDir = __DIR__ . '/../Collection';
$targetDir = $collectionDir . '/' . $id;

if (empty($id) || !is_dir($targetDir)) {
    die("Album not found.");
}

// Load Album Meta
$displayName = $id;
$currentCover = '';
$metaFile = $targetDir . '/comment_album.txt';
if (file_exists($metaFile)) {
    $parts = explode('|', file_get_contents($metaFile));
    if (isset($parts[0])) $displayName = $parts[0];
    if (isset($parts[2])) $currentCover = $parts[2];
}

// Load Photo Meta
$photoMeta = array();
$picCommentFile = $targetDir . '/comment_pic.txt';
if (file_exists($picCommentFile)) {
    $lines = file($picCommentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $p = explode('|', $line);
        if (count($p) >= 1) {
            $fn = trim($p[0]);
            $photoMeta[$fn] = array(
                'title' => isset($p[1]) ? $p[1] : '',
                'desc' => isset($p[2]) ? $p[2] : ''
            );
        }
    }
}

// Get Photos
$photos = glob($targetDir . '/*.jpg');
$totalPhotos = count($photos);
$baseUrl = '../Collection/' . $id;

// 分頁邏輯
$perPage = 40;
$totalPages = ceil($totalPhotos / $perPage);
$page = isset($_GET['page']) ? max(1, min($totalPages, (int)$_GET['page'])) : 1;
if ($totalPages == 0) $page = 1;
$offset = ($page - 1) * $perPage;
$pagedPhotos = array_slice($photos, $offset, $perPage);

$photoList = array();
foreach ($pagedPhotos as $path) {
    $fn = basename($path);
    $meta = isset($photoMeta[$fn]) ? $photoMeta[$fn] : array('title' => $fn, 'desc' => '');
    
    // Check thumb
    $thumbUrl = $baseUrl . '/Thumbnail/' . pathinfo($fn, PATHINFO_FILENAME) . '_thumbXS.jpg';
    if (!file_exists($targetDir . '/Thumbnail/' . pathinfo($fn, PATHINFO_FILENAME) . '_thumbXS.jpg')) {
        $thumbUrl = $baseUrl . '/Thumbnail/' . pathinfo($fn, PATHINFO_FILENAME) . '_thumb.jpg';
        if (!file_exists($targetDir . '/Thumbnail/' . pathinfo($fn, PATHINFO_FILENAME) . '_thumb.jpg')) {
            $thumbUrl = $baseUrl . '/' . $fn;
        }
    }

    $photoList[] = array('filename' => $fn, 'title' => $meta['title'], 'desc' => $meta['desc'], 'url' => $thumbUrl);
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('manage_photos'); ?> - <?php echo htmlspecialchars($displayName); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
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
            <div>
                <a href="albums.php" class="text-decoration-none">&larr; <?php echo __('back_to_list'); ?></a>
                <h2 class="mt-2"><?php echo htmlspecialchars($displayName); ?> <small class="text-muted fs-6">(<?php echo sprintf(__('total_count'), $totalPhotos); ?>)</small></h2>
            </div>
            <div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="bi bi-cloud-upload"></i> <?php echo __('upload_photos'); ?>
                </button>
                <a href="../make_album.php?force-thumb=1" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-gear-wide-connected"></i> <?php echo __('rebuild_thumbs'); ?>
                </a>
            </div>
        </div>

        <div class="row g-2 mb-4">
            <?php foreach ($photoList as $photo): 
                $isCover = ($currentCover === 'Collection/' . $id . '/' . $photo['filename']) || ($currentCover === $photo['filename']);
            ?>
            <div class="col-6 col-md-4 col-lg-3 col-custom-8">
                <div class="card shadow-sm photo-card <?php echo $isCover ? 'is-cover' : ''; ?>">
                    <img src="<?php echo $photo['url']; ?>" class="card-img-top">
                    <div class="card-body p-2">
                        <h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($photo['filename']); ?>" style="font-size: 0.85rem;">
                            <?php echo htmlspecialchars($photo['filename']); ?>
                        </h6>
                        <p class="card-text small text-muted text-truncate mb-2" style="font-size: 0.75rem;">
                            <?php echo $photo['title'] ? htmlspecialchars($photo['title']) : '(No Title)'; ?>
                        </p>
                        
                        <div class="btn-group w-100">
                            <button class="btn btn-sm btn-outline-primary" onclick="editPhoto('<?php echo $photo['filename']; ?>', '<?php echo addslashes($photo['title']); ?>', '<?php echo addslashes($photo['desc']); ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="photo_actions.php" method="post" class="d-inline" onsubmit="return confirm('<?php echo __('set_cover'); ?>?');">
                                <input type="hidden" name="action" value="set_cover">
                                <input type="hidden" name="album_id" value="<?php echo htmlspecialchars($id); ?>">
                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($photo['filename']); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-star<?php echo $isCover ? '-fill' : ''; ?>"></i>
                                </button>
                            </form>
                            <button class="btn btn-sm btn-outline-danger" onclick="deletePhoto('<?php echo $photo['filename']; ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?id=<?php echo urlencode($id); ?>&page=<?php echo $page - 1; ?>">&laquo;</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo urlencode($id); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?id=<?php echo urlencode($id); ?>&page=<?php echo $page + 1; ?>">&raquo;</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="photo_actions.php" method="post" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="action" value="upload_photos">
            <input type="hidden" name="album_id" value="<?php echo htmlspecialchars($id); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('upload_photos'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('photo_filename'); ?> (支援多檔, JPG only)</label>
                    <input type="file" name="photos[]" class="form-control" multiple accept="image/jpeg" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><?php echo __('upload_photos'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Photo Modal -->
<div class="modal fade" id="editPhotoModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="photo_actions.php" method="post" class="modal-content">
            <input type="hidden" name="action" value="update_photo_info">
            <input type="hidden" name="album_id" value="<?php echo htmlspecialchars($id); ?>">
            <input type="hidden" name="original_filename" id="editOriginalFilename">
            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('edit_photo'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('photo_filename'); ?></label>
                    <input type="text" name="new_filename" id="editFilename" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('photo_title'); ?></label>
                    <input type="text" name="title" id="editTitle" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('photo_desc'); ?></label>
                    <textarea name="description" id="editDesc" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><?php echo __('save_changes'); ?></button>
            </div>
        </form>
    </div>
</div>

<form id="deletePhotoForm" action="photo_actions.php" method="post" style="display:none;">
    <input type="hidden" name="action" value="delete_photo">
    <input type="hidden" name="album_id" value="<?php echo htmlspecialchars($id); ?>">
    <input type="hidden" name="filename" id="deleteFilename">
    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
</form>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
var editModal = new bootstrap.Modal(document.getElementById('editPhotoModal'));
function editPhoto(filename, title, desc) {
    document.getElementById('editOriginalFilename').value = filename;
    document.getElementById('editFilename').value = filename;
    document.getElementById('editTitle').value = title;
    document.getElementById('editDesc').value = desc;
    editModal.show();
}
function deletePhoto(filename) {
    if(confirm('<?php echo __('confirm_delete'); ?> ' + filename)) {
        document.getElementById('deleteFilename').value = filename;
        document.getElementById('deletePhotoForm').submit();
    }
}
</script>
</body>
</html>
