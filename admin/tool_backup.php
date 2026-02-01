<?php
// admin/tool_backup.php
require_once 'auth.php';
require_once 'health_check.php';
require_once 'data_provider.php'; // Needed for DB connection

requireLogin();

$currentSource = getAdminSource();
$backupDir = dirname(__DIR__) . '/backup';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$msg = '';
$msgType = '';

// Check for Post Max Size violation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $msg = sprintf(__('upload_fail'), getUploadLimit());
    $msgType = 'danger';
}

// --- Helper: MySQL Dump ---
function createMysqlDump($pdo) {
    $out = "-- MySQL Dump\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $out .= "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n\n";

    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) $tables[] = $row[0];

    foreach ($tables as $table) {
        // Structure
        $out .= "-- Table structure for `$table`\n";
        $out .= "DROP TABLE IF EXISTS `$table`;\n";
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $out .= $row[1] . ";\n\n";

        // Data
        $out .= "-- Dumping data for `$table`\n";
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $out .= "INSERT INTO `$table` VALUES \n";
            $valuesArr = [];
            foreach ($rows as $row) {
                $vals = [];
                foreach ($row as $val) {
                    if ($val === null) $vals[] = "NULL";
                    else $vals[] = $pdo->quote($val);
                }
                $valuesArr[] = "(" . implode(',', $vals) . ")";
            }
            $out .= implode(",\n", $valuesArr) . ";\n";
        }
        $out .= "\n";
    }
    $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $out;
}

// --- Helper: Restore MySQL ---
function restoreMysqlDump($pdo, $sqlFile) {
    $sql = file_get_contents($sqlFile);
    // Split by semicolon only if not inside quotes is hard with regex. 
    // But mysqldump output usually has simple structure. 
    // PDO exec handles multiple queries in some drivers, but safer to split or use specific importer.
    // For simplicity with generated dump, we execute big chunks or rely on PDO multiple queries if enabled.
    // MySQL PDO default does not allow multiple queries. We must split.
    // However, INSERTs can be large. 
    // Simple splitter for standard dumps:
    
    $lines = file($sqlFile);
    $query = '';
    
    // Disable foreign key checks for session
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '--') === 0 || strpos($line, '#') === 0) continue;
        
        $query .= $line;
        if (substr(trim($line), -1) === ';') {
            try {
                $pdo->exec($query);
            } catch (Exception $e) {
                // Log error but continue? Or fail?
                // For restore, usually fail is safer.
                throw $e;
            }
            $query = '';
        }
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
}

// --- Handle Upload Action ---
if (isset($_POST['action']) && $_POST['action'] === 'upload_backup') {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['backup_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($ext === 'zip') {
            $targetPath = $backupDir . '/' . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $msg = __('upload_success');
                $msgType = 'success';
            } else {
                $msg = sprintf(__('upload_fail'), $uploadLimitStr);
                $msgType = 'danger';
            }
        } else {
            $msg = __('upload_err_format');
            $msgType = 'danger';
        }
    } else {
        $msg = sprintf(__('upload_fail'), $uploadLimitStr);
        $msgType = 'danger';
    }
}

