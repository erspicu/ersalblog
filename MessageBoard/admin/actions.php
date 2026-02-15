<?php
require_once 'auth.php';
mb_require_login();

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';
$site = isset($_GET['site']) ? $_GET['site'] : '';
$page = isset($_GET['page']) ? $_GET['page'] : '';
$mode = $_SESSION['mb_admin_mode'];

function get_gas_url() {
    $js_file = __DIR__ . '/../config/config.js';
    if (file_exists($js_file)) {
        $content = file_get_contents($js_file);
        if (preg_match("/gas_url:\s*'([^']+)'/", $content, $m)) return $m[1];
    }
    return '';
}

if ($action === 'delete' && !empty($id) && !empty($site) && !empty($page)) {
    if ($mode === 'local') {
        $dbPath = __DIR__ . '/../data/' . $site . '/' . $page . '.sqlite3';
        if (file_exists($dbPath)) {
            try {
                $pdo = new PDO("sqlite:" . $dbPath);
                $pdo->exec("DELETE FROM guestbook_messages WHERE id = " . $pdo->quote($id));
                $pdo->exec("DELETE FROM guestbook_messages WHERE parent_id = " . $pdo->quote($id));
                echo json_encode(['success' => true]);
            } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        } else { echo json_encode(['success' => false, 'message' => 'Database not found']); }
    } else {
        // GAS 模式刪除
        $gasUrl = get_gas_url();
        if ($gasUrl) {
            $options = [
                'http' => [
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode([
                        'action' => 'delete',
                        'site_id' => $site,
                        'page_id' => $page,
                        'id' => $id
                    ]),
                ],
            ];
            $context  = stream_context_create($options);
            $result = @file_get_contents($gasUrl, false, $context);
            if ($result) {
                echo $result; // 直接回傳 GAS 的回應
            } else {
                echo json_encode(['success' => false, 'message' => '無法連接到 GAS 服務']);
            }
        }
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Request']);
