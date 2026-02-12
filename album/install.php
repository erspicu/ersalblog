<?php
/**
 * Baxermux Album Service Installation Wizard (v2 - Advanced Config)
 */

// 動態偵測可用語系
$available_langs = array();
$langDir = __DIR__ . '/langs';
if (is_dir($langDir)) {
    foreach (glob($langDir . '/install-*.php') as $file) {
        $langCode = str_replace(['install-', '.php'], '', basename($file));
        $names = array('zh_TW' => '繁體中文', 'en_US' => 'English');
        $available_langs[$langCode] = isset($names[$langCode]) ? $names[$langCode] : $langCode;
    }
}
if (empty($available_langs)) $available_langs = array('zh_TW' => '繁體中文');

$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['album_lang']) ? $_COOKIE['album_lang'] : 'zh_TW');
if (!isset($available_langs[$lang])) $lang = array_keys($available_langs)[0];
setcookie('album_lang', $lang, time() + 86400 * 30, "/");

$lang_file = __DIR__ . "/langs/install-{$lang}.php";
$t = file_exists($lang_file) ? include $lang_file : array();
function _t($key) { global $t; return isset($t[$key]) ? $t[$key] : $key; }

$isInstalled = file_exists(__DIR__ . '/config/config.php');

if (isset($_GET['action']) && $_GET['action'] === 'install') {
    header('Content-Type: application/json');
    $user = $_POST['user'];
    $pass = $_POST['pass'];
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $intro = $_POST['introduce'];
    $preview = $_POST['preview'];
    $site_url = $_POST['site_url'];
    $tz = $_POST['timezone'];
    $lang_code = $_POST['lang_code'];
    $theme = $_POST['theme'];
    $api_type = $_POST['api_type'];
    $ipp = (int)$_POST['items_per_page'];
    $cdl = (int)$_POST['concurrent_downloads'];

    try {
        // ... (目錄建立邏輯維持不變)
        $dirs = array('config', 'Collection', 'api/json', 'static/themes');
        foreach ($dirs as $d) {
            $path = __DIR__ . '/' . $d;
            if (!file_exists($path)) mkdir($path, 0777, true);
        }

        $secret = bin2hex(random_bytes(16));

        // 2. 產生 config.php
        $configPhp = "<?php\n/**\n * Baxermux Album Configuration\n */\n";
        $configPhp .= "\$albumAdminConfig = array(\n    'username' => '" . addslashes($user) . "',\n    'password' => '" . addslashes($pass) . "',\n    'session_secret' => '" . $secret . "'\n);\n\n";
        $configPhp .= "\$album_title = \"" . addslashes($title) . "\";\n";
        $configPhp .= "\$album_description = \"" . addslashes($desc) . "\";\n";
        $configPhp .= "\$album_introduce = \"" . addslashes($intro) . "\";\n";
        $configPhp .= "\$album_preview = \"" . addslashes($preview) . "\";\n";
        $configPhp .= "\$album_site_url = \"" . addslashes($site_url) . "\";\n";
        $configPhp .= "\$album_lang = \"" . addslashes($lang_code) . "\";\n";
        $configPhp .= "\$album_timezone = \"" . addslashes($tz) . "\";\n\n";
        $configPhp .= "date_default_timezone_set(\$album_timezone);\n?>";
        file_put_contents(__DIR__ . '/config/config.php', $configPhp);

        // 3. 產生 config.js (前端設定)
        $configJs = "/**\n * Baxermux Album Frontend Configuration\n */\nconst albumConfig = {\n";
        $configJs .= "    theme: '" . addslashes($theme) . "',\n";
        $configJs .= "    api_type: '" . addslashes($api_type) . "',\n";
        $configJs .= "    items_per_page: " . $ipp . ",\n";
        $configJs .= "    concurrent_downloads: " . $cdl . "\n};";
        file_put_contents(__DIR__ . '/config/config.js', $configJs);

        echo json_encode(array('success' => true));
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// 獲取可用的主題列表
function getThemes() {
    $themesDir = __DIR__ . '/static/themes';
    $themes = array('album' => 'Standard');
    if (is_dir($themesDir)) {
        $scan = scandir($themesDir);
        foreach ($scan as $entry) {
            if ($entry !== '.' && $entry !== '..' && is_dir($themesDir . '/' . $entry) && strpos($entry, 'album') === 0) {
                $themes[$entry] = $entry;
            }
        }
    }
    return $themes;
}
$themes = getThemes();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title><?php echo _t('install_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: system-ui, -apple-system, sans-serif; min-height: 100vh; padding: 50px 0; }
        .install-container { max-width: 650px; margin: auto; }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; }
        .card-header { background: #0078d4; color: white; padding: 25px; border: none; text-align: center; }
        .card-body { padding: 40px; background: white; }
        .step-badge { font-size: 0.75rem; padding: 4px 12px; background: rgba(255,255,255,0.2); border-radius: 20px; margin-bottom: 10px; display: inline-block; }
        .section-title { font-weight: 700; color: #333; margin-top: 30px; margin-bottom: 20px; border-left: 4px solid #0078d4; padding-left: 15px; }
        .form-label { font-weight: 600; color: #555; }
    </style>
</head>
<body>
<div class="install-container">
    <div class="card">
        <div class="card-header">
            <div class="step-badge"><?php echo _t('install_title'); ?></div>
            <h2 class="mb-0 fw-bold">Baxermux Gallery</h2>
        </div>
        <div class="card-body">
            <?php if ($isInstalled): ?>
                <div class="alert alert-success d-flex align-items-center mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?php echo _t('install_success'); ?></div>
                </div>
                <div class="d-grid gap-3">
                    <a href="index.html" class="btn btn-primary btn-lg shadow-sm"><?php echo _t('goto_album'); ?></a>
                    <a href="admin/" class="btn btn-outline-secondary"><?php echo _t('goto_admin'); ?></a>
                </div>
                <p class="text-center text-muted small mt-4"><?php echo _t('delete_install'); ?></p>
            <?php else: ?>
                <div id="step1">
                    <div class="text-end mb-4">
                        <select class="form-select form-select-sm d-inline-block w-auto" onchange="location.href='?lang='+this.value">
                            <?php foreach($available_langs as $k => $v): ?>
                                <option value="<?php echo $k; ?>" <?php echo $lang==$k?'selected':''; ?>><?php echo $v; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <h5 class="section-title"><?php echo _t('step_env'); ?></h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="p-3 border rounded bg-light d-flex justify-content-between align-items-center">
                                <span><?php echo _t('php_version'); ?> (>= 5.6)</span>
                                <span class="badge <?php echo version_compare(PHP_VERSION, '5.6.0', '>=') ? 'bg-success' : 'bg-danger'; ?>"><?php echo PHP_VERSION; ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 border rounded bg-light d-flex justify-content-between align-items-center">
                                <span><?php echo _t('ext_exif'); ?></span>
                                <span class="badge <?php echo extension_loaded('exif') ? 'bg-success' : 'bg-danger'; ?>"><?php echo extension_loaded('exif') ? 'Enabled' : 'Missing'; ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 border rounded bg-light d-flex justify-content-between align-items-center">
                                <span><?php echo _t('ext_gd'); ?> / Imagick</span>
                                <span class="badge <?php echo (extension_loaded('gd') || extension_loaded('imagick')) ? 'bg-success' : 'bg-danger'; ?>">OK</span>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-lg w-100 shadow-sm" onclick="document.getElementById('step1').style.display='none'; document.getElementById('step2').style.display='block';"><?php echo _t('btn_next'); ?></button>
                </div>

                <form id="installForm">
                    <div id="step2" style="display:none;">
                        <!-- 1. Admin Config -->
                        <h5 class="section-title"><?php echo _t('admin_settings'); ?></h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label"><?php echo _t('site_name'); ?></label>
                                <input type="text" name="title" class="form-control" value="Baxermux Gallery" required oninvalid="this.setCustomValidity('<?php echo _t('field_required'); ?>')" oninput="setCustomValidity('')">
                            </div>
                            <div class="col-12">
                                <label class="form-label"><?php echo _t('seo_desc'); ?></label>
                                <input type="text" name="description" class="form-control" value="ersalblog的延伸子專案相簿服務。" required oninvalid="this.setCustomValidity('<?php echo _t('field_required'); ?>')" oninput="setCustomValidity('')">
                            </div>
                            <div class="col-12">
                                <label class="form-label"><?php echo _t('album_intro'); ?></label>
                                <input type="text" name="introduce" class="form-control" value="放一些Blog用到的素材照片." required oninvalid="this.setCustomValidity('<?php echo _t('field_required'); ?>')" oninput="setCustomValidity('')">
                            </div>
                            <div class="col-12">
                                <label class="form-label"><?php echo _t('preview_img'); ?></label>
                                <input type="text" name="preview" class="form-control" value="https://www.baxermux.org/ersalblog/album/BaxerMuxAlbum.jpg">
                            </div>
                            <div class="col-12">
                                <label class="form-label"><?php echo _t('site_url'); ?></label>
                                <input type="text" name="site_url" class="form-control" value="https://www.baxermux.org/ersalblog/album/">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo _t('admin_user'); ?></label>
                                <input type="text" name="user" class="form-control" value="admin" required oninvalid="this.setCustomValidity('<?php echo _t('field_required'); ?>')" oninput="setCustomValidity('')">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo _t('admin_pass'); ?></label>
                                <input type="password" name="pass" class="form-control" placeholder="******" required oninvalid="this.setCustomValidity('<?php echo _t('field_required'); ?>')" oninput="setCustomValidity('')">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo _t('language'); ?></label>
                                <select name="lang_code" class="form-select">
                                    <?php foreach($available_langs as $k => $v): ?>
                                        <option value="<?php echo str_replace('_', '-', $k); ?>" <?php echo $lang==$k?'selected':''; ?>><?php echo $v; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo _t('timezone'); ?></label>
                                <select name="timezone" class="form-select">
                                    <option value="Asia/Taipei" selected>Asia/Taipei (UTC+8)</option>
                                    <option value="Asia/Tokyo">Tokyo (UTC+9)</option>
                                    <option value="America/New_York">New York</option>
                                    <option value="Europe/London">London</option>
                                    <option value="UTC">UTC</option>
                                </select>
                            </div>
                        </div>

                        <!-- 2. Frontend Config -->
                        <h5 class="section-title"><?php echo _t('frontend_settings'); ?></h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label"><?php echo _t('theme_select'); ?></label>
                                <select name="theme" class="form-select">
                                    <?php foreach($themes as $k => $v): ?>
                                        <option value="<?php echo $k; ?>" <?php echo $k=='album'?'selected':''; ?>><?php echo $v; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo _t('api_type'); ?></label>
                                <select name="api_type" class="form-select">
                                    <option value="json" selected><?php echo _t('api_json'); ?></option>
                                    <option value="api_filebase"><?php echo _t('api_php'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo _t('items_per_page'); ?></label>
                                <input type="number" name="items_per_page" class="form-control" value="24" min="1" max="100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo _t('concurrent_downloads'); ?></label>
                                <input type="number" name="concurrent_downloads" class="form-control" value="3" min="1" max="6">
                            </div>
                        </div>

                        <div class="mt-5 d-flex gap-3">
                            <button type="button" class="btn btn-light border px-4" onclick="document.getElementById('step2').style.display='none'; document.getElementById('step1').style.display='block';"><?php echo _t('btn_back'); ?></button>
                            <button type="submit" class="btn btn-success btn-lg flex-grow-1 shadow-sm"><?php echo _t('btn_start'); ?></button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('installForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span><?php echo _t('installing'); ?>';

    const formData = new FormData(e.target);
    try {
        const resp = await fetch('install.php?action=install', { method: 'POST', body: formData });
        const result = await resp.json();
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.message);
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    } catch (err) {
        alert('<?php echo _t('error_network'); ?>');
        btn.disabled = false;
        btn.innerHTML = orig;
    }
});
</script>
</body>
</html>
