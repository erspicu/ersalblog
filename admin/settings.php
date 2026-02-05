<?php
require_once 'auth.php';
require_once 'data_provider.php';
requireLogin();

$dataManager = new DataManager();
$configFile = __DIR__ . '/../config.js';
$msg = '';
$error = '';

// Helper to read config values
function getConfigValues($content) {
    $values = [];
    if (preg_match("/api_type:\s*'([^']+)'/", $content, $m)) $values['api_type'] = $m[1];
    if (preg_match("/theme_file:\s*'([^']+)'/", $content, $m)) $values['theme_file'] = $m[1];
    if (preg_match("/cse_id:\s*'([^']+)'/", $content, $m)) $values['cse_id'] = $m[1];
    return $values;
}

// Read current config
$configContent = file_exists($configFile) ? file_get_contents($configFile) : '';
$currentConfig = getConfigValues($configContent);

// Defaults from config.php (already included via auth.php -> data_provider.php)
$currentConfig['blog_lang'] = $GLOBALS['blog_lang'] ?? 'zh_TW';
$currentConfig['timezone'] = $GLOBALS['blog_timezone'] ?? 'Asia/Taipei';

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF Validation Failed");
    }

    $newApi = $_POST['api_type'] ?? 'api_filebase';
    $newTheme = $_POST['theme_file'] ?? 'blog';
    $newCse = $_POST['cse_id'] ?? '';
    $newLang = $_POST['blog_lang'] ?? 'zh_TW';
    $newTimezone = $_POST['timezone'] ?? 'Asia/Taipei';

    // 1. Update config.js (API, Theme, CSE)
    $newJsContent = "var AppConfig = {\n";
    $newJsContent .= "    api_type: '$newApi',\n";
    $newJsContent .= "    theme_file: '$newTheme',\n";
    $newJsContent .= "    cse_id: '$newCse'\n";
    $newJsContent .= "};";

    // 2. Update config.php (Lang, Timezone) using regex to preserve other settings
    $phpFile = __DIR__ . '/../config.php';
    $phpContent = file_get_contents($phpFile);
    $phpContent = preg_replace("/(\\\$blog_lang\s*=\s*['\"])([^'\"]*)(['\"];)/", "$1$newLang$3", $phpContent);
    $phpContent = preg_replace("/(\\\$blog_timezone\s*=\s*['\"])([^'\"]*)(['\"];)/", "$1$newTimezone$3", $phpContent);

    if (file_put_contents($configFile, $newJsContent) && file_put_contents($phpFile, $phpContent)) {
        $msg = __('msg_settings_saved');
        $currentConfig = getConfigValues($newJsContent); // Refresh JS parts
        $currentConfig['blog_lang'] = $newLang;
        $currentConfig['timezone'] = $newTimezone;
    } else {
        $error = __('error_config_write');
    }
}

// Scan Themes
$themeFiles = glob(__DIR__ . '/../blog*.css');
$themes = [];
foreach ($themeFiles as $f) {
    if (strpos($f, '.min.css') !== false) continue; // Skip minified
    $name = basename($f, '.css');
    $themes[] = $name;
}
if (empty($themes)) $themes = ['blog'];

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLang ?? 'zh_TW'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('settings_title'); ?> - Blog Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar a { color: #cfd2d6; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
        .main-content { padding: 20px; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column flex-shrink-0 p-3" style="width: 250px;">
        <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <span class="fs-4"><?php echo __('nav_brand'); ?></span>
        </a>
        <hr>
        <div class="text-center mb-3">
            <span class="badge <?php echo ($dataManager->getSource() === 'db') ? 'bg-success' : (($dataManager->getSource() === 'sqlite') ? 'bg-info text-dark' : 'bg-warning text-dark'); ?>">
                <?php echo __('mode_label'); ?>: <?php echo ($dataManager->getSource() === 'db') ? __('mode_db_short') : (($dataManager->getSource() === 'sqlite') ? 'SQLite' : __('mode_file_short')); ?>
            </span>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="index.php">
                    <?php echo __('nav_dashboard'); ?>
                </a>
            </li>
            <li>
                <a href="posts.php">
                    <?php echo __('nav_posts'); ?>
                </a>
            </li>
            <li>
                <a href="categories.php">
                    <?php echo __('nav_categories'); ?>
                </a>
            </li>
            <li>
                <a href="tool_migrate.php">
                    <?php echo __('nav_import'); ?>
                </a>
            </li>
            <li>
                <a href="tool_backup.php">
                    <?php echo __('nav_backup'); ?>
                </a>
            </li>
            <li>
                <a href="settings.php" class="active">
                    <?php echo __('nav_settings'); ?>
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="../blog.html" target="_blank"><?php echo __('nav_preview'); ?></a>
            <a href="logout.php" class="text-danger mt-2"><?php echo __('nav_logout'); ?></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 bg-light">
        <h2 class="mb-4"><?php echo __('settings_title'); ?></h2>

        <?php if ($msg): ?>
            <div class="alert alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
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
                        <label class="form-label fw-bold"><?php echo __('label_blog_lang'); ?></label>
                        <select name="blog_lang" class="form-select">
                            <option value="zh_TW" <?php echo ($currentConfig['blog_lang'] == 'zh_TW') ? 'selected' : ''; ?>><?php echo __('lang_zh_tw'); ?></option>
                            <option value="en_US" <?php echo ($currentConfig['blog_lang'] == 'en_US') ? 'selected' : ''; ?>><?php echo __('lang_en_us'); ?></option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_timezone'); ?></label>
                        <select name="timezone" class="form-select">
                            <option value="Asia/Taipei" <?php echo ($currentConfig['timezone'] == 'Asia/Taipei') ? 'selected' : ''; ?>>Asia/Taipei</option>
                            <option value="UTC" <?php echo ($currentConfig['timezone'] == 'UTC') ? 'selected' : ''; ?>>UTC</option>
                            <option value="America/New_York" <?php echo ($currentConfig['timezone'] == 'America/New_York') ? 'selected' : ''; ?>>America/New_York</option>
                        </select>
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
                        <label class="form-label fw-bold"><?php echo __('label_cse_id'); ?></label>
                        <input type="text" name="cse_id" class="form-control" value="<?php echo htmlspecialchars($currentConfig['cse_id'] ?? ''); ?>">
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary"><?php echo __('btn_save_settings'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require 'common_js_inc.php'; ?>
</body>
</html>
