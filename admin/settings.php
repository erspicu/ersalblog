<?php
require_once 'auth.php';
require_once 'data_provider.php';
requireLogin();

$dataManager = new DataManager();
$configFile = __DIR__ . '/../config.js';
$msg = '';
$error = '';

function getConfigValues($content) {
    $values = array();
    if (preg_match("/api_type:\s*'([^']+)'/", $content, $m)) $values['api_type'] = $m[1];
    if (preg_match("/theme_file:\s*'([^']+)'/", $content, $m)) $values['theme_file'] = $m[1];
    if (preg_match("/posts_per_page:\s*(\d+)/", $content, $m)) $values['posts_per_page_js'] = (int)$m[1];
    if (preg_match("/cse_id:\s*'([^']*)'/", $content, $m)) $values['cse_id'] = $m[1];
    if (preg_match("/guestbook_plugin:\s*'([^']*)'/", $content, $m)) $values['guestbook_plugin'] = $m[1];
    if (preg_match("/guestbook_per_page:\s*(\d+)/", $content, $m)) $values['guestbook_per_page'] = (int)$m[1];
    return $values;
}

// Read current values
$configContent = file_exists($configFile) ? file_get_contents($configFile) : '';
$currentConfig = getConfigValues($configContent);

// Load values from config.php
$currentConfig['blog_lang'] = isset($blog_lang) ? $blog_lang : 'zh_TW';
$currentConfig['timezone'] = isset($blog_timezone) ? $blog_timezone : 'Asia/Taipei';
$currentConfig['posts_per_page'] = isset($posts_per_page) ? $posts_per_page : 10;
$currentConfig['album_path'] = isset($album_path) ? $album_path : 'album/';
$currentConfig['blog_title'] = isset($blog_title) ? $blog_title : '';
$currentConfig['blog_description'] = isset($blog_description) ? $blog_description : '';
$currentConfig['blog_introduce'] = isset($blog_introduce) ? $blog_introduce : '';
$currentConfig['blog_favicon'] = isset($blog_favicon) ? $blog_favicon : '/static/icon-192.png';

