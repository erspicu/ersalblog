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
$album_lang = "zh_TW";
$album_timezone = "Asia/Taipei";

$phpConfigFile = __DIR__ . '/../config/config.php';
if (file_exists($phpConfigFile)) {
    include $phpConfigFile;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>全域設定 - 相簿後台</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>

    <div class="main-content flex-grow-1 bg-light">
        <div class="mb-4">
            <h2>全域設定 (Global Settings)</h2>
            <p class="text-muted">在這裡您可以統一調整相簿的前端顯示 (config.js) 與後端環境/SEO 配置 (config.php)。</p>
        </div>

        <!-- 前端設定 (AJAX) -->
        <div class="card shadow-sm col-md-8 mb-4">
            <div class="card-header bg-white fw-bold">前端設定 (JS Config)</div>
            <div class="card-body">
                <form class="ajax-form" data-action="update_settings">
                    <input type="hidden" name="action" value="update_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">相簿主題 (Theme)</label>
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
                        <label class="form-label fw-bold">資料讀取模式</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="api_type" value="json" id="api_json" <?php echo ($currentApiType === 'json') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="api_json">靜態 JSON</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="api_type" value="api_filebase" id="api_file" <?php echo ($currentApiType === 'api_filebase') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="api_file">動態 PHP API</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">每頁顯示項目數</label>
                            <input type="number" name="items_per_page" class="form-control" value="<?php echo htmlspecialchars($currentItemsPerPage); ?>" min="1" max="200">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">並行下載限制</label>
                            <input type="number" name="concurrent_downloads" class="form-control" value="<?php echo htmlspecialchars($currentConcurrentDownloads); ?>" min="1" max="6">
                            <div class="form-text">同時下載最大數量 (建議 1-6)。</div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4">儲存前端設定</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 後端設定 (AJAX) -->
        <div class="card shadow-sm col-md-8">
            <div class="card-header bg-white fw-bold">後端與 SEO 設定 (PHP Config)</div>
            <div class="card-body">
                <form class="ajax-form" data-action="update_backend_settings">
                    <input type="hidden" name="action" value="update_backend_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">相簿網站標題</label>
                        <input type="text" name="album_title" class="form-control" value="<?php echo htmlspecialchars($album_title); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">SEO 描述屬性 (Description)</label>
                        <input type="text" name="album_description" class="form-control" value="<?php echo htmlspecialchars($album_description); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">相簿簡介 (Introduce)</label>
                        <input type="text" name="album_introduce" class="form-control" value="<?php echo htmlspecialchars($album_introduce); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">分享預覽圖 URL</label>
                        <input type="text" name="album_preview" class="form-control" value="<?php echo htmlspecialchars($album_preview); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">相簿網站網址</label>
                        <input type="text" name="album_site_url" class="form-control" value="<?php echo htmlspecialchars($album_site_url); ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">語系</label>
                            <select name="album_lang" class="form-select">
                                <option value="zh-TW" <?php echo ($album_lang == 'zh-TW' ? 'selected' : ''); ?>>繁體中文 (台灣)</option>
                                <option value="en-US" <?php echo ($album_lang == 'en-US' ? 'selected' : ''); ?>>English</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">系統時區</label>
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
                        <button type="submit" class="btn btn-success px-4">儲存後端設定</button>
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
        submitBtn.innerText = '處理中...';

        try {
            const response = await fetch('album_actions.php', {
                method: 'POST',
                body: formData
            });

            // 檢查回應是否為跳轉 (雖然 AJAX 下不會自動跳轉，但後台原本的 PHP 會回傳 Location header)
            // 如果後端沒改，這裡會收到帶有跳轉後的 HTML。我們需要修正後端以支援 AJAX JSON 回應。
            if (response.ok) {
                Swal.fire({
                    icon: 'success',
                    title: '儲存成功',
                    text: '設定已更新完成！',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                throw new Error('Server returned error');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: '儲存失敗',
                text: '發生預期外的錯誤，請稍後再試。'
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
