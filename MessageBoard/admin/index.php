<?php
require_once 'system_helper.php';
mb_require_login();

$mode = $_SESSION['mb_admin_mode'];
$status = mb_check_status($mode);
if (!$status['ok']) { header("Location: setup.php"); exit; }

$dataDir = __DIR__ . '/../data';
$selected_site = $_GET['site'] ?? '';
$selected_page = $_GET['page'] ?? '';

$sites = [];
if (is_dir($dataDir)) {
    $siteDirs = array_filter(glob($dataDir . '/*'), 'is_dir');
    foreach ($siteDirs as $dir) {
        $siteName = basename($dir);
        $sites[$siteName] = array_map(function($f) {
            return basename($f, '.sqlite3');
        }, glob($dir . '/*.sqlite3'));
    }
}

$messages = [];
$error = null;

if ($mode === 'local' && $selected_site && $selected_page) {
    try {
        $dbPath = $dataDir . '/' . $selected_site . '/' . $selected_page . '.sqlite3';
        if (file_exists($dbPath)) {
            $pdo = new PDO("sqlite:" . $dbPath);
            $stmt = $pdo->query("SELECT * FROM guestbook_messages ORDER BY created_at DESC");
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) { $error = $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html lang="<?php echo mb_get_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo __mb('admin_title'); ?></title>
    <link href="../../admin/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <div class="d-flex">
        <?php include 'sidebar_inc.php'; ?>
        <div class="main-content">
            <h2 class="mb-4">留言管理 (SQLite)</h2>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-center">
                                <div class="col-auto">
                                    <label class="fw-bold">選擇站點與頁面：</label>
                                </div>
                                <div class="col-auto">
                                    <select name="site" class="form-select" onchange="this.form.submit()">
                                        <option value="">-- 請選擇站點 --</option>
                                        <?php foreach(array_keys($sites) as $s): ?>
                                            <option value="<?php echo $s; ?>" <?php echo ($selected_site === $s ? 'selected' : ''); ?>><?php echo $s; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php if($selected_site): ?>
                                <div class="col-auto">
                                    <select name="page" class="form-select" onchange="this.form.submit()">
                                        <option value="">-- 請選擇頁面 --</option>
                                        <?php foreach($sites[$selected_site] as $p): ?>
                                            <option value="<?php echo $p; ?>" <?php echo ($selected_page === $p ? 'selected' : ''); ?>><?php echo $p; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if($selected_site && $selected_page): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>作者</th>
                                <th>內容</th>
                                <th>時間</th>
                                <th class="pe-4 text-end">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($messages)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">此頁面尚無留言</td></tr>
                            <?php else: ?>
                                <?php foreach($messages as $m): ?>
                                    <tr class="<?php echo ($m['parent_id'] > 0 ? 'table-light' : ''); ?>">
                                        <td class="ps-4 small text-muted">#<?php echo $m['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($m['name']); ?></strong></td>
                                        <td><?php echo nl2br(htmlspecialchars($m['content'])); ?></td>
                                        <td><small><?php echo $m['created_at']; ?></small></td>
                                        <td class="pe-4 text-end">
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteMsg('<?php echo $m['id']; ?>')"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-info border-0 shadow-sm text-center py-5">
                    <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
                    請先從上方選單選取要管理的站點與文章頁面。
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function deleteMsg(id) {
            if(confirm('確定要刪除嗎？')) {
                const site = '<?php echo urlencode($selected_site); ?>';
                const page = '<?php echo urlencode($selected_page); ?>';
                fetch(`actions.php?action=delete&site=${site}&page=${page}&id=${id}`)
                    .then(res => res.json())
                    .then(data => { if(data.success) location.reload(); else alert(data.message); });
            }
        }
    </script>
</body>
</html>
