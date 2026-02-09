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
            // 預設日期為檔案系統時間
            $date = date('Ymd', filemtime($albumPath));
            
            // 讀取資訊
            $displayName = $dir;
            $desc = '';
            $cover = '';
            
            $commentFile = $albumPath . '/comment_album.txt';
            if (file_exists($commentFile)) {
                $content = file_get_contents($commentFile);
                $parts = explode('|', $content);
                if (isset($parts[0]) && !empty($parts[0])) $displayName = $parts[0];
                if (isset($parts[1])) $desc = $parts[1];
                if (isset($parts[2]) && !empty($parts[2])) $cover = $parts[2];
                // 如果 parts[3] 有值才覆蓋日期
                if (isset($parts[3]) && !empty(trim($parts[3]))) $date = trim($parts[3]);
            }

            // 處理封面路徑 (優先用 XS 縮圖)
            $coverUrl = '';
            if (empty($cover)) {
                $photos = glob($albumPath . '/*.jpg');
                if (!empty($photos)) $cover = basename($photos[0]);
            }

            if (!empty($cover)) {
                $coverFn = basename($cover);
                $info = pathinfo($coverFn);
                $xsPath = $albumPath . '/Thumbnail/' . $info['filename'] . '_thumbXS.jpg';
                if (file_exists($xsPath)) {
                    $coverUrl = $baseUrl . '/' . $dir . '/Thumbnail/' . $info['filename'] . '_thumbXS.jpg';
                } else {
                    $coverUrl = $baseUrl . '/' . $dir . '/' . $coverFn;
                }
            }

            $albums[] = array(
                'id' => $dir, // 目錄名
                'name' => $displayName,
                'desc' => $desc,
                'date' => $date,
                'coverUrl' => $coverUrl
            );
        }
    }
}

// 依日期排序 (新到舊)
usort($albums, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

// 分頁邏輯
$perPage = 40; // 統一改為一頁 40 個 (配合一橫 8 個)
$totalAlbums = count($albums);
$totalPages = ceil($totalAlbums / $perPage);
$page = isset($_GET['page']) ? max(1, min($totalPages, (int)$_GET['page'])) : 1;
if ($totalPages == 0) $page = 1;
$offset = ($page - 1) * $perPage;
$pagedAlbums = array_slice($albums, $offset, $perPage);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>相簿管理 - 相簿列表</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .album-card img { 
            width: 100%; 
            height: 150px; 
            object-fit: contain; 
            background-color: #f0f0f0;
        }
        .album-card { transition: all 0.2s; position: relative; }
        .album-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
        .album-badge { 
            position: absolute; 
            top: 5px; 
            right: 5px; 
            font-size: 0.7rem; 
            padding: 2px 6px; 
            background: rgba(0,0,0,0.6); 
            color: white; 
            border-radius: 4px; 
        }
        /* 自定義 8 欄位佈局 */
        @media (min-width: 1400px) {
            .col-custom-8 { flex: 0 0 auto; width: 12.5%; }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>

    <div class="main-content flex-grow-1 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>相簿管理 <small class="text-muted fs-6">(共 <?php echo $totalAlbums; ?> 個相簿)</small></h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAlbumModal">
                <i class="bi bi-plus-lg"></i> 新增相簿
            </button>
        </div>

        <div class="row g-2 mb-4">
            <?php foreach ($pagedAlbums as $album): ?>
            <div class="col-6 col-md-4 col-lg-3 col-custom-8">
                <div class="card shadow-sm album-card h-100">
                    <img src="<?php echo $album['coverUrl'] ? $album['coverUrl'] : 'https://via.placeholder.com/320x200?text=No+Photo'; ?>" class="card-img-top">
                    <span class="album-badge"><?php echo $album['date']; ?></span>
                    <div class="card-body p-2 d-flex flex-column">
                        <h6 class="card-title text-truncate mb-1" title="<?php echo htmlspecialchars($album['name']); ?>" style="font-size: 0.85rem;">
                            <?php echo htmlspecialchars($album['name']); ?>
                        </h6>
                        <p class="card-text small text-muted text-truncate mb-2" style="font-size: 0.75rem; flex-grow: 1;">
                            <?php echo $album['desc'] ? htmlspecialchars($album['desc']) : '(無描述)'; ?>
                        </p>
                        
                        <div class="btn-group w-100 mt-auto">
                            <a href="album_photos.php?id=<?php echo urlencode($album['id']); ?>" class="btn btn-sm btn-outline-primary" title="管理照片">
                                <i class="bi bi-images"></i>
                            </a>
                            <a href="album_edit.php?id=<?php echo urlencode($album['id']); ?>" class="btn btn-sm btn-outline-secondary" title="編輯相簿資訊">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('<?php echo htmlspecialchars($album['id']); ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($pagedAlbums)): ?>
            <div class="col-12 text-center py-5 text-muted">目前沒有相簿</div>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">&laquo;</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">&raquo;</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Create Album Modal -->
<div class="modal fade" id="createAlbumModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="album_actions.php" method="post" class="modal-content">
            <input type="hidden" name="action" value="create_album">
            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
            <div class="modal-header">
                <h5 class="modal-title">新增相簿</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">相簿目錄名稱 (英文/數字)</label>
                    <input type="text" name="dir_name" class="form-control" required pattern="[A-Za-z0-9_-]+" title="僅限英數字、底線與連字號">
                    <small class="text-muted">這將作為資料夾名稱，建立後建議不要頻繁修改。</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">顯示標題</label>
                    <input type="text" name="display_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">描述</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="submit" class="btn btn-primary">建立</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" action="album_actions.php" method="post" style="display:none;">
    <input type="hidden" name="action" value="delete_album">
    <input type="hidden" name="album_id" id="deleteAlbumId">
    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
</form>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id) {
    if(confirm('確定要刪除相簿「' + id + '」嗎？\n這將會刪除所有照片且無法復原！')) {
        document.getElementById('deleteAlbumId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
</body>
</html>