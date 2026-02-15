<?php
/**
 * MessageBoard Admin Index - PHP 5.x Compatible
 */
require_once 'auth.php';
mb_require_login();

$mode = $_SESSION['mb_admin_mode'];
$status = mb_check_status($mode);
if (!$status['ok']) { header("Location: setup.php"); exit; }

$selected_site = isset($_GET['site']) ? $_GET['site'] : '';
$selected_page = isset($_GET['page']) ? $_GET['page'] : '';
$current_p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$per_page = 20;

$sites = array();
$messages = array();
$pagination = array('total_pages' => 0, 'current_page' => $current_p);
$error = null;

function get_gas_url() {
    $js_file = __DIR__ . '/../config/config.js';
    if (file_exists($js_file)) {
        $content = file_get_contents($js_file);
        if (preg_match("/gas_url:\s*'([^']+)'/", $content, $m)) return $m[1];
    }
    return '';
}

if ($mode === 'local') {
    $dataDir = __DIR__ . '/../data';
    if (is_dir($dataDir)) {
        $siteDirs = array_filter(glob($dataDir . '/*'), 'is_dir');
        foreach ($siteDirs as $dir) {
            $siteName = basename($dir);
            $sites[$siteName] = array();
            foreach (glob($dir . '/*.sqlite3') as $dbFile) {
                $pId = basename($dbFile, '.sqlite3'); $pTitle = $pId;
                try {
                    $tmpPdo = new PDO("sqlite:" . $dbFile);
                    $meta = $tmpPdo->query("SELECT value FROM page_meta WHERE key='title'")->fetch();
                    if ($meta) $pTitle = $meta['value'];
                } catch (Exception $e) {}
                $sites[$siteName][] = array('id' => $pId, 'title' => $pTitle);
            }
        }
    }
    if ($selected_site && $selected_page) {
        try {
            $dbPath = $dataDir . '/' . $selected_site . '/' . $selected_page . '.sqlite3';
            if (file_exists($dbPath)) {
                $pdo = new PDO("sqlite:" . $dbPath);
                $totalParents = $pdo->query("SELECT COUNT(*) FROM guestbook_messages WHERE parent_id = 0")->fetchColumn();
                $totalPages = ceil($totalParents / $per_page);
                $offset = ($current_p - 1) * $per_page;
                $stmtIds = $pdo->prepare("SELECT id FROM guestbook_messages WHERE parent_id = 0 ORDER BY created_at DESC LIMIT ? OFFSET ?");
                $stmtIds->execute(array($per_page, $offset));
                $activeIds = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
                $allMsgs = $pdo->query("SELECT * FROM guestbook_messages ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($allMsgs as $m) {
                    if ($m['parent_id'] == 0) { if (in_array($m['id'], $activeIds)) $messages[] = $m; }
                    else { if (in_array($m['parent_id'], $activeIds)) $messages[] = $m; }
                }
                $pagination = array('total_pages' => $totalPages, 'current_page' => $current_p);
            }
        } catch (Exception $e) { $error = $e->getMessage(); }
    }
} else {
    $gasUrl = get_gas_url();
    if ($gasUrl) {
        $sitesJson = @file_get_contents($gasUrl . "?action=list_sites");
        $sitesData = json_decode($sitesJson, true);
        $snList = isset($sitesData['sites']) ? $sitesData['sites'] : array();
        foreach ($snList as $sn) { $sites[$sn] = array(); }
        if ($selected_site) {
            $pagesData = json_decode(@file_get_contents($gasUrl . "?action=list_pages&site_id=" . urlencode($selected_site)), true);
            $sites[$selected_site] = isset($pagesData['pages']) ? $pagesData['pages'] : array();
        }
        if ($selected_site && $selected_page) {
            $msgsData = json_decode(@file_get_contents($gasUrl . "?action=list&site_id=" . urlencode($selected_site) . "&page_id=" . urlencode($selected_page) . "&page=" . $current_p . "&per_page=" . $per_page), true);
            $rawMsgs = isset($msgsData['messages']) ? $msgsData['messages'] : array();
            $activeIds = isset($msgsData['pagination']['active_parents']) ? array_map('strval', $msgsData['pagination']['active_parents']) : array();
            foreach($rawMsgs as $m) {
                $pid = strval($m['parent_id']);
                $mid = strval($m['id']);
                if ($pid === "0") { if (in_array($mid, $activeIds)) $messages[] = $m; }
                else { if (in_array($pid, $activeIds)) $messages[] = $m; }
            }
            $pagination = array('total_pages' => (isset($msgsData['pagination']['total_pages']) ? $msgsData['pagination']['total_pages'] : 0), 'current_page' => $current_p);
        }
    } else { $error = "GAS URL not set."; }
}

