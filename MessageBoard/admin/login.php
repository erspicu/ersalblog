<?php
/**
 * MessageBoard Admin Login - 風格統一版
 */
require_once 'auth.php';

$error = '';
$max_attempts = 5;
$lockout_time = 15 * 60;
$attempts_log = __DIR__ . '/attempts.log';

function getAttempts($ip) {
    global $attempts_log;
    if (!file_exists($attempts_log)) return 0;
    $now = time(); $attempts = 0;
    $lines = @file($attempts_log);
    if (!$lines) return 0;
    foreach ($lines as $line) {
        $parts = explode('|', trim($line));
        if (count($parts) < 2) continue;
        if ($parts[0] === $ip && ($now - $parts[1]) < (15 * 60)) $attempts++;
    }
    return $attempts;
}

function logAttempt($ip) { global $attempts_log; file_put_contents($attempts_log, $ip . '|' . time() . "\n", FILE_APPEND); }
function clearAttempts($ip) {
    global $attempts_log; if (!file_exists($attempts_log)) return;
    $lines = array_filter(file($attempts_log), function($l) use($ip) { return strpos($l, $ip.'|') !== 0; });
    file_put_contents($attempts_log, implode('', $lines));
}

$userIp = $_SERVER['REMOTE_ADDR'];
$attempts = getAttempts($userIp);
$is_locked = ($attempts >= $max_attempts);

// 預設模式採用 config.js 的設定
$default_mode = mb_get_real_mode();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $user = isset($_POST['username']) ? $_POST['username'] : '';
    $pass = isset($_POST['password']) ? $_POST['password'] : '';
    $mode = isset($_POST['mode']) ? $_POST['mode'] : 'local';
    
    if (mb_login($user, $pass, $mode)) {
        clearAttempts($userIp);
        header("Location: index.php"); exit;
    } else {
        logAttempt($userIp);
        $newAttempts = getAttempts($userIp);
        $remaining = $max_attempts - $newAttempts;
        if ($remaining <= 0) {
            $error = __mb('error_lockout');
            $is_locked = true;
        } else {
            $error = sprintf(__mb('error_auth'), $remaining);
        }
    }
}

$current_lang = mb_get_lang();
$langs = mb_get_available_langs();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __mb('login_title'); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <style>
        body { background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { width: 100%; max-width: 400px; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .brand { text-align: center; margin-bottom: 20px; font-weight: bold; color: #333; }
        .lang-switch { margin-bottom: 15px; text-align: right; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="lang-switch">
            <select class="form-select form-select-sm d-inline-block w-auto" onchange="location.href='?lang='+this.value">
                <?php foreach($langs as $code => $name): ?>
                    <option value="<?php echo $code; ?>" <?php echo ($current_lang === $code ? 'selected' : ''); ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <h3 class="brand">MessageBoard</h3>
        
        <?php if($error): ?>
            <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold"><?php echo __mb('label_username'); ?></label>
                <input type="text" name="username" class="form-control" required autofocus <?php echo $is_locked ? 'disabled' : ''; ?>>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold"><?php echo __mb('label_password'); ?></label>
                <input type="password" name="password" class="form-control" required <?php echo $is_locked ? 'disabled' : ''; ?>>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold"><?php echo __mb('label_mode'); ?></label>
                <select name="mode" class="form-select" <?php echo $is_locked ? 'disabled' : ''; ?>>
                    <option value="local" <?php echo ($default_mode === 'local' ? 'selected' : ''); ?>><?php echo __mb('opt_sqlite'); ?></option>
                    <option value="gas" <?php echo ($default_mode === 'gas' ? 'selected' : ''); ?>><?php echo __mb('opt_gas'); ?></option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100" <?php echo $is_locked ? 'disabled' : ''; ?>><?php echo __mb('btn_login'); ?></button>
        </form>
        
        <div class="mt-3 text-center">
            <a href="../../blog.html" class="text-decoration-none text-secondary small">&larr; <?php echo __mb('btn_back_to_blog'); ?></a>
        </div>
    </div>
</body>
</html>
