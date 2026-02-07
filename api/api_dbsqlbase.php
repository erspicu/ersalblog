<?php
/**
 * API DB SQL Base (MySQL)
 * Replicates the functionality of api_filebase.php but uses MySQL database.
 */

error_reporting(E_ALL & ~E_NOTICE);
require_once '../config.php';
require_once '../admin/system_helper.php';

// Initialize Database Connection
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    die("Database Connection Error: " . $e->getMessage());
}

restful_rounter($_SERVER['QUERY_STRING']);

function restful_rounter($path)
{
    $action = explode("/", $path);
    $param_count = count($action);

    if ($param_count == 1 || !function_exists($action[1]) || $action[1] == 'page') {
        get_data('all', null);
        return;
    }

    $action[1]($action);
}

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

function get_data($filter_type, $filter_value)
{
    global $pdo, $posts_per_page;
    $per_page = isset($posts_per_page) ? (int)$posts_per_page : 10;

    $current_page = 1;
    if (isset($_GET['page'])) {
        $current_page = (int)$_GET['page'];
    } elseif (isset($_SERVER['QUERY_STRING']) && strpos($_SERVER['QUERY_STRING'], 'page=') !== false) {
        preg_match('/page=(\d+)/', $_SERVER['QUERY_STRING'], $matches);
        if (isset($matches[1])) $current_page = (int)$matches[1];
    }
    if ($current_page < 1) $current_page = 1;

    // 1. Fetch All Published Posts (For Sidebars and filtering)
    $sql = "SELECT p.*, GROUP_CONCAT(c.category_name SEPARATOR ',') as post_categories_str 
            FROM blog_posts p
            LEFT JOIN blog_post_categories pc ON p.id = pc.post_id
            LEFT JOIN blog_categories c ON pc.category_id = c.id
            WHERE p.status = 'published'
            GROUP BY p.id
            ORDER BY p.post_date DESC";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    $matched_posts = array();
    $ret_tag_count = array();
    $ret_date = array();      
    $ret_date_post = array(); 
    $cat_stats_map = array(); 

    foreach ($rows as $row) {
        $filename = $row['post_filename'];
        $title    = $row['post_title'];
        $date_str = $row['post_date']; 
        $content  = $row['post_content'];
        
        $tags_str = $row['post_tags'];
        $tags = ($tags_str !== '') ? explode(',', $tags_str) : array();
        $tags = array_map('trim', $tags);
        
        $cats_str = (isset($row['post_categories_str']) && $row['post_categories_str'] !== null) ? $row['post_categories_str'] : '';
        $cats = ($cats_str !== '') ? explode(',', $cats_str) : array();
        $cats = array_map('trim', $cats);

        // Sidebar Stats
        foreach ($tags as $t) {
            if ($t === '') continue;
            $ret_tag_count[$t] = (isset($ret_tag_count[$t]) ? $ret_tag_count[$t] : 0) + 1;
        }

        foreach ($cats as $c) {
            if ($c === '') continue;
            if (!isset($cat_stats_map[$c])) {
                $cat_stats_map[$c] = array('name' => $c, 'count' => 0, 'posts' => array());
            }
            $cat_stats_map[$c]['count']++;
            $cat_stats_map[$c]['posts'][] = str_replace('.html', '', $filename); 
        }

        $dt_parts = explode(' ', $date_str);
        $ymd = explode('-', $dt_parts[0]);
        if (count($ymd) >= 2) {
            $year = $ymd[0]; $mon = $ymd[1]; $ymKey = $year . $mon;
            $ret_date[$year] = (isset($ret_date[$year]) ? $ret_date[$year] : 0) + 1;
            $ret_date[$ymKey] = (isset($ret_date[$ymKey]) ? $ret_date[$ymKey] : 0) + 1;
            if (!isset($ret_date_post[$ymKey])) $ret_date_post[$ymKey] = array();
            $ret_date_post[$ymKey][] = array('title' => $title, 'post_index' => $filename);
        }

        // Filtering
        $is_match = false;
        if ($filter_type === 'all') {
            $is_match = true;
        } elseif ($filter_type === 'category') {
            if (in_array($filter_value, $cats)) $is_match = true;
        } elseif ($filter_type === 'tag') {
            if (in_array($filter_value, $tags)) $is_match = true;
        } elseif ($filter_type === 'date') {
            if (substr($date_str, 0, 7) === (substr($filter_value, 0, 4) . '-' . substr($filter_value, 4, 2))) $is_match = true;
        }

        if ($is_match) {
            if (!file_exists("../post/" . $filename)) continue;
            
            $matched_posts[] = array(
                'post_category' => $cats,
                'post_tags'     => $tags,
                'post_time'     => $date_str,
                'post_title'    => $title,
                'post_index'    => $filename,
                'raw_content'   => $content // Temporary
            );
        }
    }

    // --- Pagination ---
    $total_posts = count($matched_posts);
    $total_pages = ceil($total_posts / $per_page);
    $start_index = ($current_page - 1) * $per_page;
    $paged_posts = array_slice($matched_posts, $start_index, $per_page);

    foreach ($paged_posts as &$p) {
        $content_parts = explode('<!--more-->', $p['raw_content']);
        $p['post_content'] = protect_script_tags($content_parts[0]);
        unset($p['raw_content']);
    }

    $ret_all = array(
        'category'    => array_values($cat_stats_map),
        'dates_count' => $ret_date,
        'date_post'   => $ret_date_post,
        'tags'        => $ret_tag_count,
        'posts'       => $paged_posts,
        'pagination'  => array(
            'total_posts' => $total_posts,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'per_page'    => $per_page
        )
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($ret_all);
}