// --- AI Cache Handling ---
$aiCacheFile = __DIR__ . '/../static/ai_models_cache.json';
$aiModelsCache = [];
if (file_exists($aiCacheFile)) {
    $aiModelsCache = json_decode(file_get_contents($aiCacheFile), true);
}

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'CSRF Validation Failed']);
            exit;
        }
        die("CSRF Validation Failed");
    }

    if (isset($_POST['action']) && $_POST['action'] === 'save_ai_cache') {
        $models = isset($_POST['models']) ? $_POST['models'] : '[]';
        if (file_put_contents($aiCacheFile, $models)) {
            echo json_encode(['success' => true, 'message' => 'AI 模型快取已更新']);
        } else {
            echo json_encode(['success' => false, 'message' => '快取寫入失敗']);
        }
        exit;
    }

    if (isset($_POST['save_backend'])) {
        // --- Save config.php ---
        $newLang = isset($_POST['blog_lang']) ? $_POST['blog_lang'] : 'zh_TW';
        $newTimezone = isset($_POST['timezone']) ? $_POST['timezone'] : 'Asia/Taipei';
        $newPerPage = isset($_POST['posts_per_page']) ? (int)$_POST['posts_per_page'] : 10;
        $newAlbumPath = isset($_POST['album_path']) ? $_POST['album_path'] : 'album/';
        $newTitle = isset($_POST['blog_title']) ? $_POST['blog_title'] : '';
        $newDesc = isset($_POST['blog_description']) ? $_POST['blog_description'] : '';
        $newIntro = isset($_POST['blog_introduce']) ? $_POST['blog_introduce'] : '';
        $newFavicon = isset($_POST['blog_favicon']) ? $_POST['blog_favicon'] : '/static/icon-192.png';
        
        $aiEnabled = 'false';
        if (isset($_POST['ai_enabled'])) {
            $aiEnabled = 'true';
        } elseif (isset($_POST['ai_enabled_hidden']) && $_POST['ai_enabled_hidden'] === 'false') {
            $aiEnabled = 'false';
        } elseif (!$isAjax) {
            // 非 AJAX 提交時，沒帶 ai_enabled 就是 false
            $aiEnabled = 'false';
        } else {
            // AJAX 提交但沒帶狀態（通常是因為表單沒變動或是 JS 沒補位），則保持原樣
            // 為了保險起見，我們讓前端一定要帶入狀態。
            $aiEnabled = isset($aiConfig) && $aiConfig['enabled'] ? 'true' : 'false';
        }

        $aiKey = isset($_POST['ai_api_key']) ? trim($_POST['ai_api_key']) : '';
        $aiModel = isset($_POST['ai_model']) ? $_POST['ai_model'] : 'gemini-3-flash-preview';

        // --- 防呆檢查 ---
        if ($newPerPage <= 0) {
            $error = "每頁文章數量必須大於 0";
        } else {
            // 檢查相簿路徑有效性 (僅在非空時檢查)
            if (!empty($newAlbumPath)) {
                $actual_path = realpath(__DIR__ . '/../' . $newAlbumPath);
                if (!$actual_path || !is_dir($actual_path)) {
                    $error = "錯誤：指定的相簿路徑不存在。";
                } elseif (!file_exists($actual_path . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'api_album.php')) {
                    $error = "錯誤：該目錄似乎不是有效的相簿服務目錄 (找不到 api/api_album.php)。";
                }
            }
        }

        if (!$error) {
            $phpFile = __DIR__ . '/../config.php';
            $phpContent = file_get_contents($phpFile);
            
            $phpContent = preg_replace('/(\$blog_title\s*=\s*[\'"])([^"\']*)([\'"];)/', '${1}' . addslashes($newTitle) . '${3}', $phpContent);
            $phpContent = preg_replace('/(\$blog_description\s*=\s*[\'"])([^"\']*)([\'"];)/', '${1}' . addslashes($newDesc) . '${3}', $phpContent);
            $phpContent = preg_replace('/(\$blog_introduce\s*=\s*[\'"])([^"\']*)([\'"];)/', '${1}' . addslashes($newIntro) . '${3}', $phpContent);
            $phpContent = preg_replace('/(\$blog_lang\s*=\s*[\'"])([^"\']*)([\'"];)/', '${1}' . $newLang . '${3}', $phpContent);
            $phpContent = preg_replace('/(\$blog_timezone\s*=\s*[\'"])([^"\']*)([\'"];)/', '${1}' . $newTimezone . '${3}', $phpContent);
            $phpContent = preg_replace('/(\$posts_per_page\s*=\s*)([^;]*)(;)/', '${1}' . $newPerPage . '${3}', $phpContent);
            
            if (strpos($phpContent, '$blog_favicon') !== false) {
                $phpContent = preg_replace('/(\$blog_favicon\s*=\s*[\'"])([^"\']*)([\'"];)/', '${1}' . addslashes($newFavicon) . '${3}', $phpContent);
            } else {
                $phpContent = str_replace('?>', "\$blog_favicon = " . var_export($newFavicon, true) . "; // Blog Favicon路徑\n?>", $phpContent);
            }

            if (strpos($phpContent, '$album_path') !== false) {
                $phpContent = preg_replace('/(\$album_path\s*=\s*[\'"])([^"\']*)([\'"];)/', '${1}' . $newAlbumPath . '${3}', $phpContent);
            } else {
                $phpContent = str_replace('?>', "\$album_path = " . var_export($newAlbumPath, true) . "; // 相簿服務路徑\n?>", $phpContent);
            }

            // --- AI Config Insertion/Update ---
            if (strpos($phpContent, '$aiConfig') === false) {
                // 如果完全沒有 aiConfig，先補上結構
                $aiTmpl = "\n// --- AI 輔助設定 ---\n";
                $aiTmpl .= "\$aiConfig = array(\n";
                $aiTmpl .= "    'enabled' => false,\n";
                $aiTmpl .= "    'api_key' => '',\n";
                $aiTmpl .= "    'model'   => 'gemini-3-flash-preview'\n";
                $aiTmpl .= ");\n";
                $phpContent = str_replace('?>', $aiTmpl . "?>", $phpContent);
            }

            // 進行取代
            $phpContent = preg_replace('/(\'enabled\'\s*=>\s*)(true|false)/', '${1}' . $aiEnabled, $phpContent);
            $phpContent = preg_replace('/(\'api_key\'\s*=>\s*[\'"])([^"\']*)([\'"])/', '${1}' . addslashes($aiKey) . '${3}', $phpContent);
            $phpContent = preg_replace('/(\'model\'\s*=>\s*[\'"])([^"\']*)([\'"])/', '${1}' . addslashes($aiModel) . '${3}', $phpContent);

            if (file_put_contents($phpFile, $phpContent)) {
                if ($isAjax) {
                    echo json_encode(['success' => true, 'message' => __('msg_settings_saved') . ' (config.php)']);
                    exit;
                }
                $msg = __('msg_settings_saved') . ' (config.php)';
            } else {
                $error = __('error_config_write') . ' (config.php)';
            }
        }
        if ($isAjax && $error) {
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }

    } elseif (isset($_POST['save_account'])) {
        $newUsername = isset($_POST['new_username']) ? trim($_POST['new_username']) : '';
        $newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

        if (empty($newUsername)) {
            $error = "帳號不能為空";
        } else {
            $phpFile = __DIR__ . '/../config.php';
            $phpContent = file_get_contents($phpFile);
            $phpContent = preg_replace('/(\'username\'\s*=>\s*[\'"])([^"\']*)([\'"])/', '${1}' . addslashes($newUsername) . '${3}', $phpContent);
            
            if (!empty($newPassword)) {
                $fingerprint = getSystemFingerprint();
                $hashedPassword = password_hash($newPassword . $fingerprint, PASSWORD_BCRYPT);
                $replacement = str_replace('$', '\$', $hashedPassword);
                $phpContent = preg_replace('/(\'password\'\s*=>\s*[\'"])([^"\']*)([\'"])/', '${1}' . $replacement . '${3}', $phpContent);
            }

            if (file_put_contents($phpFile, $phpContent)) {
                $_SESSION['admin_user'] = $newUsername;
                if ($isAjax) {
                    echo json_encode(['success' => true, 'message' => __('msg_account_updated')]);
                    exit;
                }
                $msg = __('msg_account_updated');
            } else {
                $error = __('error_config_write') . ' (config.php)';
            }
        }
        if ($isAjax && $error) {
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }

    } elseif (isset($_POST['save_frontend'])) {
        $newApi = isset($_POST['api_type']) ? $_POST['api_type'] : 'api_filebase';
        $newTheme = isset($_POST['theme_file']) ? $_POST['theme_file'] : 'blog';
        $newPerPageJs = isset($_POST['posts_per_page_js']) ? (int)$_POST['posts_per_page_js'] : 10;
        $newCse = isset($_POST['cse_id']) ? $_POST['cse_id'] : '';
        $newGbPlugin = isset($_POST['guestbook_plugin']) ? $_POST['guestbook_plugin'] : '';
        $newGbPerPage = isset($_POST['guestbook_per_page']) ? (int)$_POST['guestbook_per_page'] : 5;

        if ($newPerPageJs <= 0) {
            $error = "前端每頁文章數量必須大於 0";
        } else {
            $newJsContent = "var AppConfig = {\n";
            $newJsContent .= "    api_type: '$newApi',\n";
            $newJsContent .= "    theme_file: '$newTheme',\n";
            $newJsContent .= "    posts_per_page: $newPerPageJs,\n";
            $newJsContent .= "    cse_id: '$newCse',\n";
            $newJsContent .= "    guestbook_plugin: '$newGbPlugin',\n";
            $newJsContent .= "    guestbook_per_page: $newGbPerPage\n";
            $newJsContent .= "};";

            if (file_put_contents($configFile, $newJsContent)) {
                if ($isAjax) {
                    echo json_encode(['success' => true, 'message' => __('msg_settings_saved') . ' (config.js)']);
                    exit;
                }
                $msg = __('msg_settings_saved') . ' (config.js)';
            } else {
                $error = __('error_config_write') . ' (config.js)';
            }
        }
        if ($isAjax && $error) {
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
    }
}

