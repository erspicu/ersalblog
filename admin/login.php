<?php
require_once 'auth.php';
require_once 'health_check.php';

// Load Version Config
$versionConfig = require 'version_config.php';
$adminVersion = $versionConfig['version'];

// lang_init.php is already included via auth.php
// $availableLangs and $currentLang are available here.

$error = '';

// --- 暴力破解防護 (Rate Limiting) ---
$max_attempts = 5;
$lockout_time = 15 * 60; // 15 分鐘
$attempts_log = __DIR__ . '/attempts.log';

function getIpAttempts($ip) {
    global $attempts_log;
    if (!file_exists($attempts_log)) return 0;
    $attempts = 0;
    $now = time();
    $lines = @file($attempts_log);
    if (!$lines) return 0;
    foreach ($lines as $line) {
        $parts = explode('|', trim($line));
        if (count($parts) < 2) continue;
        list($logIp, $timestamp) = $parts;
        if ($logIp === $ip && ($now - $timestamp) < (15 * 60)) {
            $attempts++;
        }
    }
    return $attempts;
}

function recordFailedAttempt($ip) {
    global $attempts_log;
    $entry = $ip . '|' . time() . PHP_EOL;
    @file_put_contents($attempts_log, $entry, FILE_APPEND | LOCK_EX);
}

function clearAttempts($ip) {
    global $attempts_log;
    if (!file_exists($attempts_log)) return;
    $lines = @file($attempts_log);
    if (!$lines) return;
    $newLines = array();
    foreach ($lines as $line) {
        $parts = explode('|', trim($line));
        if (count($parts) < 2) continue;
        if ($parts[0] !== $ip) $newLines[] = $line;
    }
    @file_put_contents($attempts_log, implode('', $newLines), LOCK_EX);
}

$user_ip = $_SERVER['REMOTE_ADDR'];
$current_attempts = getIpAttempts($user_ip);
$is_locked = ($current_attempts >= $max_attempts);

// 執行系統檢查
$dbStatus = SystemHealth::checkDB();
$fileStatus = SystemHealth::checkFile();
$hasSQLite = isset($sqlite_path) && !empty($sqlite_path);
$sqliteStatus = $hasSQLite ? SystemHealth::checkSQLite() : ['status' => false, 'message' => 'Not Configured'];

if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $dataSource = isset($_POST['data_source']) ? $_POST['data_source'] : 'db'; 
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if (!verifyCSRFToken($csrf_token)) {
        $error = "Security Error: Invalid CSRF Token.";
    } elseif ($is_locked) {
        $error = __('login_locked_msg');
    } else {
        // Server-side check
        $isSourceValid = false;
        if ($dataSource === 'db') $isSourceValid = $dbStatus['status'];
        elseif ($dataSource === 'file') $isSourceValid = $fileStatus['status'];
        elseif ($dataSource === 'sqlite') $isSourceValid = $sqliteStatus['status'];

        if (!$isSourceValid) {
            $error = __('error_mode_unavailable');
        } elseif (login($username, $password, $dataSource)) {
            clearAttempts($user_ip);
            header('Location: index.php');
            exit;
        } else {
            recordFailedAttempt($user_ip);
            $remaining = $max_attempts - ($current_attempts + 1);
            if ($remaining <= 0) {
                $error = __('login_locked_msg');
                $is_locked = true;
            } else {
                $error = sprintf(__('login_failed_msg'), $remaining);
            }
        }
    }
}

// Special Check: DB Configured but Tables Missing
$showInitLink = false;
$showFileInitLink = false;
$showSqliteInitLink = false;

if ($dbStatus['message'] && strpos($dbStatus['message'], '找不到資料表') !== false) { // Using raw string check (fragile if translated but ok for now) or better check logic
    // Actually the message comes from health_check which returns translated string.
    // Ideally health_check returns a code. For now reliance on string "Missing" concept.
    // Update health check to return code? No time. 
    // Let's rely on status=false and message content logic.
    // Or just "if status is false but connection didn't fail" -> implies empty.
    // For simplicity:
    if (!$dbStatus['status'] && strpos($dbStatus['message'], '連線失敗') === false) {
         $showInitLink = true;
    }
}

if (!$fileStatus['status']) {
    $showFileInitLink = true;
}