// --- Handle Restore Action ---
if (isset($_POST['action']) && $_POST['action'] === 'restore_backup' && isset($_POST['filename'])) {
    set_time_limit(600); 
    
    $filename = basename($_POST['filename']);
    $zipPath = $backupDir . '/' . $filename;
    
    if (file_exists($zipPath) && substr($filename, -4) === '.zip') {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === TRUE) {
            $baseDir = dirname(__DIR__);
            
            // Check mode compatibility
            $isDbBackup = (strpos($filename, 'dbsqlbase') === 0);
            $isSqliteBackup = (strpos($filename, 'sqlitebase') === 0);
            $isFileBackup = (strpos($filename, 'filebase') === 0);
            
            if ($currentSource === 'db' && $isDbBackup) {
                // DB Restore
                $tempExtractDir = $backupDir . '/temp_' . time();
                if (!is_dir($tempExtractDir)) mkdir($tempExtractDir);
                $zip->extractTo($tempExtractDir);
                $zip->close();
                
                try {
                    $sqlFiles = glob($tempExtractDir . '/*.sql');
                    if (!empty($sqlFiles)) {
                        global $dbConfig;
                        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
                        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                        
                        restoreMysqlDump($pdo, $sqlFiles[0]);
                        restoreStaticFiles($tempExtractDir, $baseDir);
                        
                        $msg = __('restore_success');
                        $msgType = 'success';
                    } else {
                        $msg = "SQL file not found in backup.";
                        $msgType = 'danger';
                    }
                } catch (Exception $e) {
                    $msg = __('restore_fail') . ': ' . $e->getMessage();
                    $msgType = 'danger';
                }
                cleanupTempDir($tempExtractDir);

            } elseif ($currentSource === 'sqlite' && $isSqliteBackup) {
                // SQLite Restore
                global $sqlite_path;
                if (!isset($sqlite_path)) {
                    $msg = "SQLite path config missing.";
                    $msgType = 'danger';
                    $zip->close();
                } else {
                    $tempExtractDir = $backupDir . '/temp_' . time();
                    if (!is_dir($tempExtractDir)) mkdir($tempExtractDir);
                    $zip->extractTo($tempExtractDir);
                    $zip->close();

                    try {
                        $sqliteFiles = [];
                        foreach (scandir($tempExtractDir) as $f) {
                            if ($f === '.' || $f === '..') continue;
                            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                            if (in_array($ext, ['sqlite', 'db', 'sqlite3', 'db3'])) {
                                $sqliteFiles[] = $f;
                            }
                        }

                        if (!empty($sqliteFiles)) {
                            $targetDbPath = $baseDir . '/' . $sqlite_path;
                            copy($tempExtractDir . '/' . $sqliteFiles[0], $targetDbPath);
                            
                            restoreStaticFiles($tempExtractDir, $baseDir);
                            $msg = __('restore_success');
                            $msgType = 'success';
                        } else {
                            $msg = "SQLite database file not found in backup.";
                            $msgType = 'danger';
                        }
                    } catch (Exception $e) {
                         $msg = __('restore_fail') . ': ' . $e->getMessage();
                         $msgType = 'danger';
                    }
                    cleanupTempDir($tempExtractDir);
                }

            } elseif ($currentSource === 'file' && $isFileBackup) {
                // File System Restore
                $zip->extractTo($baseDir);
                $zip->close();
                $msg = __('restore_success');
                $msgType = 'success';
            } else {
                $zip->close();
                $msg = "Backup type mismatch. Current mode: $currentSource, Backup file: $filename";
                $msgType = 'danger';
            }
        } else {
            $msg = __('restore_fail');
            $msgType = 'danger';
        }
    } else {
        $msg = "Invalid file.";
        $msgType = 'danger';
    }
}

