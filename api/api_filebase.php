<?php

error_reporting(E_ALL & ~E_NOTICE);
require_once "../admin/system_helper.php";

restful_rounter($_SERVER['QUERY_STRING']);

function restful_rounter($path)
{
    $action = explode("/", $path);
    $param_count = count($action);

    // 預設路由或無效動作
    if ($param_count == 1 || !function_exists($action[1]) || $action[1] == 'page') {
        get_data('all', null);
        return;
    }

    $action[1]($action);
}

// --- API 進入點 (與前端路徑相容) ---

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

// --- 核心資料處理邏輯 ---

function get_data($filter_type, $filter_value)
{
    // 1. 取得基礎資料
    $index_file = "../contents/index_post.txt";
    $index_content = file_get_contents($index_file);
    $index_content = str_replace("\r\n", "\n", $index_content);
    $lines = explode("\n", $index_content);

    // 2. 預掃描分類資訊 (用於計算 Sidebar 與 check_category)
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

    // 3. 初始化回傳結構
    $ret_post = array();
    $ret_tag_count = array();
    $ret_date = array();
    $ret_date_post = array();

    // 4. 迭代處理文章
    foreach ($lines as $line) {
        if (trim($line) === "") continue;
        $parts = explode("|", $line);
        $date_str = $parts[0];  // YYYY-MM-DD HH:MM:SS
        $filename = $parts[1];  // 檔名
        $title    = $parts[2];  // 標題
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

        // 檢查實體檔案是否存在 (跳過草稿或損壞索引)
        if (!file_exists("../contents/post_files/" . $filename)) continue;

        // --- 統計側邊欄資訊 (日期歸檔) ---
        $date_only = explode(" ", $date_str)[0];
        $ymd = explode("-", $date_only);
        if (count($ymd) >= 2) {
            $year = $ymd[0]; $mon = $ymd[1]; $ymKey = $year . $mon;
            $ret_date[$year] = (isset($ret_date[$year]) ? $ret_date[$year] : 0) + 1;
            $ret_date[$ymKey] = (isset($ret_date[$ymKey]) ? $ret_date[$ymKey] : 0) + 1;
            if (!isset($ret_date_post[$ymKey])) $ret_date_post[$ymKey] = array();
            $ret_date_post[$ymKey][] = array('title' => $title, 'post_index' => $filename);
        }

        // --- 篩選與內容處理 ---
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
            if (substr($date_str, 0, 7) === ($f_year . "-" . $f_mon)) $is_match = true;
        }

        if ($is_match) {
            // 僅回傳已有靜態網頁的文章
            if (!file_exists("../post/" . $filename)) continue;

            $html = file_get_contents("../contents/post_files/" . $filename);
            $html_split = explode("<!--more-->", $html);

            // 獲取該文章所屬分類
            $post_cats = array();
            $nameNoExt = str_replace(".html", "", $filename);
            foreach ($all_categories as $c) {
                if (in_array($filename, $c['posts']) || in_array($nameNoExt, $c['posts'])) {
                    $post_cats[] = $c['name'];
                }
            }

            $ret_post[] = array(
                'post_category' => $post_cats,
                'post_tags'     => $tags,
                'post_time'     => $date_str,
                'post_title'    => $title,
                'post_content'  => protect_script_tags($html_split[0]),
                'post_index'    => $filename
            );
        }
    }

    $ret_all = array(
        'category'    => $all_categories,
        'dates_count' => $ret_date,
        'date_post'   => $ret_date_post,
        'tags'        => $ret_tag_count,
        'posts'       => $ret_post
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($ret_all);
}