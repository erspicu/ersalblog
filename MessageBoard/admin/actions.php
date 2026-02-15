<?php
require_once 'system_helper.php';
mb_require_login();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$site = $_GET['site'] ?? '';
$page = $_GET['page'] ?? '';

if ($action === 'delete' && !empty($id) && !empty($site) && !empty($page)) {
    $dbPath = __DIR__ . '/../data/' . $site . '/' . $page . '.sqlite3';
    
    if (file_exists($dbPath)) {
        try {
            $pdo = new PDO("sqlite:" . $dbPath);
            $pdo->exec("DELETE FROM guestbook_messages WHERE id = " . (int)$id);
            $pdo->exec("DELETE FROM guestbook_messages WHERE parent_id = " . (int)$id);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database not found']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Request']);
