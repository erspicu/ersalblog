<?php
require_once 'auth.php';
// 這裡不能用 requireLogin() 因為會造成無限迴圈，我們手動檢查基本登入
if (!isAdminLoggedIn()) { header('Location: login.php'); exit; }

// 如果已經不是 1234 了，就不准待在這個頁面
if ($adminConfig['password'] !== '1234') { header('Location: index.php'); exit; }

$msg = '';
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
        
        $phpFile = __DIR__ . '/../config.php';
        $phpContent = file_get_contents($phpFile);
        
        // 轉義 $ 符號避免 preg_replace 誤認為後向引用
        $replacement = str_replace('$', '\$', $hashedPassword);
        
        // 替換掉明文 1234
        $phpContent = preg_replace("/('password'\s*=>\s*['\"])1234(['\"])/", '${1}' . $replacement . '${2}', $phpContent);
        
        if (file_put_contents($phpFile, $phpContent)) {
            // 重新載入設定
            header('Location: index.php?setup_complete=1');
            exit;
        } else {
            $error = "寫入設定檔失敗，請檢查檔案權限。";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>安全性初始化 - Blog Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .setup-card { width: 100%; max-width: 450px; border: none; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="card setup-card shadow-lg p-4">
        <div class="card-body">
            <h3 class="text-center mb-4"><i class="bi bi-shield-lock-fill text-primary"></i> 安全性初始化</h3>
            <p class="text-muted small">偵測到您目前仍在使用預設密碼。為了確保系統安全，請設定一組新密碼。系統將結合主機特徵碼對您的密碼進行加密存儲。</p>
            
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
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">完成初始化並加密</button>
            </form>
        </div>
    </div>
</body>
</html>