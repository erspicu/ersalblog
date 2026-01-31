<?php
// admin/data_provider.php
require_once 'auth.php';

class DataManager {
    private $source;
    private $pdo;
    private $baseDir;

    public function __construct() {
        $this->source = getAdminSource();
        $this->baseDir = dirname(__DIR__); // Project root
        
        if ($this->source === 'db') {
            global $pdo; // Use the global PDO connection from auth.php
            $this->pdo = $pdo;
        }
    }

    public function getSource() {
        return $this->source;
    }

    // --- Statistics ---

    public function getPostCount() {
        if ($this->source === 'db') {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM blog_posts");
            return $stmt->fetchColumn();
        } else {
            $file = $this->baseDir . '/contents/index_post.txt';
            if (!file_exists($file)) return 0;
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return count($lines);
        }
    }

    public function getRecentPosts($limit = 5) {
        if ($this->source === 'db') {
            $stmt = $this->pdo->prepare("SELECT post_title, post_date FROM blog_posts ORDER BY post_date DESC LIMIT ?");
            // PDO::PARAM_INT is important for LIMIT
            $stmt->bindValue(1, $limit, PDO::PARAM_INT); 
            $stmt->execute();
            return $stmt->fetchAll();
        } else {
            $posts = $this->getAllPosts(); // Already sorted by date DESC usually
            $recent = array_slice($posts, 0, $limit);
            // Map keys to match DB structure
            return array_map(function($p) {
                return [
                    'post_title' => $p['post_title'],
                    'post_date' => $p['post_date']
                ];
            }, $recent);
        }
    }

    // --- Posts Management ---

    public function getAllPosts() {
        if ($this->source === 'db') {
            $sql = "SELECT p.id, p.post_date, p.post_title, p.post_filename, p.post_tags, p.post_description, 
                           GROUP_CONCAT(c.category_name SEPARATOR ',') as post_categories
                    FROM blog_posts p
                    LEFT JOIN blog_post_categories pc ON p.id = pc.post_id
                    LEFT JOIN blog_categories c ON pc.category_id = c.id
                    GROUP BY p.id
                    ORDER BY p.post_date DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } else {
            $file = $this->baseDir . '/contents/index_post.txt';
            if (!file_exists($file)) return [];
            
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $posts = [];
            foreach ($lines as $line) {
                // Format: time|filename|title|tags|desc
                $parts = explode('|', $line);
                if (count($parts) < 3) continue;
                
                // Get Categories
                $filename = trim($parts[1]);
                $cats = $this->getFilePostCategories($filename);

                $posts[] = [
                    'id' => $filename, // Use filename as ID for file system
                    'post_date' => trim($parts[0]),
                    'post_filename' => $filename,
                    'post_title' => trim($parts[2]),
                    'post_tags' => isset($parts[3]) ? trim($parts[3]) : '',
                    'post_description' => isset($parts[4]) ? trim($parts[4]) : '',
                    'post_categories' => $cats
                ];
            }
            // Sort by date DESC
            usort($posts, function($a, $b) {
                return strcmp($b['post_date'], $a['post_date']);
            });
            return $posts;
        }
    }

    public function getPost($id) {
        if ($this->source === 'db') {
            $sql = "SELECT p.*, GROUP_CONCAT(c.category_name SEPARATOR ',') as post_categories
                    FROM blog_posts p
                    LEFT JOIN blog_post_categories pc ON p.id = pc.post_id
                    LEFT JOIN blog_categories c ON pc.category_id = c.id
                    WHERE p.id = ?
                    GROUP BY p.id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch();
        } else {
            // For file system, $id IS the filename
            $filename = $id;
            $indexFile = $this->baseDir . '/contents/index_post.txt';
            $lines = file($indexFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                $parts = explode('|', $line);
                if (trim($parts[1]) === $filename) {
                    $contentPath = $this->baseDir . '/contents/post_files/' . $filename;
                    $content = file_exists($contentPath) ? file_get_contents($contentPath) : '';
                    
                    return [
                        'id' => $filename,
                        'post_date' => trim($parts[0]),
                        'post_filename' => $filename,
                        'post_title' => trim($parts[2]),
                        'post_tags' => isset($parts[3]) ? trim($parts[3]) : '',
                        'post_description' => isset($parts[4]) ? trim($parts[4]) : '',
                        'post_categories' => $this->getFilePostCategories($filename),
                        'post_content' => $content
                    ];
                }
            }
            return false;
        }
    }