function get_page_link($p) { $params = $_GET; $params['p'] = $p; return '?' . http_build_query($params); }
?>
<!DOCTYPE html>
<html lang="<?php echo mb_get_lang(); ?>">
<head>
    <meta charset="UTF-8"><title><?php echo __mb('admin_title'); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <div class="d-flex">
        <?php include 'sidebar_inc.php'; ?>
        <div class="main-content">
            <h2 class="mb-4"><?php echo __mb('menu_management'); ?> (<?php echo strtoupper($mode); ?>)</h2>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-center">
                        <div class="col-auto"><label class="fw-bold"><?php echo __mb('label_select_site_page'); ?></label></div>
                        <div class="col-auto">
                            <select name="site" class="form-select" onchange="location.href='?site='+this.value">
                                <option value=""><?php echo __mb('ph_select_site'); ?></option>
                                <?php foreach(array_keys($sites) as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo ($selected_site === $s ? 'selected' : ''); ?>><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if($selected_site): ?>
                        <div class="col-auto">
                            <select name="page" class="form-select" onchange="this.form.submit()">
                                <option value=""><?php echo __mb('ph_select_page'); ?></option>
                                <?php foreach($sites[$selected_site] as $p): ?>
                                    <?php $pId = is_array($p) ? $p['id'] : $p; $pTitle = is_array($p) ? $p['title'] : $p; ?>
                                    <option value="<?php echo $pId; ?>" <?php echo ($selected_page === $pId ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($pTitle); ?> (<?php echo $pId; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <?php if($selected_site && $selected_page): ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th class="ps-4"><?php echo __mb('col_id'); ?></th><th><?php echo __mb('col_author'); ?></th><th><?php echo __mb('col_content'); ?></th><th><?php echo __mb('col_date'); ?></th><th class="pe-4 text-end"><?php echo __mb('col_action'); ?></th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($messages)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted"><?php echo __mb('no_data'); ?></td></tr>
                            <?php else: ?>
                                <?php foreach($messages as $m): ?>
                                    <tr class="<?php echo ($m['parent_id'] != 0 && $m['parent_id'] != '0' ? 'table-light' : ''); ?>">
                                        <td class="ps-4 small text-muted">#<?php echo $m['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($m['name']); ?></strong></td>
                                        <td><?php echo nl2br(htmlspecialchars($m['content'])); ?></td>
                                        <td><small><?php echo $m['created_at']; ?></small></td>
                                        <td class="pe-4 text-end"><button class="btn btn-sm btn-outline-danger" onclick="deleteMsg('<?php echo $m['id']; ?>')"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if($pagination['total_pages'] > 1): ?>
            <nav aria-label="Page navigation" class="mt-4"><ul class="pagination justify-content-center">
                <?php for($i=1; $i<=$pagination['total_pages']; $i++): ?>
                    <li class="page-item <?php echo ($i == $pagination['current_page'] ? 'active' : ''); ?>"><a class="page-link" href="<?php echo get_page_link($i); ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
            </ul></nav>
            <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info border-0 shadow-sm text-center py-5"><i class="bi bi-info-circle fs-1 d-block mb-3"></i><?php echo __mb('msg_not_selected'); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function deleteMsg(id) {
            if(confirm('<?php echo __mb('confirm_delete'); ?>')) {
                const site = '<?php echo urlencode($selected_site); ?>';
                const page = '<?php echo urlencode($selected_page); ?>';
                fetch(`actions.php?action=delete&site=${site}&page=${page}&id=${id}`)
                    .then(res => res.json()).then(data => { if(data.success) location.reload(); else alert(data.message); });
            }
        }
    </script>
</body>
</html>
