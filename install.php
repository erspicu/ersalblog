<?php
/**
 * ersalblog Installation Wizard
 * Supports English (Default) and Traditional Chinese.
 */

// --- 語言設定 ---
$available_langs = [
    'en_US' => 'English',
    'zh_TW' => '繁體中文'
];

// 決定當前語言
$lang = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'en_US';
if (!isset($available_langs[$lang])) $lang = 'en_US';

// 存入 Cookie 以持久化
setcookie('lang', $lang, time() + (86400 * 30), "/");

// 載入語言檔
$lang_file = __DIR__ . "/langs/admin/install_{$lang}.php";
$translations = file_exists($lang_file) ? include $lang_file : [];

// 引入共用系統輔助函式
require_once __DIR__ . '/admin/system_helper.php';

// 輔助函式：取得翻譯文字
function _t($key) {
    global $translations;
    return $translations[$key] ?? $key;
}

/**
 * 檢測是否為 WSL2 環境且正在存取 Windows 掛載目錄 (NTFS)
 */
function is_wsl_ntfs() {
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'LIN') return false;
    if (file_exists('/proc/version')) {
        $version = file_get_contents('/proc/version');
        if (strpos(strtolower($version), 'microsoft') !== false) {
            if (strpos(__DIR__, '/mnt/') === 0) return true;
        }
    }
    return false;
}

