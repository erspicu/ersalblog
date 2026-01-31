<?php
require_once 'auth.php';
require_once 'health_check.php';

// Load Version Config
$versionConfig = require 'version_config.php';
$adminVersion = $versionConfig['version'];

// lang_init.php is already included via auth.php
// $availableLangs and $currentLang are available here.

$error = '';

// 執行系統檢查
$dbStatus = SystemHealth::checkDB();
$fileStatus = SystemHealth::checkFile();

if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $dataSource = $_POST['data_source'] ?? 'db'; 

    // Server-side check
    $isSourceValid = ($dataSource === 'db') ? $dbStatus['status'] : $fileStatus['status'];

    if (!$isSourceValid) {
        $error = __('error_mode_unavailable');
    } elseif (login($username, $password, $dataSource)) {
        header('Location: index.php');
        exit;
    } else {
        $error = __('error_auth_fail');
    }
}

// Special Check: DB Configured but Tables Missing
$showInitLink = false;
$showFileInitLink = false;

if ($dbStatus['message'] && strpos($dbStatus['message'], '找不到資料表') !== false) {
    $showInitLink = true;
}

if (!$fileStatus['status']) {
    $showFileInitLink = true;
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
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="username" class="form-label"><?php echo __('login_username'); ?></label>
            <input type="text" class="form-control" id="username" name="username" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label"><?php echo __('login_password'); ?></label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="data_source" class="form-label"><?php echo __('login_mode'); ?></label>
            <select class="form-select" id="data_source" name="data_source">
                <option value="db"><?php echo __('mode_db'); ?></option>
                <option value="file"><?php echo __('mode_file'); ?></option>
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
        </div>
        <button type="submit" id="login_btn" class="btn btn-primary w-100"><?php echo __('login_btn'); ?></button>
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
        'file': <?php echo json_encode($fileStatus); ?>
    };

    var sourceSelect = document.getElementById('data_source');
    var statusDiv = document.getElementById('source_status');
    var loginBtn = document.getElementById('login_btn');
    var dbInitAlert = document.getElementById('db_init_alert');
    var fileInitAlert = document.getElementById('file_init_alert');

    function updateStatus() {
        var mode = sourceSelect.value;
        var info = statusData[mode];
        
        if (info.status) {
            // Check mark doesn't need translation, but message comes from PHP health_check.php which is not yet localized. 
            // Ideally health_check.php messages should also be localized, but for now we keep it as is.
            statusDiv.innerHTML = '<span class="text-success">✅ ' + info.message + '</span>';
            loginBtn.disabled = false;
            if(dbInitAlert) dbInitAlert.style.display = 'none';
            if(fileInitAlert) fileInitAlert.style.display = 'none';
        } else {
            statusDiv.innerHTML = '<span class="text-danger">❌ ' + info.message + '</span>';
            loginBtn.disabled = true;
            
            // Show Init Link logic matches PHP conditions
            if (mode === 'db' && info.message.indexOf('找不到資料表') !== -1 && dbInitAlert) {
                dbInitAlert.style.display = 'block';
                if(fileInitAlert) fileInitAlert.style.display = 'none';
            } 
            else if (mode === 'file' && fileInitAlert) {
                fileInitAlert.style.display = 'block';
                if(dbInitAlert) dbInitAlert.style.display = 'none';
            }
            else {
                if(dbInitAlert) dbInitAlert.style.display = 'none';
                if(fileInitAlert) fileInitAlert.style.display = 'none';
            }
        }
    }

    // Init and Listener
    sourceSelect.addEventListener('change', updateStatus);
    updateStatus(); // Run once on load
</script>

</body>
</html>