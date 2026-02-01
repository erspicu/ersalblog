<?php
// admin/sqlite_init.php
session_start();
require_once __DIR__ . '/../config.php';

$error = '';
$success = '';
$step = isset($_SESSION['sqlite_init_authorized']) ? 'options' : 'login';

// --- Functions ---

function getSQLitePath() {
    global $sqlite_path;
    if (!isset($sqlite_path) || empty($sqlite_path)) return false;
    return __DIR__ . '/../' . $sqlite_path;
}

function checkSQLiteConnection() {
    $target = getSQLitePath();
    if (!$target) return false;
    
    // Attempt to create/open
    try {
        return new PDO("sqlite:" . $target, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (Exception $e) {
        return false;
    }
}

function checkMySQLConnection() {
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

function hasMySQLData() {
    $pdo = checkMySQLConnection();
    if (!$pdo) return false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'blog_posts'");
        if ($stmt->rowCount() > 0) {
            return $pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn() > 0;
        }
    } catch (Exception $e) {}
    return false;
}

function initSQLiteDatabase($pdo, $mode) {
    // 1. Create Tables
    $queries = [
        "CREATE TABLE IF NOT EXISTS blog_posts (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          post_title TEXT NOT NULL,
          post_filename TEXT NOT NULL UNIQUE,
          post_date TEXT NOT NULL,
          post_content TEXT,
          post_tags TEXT,
          post_description TEXT,
          created_at TEXT DEFAULT CURRENT_TIMESTAMP,
          updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS blog_categories (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          category_name TEXT NOT NULL UNIQUE
        )",
        "CREATE TABLE IF NOT EXISTS blog_post_categories (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          post_id INTEGER NOT NULL,
          category_id INTEGER NOT NULL,
          FOREIGN KEY(post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
          FOREIGN KEY(category_id) REFERENCES blog_categories(id) ON DELETE CASCADE
        )"
    ];

    foreach ($queries as $sql) {
        $pdo->exec($sql);
    }

    // 2. Import Data
    try {
        $pdo->beginTransaction();

        if ($mode === 'import_file' && hasFileData()) {
            // Import from File
            $baseDir = dirname(__DIR__);
            $lines = file($baseDir . '/contents/index_post.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                $parts = explode('|', $line);
                if (count($parts) < 3) continue;

                $date = trim($parts[0]);
                $filename = trim($parts[1]);
                $title = trim($parts[2]);
                $tags = isset($parts[3]) ? trim($parts[3]) : '';
                $desc = isset($parts[4]) ? trim($parts[4]) : '';
                
                $contentPath = $baseDir . '/contents/post_files/' . $filename;
                $content = file_exists($contentPath) ? file_get_contents($contentPath) : '';

                // Insert Post
                $stmt = $pdo->prepare("INSERT OR IGNORE INTO blog_posts (post_title, post_filename, post_date, post_content, post_tags, post_description) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $filename, $date, $content, $tags, $desc]);
                
                // Get ID
                $stmtGetId = $pdo->prepare("SELECT id FROM blog_posts WHERE post_filename = ?");
                $stmtGetId->execute([$filename]);
                $postId = $stmtGetId->fetchColumn();

                if (!$postId) continue;

                // Categories (from Folder structure)
                $catDir = $baseDir . '/category';
                if (is_dir($catDir)) {
                    $cats = scandir($catDir);
                    foreach ($cats as $cat) {
                        if ($cat === '.' || $cat === '..') continue;
                        if (file_exists($catDir . '/' . $cat . '/' . $filename)) {
                            // Category
                            $stmtCat = $pdo->prepare("INSERT OR IGNORE INTO blog_categories (category_name) VALUES (?)");
                            $stmtCat->execute([$cat]);
                            
                            $stmtGetCat = $pdo->prepare("SELECT id FROM blog_categories WHERE category_name = ?");
                            $stmtGetCat->execute([$cat]);
                            $catId = $stmtGetCat->fetchColumn();

                            if ($catId) {
                                $stmtPivot = $pdo->prepare("INSERT OR IGNORE INTO blog_post_categories (post_id, category_id) VALUES (?, ?)");
                                $stmtPivot->execute([$postId, $catId]);
                            }
                        }
                    }
                }
            }
        } 
        elseif ($mode === 'import_mysql' && ($mysqlPdo = checkMySQLConnection())) {
            // Import from MySQL
            // Posts
            $posts = $mysqlPdo->query("SELECT * FROM blog_posts")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($posts as $post) {
                $stmt = $pdo->prepare("INSERT OR IGNORE INTO blog_posts (id, post_title, post_filename, post_date, post_content, post_tags, post_description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $post['id'], $post['post_title'], $post['post_filename'], $post['post_date'], 
                    $post['post_content'], $post['post_tags'], $post['post_description'], 
                    $post['created_at'], $post['updated_at']
                ]);
            }

            // Categories
            $cats = $mysqlPdo->query("SELECT * FROM blog_categories")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cats as $cat) {
                $stmt = $pdo->prepare("INSERT OR IGNORE INTO blog_categories (id, category_name) VALUES (?, ?)");
                $stmt->execute([$cat['id'], $cat['category_name']]);
            }

            // Pivot
            $pivots = $mysqlPdo->query("SELECT * FROM blog_post_categories")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($pivots as $pivot) {
                $stmt = $pdo->prepare("INSERT OR IGNORE INTO blog_post_categories (id, post_id, category_id) VALUES (?, ?, ?)");
                $stmt->execute([$pivot['id'], $pivot['post_id'], $pivot['category_id']]);
            }
            
            // Reset AutoIncrement sequence for SQLite
            // (Optional but good practice)
        }
        else {
            // Clean Install (Sample Data)
            $pdo->exec("INSERT INTO blog_categories (category_name) VALUES ('未分類')");
            $catId = $pdo->lastInsertId();

            $sampleTitle = "歡迎使用 BaxerMux Blog (SQLite版)";
            $sampleFile = "hello-sqlite.html";
            $sampleDate = date("Y-m-d H:i:s");
            $sampleContent = "<p>這是 SQLite 版本的範例文章。</p>";
            
            $stmt = $pdo->prepare("INSERT INTO blog_posts (post_title, post_filename, post_date, post_content, post_tags, post_description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$sampleTitle, $sampleFile, $sampleDate, $sampleContent, "SQLite", "測試"]);
            $postId = $pdo->lastInsertId();

            $pdo->exec("INSERT INTO blog_post_categories (post_id, category_id) VALUES ($postId, $catId)");
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// --- Logic ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login_check'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username === $adminConfig['username'] && $password === $adminConfig['password']) {
            $_SESSION['sqlite_init_authorized'] = true;
            $step = 'options';
        } else {
            $error = "管理員驗證失敗";
        }
    } elseif (isset($_POST['do_init'])) {
        if (!isset($_SESSION['sqlite_init_authorized'])) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        $mode = $_POST['init_mode'];
        $pdo = checkSQLiteConnection();
        
        if ($pdo) {
            try {
                initSQLiteDatabase($pdo, $mode);
                $success = "SQLite 資料庫初始化成功！";
                $step = 'complete';
            } catch (Exception $e) {
                $error = "初始化失敗: " . $e->getMessage();
            }
        } else {
            $error = "無法建立 SQLite 連線，請檢查路徑權限。";
        }
    }
}

// Check Available Sources
$canImportFile = hasFileData();
$canImportMySQL = hasMySQLData();

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQLite 初始化 - BaxerMux Blog</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { width: 100%; max-width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">SQLite 資料庫初始化</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($step === 'login'): ?>
            <p>您已啟用 SQLite 模式，但資料庫檔案尚未建立或初始化。</p>
            <p>請輸入後台管理密碼以繼續：</p>
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
            <h5 class="card-title">初始化選項</h5>
            
            <form method="POST" class="d-grid gap-2">
                <input type="hidden" name="do_init" value="1">
                
                <?php if ($canImportFile): ?>
                    <button type="submit" name="init_mode" value="import_file" class="btn btn-info text-white">
                        📂 從檔案系統匯入
                        <div class="fs-6 fw-normal">將現有檔案文章轉入 SQLite</div>
                    </button>
                <?php endif; ?>

                <?php if ($canImportMySQL): ?>
                    <button type="submit" name="init_mode" value="import_mysql" class="btn btn-warning text-dark">
                        🐬 從 MySQL 資料庫匯入
                        <div class="fs-6 fw-normal">複製 MySQL 資料到 SQLite</div>
                    </button>
                <?php endif; ?>
                
                <button type="submit" name="init_mode" value="clean" class="btn btn-outline-secondary">
                    🆕 全新安裝
                    <div class="fs-6 fw-normal">建立空的資料庫</div>
                </button>
            </form>

        <?php elseif ($step === 'complete'): ?>
            <div class="text-center">
                <div class="text-success display-1 mb-3">✅</div>
                <h4><?php echo $success; ?></h4>
                <a href="index.php" class="btn btn-primary w-100 mt-3">進入後台管理</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>