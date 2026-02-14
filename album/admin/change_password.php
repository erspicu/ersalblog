<?php
require_once 'auth.php';
if (!isAlbumAdminLoggedIn()) { header('Location: login.php'); exit; }

if ($albumAdminConfig['password'] !== '1234') { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRFRequest();
    
    $newPass = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirmPass = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    if (empty($newPass)) {
        $error = "密碼不能為空";
    } elseif ($newPass === '1234') {
        $error = "請設定一個與預設值不同的密碼以維護安全";
    } elseif ($newPass !== $confirmPass) {
        $error = "兩次輸入的密碼不一致";
    } else {
        $fingerprint = getSystemFingerprint();
        $hashedPassword = password_hash($newPass . $fingerprint, PASSWORD_BCRYPT);
        
        $phpFile = __DIR__ . '/../config/config.php';
        
        // 讀取目前的變數，僅替換密碼
        $album_title = isset($album_title) ? $album_title : "";
        $album_description = isset($album_description) ? $album_description : "";
        $album_introduce = isset($album_introduce) ? $album_introduce : "";
        $album_preview = isset($album_preview) ? $album_preview : "";
        $album_site_url = isset($album_site_url) ? $album_site_url : "";
        $album_lang = isset($album_lang) ? $album_lang : "zh-TW";
        $album_timezone = isset($album_timezone) ? $album_timezone : "Asia/Taipei";
        
        $albumAdminConfig['password'] = $hashedPassword;

        $phpContent = "<?php\n" .
                      "\$albumAdminConfig = " . var_export($albumAdminConfig, true) . ";\n" .
                      "\$album_title = \"" . addslashes($album_title) . "\";\n" .
                      "\$album_description = \"" . addslashes($album_description) . "\";\n" .
                      "\$album_introduce = \"" . addslashes($album_introduce) . "\";\n" .
                      "\$album_preview = \"" . addslashes($album_preview) . "\";\n" .
                      "\$album_site_url = \"" . addslashes($album_site_url) . "\";\n" .
                      "\$album_lang = \"" . addslashes($album_lang) . "\";\n" .
                      "\$album_timezone = \"" . addslashes($album_timezone) . "\";\n" .
                      "date_default_timezone_set(\$album_timezone);\n" .
                      "?>";

        if (file_put_contents($phpFile, $phpContent)) {
            header('Location: index.php?setup_complete=1');
            exit;
        } else {
            $error = "寫入設定檔失敗。";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>安全性初始化 - Album Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .setup-card { width: 100%; max-width: 450px; border: none; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="card setup-card shadow-lg p-4">
        <div class="card-body">
            <h3 class="text-center mb-4"><i class="bi bi-shield-lock-fill text-success"></i> 相簿系統安全性初始化</h3>
            <p class="text-muted small">偵測到預設密碼。請設定新密碼以啟用加密存儲。此操作將結合當前主機特徵碼，提升防護強度。</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger small"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                <div class="mb-3">
                    <label class="form-label fw-bold">新密碼</label>
                    <input type="password" name="new_password" class="form-control" placeholder="請輸入新密碼" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">確認新密碼</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="再次輸入密碼" required>
                </div>
                <button type="submit" class="btn btn-success w-100 py-2 fw-bold">更新並加密</button>
            </form>
        </div>
    </div>
</body>
</html>