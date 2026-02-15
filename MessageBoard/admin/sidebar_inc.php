<?php
$current_page = basename($_SERVER['PHP_SELF']);
$sidebarMode = isset($_SESSION['mb_admin_mode']) ? $_SESSION['mb_admin_mode'] : 'local';

function is_active($page, $current) {
    return $page === $current ? 'nav-link active bg-dark' : 'nav-link text-dark';
}
?>
<div class="sidebar d-flex flex-column flex-shrink-0 p-3 bg-white shadow-sm" style="width: 250px; min-height: 100vh;">
    <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-dark text-decoration-none">
        <span class="fs-4 fw-bold">MB Admin</span>
    </a>
    <div class="small text-muted mb-3 px-2"><?php echo strtoupper($sidebarMode); ?> Mode</div>
    <hr>
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
        <a href="../../index.html" target="_blank" class="nav-link text-dark py-1"><i class="bi bi-house me-2"></i><?php echo __mb('btn_back_to_blog'); ?></a>
        <a href="logout.php" class="nav-link text-danger py-1 mt-2"><i class="bi bi-box-arrow-right me-2"></i><?php echo __mb('nav_logout'); ?></a>
    </div>
</div>
<style>
    .main-content { flex-grow: 1; padding: 30px; }
    .sidebar .nav-link:hover { background-color: #f8f9fa; }
</style>
