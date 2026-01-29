<?php
require_once 'auth.php';
requireLogin();

// 收集系統資訊
$phpVersion = phpversion();
$dbHost = $dbConfig['host'];
$dbName = $dbConfig['dbname'];

// 1. 文章總數
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts");
    $postCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $postCount = "Error";
}

// 2. DB 大小 (MB) - 針對整個資料庫
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

// 3. 磁碟空間 (GB)
$diskFree = @disk_free_space(".");
$diskTotal = @disk_total_space(".");
$diskFreeGB = $diskFree ? round($diskFree / 1024 / 1024 / 1024, 2) : 'N/A';
$diskTotalGB = $diskTotal ? round($diskTotal / 1024 / 1024 / 1024, 2) : 'N/A';

// 4. 最新文章 (取前 5 筆)
$stmt = $pdo->query("SELECT post_title, post_date FROM blog_posts ORDER BY post_date DESC LIMIT 5");
$recentPosts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog 後台管理</title>
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
            <span class="fs-4">Blog Admin</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="index.php" class="active">
                    📊 儀表板
                </a>
            </li>
            <li>
                <a href="posts.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>歡迎回來，<?php echo htmlspecialchars($_SESSION['admin_user']); ?>！</h2>
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
                                <h6 class="card-title mb-0">文章總數</h6>
                                <h2 class="my-2"><?php echo $postCount; ?></h2>
                                <small>篇已發布文章</small>
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
                                <h6 class="card-title mb-0">資料庫大小</h6>
                                <h2 class="my-2"><?php echo $dbSize; ?> <span class="fs-6">MB</span></h2>
                                <small>DB: <?php echo htmlspecialchars($dbName); ?></small>
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
                                <h6 class="card-title mb-0">磁碟剩餘空間</h6>
                                <h2 class="my-2"><?php echo $diskFreeGB; ?> <span class="fs-6">GB</span></h2>
                                <small>總容量: <?php echo $diskTotalGB; ?> GB</small>
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
                                <h6 class="card-title mb-0">連線資訊</h6>
                                <p class="my-2 fw-bold">Host: <?php echo htmlspecialchars($dbHost); ?></p>
                                <small>Driver: PDO MySQL</small>
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
                <h5 class="mb-0">🚀 最新發布文章</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">發布日期</th>
                            <th scope="col">標題</th>
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
                        <tr><td colspan="2" class="text-center text-muted">目前沒有文章</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white text-end">
                <a href="posts.php" class="btn btn-sm btn-outline-primary">管理所有文章 &rarr;</a>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>