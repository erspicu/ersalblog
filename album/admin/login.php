<?php
require_once 'auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (albumLogin($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = '帳號或密碼錯誤';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>相簿後台登入</title>
    <!-- 使用與 Blog Admin 相同的 Bootstrap -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { width: 100%; max-width: 400px; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); background: white; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="mb-3">
            <a href="../album.html" class="text-decoration-none small text-secondary">&larr; 回到相簿首頁</a>
        </div>
        <h3 class="text-center mb-4 fw-bold">Album Admin</h3>
        <?php if (file_exists(__DIR__ . '/../install.php')): ?>
            <div class="alert alert-warning py-2 small fw-bold">
                ⚠️ 安全警告：install.php 檔案仍然存在！請在安裝完成後立即刪除它，以防止系統被他人惡意重新安裝。
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">帳號</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">密碼</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">登入</button>
        </form>
        <div class="text-center mt-3">
            <a href="../../admin/login.php" class="text-muted small">前往 Blog 後台</a>
        </div>
    </div>
</body>
</html>
