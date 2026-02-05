<?php

error_reporting(E_ALL & ~E_NOTICE);

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

function category($url_param)
{
  $category = urldecode(explode("&", $url_param[2])[0]);
  get_Category_index($category, 1);
}

function date_range($url_param)
{
  $date_param = explode("&", $url_param[2])[0];
  get_daterange_index($date_param, 1);
}

function tag($url_param)
{
  $dec_param =  urldecode($url_param[2]);
  $tag = explode("&", $dec_param)[0];
  get_tag_index($tag, 1);
}

function get_Category_index($category_ch, $index_page)
{
  $arr = get_contents(); //取得索引資料
  $category =  category_deal(); //文章分類功能
  $ret_post = array();
  $ret_tag_count = array();
  $ret_date = array();
  $ret_date_post = array();

  // Fix: Path Traversal
  $safe_category = basename($category_ch);
  if ($safe_category !== $category_ch || empty($safe_category) || $safe_category == '.' || $safe_category == '..') {
      // Invalid path or traversal attempt
      echo json_encode(['error' => 'Invalid category path']);
      return;
  }

  $category_dir = "../category/" . $safe_category;
  if (!is_dir($category_dir)) {
      echo json_encode(['error' => 'Category not found']);
      return;
  }

  $category_post_index = scandir($category_dir);

  foreach ($arr as $val) {
    $line_arr = explode("|", $val);
    $tags = explode(",", trim($line_arr[3]));
    if (!file_exists("../contents/post_files/" . $line_arr[1])) continue;

    $nameNoExt = str_replace(".html", "", $line_arr[1]);
    if (in_array($line_arr[1], $category_post_index) || in_array($nameNoExt, $category_post_index)) {
      if (!file_exists("../contents/post_files/" . $line_arr[1])) continue;
      $html = file_get_contents("../contents/post_files/" . $line_arr[1]);
      $html_split = explode("<!--more-->", $html);

      //檢查有沒有在特定分類內start
      $in_category = check_category($category, $line_arr[1]);

      $tmp = array('post_category' => $in_category, 'post_tags' => $tags, 'post_time' => $line_arr[0], 'post_title' => $line_arr[2], 'post_content' => $html_split[0], 'post_index' => $line_arr[1]);
      array_push($ret_post, $tmp);
    }
    date_deal($ret_date, $ret_date_post, $line_arr); //日期處理
    tag_deal($tags, $ret_tag_count);   //統計標籤
  }
  $ret_all = array('category' => $category, 'dates_count' => $ret_date, "date_post" => $ret_date_post, 'tags' => $ret_tag_count,  'posts' => $ret_post);
  echo json_encode($ret_all);
}

function get_daterange_index($date_param, $index_page)
{
  $year =  substr($date_param, 0, 4);
  $mon = substr($date_param, 4, 2);

  $arr = get_contents(); //取得索引資料
  $category =  category_deal(); //文章分類功能
  $ret_post = array();
  $ret_tag_count = array();
  $ret_date = array();
  $ret_date_post = array();

  foreach ($arr as $val) {
    $line_arr = explode("|", $val);
    $tags = explode(",", trim($line_arr[3]));
    if (!file_exists("../contents/post_files/" . $line_arr[1])) continue;

    //改成檢查年跟月是否合乎範圍
    if (startsWith($line_arr[0], $year . "-" . $mon)) {
      if (!file_exists("../contents/post_files/" . $line_arr[1])) continue;
      $html = file_get_contents("../contents/post_files/" . $line_arr[1]);
      $html_split = explode("<!--more-->", $html);

      //檢查有沒有在特定分類內start
      $in_category = check_category($category, $line_arr[1]);

      $tmp = array('post_category' => $in_category, 'post_tags' => $tags, 'post_time' => $line_arr[0], 'post_title' => $line_arr[2], 'post_content' => $html_split[0], 'post_index' => $line_arr[1]);
      array_push($ret_post, $tmp);
    }

    date_deal($ret_date, $ret_date_post, $line_arr); //日期處理
    tag_deal($tags, $ret_tag_count);   //統計標籤
  }
  $ret_all = array('category' => $category, 'dates_count' => $ret_date, "date_post" => $ret_date_post, 'tags' => $ret_tag_count,  'posts' => $ret_post);
  echo json_encode($ret_all);
}

