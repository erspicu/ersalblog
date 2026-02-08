<?php
require_once 'auth.php';
require_once 'data_provider.php'; // Include DataManager
requireLogin();

$dataManager = new DataManager();
$source = $dataManager->getSource(); // 'db', 'file', or 'sqlite'

// 收集系統資訊
$phpVersion = phpversion();

// 1. 文章總數
$postCounts = $dataManager->getPostCounts();
$postCount = $postCounts['total'];

// 1.1 統計未建置靜態網頁的文章 (僅限已發布文章)
$allPosts = $dataManager->getAllPosts();
$missingStaticCount = 0;
foreach ($allPosts as $p) {
    if (isset($p['status']) && $p['status'] === 'draft') continue;
    $staticPath = __DIR__ . '/../post/' . $p['post_filename'];
    if (!file_exists($staticPath)) {
        $missingStaticCount++;
    }
}

// 2. DB 大小 & 連線資訊 (For DB / SQLite)
$dbSize = 'N/A';
$dbHost = 'N/A';
$serverVersion = 'N/A';
$serverInfo = '';
$dbType = 'File System';
$dbName = '';

if ($source === 'db') {
    $dbHost = $dbConfig['host'];
    $dbName = $dbConfig['dbname'];
    
    // DB Size (MySQL)
    try {
        $stmt = $pdo->prepare("SELECT round(SUM(data_length + index_length) / 1024 / 1024, 2) 
                               FROM information_schema.TABLES 
                               WHERE table_schema = ?");
        $stmt->execute(array($dbName));
        $dbSize = $stmt->fetchColumn();
        if($dbSize === false) $dbSize = 0;
    } catch (Exception $e) {
        $dbSize = "Unknown";
    }

    // Version Info
    try {
        $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        $serverInfo = $pdo->getAttribute(PDO::ATTR_SERVER_INFO);
        $dbType = (strpos(strtolower($serverVersion), 'mariadb') !== false) ? 'MariaDB' : 'MySQL';
    } catch (Exception $e) {
        $serverVersion = "Unknown";
    }
} elseif ($source === 'sqlite') {
    $dbType = 'SQLite';
    // DB Size (File Size)
    // $sqlite_path is available from config via auth.php
    $target = __DIR__ . '/../' . $sqlite_path;
    $dbName = basename($sqlite_path);
    if (file_exists($target)) {
        $dbSize = round(filesize($target) / 1024 / 1024, 2);
    } else {
        $dbSize = 0;
    }

    // Version
    try {
        $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    } catch (Exception $e) {
        $serverVersion = "Unknown";
    }
}

// 3. 磁碟空間 (GB)
$diskFree = @disk_free_space(".");
$diskTotal = @disk_total_space(".");
$diskFreeGB = $diskFree ? round($diskFree / 1024 / 1024 / 1024, 2) : 'N/A';
$diskTotalGB = $diskTotal ? round($diskTotal / 1024 / 1024 / 1024, 2) : 'N/A';

// 4. 最新文章
$recentPosts = $dataManager->getRecentPosts(5);

// 5. 相簿服務狀態
require_once 'health_check.php';
$albumStatus = SystemHealth::checkAlbum();

// Sidebar Logic
$badgeClass = 'bg-warning text-dark';
$modeText = __('mode_file_short');
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
    <title><?php echo __('login_title'); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <?php require 'sidebar_inc.php'; ?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 bg-light">
        <?php 
        require_once 'health_check.php';
        $installCheck = SystemHealth::checkInstaller();
        if ($installCheck['exists']): 
        ?>
            <div class="alert alert-danger shadow-sm mb-4">
                <h5 class="alert-heading fw-bold"><?php echo $installCheck['message']; ?></h5>
                <p class="mb-0"><?php echo __('warn_install_file_exists_desc'); ?></p>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0"><?php echo __('welcome_msg'); ?>，<?php echo htmlspecialchars($_SESSION['admin_user']); ?>！</h2>
                <small class="text-muted"><?php echo get_detailed_os_info(); ?></small>
            </div>
            <span class="badge bg-secondary">PHP v<?php echo $phpVersion; ?></span>
        </div>
        
        <!-- 數據卡片列 -->
        <div class="row g-3 mb-4">
            <?php if ($albumStatus['status']): ?>
            <!-- 相簿服務入口 -->
            <div class="col-md-3">
                <a href="../<?php echo $album_path; ?>admin/index.php" class="text-decoration-none">
                    <div class="card stat-card text-white h-100" style="background-color: #6f42c1;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">相簿服務</h6>
                                    <h2 class="my-2">管理</h2>
                                    <small><?php echo $albumStatus['message']; ?></small>
                                </div>
                                <span class="fs-1">🖼️</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <!-- 文章總數 -->
            <div class="col-md-3">
                <div class="card stat-card text-white bg-primary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><?php echo __('stat_posts'); ?></h6>
                                <h2 class="my-2"><?php echo $postCount; ?></h2>
                                <small>
                                    <?php echo __('stat_published'); ?>: <b><?php echo $postCounts['published']; ?></b> | 
                                    <?php echo __('stat_drafts'); ?>: <b><?php echo $postCounts['draft']; ?></b>
                                </small>
                                <?php if ($missingStaticCount > 0): ?>
                                <div class="mt-1">
                                    <span class="badge bg-danger">
                                        <?php echo __('stat_no_static'); ?>: <?php echo $missingStaticCount; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <span class="fs-1">📝</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- DB 大小 -->
            <div class="col-md-3">
                <div class="card stat-card text-white bg-success h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><?php echo __('stat_db_size'); ?></h6>
                                <h2 class="my-2"><?php echo $dbSize; ?> <span class="fs-6"><?php echo ($source === 'db' || $source === 'sqlite') ? 'MB' : ''; ?></span></h2>
                                <small><?php echo ($source === 'db' || $source === 'sqlite') ? 'DB: '.htmlspecialchars($dbName) : __('not_applicable'); ?></small>
                            </div>
                            <span class="fs-1">💾</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 磁碟空間 -->
            <div class="col-md-3">
                <div class="card stat-card text-white bg-info h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><?php echo __('stat_disk_free'); ?></h6>
                                <h2 class="my-2"><?php echo $diskFreeGB; ?> <span class="fs-6">GB</span></h2>
                                <small><?php echo __('stat_disk_total'); ?>: <?php echo $diskTotalGB; ?> GB</small>
                            </div>
                            <span class="fs-1">💿</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 連線資訊 -->
            <div class="col-md-3">
                <div class="card stat-card text-white bg-secondary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><?php echo __('stat_connection'); ?></h6>
                                <?php if ($source === 'db'): ?>
                                    <p class="my-2 fw-bold">Host: <?php echo htmlspecialchars($dbHost); ?></p>
                                    <small><?php echo sprintf(__('stat_type'), $dbType); ?></small><br>
                                    <small title="<?php echo htmlspecialchars($serverInfo); ?>"><?php echo sprintf(__('stat_ver'), htmlspecialchars($serverVersion)); ?></small><br>
                                    <small><?php echo sprintf(__('stat_driver'), 'PDO MySQL'); ?></small>
                                <?php elseif ($source === 'sqlite'): ?>
                                    <p class="my-2 fw-bold" style="font-size: 0.9em;"><?php echo htmlspecialchars($dbName); ?></p>
                                    <small><?php echo sprintf(__('stat_type'), $dbType); ?></small><br>
                                    <small><?php echo sprintf(__('stat_ver'), htmlspecialchars($serverVersion)); ?></small><br>
                                    <small><?php echo sprintf(__('stat_driver'), 'PDO SQLite'); ?></small>
                                <?php else: ?>
                                    <p class="my-2 fw-bold"><?php echo __('stat_mode_file'); ?></p>
                                    <small><?php echo __('stat_path'); ?></small><br>
                                    <small><?php echo __('stat_log'); ?></small>
                                <?php endif; ?>
                                <hr class="my-1 opacity-25">
                                <small>相簿服務: <span class="badge <?php echo $albumStatus['status'] ? 'bg-success' : 'bg-danger'; ?>"><?php echo $albumStatus['status'] ? 'ON' : 'OFF'; ?></span></small>
                                <div style="font-size: 0.7rem;" class="text-truncate" title="<?php echo htmlspecialchars($albumStatus['message']); ?>"><?php echo htmlspecialchars($albumStatus['message']); ?></div>
                            </div>
                            <span class="fs-1">🔌</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 最新文章列表 -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><?php echo __('recent_posts_title'); ?></h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col"><?php echo __('col_date'); ?></th>
                            <th scope="col"><?php echo __('col_title'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPosts as $post): ?>
                        <tr>
                            <td><?php echo substr($post['post_date'], 0, 10); ?></td>
                            <td>
                                <?php echo htmlspecialchars($post['post_title']); ?>
                                <?php if (isset($post['status']) && $post['status'] === 'draft'): ?>
                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 0.75em;"><?php echo __('stat_drafts'); ?></span>
                                <?php else: ?>
                                    <?php 
                                    // 檢查實體檔是否存在 (僅限非草稿)
                                    $staticPath = __DIR__ . '/../post/' . $post['post_filename'];
                                    if (!file_exists($staticPath)) {
                                        echo '<span class="badge bg-danger ms-1" style="font-size: 0.75em;">' . __('badge_no_static') . '</span>';
                                    }
                                    ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentPosts)): ?>
                        <tr><td colspan="2" class="text-center text-muted"><?php echo __('no_posts'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white text-end">
                <a href="posts.php" class="btn btn-sm btn-outline-primary"><?php echo __('view_all_posts'); ?> &rarr;</a>
            </div>
        </div>
    </div>
</div>

<?php require 'common_js_inc.php'; ?>
</body>
</html>
