<?php
// admin/db_init.php
session_start();
require_once __DIR__ . '/../config.php';

$error = '';
$success = '';
$step = isset($_SESSION['db_init_authorized']) ? 'options' : 'login';

// --- Functions ---

function checkDBConnection() {
    global $dbConfig;
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        return new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (Exception $e) {
        return false;
    }
}

function hasFileData() {
    $indexFile = __DIR__ . '/../contents/index_post.txt';
    return file_exists($indexFile) && filesize($indexFile) > 0;
}

function initDatabase($pdo, $importFiles = false) {
    global $dbConfig;
    
    // 1. Create Tables (DDL)
    // Execute separately to ensure driver compatibility and avoid implicit commit issues mixed with transactions
    $queries = [
        "CREATE TABLE IF NOT EXISTS `blog_posts` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `post_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `post_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `post_date` datetime NOT NULL,
          `post_content` longtext COLLATE utf8mb4_unicode_ci,
          `post_tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `post_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'published',
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `post_filename` (`post_filename`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `blog_categories` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `category_name` (`category_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `blog_post_categories` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `post_id` int(11) NOT NULL,
          `category_id` int(11) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `post_id` (`post_id`),
          KEY `category_id` (`category_id`),
          CONSTRAINT `fk_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($queries as $sql) {
        $pdo->exec($sql);
    }

    // 2. Insert Data (DML)
    try {
        if (!$pdo->beginTransaction()) {
            throw new Exception("無法啟動資料庫事務 (Transaction)");
        }

        if ($importFiles && hasFileData()) {
            // Import from File System
            require_once 'data_provider.php'; 
            
            $baseDir = dirname(__DIR__);
            $lines = file($baseDir . '/contents/index_post.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                $parts = explode('|', $line);
                if (count($parts) < 3) continue;

                // Prepare Data
                $date = trim($parts[0]);
                $filename = trim($parts[1]);
                $title = trim($parts[2]);
                $tags = isset($parts[3]) ? trim($parts[3]) : '';
                $desc = isset($parts[4]) ? trim($parts[4]) : '';
                
                $contentPath = $baseDir . '/contents/post_files/' . $filename;
                $content = file_exists($contentPath) ? file_get_contents($contentPath) : '';

                // Insert Post
                $stmt = $pdo->prepare("INSERT IGNORE INTO blog_posts (post_title, post_filename, post_date, post_content, post_tags, post_description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$title, $filename, $date, $content, $tags, $desc]);
                
                if ($stmt->rowCount() > 0) {
                     $postId = $pdo->lastInsertId();
                } else {
                     $stmtGetId = $pdo->prepare("SELECT id FROM blog_posts WHERE post_filename = ?");
                     $stmtGetId->execute([$filename]);
                     $postId = $stmtGetId->fetchColumn();
                }

                if (!$postId) continue;

                // Process Categories
                $catDir = $baseDir . '/category';
                if (is_dir($catDir)) {
                    $cats = scandir($catDir);
                    foreach ($cats as $cat) {
                        if ($cat === '.' || $cat === '..') continue;
                        if (file_exists($catDir . '/' . $cat . '/' . $filename)) {
                            // Category
                            $stmtCat = $pdo->prepare("INSERT IGNORE INTO blog_categories (category_name) VALUES (?)");
                            $stmtCat->execute([$cat]);
                            
                            $stmtGetCat = $pdo->prepare("SELECT id FROM blog_categories WHERE category_name = ?");
                            $stmtGetCat->execute([$cat]);
                            $catId = $stmtGetCat->fetchColumn();

                            if ($catId) {
                                // Pivot
                                $stmtPivot = $pdo->prepare("INSERT IGNORE INTO blog_post_categories (post_id, category_id) VALUES (?, ?)");
                                $stmtPivot->execute([$postId, $catId]);
                            }
                        }
                    }
                }
            }
        } else {
            // Insert Sample Data
            $pdo->exec("INSERT INTO blog_categories (category_name) VALUES ('未分類')");
            $catId = $pdo->lastInsertId();

            $sampleTitle = "歡迎使用 BaxerMux Blog";
            $sampleFile = "hello-world.html";
            $sampleDate = date("Y-m-d H:i:s");
            $sampleContent = "<p>這是您的第一篇文章。您可以開始編輯或刪除它。</p><!--more--><p>這是更多內容。</p>";
            
            $stmt = $pdo->prepare("INSERT INTO blog_posts (post_title, post_filename, post_date, post_content, post_tags, post_description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$sampleTitle, $sampleFile, $sampleDate, $sampleContent, "Hello,World", "這是範例文章"]);
            $postId = $pdo->lastInsertId();

            $pdo->exec("INSERT INTO blog_post_categories (post_id, category_id) VALUES ($postId, $catId)");
        }

        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

// --- Logic Handling ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login_check'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username === $adminConfig['username'] && $password === $adminConfig['password']) {
            $_SESSION['db_init_authorized'] = true;
            $step = 'options';
        } else {
            $error = "管理員驗證失敗";
        }
    } elseif (isset($_POST['do_init'])) {
        if (!isset($_SESSION['db_init_authorized'])) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        $mode = $_POST['init_mode']; // 'import' or 'clean'
        $pdo = checkDBConnection();
        
        if ($pdo) {
            try {
                initDatabase($pdo, ($mode === 'import'));
                $success = "資料庫初始化成功！";
                $step = 'complete';
            } catch (Exception $e) {
                $error = "初始化失敗: " . $e->getMessage();
            }
        } else {
            $error = "無法連線至資料庫";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料庫初始化 - BaxerMux Blog</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { width: 100%; max-width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">資料庫初始化精靈</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($step === 'login'): ?>
            <p>檢測到您已設定資料庫連線，但尚未初始化資料表。</p>
            <p>請先輸入後台管理帳號密碼以繼續：</p>
            <form method="POST">
                <input type="hidden" name="login_check" value="1">
                <div class="mb-3">
                    <label class="form-label">帳號</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">密碼</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">驗證身分</button>
            </form>

        <?php elseif ($step === 'options'): ?>
            <h5 class="card-title">請選擇初始化方式</h5>
            
            <?php if (hasFileData()): ?>
                <div class="alert alert-info">
                    <strong>發現現有的檔案資料！</strong><br>
                    系統檢測到此網站已有運作中的檔案版部落格資料。
                </div>
                
                <form method="POST" class="d-grid gap-2">
                    <input type="hidden" name="do_init" value="1">
                    
                    <button type="submit" name="init_mode" value="import" class="btn btn-success btn-lg">
                        📥 匯入現有檔案並初始化
                        <div class="fs-6 fw-normal">將現有文章與分類匯入資料庫</div>
                    </button>
                    
                    <button type="submit" name="init_mode" value="clean" class="btn btn-outline-secondary">
                        🆕 全新安裝 (僅建立範例資料)
                        <div class="fs-6 fw-normal">忽略現有檔案，重新開始</div>
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    <strong>未發現現有資料</strong><br>
                    系統將為您建立資料庫架構並寫入一筆範例資料。
                </div>
                 <form method="POST" class="d-grid gap-2">
                    <input type="hidden" name="do_init" value="1">
                    <button type="submit" name="init_mode" value="clean" class="btn btn-primary btn-lg">
                        🚀 開始初始化資料庫
                    </button>
                </form>
            <?php endif; ?>

        <?php elseif ($step === 'complete'): ?>
            <div class="text-center">
                <div class="text-success display-1 mb-3">✅</div>
                <h4><?php echo $success; ?></h4>
                <p class="text-muted">您可以開始使用資料庫版後台了。</p>
                <a href="index.php" class="btn btn-primary w-100 mt-3">進入後台管理</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
