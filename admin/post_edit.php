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

$pageTitle = __('edit_title_new');

if ($id) {
    $fetched = $dataManager->getPost($id);
    if ($fetched) {
        $post = $fetched;
        $pageTitle = __('edit_title_edit');
    } else {
        die(__('post_not_found'));
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
<html lang="<?php echo htmlspecialchars($currentLang ?? 'zh_TW'); ?>">
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
            <span class="fs-4"><?php echo __('nav_brand'); ?></span>
        </a>
        <hr>
        <div class="text-center mb-3">
            <span class="badge <?php echo ($dataManager->getSource() === 'db') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                <?php echo __('mode_label'); ?>: <?php echo ($dataManager->getSource() === 'db') ? __('mode_db_short') : __('mode_file_short'); ?>
            </span>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="index.php">
                    <?php echo __('nav_dashboard'); ?>
                </a>
            </li>
            <li>
                <a href="posts.php" class="active">
                    <?php echo __('nav_posts'); ?>
                </a>
            </li>
            <li>
                <a href="categories.php">
                    <?php echo __('nav_categories'); ?>
                </a>
            </li>
            <?php if ($dataManager->getSource() === 'file'): ?>
            <li>
                <a href="tool_migrate.php">
                    <?php echo __('nav_import'); ?>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="../blog.html" target="_blank"><?php echo __('nav_preview'); ?></a>
            <a href="logout.php" class="text-danger mt-2"><?php echo __('nav_logout'); ?></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-3">
             <!-- 麵包屑導航 (Optional) -->
             <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="posts.php"><?php echo __('breadcrumb_home'); ?></a></li>
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
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
            
            <input type="hidden" name="old_filename" value="<?php echo htmlspecialchars($filename); ?>">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id ?? ''); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_post_title'); ?></label>
                        <input type="text" name="post_title" class="form-control form-control-lg" value="<?php echo htmlspecialchars($post['post_title']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('label_filename'); ?></label>
                            <input type="text" name="post_filename" class="form-control" value="<?php echo htmlspecialchars($post['post_filename']); ?>" placeholder="例如: 20260101-my-post.html">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('label_post_date'); ?></label>
                            <input type="text" name="post_date" class="form-control" value="<?php echo htmlspecialchars($post['post_date']); ?>" placeholder="YYYY-MM-DD HH:MM:SS">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_html_content'); ?></label>
                        <div class="form-text mb-1"><?php echo __('hint_html_content'); ?></div>
                        <textarea name="post_content" class="form-control" style="height: 400px; font-family: monospace;"><?php echo htmlspecialchars($post['post_content']); ?></textarea>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded border">
                        <label class="form-label fw-bold"><?php echo __('label_categories'); ?></label>
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
                            <span class="input-group-text"><?php echo __('label_add_category'); ?></span>
                            <input type="text" name="new_category" class="form-control" placeholder="<?php echo __('ph_new_category'); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_tags'); ?></label>
                        <input type="text" name="post_tags" class="form-control" value="<?php echo htmlspecialchars($post['post_tags']); ?>" placeholder="<?php echo __('ph_tags'); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('label_desc'); ?></label>
                        <textarea name="post_description" class="form-control" rows="2"><?php echo htmlspecialchars($post['post_description']); ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-block text-end">
                        <a href="posts.php" class="btn btn-secondary me-2"><?php echo __('btn_cancel'); ?></a>
                        <button type="submit" class="btn btn-primary btn-lg px-5"><?php echo __('btn_save'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require 'common_js_inc.php'; ?>
</body>
</html>
