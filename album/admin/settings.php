<?php
require_once 'auth.php';
requireAlbumLogin();

$configFile = __DIR__ . '/../config/config.js';
$configContent = file_exists($configFile) ? file_get_contents($configFile) : '';

// 簡單的 Regex 讀取 JS 物件屬性 (這部分若 config.js 格式太複雜可能需更精確)
function getConfigValue($content, $key, $default = '') {
    if (preg_match('/' . $key . ':\s*\'([^\']+)\'/', $content, $m)) return $m[1];
    if (preg_match('/' . $key . ':\s*(\d+)/', $content, $m)) return $m[1];
    return $default;
}

$currentTheme = getConfigValue($configContent, 'theme', 'album');
$currentApiType = getConfigValue($configContent, 'api_type', 'json');
$currentItemsPerPage = getConfigValue($configContent, 'items_per_page', '24');
$currentConcurrentDownloads = getConfigValue($configContent, 'concurrent_downloads', '3');

// 讀取 config.php 變數
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
    <title>前端設定 - 相簿後台</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>

    <div class="main-content flex-grow-1 bg-light">
        <div class="mb-4">
            <h2>前端設定 (config.js)</h2>
            <p class="text-muted">這裡調整的設定將直接影響相簿前端 SPA 的行為與樣式。</p>
        </div>

        <div class="card shadow-sm col-md-8 mb-5">
            <!-- ... 原有的前端設定表單 ... -->
            <div class="card-body">
                <form action="album_actions.php" method="post">
                    <input type="hidden" name="action" value="update_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <!-- 此處省略原本的前端設定內容，保持不變 -->

                    <div class="mb-4">
                        <label class="form-label fw-bold">相簿主題 (Theme)</label>
                        <select name="theme" class="form-select">
                            <?php
                            $themesDir = __DIR__ . '/../static/themes';
                            $themes = [];
                            if (is_dir($themesDir)) {
                                $scan = scandir($themesDir);
                                foreach ($scan as $entry) {
                                    if ($entry === '.' || $entry === '..') continue;
                                    $fullPath = $themesDir . '/' . $entry;
                                    
                                    // 僅偵測目錄且名稱以 'album' 開頭
                                    if (is_dir($fullPath) && strpos($entry, 'album') === 0) {
                                        $displayName = $entry; // 預設顯示目錄名
                                        $readme = $fullPath . '/readme.txt';
                                        
                                        if (file_exists($readme)) {
                                            $lines = file($readme, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                            foreach ($lines as $line) {
                                                if (stripos($line, 'Name:') === 0) {
                                                    $displayName = trim(substr($line, 5));
                                                    break;
                                                }
                                            }
                                        }
                                        $themes[$entry] = $displayName;
                                    }
                                }
                            }
                            
                            // 排序 (讓 album 排前面，其他字母順序)
                            uksort($themes, function($a, $b) {
                                if ($a === 'album') return -1;
                                if ($b === 'album') return 1;
                                return strcmp($a, $b);
                            });
                            
                            foreach ($themes as $key => $name) {
                                $selected = ($currentTheme === $key) ? 'selected' : '';
                                echo "<option value=\"$key\" $selected>" . htmlspecialchars($name) . "</option>";
                            }
                            ?>
                        </select>
                        <div class="form-text">切換相簿整體的配色方案 (自動偵測 themes 目錄)。</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">資料讀取模式 (API Type)</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="api_type" value="json" id="api_json" <?php echo ($currentApiType === 'json') ? 'selected' : ''; ?> <?php echo ($currentApiType === 'json') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="api_json">
                                靜態 JSON 模式 (適合純靜態託管，需執行 make_album.php)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="api_type" value="api_filebase" id="api_file" <?php echo ($currentApiType === 'api_filebase') ? 'selected' : ''; ?> <?php echo ($currentApiType === 'api_filebase') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="api_file">
                                動態 PHP API 模式 (即時讀取檔案系統，不需頻繁產生 JSON)
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">每頁顯示項目數 (Items Per Page)</label>
                        <input type="number" name="items_per_page" class="form-control" value="<?php echo htmlspecialchars($currentItemsPerPage); ?>" min="1" max="200">
                        <div class="form-text">設定首頁與相簿內頁一頁要顯示多少張照片/相簿。</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">並行下載限制 (Concurrent Downloads)</label>
                        <input type="number" name="concurrent_downloads" class="form-control" value="<?php echo htmlspecialchars($currentConcurrentDownloads); ?>" min="1" max="10">
                        <div class="form-text">同時下載照片資源的最大數量。較小的值可減輕伺服器負擔並增加穩定性。</div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-danger small">修改後將直接覆蓋 config.js 檔案。</span>
                        <button type="submit" class="btn btn-primary px-4">儲存設定</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="mb-4">
            <h2>後端與 SEO 設定 (config.php)</h2>
            <p class="text-muted">這裡調整的設定涉及網站標題、SEO 描述以及系統環境配置。</p>
        </div>

        <div class="card shadow-sm col-md-8">
            <div class="card-body">
                <form action="album_actions.php" method="post">
                    <input type="hidden" name="action" value="update_backend_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">相簿網站標題 (Album Title)</label>
                        <input type="text" name="album_title" class="form-control" value="<?php echo htmlspecialchars($album_title); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">SEO 描述屬性 (Description)</label>
                        <textarea name="album_description" class="form-control" rows="2"><?php echo htmlspecialchars($album_description); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">相簿簡介 (Introduce)</label>
                        <input type="text" name="album_introduce" class="form-control" value="<?php echo htmlspecialchars($album_introduce); ?>">
                        <div class="form-text">顯示在頁面頂部的小字簡介。</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">分享預覽圖 URL (Preview Image)</label>
                        <input type="text" name="album_preview" class="form-control" value="<?php echo htmlspecialchars($album_preview); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">相簿網站網址 (Site URL)</label>
                        <input type="text" name="album_site_url" class="form-control" value="<?php echo htmlspecialchars($album_site_url); ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">網站語系 (Language)</label>
                            <select name="album_lang" class="form-select">
                                <option value="zh_TW" <?php echo ($album_lang == 'zh_TW' ? 'selected' : ''); ?>>繁體中文 (zh_TW)</option>
                                <option value="en_US" <?php echo ($album_lang == 'en_US' ? 'selected' : ''); ?>>English (en_US)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">系統時區 (Timezone)</label>
                            <input type="text" name="album_timezone" class="form-control" value="<?php echo htmlspecialchars($album_timezone); ?>">
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-danger small">修改後將直接更新 config.php 檔案。</span>
                        <button type="submit" class="btn btn-success px-4">儲存後端設定</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
