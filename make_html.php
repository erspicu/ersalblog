<?php
require_once(__DIR__ . "/PHP_LIB/html2text/Html2Text.php");
require_once(__DIR__ . "/PHP_LIB/html2text/Html2TextException.php");

require_once(__DIR__ . '/PHP_LIB/dindent/Indenter.php');
require_once(__DIR__ . '/PHP_LIB/dindent/Exception/DindentException.php');


use Gajus\Dindent\Indenter;
use Soundasleep\Html2Text;

build();


function loadAllTemplates($content)
{
    $result = [];
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);

    // 強制 UTF-8
    $dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $content);
    libxml_clear_errors();

    $templates = $dom->getElementsByTagName('template');

    foreach ($templates as $node) {
        $id = $node->getAttribute('id');
        if (empty($id)) continue;

        $innerHTML = "";
        foreach ($node->childNodes as $child) {
            $innerHTML .= $dom->saveHTML($child);
        }

        // 【關鍵修正】
        // DOMDocument 會把 href="{{link}}" 轉成 href="%7B%7Blink%7D%7D"
        // 我們要手動把它轉回來，這樣後面的 str_replace 才能生效
        $innerHTML = str_replace(
            ['%7B%7B', '%7D%7D'],
            ['{{', '}}'],
            $innerHTML
        );

        $result[$id] = $innerHTML;
    }

    return $result;
}


function del_by_value($array, $del_val)
{
    if (($key = array_search($del_val, $array)) !== false) {
        unset($array[$key]);
    }
    return $array;
}

function startsWith($string, $startString)
{
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}

function endsWith($string, $endString)
{
    $len = strlen($endString);
    if ($len == 0) {
        return true;
    }
    return (substr($string, -$len) === $endString);
}

function str_has($haystack, $needle)
{
    // 1. 如果是 PHP 8.0 以上，直接使用原生函數 (效能最好)
    if (function_exists('str_contains')) {
        return str_contains($haystack, $needle);
    }

    // 2. 舊版 PHP 的處理方式
    // 如果 $needle 是空字串，根據 PHP 8 標準應回傳 true
    if ($needle === '') {
        return true;
    }

    // 3. 使用 strpos 判斷
    // 重要：必須使用 !== false，因為如果目標在「第0個位置」，strpos 會回傳 0
    return strpos($haystack, $needle) !== false;
}

/**
 * 針對 HTML 內容中的圖片進行載入優化 (PHP 版)
 * 邏輯：第一張圖 Eager (高優先)，其餘 Lazy (懶載入)
 * @param string $html 原始 HTML
 * @return string 優化後的 HTML
 */