// --- Handle Backup Action ---
if (isset($_POST['action']) && $_POST['action'] === 'create_backup') {
    set_time_limit(0); 
    
    $timestamp = date('Ymd-His');
    $baseDir = dirname(__DIR__);
    
    if ($currentSource === 'db') {
        // --- DB Mode Backup ---
        $zipFilename = "dbsqlbase-{$timestamp}-backup.zip";
        $zipPath = $backupDir . '/' . $zipFilename;
        $sqlFilename = "backup-{$timestamp}.sql";
        $sqlPath = $backupDir . '/' . $sqlFilename;
        
        try {
            global $dbConfig;
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            // 1. Generate SQL
            $sqlContent = createMysqlDump($pdo);
            file_put_contents($sqlPath, $sqlContent);
            
            // 2. Zip it
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($sqlPath, $sqlFilename);
                addStaticFilesToZip($zip, $baseDir);
                $zip->close();
                unlink($sqlPath); // Remove raw SQL after zip
                
                $msg = __('backup_success') . ": " . $zipFilename;
                $msgType = 'success';
            } else {
                $msg = __('backup_fail');
                $msgType = 'danger';
            }
        } catch (Exception $e) {
            $msg = __('backup_fail') . ': ' . $e->getMessage();
            $msgType = 'danger';
        }

    } elseif ($currentSource === 'sqlite') {
        // --- SQLite Mode Backup ---
        global $sqlite_path;
        if (isset($sqlite_path) && file_exists($baseDir . '/' . $sqlite_path)) {
            $zipFilename = "sqlitebase-{$timestamp}-backup.zip";
            $zipPath = $backupDir . '/' . $zipFilename;
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($baseDir . '/' . $sqlite_path, basename($sqlite_path));
                addStaticFilesToZip($zip, $baseDir);
                $zip->close();
                $msg = __('backup_success') . ": " . $zipFilename;
                $msgType = 'success';
            } else {
                 $msg = __('backup_fail');
                 $msgType = 'danger';
            }
        } else {
            $msg = __('backup_fail') . ": SQLite file not found.";
            $msgType = 'danger';
        }

    } else {
        // --- File Mode Backup (Existing) ---
        $zipFilename = "filebase-{$timestamp}-backup.zip";
        $zipPath = $backupDir . '/' . $zipFilename;
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            $dirs = ['category', 'contents', 'preview', 'pic'];
            foreach ($dirs as $dir) {
                $fullPath = $baseDir . '/' . $dir;
                if (is_dir($fullPath)) {
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    foreach ($files as $file) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($baseDir) + 1);
                        $relativePath = str_replace('\\', '/', $relativePath);
                        if ($file->isDir()) $zip->addEmptyDir($relativePath);
                        else $zip->addFile($filePath, $relativePath);
                    }
                }
            }
            $iconPath = $baseDir . '/static/icon-192.png';
            if (file_exists($iconPath)) $zip->addFile($iconPath, 'static/icon-192.png');
            
            $zip->close();
            $msg = __('backup_success') . ": " . $zipFilename;
            $msgType = 'success';
        } else {
            $msg = __('backup_fail');
            $msgType = 'danger';
        }
    }
}

// --- Handle Delete Action ---
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['filename'])) {
    $fileToDelete = $backupDir . '/' . basename($_POST['filename']);
    if (file_exists($fileToDelete) && substr($fileToDelete, -4) === '.zip') {
        unlink($fileToDelete);
        $msg = __('backup_deleted');
        $msgType = 'success';
    }
}

// --- List Backups ---
$allBackups = glob($backupDir . '/*.zip');
$backups = [];
foreach ($allBackups as $file) {
    $bn = basename($file);
    if ($currentSource === 'db') {
        if (strpos($bn, 'dbsqlbase') === 0) $backups[] = $file;
    } elseif ($currentSource === 'sqlite') {
        if (strpos($bn, 'sqlitebase') === 0) $backups[] = $file;
    } else {
        if (strpos($bn, 'filebase') === 0) $backups[] = $file;
    }
}
usort($backups, function($a, $b) {
    return filemtime($b) - filemtime($a); 
});

// --- Helper: Get PHP Upload Limit ---
function getUploadLimit() {
    $parse = function($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    };
    $upload_max = $parse(ini_get('upload_max_filesize'));
    $post_max = $parse(ini_get('post_max_size'));
    $limit = min($upload_max, $post_max);
    
    if ($limit >= 1024 * 1024 * 1024) return round($limit / 1024 / 1024 / 1024, 2) . ' GB';
    else return round($limit / 1024 / 1024, 2) . ' MB';
}
$uploadLimitStr = getUploadLimit();

// --- Shared Helpers ---

