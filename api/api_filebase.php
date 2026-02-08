<?php

error_reporting(E_ALL & ~E_NOTICE);
require_once "../config.php";
require_once "../admin/system_helper.php";

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
    global $posts_per_page;
    $per_page = isset($posts_per_page) ? (int)$posts_per_page : 10;
    
    // 獲取當前頁碼 (支援 ?page=N 或 &page=N)
    $current_page = 1;
    if (isset($_GET['page'])) {
        $current_page = (int)$_GET['page'];
    } elseif (isset($_SERVER['QUERY_STRING']) && strpos($_SERVER['QUERY_STRING'], 'page=') !== false) {
        preg_match('/page=(\d+)/', $_SERVER['QUERY_STRING'], $matches);
        if (isset($matches[1])) $current_page = (int)$matches[1];
    }
    if ($current_page < 1) $current_page = 1;

    $index_file = "../contents/index_post.txt";
    $index_content = file_get_contents($index_file);
    $index_content = str_replace("\r\n", "\n", $index_content);
    $lines = explode("\n", $index_content);

    $all_categories = array();
    $cat_dirs = scandir("../category");
    foreach ($cat_dirs as $dir) {
        if ($dir == '.' || $dir == '..' || !is_dir("../category/" . $dir)) continue;
        $files = scandir("../category/" . $dir);
        $valid_files = array();
        foreach ($files as $f) {
            if ($f == '.' || $f == '..') continue;
            if (file_exists("../contents/post_files/" . $f) || file_exists("../contents/post_files/" . $f . ".html")) {
                $valid_files[] = $f;
            }
        }
        $all_categories[] = array('name' => $dir, 'count' => count($valid_files), 'posts' => $valid_files);
    }

    $matched_posts = array(); // 先存所有符合條件的文章
    $ret_tag_count = array();
    $ret_date = array();
    $ret_date_post = array();

    foreach ($lines as $line) {
        if (trim($line) === "") continue;
        $parts = explode("|", $line);
        $date_str = $parts[0];
        $filename = $parts[1];
        $title    = $parts[2];
        $tags_str = trim($parts[3]);
        
        $raw_tags = ($tags_str !== "") ? explode(",", $tags_str) : array();
        $tags = array();
        foreach ($raw_tags as $t) {
            $t = trim($t);
            if ($t !== "") {
                $tags[] = $t;
                $ret_tag_count[$t] = (isset($ret_tag_count[$t]) ? $ret_tag_count[$t] : 0) + 1;
            }
        }

        if (!file_exists("../contents/post_files/" . $filename)) continue;

        $date_only = explode(" ", $date_str)[0];
        $ymd = explode("-", $date_only);
        if (count($ymd) >= 2) {
            $year = $ymd[0]; $mon = $ymd[1]; $ymKey = $year . $mon;
            $ret_date[$year] = (isset($ret_date[$year]) ? $ret_date[$year] : 0) + 1;
            $ret_date[$ymKey] = (isset($ret_date[$ymKey]) ? $ret_date[$ymKey] : 0) + 1;
            if (!isset($ret_date_post[$ymKey])) $ret_date_post[$ymKey] = array();
            $ret_date_post[$ymKey][] = array('title' => $title, 'post_index' => $filename);
        }

        $is_match = false;
        if ($filter_type === 'all') {
            $is_match = true;
        } elseif ($filter_type === 'tag') {
            if (in_array($filter_value, $tags)) $is_match = true;
        } elseif ($filter_type === 'category') {
            $nameNoExt = str_replace(".html", "", $filename);
            foreach ($all_categories as $c) {
                if ($c['name'] === $filter_value) {
                    if (in_array($filename, $c['posts']) || in_array($nameNoExt, $c['posts'])) $is_match = true;
                    break;
                }
            }
        } elseif ($filter_type === 'date') {
            $f_year = substr($filter_value, 0, 4);
            $f_mon  = substr($filter_value, 4, 2);
            $target_prefix = ($f_mon !== "") ? ($f_year . "-" . $f_mon) : ($f_year . "-");
            if (strpos($date_str, $target_prefix) === 0) $is_match = true;
        }

        if ($is_match) {
            if (!file_exists("../post/" . $filename)) continue;
            
            $post_cats = array();
            $nameNoExt = str_replace(".html", "", $filename);
            foreach ($all_categories as $c) {
                if (in_array($filename, $c['posts']) || in_array($nameNoExt, $c['posts'])) {
                    $post_cats[] = $c['name'];
                }
            }

            $matched_posts[] = array(
                'post_category' => $post_cats,
                'post_tags'     => $tags,
                'post_time'     => $date_str,
                'post_title'    => $title,
                'post_index'    => $filename
            );
        }
    }

    // --- 分頁切割 ---
    $total_posts = count($matched_posts);
    $total_pages = ceil($total_posts / $per_page);
    $start_index = ($current_page - 1) * $per_page;
    $paged_posts = array_slice($matched_posts, $start_index, $per_page);

    // 補充內容 (僅對該頁進行讀取，節省 IO)
    foreach ($paged_posts as &$p) {
        $html = file_get_contents("../contents/post_files/" . $p['post_index']);
        $html_split = explode("<!--more-->", $html);
        $summary = protect_script_tags($html_split[0]);
        // 修正相簿圖片路徑
        $p['post_content'] = fix_album_paths_for_root($summary, $posts_per_page_global_or_album_path = $GLOBALS['album_path']);
    }

    $ret_all = array(
        'category'    => $all_categories,
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
