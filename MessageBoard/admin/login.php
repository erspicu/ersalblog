<?php
require_once 'system_helper.php';

$error = '';
$defaultMode = mb_get_default_mode();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    $mode = $_POST['mode'] ?? 'local';

    if ($user === $mb_admin_user && $pass === $mb_admin_pass) {
        $_SESSION['mb_admin_logged_in'] = true;
        $_SESSION['mb_admin_user'] = $user;
        // 如果表單有選模式則用表單的，否則用 config.js 的
        $_SESSION['mb_admin_mode'] = $mode;
        header("Location: index.php");
        exit;
    } else {
        $error = __mb('error_auth');
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title><?php echo __mb('login_title'); ?></title>
    <link href="../../admin/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 400px; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); background: #fff; }
    </style>
</head>
<body>
    <div class="login-card">
        <h3 class="text-center mb-4"><?php echo __mb('admin_title'); ?></h3>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label"><?php echo __mb('label_username'); ?></label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label"><?php echo __mb('label_password'); ?></label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label"><?php echo __mb('label_mode'); ?></label>
                <select name="mode" class="form-select">
                    <option value="local" <?php echo ($defaultMode === 'local' ? 'selected' : ''); ?>><?php echo __mb('opt_sqlite'); ?></option>
                    <option value="gas" <?php echo ($defaultMode === 'gas' ? 'selected' : ''); ?>><?php echo __mb('opt_gas'); ?></option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Language / 語言</label>
                <select class="form-select" onchange="location.href='?lang=' + this.value">
                    <?php foreach(mb_get_available_langs() as $code => $label): ?>
                        <option value="<?php echo $code; ?>" <?php echo (mb_get_lang() === $code ? 'selected' : ''); ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2"><?php echo __mb('btn_login'); ?></button>
        </form>
    </div>
</body>
</html>
