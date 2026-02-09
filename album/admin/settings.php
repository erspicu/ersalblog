<?php
require_once 'auth.php';
requireAlbumLogin();

$configFile = __DIR__ . '/../config.js';
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
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>前端設定 - 相簿後台</title>
    <link href="../../admin/assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>

    <div class="main-content flex-grow-1 bg-light">
        <div class="mb-4">
            <h2>前端設定 (config.js)</h2>
            <p class="text-muted">這裡調整的設定將直接影響相簿前端 SPA 的行為與樣式。</p>
        </div>

        <div class="card shadow-sm col-md-8">
            <div class="card-body">
                <form action="album_actions.php" method="post">
                    <input type="hidden" name="action" value="update_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

                    <div class="mb-4">
                        <label class="form-label fw-bold">相簿主題 (Theme)</label>
                        <select name="theme" class="form-select">
                            <option value="album" <?php echo ($currentTheme === 'album') ? 'selected' : ''; ?>>預設主題 (Default)</option>
                            <option value="album-dark" <?php echo ($currentTheme === 'album-dark') ? 'selected' : ''; ?>>深色模式 (Dark)</option>
                            <option value="album-pink" <?php echo ($currentTheme === 'album-pink') ? 'selected' : ''; ?>>粉紅風格 (Pink)</option>
                            <option value="album-matrix" <?php echo ($currentTheme === 'album-matrix') ? 'selected' : ''; ?>>駭客任務 (Matrix)</option>
                            <option value="album-y2k" <?php echo ($currentTheme === 'album-y2k') ? 'selected' : ''; ?>>復古 Y2K (Y2K)</option>
                        </select>
                        <div class="form-text">切換相簿整體的配色方案。</div>
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

                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-danger small">修改後將直接覆蓋 config.js 檔案。</span>
                        <button type="submit" class="btn btn-primary px-4">儲存設定</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../../admin/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