    public function savePost($data) {
        $id = $data['id'] ?? null;
        
        if ($this->source === 'db') {
            try {
                $this->pdo->beginTransaction();

                if ($id) {
                    // Update
                    $sql = "UPDATE blog_posts SET 
                            post_title = ?, post_filename = ?, post_date = ?, post_content = ?, 
                            post_tags = ?, post_description = ?, updated_at = NOW()
                            WHERE id = ?";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        $data['title'], $data['filename'], $data['date'], $data['content'],
                        $data['tags'], $data['desc'], $id
                    ]);
                    $postId = $id;
                } else {
                    // Insert
                    // Check dup filename
                    $check = $this->pdo->prepare("SELECT id FROM blog_posts WHERE post_filename = ?");
                    $check->execute([$data['filename']]);
                    if ($check->rowCount() > 0) {
                         $data['filename'] = str_replace('.html', '-' . rand(100,999) . '.html', $data['filename']);
                    }

                    $sql = "INSERT INTO blog_posts 
                            (post_title, post_filename, post_date, post_content, post_tags, post_description, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        $data['title'], $data['filename'], $data['date'], $data['content'],
                        $data['tags'], $data['desc']
                    ]);
                    $postId = $this->pdo->lastInsertId();
                }

                // --- Sync Categories (New Schema) ---
                // 1. Clear old pivot records
                $delPivot = $this->pdo->prepare("DELETE FROM blog_post_categories WHERE post_id = ?");
                $delPivot->execute([$postId]);

                // 2. Process Categories
                $cats = explode(',', $data['categories']);
                foreach ($cats as $catName) {
                    $catName = trim($catName);
                    if ($catName === '') continue;

                    // Get or Create Category ID
                    $checkCat = $this->pdo->prepare("SELECT id FROM blog_categories WHERE category_name = ?");
                    $checkCat->execute([$catName]);
                    $catId = $checkCat->fetchColumn();

                    if (!$catId) {
                        $insCat = $this->pdo->prepare("INSERT INTO blog_categories (category_name) VALUES (?)");
                        $insCat->execute([$catName]);
                        $catId = $this->pdo->lastInsertId();
                    }

                    // Insert Pivot
                    $insPivot = $this->pdo->prepare("INSERT INTO blog_post_categories (post_id, category_id) VALUES (?, ?)");
                    $insPivot->execute([$postId, $catId]);
                }