// --- 處理 AJAX 請求 ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    if ($_GET['action'] === 'check_db_connection') {
        $host = $_POST['db_host'] ?? '';
        $user = $_POST['db_user'] ?? '';
        $pass = $_POST['db_pass'] ?? '';
        $dbname = $_POST['db_name'] ?? '';

        if (!extension_loaded('pdo_mysql')) {
            $response['message'] = _t('no_pdo_mysql');
            echo json_encode($response); exit;
        }

        try {
            $dsn = "mysql:host=$host;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
            if (!empty($dbname)) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($dbname));
                if ((bool) $stmt->fetchColumn()) {
                    $response['success'] = true;
                    $response['message'] = _t('conn_success') . ' ' . sprintf(_t('db_exists'), $dbname);
                } else {
                    $response['success'] = true;
                    $response['message'] = _t('conn_success') . ' ' . sprintf(_t('db_not_exists'), $dbname);
                    $response['warning'] = true;
                }
            } else {
                $response['success'] = true;
                $response['message'] = _t('conn_success');
            }
        } catch (PDOException $e) {
            $response['message'] = _t('conn_failed') . ': ' . $e->getMessage();
        }
        echo json_encode($response); exit;

    } elseif ($_GET['action'] === 'check_sqlite_connection') {
        $sqlitePath = $_POST['sqlite_path'] ?? 'blog.sqlite3';
        if (!extension_loaded('pdo_sqlite')) {
            $response['message'] = _t('no_pdo_sqlite');
            echo json_encode($response); exit;
        }
        $targetDir = __DIR__; 
        if (dirname($sqlitePath) !== '.') {
            $targetDir = __DIR__ . '/' . dirname($sqlitePath);
            if (!is_dir($targetDir)) {
                 $response['message'] = sprintf(_t('dir_not_exists'), dirname($sqlitePath));
                 echo json_encode($response); exit;
            }
        }
        if (!is_writable($targetDir)) {
             $response['message'] = sprintf(_t('dir_not_writable'), $targetDir);
        } else {
             $response['success'] = true;
             $targetFile = $targetDir . '/' . basename($sqlitePath);
             if (file_exists($targetFile)) {
                 if (is_writable($targetFile)) $response['message'] = _t('file_found_writable');
                 else { $response['success'] = false; $response['message'] = _t('file_found_not_writable'); }
             } else {
                 $response['message'] = _t('sqlite_ok');
             }
        }
        echo json_encode($response); exit;

    } elseif ($_GET['action'] === 'check_file_connection') {
        $requiredDirs = ['.' => 'Root', 'contents' => 'contents', 'contents/post_files' => 'post_files', 'backup' => 'backup'];
        $errors = [];
        foreach ($requiredDirs as $path => $label) {
            $fullPath = __DIR__ . '/' . $path;
            if (!file_exists($fullPath) && $path !== '.') { @mkdir($fullPath, 0777, true); }
            if (file_exists($fullPath) && !is_writable($fullPath)) { $errors[] = "❌ " . sprintf(_t('dir_not_writable'), $label); }
        }
        if (empty($errors)) { $response['success'] = true; $response['message'] = _t('file_ok'); } 
        else { $response['message'] = _t('file_failed') . '<br>' . implode('<br>', $errors); }
        echo json_encode($response); exit;

    } elseif ($_GET['action'] === 'check_system_permissions') {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $response['success'] = true; $response['message'] = _t('win_no_perms');
            echo json_encode($response); exit;
        }
        if (is_wsl_ntfs()) {
            $response['success'] = true; $response['message'] = _t('wsl_ntfs_perms');
            $response['is_wsl_ntfs'] = true;
            echo json_encode($response); exit;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        $invalidCount = 0;
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if (strpos($path, '/.git') !== false || strpos($path, '/node_modules') !== false) continue;
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            if ($item->isDir()) { if ($perms !== '0755') $invalidCount++; } 
            else { if (in_array(pathinfo($path, PATHINFO_EXTENSION), ['php', 'html', 'js', 'css'])) { if ($perms !== '0644') $invalidCount++; } } 
        }
        if ($invalidCount === 0) { $response['success'] = true; $response['message'] = _t('fix_success'); } 
        else { $response['success'] = false; $response['message'] = "Found $invalidCount items with incorrect permissions."; $response['invalid_count'] = $invalidCount; }
        echo json_encode($response); exit;

    } elseif ($_GET['action'] === 'fix_system_permissions') {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' || is_wsl_ntfs()) { 
            $response['message'] = "Not supported or not needed in this environment."; 
            echo json_encode($response); exit; 
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        $fixed = 0; $failed = 0;
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if (strpos($path, '/.git') !== false || strpos($path, '/node_modules') !== false) continue;
            if ($item->isDir()) { if (!@chmod($path, 0755)) $failed++; else $fixed++; } 
            else { if (in_array(pathinfo($path, PATHINFO_EXTENSION), ['php', 'html', 'js', 'css'])) { if (!@chmod($path, 0644)) $failed++; else $fixed++; } } 
        }
        if ($failed > 0) { $response['success'] = false; $response['message'] = sprintf(_t('fix_finished_errors'), $failed); } 
        else { $response['success'] = true; $response['message'] = _t('fix_success'); }
        echo json_encode($response); exit;

    } elseif ($_GET['action'] === 'install') {
        if (file_exists('config.php')) {
            $response['message'] = _t('config_exists');
            echo json_encode($response); exit;
        }

        $blog_title = $_POST['blog_title'] ?? '';
        $blog_description = $_POST['blog_description'] ?? '';
        $blog_introduce = $_POST['blog_introduce'] ?? '';
        $blog_preview = $_POST['blog_preview'] ?? '';
        $site_url = $_POST['site_url'] ?? '';
        $timezone = $_POST['timezone'] ?? 'Asia/Taipei';
        $debug_mode = isset($_POST['debug_mode']) ? 'true' : 'false';

        $api_type = $_POST['api_type'] ?? 'api_dbsqlbase';
        $sqlite_path = $_POST['sqlite_path'] ?? 'blog.sqlite3';

        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';

        $admin_user = $_POST['admin_user'] ?? 'admin';
        $admin_pass = $_POST['admin_pass'] ?? '';
        $session_secret = $_POST['session_secret'] ?? '';

        $cse_id = $_POST['cse_id'] ?? '';
        $theme_file = $_POST['theme_file'] ?? 'blog';

        // Generate config.php
        $config_php = "<?php\n\n";
        $config_php .= "\$blog_title = " . var_export($blog_title, true) . "; //Blog網站標題\n";
        $config_php .= "\$blog_description = " . var_export($blog_description, true) . "; //Blog SEO描述屬性\n";
        $config_php .= "\$blog_introduce = " . var_export($blog_introduce, true) . "; //描述一下你的blog用途或是特色\n";
        $config_php .= "\$blog_preview = " . var_export($blog_preview, true) . "; //預覽圖網址\n";
        $config_php .= "\$site_url = " . var_export($site_url, true) . "; // 網站網址\n\n";
        $config_php .= "\$sqlite_path = " . var_export($sqlite_path, true) . "; // SQLite 資料庫路徑\n\n";
        $config_php .= "\$dbConfig = [\n";
        $config_php .= "    'host'     => " . var_export($db_host, true) . ",\n";
        $config_php .= "    'dbname'   => " . var_export($db_name, true) . ",\n";
        $config_php .= "    'username' => " . var_export($db_user, true) . ",\n";
        $config_php .= "    'password' => " . var_export($db_pass, true) . ",\n";
        $config_php .= "    'charset'  => 'utf8mb4',\n";
        $config_php .= "    'debug_mode' => $debug_mode\n";
        $config_php .= "];\n\n";
        $config_php .= "\$adminConfig = [\n";
        $config_php .= "    'username' => " . var_export($admin_user, true) . ",\n";
        $config_php .= "    'password' => " . var_export($admin_pass, true) . ",\n";
        $config_php .= "    'session_secret' => " . var_export($session_secret, true) . "\n";
        $config_php .= "];\n\n";
        $config_php .= "date_default_timezone_set(" . var_export($timezone, true) . ");\n";
        $config_php .= "?>";

        // Generate config.js
        $config_js = "var AppConfig = {\n";
        $config_js .= "    api_type: " . var_export($api_type, true) . ",\n";
        $config_js .= "    theme_file: " . var_export($theme_file, true) . ",\n";
        $config_js .= "    cse_id: " . var_export($cse_id, true) . "\n";
        $config_js .= "};";

        if (@file_put_contents('config.php', $config_php) && @file_put_contents('config.js', $config_js)) {
            $response['success'] = true;
            $response['message'] = _t('install_success');
        } else {
            $response['message'] = _t('install_failed') . " Permission denied or write error.";
        }
        echo json_encode($response); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang === 'zh_TW' ? 'zh-TW' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo _t('page_title'); ?></title>
    <link href="admin/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .install-container { max-width: 800px; margin: 50px auto; background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); position: relative; }
        .step-section { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .step-section:last-child { border-bottom: none; }
        .step-title { font-size: 1.25rem; color: #0d6efd; margin-bottom: 20px; border-left: 5px solid #0d6efd; padding-left: 10px; }
        .hidden { display: none; }
        .lang-switcher { position: absolute; top: 20px; right: 20px; }
    </style>
</head>
<body>

<div class="container">
    <div class="install-container">
        <!-- Language Switcher -->
        <div class="lang-switcher">
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="langSelect" data-bs-toggle="dropdown" aria-expanded="false">
                    🌐 <?php echo $available_langs[$lang]; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langSelect">
                    <?php foreach ($available_langs as $code => $name):
                        $activeClass = ($lang === $code) ? ' active' : '';
                    ?>
                        <li><a class="dropdown-item<?php echo $activeClass; ?>" href="?lang=<?php echo $code; ?>"><?php echo $name; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <h2 class="text-center mb-4"><?php echo _t('main_title'); ?></h2>
        <p class="text-muted text-center mb-5"><?php echo _t('main_desc'); ?></p>

        <form id="installForm" method="post" action="?action=install">
            
            <!-- 0. System Info -->
            <div class="step-section">
                <h4 class="step-title"><?php echo _t('step_0'); ?></h4>
                <?php
                // 嘗試讀取版本資訊
                $app_ver = 'Unknown';
                $gemini_ver = 'Unknown';
                if (file_exists('admin/version_config.php')) {
                    include 'admin/version_config.php';
                    if (defined('APP_VERSION')) $app_ver = APP_VERSION;
                    if (defined('GEMINI_CLI_VERSION')) $gemini_ver = GEMINI_CLI_VERSION;
                }
                ?>
                <div class="row g-3">
                    <!-- Row 1: Versions -->
                    <div class="col-md-6">
                        <label class="form-label"><?php echo _t('app_version'); ?></label>
                        <input type="text" class="form-control" value="<?php echo $app_ver; ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo _t('gemini_version'); ?></label>
                        <input type="text" class="form-control" value="<?php echo $gemini_ver; ?>" readonly>
                    </div>
                    
                    <!-- Row 2: PHP & OS -->
                    <div class="col-md-6">
                        <label class="form-label"><?php echo _t('php_version'); ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo phpversion(); ?>" readonly>
                            <?php if (version_compare(phpversion(), '7.0.0', '<')): ?>
                                <span class="input-group-text bg-danger text-white">⚠️</span>
                            <?php else: ?>
                                <span class="input-group-text bg-success text-white">✅</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo _t('os_info'); ?></label>
                        <input type="text" class="form-control" value="<?php echo get_detailed_os_info(); ?>" readonly title="<?php echo php_uname('a'); ?>">
                    </div>
                    
                    <?php if (version_compare(phpversion(), '7.0.0', '<')): ?>
                        <div class="col-12">
                            <div class="alert alert-danger mb-0 py-2 small fw-bold"><?php echo _t('php_warning'); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN'): ?>
                    <!-- Permission Check (Unix-like only) -->
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo _t('file_perms'); ?></h6>
                                <p class="card-text small text-muted"><?php echo _t('file_perms_desc'); ?></p>
                                <div id="perm_status_area">
                                    <button type="button" class="btn btn-info btn-sm text-white" onclick="checkSystemPermissions()">
                                        <?php echo _t('btn_check_perms'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 1. General Settings -->
            <div class="step-section">
                <h4 class="step-title"><?php echo _t('step_1'); ?></h4>
                <div class="row g-3">
                    <div class="col-md-12"><label for="blog_title" class="form-label"><?php echo _t('blog_title'); ?></label><input type="text" class="form-control" id="blog_title" name="blog_title" placeholder="<?php echo _t('blog_title_placeholder'); ?>" required></div>
                    <div class="col-md-12"><label for="blog_description" class="form-label"><?php echo _t('blog_desc'); ?></label><input type="text" class="form-control" id="blog_description" name="blog_description" placeholder="<?php echo _t('blog_desc_placeholder'); ?>"></div>
                    <div class="col-md-12"><label for="blog_introduce" class="form-label"><?php echo _t('blog_intro'); ?></label><textarea class="form-control" id="blog_introduce" name="blog_introduce" rows="3" placeholder="<?php echo _t('blog_intro_placeholder'); ?>"></textarea></div>
                    <div class="col-md-12"><label for="blog_preview" class="form-label"><?php echo _t('blog_preview'); ?></label><input type="url" class="form-control" id="blog_preview" name="blog_preview" placeholder="<?php echo _t('blog_preview_placeholder'); ?>"></div>
                    <div class="col-md-6"><label for="site_url" class="form-label"><?php echo _t('site_url'); ?></label><input type="url" class="form-control" id="site_url" name="site_url" required><div class="form-text"><?php echo _t('site_url_help'); ?></div></div>
                    <div class="col-md-6"><label for="timezone" class="form-label"><?php echo _t('timezone'); ?></label>
                        <select class="form-select" id="timezone" name="timezone">
                            <option value="Asia/Taipei" selected>Asia/Taipei</option>
                            <option value="UTC">UTC</option>
                            <option value="America/New_York">America/New_York</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="debug_mode" name="debug_mode" checked>
                            <label class="form-check-label" for="debug_mode"><?php echo _t('debug_mode'); ?></label>
                        </div>
                        <div class="form-text"><?php echo _t('debug_mode_help'); ?></div>
                    </div>
                </div>
            </div>

            <!-- 2. Storage Engine -->
            <div class="step-section">
                <h4 class="step-title"><?php echo _t('step_2'); ?></h4>
                <div class="mb-3">
                    <label class="form-label d-block"><?php echo _t('select_engine'); ?></label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="api_type" id="type_file" value="api_filebase" onchange="toggleDbSettings()">
                        <label class="btn btn-outline-secondary" for="type_file"><?php echo _t('engine_file'); ?></label>
                        <input type="radio" class="btn-check" name="api_type" id="type_sqlite" value="api_sqlitebase" onchange="toggleDbSettings()">
                        <label class="btn btn-outline-secondary" for="type_sqlite"><?php echo _t('engine_sqlite'); ?></label>
                        <input type="radio" class="btn-check" name="api_type" id="type_mysql" value="api_dbsqlbase" checked onchange="toggleDbSettings()">
                        <label class="btn btn-outline-secondary" for="type_mysql"><?php echo _t('engine_mysql'); ?></label>
                    </div>
                </div>

                <div id="file_settings" class="card card-body bg-light mb-3 hidden">
                    <h5 class="card-title"><?php echo _t('file_settings'); ?></h5>
                    <p class="card-text text-muted"><?php echo _t('file_settings_desc'); ?></p>
                    <div class="text-end"><button type="button" class="btn btn-warning btn-sm" onclick="checkFileConnection()"><?php echo _t('btn_check_dir'); ?></button></div>
                </div>

                <div id="sqlite_settings" class="card card-body bg-light mb-3 hidden">
                    <h5 class="card-title"><?php echo _t('sqlite_settings'); ?></h5>
                    <div class="mb-3">
                        <label for="sqlite_path" class="form-label"><?php echo _t('sqlite_path'); ?></label>
                        <input type="text" class="form-control" id="sqlite_path" name="sqlite_path" value="blog.sqlite3">
                        <div class="form-text"><?php echo _t('sqlite_path_help'); ?></div>
                    </div>
                    <div class="text-end"><button type="button" class="btn btn-warning btn-sm" onclick="checkSqliteConnection()"><?php echo _t('btn_check_sqlite'); ?></button></div>
                </div>

                <div id="mysql_settings" class="card card-body bg-light mb-3">
                    <h5 class="card-title"><?php echo _t('mysql_settings'); ?></h5>
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label"><?php echo _t('db_host'); ?></label><input type="text" class="form-control" id="db_host" name="db_host" value="localhost"></div>
                        <div class="col-md-4"><label class="form-label"><?php echo _t('db_name'); ?></label><input type="text" class="form-control" id="db_name" name="db_name"></div>
                        <div class="col-md-6"><label class="form-label"><?php echo _t('db_user'); ?></label><input type="text" class="form-control" id="db_user" name="db_user"></div>
                        <div class="col-md-6"><label class="form-label"><?php echo _t('db_pass'); ?></label><input type="password" class="form-control" id="db_pass" name="db_pass"></div>
                        <div class="col-12 text-end"><button type="button" class="btn btn-warning btn-sm" onclick="checkDbConnection()"><?php echo _t('btn_test_conn'); ?></button></div>
                    </div>
                </div>
            </div>

            <!-- 3. Admin Account -->
            <div class="step-section">
                <h4 class="step-title"><?php echo _t('step_3'); ?></h4>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label"><?php echo _t('admin_user'); ?></label><input type="text" class="form-control" id="admin_user" name="admin_user" value="admin" required></div>
                    <div class="col-md-6"><label class="form-label"><?php echo _t('admin_pass'); ?></label>
                        <div class="input-group"><input type="password" class="form-control" id="admin_pass" name="admin_pass" placeholder="<?php echo _t('admin_pass_placeholder'); ?>" required><button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('admin_pass')">👁️</button></div>
                    </div>
                    <div class="col-12"><label class="form-label"><?php echo _t('session_secret'); ?></label>
                        <div class="input-group"><input type="text" class="form-control" id="session_secret" name="session_secret" required><button class="btn btn-outline-secondary" type="button" onclick="generateRandomString()"><?php echo _t('btn_gen_secret'); ?></button></div>
                        <div class="form-text"><?php echo _t('session_secret_help'); ?></div>
                    </div>
                </div>
            </div>

            <!-- 4. Frontend -->
            <div class="step-section">
                <h4 class="step-title"><?php echo _t('step_4'); ?></h4>
                <div class="mb-3">
                    <label class="form-label"><?php echo _t('theme_file'); ?></label>
                    <select class="form-select" name="theme_file">
                        <option value="blog" selected><?php echo _t('theme_light'); ?></option>
                        <option value="blog-dark"><?php echo _t('theme_dark'); ?></option>
                    </select>
                    <div class="form-text"><?php echo _t('theme_file_help'); ?></div>
                </div>
                <div class="mb-3"><label class="form-label"><?php echo _t('cse_id'); ?></label><input type="text" class="form-control" id="cse_id" name="cse_id">
                    <div class="form-text"><?php echo _t('cse_id_help'); ?></div>
                </div>
            </div>

            <div class="d-grid gap-2"><button type="submit" class="btn btn-primary btn-lg"><?php echo _t('btn_install'); ?></button></div>
        </form>
    </div>
</div>

<script src="admin/assets/js/bootstrap.bundle.min.js"></script>
<script src="admin/assets/js/sweetalert2.all.min.js"></script>
<script>
    const ajaxError = "<?php echo _t('ajax_error'); ?>";
    document.addEventListener('DOMContentLoaded', () => {
        const rootUrl = window.location.href.substring(0, window.location.href.lastIndexOf('/') + 1);
        document.getElementById('site_url').value = rootUrl;
        toggleDbSettings();

        // Handle form submission
        document.getElementById('installForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '🚀 Installing...';
            
            const fd = new FormData(this);
            fetch('?action=install', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ 
                            icon: 'success', 
                            title: 'Success', 
                            text: data.message,
                            confirmButtonText: 'Go to Admin Login'
                        }).then(() => {
                            window.location.href = 'admin/login.php';
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        btn.disabled = false; btn.innerHTML = orig;
                    }
                })
                .catch(() => {
                    Swal.fire({ icon: 'error', title: 'Error', text: ajaxError });
                    btn.disabled = false; btn.innerHTML = orig;
                });
        });
    });

    function toggleDbSettings() {
        const type = document.querySelector('input[name="api_type"]:checked').value;
        ['file_settings', 'sqlite_settings', 'mysql_settings'].forEach(id => document.getElementById(id).classList.add('hidden'));
        if (type === 'api_filebase') document.getElementById('file_settings').classList.remove('hidden');
        else if (type === 'api_sqlitebase') document.getElementById('sqlite_settings').classList.remove('hidden');
        else if (type === 'api_dbsqlbase') document.getElementById('mysql_settings').classList.remove('hidden');
    }

    function checkDbConnection() {
        const btn = document.querySelector('button[onclick="checkDbConnection()"]');
        const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '⏳...';
        const fd = new FormData();
        ['db_host', 'db_name', 'db_user', 'db_pass'].forEach(id => fd.append(id, document.getElementById(id).value));
        fetch('?action=check_db_connection', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
            Swal.fire({ icon: data.success ? (data.warning ? 'warning' : 'success') : 'error', title: data.success ? "<?php echo _t('conn_success'); ?>" : "<?php echo _t('conn_failed'); ?>", text: data.message });
        }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: ajaxError })).finally(() => { btn.disabled = false; btn.innerHTML = orig; });
    }

    function checkSqliteConnection() {
        const btn = document.querySelector('button[onclick="checkSqliteConnection()"]');
        const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '⏳...';
        const fd = new FormData(); fd.append('sqlite_path', document.getElementById('sqlite_path').value);
        fetch('?action=check_sqlite_connection', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
            Swal.fire({ icon: data.success ? 'success' : 'error', title: 'SQLite', text: data.message });
        }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: ajaxError })).finally(() => { btn.disabled = false; btn.innerHTML = orig; });
    }

    function checkFileConnection() {
        const btn = document.querySelector('button[onclick="checkFileConnection()"]');
        const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '⏳...';
        fetch('?action=check_file_connection', { method: 'POST' }).then(r => r.json()).then(data => {
            Swal.fire({ icon: data.success ? 'success' : 'error', title: 'File Base', html: data.message });
        }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: ajaxError })).finally(() => { btn.disabled = false; btn.innerHTML = orig; });
    }

    function checkSystemPermissions() {
        const area = document.getElementById('perm_status_area');
        area.innerHTML = '⏳...';
        fetch('?action=check_system_permissions', { method: 'POST' }).then(r => r.json()).then(data => {
            if (data.success) {
                area.innerHTML = `<div class="text-success fw-bold">✅ ${data.message}</div>`;
                // If it's WSL NTFS, we don't need a fix button even if it was successful (it's a bypass)
            } else {
                area.innerHTML = `<div class="alert alert-warning mb-2">⚠️ ${data.message}</div><button type="button" class="btn btn-warning btn-sm" onclick="fixSystemPermissions()"><?php echo _t('btn_fix_perms'); ?></button>`;
            }
        }).catch(() => area.innerHTML = `<span class="text-danger">${ajaxError}</span>`);
    }

    function fixSystemPermissions() {
        const btn = document.querySelector('button[onclick="fixSystemPermissions()"]');
        const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '⏳...';
        fetch('?action=fix_system_permissions', { method: 'POST' }).then(r => r.json()).then(data => {
            Swal.fire({ icon: data.success ? 'success' : 'error', title: 'Fix', text: data.message });
            if (data.success) checkSystemPermissions();
        }).catch(() => Swal.fire({ icon: 'error', title: 'Error', text: ajaxError })).finally(() => { btn.disabled = false; btn.innerHTML = orig; });
    }

    function generateRandomString() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
        let res = ''; for (let i = 0; i < 32; i++) res += chars.charAt(Math.floor(Math.random() * chars.length));
        document.getElementById('session_secret').value = res;
    }

    function togglePasswordVisibility(id) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>
</body>
</html>