function page($url_param)
{
  $count = count($url_param);

  if ($count == 2 || $url_param[2] == "") {
    get_index(1);
    return;
  }
  if (ctype_digit($url_param[2])) {
    get_index($url_param[2]);
  } else {
    echo "分頁參數錯誤!";
  }
}

function get_tag_index($tag, $index_page)
{
  $arr = get_contents(); //取得索引資料
  $category =  category_deal(); //文章分類功能
  $ret_post = array();
  $ret_tag_count = array();
  $ret_date = array();
  $ret_date_post = array();

  foreach ($arr as $val) {
    $line_arr = explode("|", $val);
    $tags = explode(",", trim($line_arr[3]));
    if (!file_exists("../contents/post_files/" . $line_arr[1])) continue;

    if (in_array($tag, $tags)) {
      if (!file_exists("../contents/post_files/" . $line_arr[1])) continue;
      $html = file_get_contents("../contents/post_files/" . $line_arr[1]);
      $html_split = explode("<!--more-->", $html);

      //檢查有沒有在特定分類內
      $in_category = check_category($category, $line_arr[1]);

      $tmp = array('post_category' => $in_category, 'post_tags' => $tags, 'post_time' => $line_arr[0], 'post_title' => $line_arr[2], 'post_content' => $html_split[0], 'post_index' => $line_arr[1]);
      array_push($ret_post, $tmp);
    }

    date_deal($ret_date, $ret_date_post, $line_arr); //日期處理
    tag_deal($tags, $ret_tag_count);   //統計標籤
  }
  $ret_all = array('category' => $category, 'dates_count' => $ret_date, "date_post" => $ret_date_post, 'tags' => $ret_tag_count,  'posts' => $ret_post);
  echo json_encode($ret_all);
}

function get_index($index_page)
{
  $arr = get_contents(); //取得索引資料
  $category =  category_deal(); //文章分類功能
  $ret_post = array();
  $ret_tag_count = array();
  $ret_date = array();
  $ret_date_post = array();

  foreach ($arr as $val) {
    $line_arr = explode("|", $val);
    $tags = explode(",", trim($line_arr[3]));
    if (!file_exists("../contents/post_files/" . $line_arr[1])) continue;
    $html = file_get_contents("../contents/post_files/" . $line_arr[1]);
    $html_split = explode("<!--more-->", $html);

    //檢查有沒有在特定分類內start
    $in_category = check_category($category, $line_arr[1]);

    $tmp = array('post_category' => $in_category, 'post_tags' => $tags, 'post_time' => $line_arr[0], 'post_title' => $line_arr[2], 'post_content' => $html_split[0], 'post_index' => $line_arr[1]);
    array_push($ret_post, $tmp);

    date_deal($ret_date, $ret_date_post, $line_arr); //日期處理
    tag_deal($tags, $ret_tag_count);   //統計標籤
  }

  $ret_all = array('category' => $category, 'dates_count' => $ret_date, "date_post" => $ret_date_post, 'tags' => $ret_tag_count,  'posts' => $ret_post);

  echo json_encode($ret_all);
}

function startsWith($string, $startString)
{
  $len = strlen($startString);
  return (substr($string, 0, $len) === $startString);
}

function del_by_value($array, $del_val)
{
  if (($key = array_search($del_val, $array)) !== false) {
    unset($array[$key]);
  }
  return $array;
}



