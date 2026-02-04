<?php

require_once __DIR__ . '/config.php';

require_once(__DIR__ . '/PHP_LIB/dindent/Indenter.php');
require_once(__DIR__ . '/PHP_LIB/dindent/Exception/DindentException.php');


use Gajus\Dindent\Indenter;

build();

/**
 * 從 HTML 中解析所有 <template> 標籤的內容 (Regex 版)
 */
function loadAllTemplates($content)
{
    $result = array();
    
    // 使用 Regex 抓取 <template id="...">...</template>
    // /s 修飾符讓 . 可以匹配換行符號
    // /i 忽略大小寫
    if (preg_match_all('/<template\s+id="([^"]+)"[^>]*>(.*?)<\/template>/is', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $id = $match[1];
            $innerHTML = $match[2]; // Regex 抓到的就是原始字串，不會被轉碼
            $result[$id] = $innerHTML;
        }
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

function endsWith($string, $endString)
{
    $len = strlen($endString);
    if ($len == 0) {
        return true;
    }
    return (substr($string, -$len) === $endString);
}

/**
 * 圖片優化回呼函式 (全域變數計數器)
 */
$g_img_counter = 0;
function _img_optimize_callback($matches) {
    global $g_img_counter;
    
    $full_tag = $matches[0];
    $attrs = $matches[1];

    // 移除舊的 loading 與 fetchpriority 屬性 (避免重複)
    // 支援雙引號與單引號
    $attrs = preg_replace('/\s+(loading|fetchpriority)\s*=\s*("[^"]*"|\'[^\']*")/i', '', $attrs);

    $new_attrs = '';
    if ($g_img_counter === 0) {
        // 第一張圖：Eager + High Priority
        $new_attrs = ' loading="eager" fetchpriority="high"';
    } else {
        // 其餘圖片：Lazy
        $new_attrs = ' loading="lazy"';
    }
    
    $g_img_counter++;

    // 重組 img 標籤
    return '<img ' . trim($attrs) . $new_attrs . '>';
}

/**
 * 針對 HTML 內容中的圖片進行載入優化 (Regex 版)
 */
function optimize_full_page_images($html)
{
    if (trim($html) === "") return "";

    // 重置計數器
    global $g_img_counter;
    $g_img_counter = 0;

    // 使用 preg_replace_callback 尋找所有 img 標籤
    // 假設 img 標籤格式為 <img ... >
    return preg_replace_callback('/<img\s+([^>]+)>/i', '_img_optimize_callback', $html);
}

/**
 * 移除 HTML 字串中的 <template> 標籤及其內容
 */
function remove_templates($html)
{
    return preg_replace('/<template\b[^>]*>.*?<\/template>\s*/is', '', $html);
}

/**
 * 修正 post/ 目錄下靜態檔案的資源路徑
 */
function fix_resource_paths_for_post_dir($html)
{
    $html = str_replace('href="static/', 'href="../static/', $html);
    $html = str_replace('src="static/', 'src="../static/', $html);
    
    $root_files = array(
        'config.js',
        'blog.css', 'blog.min.css',
        'favicon.ico', 'apple-touch-icon.png',
        'blog.html', 'blog_list.html'
    );
    
    foreach ($root_files as $file) {
        $html = str_replace('href="' . $file, 'href="../' . $file, $html);
        $html = str_replace('src="' . $file, 'src="../' . $file, $html);
    }
    
    $html = str_replace('content="preview/', 'content="../preview/', $html);
    $html = str_replace("href=\"' + themeFile", "href=\"../' + themeFile", $html);

    return $html;
}

/**
 * 核心渲染函數
 */
function render_page($template_html, $replace_map, $indenter, $is_post_dir = false, $remove_templates = true, $optimize_images = true) {
    
    // 1. 執行變數替換
    $result = str_replace(
        array_keys($replace_map),
        array_values($replace_map),
        $template_html
    );

    // 2. 移除 <template> 區塊 (選擇性)
    if ($remove_templates) {
        $result = remove_templates($result);
    }

    // 3. 修正資源路徑 (如果是 post/ 目錄下的檔案)
    if ($is_post_dir) {
        $result = fix_resource_paths_for_post_dir($result);
    }

    // 4. 圖片載入優化 (選擇性)
    if ($optimize_images) {
        $result = optimize_full_page_images($result);
        
        // 註：因為移除了 DOMDocument，所以不需要再處理 %7B%7B 轉碼問題了
        // Regex 處理字串時，不會改變原有的屬性編碼
    }

    // 5. 排版美化
    // 為了安全起見，如果 Indenter 內部用了 DOMDocument 且失敗，可以加 try-catch，但該庫主要是 regex base
    try {
        $result = $indenter->indent($result);
    } catch (Exception $e) {
        // 如果排版失敗，就輸出原始結果，不要讓程式掛掉
    }

    return $result;
}


function build()
{
    // 0. 準備工具
    $indenter = new Indenter();
    
    // 1. 讀取母樣板 (static/blog_template.html)
    $html_blog_template = file_get_contents("static/blog_template.html");
    
    // 2. 解析並提取其中的 <template> (現在使用 Regex)
    $templates = loadAllTemplates($html_blog_template);
    
    // 3. 準備通用變數 (Global)
    $global_map = array(
        '{{blog_title}}'       => $GLOBALS['blog_title'],
        '{{blog_description}}' => $GLOBALS['blog_description'],
        '{{blog_introduce}}'   => $GLOBALS['blog_introduce'],
        '{{site_url}}'         => $GLOBALS['site_url'],
    );

    // ============================================ 
    // A. 生成首頁 (blog.html)
    // ============================================ 
    $index_map = array_merge($global_map, array(
        '{{page_title}}'          => $GLOBALS['blog_title'],
        '{{page_canonical}}'      => $GLOBALS['site_url'] . 'blog.html',
        '{{page_description}}'    => $GLOBALS['blog_description'],
        '{{page_og_title}}'       => $GLOBALS['blog_title'],
        '{{page_og_description}}' => $GLOBALS['blog_description'],
        '{{page_og_image}}'       => $GLOBALS['blog_preview'], 
        '{{page_og_url}}'         => $GLOBALS['site_url'] . 'blog.html',
        '{{page_twitter_card}}'   => 'summary_large_image',
        '{{body_class}}'          => '',
        '{{page_main_content}}'   => '', 
    ));
    
    // 對於 blog.html，我們仍然保留模板 ($remove_templates = false)
    // 但因為現在使用 Regex 優化圖片，不會破壞模板結構，所以 $optimize_images 可以設為 true (或 false 也可以，因為首頁主內容是空的)
    // 為了保險起見，首頁通常不需要針對空內容做圖片優化，維持 false 即可
    $index_html = render_page($html_blog_template, $index_map, $indenter, false, false, false);
    file_put_contents("blog.html", $index_html);
    echo "blog.html render ok!<br>\r\n";
    $blog_template_mtime = filemtime("static/blog_template.html");


    // ============================================ 
    // B. 準備文章列表與分類
    // ============================================ 
    $index_file = "contents/index_post.txt";
    $index_content = file_get_contents($index_file);
    $index_content = str_replace("\r\n", "\n", $index_content);
    $arr = explode("\n", $index_content);
    
    if (!is_dir("post")) mkdir("post", 0755, true);

    $category = array();
    if (is_dir("category")) {
        $dirs = scandir("category");
        $dirs = del_by_value($dirs, ".");
        $dirs = del_by_value($dirs, "..");
        foreach ($dirs as $dir) {
            $full_path = "category/" . $dir;
            if (!is_dir($full_path)) continue;
            $files = scandir("category/" . $dir);
            $files = del_by_value($files, ".");
            $files = del_by_value($files, "..");
            array_push($category, array('name' => $dir, 'count' => count($files), 'posts' => $files));
        }
    }

    $all_posts_list_html = "";

    // ============================================ 
    // C. 生成各單篇 Post (post/xxx.html)
    // ============================================ 
    foreach ($arr as $val) {
        if (trim($val) == "") continue;
        $line_arr = explode("|", $val);
        
        $post_filename = $line_arr[1];
        if (!file_exists("contents/post_files/" . $post_filename)) {
            echo $post_filename . " skipped (draft or missing).<br>\r\n";
            continue;
        }

        $post_target_path = "post/" . $post_filename;

        // 列表項目
        $listItem = $templates['tmpl_blog_list_item'];
        $listItem = str_replace('{{link}}', "post/" . $post_filename, $listItem);
        $listItem = str_replace('{{time}}', $line_arr[0], $listItem);
        $listItem = str_replace('{{title}}', $line_arr[2], $listItem);
        $all_posts_list_html .= $listItem . "\r\n";

        // 快取檢查
        if (file_exists($post_target_path)) {
            $org_mtime = filemtime("contents/post_files/" . $post_filename);
            $cached_mtime = filemtime($post_target_path);
            if ($org_mtime < $cached_mtime && $blog_template_mtime < $cached_mtime) {
                echo $post_target_path . " cached.<br>\r\n";
                continue;
            }
        }

        $post_html_content = file_get_contents("contents/post_files/" . $post_filename);
        $description_exist = (count($line_arr) == 5);
        $post_desc = $description_exist ? $line_arr[4] : "";
        
        $icon_name = "icon-" . str_replace(".html", ".jpg", $post_filename);
        $icon_exist = file_exists(__DIR__ . "/preview/" . $icon_name);
        $post_image = $icon_exist ? ($GLOBALS['site_url'] . 'preview/' . $icon_name) : "";

        // 組裝文章內容
        // [Tags]
        $tags = explode(",", trim($line_arr[3]));
        $tagsInnerHtml = "";
        if (count($tags) > 1 || $tags[0] != "") {
            foreach ($tags as $tag_item) {
                if (trim($tag_item) == "") continue;
                $t = str_replace('{{name}}', $tag_item, $templates['tmpl_post_tag_item']);
                $tagsInnerHtml .= $t;
            }
        }
        $tagsBlockHtml = ($tagsInnerHtml !== "") ? str_replace('{{items}}', $tagsInnerHtml, $templates['tmpl_post_tag_container']) : "";

        // [Category]
        $in_category = array();
        foreach ($category as $c) {
            if (in_array(substr($post_filename, 0, 14), $c['posts'])) {
                array_push($in_category, $c['name']);
            }
        }
        $catsInnerHtml = "";
        foreach ($in_category as $category_item) {
            $c = str_replace('{{name}}', $category_item, $templates['tmpl_post_cat_item']);
            $catsInnerHtml .= $c;
        }
        $catsBlockHtml = ($catsInnerHtml !== "") ? str_replace('{{items}}', $catsInnerHtml, $templates['tmpl_post_cat_container']) : "";

        // [Main Post Content]
        $postHtml = $templates['tmpl_post_main'];
        $postHtml = str_replace('{{time}}', $line_arr[0], $postHtml);
        $postHtml = str_replace('{{title}}', $line_arr[2], $postHtml);
        $postHtml = str_replace('{{link}}', $post_filename, $postHtml);
        $postHtml = str_replace('{{content}}', $post_html_content, $postHtml);
        $postHtml = str_replace('{{tags_block}}', $tagsBlockHtml, $postHtml);
        $postHtml = str_replace('{{category_block}}', $catsBlockHtml, $postHtml);

        // 準備 Post Map
        $post_map = array_merge($global_map, array(
            '{{page_title}}'          => $GLOBALS['blog_title'] . "-" . $line_arr[2],
            '{{page_canonical}}'      => $GLOBALS['site_url'] . 'post/' . $post_filename,
            '{{page_description}}'    => $post_desc,
            '{{page_og_title}}'       => $line_arr[2],
            '{{page_og_description}}' => $post_desc,
            '{{page_og_image}}'       => $post_image, 
            '{{page_og_url}}'         => $GLOBALS['site_url'] . 'post/' . $post_filename,
            '{{page_twitter_card}}'   => $icon_exist ? 'summary_large_image' : '', 
            '{{body_class}}'          => 'is-single-page',
            '{{page_main_content}}'   => $postHtml,
        ));

        $render = render_page($html_blog_template, $post_map, $indenter, true, true, true);
        file_put_contents($post_target_path, $render);
        echo $post_target_path . " render ok!<br>\r\n";
    }

    // ============================================ 
    // D. 生成文章總列表 (blog_list.html)
    // ============================================ 
    $listHtml = $templates['tmpl_blog_list_container'];
    $listHtml = str_replace('{{items}}', $all_posts_list_html, $listHtml);

    $list_map = array_merge($global_map, array(
        '{{page_title}}'          => $GLOBALS['blog_title'] . "-文章總列表",
        '{{page_canonical}}'      => $GLOBALS['site_url'] . 'blog_list.html',
        '{{page_description}}'    => '',
        '{{page_og_title}}'       => $GLOBALS['blog_title'] . "-文章總列表",
        '{{page_og_description}}' => '',
        '{{page_og_image}}'       => '',
        '{{page_og_url}}'         => $GLOBALS['site_url'] . 'blog_list.html',
        '{{page_twitter_card}}'   => '',
        '{{body_class}}'          => '', 
        '{{page_main_content}}'   => $listHtml,
    ));

    $list_render = render_page($html_blog_template, $list_map, $indenter, false, true, false);
    file_put_contents("blog_list.html", $list_render);
    echo "blog_list.html render ok!<br>\r\n";


    // ============================================ 
    // E. 生成 Sitemap
    // ============================================ 
    date_default_timezone_set('Asia/Taipei');
    $site_path = $GLOBALS['site_url'];
    $site_map_begin = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    $site_map_render = "";
    
    $files = scandir("post");
    $files = del_by_value($files, ".");
    $files = del_by_value($files, "..");
    
    $add_list = array("blog.html", "blog_list.html");
    
    foreach ($add_list as $file) {
        if (file_exists($file)) {
             $last_m = date("c", filemtime($file));
             $site_map_render .= "\n    <url>\n        <loc>$site_path$file</loc>\n        <lastmod>$last_m</lastmod>\n    </url>";
        }
    }

    foreach ($files as $file) {
        if (endsWith($file, ".html")) {
            $full_path = "post/" . $file;
            $last_m = date("c", filemtime($full_path));
            $site_map_render .= "\n    <url>\n        <loc>$site_path" . "post/$file</loc>\n        <lastmod>$last_m</lastmod>\n    </url>";
        }
    }
    
    $site_map_render = $site_map_begin . $site_map_render . "\n</urlset>";
    file_put_contents("sitemap.xml", $site_map_render);
    echo "sitemap.xml render ok!<br>\r\n";
}
