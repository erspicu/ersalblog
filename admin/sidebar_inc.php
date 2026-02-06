<?php
// Sidebar Component
$current_page = basename($_SERVER['PHP_SELF']);
$dataManagerForSidebar = new DataManager();
$sidebarSource = $dataManagerForSidebar->getSource();

$sidebarBadgeClass = 'bg-warning text-dark';
$sidebarModeText = __('mode_file_short');
if ($sidebarSource === 'db') {
    $sidebarBadgeClass = 'bg-success';
    $sidebarModeText = __('mode_db_short');
} elseif ($sidebarSource === 'sqlite') {
    $sidebarBadgeClass = 'bg-info text-dark';
    $sidebarModeText = 'SQLite';
}

function is_active($page, $current) {
    return ($page === $current) ? 'active' : '';
}
?>
<style>
    .sidebar { 
        min-height: 100vh; 
        background-color: #343a40; 
        color: white; 
        position: fixed; /* 固定側邊欄 */
        top: 0;
        left: 0;
        width: 250px;
        z-index: 1000;
        overflow-y: auto; /* 若選單太長可自行捲動 */
    }
    .sidebar a { color: #cfd2d6; text-decoration: none; padding: 10px 15px; display: block; }
    .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
    
    /* 修正內容區位移 */
    .main-content { 
        margin-left: 250px; 
        width: calc(100% - 250px);
        min-height: 100vh;
        padding: 20px; 
    }
</style>
<div class="sidebar d-flex flex-column flex-shrink-0 p-3">
    <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <span class="fs-4"><?php echo __('nav_brand'); ?></span>
    </a>
    <hr>
    <div class="text-center mb-3">
        <span class="badge <?php echo $sidebarBadgeClass; ?>">
            <?php echo __('mode_label'); ?>: <?php echo $sidebarModeText; ?>
        </span>
    </div>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="index.php" class="<?php echo is_active('index.php', $current_page); ?>">
                <?php echo __('nav_dashboard'); ?>
            </a>
        </li>
        <li>
            <a href="posts.php" class="<?php echo is_active('posts.php', $current_page); ?>">
                <?php echo __('nav_posts'); ?>
            </a>
        </li>
        <li>
            <a href="categories.php" class="<?php echo is_active('categories.php', $current_page); ?>">
                <?php echo __('nav_categories'); ?>
            </a>
        </li>
        <li>
            <a href="build.php" class="<?php echo is_active('build.php', $current_page); ?>">
                <?php echo __('nav_build'); ?>
            </a>
        </li>
        <li>
            <a href="tool_migrate.php" class="<?php echo is_active('tool_migrate.php', $current_page); ?>">
                <?php echo __('nav_import'); ?>
            </a>
        </li>
        <li>
            <a href="tool_backup.php" class="<?php echo is_active('tool_backup.php', $current_page); ?>">
                <?php echo __('nav_backup'); ?>
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?php echo is_active('settings.php', $current_page); ?>">
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
