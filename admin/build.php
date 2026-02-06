<?php
require_once 'auth.php';
require_once 'data_provider.php';
requireLogin();

$dataManager = new DataManager();
$posts = $dataManager->getAllPosts();

// 側邊欄狀態
$badgeClass = 'bg-warning text-dark';
$modeText = __('mode_file_short');
$source = $dataManager->getSource();
if ($source === 'db') {
    $badgeClass = 'bg-success';
    $modeText = __('mode_db_short');
} elseif ($source === 'sqlite') {
    $badgeClass = 'bg-info text-dark';
    $modeText = 'SQLite';
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(isset($currentLang) ? $currentLang : 'zh_TW'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('build_title'); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar a { color: #cfd2d6; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
        .main-content { padding: 20px; }
        .log-container { background: #000; color: #00ff00; padding: 15px; height: 300px; overflow-y: scroll; font-family: monospace; border-radius: 5px; }
        .post-list-scroll { max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px; padding: 10px; background: white; }
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
            <span class="badge <?php echo $badgeClass; ?>">
                <?php echo __('mode_label'); ?>: <?php echo $modeText; ?>
            </span>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item"><a href="index.php"><?php echo __('nav_dashboard'); ?></a></li>
            <li><a href="posts.php"><?php echo __('nav_posts'); ?></a></li>
            <li><a href="categories.php"><?php echo __('nav_categories'); ?></a></li>
            <li><a href="build.php" class="active"><?php echo __('nav_build'); ?></a></li>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><?php echo __('build_title'); ?></h2>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold">
                        <?php echo __('label_build_options'); ?>
                    </div>
                    <div class="card-body">
                        <form id="buildForm">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="forceAll" name="forceAll">
                                <label class="form-check-label" for="forceAll"><?php echo __('opt_force_all'); ?></label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="updateJson" name="updateJson" checked>
                                <label class="form-check-label" for="updateJson"><?php echo __('opt_update_json'); ?></label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="forceGlobal" name="forceGlobal">
                                <label class="form-check-label" for="forceGlobal"><?php echo __('opt_force_global'); ?></label>
                            </div>
                            
                            <hr>
                            
                            <button type="button" id="startBuildBtn" class="btn btn-primary w-100 py-2 fw-bold">
                                <?php echo __('btn_start_build'); ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">
                        <?php echo __('build_log_title'); ?>
                    </div>
                    <div class="card-body">
                        <div id="buildLog" class="log-container">
                            <div class="text-muted">Waiting for action...</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><?php echo __('label_select_posts'); ?></span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAllPosts(true)">Select All</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAllPosts(false)">Clear</button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="post-list-scroll">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="40" class="text-center">#</th>
                                        <th><?php echo __('col_title'); ?></th>
                                        <th width="120"><?php echo __('col_date'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $p): ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input post-check" name="selected_posts[]" value="<?php echo htmlspecialchars($p['post_filename']); ?>">
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($p['post_title'] ?: __('no_title')); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($p['post_filename']); ?></small>
                                        </td>
                                        <td><small><?php echo substr($p['post_date'], 0, 10); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
function toggleAllPosts(checked) {
    document.querySelectorAll('.post-check').forEach(el => el.checked = checked);
}

document.getElementById('startBuildBtn').addEventListener('click', function() {
    const btn = this;
    const log = document.getElementById('buildLog');
    const formData = new FormData(document.getElementById('buildForm'));
    
    // Add selected posts manually from checkboxes
    const selected = [];
    document.querySelectorAll('.post-check:checked').forEach(el => {
        selected.push(el.value);
    });
    
    if (selected.length > 0) {
        formData.append('selected_posts', JSON.stringify(selected));
    }

    btn.disabled = true;
    log.innerHTML = '<div>[SYSTEM] Starting build process...</div>';
    
    // Use XMLHttpRequest for streaming-like experience or just fetch and update
    // Since we want a simple solution, we use a loop or a single request that returns multiple lines.
    
    fetch('build_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        log.innerHTML += '<div>' + data + '</div>';
        log.scrollTop = log.scrollHeight;
        btn.disabled = false;
    })
    .catch(error => {
        log.innerHTML += '<div class="text-danger">[ERROR] ' + error + '</div>';
        btn.disabled = false;
    });
});
</script>
</body>
</html>
