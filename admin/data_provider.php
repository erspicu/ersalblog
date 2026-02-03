<?php
// admin/data_provider.php
require_once 'auth.php';

class DataManager {
    private $source;
    private $pdo;
    private $baseDir;
    private $isSQLite = false;

    public function __construct() {
        $this->source = getAdminSource();
        $this->baseDir = dirname(__DIR__); // Project root
        
        if ($this->source === 'db' || $this->source === 'sqlite') {
            global $pdo; 
            $this->pdo = $pdo;
            $this->isSQLite = ($this->source === 'sqlite');
            $this->ensureSchema();
        }
    }

    private function ensureSchema() {
        try {
            // Check if 'status' column exists
            $stmt = $this->pdo->query("SELECT status FROM blog_posts LIMIT 1");
        } catch (Exception $e) {
            // Column likely missing, try to add it
            try {
                if ($this->isSQLite) {
                    $this->pdo->exec("ALTER TABLE blog_posts ADD COLUMN status TEXT DEFAULT 'published'");
                } else {
                    $this->pdo->exec("ALTER TABLE blog_posts ADD COLUMN status VARCHAR(20) DEFAULT 'published'");
                }
            } catch (Exception $ex) {
                // Ignore if it fails (e.g. concurrent update), user might need manual fix if strictly broken
            }
        }
    }

    public function getSource() {
        return $this->source;
    }

    // --- Statistics ---

    public function getPostCount() {
        if ($this->source === 'db' || $this->source === 'sqlite') {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM blog_posts");
            return $stmt->fetchColumn();
        } else {
            $file = $this->baseDir . '/contents/index_post.txt';
            if (!file_exists($file)) return 0;
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return count($lines);
        }
    }

    public function getPostCounts() {
        if ($this->source === 'db' || $this->source === 'sqlite') {
            $sql = "SELECT status, COUNT(*) as cnt FROM blog_posts GROUP BY status";
            try {
                $stmt = $this->pdo->query($sql);
                $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Returns ['published' => 10, 'draft' => 2]
            } catch (Exception $e) {
                // Fallback if status column missing (shouldn't happen due to ensureSchema)
                $counts = [];
            }
            
            $published = $counts['published'] ?? 0;
            $draft = $counts['draft'] ?? 0;
            
            // Recalculate total from parts or DB count (total might include other statuses if any)
            // Or just sum them.
            $total = $this->getPostCount(); // Keep consistent with total rows

            return [
                'total' => $total,
                'published' => $published,
                'draft' => $draft
            ];
        } else {
            $file = $this->baseDir . '/contents/index_post.txt';
            if (!file_exists($file)) return ['total'=>0, 'published'=>0, 'draft'=>0];
            
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $total = 0;
            $published = 0;
            $draft = 0;

            foreach ($lines as $line) {
                $parts = explode('|', $line);
                if (count($parts) < 2) continue;
                $filename = trim($parts[1]);
                
                $pubPath = $this->baseDir . '/contents/post_files/' . $filename;
                $draftPath = $pubPath . '.tmp';
                
                if (file_exists($pubPath)) {
                    $published++;
                    $total++;
                } elseif (file_exists($draftPath)) {
                    $draft++;
                    $total++;
                } else {
                    // Missing file, still counts as total? 
                    // Usually yes, it's an entry. But maybe separate?
                    // Let's count as total but not published/draft.
                    $total++;
                }
            }
            return [
                'total' => $total,
                'published' => $published,
                'draft' => $draft
            ];
        }
    }

