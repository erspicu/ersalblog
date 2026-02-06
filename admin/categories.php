<?php
require_once 'auth.php';
require_once 'data_provider.php';
requireLogin();

$dataManager = new DataManager();
$msg = '';

// --- 處理表單提交 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 驗證 CSRF Token
    validateCSRFRequest();
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // [改名功能]
    if ($action === 'rename') {
        $oldName = trim($_POST['old_name']);
        $newName = trim($_POST['new_name']);
        
        if ($oldName && $newName && $oldName !== $newName) {
            $count = $dataManager->renameCategory($oldName, $newName);
            if ($count > 0 || $count === true) {
                $msg = sprintf(__('msg_rename_success'), $oldName, $newName);
            } else {
                 $msg = __('msg_rename_fail');
            }
        }
    }
    
    // [刪除功能]
    if ($action === 'delete') {
        $delName = trim($_POST['delete_name']);
        
        if ($delName) {
            $count = $dataManager->deleteCategory($delName);
            if ($count > 0 || $count === true) {
                $msg = sprintf(__('msg_remove_success'), $delName);
            } else {
                 $msg = __('msg_remove_fail');
            }
        }
    }

    // [新增功能]
    if ($action === 'create') {
        $newCatName = trim($_POST['new_category_name']);
        if ($newCatName) {
            $result = $dataManager->createCategory($newCatName);
            if ($result) {
                $msg = sprintf(__('msg_create_success'), $newCatName);
            } else {
                $msg = __('msg_create_fail');
            }
        }
    }
}

// --- 取得目前所有分類統計 ---
$catStats = $dataManager->getAllCategories();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(isset($currentLang) ? $currentLang : 'zh_TW'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="<?php echo getCSRFToken(); ?>">
    <title><?php echo __('cat_title'); ?> - Blog Admin</title>
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
            <span class="badge <?php echo ($dataManager->getSource() === 'db') ? 'bg-success' : (($dataManager->getSource() === 'sqlite') ? 'bg-info text-dark' : 'bg-warning text-dark'); ?>">
                <?php echo __('mode_label'); ?>: <?php echo ($dataManager->getSource() === 'db') ? __('mode_db_short') : (($dataManager->getSource() === 'sqlite') ? 'SQLite' : __('mode_file_short')); ?>
            </span>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="index.php">
                    <?php echo __('nav_dashboard'); ?>
                </a>
            </li>
            <li class="nav-item"><a href="posts.php"><?php echo __('nav_posts'); ?></a></li>
            <li class="nav-item"><a href="categories.php" class="active"><?php echo __('nav_categories'); ?></a></li>
            <li class="nav-item"><a href="tool_migrate.php"><?php echo __('nav_import'); ?></a></li>
            <li class="nav-item"><a href="tool_backup.php"><?php echo __('nav_backup'); ?></a></li>
            <li class="nav-item"><a href="settings.php"><?php echo __('nav_settings'); ?></a></li>
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
            <h2><?php echo __('cat_title'); ?></h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createModal">
                <?php echo __('btn_add_cat'); ?>
            </button>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-info">
            <small><?php echo __('cat_hint'); ?></small>
        </div>

        <div class="row">
            <?php foreach ($catStats as $catName => $count): ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0 fw-bold text-primary"><?php echo htmlspecialchars($catName); ?></h5>
                            <small class="text-muted"><?php echo __('cat_count_suffix'); ?>: <?php echo $count; ?></small>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                    data-bs-toggle="modal" data-bs-target="#renameModal" 
                                    data-oldname="<?php echo htmlspecialchars($catName); ?>">
                                <?php echo __('btn_rename'); ?>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                    data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                    data-delname="<?php echo htmlspecialchars($catName); ?>">
                                <?php echo __('btn_remove'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($catStats)): ?>
                <div class="col-12 text-center text-muted p-5"><?php echo __('no_categories'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Create -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('modal_create_title'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('modal_cat_name'); ?></label>
                    <input type="text" name="new_category_name" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('confirm_cancel'); ?></button>
                <button type="submit" class="btn btn-success"><?php echo __('btn_create'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Rename -->
<div class="modal fade" id="renameModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
            <input type="hidden" name="action" value="rename">
            <input type="hidden" name="old_name" id="renameOldName">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('modal_rename_title'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('modal_old_name'); ?></label>
                    <input type="text" class="form-control" id="renameOldNameDisplay" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('modal_new_name'); ?></label>
                    <input type="text" name="new_name" class="form-control" required>
                </div>
                <div class="alert alert-warning py-2 small">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo __('modal_rename_warn'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('confirm_cancel'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo __('btn_confirm_modify'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Delete -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="delete_name" id="deleteName">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('modal_remove_title'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?php echo __('modal_remove_confirm'); ?> <strong id="deleteNameDisplay" class="text-danger"></strong> <?php echo __('modal_remove_confirm_suffix'); ?></p>
                <p class="text-muted"><?php echo __('modal_remove_hint'); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('confirm_cancel'); ?></button>
                <button type="submit" class="btn btn-danger"><?php echo __('btn_confirm_remove'); ?></button>
            </div>
        </form>
    </div>
</div>

<?php require 'common_js_inc.php'; ?>
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
