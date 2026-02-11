<?php
header('Content-Type: application/json');
$themesDir = __DIR__ . '/../static/themes';
$themes = [];

if (is_dir($themesDir)) {
    $scan = scandir($themesDir);
    foreach ($scan as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $fullPath = $themesDir . '/' . $entry;
        
        if (is_dir($fullPath) && strpos($entry, 'album') === 0) {
            $name = $entry;
            $desc = "相簿預設主題風格";
            $readme = $fullPath . '/readme.txt';
            
            if (file_exists($readme)) {
                $lines = file($readme, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (stripos($line, 'Name:') === 0) $name = trim(substr($line, 5));
                    if (stripos($line, 'Description:') === 0) $desc = trim(substr($line, 12));
                }
            }
            $themes[] = [
                'id' => $entry,
                'name' => $name,
                'desc' => $desc
            ];
        }
    }
}

// 讓預設 album 排第一
usort($themes, function($a, $b) {
    if ($a['id'] === 'album') return -1;
    if ($b['id'] === 'album') return 1;
    return strcmp($a['id'], $b['id']);
});

echo json_encode($themes);
?>