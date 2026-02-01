<?php
require_once 'auth.php';
require_once 'data_provider.php';
require_once 'health_check.php';

requireLogin();

$dataManager = new DataManager();
$currentSource = $dataManager->getSource(); // 'file', 'db', 'sqlite'

// Check DB connection statuses
$dbStatus = SystemHealth::checkDB();
$dbReady = $dbStatus['status'];

$sqliteStatus = SystemHealth::checkSQLite();
$hasSqliteConfig = isset($sqlite_path) && !empty($sqlite_path);

// Determine "Cross" DB (The other DB option)
$crossDB = null;
$crossDBLabel = '';
$crossDBReady = false;

if ($currentSource === 'db') {
    $crossDB = 'sqlite';
    $crossDBLabel = 'SQLite 3';
    $crossDBReady = $hasSqliteConfig; // SQLite file implies ready to init/write
} elseif ($currentSource === 'sqlite') {
    $crossDB = 'mysql';
    $crossDBLabel = 'MySQL';
    $crossDBReady = $dbReady;
}

// Flags & Input
$startMigration = isset($_POST['start_migration']); // Action: Import (Pull)
$startExport = isset($_POST['start_export']);       // Action: Export (Push)

$importSource = $_POST['import_source'] ?? 'file';  // file | mysql | sqlite
$exportTarget = $_POST['export_target'] ?? 'file';  // file | mysql | sqlite

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLang ?? 'zh_TW'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('migrate_title'); ?> - Blog Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar a { color: #cfd2d6; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
        .main-content { padding: 20px; }
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
            <span class="fs-4"><?php echo __('nav_brand'); ?></span>
        </a>
        <hr>
        <div class="text-center mb-3">
            <span class="badge <?php echo ($currentSource === 'db') ? 'bg-success' : (($currentSource === 'sqlite') ? 'bg-info text-dark' : 'bg-warning text-dark'); ?>">
                <?php echo __('mode_label'); ?>: <?php echo ($currentSource === 'db') ? __('mode_db_short') : (($currentSource === 'sqlite') ? 'SQLite' : __('mode_file_short')); ?>
            </span>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item"><a href="index.php"><?php echo __('nav_dashboard'); ?></a></li>
            <li class="nav-item"><a href="posts.php"><?php echo __('nav_posts'); ?></a></li>
            <li class="nav-item"><a href="categories.php"><?php echo __('nav_categories'); ?></a></li>
            <li class="nav-item"><a href="tool_migrate.php" class="active"><?php echo __('nav_import'); ?></a></li>
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
            <h2><?php echo __('migrate_title'); ?></h2>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title"><?php echo __('migrate_info_title'); ?></h5>
                
                <?php if ($currentSource === 'file'): ?>
                    <!-- ========================================== -->
                    <!-- Mode: File System                          -->
                    <!-- ========================================== -->
                    <p class="card-text"><?php echo __('migrate_desc'); ?></p>
                    <div class="row">
                        <!-- Push: File -> DB -->
                        <div class="col-md-6 border-end">
                            <h6 class="text-primary fw-bold"><?php echo __('migrate_export_to_db'); ?></h6>
                            <form method="POST" onsubmit="return confirm('<?php echo __('confirm_start_migration'); ?>');">
                                <input type="hidden" name="start_migration" value="1">
                                <!-- Import Source implicitly File -->
                                <div class="mb-3">
                                    <label class="form-label small fw-bold"><?php echo __('migrate_target_db'); ?></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="export_target" value="mysql" id="target_mysql" <?php echo ($dbReady) ? 'checked' : 'disabled'; ?>/>
                                        <label class="form-check-label" for="target_mysql">MySQL <?php if(!$dbReady) echo '(N/A)'; ?></label>
                                    </div>
                                    <?php if ($hasSqliteConfig): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="export_target" value="sqlite" id="target_sqlite" <?php echo (!$dbReady) ? 'checked' : ''; ?>/> 
                                        <label class="form-check-label" for="target_sqlite">SQLite 3</label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!$startMigration && !$startExport): ?>
                                    <button type="submit" class="btn btn-primary w-100" <?php echo (!$dbReady && !$hasSqliteConfig) ? 'disabled' : ''; ?>/><?php echo __('btn_start_migration'); ?></button>
                                <?php endif; ?>
                            </form>
                        </div>

                        <!-- Pull: DB -> File -->
                        <div class="col-md-6">
                            <h6 class="text-success fw-bold"><?php echo __('migrate_restore_from_db'); ?></h6>
                            <form method="POST" onsubmit="return confirm('<?php echo __('confirm_restore_warn'); ?>');">
                                <input type="hidden" name="start_export" value="1">
                                <!-- Export Target implicitly File -->
                                <div class="mb-3">
                                    <label class="form-label small fw-bold"><?php echo __('migrate_source_db'); ?></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="import_source" value="mysql" id="source_mysql" <?php echo ($dbReady) ? 'checked' : 'disabled'; ?>/> 
                                        <label class="form-check-label" for="source_mysql">MySQL <?php if(!$dbReady) echo '(N/A)'; ?></label>
                                    </div>
                                    <?php if ($hasSqliteConfig): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="import_source" value="sqlite" id="source_sqlite" <?php echo (!$dbReady) ? 'checked' : ''; ?>/> 
                                        <label class="form-check-label" for="source_sqlite">SQLite 3</label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!$startMigration && !$startExport): ?>
                                    <button type="submit" class="btn btn-success w-100" <?php echo (!$dbReady && !$hasSqliteConfig) ? 'disabled' : ''; ?>/><?php echo __('btn_start_restore'); ?></button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- ========================================== -->
                    <!-- Mode: DB / SQLite                          -->
                    <!-- ========================================== -->
                    <p class="card-text">
                        <?php echo __('migrate_current_mode'); ?> <strong><?php echo ($currentSource === 'db') ? 'MySQL' : 'SQLite'; ?></strong>
                    </p>
                    
                    <div class="row">
                        <!-- Left: Export (Push) -->
                        <div class="col-md-6 border-end">
                            <h6 class="text-success fw-bold"><?php echo __('migrate_export_data'); ?></h6>
                            <p class="small text-muted"><?php echo __('migrate_export_desc'); ?></p>
                            
                            <form method="POST" onsubmit="return confirm('<?php echo __('confirm_export_warn'); ?>');">
                                <input type="hidden" name="start_export" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold"><?php echo __('migrate_target_loc'); ?></label>
                                    
                                    <!-- Option: File System -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="export_target" value="file" id="exp_file" checked>
                                        <label class="form-check-label" for="exp_file">
                                            📁 <?php echo __('mode_file'); ?>
                                        </label>
                                    </div>

                                    <!-- Option: Other DB -->
                                    <?php if ($crossDB): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="export_target" value="<?php echo $crossDB; ?>" id="exp_cross" <?php echo (!$crossDBReady) ? 'disabled' : ''; ?>/>
                                        <label class="form-check-label" for="exp_cross">
                                            🗄️ <?php echo $crossDBLabel; ?>
                                            <?php if(!$crossDBReady) echo '(N/A)'; ?>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$startMigration && !$startExport): ?>
                                    <button type="submit" class="btn btn-success w-100"><?php echo __('btn_exec_export'); ?></button>
                                <?php endif; ?>
                            </form>
                        </div>

                        <!-- Right: Import (Pull) -->
                        <div class="col-md-6">
                            <h6 class="text-primary fw-bold"><?php echo __('migrate_import_data'); ?></h6>
                            <p class="small text-muted"><?php echo __('migrate_import_desc'); ?></p>
                            
                            <form method="POST" onsubmit="return confirm('<?php echo __('confirm_import_warn'); ?>');">
                                <input type="hidden" name="start_migration" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold"><?php echo __('migrate_source_loc'); ?></label>
                                    
                                    <!-- Option: File System -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="import_source" value="file" id="imp_file" checked>
                                        <label class="form-check-label" for="imp_file">
                                            📁 <?php echo __('mode_file'); ?>
                                        </label>
                                    </div>

                                    <!-- Option: Other DB -->
                                    <?php if ($crossDB): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="import_source" value="<?php echo $crossDB; ?>" id="imp_cross" <?php echo (!$crossDBReady) ? 'disabled' : ''; ?>/>
                                        <label class="form-check-label" for="imp_cross">
                                            🗄️ <?php echo $crossDBLabel; ?>
                                            <?php if(!$crossDBReady) echo '(N/A)'; ?>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$startMigration && !$startExport): ?>
                                    <button type="submit" class="btn btn-primary w-100"><?php echo __('btn_exec_import'); ?></button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <?php if ($startMigration): ?>
            <!-- Action: Import (Writing to Current/Target DB) -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white"><?php echo __('log_header'); ?></div>
                <div class="card-body bg-dark">
                    <div class="progress-bar-container"><div id="p-bar" class="progress-fill">0%</div></div>
                    <div class="log-container">
                        <?php 
                            if ($currentSource === 'file') {
                                // File Mode: Import FROM File TO Target DB
                                runImport($exportTarget); 
                            } else {
                                // DB Mode: Import FROM Source TO Current DB
                                if ($importSource === 'file') {
                                    $targetDB = ($currentSource === 'db') ? 'mysql' : 'sqlite';
                                    runImport($targetDB);
                                } else {
                                    // DB -> DB Import (e.g. SQLite -> MySQL)
                                    $targetDB = ($currentSource === 'db') ? 'mysql' : 'sqlite';
                                    runDBMigration($importSource, $targetDB);
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>
        <?php elseif ($startExport): ?>
            <!-- Action: Export (Reading from Current/Source DB) -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white"><?php echo __('log_header'); ?></div>
                <div class="card-body bg-dark">
                    <div class="progress-bar-container"><div id="p-bar" class="progress-fill">0%</div></div>
                    <div class="log-container">
                        <?php 
                            if ($currentSource === 'file') {
                                // File Mode: Restore FROM DB TO File
                                $pdo = connectTo($_POST['import_source']); // Actually used as source
                                if ($pdo) runExport($pdo);
                                else output_log("無法連線至來源資料庫", 'error');
                            } else {
                                // DB Mode: Export FROM Current DB
                                if ($exportTarget === 'file') {
                                    runExport(); // Use global PDO
                                } else {
                                    // DB -> DB Export (e.g. MySQL -> SQLite)
                                    $sourceDB = ($currentSource === 'db') ? 'mysql' : 'sqlite';
                                    runDBMigration($sourceDB, $exportTarget);
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require 'common_js_inc.php'; ?>
</body>
</html>

<?php
// ==========================================
// Utilities
// ==========================================
function output_log($msg, $type = 'info') {
    $colors = ['success'=>'#2ecc71', 'error'=>'#e74c3c', 'warning'=>'#f39c12', 'system'=>'#3498db', 'default'=>'#bdc3c7'];
    $color = $colors[$type] ?? $colors['default'];
    $icons = ['success'=>'✅', 'error'=>'❌', 'warning'=>'⚠️', 'system'=>'⚙️', 'default'=>'📝'];
    $icon = $icons[$type] ?? $icons['default'];
    echo "<div class='log-item' style='border-left: 4px solid $color;'><span class='icon'>$icon</span><span class='msg'>$msg</span></div>";
    echo "<script>var c=document.querySelector('.log-container');if(c)c.scrollTop=c.scrollHeight;</script>";
    flush(); if (ob_get_level() > 0) ob_flush();
}

function update_progress($current, $total) {
    $percent = ($total > 0) ? round(($current / $total) * 100) : 0;
    echo "<script>var p=document.getElementById('p-bar');if(p){p.style.width='$percent%';p.innerText='$percent%';}</script>";
    flush(); if (ob_get_level() > 0) ob_flush();
}

function connectTo($target) {
    global $dbConfig, $sqlite_path;
    try {
        if ($target === 'mysql') {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
            return new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        } elseif ($target === 'sqlite') {
            return new PDO("sqlite:" . dirname(__DIR__) . '/' . $sqlite_path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        }
    } catch (Exception $e) { return null; }
    return null;
}

// ==========================================
// 1. File -> DB Logic
// ==========================================
function runImport($targetDB) {
    global $dbConfig, $sqlite_path;
    set_time_limit(600); @ini_set('implicit_flush', 1);

    $pdo = connectTo($targetDB);
    if (!$pdo) { output_log(sprintf(__('log_target_connect_fail'), $targetDB), 'error'); return; }
    output_log(sprintf(__('log_target_connect_success'), strtoupper($targetDB)), 'success');

    // Create Schema
    ensureSchema($pdo, $targetDB);

    $baseDir = dirname(__DIR__);
    $indexFile = $baseDir . '/contents/index_post.txt';
    if (!file_exists($indexFile)) { output_log(sprintf(__('log_file_missing'), 'index_post.txt'), 'error'); return; }

    $lines = file($indexFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $total = count($lines);
    $count = 0;

    $stmt = prepareUpsertStmt($pdo, $targetDB);

    foreach ($lines as $index => $line) {
        $parts = explode('|', $line);
        if (count($parts) < 3) continue;
        list($date, $fname, $title) = [$parts[0], $parts[1], $parts[2]];
        $tags = $parts[3]??''; $desc = $parts[4]??'';
        
        $content = '';
        if (file_exists($baseDir."/contents/post_files/$fname")) $content = file_get_contents($baseDir."/contents/post_files/$fname");

        // Categories
        $cats = [];
        foreach (glob($baseDir.'/category/*', GLOB_ONLYDIR) as $dir) {
            if (file_exists("$dir/$fname") || file_exists("$dir/".pathinfo($fname, PATHINFO_FILENAME))) $cats[] = basename($dir);
        }

        if (processPostImport($pdo, $stmt, $targetDB, $fname, $title, $date, $tags, $desc, $content, $cats)) {
            output_log(sprintf(__('log_imported'), $title), 'success');
            $count++;
        }
        update_progress($index + 1, $total);
    }
    output_log(sprintf(__('log_complete'), $count), 'system');
}

// ==========================================
// 2. DB -> File Logic
// ==========================================
function runExport($pdo = null) {
    if ($pdo === null) { global $pdo; }
    if (!$pdo) { output_log(__('log_db_connect_fail'), 'error'); return; }
    
    set_time_limit(600); @ini_set('implicit_flush', 1);
    
    $baseDir = dirname(__DIR__);
    foreach (['/contents', '/contents/post_files', '/category'] as $sub) {
        if (!is_dir($baseDir.$sub)) mkdir($baseDir.$sub, 0755, true);
    }

    try {
        output_log(__('log_reading_source'), 'system');
        $stmt = $pdo->query("SELECT p.*, GROUP_CONCAT(c.category_name) as cats FROM blog_posts p LEFT JOIN blog_post_categories pc ON p.id = pc.post_id LEFT JOIN blog_categories c ON pc.category_id = c.id GROUP BY p.id ORDER BY p.post_date DESC");
        $posts = $stmt->fetchAll();
        $total = count($posts);
        output_log(sprintf(__('log_found_posts'), $total), 'system');

        $lines = [];
        $i = 0;
        foreach ($posts as $p) {
            $fname = $p['post_filename'];
            file_put_contents($baseDir."/contents/post_files/$fname", $p['post_content']);
            $lines[] = implode('|', [$p['post_date'], $fname, $p['post_title'], $p['post_tags'], $p['post_description']]);
            
            $cats = explode(',', $p['cats']??'');
            foreach ($cats as $c) {
                $c = trim($c); if(!$c) continue;
                if(!is_dir($baseDir."/category/$c")) mkdir($baseDir."/category/$c", 0755, true);
                touch($baseDir."/category/$c/$fname");
            }
            $i++;
            output_log(sprintf(__('log_exported'), $p['post_title']), 'success');
            update_progress($i, $total);
        }
        file_put_contents($baseDir."/contents/index_post.txt", implode("\n", $lines));
        output_log(__('log_export_complete'), 'system');
    } catch (Exception $e) {
        output_log(sprintf(__('log_sys_error'), $e->getMessage()), 'error');
    }
}

// ==========================================
// 3. DB -> DB Logic (New)
// ==========================================
function runDBMigration($sourceType, $targetType) {
    set_time_limit(600); @ini_set('implicit_flush', 1);
    
    output_log(sprintf(__('log_prepare_migration'), strtoupper($sourceType), strtoupper($targetType)), 'system');

    // 1. Connect Both
    $srcPdo = connectTo($sourceType);
    $tgtPdo = connectTo($targetType);

    if (!$srcPdo) { output_log(sprintf(__('log_target_connect_fail'), 'Source ' . $sourceType), 'error'); return; }
    if (!$tgtPdo) { output_log(sprintf(__('log_target_connect_fail'), 'Target ' . $targetType), 'error'); return; }

    // 2. Init Target Schema
    ensureSchema($tgtPdo, $targetType);

    // 3. Fetch Source
    try {
        $sql = "SELECT p.*, GROUP_CONCAT(c.category_name) as cats 
                FROM blog_posts p
                LEFT JOIN blog_post_categories pc ON p.id = pc.post_id
                LEFT JOIN blog_categories c ON pc.category_id = c.id
                GROUP BY p.id";
        // SQLite uses slightly different GROUP_CONCAT but we fixed it to use standard comma
        $rows = $srcPdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $total = count($rows);
        output_log(sprintf(__('log_source_count'), $total), 'system');

        // 4. Write Target
        $stmt = prepareUpsertStmt($tgtPdo, $targetType);
        $count = 0;

        foreach ($rows as $index => $row) {
            $fname = $row['post_filename'];
            $cats = explode(',', $row['cats']??'');
            
            if (processPostImport($tgtPdo, $stmt, $targetType, $fname, $row['post_title'], $row['post_date'], $row['post_tags'], $row['post_description'], $row['post_content'], $cats)) {
                output_log(sprintf(__('log_migration_success'), $row['post_title']), 'success');
                $count++;
            } else {
                output_log(sprintf(__('log_migration_fail'), $row['post_title']), 'error');
            }
            update_progress($index + 1, $total);
        }
        output_log(__('log_migration_complete'), 'system');

    } catch (Exception $e) {
        output_log(sprintf(__('log_migration_error'), $e->getMessage()), 'error');
    }
}

// ==========================================
// Helpers
// ==========================================
function ensureSchema($pdo, $type) {
    if ($type === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_posts` (`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `post_filename` VARCHAR(190) NOT NULL, `post_title` VARCHAR(255) NOT NULL, `post_date` DATETIME NOT NULL, `post_tags` TEXT, `post_description` TEXT, `post_content` LONGTEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY `unique_filename` (`post_filename`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_categories` (`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `category_name` VARCHAR(190) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_post_categories` (`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `post_id` INT UNSIGNED NOT NULL, `category_id` INT UNSIGNED NOT NULL, UNIQUE KEY `unique_post_cat` (`post_id`, `category_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, post_title TEXT, post_filename TEXT UNIQUE, post_date TEXT, post_content TEXT, post_tags TEXT, post_description TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS blog_categories (id INTEGER PRIMARY KEY AUTOINCREMENT, category_name TEXT UNIQUE)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS blog_post_categories (id INTEGER PRIMARY KEY AUTOINCREMENT, post_id INTEGER, category_id INTEGER)");
    }
}

function prepareUpsertStmt($pdo, $type) {
    if ($type === 'mysql') {
        return $pdo->prepare("INSERT INTO blog_posts (post_filename, post_title, post_date, post_tags, post_description, post_content, updated_at) VALUES (?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE post_title=VALUES(post_title), post_date=VALUES(post_date), post_tags=VALUES(post_tags), post_description=VALUES(post_description), post_content=VALUES(post_content), updated_at=NOW()");
    } else {
        return $pdo->prepare("INSERT INTO blog_posts (post_filename, post_title, post_date, post_tags, post_description, post_content, updated_at) VALUES (?,?,?,?,?,?,datetime('now','localtime')) ON CONFLICT(post_filename) DO UPDATE SET post_title=excluded.post_title, post_date=excluded.post_date, post_tags=excluded.post_tags, post_description=excluded.post_description, post_content=excluded.post_content, updated_at=datetime('now','localtime')");
    }
}

function processPostImport($pdo, $stmt, $type, $fname, $title, $date, $tags, $desc, $content, $cats) {
    try {
        $pdo->beginTransaction();
        try {
            $stmt->execute([$fname, $title, $date, $tags, $desc, $content]);
        } catch(Exception $e) {
            // Fallback for old SQLite
            if ($type === 'sqlite' && strpos($e->getMessage(), 'syntax error') !== false) {
                 $check = $pdo->prepare("SELECT id FROM blog_posts WHERE post_filename = ?");
                 $check->execute([$fname]); $pid = $check->fetchColumn();
                 if ($pid) $pdo->prepare("UPDATE blog_posts SET post_title=?, post_date=?, post_tags=?, post_description=?, post_content=?, updated_at=datetime('now') WHERE id=?")->execute([$title, $date, $tags, $desc, $content, $pid]);
                 else $pdo->prepare("INSERT INTO blog_posts (post_filename, post_title, post_date, post_tags, post_description, post_content, updated_at) VALUES (?,?,?,?,?,?,datetime('now'))")->execute([$fname, $title, $date, $tags, $desc, $content]);
            } else { throw $e; }
        }
        
        $checkId = $pdo->prepare("SELECT id FROM blog_posts WHERE post_filename = ?");
        $checkId->execute([$fname]); $postId = $checkId->fetchColumn();
        
        $pdo->prepare("DELETE FROM blog_post_categories WHERE post_id = ?")->execute([$postId]);
        
        foreach ($cats as $cat) {
            $cat = trim($cat); if(!$cat) continue;
            $checkCat = $pdo->prepare("SELECT id FROM blog_categories WHERE category_name = ?");
            $checkCat->execute([$cat]); $catId = $checkCat->fetchColumn();
            if (!$catId) {
                try { $pdo->prepare("INSERT INTO blog_categories (category_name) VALUES (?)")->execute([$cat]); $catId = $pdo->lastInsertId(); } 
                catch(Exception $e) { $checkCat->execute([$cat]); $catId = $checkCat->fetchColumn(); }
            }
            if($catId) $pdo->prepare("INSERT INTO blog_post_categories (post_id, category_id) VALUES (?, ?)")->execute([$postId, $catId]);
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        output_log("Error [$fname]: " . $e->getMessage(), 'error');
        return false;
    }
}
?>
