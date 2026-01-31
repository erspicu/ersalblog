<?php
require_once 'auth.php';
require_once 'data_provider.php'; // Include DataManager
requireLogin();

$dataManager = new DataManager();
$source = $dataManager->getSource(); // 'db' or 'file'

// 收集系統資訊
$phpVersion = phpversion();

// 1. 文章總數
$postCount = $dataManager->getPostCount();

// 2. DB 大小 & 連線資訊 (Only for DB mode)
$dbSize = 'N/A';
$dbHost = 'N/A';
$serverVersion = 'N/A';
$serverInfo = '';
$dbType = 'File System';

if ($source === 'db') {
    $dbHost = $dbConfig['host'];
    $dbName = $dbConfig['dbname'];
    
    // DB Size
    try {
        $stmt = $pdo->prepare("SELECT round(SUM(data_length + index_length) / 1024 / 1024, 2) 
                               FROM information_schema.TABLES 
                               WHERE table_schema = ?");
        $stmt->execute([$dbName]);
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
}

// 3. 磁碟空間 (GB)
$diskFree = @disk_free_space(".");
$diskTotal = @disk_total_space(".");
$diskFreeGB = $diskFree ? round($diskFree / 1024 / 1024 / 1024, 2) : 'N/A';
$diskTotalGB = $diskTotal ? round($diskTotal / 1024 / 1024 / 1024, 2) : 'N/A';

// 4. 最新文章
$recentPosts = $dataManager->getRecentPosts(5);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLang ?? 'zh_TW'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login_title'); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar a { color: #cfd2d6; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
        .main-content { padding: 20px; }
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
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
            <span class="badge <?php echo ($source === 'db') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                <?php echo __('mode_label'); ?>: <?php echo ($source === 'db') ? __('mode_db_short') : __('mode_file_short'); ?>
            </span>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="index.php" class="active">
                    <?php echo __('nav_dashboard'); ?>
                </a>
            </li>
            <li>
                <a href="posts.php">
                    <?php echo __('nav_posts'); ?>
                </a>
            </li>
            <li>
                <a href="categories.php">
                    <?php echo __('nav_categories'); ?>
                </a>
            </li>
            <?php if ($source === 'file'): ?>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?php echo __('welcome_msg'); ?>，<?php echo htmlspecialchars($_SESSION['admin_user']); ?>！</h2>
            <span class="badge bg-secondary">PHP v<?php echo $phpVersion; ?></span>
        </div>
        
        <!-- 數據卡片列 -->
        <div class="row g-3 mb-4">
            <!-- 文章總數 -->
            <div class="col-md-3">
                <div class="card stat-card text-white bg-primary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0"><?php echo __('stat_posts'); ?></h6>
                                <h2 class="my-2"><?php echo $postCount; ?></h2>
                                <small><?php echo __('stat_posts_sub'); ?></small>
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
                                <h2 class="my-2"><?php echo $dbSize; ?> <span class="fs-6"><?php echo ($source === 'db') ? 'MB' : ''; ?></span></h2>
                                <small><?php echo ($source === 'db') ? 'DB: '.htmlspecialchars($dbName) : __('not_applicable'); ?></small>
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
                                    <small>Type: <?php echo $dbType; ?></small><br>
                                    <small title="<?php echo htmlspecialchars($serverInfo); ?>">Ver: <?php echo htmlspecialchars($serverVersion); ?></small><br>
                                    <small>Driver: PDO MySQL</small>
                                <?php else: ?>
                                    <p class="my-2 fw-bold">Mode: File System</p>
                                    <small>Path: contents/</small><br>
                                    <small>Log: index_post.txt</small>
                                <?php endif; ?>
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
                            <td><?php echo $post['post_date']; ?></td>
                            <td><?php echo htmlspecialchars($post['post_title']); ?></td>
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