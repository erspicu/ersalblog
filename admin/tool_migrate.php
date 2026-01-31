<?php
require_once 'auth.php';
require_once 'data_provider.php';
require_once 'health_check.php';

requireLogin();

$dataManager = new DataManager();

// Only allow in File Mode (or strict restriction as per request)
// The request said: "登入檔案模式後...增加一個功能分類...將檔案內容匯入到資料庫"
// So we restrict to File Mode or allow both? The prompt implies File Mode specifically.
if ($dataManager->getSource() !== 'file') {
    // Optional: Redirect or just show message.
    // But let's allow accessing it, just warning that source is DB?
    // Actually, if I am in DB mode, I am seeing DB data. "Importing from File" is also valid.
    // But let's stick to the prompt: "In File Mode".
}

// Check DB connection first
$dbStatus = SystemHealth::checkDB();
$dbReady = $dbStatus['status'];

// Start Migration Flag
$startMigration = isset($_POST['start_migration']);

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料遷移 - Blog Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar a { color: #cfd2d6; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
        .main-content { padding: 20px; }
        /* Log Styles */
        .log-container { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; height: 400px; overflow-y: auto; font-family: 'Consolas', monospace; font-size: 14px; line-height: 1.5; border: 1px solid #444; }
        .log-item { padding: 5px 10px; margin-bottom: 5px; background: #2d2d2d; border-radius: 3px; display: flex; align-items: center; }
        .log-item .icon { margin-right: 12px; min-width: 25px; text-align: center; }
        .progress-bar-container { width: 100%; background-color: #7f8c8d; border-radius: 4px; overflow: hidden; height: 25px; margin-bottom: 20px; }
        .progress-fill { height: 100%; background-color: #2ecc71; width: 0%; transition: width 0.3s; text-align: center; color: white; font-weight: bold; font-size: 14px; line-height: 25px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); }
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
            <li class="nav-item"><a href="index.php">📊 儀表板</a></li>
            <li class="nav-item"><a href="posts.php">📝 文章管理</a></li>
            <li class="nav-item"><a href="categories.php">📂 分類管理</a></li>
            <?php if ($dataManager->getSource() === 'file'): ?>
            <li class="nav-item"><a href="tool_migrate.php" class="active">🔄 資料匯入</a></li>
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
            <h2>🔄 資料匯入 (File to DB)</h2>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">說明</h5>
                <p class="card-text">
                    此功能會讀取目前的「檔案系統」內容 (contents, category)，並將其解析後寫入至設定的 MySQL 資料庫中。<br>
                    <span class="text-danger">注意：若資料庫中已有同檔名的文章，將會進行更新 (Update)；若無則新增 (Insert)。</span>
                </p>
                
                <div class="alert <?php echo $dbReady ? 'alert-success' : 'alert-danger'; ?>">
                    <strong>目標資料庫狀態:</strong> <?php echo $dbStatus['message']; ?>
                </div>

                <?php if ($dbReady && !$startMigration): ?>
                    <form method="POST" onsubmit="return confirm('確定要開始匯入嗎？這可能需要一點時間。');">
                        <input type="hidden" name="start_migration" value="1">
                        <button type="submit" class="btn btn-primary">🚀 開始匯入資料</button>
                    </form>
                <?php elseif (!$dbReady): ?>
                    <button class="btn btn-secondary" disabled>無法連線至資料庫</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($startMigration): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">執行日誌</div>
                <div class="card-body bg-dark">
                    <div class="progress-bar-container">
                        <div id="p-bar" class="progress-fill">0%</div>
                    </div>
                    <div class="log-container">
                        <?php 
                            // 執行匯入邏輯
                            runMigration($dbConfig); 
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// ==========================================
// Migration Logic (Ported from migrate_full.php)
// ==========================================
function output_log($msg, $type = 'info') {
    $colors = [
        'success' => '#2ecc71',
        'error'   => '#e74c3c',
        'warning' => '#f39c12',
        'system'  => '#3498db',
        'default' => '#bdc3c7'
    ];
    $color = $colors[$type] ?? $colors['default'];

    $icons = [
        'success' => '✅',
        'error'   => '❌',
        'warning' => '⚠️',
        'system'  => '⚙️',
        'default' => '📝'
    ];
    $icon = $icons[$type] ?? $icons['default'];
    
    echo "<div class='log-item' style='border-left: 4px solid $color;'>
            <span class='icon'>$icon</span>
            <span class='msg'>$msg</span>
          </div>";
    
    echo "<script>
        var container = document.querySelector('.log-container');
        if(container) container.scrollTop = container.scrollHeight;
    </script>";
    
    flush();
    if (ob_get_level() > 0) ob_flush();
}

function runMigration($dbConfig) {
    // 設置環境
    set_time_limit(600);
    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', 1);
    @ini_set('zlib.output_compression', 0);
    @ini_set('implicit_flush', 1);
    while (ob_get_level() > 0) ob_end_flush();
    ob_implicit_flush(1);

    $paths = [
        'index_file'   => dirname(__DIR__) . '/contents/index_post.txt',
        'category_dir' => dirname(__DIR__) . '/category',
        'content_dirs' => [
            dirname(__DIR__) . '/contents/post_files',
            dirname(__DIR__)
        ]
    ];

    try {
        output_log("正在連線到資料庫...", 'system');
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        output_log("連線成功!", 'success');

        // Check Table (New Schema)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_posts` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `post_filename` VARCHAR(190) NOT NULL COMMENT '唯一檔名',
            `post_title` VARCHAR(255) NOT NULL,
            `post_date` DATETIME NOT NULL,
            `post_tags` TEXT DEFAULT NULL,
            `post_description` TEXT DEFAULT NULL,
            `post_content` LONGTEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_filename` (`post_filename`),
            INDEX `idx_post_date` (`post_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_categories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `category_name` VARCHAR(190) NOT NULL UNIQUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_post_categories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `post_id` INT UNSIGNED NOT NULL,
            `category_id` INT UNSIGNED NOT NULL,
            FOREIGN KEY (`post_id`) REFERENCES `blog_posts`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`category_id`) REFERENCES `blog_categories`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_post_cat` (`post_id`, `category_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Read File Data
        if (!file_exists($paths['index_file'])) {
            output_log("找不到索引檔: " . $paths['index_file'], 'error');
            return;
        }

        $lines = file($paths['index_file'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $total_lines = count($lines);
        $success_count = 0;

        $stmt = $pdo->prepare("
            INSERT INTO blog_posts 
            (post_filename, post_title, post_date, post_tags, post_description, post_content, updated_at) 
            VALUES 
            (:filename, :title, :date, :tags, :desc, :content, NOW())
            ON DUPLICATE KEY UPDATE 
            post_title = VALUES(post_title),
            post_date = VALUES(post_date),
            post_tags = VALUES(post_tags),
            post_description = VALUES(post_description),
            post_content = VALUES(post_content),
            updated_at = NOW()
        ");

        output_log("開始處理 $total_lines 篇文章...", 'system');

        foreach ($lines as $index => $line) {
            $parts = explode('|', $line);
            if (count($parts) < 3) continue;

            $post_date = trim($parts[0]);
            $post_filename = trim($parts[1]);
            $post_title = trim($parts[2]);
            $post_tags = isset($parts[3]) ? trim($parts[3]) : '';
            $post_desc = isset($parts[4]) ? trim($parts[4]) : '';

            // Get Categories from File System
            $cats = [];
            if (is_dir($paths['category_dir'])) {
                $dirs = glob($paths['category_dir'] . '/*', GLOB_ONLYDIR);
                foreach ($dirs as $dir) {
                    $cName = basename($dir);
                    $target = $dir . '/' . $post_filename;
                    $targetNoExt = $dir . '/' . pathinfo($post_filename, PATHINFO_FILENAME);
                    if (file_exists($target) || file_exists($targetNoExt)) {
                        $cats[] = $cName;
                    }
                }
            }

            // Get Content
            $content = '';
            foreach ($paths['content_dirs'] as $d) {
                if (file_exists($d . '/' . $post_filename)) {
                    $content = file_get_contents($d . '/' . $post_filename);
                    break;
                }
            }

            try {
                $pdo->beginTransaction();

                // 1. Insert/Update Post
                $stmt->execute([
                    ':filename' => $post_filename,
                    ':title'    => $post_title,
                    ':date'     => $post_date,
                    ':tags'     => $post_tags,
                    ':desc'     => $post_desc,
                    ':content'  => $content
                ]);
                
                // Get Post ID
                $checkId = $pdo->prepare("SELECT id FROM blog_posts WHERE post_filename = ?");
                $checkId->execute([$post_filename]);
                $postId = $checkId->fetchColumn();

                // 2. Sync Categories
                // First clear old
                $delPivot = $pdo->prepare("DELETE FROM blog_post_categories WHERE post_id = ?");
                $delPivot->execute([$postId]);

                foreach ($cats as $catName) {
                    $catName = trim($catName);
                    if ($catName === '') continue;

                    // Get/Create Cat ID
                    $checkCat = $pdo->prepare("SELECT id FROM blog_categories WHERE category_name = ?");
                    $checkCat->execute([$catName]);
                    $catId = $checkCat->fetchColumn();

                    if (!$catId) {
                        $insCat = $pdo->prepare("INSERT INTO blog_categories (category_name) VALUES (?)");
                        $insCat->execute([$catName]);
                        $catId = $pdo->lastInsertId();
                    }

                    // Insert Relation
                    $insRel = $pdo->prepare("INSERT INTO blog_post_categories (post_id, category_id) VALUES (?, ?)");
                    $insRel->execute([$postId, $catId]);
                }

                $pdo->commit();
                output_log("已匯入: $post_title", 'success');
                $success_count++;

            } catch (Exception $e) {
                $pdo->rollBack();
                output_log("失敗 [$post_filename]: " . $e->getMessage(), 'error');
            }

            // Update Progress
            $percent = round((($index + 1) / $total_lines) * 100);
            echo "<script>
                var pbar = document.getElementById('p-bar');
                if(pbar) {
                    pbar.style.width = '$percent%';
                    pbar.innerText = '$percent%';
                }
            </script>";
            flush();
            if (ob_get_level() > 0) ob_flush();
        }

        output_log("匯入完成! 成功: $success_count", 'system');

    } catch (Exception $e) {
        output_log("系統錯誤: " . $e->getMessage(), 'error');
    }
}
?>