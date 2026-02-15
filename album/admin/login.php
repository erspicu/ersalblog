<?php
require_once 'auth.php';

$error = '';
$max_attempts = 5;
$lockout_time = 15 * 60; // 15 分鐘
$attempts_log = __DIR__ . '/attempts.log';

/**
 * 獲取 IP 失敗次數
 */
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

/**
 * 記錄一次失敗
 */
function logAttempt($ip) {
    global $attempts_log;
    $line = $ip . '|' . time() . "\n";
    file_put_contents($attempts_log, $line, FILE_APPEND);
}

/**
 * 清除失敗紀錄
 */
function clearAttempts($ip) {
    global $attempts_log;
    if (!file_exists($attempts_log)) return;
    $lines = file($attempts_log);
    $newLines = [];
    foreach ($lines as $line) {
        if (strpos($line, $ip . '|') !== 0) $newLines[] = $line;
    }
    file_put_contents($attempts_log, implode('', $newLines));
}

$userIp = $_SERVER['REMOTE_ADDR'];
$attempts = getIpAttempts($userIp);
$is_locked = ($attempts >= $max_attempts);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (albumLogin($username, $password)) {
        clearAttempts($userIp);
        header('Location: index.php');
        exit;
    } else {
        logAttempt($userIp);
        $newAttempts = getIpAttempts($userIp);
        $remaining = $max_attempts - $newAttempts;
        if ($remaining <= 0) {
            $error = __('error_lockout');
            $is_locked = true;
        } else {
            $error = sprintf(__('error_login'), $remaining);
        }
    }
} elseif ($is_locked) {
    $error = __('error_lockout');
}
?>
<!DOCTYPE html>
<html lang="<?php echo getWebLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login_title'); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { width: 100%; max-width: 400px; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); background: white; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="mb-3">
            <a href="../album.html" class="text-decoration-none small text-secondary">&larr; <?php echo __('back_to_home'); ?></a>
        </div>
        <h3 class="text-center mb-4 fw-bold">Album Admin</h3>
        <?php if (file_exists(__DIR__ . '/../install.php')): ?>
            <div class="alert alert-warning py-2 small fw-bold">
                ⚠️ <?php echo __('security_warning'); ?> <?php echo __('install_exists'); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label small fw-bold"><?php echo __('username'); ?></label>
                <input type="text" name="username" class="form-control" required autofocus <?php echo $is_locked ? 'disabled' : ''; ?>>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold"><?php echo __('password'); ?></label>
                <input type="password" name="password" class="form-control" required <?php echo $is_locked ? 'disabled' : ''; ?>>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold"><?php echo __('language'); ?></label>
                <select name="lang" id="lang-selector" class="form-select form-select-sm" <?php echo $is_locked ? 'disabled' : ''; ?>>
                    <?php 
                    $adminLangs = getAvailableLangs('admin-');
                    foreach ($adminLangs as $code => $name): 
                        $webCode = str_replace('_', '-', $code);
                    ?>
                        <option value="<?php echo $webCode; ?>" <?php echo ($currentLang == $code ? 'selected' : ''); ?>><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold" <?php echo $is_locked ? 'disabled' : ''; ?>><?php echo __('login_btn'); ?></button>
        </form>
        <div class="text-center mt-3">
            <a href="../../admin/login.php" class="text-muted small"><?php echo __('go_to_blog'); ?></a>
        </div>
    </div>

    <script>
    document.getElementById('lang-selector').addEventListener('change', function() {
        const lang = this.value;
        window.location.href = '?lang=' + lang;
    });
    </script>
</body>
</html>