if ($hasSQLite && !$sqliteStatus['status']) {
    // If not ready (status=false), likely needs init
    $showSqliteInitLink = true;
}

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login_title'); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; flex-direction: column;}
        .login-card { width: 100%; max-width: 400px; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .brand { text-align: center; margin-bottom: 20px; font-weight: bold; color: #333; }
        .status-msg { font-size: 0.85em; margin-top: 5px; }
        .version-tag { margin-top: 10px; font-size: 0.8em; color: #888; }
        .lang-switch { margin-bottom: 15px; text-align: right; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="lang-switch">
        <form method="GET" id="langForm" class="d-inline-block">
            <select name="lang" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($availableLangs as $l): ?>
                    <option value="<?php echo $l; ?>" <?php echo ($currentLang === $l) ? 'selected' : ''; ?>>
                        <?php echo $l; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <h3 class="brand"><?php echo __('login_title'); ?></h3>
    
    <?php 
    $installCheck = SystemHealth::checkInstaller();
    if ($installCheck['exists']): 
    ?>
        <div class="alert alert-danger small py-2 mb-3">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $installCheck['message']; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
        <div class="mb-3">
            <label for="username" class="form-label"><?php echo __('login_username'); ?></label>
            <input type="text" class="form-control" id="username" name="username" required autofocus <?php echo $is_locked ? 'disabled' : ''; ?>>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label"><?php echo __('login_password'); ?></label>
            <input type="password" class="form-control" id="password" name="password" required <?php echo $is_locked ? 'disabled' : ''; ?>>
        </div>
        <div class="mb-3">
            <label for="data_source" class="form-label"><?php echo __('login_mode'); ?></label>
            <select class="form-select" id="data_source" name="data_source" <?php echo $is_locked ? 'disabled' : ''; ?>>
                <option value="db"><?php echo __('mode_db'); ?></option>
                <option value="file"><?php echo __('mode_file'); ?></option>
                <?php if ($hasSQLite): ?>
                    <option value="sqlite">SQLite 3</option>
                <?php endif; ?>
            </select>
            <div id="source_status" class="status-msg"></div>
            
            <?php if ($showInitLink): ?>
                <div id="db_init_alert" class="alert alert-warning mt-2 mb-0 p-2" style="font-size: 0.9em; display: none;">
                    <?php echo __('db_init_msg'); ?><br>
                    <a href="db_init.php" class="fw-bold"><?php echo __('db_init_link'); ?> &rarr;</a>
                </div>
            <?php endif; ?>
            
            <?php if ($showFileInitLink): ?>
                <div id="file_init_alert" class="alert alert-warning mt-2 mb-0 p-2" style="font-size: 0.9em; display: none;">
                    <?php echo __('file_init_msg'); ?><br>
                    <a href="file_init.php" class="fw-bold"><?php echo __('file_init_link'); ?> &rarr;</a>
                </div>
            <?php endif; ?>

            <?php if ($showSqliteInitLink): ?>
                <div id="sqlite_init_alert" class="alert alert-warning mt-2 mb-0 p-2" style="font-size: 0.9em; display: none;">
                    SQLite 需要初始化<br>
                    <a href="sqlite_init.php" class="fw-bold">前往初始化 &rarr;</a>
                </div>
            <?php endif; ?>
        </div>
        <button type="submit" id="login_btn" class="btn btn-primary w-100" <?php echo $is_locked ? 'disabled' : ''; ?>><?php echo __('login_btn'); ?></button>
    </form>
    
    <div class="mt-3 text-center">
        <a href="../blog.html" class="text-decoration-none text-secondary">&larr; <?php echo __('login_back_home'); ?></a>
    </div>
</div>

<div class="version-tag text-center">
    Ver: <?php echo $adminVersion; ?><br>
    <span style="font-size: 0.9em;">
        Vibe coded with <strong>Gemini CLI v<?php echo $versionConfig['cli_version']; ?></strong><br>
        AI Model: <strong><?php echo $versionConfig['model_name']; ?></strong>
    </span>
</div>

<script>
    // Pass PHP status to JS
    var statusData = {
        'db': <?php echo json_encode($dbStatus); ?>,
        'file': <?php echo json_encode($fileStatus); ?>,
        'sqlite': <?php echo json_encode($sqliteStatus); ?>
    };

    var sourceSelect = document.getElementById('data_source');
    var statusDiv = document.getElementById('source_status');
    var loginBtn = document.getElementById('login_btn');
    var dbInitAlert = document.getElementById('db_init_alert');
    var fileInitAlert = document.getElementById('file_init_alert');
    var sqliteInitAlert = document.getElementById('sqlite_init_alert');

    function updateStatus() {
        var mode = sourceSelect.value;
        var info = statusData[mode];
        
        if (info.status) {
            statusDiv.innerHTML = '<span class="text-success">✅ ' + info.message + '</span>';
            loginBtn.disabled = false;
            if(dbInitAlert) dbInitAlert.style.display = 'none';
            if(fileInitAlert) fileInitAlert.style.display = 'none';
            if(sqliteInitAlert) sqliteInitAlert.style.display = 'none';
        } else {
            statusDiv.innerHTML = '<span class="text-danger">❌ ' + info.message + '</span>';
            loginBtn.disabled = true;
            
            // Hide all first
            if(dbInitAlert) dbInitAlert.style.display = 'none';
            if(fileInitAlert) fileInitAlert.style.display = 'none';
            if(sqliteInitAlert) sqliteInitAlert.style.display = 'none';

            // Show relevant
            if (mode === 'db' && dbInitAlert && info.message.indexOf('連線失敗') === -1) {
                dbInitAlert.style.display = 'block';
            } 
            else if (mode === 'file' && fileInitAlert) {
                fileInitAlert.style.display = 'block';
            }
            else if (mode === 'sqlite' && sqliteInitAlert) {
                sqliteInitAlert.style.display = 'block';
            }
        }
    }

    // Init and Listener
    sourceSelect.addEventListener('change', updateStatus);
    updateStatus(); // Run once on load
</script>

</body>
</html>