<?php
require_once 'auth.php';
requireAlbumLogin();

$configFile = __DIR__ . '/../config/config.js';
$configContent = file_exists($configFile) ? file_get_contents($configFile) : '';

function getConfigValue($content, $key, $default = '') {
    if (preg_match('/' . $key . ':\s*\'([^\']+)\'/', $content, $m)) return $m[1];
    if (preg_match('/' . $key . ':\s*(\d+)/', $content, $m)) return $m[1];
    return $default;
}

$currentTheme = getConfigValue($configContent, 'theme', 'album');
$currentApiType = getConfigValue($configContent, 'api_type', 'json');
$currentItemsPerPage = getConfigValue($configContent, 'items_per_page', '24');
$currentConcurrentDownloads = getConfigValue($configContent, 'concurrent_downloads', '3');

$album_title = "Baxermux的相簿";
$album_description = "ersalblog的延伸子專案相簿服務。";
$album_introduce = "放一些Blog用到的素材照片.";
$album_preview = "";
$album_site_url = "";
$album_lang = "zh-TW";
$album_timezone = "Asia/Taipei";

$phpConfigFile = __DIR__ . '/../config/config.php';
if (file_exists($phpConfigFile)) {
    include $phpConfigFile;
}
?>
<!DOCTYPE html>
<html lang="<?php echo getWebLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('global_settings'); ?> - 相簿後台</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>

    <div class="main-content flex-grow-1 bg-light">
        <div class="mb-4">
            <h2><?php echo __('global_settings'); ?></h2>
            <p class="text-muted"><?php echo __('settings_desc'); ?></p>
        </div>

        <!-- 前端設定 (AJAX) -->
        <div class="card shadow-sm col-md-8 mb-4">
            <div class="card-header bg-white fw-bold"><?php echo __('frontend_settings'); ?></div>
            <div class="card-body">
                <form class="ajax-form" data-action="update_settings">
                    <input type="hidden" name="action" value="update_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('theme'); ?></label>
                        <select name="theme" class="form-select">
                            <?php
                            $themesDir = __DIR__ . '/../static/themes';
                            $themes = [];
                            if (is_dir($themesDir)) {
                                foreach (scandir($themesDir) as $entry) {
                                    if ($entry === '.' || $entry === '..') continue;
                                    if (is_dir($themesDir . '/' . $entry) && strpos($entry, 'album') === 0) {
                                        $displayName = $entry;
                                        $readme = $themesDir . '/' . $entry . '/readme.txt';
                                        if (file_exists($readme)) {
                                            foreach (file($readme) as $line) {
                                                if (stripos($line, 'Name:') === 0) { $displayName = trim(substr($line, 5)); break; }
                                            }
                                        }
                                        $themes[$entry] = $displayName;
                                    }
                                }
                            }
                            uksort($themes, function($a, $b) { return ($a === 'album') ? -1 : (($b === 'album') ? 1 : strcmp($a, $b)); });
                            foreach ($themes as $key => $name) {
                                $selected = ($currentTheme === $key) ? 'selected' : '';
                                echo "<option value=\"$key\" $selected>" . htmlspecialchars($name) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('api_mode'); ?></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="api_type" value="json" id="api_json" <?php echo ($currentApiType === 'json') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="api_json"><?php echo __('api_static'); ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="api_type" value="api_filebase" id="api_file" <?php echo ($currentApiType === 'api_filebase') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="api_file"><?php echo __('api_dynamic'); ?></label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold"><?php echo __('items_per_page'); ?></label>
                            <input type="number" name="items_per_page" class="form-control" value="<?php echo htmlspecialchars($currentItemsPerPage); ?>" min="1" max="200">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold"><?php echo __('concurrent_downloads'); ?></label>
                            <input type="number" name="concurrent_downloads" class="form-control" value="<?php echo htmlspecialchars($currentConcurrentDownloads); ?>" min="1" max="6">
                            <div class="form-text"><?php echo __('concurrent_desc'); ?></div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4"><?php echo __('save_frontend'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 後端設定 (AJAX) -->
        <div class="card shadow-sm col-md-8">
            <div class="card-header bg-white fw-bold"><?php echo __('backend_settings'); ?></div>
            <div class="card-body">
                <form class="ajax-form" data-action="update_backend_settings">
                    <input type="hidden" name="action" value="update_backend_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('album_title'); ?></label>
                        <input type="text" name="album_title" class="form-control" value="<?php echo htmlspecialchars($album_title); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('seo_desc'); ?></label>
                        <input type="text" name="album_description" class="form-control" value="<?php echo htmlspecialchars($album_description); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('album_intro'); ?></label>
                        <input type="text" name="album_introduce" class="form-control" value="<?php echo htmlspecialchars($album_introduce); ?>">
                        <div class="form-text"><?php echo __('album_intro_desc'); ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('preview_img'); ?></label>
                        <input type="text" name="album_preview" class="form-control" value="<?php echo htmlspecialchars($album_preview); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('site_url'); ?></label>
                        <input type="text" name="album_site_url" class="form-control" value="<?php echo htmlspecialchars($album_site_url); ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold"><?php echo __('language'); ?></label>
                            <select name="album_lang" class="form-select">
                                <option value="zh-TW" <?php echo ($album_lang == 'zh-TW' ? 'selected' : ''); ?>>繁體中文 (zh-TW)</option>
                                <option value="en-US" <?php echo ($album_lang == 'en-US' ? 'selected' : ''); ?>>English (en-US)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold"><?php echo __('timezone'); ?></label>
                            <select name="album_timezone" class="form-select">
                                <?php
                                $timezones = [
                                    'Asia/Taipei' => '台北 (UTC+8)',
                                    'Asia/Hong_Kong' => '香港 (UTC+8)',
                                    'Asia/Shanghai' => '上海 (UTC+8)',
                                    'Asia/Tokyo' => '東京 (UTC+9)',
                                    'Asia/Seoul' => '首爾 (UTC+9)',
                                    'Asia/Singapore' => '新加坡 (UTC+8)',
                                    'Asia/Bangkok' => '曼谷 (UTC+7)',
                                    'America/New_York' => '紐約 (EST/EDT)',
                                    'America/Los_Angeles' => '洛杉磯 (PST/PDT)',
                                    'Europe/London' => '倫敦 (GMT/BST)',
                                    'Europe/Paris' => '巴黎 (CET/CEST)',
                                    'UTC' => 'UTC 標準時間'
                                ];
                                foreach ($timezones as $val => $lbl) {
                                    $sel = ($album_timezone == $val) ? 'selected' : '';
                                    echo "<option value=\"$val\" $sel>$lbl</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success px-4"><?php echo __('save_backend'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sweetalert2.all.min.js"></script>
<script>
document.querySelectorAll('.ajax-form').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerText;

        submitBtn.disabled = true;
        submitBtn.innerText = '<?php echo __('processing'); ?>';

        try {
            const response = await fetch('album_actions.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                Swal.fire({
                    icon: 'success',
                    title: '<?php echo __('success_save'); ?>',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                throw new Error('Server returned error');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'System Error'
            });
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerText = originalBtnText;
        }
    });
});
</script>
</body>
</html>
