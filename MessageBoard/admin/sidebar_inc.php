<?php
$current_page = basename($_SERVER['PHP_SELF']);
$sidebarMode = $_SESSION['mb_admin_mode'] ?? 'local';
$sidebarBadgeClass = ($sidebarMode === 'gas') ? 'bg-info text-dark' : 'bg-success';

function is_active($page, $current) {
    return ($page === $current) ? 'active' : '';
}
?>
<style>
    .sidebar { 
        min-height: 100vh; background-color: #343a40; color: white; position: fixed; top: 0; left: 0; width: 250px; z-index: 1000; padding: 15px;
    }
    .sidebar a { color: #cfd2d6; text-decoration: none; padding: 10px 15px; display: block; border-radius: 5px; margin-bottom: 5px; }
    .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
    .main-content { margin-left: 250px; width: calc(100% - 250px); min-height: 100vh; padding: 30px; background-color: #f8f9fa; }
</style>
<div class="sidebar d-flex flex-column">
    <a href="index.php" class="d-flex align-items-center mb-3 text-white text-decoration-none">
        <span class="fs-4 fw-bold">MB Admin</span>
    </a>
    <hr>
    <div class="text-center mb-3">
        <span class="badge <?php echo $sidebarBadgeClass; ?>">
            <?php echo __mb('label_mode'); ?>: <?php echo strtoupper($sidebarMode); ?>
        </span>
    </div>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="index.php" class="<?php echo is_active('index.php', $current_page); ?>">
                <i class="bi bi-chat-left-dots me-2"></i><?php echo __mb('menu_management'); ?>
            </a>
        </li>
        <li>
            <a href="setup.php" class="<?php echo is_active('setup.php', $current_page); ?>">
                <i class="bi bi-gear me-2"></i><?php echo __mb('menu_settings'); ?>
            </a>
        </li>
    </ul>
    <hr>
    <div>
        <a href="../../index.html" target="_blank"><i class="bi bi-house me-2"></i><?php echo __mb('btn_back_to_blog'); ?></a>
        <a href="logout.php" class="text-danger mt-2"><i class="bi bi-box-arrow-right me-2"></i><?php echo __mb('nav_logout'); ?></a>
    </div>
</div>
