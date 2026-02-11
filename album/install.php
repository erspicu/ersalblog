<?php
/**
 * Baxermux Album Service Installation Wizard
 */

$available_langs = array('zh_TW' => '繁體中文', 'en_US' => 'English');
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['album_lang']) ? $_COOKIE['album_lang'] : 'zh_TW');
if (!isset($available_langs[$lang])) $lang = 'zh_TW';
setcookie('album_lang', $lang, time() + 86400 * 30, "/");

$lang_file = __DIR__ . "/langs/install_{$lang}.php";
$t = file_exists($lang_file) ? include $lang_file : array();
function _t($key) { global $t; return isset($t[$key]) ? $t[$key] : $key; }

// 如果已經安裝且鎖定 (這裡簡單檢查 config.php 是否存在且非 example)
$isInstalled = file_exists(__DIR__ . '/config/config.php');

if (isset($_GET['action']) && $_GET['action'] === 'install') {
    header('Content-Type: application/json');
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    $title = $_POST['title'];

    try {
        // 1. 建立目錄
        $dirs = array('config', 'Collection', 'api/json', 'static/themes');
        foreach ($dirs as $d) {
            $path = __DIR__ . '/' . $d;
            if (!file_exists($path)) mkdir($path, 0777, true);
        }

        // 2. 產生 config.php
        $configPhp = "<?php
// Album Service Configuration
\$albumAdminConfig = array(
    'username' => '" . addslashes($user) . "',
    'password' => '" . addslashes($pass) . "',
    'site_title' => '" . addslashes($title) . "'
);
?>";
        file_put_contents(__DIR__ . '/config/config.php', $configPhp);

        // 3. 產生 config.js
        $configJs = "/**
 * Baxermux Album Configuration
 */
const albumConfig = {
    theme: 'album',
    api_type: 'json',
    items_per_page: 24
};";
        file_put_contents(__DIR__ . '/config/config.js', $configJs);

        echo json_encode(array('success' => true));
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title><?php echo _t('install_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: system-ui, sans-serif; height: 100vh; display: flex; align-items: center; }
        .install-box { max-width: 500px; width: 100%; margin: auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .step-icon { font-size: 2rem; color: #0078d4; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="install-box">
    <div class="text-end mb-3">
        <select class="form-select form-select-sm d-inline-block w-auto" onchange="location.href='?lang='+this.value">
            <?php foreach($available_langs as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo $lang==$k?'selected':''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <h3 class="fw-bold mb-4"><?php echo _t('install_title'); ?></h3>

    <?php if ($isInstalled): ?>
        <div class="alert alert-warning"><?php echo _t('install_success'); ?></div>
        <div class="d-grid gap-2">
            <a href="index.html" class="btn btn-primary"><?php echo _t('goto_album'); ?></a>
            <a href="admin/" class="btn btn-outline-secondary"><?php echo _t('goto_admin'); ?></a>
        </div>
        <p class="text-muted small mt-3"><?php echo _t('delete_install'); ?></p>
    <?php else: ?>
        <div id="step1">
            <h6 class="text-uppercase text-muted mb-3"><?php echo _t('step_env'); ?></h6>
            <ul class="list-group mb-4">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?php echo _t('php_version'); ?> (>= 5.6)
                    <span class="badge <?php echo version_compare(PHP_VERSION, '5.6.0', '>=') ? 'bg-success' : 'bg-danger'; ?> rounded-pill"><?php echo PHP_VERSION; ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?php echo _t('ext_exif'); ?>
                    <span class="badge <?php echo extension_loaded('exif') ? 'bg-success' : 'bg-danger'; ?> rounded-pill"><?php echo extension_loaded('exif') ? 'OK' : 'Missing'; ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?php echo _t('ext_gd'); ?> / Imagick
                    <span class="badge <?php echo (extension_loaded('gd') || extension_loaded('imagick')) ? 'bg-success' : 'bg-danger'; ?> rounded-pill">Checked</span>
                </li>
            </ul>
            <button class="btn btn-primary w-100" onclick="document.getElementById('step1').style.display='none'; document.getElementById('step2').style.display='block';"><?php echo _t('btn_next'); ?></button>
        </div>

        <div id="step2" style="display:none;">
            <h6 class="text-uppercase text-muted mb-3"><?php echo _t('step_config'); ?></h6>
            <form id="installForm">
                <div class="mb-3">
                    <label class="form-label small"><?php echo _t('site_name'); ?></label>
                    <input type="text" name="title" class="form-control" value="Baxermux Gallery" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small"><?php echo _t('admin_user'); ?></label>
                    <input type="text" name="user" class="form-control" value="admin" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small"><?php echo _t('admin_pass'); ?></label>
                    <input type="password" name="pass" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success w-100"><?php echo _t('btn_start'); ?></button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('installForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const resp = await fetch('install.php?action=install', { method: 'POST', body: formData });
    const result = await resp.json();
    if (result.success) {
        location.reload();
    } else {
        alert('Error: ' + result.message);
    }
});
</script>
</body>
</html>
