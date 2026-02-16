<?php
/**
 * MessageBoard Admin Sidebar - 風格統一樣式版
 */
$current_page = basename($_SERVER['PHP_SELF']);
$sidebarMode = isset($_SESSION['mb_admin_mode']) ? $_SESSION['mb_admin_mode'] : 'local';

function is_active($page, $current) {
    return ($page === $current) ? 'active' : '';
}

// 與部落格主系統一致的 Badge 顏色邏輯
$sidebarBadgeClass = 'bg-info text-dark';
$sidebarModeText = __mb('mode_sqlite_short');

if ($sidebarMode === 'gas') {
    $sidebarBadgeClass = 'bg-primary';
    $sidebarModeText = __mb('mode_gas_short');
}
?>
<style>
    .sidebar { 
        min-height: 100vh; 
        background-color: #343a40; 
        color: white; 
        position: fixed; 
        top: 0;
        left: 0;
        width: 250px;
        z-index: 1000;
        overflow-y: auto;
    }
    .sidebar a { color: #cfd2d6; text-decoration: none; padding: 10px 15px; display: block; }
    .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
    
    .main-content { 
        margin-left: 250px; 
        width: calc(100% - 250px);
        min-height: 100vh;
        padding: 40px; 
        background-color: #f5f5f5;
    }
</style>
<div class="sidebar d-flex flex-column flex-shrink-0 p-3">
    <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <span class="fs-4 fw-bold">MessageBoard</span>
    </a>
    <hr>
    <div class="text-center mb-3">
        <span class="badge <?php echo $sidebarBadgeClass; ?>">
            <?php echo __mb('mode_label'); ?>: <?php echo $sidebarModeText; ?>
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
    <div class="dropdown">
        <a href="../../blog.html" target="_blank"><i class="bi bi-house me-2"></i><?php echo __mb('btn_back_to_blog'); ?></a>
        <a href="logout.php" class="text-danger mt-2"><i class="bi bi-box-arrow-right me-2"></i><?php echo __mb('nav_logout'); ?></a>
    </div>
</div>
