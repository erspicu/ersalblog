<?php
require_once 'system_helper.php';
mb_require_login();

$mode = $_SESSION['mb_admin_mode'];
$msg = ''; $error = ''; $action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsPath = __DIR__ . '/../config/config.js';
    $content = file_exists($jsPath) ? file_get_contents($jsPath) : '';

    if ($action === 'save_global') {
        $newMode = $_POST['mb_mode'] ?? 'local';
        $newTheme = $_POST['mb_theme'] ?? 'default';
        $newLang = $_POST['mb_lang_plugin'] ?? 'zh_TW';
        $newPerPage = (int)($_POST['mb_per_page'] ?? 5);

        $content = preg_replace("/mode:\s*'[^']*'/", "mode: '$newMode'", $content);
        $content = preg_replace("/theme:\s*'[^']*'/", "theme: '$newTheme'", $content);
        $content = preg_replace("/lang:\s*'[^']*'/", "lang: '$newLang'", $content);
        $content = preg_replace("/per_page:\s*\d+/", "per_page: $newPerPage", $content);

        if (file_put_contents($jsPath, $content)) {
            $msg = "全域設定已更新！";
            $_SESSION['mb_admin_mode'] = $newMode;
        } else { $error = "設定檔寫入失敗"; }
    } elseif ($action === 'save_gas') {
        $newUrl = $_POST['gas_url'] ?? '';
        if (strpos($newUrl, 'https://script.google.com') === 0) {
            $content = preg_replace("/gas_url:\s*'[^']*'/", "gas_url: '$newUrl'", $content);
            file_put_contents($jsPath, $content); $msg = "GAS 服務網址已更新！";
        } else { $error = "網址格式不正確"; }
    }
}

// 重新讀取設定
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
    $langs = [];
    $files = glob(__DIR__ . '/../langs/plugin-*.js');
    foreach ($files as $f) {
        if (preg_match('/plugin-([^.]+)\.js$/', basename($f), $m)) $langs[] = $m[1];
    }
    return $langs;
}

$diagnostics = mb_get_env_diagnostics();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>系統設定 - MB Admin</title>
    <link href="../../admin/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .diag-item { border-bottom: 1px solid #e9ecef; padding: 12px 0; }
        .diag-item:last-child { border-bottom: none; }
        .main-content { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar_inc.php'; ?>
        <div class="main-content">
            <h2 class="mb-4">系統環境與設定</h2>
            <?php if($msg): ?><div class="alert alert-success shadow-sm"><?php echo $msg; ?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger shadow-sm"><?php echo $error; ?></div><?php endif; ?>
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold py-3"><i class="bi bi-globe2 me-2"></i>全域運行設定</div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_global">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">儲存模式</label>
                                    <select name="mb_mode" class="form-select form-select-sm">
                                        <option value="local" <?php echo ($currentMode === 'local' ? 'selected' : ''); ?>>本地 SQLite</option>
                                        <option value="gas" <?php echo ($currentMode === 'gas' ? 'selected' : ''); ?>>Google 試算表 (GAS)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">CSS 主題</label>
                                    <select name="mb_theme" class="form-select form-select-sm">
                                        <option value="default" <?php echo ($currentTheme === 'default' ? 'selected' : ''); ?>>預設明亮 (Default)</option>
                                        <option value="dark" <?php echo ($currentTheme === 'dark' ? 'selected' : ''); ?>>深色模式 (Dark)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">外掛預設語系</label>
                                    <select name="mb_lang_plugin" class="form-select form-select-sm">
                                        <?php foreach(get_plugin_langs() as $l): ?>
                                            <option value="<?php echo $l; ?>" <?php echo ($currentLang === $l ? 'selected' : ''); ?>><?php echo $l; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">每頁主題筆數</label>
                                    <input type="number" name="mb_per_page" class="form-control form-control-sm" value="<?php echo $currentPerPage; ?>" min="1" max="50">
                                </div>
                                <button type="submit" class="btn btn-dark btn-sm w-100">套用全域設定</button>
                            </form>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold py-3"><i class="bi bi-shield-check me-2"></i>環境診斷</div>
                        <div class="card-body">
                            <?php foreach($diagnostics as $d): ?>
                                <div class="diag-item">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="fw-bold"><?php echo $d['label']; ?></small>
                                        <span class="badge <?php echo $d['pass'] ? 'bg-success' : 'bg-danger'; ?>">Pass</span>
                                    </div>
                                    <div class="small text-muted"><?php echo $d['value']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <?php if ($currentMode === 'gas'): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold py-3"><i class="bi bi-google me-2"></i>GAS 雲端儲存配置</div>
                        <div class="card-body py-4">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_gas">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">GAS Web App URL</label>
                                    <input type="url" name="gas_url" class="form-control" value="<?php echo htmlspecialchars($currentGasUrl); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary mt-2">儲存雲端網址</button>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card border-0 shadow-sm mb-4 bg-white">
                        <div class="card-body p-5 text-center">
                            <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">SQLite 模式已就緒</h4>
                            <p class="text-muted">本系統採用動態資料庫技術。當新的網站或頁面有留言產生時，系統會自動在 <code>data/</code> 目錄下建立對應的資料夾與資料庫檔案。</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="../../admin/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
