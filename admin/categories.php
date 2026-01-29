<?php
require_once 'auth.php';
requireLogin();

$msg = '';

// --- 處理表單提交 (邏輯不變) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // [改名功能]
    if ($action === 'rename') {
        $oldName = trim($_POST['old_name']);
        $newName = trim($_POST['new_name']);
        
        if ($oldName && $newName && $oldName !== $newName) {
            $stmt = $pdo->prepare("SELECT id, post_categories FROM blog_posts WHERE post_categories LIKE ?");
            $stmt->execute(['%' . $oldName . '%']);
            $rows = $stmt->fetchAll();
            
            $count = 0;
            foreach ($rows as $row) {
                $cats = explode(',', $row['post_categories']);
                $cats = array_map('trim', $cats);
                
                if (($key = array_search($oldName, $cats)) !== false) {
                    $cats[$key] = $newName; 
                    $cats = array_unique($cats);
                    $newStr = implode(',', $cats);
                    
                    $update = $pdo->prepare("UPDATE blog_posts SET post_categories = ? WHERE id = ?");
                    $update->execute([$newStr, $row['id']]);
                    $count++;
                }
            }
            $msg = "成功將 {$count} 篇文章的分類由「{$oldName}」改為「{$newName}」。";
        }
    }
    
    // [刪除功能]
    if ($action === 'delete') {
        $delName = trim($_POST['delete_name']);
        
        if ($delName) {
            $stmt = $pdo->prepare("SELECT id, post_categories FROM blog_posts WHERE post_categories LIKE ?");
            $stmt->execute(['%' . $delName . '%']);
            $rows = $stmt->fetchAll();
            
            $count = 0;
            foreach ($rows as $row) {
                $cats = explode(',', $row['post_categories']);
                $cats = array_map('trim', $cats);
                
                if (($key = array_search($delName, $cats)) !== false) {
                    unset($cats[$key]);
                    $newStr = implode(',', $cats);
                    
                    $update = $pdo->prepare("UPDATE blog_posts SET post_categories = ? WHERE id = ?");
                    $update->execute([$newStr, $row['id']]);
                    $count++;
                }
            }
            $msg = "成功從 {$count} 篇文章中移除分類「{$delName}」。";
        }
    }
}

// --- 取得目前所有分類統計 ---
$catStats = [];
$stmt = $pdo->query("SELECT post_categories FROM blog_posts");
while ($row = $stmt->fetch()) {
    $parts = explode(',', $row['post_categories']);
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        if (!isset($catStats[$p])) {
            $catStats[$p] = 0;
        }
        $catStats[$p]++;
    }
}
arsort($catStats);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分類管理 - Blog Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar a { color: #cfd2d6; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
        .main-content { padding: 20px; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column flex-shrink-0 p-3" style="width: 250px;">
        <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <span class="fs-4">Blog Admin</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="index.php">
                    📊 儀表板
                </a>
            </li>
            <li>
                <a href="posts.php">
                    📝 文章管理
                </a>
            </li>
            <li>
                <a href="categories.php" class="active">
                    📂 分類管理
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="../blog.html" target="_blank">🌍 預覽網站</a>
            <a href="logout.php" class="text-danger mt-2">🚪 登出</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>📂 分類管理</h2>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-info">
            <small>提示：此處列出目前所有文章中已使用的分類。由於系統架構特性，「新增分類」請直接在撰寫文章時輸入即可。此處主要提供維護現有分類的功能。</small>
        </div>

        <div class="row">
            <?php foreach ($catStats as $catName => $count): ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0 fw-bold text-primary"><?php echo htmlspecialchars($catName); ?></h5>
                            <small class="text-muted">共 <?php echo $count; ?> 篇文章</small>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                    data-bs-toggle="modal" data-bs-target="#renameModal" 
                                    data-oldname="<?php echo htmlspecialchars($catName); ?>">
                                改名
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                    data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                    data-delname="<?php echo htmlspecialchars($catName); ?>">
                                移除
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($catStats)): ?>
                <div class="col-12 text-center text-muted p-5">目前沒有任何分類資料。</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal 保持不變，複製進來 -->
<div class="modal fade" id="renameModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="rename">
            <input type="hidden" name="old_name" id="renameOldName">
            <div class="modal-header">
                <h5 class="modal-title">重新命名分類</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">原名稱</label>
                    <input type="text" class="form-control" id="renameOldNameDisplay" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">新名稱</label>
                    <input type="text" name="new_name" class="form-control" required placeholder="請輸入新名稱">
                </div>
                <div class="alert alert-warning py-2 small">
                    <i class="bi bi-exclamation-triangle"></i> 注意：這將會批次修改所有相關的文章資料。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="submit" class="btn btn-primary">確認修改</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="delete_name" id="deleteName">
            <div class="modal-header">
                <h5 class="modal-title">移除分類</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>您確定要移除分類 <strong id="deleteNameDisplay" class="text-danger"></strong> 嗎？</p>
                <p class="text-muted">這將會從所有相關的文章中刪除此分類標籤，但文章本身不會被刪除。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="submit" class="btn btn-danger">確認移除</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
    var renameModal = document.getElementById('renameModal');
    renameModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var name = button.getAttribute('data-oldname');
        document.getElementById('renameOldName').value = name;
        document.getElementById('renameOldNameDisplay').value = name;
    });

    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var name = button.getAttribute('data-delname');
        document.getElementById('deleteName').value = name;
        document.getElementById('deleteNameDisplay').textContent = name;
    });
</script>
</body>
</html>