function addStaticFilesToZip($zip, $baseDir) {
    $dirs = ['preview', 'pic'];
    foreach ($dirs as $dir) {
        $fullPath = $baseDir . '/' . $dir;
        if (is_dir($fullPath)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($baseDir) + 1);
                $relativePath = str_replace('\\', '/', $relativePath);
                if ($file->isDir()) {
                    $zip->addEmptyDir($relativePath);
                } else {
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
    }
    // Specific file: static/icon-192.png
    $iconPath = $baseDir . '/static/icon-192.png';
    if (file_exists($iconPath)) {
        $zip->addFile($iconPath, 'static/icon-192.png');
    }
}

function restoreStaticFiles($srcDir, $destDir) {
     foreach(['preview', 'pic', 'static'] as $d) {
         if (is_dir($srcDir . '/' . $d)) {
             $src = $srcDir . '/' . $d;
             $dst = $destDir . '/' . $d;
             if (!is_dir($dst)) mkdir($dst, 0755, true);
             
             $it = new RecursiveIteratorIterator(
                 new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
                 RecursiveIteratorIterator::SELF_FIRST
             );
             foreach ($it as $item) {
                 $subPath = $it->getSubPathName();
                 if ($item->isDir()) {
                     if (!is_dir($dst . '/' . $subPath)) mkdir($dst . '/' . $subPath);
                 } else {
                     copy($item, $dst . '/' . $subPath);
                 }
             }
         }
     }
}

function cleanupTempDir($dir) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        if ($file->isDir()) rmdir($file->getRealPath());
        else unlink($file->getRealPath());
    }
    rmdir($dir);
}


?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLang ?? 'zh_TW'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('backup_title'); ?> - Blog Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar a { color: #cfd2d6; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
        .main-content { padding: 20px; }
        /* Loading Overlay */
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            color: white;
        }
        .spinner-border { width: 3rem; height: 3rem; }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div id="loadingOverlay">
    <div class="spinner-border text-light mb-3" role="status"></div>
    <h4 id="loadingText"><?php echo __('loading_backup'); ?></h4>
