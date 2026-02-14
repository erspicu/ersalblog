<?php
// album/admin/sidebar_inc.php
$current_page = basename($_SERVER['PHP_SELF']);

function is_active($page, $current) {
    return ($page === $current) ? 'active' : '';
}
?>
<style>
    .sidebar { 
        min-height: 100vh; 
        background-color: #212529; /* Darker than blog */
        color: white; 
        position: fixed; 
        top: 0;
        left: 0;
        width: 250px;
        z-index: 1000;
        overflow-y: auto; 
    }
    .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 15px; display: block; }
    .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
    
    .main-content { 
        margin-left: 250px; 
        width: calc(100% - 250px);
        min-height: 100vh;
        padding: 20px; 
    }
</style>
<div class="sidebar d-flex flex-column flex-shrink-0 p-3">
    <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <span class="fs-4 fw-bold">Album Admin</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="index.php" class="<?php echo is_active('index.php', $current_page); ?>">
                <?php echo __('dashboard'); ?> (Dashboard)
            </a>
        </li>
        <li>
            <a href="albums.php" class="<?php echo is_active('albums.php', $current_page); ?>">
                <?php echo __('manage_albums'); ?> (Albums)
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?php echo is_active('settings.php', $current_page); ?>">
                <?php echo __('settings'); ?> (Settings)
            </a>
        </li>
        <li>
            <a href="maintenance.php" class="<?php echo is_active('maintenance.php', $current_page); ?> text-warning">
                <i class="bi bi-tools"></i> <?php echo __('rebuild_all'); ?> (Maintenance)
            </a>
        </li>
        <li>
            <a href="health_check.php" class="<?php echo is_active('health_check.php', $current_page); ?>">
                <i class="bi bi-heart-pulse"></i> 環境檢查 (Health)
            </a>
        </li>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="../album.html" target="_blank"><?php echo __('preview_site'); ?></a>
        <a href="logout.php" class="text-danger mt-2"><?php echo __('logout_btn'); ?></a>
    </div>
</div>
