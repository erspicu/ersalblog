<?php
require_once 'auth.php';
requireAlbumLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: albums.php');
    exit;
}

validateCSRFRequest();

$action = isset($_POST['action']) ? $_POST['action'] : '';
$albumId = isset($_POST['album_id']) ? trim($_POST['album_id']) : '';
$collectionDir = __DIR__ . '/../Collection';
$targetDir = $collectionDir . '/' . $albumId;

if (empty($albumId) || !is_dir($targetDir)) die("Album not found");

// Helper to save photo meta
function savePhotoMeta($targetDir, $metaData) {
    $lines = array();
    foreach ($metaData as $fn => $data) {
        // filename|title|desc
        $lines[] = $fn . '|' . $data['title'] . '|' . $data['desc'];
    }
    file_put_contents($targetDir . '/comment_pic.txt', implode("
", $lines));
}

// Helper to load photo meta
function loadPhotoMeta($targetDir) {
    $meta = array();
    $file = $targetDir . '/comment_pic.txt';
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $p = explode('|', $line);
            if (count($p) >= 1) {
                $meta[trim($p[0])] = array(
                    'title' => isset($p[1]) ? $p[1] : '',
                    'desc' => isset($p[2]) ? $p[2] : ''
                );
            }
        }
    }
    return $meta;
}

switch ($action) {
    case 'upload_photos':
        $uploadedFiles = array();
        $errors = array();

        if (isset($_FILES['photos'])) {
            $files = $_FILES['photos'];
            
            // Check if it's multiple files or a single file
            if (is_array($files['name'])) {
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpName = $files['tmp_name'][$i];
                        $name = $files['name'][$i];
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        
                        if ($ext === 'jpg' || $ext === 'jpeg') {
                            $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $name);
                            if (move_uploaded_file($tmpName, $targetDir . '/' . $safeName)) {
                                $uploadedFiles[] = $safeName;
                            } else {
                                $errors[] = "Failed to move: $name";
                            }
                        } else {
                            $errors[] = "Invalid extension: $name";
                        }
                    } else {
                        $errors[] = "Upload error code (" . $files['error'][$i] . ") for file index $i";
                    }
                }
            } else {
                // Single file upload handling (often from AJAX FormData)
                if ($files['error'] === UPLOAD_ERR_OK) {
                    $tmpName = $files['tmp_name'];
                    $name = $files['name'];
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if ($ext === 'jpg' || $ext === 'jpeg') {
                        $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $name);
                        if (move_uploaded_file($tmpName, $targetDir . '/' . $safeName)) {
                            $uploadedFiles[] = $safeName;
                        } else {
                            $errors[] = "Failed to move: $name";
                        }
                    } else {
                        $errors[] = "Invalid extension: $name";
                    }
                } else {
                    $errors[] = "Upload error code (" . $files['error'] . ")";
                }
            }
        }

        // Return JSON if it's an AJAX request
        if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
            header('Content-Type: application/json');
            if (empty($errors)) {
                echo json_encode(array('status' => 'success', 'files' => $uploadedFiles));
            } else {
                echo json_encode(array('status' => 'error', 'message' => implode(', ', $errors), 'files' => $uploadedFiles));
            }
            exit;
        }

        header("Location: album_photos.php?id=" . urlencode($albumId));
        break;

    case 'delete_photo':
        $filename = isset($_POST['filename']) ? trim($_POST['filename']) : '';
        if ($filename && file_exists($targetDir . '/' . $filename)) {
            unlink($targetDir . '/' . $filename);
            // Delete thumbs
            $namePart = pathinfo($filename, PATHINFO_FILENAME);
            array_map('unlink', glob($targetDir . '/Thumbnail/' . $namePart . '_*.jpg'));
            
            // Remove from meta
            $meta = loadPhotoMeta($targetDir);
            if (isset($meta[$filename])) {
                unset($meta[$filename]);
                savePhotoMeta($targetDir, $meta);
            }
        }
        header("Location: album_photos.php?id=" . urlencode($albumId));
        break;

    case 'update_photo_info':
        $originalFilename = isset($_POST['original_filename']) ? trim($_POST['original_filename']) : '';
        $newFilename = isset($_POST['new_filename']) ? trim($_POST['new_filename']) : '';
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $desc = isset($_POST['description']) ? trim($_POST['description']) : '';

        if (!$originalFilename || !file_exists($targetDir . '/' . $originalFilename)) die("File not found");

        $meta = loadPhotoMeta($targetDir);
        
        // Rename if needed
        if ($newFilename && $newFilename !== $originalFilename) {
            $ext = strtolower(pathinfo($newFilename, PATHINFO_EXTENSION));
            if ($ext !== 'jpg' && $ext !== 'jpeg') $newFilename .= '.jpg';
            
            if (!file_exists($targetDir . '/' . $newFilename)) {
                rename($targetDir . '/' . $originalFilename, $targetDir . '/' . $newFilename);
                // Handle Meta Key Update
                unset($meta[$originalFilename]);
                $originalFilename = $newFilename; // Update for next steps
                
                // TODO: Rename thumbs logically (complex due to _thumb suffixes)
                // For simplicity, we might just delete thumbs and let generator recreate, 
                // or try to rename them. Let's delete to ensure consistency.
                $oldNamePart = pathinfo($_POST['original_filename'], PATHINFO_FILENAME);
                array_map('unlink', glob($targetDir . '/Thumbnail/' . $oldNamePart . '_*.jpg'));
            } else {
                die("Target filename exists.");
            }
        }

        // Update Meta
        $meta[$originalFilename] = array('title' => $title, 'desc' => $desc);
        savePhotoMeta($targetDir, $meta);

        header("Location: album_photos.php?id=" . urlencode($albumId));
        break;

    case 'set_cover':
        $filename = isset($_POST['filename']) ? trim($_POST['filename']) : '';
        
        $metaFile = $targetDir . '/comment_album.txt';
        $parts = array('', '', '', date('Ymd'));
        if (file_exists($metaFile)) {
            $parts = explode('|', file_get_contents($metaFile));
        }
        
        // Update cover field (index 2)
        // Store relative path or just filename? make_album logic checks both.
        // Let's store just filename to match default behavior or specific if needed.
        $parts[2] = $filename; // make_album uses basename() mostly
        
        file_put_contents($metaFile, implode('|', $parts));
        
        header("Location: album_photos.php?id=" . urlencode($albumId));
        break;

    default:
        header('Location: albums.php');
}
?>