/*
//統計標籤
function tag_deal(&$tags, &$ret_tag_count)
{
  foreach ($tags as $tag_val) {
    $ret_tag_count[$tag_val]++;
  }
}

//處理日期
function date_deal(&$ret_date, &$ret_date_post, &$line_arr)
{
  $date =  explode(" ", $line_arr[0])[0];
  $date_arr = explode("-", $date);
  $ret_date[$date_arr[0]]++;
  $ret_date[$date_arr[0] . $date_arr[1]]++;
  $tmp2 = array('title' => $line_arr[2], 'post_index' => $line_arr[1]);
  if ($ret_date_post[$date_arr[0] . $date_arr[1]]  == null) {
    $ret_date_post[$date_arr[0] . $date_arr[1]] = array();
  }
  array_push($ret_date_post[$date_arr[0] . $date_arr[1]], $tmp2);
}*/

// 統計標籤
function tag_deal(&$tags, &$ret_tag_count)
{
    foreach ($tags as $tag_val) {
        // 修改重點：如果 key 不存在，預設給 0，然後再 +1
        // 舊寫法: $ret_tag_count[$tag_val]++;
        
        // 新寫法 (PHP 7.0+):
        $ret_tag_count[$tag_val] = ($ret_tag_count[$tag_val] ?? 0) + 1;
        
        /* 如果您用的是很舊的 PHP 5，則要寫成:
           if (!isset($ret_tag_count[$tag_val])) {
               $ret_tag_count[$tag_val] = 0;
           }
           $ret_tag_count[$tag_val]++;
        */
    }
}

// 處理日期
function date_deal(&$ret_date, &$ret_date_post, &$line_arr)
{
    // 防呆：確保 line_arr 資料足夠，避免 index 0 不存在的錯誤
    if (!isset($line_arr[0])) return; 

    $date = explode(" ", $line_arr[0])[0];
    $date_arr = explode("-", $date);
    
    // 取得年份與月份 Key
    $yearKey = $date_arr[0];
    $ymKey   = $date_arr[0] . $date_arr[1];

    // 修改重點 1：計算年份數量 (修正 Undefined key)
    $ret_date[$yearKey] = ($ret_date[$yearKey] ?? 0) + 1;

    // 修改重點 2：計算年月數量 (修正 Undefined key)
    $ret_date[$ymKey] = ($ret_date[$ymKey] ?? 0) + 1;

    // 準備要塞入的資料
    $tmp2 = array('title' => $line_arr[2] ?? '', 'post_index' => $line_arr[1] ?? '');

    // 修改重點 3：初始化二維陣列
    // 舊寫法: if ($ret_date_post[$ymKey] == null) <- 這裡存取時就會報錯
    
    // 新寫法: 使用 isset 檢查
    if (!isset($ret_date_post[$ymKey])) {
        $ret_date_post[$ymKey] = array();
    }
    
    // 塞入資料
    array_push($ret_date_post[$ymKey], $tmp2);
}


//檢查有沒有在特定分類內
function check_category($category, $line)
{
  $in_category = array();
  $nameNoExt = str_replace(".html", "", $line);
  foreach ($category as $c) {
    if (in_array($line, $c['posts']) || in_array($nameNoExt, $c['posts'])) {
      array_push($in_category, $c['name']);
    }
  }
  return $in_category;
}

//文章分類
function category_deal()
{
  $category = array();
  $dirs = scandir("../category");
  $dirs = del_by_value($dirs, ".");
  $dirs = del_by_value($dirs, "..");
  foreach ($dirs as $dir) {
    if (!is_dir("../category/" . $dir)) continue; // 跳過非目錄的檔案 (例如 readme.md)
    
    $files = scandir("../category/" . $dir);
    $files = del_by_value($files, ".");
    $files = del_by_value($files, "..");

    // Filter out drafts (check if actual content file exists)
    $valid_files = [];
    foreach($files as $f) {
        if(file_exists("../contents/post_files/" . $f) || file_exists("../contents/post_files/" . $f . ".html")) {
            $valid_files[] = $f;
        }
    }

    array_push($category, array('name' => $dir, 'count' => count($valid_files), 'posts' => $valid_files));
  }
  return $category;
}

//取得索引資料
function get_contents()
{
  $index_file = "../contents/index_post.txt";
  $index = file_get_contents($index_file);
  $index  = str_replace("\r\n", "\n", $index);
  $arr = explode("\n", $index);
  return $arr;
}
