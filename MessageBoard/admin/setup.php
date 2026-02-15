<?php
/**
 * MessageBoard Admin Setup - PHP 5.x Compatible
 */
require_once 'auth.php';
mb_require_login();

$mode = $_SESSION['mb_admin_mode'];
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$success_msg = ''; $error = ''; 
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($msg === 'force_change') $error = "為了安全性，請先更換初始密碼 1234。";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsPath = __DIR__ . '/../config/config.js';
    $phpPath = __DIR__ . '/../config/config.php';
    
    if ($action === 'save_global') {
        $content = file_exists($jsPath) ? file_get_contents($jsPath) : '';
        $newMode = isset($_POST['mb_mode']) ? $_POST['mb_mode'] : 'local';
        $newTheme = isset($_POST['mb_theme']) ? $_POST['mb_theme'] : 'default';
        $newLang = isset($_POST['mb_lang_plugin']) ? $_POST['mb_lang_plugin'] : 'zh_TW';
        $newPerPage = isset($_POST['mb_per_page']) ? (int)$_POST['mb_per_page'] : 5;
        
        $content = preg_replace("/mode:\s*'[^']*'/", "mode: '$newMode'", $content);
        $content = preg_replace("/theme:\s*'[^']*'/", "theme: '$newTheme'", $content);
        $content = preg_replace("/lang:\s*'[^']*'/", "lang: '$newLang'", $content);
        $content = preg_replace("/per_page:\s*\d+/", "per_page: $newPerPage", $content);
        
        if (file_put_contents($jsPath, $content)) { 
            $success_msg = "Settings updated!"; 
            $_SESSION['mb_admin_mode'] = $newMode; 
        } else { $error = "Write failed"; }
        
    } elseif ($action === 'save_gas') {
        $content = file_exists($jsPath) ? file_get_contents($jsPath) : '';
        $newUrl = isset($_POST['gas_url']) ? $_POST['gas_url'] : '';
        if (strpos($newUrl, 'https://script.google.com') === 0) {
            $content = preg_replace("/gas_url:\s*'[^']*'/", "gas_url: '$newUrl'", $content);
            file_put_contents($jsPath, $content); $success_msg = "GAS URL updated!";
        } else { $error = "Invalid URL"; }
        
    } elseif ($action === 'save_account') {
        $newUsername = isset($_POST['new_username']) ? trim($_POST['new_username']) : 'admin';
        $newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
        
        $finalPassword = $mb_admin_pass;
        if (!empty($newPassword)) {
            $finalPassword = password_hash($newPassword . getMBSystemFingerprint(), PASSWORD_BCRYPT);
        }

        $newPhpContent = "<?php\n"
                       . "/**\n"
                       . " * MessageBoard Service - PHP Configuration\n"
                       . " */\n\n"
                       . "// 管理員帳號設定\n"
                       . "\$mb_admin_user = '" . addslashes($newUsername) . "';\n"
                       . "\$mb_admin_pass = '" . addslashes($finalPassword) . "';\n";

        if (file_put_contents($phpPath, $newPhpContent)) {
            $success_msg = __mb('msg_account_updated');
            $_SESSION['mb_admin_user'] = $newUsername;
            $mb_admin_user = $newUsername;
            $mb_admin_pass = $finalPassword;
        } else { $error = "Failed to write config.php"; }
    }
}

// 重新讀取 JS 設定
$currentGasUrl = ''; $currentMode = 'local'; $currentTheme = 'default'; $currentLang = 'zh_TW'; $currentPerPage = 5;
$js_file = __DIR__ . '/../config/config.js';
if (file_exists($js_file)) {
    $c = file_get_contents($js_file);
    if (preg_match("/mode:\s*'([^']+)'/", $c, $m)) $currentMode = $m[1];
    if (preg_match("/theme:\s*'([^']+)'/", $c, $m)) $currentTheme = $m[1];
    if (preg_match("/lang:\s*'([^']+)'/", $c, $m)) $currentLang = $m[1];
    if (preg_match("/per_page:\s*(\d+)/", $c, $m)) $currentPerPage = (int)$m[1];
    if (preg_match("/gas_url:\s*'([^']+)'/", $c, $m)) $currentGasUrl = $m[1];
}