$themeFiles = glob(__DIR__ . '/../blog*.css');
$themes = array();
foreach ($themeFiles as $f) {
    if (strpos($f, '.min.css') !== false) continue;
    $name = basename($f, '.css');
    $themes[] = $name;
}
if (empty($themes)) $themes = array('blog');

?>
<!DOCTYPE html>
<html lang="<?php echo getWebLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('settings_title'); ?> - Blog Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="assets/js/sweetalert2.all.min.js"></script>
    <style>
        .settings-section-title {
            padding: 10px 15px;
            background-color: #e9ecef;
            border-radius: 5px;
            margin-top: 10px;
            margin-bottom: 20px;
            font-weight: bold;
            color: #495057;
            border-left: 5px solid #0d6efd;
        }
        .card-settings {
            border: 1px solid #dee2e6;
            border-top: none;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <?php require 'sidebar_inc.php'; ?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 bg-light">
        <h2 class="mb-4"><?php echo __('settings_title'); ?></h2>

        <?php if ($msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Section 1: Backend Settings -->
        <div class="card shadow-sm mb-4">
            <div class="settings-section-title mb-0"><?php echo __('section_backend'); ?></div>
            <div class="card-body">
                <form method="POST" id="formBackend">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_blog_title'); ?></label>
                        <input type="text" name="blog_title" class="form-control" value="<?php echo htmlspecialchars($currentConfig['blog_title']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_blog_description'); ?></label>
                        <input type="text" name="blog_description" class="form-control" value="<?php echo htmlspecialchars($currentConfig['blog_description']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_blog_introduce'); ?></label>
                        <textarea name="blog_introduce" class="form-control" rows="2"><?php echo htmlspecialchars($currentConfig['blog_introduce']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold"><?php echo __('label_blog_lang'); ?></label>
                            <select name="blog_lang" class="form-select">
                                <option value="zh_TW" <?php echo ($currentConfig['blog_lang'] == 'zh_TW') ? 'selected' : ''; ?>><?php echo __('lang_zh_tw'); ?></option>
                                <option value="en_US" <?php echo ($currentConfig['blog_lang'] == 'en_US') ? 'selected' : ''; ?>><?php echo __('lang_en_us'); ?></option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold"><?php echo __('label_timezone'); ?></label>
                            <select name="timezone" class="form-select">
                                <option value="Asia/Taipei" <?php echo ($currentConfig['timezone'] == 'Asia/Taipei') ? 'selected' : ''; ?>>Asia/Taipei</option>
                                <option value="UTC" <?php echo ($currentConfig['timezone'] == 'UTC') ? 'selected' : ''; ?>>UTC</option>
                                <option value="America/New_York" <?php echo ($currentConfig['timezone'] == 'America/New_York') ? 'selected' : ''; ?>>America/New_York</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_album_path'); ?></label>
                        <div class="input-group">
                            <input type="text" name="album_path" id="album_path_input" class="form-control" value="<?php echo htmlspecialchars($currentConfig['album_path']); ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="openFolderPicker()">
                                <i class="bi bi-folder2-open"></i> 瀏覽...
                            </button>
                            <button class="btn btn-outline-danger" type="button" onclick="document.getElementById('album_path_input').value = '';">
                                <i class="bi bi-x-circle"></i> 清空
                            </button>
                        </div>
                        <div class="form-text"><?php echo __('hint_album_path'); ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_posts_per_page'); ?></label>
                        <input type="number" name="posts_per_page" class="form-control" value="<?php echo htmlspecialchars($currentConfig['posts_per_page']); ?>" min="1" max="100">
                        <div class="form-text"><?php echo __('hint_posts_per_page'); ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_blog_favicon'); ?></label>
                        <input type="text" name="blog_favicon" class="form-control" value="<?php echo htmlspecialchars($currentConfig['blog_favicon']); ?>" placeholder="/static/icon-192.png">
                        <div class="form-text"><?php echo __('hint_blog_favicon'); ?></div>
                    </div>

                    <hr>
                    <div class="settings-section-title bg-light border-start-0 border-top border-bottom rounded-0 ps-0 mb-3" style="border-left: 5px solid #6f42c1 !important;">
                        <i class="bi bi-robot me-2"></i><?php echo __('section_ai'); ?>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="ai_enabled" id="aiEnabledSwitch" <?php echo (isset($aiConfig) && $aiConfig['enabled']) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="aiEnabledSwitch"><?php echo __('label_ai_enabled'); ?></label>
                    </div>

                    <div id="ai-settings-fields" class="<?php echo (isset($aiConfig) && $aiConfig['enabled']) ? '' : 'd-none'; ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold"><?php echo __('label_ai_api_key'); ?></label>
                            <div class="input-group">
                                <input type="password" name="ai_api_key" id="aiApiKeyInput" class="form-control" value="<?php echo htmlspecialchars(isset($aiConfig) ? $aiConfig['api_key'] : ''); ?>" placeholder="AI Studio API Key">
                                <button class="btn btn-outline-secondary" type="button" id="btn-fetch-models">
                                    <i class="bi bi-arrow-clockwise"></i> 抓取可用模型
                                </button>
                            </div>
                            <div class="form-text"><?php echo __('hint_ai_api_key'); ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold"><?php echo __('label_ai_model'); ?></label>
                            <?php $currentModel = isset($aiConfig) ? $aiConfig['model'] : 'gemini-3-flash-preview'; ?>
                            <select name="ai_model" class="form-select" id="aiModelSelect">
                                <?php if (!empty($aiModelsCache)): ?>
                                    <?php foreach ($aiModelsCache as $m): ?>
                                        <option value="<?php echo htmlspecialchars($m['id']); ?>" <?php echo ($currentModel == $m['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($m['displayName']); ?> (<?php echo htmlspecialchars($m['id']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="<?php echo htmlspecialchars($currentModel); ?>" selected>
                                        目前設定: <?php echo htmlspecialchars($currentModel); ?>
                                    </option>
                                    <option disabled>─── 請點擊上方按鈕重新抓取 ───</option>
                                <?php endif; ?>
                            </select>
                            <div class="form-text"><?php echo __('hint_ai_model'); ?></div>
                        </div>
                    </div>

                    <script>
                        document.getElementById('aiEnabledSwitch').addEventListener('change', function() {
                            document.getElementById('ai-settings-fields').classList.toggle('d-none', !this.checked);
                        });

                        document.getElementById('btn-fetch-models').addEventListener('click', async function() {
                            const apiKey = document.getElementById('aiApiKeyInput').value.trim();
                            if (!apiKey) {
                                Swal.fire('請先輸入 API Key。', '', 'warning');
                                return;
                            }

                            const btn = this;
                            const select = document.getElementById('aiModelSelect');
                            const originalBtnHtml = btn.innerHTML;
                            
                            btn.disabled = true;
                            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 抓取中...';

                            try {
                                const url = `https://generativelanguage.googleapis.com/v1beta/models?key=${apiKey}`;
                                const resp = await fetch(url);
                                const data = await resp.json();

                                if (data.error) throw new Error(data.error.message || 'API Key 驗證失敗');

                                const models = data.models || [];
                                const filtered = models.filter(m => 
                                    m.supportedGenerationMethods.includes('generateContent') && 
                                    m.name.toLowerCase().includes('gemini')
                                );

                                if (filtered.length === 0) throw new Error('找不到可用的 Gemini 模型。');

                                // 準備快取資料
                                const cacheData = filtered.map(m => ({
                                    id: m.name.replace('models/', ''),
                                    displayName: m.displayName
                                }));

                                // AJAX 存入快取檔案
                                const fd = new FormData();
                                fd.append('action', 'save_ai_cache');
                                fd.append('models', JSON.stringify(cacheData));
                                fd.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
                                
                                await fetch('settings.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });

                                // 更新選單
                                const currentVal = select.value;
                                select.innerHTML = '';
                                cacheData.forEach(m => {
                                    const opt = document.createElement('option');
                                    opt.value = m.id;
                                    opt.textContent = `${m.displayName} (${m.id})`;
                                    if (m.id === currentVal) opt.selected = true;
                                    select.appendChild(opt);
                                });

                                Swal.fire('成功', `已獲取並快取 ${filtered.length} 個模型。`, 'success');

                            } catch (e) {
                                Swal.fire('抓取失敗', e.message, 'error');
                            } finally {
                                btn.disabled = false;
                                btn.innerHTML = originalBtnHtml;
                            }
                        });

                        // --- AJAX Settings Save Logic ---
                        document.querySelectorAll('.btn-ajax-save').forEach(btn => {
                            btn.addEventListener('click', async function() {
                                const action = this.dataset.formAction;
                                const confirmMsg = this.dataset.confirm;
                                const form = this.closest('form');

                                if (confirmMsg && !confirm(confirmMsg)) return;

                                const formData = new FormData(form);
                                formData.append(action, '1'); 
                                formData.append('ajax', '1');
                                // 確保 checkbox 狀態有帶入 (如果沒選，FormData 可能不會帶入)
                                if (form.id === 'formBackend') {
                                    const aiEnabled = document.getElementById('aiEnabledSwitch');
                                    if (aiEnabled && !aiEnabled.checked) {
                                        formData.append('ai_enabled_hidden', 'false'); // 提供一個隱藏欄位讓後端知道要設為 false
                                    }
                                }

                                const originalHtml = this.innerHTML;
                                this.disabled = true;
                                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 儲存中...';

                                try {
                                    const resp = await fetch('settings.php', {
                                        method: 'POST',
                                        body: formData,
                                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                    });
                                    const res = await resp.json();
                                    
                                    if (res.success) {
                                        Swal.fire('已儲存', res.message, 'success');
                                    } else {
                                        Swal.fire('錯誤', res.message, 'error');
                                    }
                                } catch (e) {
                                    Swal.fire('傳輸失敗', e.message, 'error');
                                } finally {
                                    this.disabled = false;
                                    this.innerHTML = originalHtml;
                                }
                            });
                        });
                    </script>

                    <div class="text-end">
                        <button type="submit" name="save_backend" class="btn btn-primary px-4">
                            <i class="bi bi-save"></i> 儲存後端設定
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section: Admin Account Settings -->
        <div class="card shadow-sm mb-4">
            <div class="settings-section-title mb-0"><?php echo __('label_admin_account'); ?></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold"><?php echo __('label_new_username'); ?></label>
                            <input type="text" name="new_username" class="form-control" value="<?php echo htmlspecialchars($_SESSION['admin_user']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold"><?php echo __('label_new_password'); ?></label>
                            <input type="password" name="new_password" class="form-control" placeholder="<?php echo __('hint_password_keep'); ?>">
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" name="save_account" class="btn btn-warning px-4" onclick="return confirm('確定要更新管理者帳號密碼嗎？');">
                            <i class="bi bi-person-gear"></i> <?php echo __('btn_save_account'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 2: Frontend Settings -->
        <div class="card shadow-sm mb-5">
            <div class="settings-section-title mb-0"><?php echo __('section_frontend'); ?></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_api_type'); ?></label>
                        <select name="api_type" class="form-select">
                            <option value="api_filebase" <?php echo ($currentConfig['api_type'] == 'api_filebase') ? 'selected' : ''; ?>><?php echo __('opt_api_file'); ?> (api_filebase)</option>
                            <option value="api_dbsqlbase" <?php echo ($currentConfig['api_type'] == 'api_dbsqlbase') ? 'selected' : ''; ?>><?php echo __('opt_api_db'); ?> (api_dbsqlbase)</option>
                            <option value="api_sqlitebase" <?php echo ($currentConfig['api_type'] == 'api_sqlitebase') ? 'selected' : ''; ?>><?php echo __('opt_api_sqlite'); ?> (api_sqlitebase)</option>
                            <option value="json" <?php echo ($currentConfig['api_type'] == 'json') ? 'selected' : ''; ?>>Static JSON (Pre-generated)</option>
                        </select>
                        <div class="form-text">控制前端網頁讀取資料的來源 API。</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_theme'); ?></label>
                        <select name="theme_file" class="form-select">
                            <?php foreach ($themes as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>" <?php echo ($currentConfig['theme_file'] == $t) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_posts_per_page_js'); ?></label>
                        <input type="number" name="posts_per_page_js" class="form-control" value="<?php echo htmlspecialchars($currentConfig['posts_per_page_js']); ?>" min="1" max="100">
                        <div class="form-text"><?php echo __('hint_posts_per_page_js'); ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_cse_id'); ?></label>
                        <input type="text" name="cse_id" class="form-control" value="<?php echo htmlspecialchars(isset($currentConfig['cse_id']) ? $currentConfig['cse_id'] : ''); ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold"><?php echo __('label_guestbook_plugin'); ?></label>
                            <div class="input-group">
                                <input type="text" name="guestbook_plugin" id="guestbook_plugin_input" class="form-control" value="<?php echo htmlspecialchars(isset($currentConfig['guestbook_plugin']) ? $currentConfig['guestbook_plugin'] : ''); ?>" placeholder="MessageBoard/static/guestbook.js">
                                <button class="btn btn-outline-secondary" type="button" onclick="openFilePicker('guestbook_plugin_input', 'js')">
                                    <i class="bi bi-filetype-js"></i> 瀏覽...
                                </button>
                                <button class="btn btn-outline-danger" type="button" onclick="document.getElementById('guestbook_plugin_input').value = '';">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                            <div class="form-text"><?php echo __('hint_guestbook_plugin'); ?></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold"><?php echo __('label_guestbook_per_page'); ?></label>
                            <input type="number" name="guestbook_per_page" class="form-control" value="<?php echo htmlspecialchars(isset($currentConfig['guestbook_per_page']) ? $currentConfig['guestbook_per_page'] : 5); ?>" min="1" max="50">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" name="save_frontend" class="btn btn-secondary px-4">
                            <i class="bi bi-save"></i> 儲存前端設定
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require 'common_js_inc.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Folder Picker Modal -->
<div class="modal fade" id="folderPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">選擇相簿目錄</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2 small text-muted">目前位置：<span id="current-folder-display">/</span></div>
                <div class="list-group" id="folder-list" style="max-height: 400px; overflow-y: auto;">
                    <!-- 動態載入目錄 -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-folder">確定選擇</button>
            </div>
        </div>
    </div>
</div>

<script>
    let folderModal;
    let selectedPath = '';
    let targetInputId = '';
    let currentPickerMode = 'dir'; // 'dir' or 'file'
    let currentExtFilter = '';

    function openFolderPicker() {
        targetInputId = 'album_path_input';
        currentPickerMode = 'dir';
        currentExtFilter = '';
        if (!folderModal) folderModal = new bootstrap.Modal(document.getElementById('folderPickerModal'));
        loadItems('');
        folderModal.show();
    }

    function openFilePicker(inputId, ext = '') {
        targetInputId = inputId;
        currentPickerMode = 'file';
        currentExtFilter = ext;
        if (!folderModal) folderModal = new bootstrap.Modal(document.getElementById('folderPickerModal'));
        document.querySelector('#folderPickerModal .modal-title').innerText = '選擇插件檔案 (.js)';
        loadItems('');
        folderModal.show();
    }

    async function loadItems(path) {
        const container = document.getElementById('folder-list');
        const display = document.getElementById('current-folder-display');
        container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>';
        
        try {
            const showFiles = currentPickerMode === 'file' ? '1' : '0';
            const resp = await fetch(`tool_folders.php?action=list&path=${encodeURIComponent(path)}&show_files=${showFiles}&ext=${currentExtFilter}`);
            const data = await resp.json();
            display.innerText = data.current || '/ (Blog Root)';

            let html = '';
            data.items.forEach(item => {
                let icon = 'bi-folder';
                let action = '';
                
                if (item.is_parent) {
                    icon = 'bi-arrow-90deg-up';
                    action = `loadItems('${item.path}')`;
                } else if (item.is_dir) {
                    icon = 'bi-folder';
                    // 如果是目錄模式，點擊文字選取目錄，點擊右側進入目錄
                    // 如果是檔案模式，點擊目錄直接進入
                    if (currentPickerMode === 'dir') {
                        action = `selectItem('${item.path}')`;
                    } else {
                        action = `loadItems('${item.path}')`;
                    }
                } else if (item.is_file) {
                    icon = 'bi-file-earmark-code';
                    action = `selectItem('${item.path}')`;
                }

                html += `
                <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                   onclick="event.preventDefault(); ${action}">
                    <span><i class="bi ${icon} me-2"></i>${item.name}</span>
                    ${(item.is_dir && !item.is_parent) ? `<button class="btn btn-sm btn-link p-0" onclick="event.stopPropagation(); loadItems('${item.path}')"><i class="bi bi-chevron-right"></i></button>` : ''}
                </a>`;
            });
            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger">載入失敗</div>';
        }
    }

    function selectItem(path) {
        selectedPath = path;
        document.querySelectorAll('#folder-list .list-group-item').forEach(el => el.classList.remove('active'));
        event.currentTarget.classList.add('active');
    }

    document.getElementById('btn-confirm-folder').addEventListener('click', () => {
        document.getElementById(targetInputId).value = selectedPath;
        folderModal.hide();
    });
</script>
</body>
</html>