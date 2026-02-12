<?php
require_once 'auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    // 語系已透過 auth.php 的 GET 或之前的 Session 設定

    if (albumLogin($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = __('error_login');
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
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
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label"><?php echo __('username'); ?></label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label"><?php echo __('password'); ?></label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><?php echo __('language'); ?></label>
                <select name="lang" id="lang-selector" class="form-select">
                    <option value="zh_TW" <?php echo ($currentLang == 'zh_TW' ? 'selected' : ''); ?>>繁體中文 (zh_TW)</option>
                    <option value="en_US" <?php echo ($currentLang == 'en_US' ? 'selected' : ''); ?>>English (en_US)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100"><?php echo __('login_btn'); ?></button>
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
