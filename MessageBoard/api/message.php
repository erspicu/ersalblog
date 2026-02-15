<?php
/**
 * MessageBoard PHP API (SQLite) - PHP 5.x Compatible
 */
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

function send_json($data) { echo json_encode($data); exit; }

function sanitize_path_node($name) {
    return preg_replace('/[\/\\\:\*\?"<>|]/', '_', $name);
}

try {
    require_once __DIR__ . '/../config/config.php';

    $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    
    if ($method === 'GET') {
        $site_id = isset($_GET['site_id']) ? $_GET['site_id'] : 'Default_Site';
        $page_id = isset($_GET['page_id']) ? $_GET['page_id'] : 'index';
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $site_id = isset($input['site_id']) ? $input['site_id'] : 'Default_Site';
        $page_id = isset($input['page_id']) ? $input['page_id'] : 'index';
        $page_title = isset($input['page_title']) ? $input['page_title'] : '';
    }

    $site_dir_name = sanitize_path_node($site_id);
    $page_file_name = sanitize_path_node($page_id);
    $baseDataDir = __DIR__ . '/../data/' . $site_dir_name;
    if (!is_dir($baseDataDir)) mkdir($baseDataDir, 0777, true);
    $dbPath = $baseDataDir . '/' . $page_file_name . '.sqlite3';

    $pdo = new PDO("sqlite:" . $dbPath, null, null, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));

    $pdo->exec("CREATE TABLE IF NOT EXISTS guestbook_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        parent_id INTEGER DEFAULT 0,
        name TEXT NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT (datetime('now','localtime')),
        status INTEGER DEFAULT 1
    );");
    $pdo->exec("CREATE TABLE IF NOT EXISTS page_meta (
        key TEXT PRIMARY KEY,
        value TEXT
    );");

    if ($method === 'POST') {
        if (empty($input['name']) || empty($input['content'])) send_json(array('success' => false, 'message' => 'Missing fields'));
        
        if (!empty($page_title)) {
            $stmtMeta = $pdo->prepare("INSERT OR REPLACE INTO page_meta (key, value) VALUES ('title', ?)");
            $stmtMeta->execute(array($page_title));
        }

        $stmt = $pdo->prepare("INSERT INTO guestbook_messages (parent_id, name, content) VALUES (?, ?, ?)");
        $stmt->execute(array( (isset($input['parent_id']) ? $input['parent_id'] : 0), $input['name'], $input['content'] ));
        send_json(array('success' => true));
    } else {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $offset = ($page - 1) * $per_page;

        $stmtParents = $pdo->prepare("SELECT id FROM guestbook_messages WHERE parent_id = 0 AND status = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmtParents->execute(array($per_page, $offset));
        $parentIds = $stmtParents->fetchAll(PDO::FETCH_COLUMN);

        $stmtCount = $pdo->query("SELECT COUNT(*) FROM guestbook_messages WHERE parent_id = 0 AND status = 1");
        $totalParents = $stmtCount->fetchColumn();

        $allMessages = array();
        if (count($parentIds) > 0) {
            $stmtAll = $pdo->query("SELECT * FROM guestbook_messages WHERE status = 1 ORDER BY created_at ASC");
            $allMessages = $stmtAll->fetchAll();
        }

        send_json(array(
            'messages' => $allMessages,
            'pagination' => array(
                'total_parents' => (int)$totalParents, 
                'current_page' => $page, 
                'per_page' => $per_page, 
                'total_pages' => ceil($totalParents / $per_page), 
                'active_parents' => $parentIds
            )
        ));
    }
} catch (Exception $e) { send_json(array('error' => true, 'message' => $e->getMessage())); }