function get_plugin_langs() {
    $langs = array(); 
    $files = glob(__DIR__ . '/../langs/plugin-*.js');
    foreach ($files as $f) { if (preg_match('/plugin-([^.]+)\.js$/', basename($f), $m)) $langs[] = $m[1]; }
    return $langs;
}
$diagnostics = mb_get_env_diagnostics();
?>
<!DOCTYPE html>
<html lang="<?php echo mb_get_lang(); ?>">
<head>
    <meta charset="UTF-8"><title><?php echo __mb('menu_settings'); ?> - MB Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <style>.diag-item { border-bottom: 1px solid #e9ecef; padding: 12px 0; } .diag-item:last-child { border-bottom: none; } .main-content { background-color: #f8f9fa; }</style>
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar_inc.php'; ?>
        <div class="main-content">
            <h2 class="mb-4"><?php echo __mb('menu_settings'); ?></h2>
            <?php if($success_msg): ?><div class="alert alert-success shadow-sm"><?php echo $success_msg; ?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger shadow-sm"><?php echo $error; ?></div><?php endif; ?>
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold py-3"><i class="bi bi-globe2 me-2"></i><?php echo __mb('section_global'); ?></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_global">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold"><?php echo __mb('label_mode'); ?></label>
                                    <select name="mb_mode" class="form-select form-select-sm">
                                        <option value="local" <?php echo ($currentMode === 'local' ? 'selected' : ''); ?>><?php echo __mb('opt_sqlite'); ?></option>
                                        <option value="gas" <?php echo ($currentMode === 'gas' ? 'selected' : ''); ?>><?php echo __mb('opt_gas'); ?></option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold"><?php echo __mb('label_theme'); ?></label>
                                    <select name="mb_theme" class="form-select form-select-sm">
                                        <option value="default" <?php echo ($currentTheme === 'default' ? 'selected' : ''); ?>><?php echo __mb('theme_default'); ?></option>
                                        <option value="dark" <?php echo ($currentTheme === 'dark' ? 'selected' : ''); ?>><?php echo __mb('theme_dark'); ?></option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold"><?php echo __mb('label_plugin_lang'); ?></label>
                                    <select name="mb_lang_plugin" class="form-select form-select-sm">
                                        <?php foreach(get_plugin_langs() as $l): ?><option value="<?php echo $l; ?>" <?php echo ($currentLang === $l ? 'selected' : ''); ?>><?php echo $l; ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold"><?php echo __mb('label_per_page'); ?></label>
                                    <input type="number" name="mb_per_page" class="form-control form-control-sm" value="<?php echo $currentPerPage; ?>" min="1" max="50">
                                </div>
                                <button type="submit" class="btn btn-dark btn-sm w-100"><?php echo __mb('label_save_global'); ?></button>
                            </form>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold py-3"><i class="bi bi-shield-check me-2"></i><?php echo __mb('section_env_diag'); ?></div>
                        <div class="card-body">
                            <?php foreach($diagnostics as $d): ?>
                                <div class="diag-item">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="fw-bold"><?php echo $d['label']; ?></small>
                                        <span class="badge <?php echo $d['pass'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $d['pass'] ? __mb('diag_pass') : __mb('diag_fail'); ?>
                                        </span>
                                    </div>
                                    <div class="small text-muted"><?php echo $d['value']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold py-3"><i class="bi bi-person-lock me-2"></i><?php echo __mb('label_admin_account'); ?></div>
                        <div class="card-body py-4">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_account">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small fw-bold"><?php echo __mb('label_new_username'); ?></label>
                                        <input type="text" name="new_username" class="form-control" value="<?php echo htmlspecialchars($mb_admin_user); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small fw-bold"><?php echo __mb('label_new_password'); ?></label>
                                        <input type="password" name="new_password" class="form-control" placeholder="<?php echo __mb('hint_password_keep'); ?>">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-warning mt-2 px-4" onclick="return confirm('確定要更新帳號密碼嗎？');"><?php echo __mb('btn_save_account'); ?></button>
                            </form>
                        </div>
                    </div>
                    <?php if ($currentMode === 'gas'): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold py-3"><i class="bi bi-google me-2"></i><?php echo __mb('section_gas_config'); ?></div>
                        <div class="card-body py-4">
                            <form method="POST"><input type="hidden" name="action" value="save_gas">
                                <div class="mb-3"><label class="form-label small fw-bold">GAS Web App URL</label>
                                    <div class="input-group mb-3">
                                        <input type="url" name="gas_url" id="gas_url_input" class="form-control" value="<?php echo htmlspecialchars($currentGasUrl); ?>" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="pasteToInput('gas_url_input')"><i class="bi bi-clipboard-plus"></i> Paste</button>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-2"><?php echo __mb('label_save_gas'); ?></button>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card border-0 shadow-sm mb-4 bg-white"><div class="card-body p-5 text-center">
                        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                        <h4 class="mt-3"><?php echo __mb('status_ready'); ?></h4>
                        <p class="text-muted"><?php echo __mb('status_ready_desc'); ?></p>
                    </div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>async function pasteToInput(inputId) { try { const text = await navigator.clipboard.readText(); document.getElementById(inputId).value = text; } catch (err) { alert('Clipboard error'); } }</script>
</body>
</html>
