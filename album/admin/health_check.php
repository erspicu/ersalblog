<?php
require_once 'auth.php';
requireAlbumLogin();

$current_page = 'health_check.php';

function check_extension($name) {
    return extension_loaded($name) ? '<span class="text-success">✅ OK</span>' : '<span class="text-danger">❌ Missing</span>';
}

function check_writable($path) {
    $realPath = realpath(__DIR__ . '/../../' . $path);
    if (!$realPath) return '<span class="text-danger">❌ Path Not Found</span>';
    return is_writable($realPath) ? '<span class="text-success">✅ Writable</span>' : '<span class="text-danger">❌ Permission Denied</span>';
}

$is_wsl = false;
if (strpos(strtolower(php_uname()), 'microsoft') !== false || strpos(strtolower(php_uname()), 'wsl') !== false) {
    $is_wsl = true;
}
?>
<!DOCTYPE html>
<html lang="<?php echo getWebLang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo __('health_check'); ?> - Album Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>
    <div class="main-content flex-grow-1 p-4">
        <h2><i class="bi bi-heart-pulse"></i> <?php echo __('health_check'); ?></h2>
        <p class="text-muted"><?php echo __('health_check_desc'); ?></p>

        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white fw-bold"><?php echo __('sys_info'); ?></div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <tr><th width="40%"><?php echo __('os_platform'); ?></th><td><?php echo PHP_OS; ?></td></tr>
                            <tr><th><?php echo __('php_version'); ?></th><td><?php echo PHP_VERSION; ?></td></tr>
                            <tr><th><?php echo __('run_env'); ?></th><td><?php echo $is_wsl ? '<span class="badge bg-info">WSL2 / Linux</span>' : '<span class="badge bg-primary">Native / Standard</span>'; ?></td></tr>
                            <tr><th>SAPI</th><td><?php echo PHP_SAPI; ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white fw-bold"><?php echo __('core_ext'); ?></div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <tr><th width="40%">Imagick</th><td><?php echo check_extension('imagick'); ?></td></tr>
                            <tr><th>EXIF</th><td><?php echo check_extension('exif'); ?></td></tr>
                            <tr><th>GD Library</th><td><?php echo check_extension('gd'); ?></td></tr>
                            <tr><th>MBString</th><td><?php echo check_extension('mbstring'); ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark fw-bold"><?php echo __('perm_check'); ?></div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <tr><th width="20%">Collection/</th><td><?php echo check_writable('album/Collection'); ?></td></tr>
                            <tr><th>api/json/</th><td><?php echo check_writable('album/api/json'); ?></td></tr>
                            <tr><th>album.html</th><td><?php echo check_writable('album/album.html'); ?></td></tr>
                            <tr><th>generator.log</th><td><?php echo check_writable('album/api/json/generator.log'); ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white fw-bold"><?php echo __('imagick_test'); ?></div>
                    <div class="card-body">
                        <?php
                        if (extension_loaded('imagick')) {
                            try {
                                $im = new Imagick();
                                $allFormats = $im->queryFormats();
                                $targets = ['JPG', 'JPEG', 'PNG', 'WEBP', 'GIF'];
                                $supported = [];
                                foreach ($targets as $t) {
                                    if (in_array($t, $allFormats)) $supported[] = $t;
                                }
                                
                                echo '<div class="alert alert-success d-flex align-items-center mb-0">';
                                echo '<i class="bi bi-check-circle-fill me-3 fs-3"></i>';
                                echo '<div>';
                                echo '<strong>' . __('imagick_ok') . '</strong><br>';
                                echo '<small>' . __('imagick_supported') . ' ' . implode(', ', $supported) . '</small>';
                                echo '</div>';
                                echo '</div>';
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger mb-0"><strong>' . __('imagick_fail') . '</strong> ' . $e->getMessage() . '</div>';
                            }
                        } else {
                            echo '<div class="alert alert-secondary mb-0">' . __('imagick_not_found') . '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