                $this->pdo->commit();
                return true;

            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } else {
            // File System Save
            // 1. Save Content HTML
            $contentPath = $this->baseDir . '/contents/post_files/' . $data['filename'];
            file_put_contents($contentPath, $data['content']);

            // 2. Update Index File
            $indexFile = $this->baseDir . '/contents/index_post.txt';
            $lines = file_exists($indexFile) ? file($indexFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
            
            $newLine = implode('|', [
                $data['date'],
                $data['filename'],
                $data['title'],
                $data['tags'],
                $data['desc']
            ]);

            $found = false;
            // If ID exists (filename), we might be updating or renaming. 
            // In File mode, ID is old filename. 
            $targetFilename = $id ?: $data['filename']; 

            foreach ($lines as $k => $line) {
                $parts = explode('|', $line);
                if (trim($parts[1]) === $targetFilename) {
                    $lines[$k] = $newLine;
                    $found = true;
                    // If filename changed (rename), delete old content file? 
                    // For safety, we keep it or manual cleanup. But if we renamed, we should write to new file (done above)
                    // and maybe remove old one. 
                    if ($id && $id !== $data['filename']) {
                         $oldPath = $this->baseDir . '/contents/post_files/' . $id;
                         if (file_exists($oldPath)) @unlink($oldPath);
                         
                         // Also remove old category references
                         $this->updateFileCategories($id, ''); 
                    }
                    break;
                }
            }

            if (!$found) {
                // Add new at top (or append and sort?)
                // Usually append, rendering sorts it. Or prepend.
                array_unshift($lines, $newLine); 
            }

            // Save Index
            file_put_contents($indexFile, implode("\n", $lines));

            // 3. Update Categories (Folders)
            $this->updateFileCategories($data['filename'], $data['categories']);
            
            return true;
        }
    }

    public function deletePost($id) {
        if ($this->source === 'db') {
            $stmt = $this->pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $filename = $id;
            
            // 1. Remove from Index
            $indexFile = $this->baseDir . '/contents/index_post.txt';
            $lines = file($indexFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $newLines = [];
            foreach ($lines as $line) {
                if (strpos($line, $filename) === false) { // Simple check, better explode
                    $parts = explode('|', $line);
                    if (trim($parts[1]) !== $filename) {
                        $newLines[] = $line;
                    }
                }
            }
            file_put_contents($indexFile, implode("\n", $newLines));

            // 2. Delete Content File
            $contentPath = $this->baseDir . '/contents/post_files/' . $filename;
            if (file_exists($contentPath)) @unlink($contentPath);

            // 3. Remove Category Links
            $this->updateFileCategories($filename, ''); // Empty string = remove all
        }
    }

    // --- Categories Management ---

    public function createCategory($name) {
        $name = trim($name);
        if ($name === '') return false;

        if ($this->source === 'db') {
            try {
                $stmt = $this->pdo->prepare("INSERT IGNORE INTO blog_categories (category_name) VALUES (?)");
                $stmt->execute([$name]);
                return $stmt->rowCount() > 0;
            } catch (Exception $e) {
                return false;
            }
        } else {
            // File System
            $dir = $this->baseDir . '/category/' . $name;
            if (!is_dir($dir)) {
                return mkdir($dir);
            }
            return false; // Already exists
        }
    }

    public function getAllCategories() {
        if ($this->source === 'db') {
            $catStats = [];
            // Use LEFT JOIN to get all categories even with 0 posts
            $sql = "SELECT c.category_name, COUNT(pc.post_id) as cnt 
                    FROM blog_categories c
                    LEFT JOIN blog_post_categories pc ON c.id = pc.category_id
                    GROUP BY c.id, c.category_name
                    ORDER BY cnt DESC";
            
            try {
                $stmt = $this->pdo->query($sql);
                while ($row = $stmt->fetch()) {
                    $catStats[$row['category_name']] = $row['cnt'];
                }
            } catch (Exception $e) {
                // Fallback or Empty
                $catStats = [];
            }
            return $catStats;
        } else {
            $catDir = $this->baseDir . '/category';
            $cats = [];
            if (!is_dir($catDir)) return [];
            
            $dirs = scandir($catDir);
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') continue;
                if (is_dir($catDir . '/' . $dir)) {
                    // Count files in this category folder
                    $files = scandir($catDir . '/' . $dir);
                    $count = 0;
                    foreach ($files as $f) {
                        if ($f !== '.' && $f !== '..') $count++;
                    }
                    $cats[$dir] = $count;
                }
            }
            arsort($cats);
            return $cats;
        }
    }

    public function renameCategory($oldName, $newName) {
         if ($this->source === 'db') {
            // 1. Update Category Table
            $stmt = $this->pdo->prepare("UPDATE blog_categories SET category_name = ? WHERE category_name = ?");
            $stmt->execute([$newName, $oldName]);
            return $stmt->rowCount() > 0;
         } else {
             // File System: Rename Directory
             $base = $this->baseDir . '/category/';
             if (is_dir($base . $oldName)) {
                 rename($base . $oldName, $base . $newName);
                 return true; // Simplified count
             }
             return 0;
         }
    }

    public function deleteCategory($name) {
        if ($this->source === 'db') {
            // 1. Get ID
            $stmt = $this->pdo->prepare("SELECT id FROM blog_categories WHERE category_name = ?");
            $stmt->execute([$name]);
            $catId = $stmt->fetchColumn();

            if ($catId) {
                // 2. Delete from Category Table (Cascade deletes from pivot)
                $del = $this->pdo->prepare("DELETE FROM blog_categories WHERE id = ?");
                $del->execute([$catId]);
                return true;
            }
            return false;
        } else {
             // File System: Remove Directory
             $dir = $this->baseDir . '/category/' . $name;
             if (is_dir($dir)) {
                 $files = glob($dir . '/*');
                 foreach ($files as $file) {
                     if (is_file($file)) unlink($file);
                 }
                 rmdir($dir);
                 return true;
             }
             return 0;
        }
    }

    // --- Helpers for File System ---

    private function getFilePostCategories($filename) {
        $cats = [];
        $catBase = $this->baseDir . '/category';
        if (!is_dir($catBase)) return '';
        
        $dirs = scandir($catBase);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            // Check if filename exists inside this category folder
            // Usually empty files with same name
            $target = $catBase . '/' . $dir . '/' . $filename;
            if (file_exists($target)) {
                $cats[] = $dir;
            }
        }
        return implode(',', $cats);
    }

    private function updateFileCategories($filename, $newCatsStr) {
        // 1. Remove from all existing categories first
        $catBase = $this->baseDir . '/category';
        if (!is_dir($catBase)) mkdir($catBase);
        
        $dirs = scandir($catBase);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            $target = $catBase . '/' . $dir . '/' . $filename;
            if (file_exists($target)) unlink($target);
        }

        // 2. Add to new categories
        if (trim($newCatsStr) === '') return;
        $newCats = explode(',', $newCatsStr);
        foreach ($newCats as $cat) {
            $cat = trim($cat);
            if ($cat === '') continue;
            
            $catDir = $catBase . '/' . $cat;
            if (!is_dir($catDir)) mkdir($catDir);
            
            // Create empty file
            touch($catDir . '/' . $filename);
        }
    }
}
?>