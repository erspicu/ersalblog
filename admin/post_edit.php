<?php
require_once 'auth.php';
require_once 'data_provider.php';
requireLogin();

$dataManager = new DataManager();
$id = $_GET['id'] ?? null;

$post = [
    'post_title' => '',
    'post_filename' => '', 
    'post_date' => date('Y-m-d H:i:s'),
    'post_content' => '',
    'post_tags' => '',
    'post_categories' => '',
    'post_description' => ''
];

$pageTitle = '撰寫新文章';

if ($id) {
    $fetched = $dataManager->getPost($id);
    if ($fetched) {
        $post = $fetched;
        $pageTitle = '編輯文章';
    } else {
        die('文章不存在');
    }
}

// Get all categories for checkboxes
$allCatsData = $dataManager->getAllCategories(); // Returns ['CatName' => Count, ...]
$allCats = array_keys($allCatsData);

// Current post categories (Handle both string and array just in case)
$currentCats = $post['post_categories'];
if (!is_array($currentCats)) {
    $currentCats = explode(',', $currentCats ?? '');
}
$currentCats = array_map('trim', $currentCats);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Blog Admin</title>
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
             <!-- 麵包屑導航 (Optional) -->
             <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="posts.php">文章管理</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $pageTitle; ?></li>
                </ol>
            </nav>
        </div>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
            </div>
            <div class="card-body">
                <form action="post_save.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id ?? ''); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">文章標題</label>
                        <input type="text" name="post_title" class="form-control form-control-lg" value="<?php echo htmlspecialchars($post['post_title']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">檔案名稱 (留空則自動以時間生成)</label>
                            <input type="text" name="post_filename" class="form-control" value="<?php echo htmlspecialchars($post['post_filename']); ?>" placeholder="例如: 20260101-my-post.html">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">發布時間</label>
                            <input type="text" name="post_date" class="form-control" value="<?php echo htmlspecialchars($post['post_date']); ?>" placeholder="YYYY-MM-DD HH:MM:SS">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">HTML 內容</label>
                        <div class="form-text mb-1">請直接輸入 HTML 原始碼。可使用 &lt;!--more--&gt; 設定摘要分隔線。</div>
                        <textarea name="post_content" class="form-control" style="height: 400px; font-family: monospace;"><?php echo htmlspecialchars($post['post_content']); ?></textarea>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded border">
                        <label class="form-label fw-bold">文章分類</label>
                        <div class="mb-2">
                            <?php foreach ($allCats as $cat): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="cats_check[]" value="<?php echo htmlspecialchars($cat); ?>" id="cat_<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo in_array($cat, $currentCats) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cat_<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="input-group input-group-sm w-50">
                            <span class="input-group-text">新增分類</span>
                            <input type="text" name="new_category" class="form-control" placeholder="輸入新分類名稱">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">標籤 (Tags)</label>
                        <input type="text" name="post_tags" class="form-control" value="<?php echo htmlspecialchars($post['post_tags']); ?>" placeholder="使用逗號分隔，例如: 攝影, 開箱, 心得">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">摘要描述 (Meta Description)</label>
                        <textarea name="post_description" class="form-control" rows="2"><?php echo htmlspecialchars($post['post_description']); ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-block text-end">
                        <a href="posts.php" class="btn btn-secondary me-2">取消</a>
                        <button type="submit" class="btn btn-primary btn-lg px-5">儲存文章</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>