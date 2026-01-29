<?php
require_once 'auth.php';
requireLogin();

// 處理刪除請求
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    header("Location: posts.php?msg=deleted");
    exit;
}

// 讀取文章列表 (依日期降序)
$stmt = $pdo->query("SELECT id, post_date, post_title, post_categories, post_filename, post_tags FROM blog_posts ORDER BY post_date DESC");
$posts = $stmt->fetchAll();
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
                <a href="posts.php" class="active">
                    📝 文章管理
                </a>
            </li>
            <li>
                <a href="categories.php">
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
            <h2>📂 文章列表</h2>
            <a href="post_edit.php" class="btn btn-success">+ 撰寫新文章</a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                文章已刪除。
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="10%">日期</th>
                            <th width="35%">標題</th>
                            <th width="15%">分類</th>
                            <th width="20%">標籤</th>
                            <th width="20%" class="text-end">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                        <tr>
                            <td><?php echo substr($post['post_date'], 0, 10); ?></td>
                            <td>
                                <a href="post_edit.php?id=<?php echo $post['id']; ?>" class="text-decoration-none fw-bold">
                                    <?php echo htmlspecialchars($post['post_title'] ?? ''); ?>
                                </a>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($post['post_filename'] ?? ''); ?></small>
                            </td>
                            <td>
                                <?php 
                                $cats = explode(',', $post['post_categories'] ?? '');
                                foreach($cats as $c) {
                                    if(trim($c)) echo "<span class='badge bg-info text-dark me-1'>".htmlspecialchars($c)."</span>";
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $tags = explode(',', $post['post_tags'] ?? '');
                                foreach($tags as $t) {
                                    $t = trim($t);
                                    if($t) echo "<span class='badge bg-secondary me-1'>".htmlspecialchars($t)."</span>";
                                }
                                ?>
                            </td>
                            <td class="text-end">
                                <a href="post_edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-primary">編輯</a>
                                <form method="POST" class="d-inline-block" onsubmit="return confirm('確定要刪除這篇文章嗎？');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">刪除</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
