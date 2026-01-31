<?php
// admin/file_init.php
session_start();
require_once __DIR__ . '/../config.php';

$error = '';
$success = '';
$step = isset($_SESSION['file_init_authorized']) ? 'options' : 'login';

// --- Functions ---

function getDBConnection() {
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

function hasDBData($pdo) {
    if (!$pdo) return false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'blog_posts'");
        if ($stmt->rowCount() == 0) return false;
        
        $count = $pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
        return $count > 0;
    } catch (Exception $e) {
        return false;
    }
}

function initFileSystem($pdo = null, $importDB = false) {
    $baseDir = dirname(__DIR__);
    $dirs = [
        $baseDir . '/contents',
        $baseDir . '/contents/post_files',
        $baseDir . '/category',
        $baseDir . '/preview',
        $baseDir . '/static'
    ];

    // 1. Create Directories
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new Exception("無法建立目錄: " . $dir);
            }
        }
    }

    // 2. Import from DB or Create Sample
    if ($importDB && $pdo) {
        // --- Export from DB ---
        
        // Fetch All Posts
        $sql = "SELECT p.*, GROUP_CONCAT(c.category_name SEPARATOR ',') as cats 
                FROM blog_posts p
                LEFT JOIN blog_post_categories pc ON p.id = pc.post_id
                LEFT JOIN blog_categories c ON pc.category_id = c.id
                GROUP BY p.id
                ORDER BY p.post_date DESC";
        $stmt = $pdo->query($sql);
        $posts = $stmt->fetchAll();

        $indexLines = [];

        foreach ($posts as $post) {
            $filename = $post['post_filename'];
            
            // Write Content File
            file_put_contents($baseDir . '/contents/post_files/' . $filename, $post['post_content']);
            
            // Prepare Index Line
            // Format: Date|Filename|Title|Tags|Description
            $line = implode('|', [
                $post['post_date'],
                $filename,
                $post['post_title'],
                $post['post_tags'],
                $post['post_description']
            ]);
            $indexLines[] = $line;

            // Handle Categories
            $cats = explode(',', $post['cats'] ?? '');
            foreach ($cats as $catName) {
                $catName = trim($catName);
                if ($catName === '') continue;
                
                $catDir = $baseDir . '/category/' . $catName;
                if (!is_dir($catDir)) mkdir($catDir, 0755, true);
                
                // Create empty file link
                touch($catDir . '/' . $filename);
            }
        }

        // Write Index File
        file_put_contents($baseDir . '/contents/index_post.txt', implode("\n", $indexLines));

    } else {
        // --- Create Sample Data ---
        
        // Sample Content
        $sampleFilename = 'hello-world.html';
        $sampleContent = "<p>這是您的第一篇文章 (檔案模式)。</p><!--more--><p>這是更多內容。</p>";
        file_put_contents($baseDir . '/contents/post_files/' . $sampleFilename, $sampleContent);
        
        // Sample Index
        $date = date("Y-m-d H:i:s");
        $line = "$date|$sampleFilename|歡迎使用 BaxerMux Blog (檔案版)|Hello,World|這是檔案模式的範例文章";
        file_put_contents($baseDir . '/contents/index_post.txt', $line);

        // Sample Category
        $catDir = $baseDir . '/category/未分類';
        if (!is_dir($catDir)) mkdir($catDir, 0755, true);
        touch($catDir . '/' . $sampleFilename);
    }

    // Create Readme files if missing (Optional but good)
    if (!file_exists($baseDir . '/contents/readme.txt')) file_put_contents($baseDir . '/contents/readme.txt', "Blog Contents Root");
    
    return true;
}

// --- Logic ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login_check'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username === $adminConfig['username'] && $password === $adminConfig['password']) {
            $_SESSION['file_init_authorized'] = true;
            $step = 'options';
        } else {
            $error = "管理員驗證失敗";
        }
    } elseif (isset($_POST['do_init'])) {
        if (!isset($_SESSION['file_init_authorized'])) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        $mode = $_POST['init_mode']; // 'import' or 'clean' 
        $pdo = getDBConnection();
        
        try {
            initFileSystem($pdo, ($mode === 'import'));
            $success = "檔案系統初始化成功！";
            $step = 'complete';
        } catch (Exception $e) {
            $error = "初始化失敗: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>檔案系統初始化 - BaxerMux Blog</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { width: 100%; max-width: 500px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">檔案系統初始化精靈</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($step === 'login'): ?>
            <p>檢測到您的檔案系統結構不完整。</p>
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
            
            <?php 
            $pdo = getDBConnection();
            $hasData = hasDBData($pdo);
            ?>

            <?php if ($hasData): ?>
                <div class="alert alert-info">
                    <strong>發現資料庫資料！</strong><br>
                    您可以將現有的資料庫內容匯出至檔案系統。
                </div>
                
                <form method="POST" class="d-grid gap-2">
                    <input type="hidden" name="do_init" value="1">
                    
                    <button type="submit" name="init_mode" value="import" class="btn btn-success btn-lg">
                        📤 從資料庫還原至檔案
                        <div class="fs-6 fw-normal">將資料庫文章與分類寫入檔案系統</div>
                    </button>
                    
                    <button type="submit" name="init_mode" value="clean" class="btn btn-outline-secondary">
                        🆕 全新安裝 (僅建立目錄)
                        <div class="fs-6 fw-normal">忽略資料庫，重新建立空白結構</div>
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    <strong>未發現資料庫資料</strong><br>
                    系統將為您建立基礎目錄結構並寫入範例檔案。
                </div>
                 <form method="POST" class="d-grid gap-2">
                    <input type="hidden" name="do_init" value="1">
                    <button type="submit" name="init_mode" value="clean" class="btn btn-success btn-lg">
                        🚀 開始建構檔案系統
                    </button>
                </form>
            <?php endif; ?>

        <?php elseif ($step === 'complete'): ?>
            <div class="text-center">
                <div class="text-success display-1 mb-3">✅</div>
                <h4><?php echo $success; ?></h4>
                <p class="text-muted">檔案系統已準備就緒。</p>
                <a href="index.php" class="btn btn-primary w-100 mt-3">進入後台管理</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
