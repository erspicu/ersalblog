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

        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="?id=<?php echo urlencode($id); ?>&page=<?php echo $page - 1; ?>">&laquo;</a></li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>"><a class="page-link" href="?id=<?php echo urlencode($id); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>"><a class="page-link" href="?id=<?php echo urlencode($id); ?>&page=<?php echo $page + 1; ?>">&raquo;</a></li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><?php echo __('upload_photos'); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" id="uploadModalClose"></button></div>
            <div class="modal-body">
                <div id="uploadInputArea">
                    <div class="mb-3"><label class="form-label"><?php echo __('select_photos'); ?> (JPG/JPEG)</label><input type="file" id="photoInput" class="form-control" multiple accept=".jpg,.jpeg"></div>
                    <div class="alert alert-info small"><i class="bi bi-info-circle"></i> 支援多檔案選取，系統將自動逐一上傳以避免伺服器限制。</div>
                </div>
                <div id="uploadProgressArea" style="display:none;">
                    <div class="mb-2 d-flex justify-content-between">
                        <span id="uploadStatusText">準備中...</span>
                        <span id="uploadCountText">0 / 0</span>
                    </div>
                    <div class="progress mb-3" style="height: 20px;">
                        <div id="totalProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <div class="small text-muted mb-1">當前檔案進度:</div>
                    <div class="progress" style="height: 10px;">
                        <div id="fileProgressBar" class="progress-bar bg-info" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div id="uploadLog" class="mt-3 small overflow-auto" style="max-height: 150px; background: #f8f9fa; border: 1px solid #eee; padding: 5px;"></div>
                </div>
            </div>
            <div class="modal-footer" id="uploadModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                <button type="button" class="btn btn-success" onclick="startSequentialUpload()"><?php echo __('upload_btn'); ?></button>
            </div>
        </div>
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
            <div class="modal-header"><h5 class="modal-title"><?php echo __('edit_photo'); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label"><?php echo __('photo_filename'); ?></label><input type="text" name="new_filename" id="editNewFilename" class="form-control" required></div>
                <div class="mb-3"><label class="form-label"><?php echo __('photo_title'); ?></label><input type="text" name="title" id="editPhotoTitle" class="form-control"></div>
                <div class="mb-3"><label class="form-label"><?php echo __('photo_desc'); ?></label><textarea name="description" id="editPhotoDesc" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button><button type="submit" class="btn btn-primary"><?php echo __('save_changes'); ?></button></div>
        </form>
    </div>
</div>

<form id="deletePhotoForm" action="photo_actions.php" method="post" style="display:none;">
    <input type="hidden" name="action" value="delete_photo">
    <input type="hidden" name="album_id" value="<?php echo htmlspecialchars($id); ?>">
    <input type="hidden" name="filename" id="deletePhotoFilename">
    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
</form>

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

function editPhoto(filename, title, desc) {
    document.getElementById('editOriginalFilename').value = filename;
    document.getElementById('editNewFilename').value = filename;
    document.getElementById('editPhotoTitle').value = title;
    document.getElementById('editPhotoDesc').value = desc;
    new bootstrap.Modal(document.getElementById('editPhotoModal')).show();
}

function deletePhoto(filename) {
    if (confirm('<?php echo __('confirm_delete'); ?>')) {
        document.getElementById('deletePhotoFilename').value = filename;
        document.getElementById('deletePhotoForm').submit();
    }
}

async function startSequentialUpload() {
    const input = document.getElementById('photoInput');
    const files = input.files;
    if (files.length === 0) {
        Swal.fire(adminLang.error, '請先選擇照片', 'error');
        return;
    }

    // UI Initial State
    document.getElementById('uploadInputArea').style.display = 'none';
    document.getElementById('uploadProgressArea').style.display = 'block';
    document.getElementById('uploadModalFooter').style.display = 'none';
    document.getElementById('uploadModalClose').style.display = 'none';
    const log = document.getElementById('uploadLog');
    log.innerHTML = '';
    
    const total = files.length;
    let successCount = 0;
    let errorCount = 0;

    for (let i = 0; i < total; i++) {
        const file = files[i];
        const currentNum = i + 1;
        
        // Update UI for current file
        document.getElementById('uploadStatusText').innerText = `正在上傳: ${file.name}`;
        document.getElementById('uploadCountText').innerText = `${currentNum} / ${total}`;
        document.getElementById('fileProgressBar').style.width = '0%';
        
        const formData = new FormData();
        formData.append('action', 'upload_photos');
        formData.append('album_id', '<?php echo addslashes($id); ?>');
        formData.append('csrf_token', '<?php echo getCSRFToken(); ?>');
        formData.append('ajax', '1');
        formData.append('photos', file); // Use 'photos' name as expected by backend (single file)

        try {
            await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'photo_actions.php', true);
                
                // Track single file upload progress
                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        document.getElementById('fileProgressBar').style.width = percent + '%';
                    }
                };

                xhr.onload = () => {
                    if (xhr.status === 200) {
                        const res = JSON.parse(xhr.responseText);
                        if (res.status === 'success') {
                            successCount++;
                            log.innerHTML += `<div class="text-success">✅ ${file.name} 上傳成功</div>`;
                        } else {
                            errorCount++;
                            log.innerHTML += `<div class="text-danger">❌ ${file.name} 失敗: ${res.message}</div>`;
                        }
                    } else {
                        errorCount++;
                        log.innerHTML += `<div class="text-danger">❌ ${file.name} HTTP 錯誤: ${xhr.status}</div>`;
                    }
                    resolve();
                };

                xhr.onerror = () => {
                    errorCount++;
                    log.innerHTML += `<div class="text-danger">❌ ${file.name} 網路錯誤</div>`;
                    resolve();
                };

                xhr.send(formData);
            });
        } catch (e) {
            console.error(e);
        }

        // Update Total Progress
        const totalPercent = Math.round((currentNum / total) * 100);
        const totalBar = document.getElementById('totalProgressBar');
        totalBar.style.width = totalPercent + '%';
        totalBar.innerText = totalPercent + '%';
        log.scrollTop = log.scrollHeight;
    }

    // Finished
    document.getElementById('uploadStatusText').innerText = '正在自動更新相簿資料與縮圖...';
    
    // 自動觸發重建 (快速模式)
    if (successCount > 0) {
        const rebuildData = new FormData();
        rebuildData.append('action', 'rebuild_album');
        rebuildData.append('album_id', '<?php echo addslashes($id); ?>');
        rebuildData.append('csrf_token', '<?php echo getCSRFToken(); ?>');
        // 不帶 force 參數即為快速模式 (僅處理新增檔案)

        try {
            const rbRes = await fetch('album_actions.php', { method: 'POST', body: rebuildData });
            const rbJson = await rbRes.json();
            console.log('Auto Rebuild:', rbJson.message);
        } catch (e) {
            console.error('Auto Rebuild Failed:', e);
        }
    }

    document.getElementById('uploadStatusText').innerText = '上傳與更新作業已完成';
    Swal.fire({
        title: '上傳完成',
        text: `成功: ${successCount}, 失敗: ${errorCount}。相簿資料已自動更新。`,
        icon: successCount > 0 ? 'success' : 'error'
    }).then(() => location.reload());
}
</script>
</body>
</html>
