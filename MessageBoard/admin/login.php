<?php
/**
 * MessageBoard Admin Login - 安全強化介面
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
    foreach (file($attempts_log) as $line) {
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
        $error = ($remaining <= 0) ? __mb('error_lockout') : sprintf(__mb('error_auth'), $remaining);
        if ($remaining <= 0) $is_locked = true;
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
        body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .login-card { width: 100%; max-width: 400px; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .brand { text-align: center; font-weight: 800; color: #333; margin-bottom: 1.5rem; letter-spacing: -1px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-end mb-3">
            <select class="form-select form-select-sm d-inline-block w-auto" onchange="location.href='?lang='+this.value">
                <?php foreach($langs as $code => $name): ?>
                    <option value="<?php echo $code; ?>" <?php echo ($current_lang === $code ? 'selected' : ''); ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <h3 class="brand">MessageBoard</h3>
        <?php if($error): ?><div class="alert alert-danger py-2 small"><?php echo $error; ?></div><?php endif; ?>
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
                    <option value="local"><?php echo __mb('opt_sqlite'); ?></option>
                    <option value="gas"><?php echo __mb('opt_gas'); ?></option>
                </select>
            </div>
            <button type="submit" class="btn btn-dark w-100 py-2 fw-bold" <?php echo $is_locked ? 'disabled' : ''; ?>><?php echo __mb('btn_login'); ?></button>
        </form>
        <div class="mt-4 text-center">
            <a href="../../blog.html" class="text-decoration-none text-muted small">&larr; <?php echo __mb('btn_back_to_blog'); ?></a>
        </div>
    </div>
</body>
</html>