function optimize_content_images_php($html)
{
    if (trim($html) === "") return "";

    $dom = new DOMDocument();
    // 抑制 HTML5 標籤警告
    libxml_use_internal_errors(true);

    // 技巧：包一層 <div> 並加上 UTF-8 meta，確保片段解析正確且不亂碼
    $html_utf8 = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><div>' . $html . '</div>';

    //載入 HTML (參數：不自動補全 DOCTYPE 或 html/body)
    $dom->loadHTML($html_utf8, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // 抓出所有圖片
    $images = $dom->getElementsByTagName('img');
    $index = 0;

    foreach ($images as $img) {
        if ($index === 0) {
            // 第一張圖：立即載入 + 高優先權
            $img->setAttribute('loading', 'eager');
            $img->setAttribute('fetchpriority', 'high');
        } else {
            // 其餘圖片：懶載入
            $img->setAttribute('loading', 'lazy');
            // 移除可能存在的 high priority (防呆)
            $img->removeAttribute('fetchpriority');
        }
        $index++;
    }

    // 輸出處理後的 HTML (只抓取 wrapper div 裡面的內容)
    $container = $dom->getElementsByTagName('div')->item(0);
    $output = "";
    if ($container) {
        foreach ($container->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }
    }

    return $output;
}

/**
 * 處理最終 HTML 輸出：
 * 1. 修正 UTF-8 編碼
 * 2. 圖片 LCP/Lazy Loading 優化
 * 3. 格式化排版 (Beautify)
 * * @param string $html 完整的 HTML 頁面內容
 * @return string 處理後的 HTML
 */
function finalize_html_output($html)
{
    if (trim($html) === "") return "";

    $dom = new DOMDocument();

    // 【排版關鍵設定】
    // preserveWhiteSpace = false : 移除原始多餘空白 (重新計算縮排的前提)
    // formatOutput = true : 開啟美化輸出
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    // 抑制 HTML5 標籤警告
    libxml_use_internal_errors(true);



    // 載入 HTML
    // LIBXML_HTML_NODEFDTD: 不自動添加 DOCTYPE (因為我們原本的 HTML 可能已經有了，或者我們想自己控制)
    $dom->loadHTML($html, LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // -------------------------------------------------
    // 2. 圖片優化邏輯 (Image Optimization)
    // -------------------------------------------------
    $images = $dom->getElementsByTagName('img');
    $index = 0;

    foreach ($images as $img) {
        if ($index === 0) {
            // 第一張圖 (LCP)：立即載入 + 高優先權
            $img->setAttribute('loading', 'eager');
            $img->setAttribute('fetchpriority', 'high');
        } else {
            // 其餘圖片：懶載入
            $img->setAttribute('loading', 'lazy');
            $img->removeAttribute('fetchpriority');
        }
        $index++;
    }

    // -------------------------------------------------
    // 3. 輸出與清理
    // -------------------------------------------------
    $output = $dom->saveHTML();
    return $output;
}

/**
 * 移除 HTML 字串中的 <template> 標籤及其內容，並清理留下的空白
 * @param string $html
 * @return string
 */
function remove_templates($html)
{
    // 修正重點：
    // 1. 移除開頭的 ^ 與 \s* : 不管 template 前面有沒有東西，都要抓出來刪掉。
    // 2. 移除 m 修飾符 : 我們不需要逐行對位，只需要全域掃描。
    // 3. 保留尾部的 \s* : 這會自動吃掉 template 標籤「後面」緊接著的換行與空白，
    //    這樣刪除後就不會留下一行全白的空行。

    return preg_replace('/<template\b[^>]*>.*?<\/template>\s*/is', '', $html);
}
function build()
{
    $index_file = "contents/index_post.txt";
    $index = file_get_contents($index_file);
    $index = str_replace("\r\n", "\n", $index);
    $arr = explode("\n", $index);

    $html_template = file_get_contents("blog.html");

    // 1. 解析全部樣版 (Key => HTML)
    $templates = loadAllTemplates($html_template);

    // 分割 Header 與 Footer
    $html_template_parts = explode('<!--post_load-->', $html_template);
    $begin_html_line_arr = preg_split('/\r\n|\r|\n/', $html_template_parts[0]);

    $indenter = new Indenter();
    $blog_template_mtime = filemtime("blog.html");

    // --- 文章分類功能準備 ---
    $category = array();
    if (is_dir("category")) {
        $dirs = scandir("category");
        $dirs = del_by_value($dirs, ".");
        $dirs = del_by_value($dirs, "..");
        foreach ($dirs as $dir) {
            $files = scandir("category/" . $dir);
            $files = del_by_value($files, ".");
            $files = del_by_value($files, "..");
            array_push($category, array('name' => $dir, 'count' => count($files), 'posts' => $files));
        }
    }

    // 用來累積「文章總列表」的 HTML 字串
    $all_posts_list_html = "";

    foreach ($arr as $val) {
        if (trim($val) == "") continue;

        $line_arr = explode("|", $val);
        // $line_arr: [0]時間, [1]檔名, [2]標題, [3]標籤, [4]描述

        // ============================================
        // 1. 生成「文章總列表」的單一項目 (不再 Hardcode)
        // ============================================
        $listItem = $templates['tmpl_blog_list_item'];
        $listItem = str_replace('{{link}}', $line_arr[1], $listItem);
        $listItem = str_replace('{{time}}', $line_arr[0], $listItem);
        $listItem = str_replace('{{title}}', $line_arr[2], $listItem);
        $all_posts_list_html .= $listItem . "\r\n";


        // ============================================
        // 2. 快取檢查 (Cache Check)
        // ============================================
        if (file_exists($line_arr[1])) {
            $org_mtime = file_exists("contents/post_files/" . $line_arr[1]) ? filemtime("contents/post_files/" . $line_arr[1]) : 0;
            $cached_mtime = filemtime($line_arr[1]);

            if ($org_mtime < $cached_mtime && $blog_template_mtime < $cached_mtime) {
                echo $line_arr[1] . " cached.<br>\r\n";
                continue;
            }
        }

        // 讀取文章原始 HTML
        $post_html_content = file_exists("contents/post_files/" . $line_arr[1]) ? file_get_contents("contents/post_files/" . $line_arr[1]) : "";


        // ============================================
        // 3. 處理 Meta Tags (Header)
        // ============================================
        $parts_array = array_merge($begin_html_line_arr);
        $description_exist = (count($line_arr) == 5);
        $icon_name = "icon-" . str_replace(".html", ".jpg", $line_arr[1]);
        $icon_exist = file_exists(__DIR__ . "/preview/" . $icon_name);

        foreach ($parts_array as $key => $item) {
            // 檢查是否匹配 (這裡示範完全相同)
            if (str_has($item, '<!--title-->')) {
                $parts_array[$key] = "	<title>" . "Baxermux的攝影Blog" . "-" . $line_arr[2] . "</title>";
            } else if (str_has($item, '<!--og:title-->')) {
                $parts_array[$key] = '	<meta property="og:title" content="' . $line_arr[2] . '" />';
            } else if (str_has($item, '<!--description-->')) {
                if ($description_exist) {
                    $parts_array[$key] = '	<meta name="description" content="' . $line_arr[4] . '">';
                } else {
                    $parts_array[$key] = "";
                }
            } else if (str_has($item, '<!--og:description-->')) {

                if ($description_exist) {
                    $parts_array[$key] = '	<meta property="og:description" content="' . $line_arr[4] . '">';
                } else {
                    $parts_array[$key] = "";
                }
            } else if (str_has($item, '<!--og:url-->')) {
                $parts_array[$key] = '	<meta property="og:url" content="https://www.baxermux.org/ersalblog/' . $line_arr[1] . '" />';
            } else if (str_has($item, '<!--og:image-->')) {
                if ($icon_exist) {
                    $parts_array[$key] = '	<meta property="og:image" content="https://www.baxermux.org/ersalblog/preview/' . $icon_name . '" />';
                } else {
                    $parts_array[$key] = "";
                }
            } else if (str_has($item, '<!--twitter:card-->')) {
                if ($icon_exist) {
                    $parts_array[$key] = '	<meta name="twitter:card" content="summary_large_image" />';
                } else {
                    $parts_array[$key] = "";
                }
            } else if (str_has($item, '<!--canonical-->')) {
                $parts_array[$key] = '	<link rel="canonical" href="https://www.baxermux.org/ersalblog/' . $line_arr[1] . '" />';
            }
        }
        $begin_html = implode("\r\n", $parts_array);

        // 【關鍵修改】
        // 替換 <body> 標籤，加上 is-single-page class
        // 這樣 CSS 就知道現在是「內文頁」，會自動顯示「回到最上層」並隱藏「繼續閱讀」
        $begin_html = str_replace('<body>', '<body class="is-single-page">', $begin_html);


        // ============================================
        // 4. 內文組裝 (全樣板化)
        // ============================================

        // [A] 處理標籤
        $tags = explode(",", trim($line_arr[3]));
        $tagsInnerHtml = "";
        if (count($tags) > 1 || $tags[0] != "") {
            foreach ($tags as $tag_item) {
                if (trim($tag_item) == "") continue;
                $t = $templates['tmpl_post_tag_item'];
                $t = str_replace('{{name}}', $tag_item, $t);
                $tagsInnerHtml .= $t;
            }
        }
        $tagsBlockHtml = "";
        if ($tagsInnerHtml !== "") {
            $tagsBlockHtml = str_replace('{{items}}', $tagsInnerHtml, $templates['tmpl_post_tag_container']);
        }

        // [B] 處理分類
        $in_category = array();
        foreach ($category as $c) {
            if (in_array(substr($line_arr[1], 0, 14), $c['posts'])) {
                array_push($in_category, $c['name']);
            }
        }
        $catsInnerHtml = "";
        foreach ($in_category as $category_item) {
            $c = $templates['tmpl_post_cat_item'];
            $c = str_replace('{{name}}', $category_item, $c);
            $catsInnerHtml .= $c;
        }
        $catsBlockHtml = "";
        if ($catsInnerHtml !== "") {
            $catsBlockHtml = str_replace('{{items}}', $catsInnerHtml, $templates['tmpl_post_cat_container']);
        }

        // [C] 處理主文章
        $postHtml = $templates['tmpl_post_main'];
        $postHtml = str_replace('{{time}}', $line_arr[0], $postHtml);
        $postHtml = str_replace('{{title}}', $line_arr[2], $postHtml);
        $postHtml = str_replace('{{link}}', $line_arr[1], $postHtml);
        $postHtml = str_replace('{{content}}', $post_html_content, $postHtml);
        $postHtml = str_replace('{{tags_block}}', $tagsBlockHtml, $postHtml);
        $postHtml = str_replace('{{category_block}}', $catsBlockHtml, $postHtml);
        // [D] 寫入單頁檔案
        $render = $begin_html . "\r\n" . $postHtml . "\r\n" . $html_template_parts[1];
        $render = remove_templates($render);
        $render = '<!doctype html><html  lang="zh-hant">' . optimize_content_images_php($render) . "</html>";
        $render = $indenter->indent($render);
        file_put_contents($line_arr[1], $render);
        echo $line_arr[1] . " render ok!<br>\r\n";
    }


    // ============================================
    // 5. 產生「文章總列表」頁面 (blog_list.html)
    // ============================================
    // [A] 處理 Meta Tags (為列表頁客製化)
    $parts_array = array_merge($begin_html_line_arr);
    foreach ($parts_array as $key => $item) {
        if (str_has($item, '<!--title-->')) {
            $parts_array[$key] = "	<title>" . "Baxermux的攝影Blog-文章總列表</title>";
        } else if (str_has($item, '<!--og:title-->')) {
            $parts_array[$key] = '	<meta property="og:title" content="Baxermux的攝影Blog-文章總列表" />';
        } else if (str_has($item, '<!--description-->')) {
            $parts_array[$key] = "";
        } else if (str_has($item, '<!--og:description-->')) {
            $parts_array[$key] = "";
        } else if (str_has($item, '<!--og:url-->')) {
            $parts_array[$key] = '	<meta property="og:url" content="https://www.baxermux.org/ersalblog/blog_list.html" />';
        } else if (str_has($item, '<!--og:image-->')) {
            $parts_array[$key] = "";
        } else if (str_has($item, '<!--twitter:card-->')) {
            if ($icon_exist) {
                $parts_array[$key] = '	<meta name="twitter:card" content="summary_large_image" />';
            } else {
                $parts_array[$key] = "";
            }
        } else if (str_has($item, '<!--canonical-->')) {
            $parts_array[$key] = '	<link rel="canonical" href="https://www.baxermux.org/ersalblog/blog_list.html" />';
        }
    }

    $begin_html_list = implode("\r\n", $parts_array);

    // [B] 使用新的容器樣板 (tmpl_blog_list_container)
    $listHtml = $templates['tmpl_blog_list_container'];
    // 將剛剛迴圈裡累積的所有文章連結 ($all_posts_list_html) 塞進去
    $listHtml = str_replace('{{items}}', $all_posts_list_html, $listHtml);

    // [C] 寫入列表檔案
    $blog_list_render = $begin_html_list . "\r\n" . $listHtml . "\r\n" . $html_template_parts[1];
    $render = $indenter->indent(remove_templates($blog_list_render));
    file_put_contents("blog_list.html", $blog_list_render);
    echo "blog_list.html render ok!<br>\r\n";

    // ============================================
    // 6. 處理 Sitemap (保持不變)
    // ============================================
    date_default_timezone_set('Asia/Taipei');
    $site_path = "https://www.baxermux.org/ersalblog/";
    $site_map_begin = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    $site_map_render = "";
    $files = scandir(".");
    $files = del_by_value($files, ".");
    $files = del_by_value($files, "..");
    $add_list = array("blog.html", "blog_list.html");
    foreach ($files as $file) {
        if (endsWith($file, ".html") || in_array($file, $add_list)) {
            $last_m = date("c", filemtime($file));
            $site_map_render .= "\n    <url>\n        <loc>$site_path$file</loc>\n        <lastmod>$last_m</lastmod>\n    </url>";
        }
    }
    $site_map_render = $site_map_begin . $site_map_render . "\n</urlset>";
    file_put_contents("sitemap.xml", $site_map_render);
    echo "sitemap.xml render ok!<br>\r\n";
}