</div>

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
            <li class="nav-item"><a href="tool_migrate.php"><?php echo __('nav_import'); ?></a></li>
            <!-- Backup Link (Active) -->
            <li class="nav-item"><a href="tool_backup.php" class="active"><?php echo __('nav_backup'); ?></a></li>
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
            <h2><?php echo __('backup_title'); ?></h2>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title"><?php echo __('backup_create_title'); ?></h5>
                <p class="text-muted"><?php echo __('backup_desc'); ?></p>
                
                <form method="POST" id="createBackupForm">
                    <input type="hidden" name="action" value="create_backup">
                    <button type="button" class="btn btn-primary" onclick="confirmAction('create')">
                        💾 <?php echo __('btn_create_backup'); ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title"><?php echo __('backup_upload_title'); ?></h5>
                <p class="text-muted mb-1"><?php echo __('backup_upload_desc'); ?></p>
                
                <div class="alert alert-info alert-dismissible fade show my-3" role="alert">
                    <strong><i class="bi bi-info-circle"></i> <?php echo __('php_ini_hint_title'); ?></strong><br>
                    <small><?php echo __('php_ini_hint_desc'); ?></small>
                    <pre class="bg-dark text-light p-2 mt-2 rounded small mb-2">upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300</pre>
                    <small><?php echo __('php_ini_hint_fail'); ?></small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <div class="small text-danger mb-3">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo sprintf(__('upload_limit_hint'), $uploadLimitStr); ?>
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="uploadBackupForm">
                    <input type="hidden" name="action" value="upload_backup">
                    <div class="input-group">
                        <input type="file" class="form-control" name="backup_file" accept=".zip" required>
                        <button class="btn btn-outline-secondary" type="submit"><?php echo __('btn_upload'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?php echo __('backup_list_title'); ?></h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('col_filename'); ?></th>
                            <th><?php echo __('col_size'); ?></th>
                            <th><?php echo __('col_time'); ?></th>
                            <th class="text-end"><?php echo __('col_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $file):
                            $filename = basename($file);
                            $size = round(filesize($file) / 1024 / 1024, 2) . ' MB';
                            $time = date('Y-m-d H:i:s', filemtime($file));
                        ?>
                        <tr>
                            <td><?php echo $filename; ?></td>
                            <td><?php echo $size; ?></td>
                            <td><?php echo $time; ?></td>
                            <td class="text-end">
                                <!-- Download -->
                                <a href="../backup/<?php echo $filename; ?>" class="btn btn-sm btn-outline-success me-1" download>
                                    ⬇️ <?php echo __('btn_download'); ?>
                                </a>
                                
                                <!-- Restore -->
                                <form method="POST" class="d-inline-block" id="restore_<?php echo md5($filename); ?>">
                                    <input type="hidden" name="action" value="restore_backup">
                                    <input type="hidden" name="filename" value="<?php echo $filename; ?>">
                                    <button type="button" class="btn btn-sm btn-outline-warning me-1" onclick="confirmAction('restore', '<?php echo md5($filename); ?>')">
                                        🔄 <?php echo __('btn_restore'); ?>
                                    </button>
                                </form>

                                <!-- Delete -->
                                <form method="POST" class="d-inline-block" id="delete_<?php echo md5($filename); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="filename" value="<?php echo $filename; ?>">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmAction('delete', '<?php echo md5($filename); ?>')">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($backups)): ?>
                            <tr><td colspan="4" class="text-center text-muted"><?php echo __('no_backups'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php require 'common_js_inc.php'; ?>
<script>
    // Upload Loading
    const uploadForm = document.getElementById('uploadBackupForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function() {
            document.getElementById('loadingText').innerText = '<?php echo __('loading_upload'); ?>';
            document.getElementById('loadingOverlay').style.display = 'flex';
        });
    }

    // Trigger SweetAlert for PHP messages
    <?php if ($msg): ?>
    Swal.fire({
        title: '<?php echo ($msgType === 'success') ? __('op_success') : __('op_error'); ?>',
        text: '<?php echo str_replace("'", "\'", $msg); ?>', // Simple escape
        icon: '<?php echo ($msgType === 'success') ? 'success' : 'error'; ?>',
        confirmButtonColor: '<?php echo ($msgType === 'success') ? '#198754' : '#dc3545'; ?>'
    });
    <?php endif; ?>

    function confirmAction(type, formId) {
        let title, text, confirmBtnColor, confirmBtnText, loadingText;
        let form;

        if (type === 'create') {
            title = '<?php echo __('backup_create_title'); ?>';
            text = '<?php echo __('backup_confirm'); ?>';
            confirmBtnColor = '#0d6efd';
            confirmBtnText = '<?php echo __('btn_confirm_yes'); ?>';
            loadingText = '<?php echo __('loading_backup'); ?>';
            form = document.getElementById('createBackupForm');
        } else if (type === 'delete') {
            title = '<?php echo __('confirm_delete_backup'); ?>';
            text = '';
            confirmBtnColor = '#dc3545';
            confirmBtnText = '<?php echo __('btn_confirm_yes'); ?>';
            loadingText = ''; // No loading for delete usually, but we can prevent interaction
            form = document.getElementById('delete_' + formId);
        } else if (type === 'restore') {
            title = '<?php echo __('confirm_restore_title'); ?>';
            text = '<?php echo __('confirm_restore_text'); ?>';
            confirmBtnColor = '#ffc107'; // Warning color
            confirmBtnText = '<?php echo __('btn_confirm_yes'); ?>';
            loadingText = '<?php echo __('loading_restore'); ?>';
            form = document.getElementById('restore_' + formId);
        }

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: confirmBtnColor,
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmBtnText,
            cancelButtonText: '<?php echo __('btn_confirm_cancel'); ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                if (loadingText) {
                    document.getElementById('loadingText').innerText = loadingText;
                    document.getElementById('loadingOverlay').style.display = 'flex';
                }
                form.submit();
            }
        });
    }
</script>
</body>
</html>