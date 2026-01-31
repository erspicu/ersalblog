<?php
require_once 'auth.php';
require_once 'data_provider.php';
requireLogin();

$dataManager = new DataManager();

// 處理刪除請求
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $dataManager->deletePost($_POST['id']);
    header("Location: posts.php?msg=deleted");
    exit;
}

// 讀取文章列表 (依日期降序)
$posts = $dataManager->getAllPosts();

// 輔助函式
function truncate($text, $limit = 60) {
    $text = $text ?? ''; 
    if (mb_strlen($text) > $limit) {
        return mb_substr($text, 0, $limit) . '...';
    }
    return $text;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文章管理 - Blog Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar a { color: #cfd2d6; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
        .main-content { padding: 20px; }
        .table td { vertical-align: middle; }
        /* 描述文字樣式：自然換行，字體稍小 */
        .desc-text { 
            font-size: 0.85em; 
            color: #666; 
            display: block; 
            margin-top: 4px;
            white-space: normal; /* 允許換行 */
            line-height: 1.4;
        }
        .meta-text { font-size: 0.8em; color: #999; }
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
        <div class="text-center mb-3">
            <span class="badge <?php echo ($dataManager->getSource() === 'db') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                模式: <?php echo ($dataManager->getSource() === 'db') ? '資料庫' : '檔案系統'; ?>
            </span>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="index.php">
                    📊 儀表板
                </a>
            </li>
            <li>
                <a href="posts.php" class="active">
                    📝 文章管理
                </a>
            </li>
            <li>
                <a href="categories.php">
                    📂 分類管理
                </a>
            </li>
            <?php if ($dataManager->getSource() === 'file'): ?>
            <li>
                <a href="tool_migrate.php">
                    🔄 資料匯入
                </a>
            </li>
            <?php endif; ?>
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
            <h2>📂 文章列表</h2>
            <a href="post_edit.php" class="btn btn-success">+ 撰寫新文章</a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <!-- 這裡也可以改用 SweetAlert，但先保留 Bootstrap Alert 作為簡單反饋 -->
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                文章已刪除。
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0 table-bordered">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th style="width: 10%;">日期</th>
                            <th style="width: 45%;">文章資訊 (標題/檔名/描述)</th>
                            <th style="width: 15%;">分類</th>
                            <th style="width: 15%;">標籤</th>
                            <th style="width: 15%;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                        <tr>
                            <td class="text-center text-muted small">
                                <?php echo substr($post['post_date'], 0, 10); ?>
                            </td>
                            <td>
                                <!-- 標題 -->
                                <a href="post_edit.php?id=<?php echo urlencode($post['id']); ?>" class="text-decoration-none fw-bold text-dark fs-5">
                                    <?php echo htmlspecialchars($post['post_title'] ?? '無標題'); ?>
                                </a>
                                <br>
                                <!-- 檔名 -->
                                <span class="meta-text text-monospace">
                                    <i class="bi bi-file-earmark-code"></i> <?php echo htmlspecialchars($post['post_filename'] ?? ''); ?>
                                </span>
                                <!-- 描述 (整合至此) -->
                                <?php if (!empty($post['post_description'])): ?>
                                    <div class="desc-text border-start border-3 border-secondary ps-2 mt-2">
                                        <?php echo htmlspecialchars($post['post_description']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="align-top pt-3">
                                <?php 
                                $cats = is_array($post['post_categories']) ? $post['post_categories'] : explode(',', $post['post_categories'] ?? '');
                                foreach($cats as $c) {
                                    $c = trim($c);
                                    if($c) echo "<span class='badge bg-info text-dark me-1 mb-1 d-inline-block'>".htmlspecialchars($c)."</span>";
                                }
                                ?>
                            </td>
                            <td class="align-top pt-3">
                                <?php 
                                $tags = explode(',', $post['post_tags'] ?? '');
                                foreach($tags as $t) {
                                    $t = trim($t);
                                    if($t) echo "<span class='badge bg-secondary me-1 mb-1 d-inline-block'>".htmlspecialchars($t)."</span>";
                                }
                                ?>
                            </td>
                            <td class="text-center align-middle">
                                <a href="post_edit.php?id=<?php echo urlencode($post['id']); ?>" class="btn btn-sm btn-outline-primary mb-1 w-100">編輯</a>
                                <!-- 加入 delete-form class 並移除 onsubmit -->
                                <form method="POST" class="d-block delete-form">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($post['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">刪除</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($posts)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">目前沒有任何文章</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2.all.min.js"></script>
<script>
    // 綁定所有刪除表單
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // 阻止直接送出
            
            Swal.fire({
                title: '確定要刪除這篇文章嗎？',
                text: "這個動作無法復原！",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545', // Danger color
                cancelButtonColor: '#6c757d',  // Secondary color
                confirmButtonText: '是的，刪除它！',
                cancelButtonText: '取消'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit(); // 確認後再送出
                }
            });
        });
    });

    // 檢查 URL 是否有 msg=deleted，如果有，顯示成功提示
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('msg') === 'deleted') {
        Swal.fire({
            title: '已刪除！',
            text: '文章已成功移除。',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        // 移除 URL 參數以免重整時又跳出來 (Optional)
        window.history.replaceState({}, document.title, window.location.pathname);
    }
</script>
</body>
</html>
