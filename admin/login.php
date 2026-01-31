<?php
require_once 'auth.php';
require_once 'health_check.php';

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
        $error = '選定的管理模式目前不可用，請檢查系統環境。';
    } elseif (login($username, $password, $dataSource)) {
        header('Location: index.php');
        exit;
    } else {
        $error = '帳號或密碼錯誤';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BaxerMux Blog Admin Login</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { width: 100%; max-width: 400px; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .brand { text-align: center; margin-bottom: 20px; font-weight: bold; color: #333; }
        .status-msg { font-size: 0.85em; margin-top: 5px; }
    </style>
</head>
<body>

<div class="login-card">
    <h3 class="brand">Blog 後台管理</h3>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="username" class="form-label">帳號</label>
            <input type="text" class="form-control" id="username" name="username" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">密碼</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="data_source" class="form-label">管理模式</label>
            <select class="form-select" id="data_source" name="data_source">
                <option value="db">資料庫 (Database)</option>
                <option value="file">檔案系統 (File System)</option>
            </select>
            <div id="source_status" class="status-msg"></div>
        </div>
        <button type="submit" id="login_btn" class="btn btn-primary w-100">登入</button>
    </form>
    
    <div class="mt-3 text-center">
        <a href="../blog.html" class="text-decoration-none text-secondary">&larr; 回到部落格首頁</a>
    </div>
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

    function updateStatus() {
        var mode = sourceSelect.value;
        var info = statusData[mode];
        
        if (info.status) {
            statusDiv.innerHTML = '<span class="text-success">✅ ' + info.message + '</span>';
            loginBtn.disabled = false;
        } else {
            statusDiv.innerHTML = '<span class="text-danger">❌ ' + info.message + '</span>';
            loginBtn.disabled = true;
        }
    }

    // Init and Listener
    sourceSelect.addEventListener('change', updateStatus);
    updateStatus(); // Run once on load
</script>

</body>
</html>
