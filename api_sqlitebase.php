<?php
/**
 * API DB SQL Base
 * Replicates the functionality of api_filebase.php but uses MySQL database.
 */

error_reporting(E_ALL & ~E_NOTICE);
require_once 'config.php';

// Initialize Database Connection
try {
    if (!isset($sqlite_path) || empty($sqlite_path)) {
        throw new Exception("SQLite path not configured in config.php");
    }
    // Check if file exists. If not, API cannot function (Init must be done in Admin)
    if (!file_exists($sqlite_path)) {
         throw new Exception("SQLite database file not found: " . $sqlite_path);
    }

    $dsn = "sqlite:" . $sqlite_path;
    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    // In a real API, return JSON error, but to match filebase behavior we might output text or die.
    header('HTTP/1.1 500 Internal Server Error');
    die("Database Connection Error: " . $e->getMessage());
}

restful_rounter($_SERVER['QUERY_STRING']);

function restful_rounter($path)
{
    $action = explode("/", $path);
    $param_count = count($action);
    if ($param_count == 1) {
        get_index(1);
        return;
    }

    if (function_exists($action[1])) {
        $action[1]($action);
    } else {
        echo urldecode($path) . " url param error!";
    }
}

// --- Routes ---

function category($url_param)
{
    $category = urldecode(explode("&", $url_param[2])[0]);
    get_data('category', $category);
}

function date_range($url_param)
{
    $date_param = explode("&", $url_param[2])[0];
    get_data('date', $date_param);
}

function tag($url_param)
{
    $dec_param =  urldecode($url_param[2]);
    $tag = explode("&", $dec_param)[0];
    get_data('tag', $tag);
}

function page($url_param)
{
    $count = count($url_param);
    if ($count == 2 || $url_param[2] == "") {
        get_index(1);
        return;
    }
    if (ctype_digit($url_param[2])) {
        // Pagination logic is not strictly implemented in filebase (it just takes the param but usually returns all or handled in JS?)
        // filebase: get_index($url_param[2]); -> calls get_contents() -> returns ALL posts.
        // It seems pagination is client-side or not fully implemented in backend limit.
        // We will follow filebase behavior: return data (maybe logic uses index_page for something but filebase get_index doesn't use it to LIMIT).
        get_index($url_param[2]);
    } else {
        echo "分頁參數錯誤!";
    }
}

function get_index($index_page)
{
    get_data('all', null);
}

// --- Core Logic ---

function get_data($filter_type, $filter_value)
{
    global $pdo;

    // 1. Fetch All Posts
    // Modified to use JOINs and GROUP_CONCAT for categories
    $sql = "SELECT p.*, GROUP_CONCAT(c.category_name) as post_categories_str 
            FROM blog_posts p
            LEFT JOIN blog_post_categories pc ON p.id = pc.post_id
            LEFT JOIN blog_categories c ON pc.category_id = c.id
            GROUP BY p.id
            ORDER BY p.post_date DESC";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    // 2. Initialize Return Structures
    $ret_post = [];
    $ret_tag_count = [];
    $ret_date = [];      // Year/Month counts
    $ret_date_post = []; // Posts grouped by Month
    
    // We need to build the category list dynamically.
    // Structure: Name => { name, count, posts: [filenames...] }
    $cat_stats_map = []; 

    // 3. Iterate and Process
    foreach ($rows as $row) {
        // --- A. Extract Data ---
        $filename = $row['post_filename'];
        $title    = $row['post_title'];
        $date_str = $row['post_date']; // "YYYY-MM-DD HH:II:SS"
        $content  = $row['post_content'];
        
        // Tags
        $tags_str = $row['post_tags'];
        $tags = ($tags_str !== '') ? explode(',', $tags_str) : [];
        $tags = array_map('trim', $tags);
        
        // Categories (Now from GROUP_CONCAT)
        $cats_str = $row['post_categories_str'] ?? '';
        $cats = ($cats_str !== '') ? explode(',', $cats_str) : [];
        $cats = array_map('trim', $cats);

        // --- B. Build Global Stats (Sidebars) ---
        
        // 1. Tags Stats
        foreach ($tags as $t) {
            if ($t === '') continue;
            $ret_tag_count[$t] = ($ret_tag_count[$t] ?? 0) + 1;
        }

        // 2. Category Stats
        foreach ($cats as $c) {
            if ($c === '') continue;
            if (!isset($cat_stats_map[$c])) {
                $cat_stats_map[$c] = [
                    'name' => $c,
                    'count' => 0,
                    'posts' => []
                ];
            }
            $cat_stats_map[$c]['count']++;
            $cat_stats_map[$c]['posts'][] = str_replace('.html', '', $filename); 
        }

        // 3. Date Stats
        // Date format: 2026-01-29 22:51:13
        $dt_parts = explode(' ', $date_str);
        $ymd = explode('-', $dt_parts[0]); // [2026, 01, 29]
        if (count($ymd) >= 2) {
            $year = $ymd[0];
            $mon  = $ymd[1];
            $ymKey = $year . $mon; // 202601

            // Count for Year
            $ret_date[$year] = ($ret_date[$year] ?? 0) + 1;
            // Count for Month
            $ret_date[$ymKey] = ($ret_date[$ymKey] ?? 0) + 1;

            // Group posts by Month
            if (!isset($ret_date_post[$ymKey])) {
                $ret_date_post[$ymKey] = [];
            }
            $ret_date_post[$ymKey][] = [
                'title' => $title,
                'post_index' => $filename
            ];
        }

        // --- C. Filter Logic (Main Content) ---
        $is_match = false;

        if ($filter_type === 'all') {
            $is_match = true;
        } 
        elseif ($filter_type === 'category') {
            if (in_array($filter_value, $cats)) {
                $is_match = true;
            }
        } 
        elseif ($filter_type === 'tag') {
            if (in_array($filter_value, $tags)) {
                $is_match = true;
            }
        } 
        elseif ($filter_type === 'date') {
            $f_year = substr($filter_value, 0, 4);
            $f_mon  = substr($filter_value, 4, 2);
            $target_prefix = $f_year . '-' . $f_mon;
            
            if (strpos($date_str, $target_prefix) === 0) {
                $is_match = true;
            }
        }

        // --- D. Add to Result if Match ---
        if ($is_match) {
            // Prepare content (summary only)
            $content_parts = explode('<!--more-->', $content);
            $summary = $content_parts[0];

            $ret_post[] = [
                'post_category' => $cats,
                'post_tags'     => $tags,
                'post_time'     => $date_str, // api_filebase returns raw string from file
                'post_title'    => $title,
                'post_content'  => $summary,
                'post_index'    => $filename
            ];
        }
    }

    // 4. Final Output Construction
    // Format category map to list
    $category_list = array_values($cat_stats_map);

    $ret_all = [
        'category'    => $category_list,
        'dates_count' => $ret_date,
        'date_post'   => $ret_date_post,
        'tags'        => $ret_tag_count,
        'posts'       => $ret_post
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($ret_all);
}
