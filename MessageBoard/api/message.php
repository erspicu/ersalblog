<?php
/**
 * MessageBoard PHP API (SQLite) - 通用平台化版本 (支援中文路徑)
 */
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

function send_json($data) { echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

// 安全過濾檔名與目錄名 (保留中文)
function sanitize_path_node($name) {
    // 移除危險字元，但保留中文、英數、底線、橫線
    return preg_replace('/[\/\\\:\*\?"<>|]/', '_', $name);
}

try {
    require_once __DIR__ . '/../config/config.php';

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') {
        $site_id = $_GET['site_id'] ?? 'Default_Site';
        $page_id = $_GET['page_id'] ?? 'index';
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $site_id = $input['site_id'] ?? 'Default_Site';
        $page_id = $input['page_id'] ?? 'index';
    }

    // 2. 根據 Site ID 動態決定資料庫路徑
    $site_dir_name = sanitize_path_node($site_id);
    $page_file_name = sanitize_path_node($page_id);

    $baseDataDir = __DIR__ . '/../data/' . $site_dir_name;
    if (!is_dir($baseDataDir)) mkdir($baseDataDir, 0777, true);
    
    $dbPath = $baseDataDir . '/' . $page_file_name . '.sqlite3';

    $isNewDb = !file_exists($dbPath);
    $pdo = new PDO("sqlite:" . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    if ($isNewDb) {
        $pdo->exec("CREATE TABLE guestbook_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            parent_id INTEGER DEFAULT 0,
            name TEXT NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT (datetime('now','localtime')),
            status INTEGER DEFAULT 1
        );");
        $pdo->exec("CREATE INDEX idx_parent ON guestbook_messages(parent_id);");
    }

    if ($method === 'GET') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $offset = ($page - 1) * $per_page;

        $stmtParents = $pdo->prepare("SELECT id FROM guestbook_messages WHERE parent_id = 0 AND status = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmtParents->execute([$per_page, $offset]);
        $parentIds = $stmtParents->fetchAll(PDO::FETCH_COLUMN);

        $stmtCount = $pdo->query("SELECT COUNT(*) FROM guestbook_messages WHERE parent_id = 0 AND status = 1");
        $totalParents = $stmtCount->fetchColumn();

        $allMessages = [];
        if (count($parentIds) > 0) {
            $stmtAll = $pdo->query("SELECT * FROM guestbook_messages WHERE status = 1 ORDER BY created_at ASC");
            $allMessages = $stmtAll->fetchAll();
        }

        send_json([
            'messages' => $allMessages,
            'pagination' => [
                'total_parents' => (int)$totalParents,
                'current_page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($totalParents / $per_page),
                'active_parents' => $parentIds
            ]
        ]);
        
    } elseif ($method === 'POST') {
        if (empty($input['name']) || empty($input['content'])) send_json(['success' => false, 'message' => 'Missing fields']);
        $stmt = $pdo->prepare("INSERT INTO guestbook_messages (parent_id, name, content) VALUES (?, ?, ?)");
        $stmt->execute([ $input['parent_id'] ?? 0, $input['name'], $input['content'] ]);
        send_json(['success' => true]);
    }

} catch (Exception $e) {
    send_json(['error' => true, 'message' => $e->getMessage()]);
}