    public function getRecentPosts($limit = 5) {
        if ($this->source === 'db' || $this->source === 'sqlite') {
            $stmt = $this->pdo->prepare("SELECT post_title, post_date, status FROM blog_posts ORDER BY post_date DESC LIMIT ?");
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
                    'post_date' => $p['post_date'],
                    'status' => $p['status'] ?? 'published'
                ];
            }, $recent);
        }
    }

    // --- Posts Management ---

    public function getAllPosts() {
        if ($this->source === 'db' || $this->source === 'sqlite') {
            $sql = "SELECT p.id, p.post_date, p.post_title, p.post_filename, p.post_tags, p.post_description, p.status, 
                           GROUP_CONCAT(c.category_name) as post_categories
                    FROM blog_posts p
                    LEFT JOIN blog_post_categories pc ON p.id = pc.post_id
                    LEFT JOIN blog_categories c ON pc.category_id = c.id
                    GROUP BY p.id
                    ORDER BY p.post_date DESC";
            $stmt = $this->pdo->query($sql);
            $posts = $stmt->fetchAll();
            return $posts;
        } else {
            $file = $this->baseDir . '/contents/index_post.txt';
            if (!file_exists($file)) return [];
            
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $posts = [];
            foreach ($lines as $line) {
                // Format: time|filename|title|tags|desc
                $parts = explode('|', $line);
                if (count($parts) < 3) continue;
                
                $filename = trim($parts[1]);
                $cats = $this->getFilePostCategories($filename);

                // Determine Status
                $basePath = $this->baseDir . '/contents/post_files/' . $filename;
                $status = 'missing';
                if (file_exists($basePath)) {
                    $status = 'published';
                } elseif (file_exists($basePath . '.tmp')) {
                    $status = 'draft';
                }

                $posts[] = [
                    'id' => $filename, // Use filename as ID for file system
                    'post_date' => trim($parts[0]),
                    'post_filename' => $filename,
                    'post_title' => trim($parts[2]),
                    'post_tags' => isset($parts[3]) ? trim($parts[3]) : '',
                    'post_description' => isset($parts[4]) ? trim($parts[4]) : '',
                    'post_categories' => $cats,
                    'status' => $status
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
        if ($this->source === 'db' || $this->source === 'sqlite') {
            $sql = "SELECT p.*, GROUP_CONCAT(c.category_name) as post_categories
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
                    $status = 'published';
                    
                    // Try Normal (Published)
                    if (file_exists($contentPath)) {
                        $content = file_get_contents($contentPath);
                    } 
                    // Try Draft
                    elseif (file_exists($contentPath . '.tmp')) {
                        $content = file_get_contents($contentPath . '.tmp');
                        $status = 'draft';
                    } 
                    // Missing
                    else {
                        $content = '';
                        $status = 'missing';
                    }
                    
                    return [
                        'id' => $filename,
                        'post_date' => trim($parts[0]),
                        'post_filename' => $filename,
                        'post_title' => trim($parts[2]),
                        'post_tags' => isset($parts[3]) ? trim($parts[3]) : '',
                        'post_description' => isset($parts[4]) ? trim($parts[4]) : '',
                        'post_categories' => $this->getFilePostCategories($filename),
                        'post_content' => $content,
                        'status' => $status
                    ];
                }
            }
            return false;
        }
    }

    public function savePost($data) {
        $id = $data['id'] ?? null;
        
        if ($this->source === 'db' || $this->source === 'sqlite') {
            try {
                $this->pdo->beginTransaction();
                $now = date('Y-m-d H:i:s');
                $status = (!empty($data['is_draft'])) ? 'draft' : 'published';

                if ($id) {
                    // Update
                    $sql = "UPDATE blog_posts SET 
                            post_title = ?, post_filename = ?, post_date = ?, post_content = ?, 
                            post_tags = ?, post_description = ?, status = ?, updated_at = ?
                            WHERE id = ?";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        $data['title'], $data['filename'], $data['date'], $data['content'],
                        $data['tags'], $data['desc'], $status, $now, $id
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
                            (post_title, post_filename, post_date, post_content, post_tags, post_description, status, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        $data['title'], $data['filename'], $data['date'], $data['content'],
                        $data['tags'], $data['desc'], $status, $now, $now
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
                        try {
                            $insCat = $this->pdo->prepare("INSERT INTO blog_categories (category_name) VALUES (?)");
                            $insCat->execute([$catName]);
                            $catId = $this->pdo->lastInsertId();
                        } catch (Exception $e) {
                            // Race condition or constraint violation, try select again
                            $checkCat->execute([$catName]);
                            $catId = $checkCat->fetchColumn();
                        }
                    }

                    // Insert Pivot
                    if ($catId) {
                        $insPivot = $this->pdo->prepare("INSERT INTO blog_post_categories (post_id, category_id) VALUES (?, ?)");
                        $insPivot->execute([$postId, $catId]);
                    }
                }

                $this->pdo->commit();
                return true;

            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } else {
            // File System Save
            
            $isDraft = !empty($data['is_draft']);
            $baseFilename = $data['filename']; // This is always xxxx.html
            
            // Define paths
            $publishedPath = $this->baseDir . '/contents/post_files/' . $baseFilename;
            $draftPath     = $publishedPath . '.tmp';
            
            // 1. Save Content HTML to the correct location and clean up the other
            if ($isDraft) {
                file_put_contents($draftPath, $data['content']);
                if (file_exists($publishedPath)) unlink($publishedPath); // Unpublish if it was published
            } else {
                file_put_contents($publishedPath, $data['content']);
                if (file_exists($draftPath)) unlink($draftPath); // Remove draft if publishing
            }

            // 2. Update Index File
            // Note: Index file always records the "base" filename (xxxx.html), regardless of draft status.
            $indexFile = $this->baseDir . '/contents/index_post.txt';
            $lines = file_exists($indexFile) ? file($indexFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
            
            $newLine = implode('|', [
                $data['date'],
                $baseFilename,
                $data['title'],
                $data['tags'],
                $data['desc']
            ]);

            $found = false;
            // If ID exists (filename), we might be updating or renaming. 
            // In File mode, ID is old filename. 
            $targetFilename = $id ?: $baseFilename; 

            foreach ($lines as $k => $line) {
                $parts = explode('|', $line);
                if (trim($parts[1]) === $targetFilename) {
                    $lines[$k] = $newLine;
                    $found = true;
                    
                    // Handle Rename
                    if ($id && $id !== $baseFilename) {
                         // Remove old files
                         $oldPub = $this->baseDir . '/contents/post_files/' . $id;
                         $oldDraft = $oldPub . '.tmp';
                         if (file_exists($oldPub)) @unlink($oldPub);
                         if (file_exists($oldDraft)) @unlink($oldDraft);
                         
                         // Also remove old category references
                         $this->updateFileCategories($id, ''); 
                    }
                    break;
                }
            }

            if (!$found) {
                // New Post: Add to top
                array_unshift($lines, $newLine); 
            }

            // Save Index
            file_put_contents($indexFile, implode("\n", $lines));

            // 3. Update Categories (Folders)
            // Note: We use the base filename for category folders too, even if draft.
            // This is fine as empty files in category/xxx/ serve as DB indices.
            $this->updateFileCategories($baseFilename, $data['categories']);
            
            return true;
        }
    }

    public function deletePost($id) {
        if ($this->source === 'db' || $this->source === 'sqlite') {
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

        if ($this->source === 'db' || $this->source === 'sqlite') {
            try {
                // Compatible "INSERT IGNORE" logic
                // Try insert, catch exception if unique constraint fails
                $stmt = $this->pdo->prepare("INSERT INTO blog_categories (category_name) VALUES (?)");
                $stmt->execute([$name]);
                return $stmt->rowCount() > 0;
            } catch (Exception $e) {
                // If using MySQL, maybe error 1062. SQLite error 19.
                // If it fails, it likely exists, so return false (as no row inserted)
                // But functionally we might want to return true if it exists? 
                // The interface expects boolean "created or not". 
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
        if ($this->source === 'db' || $this->source === 'sqlite') {
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
         if ($this->source === 'db' || $this->source === 'sqlite') {
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
        if ($this->source === 'db' || $this->source === 'sqlite') {
            // 1. Get ID
            $stmt = $this->pdo->prepare("SELECT id FROM blog_categories WHERE category_name = ?");
            $stmt->execute([$name]);
            $catId = $stmt->fetchColumn();

            if ($catId) {
                // 2. Delete from Category Table (Cascade deletes from pivot)
                // Note: SQLite FK constraint support needs to be enabled via "PRAGMA foreign_keys = ON" 
                // However, standard DELETE works. 
                // If cascade is defined in CREATE TABLE, it works if enabled.
                // Our sqlite_init.php defined ON DELETE CASCADE.
                // But PDO defaults might not enable foreign_keys pragma by default in some versions.
                // For safety, we can rely on DB definition.
                if ($this->isSQLite) {
                     $this->pdo->exec("PRAGMA foreign_keys = ON");
                }
                
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