<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PHP_LIB/dindent/Indenter.php';
require_once __DIR__ . '/PHP_LIB/dindent/Exception/DindentException.php';
require_once __DIR__ . '/PHP_LIB/TemplateManager.php';

use Gajus\Dindent\Indenter;

// --- Config & Language Init ---
global $blog_lang; 
if (!isset($blog_lang)) $blog_lang = 'zh_TW'; 

// Load Language
$langFile = __DIR__ . "/langs/template/template-{$blog_lang}.php";
if (!file_exists($langFile)) $langFile = __DIR__ . "/langs/template/template-zh_TW.php";
$langData = file_exists($langFile) ? require $langFile : [];

// Prefix keys
$langVars = [];
foreach ($langData as $k => $v) {
    $langVars["lang_{$k}"] = $v;
}
// ------------------------------

// 檢查是否強制重產
$isForce = in_array('-f', $argv) || in_array('--force', $argv);

// 執行建置
build($isForce);

function build($force = false) {
    global $langVars; // Access global lang vars
    $indenter = new Indenter();
    $tpl = new TemplateManager();
    $templatePath = "static/blog_template.html";
    $tpl->load($templatePath);

    // 準備全域變數
    $globalVars = array_merge($langVars, array(
        'blog_title'       => $GLOBALS['blog_title'],
        'blog_description' => $GLOBALS['blog_description'],
        'blog_introduce'   => $GLOBALS['blog_introduce'],
        'site_url'         => $GLOBALS['site_url'],
    ));

    // 準備資料來源
    $indexFile = "contents/index_post.txt";
    $posts = loadPosts($indexFile);
    $categories = scanCategories("category");

    // ==========================================
    // A. 生成首頁 (blog.html)
    // ==========================================
    $targetBlog = "blog.html";
    // 首頁依賴: 樣板檔 + 設定檔 (若標題變更需重產)
    if ($force || !checkCache($targetBlog, array($templatePath, __DIR__ . '/config.php'))) {
        $indexVars = array_merge($globalVars, array(
            'page_title'          => $GLOBALS['blog_title'],
            'page_canonical'      => $GLOBALS['site_url'] . 'blog.html',
            'page_description'    => $GLOBALS['blog_description'],
            'page_og_title'       => $GLOBALS['blog_title'],
            'page_og_description' => $GLOBALS['blog_description'],
            'page_og_image'       => $GLOBALS['blog_preview'],
            'page_og_url'         => $GLOBALS['site_url'] . 'blog.html',
            'page_twitter_card'   => 'summary_large_image',
            'body_class'          => '',
            'page_main_content'   => '', 
        ));

        $html = $tpl->render($tpl->getSource(), $indexVars);
        $html = pipeline($html, $indenter, false, false, false);
        write($targetBlog, $html);
    } else {
        echo "$targetBlog cached (skipped).<br>\r\n";
    }


    // ==========================================
    // B. 生成單篇文章 (post/*.html)
    // ==========================================
    if (!is_dir("post")) mkdir("post", 0755, true);
    
    $listItemsHtml = "";

    foreach ($posts as $post) {
        if (!$post['isValid']) {
            // echo $post['filename'] . " skipped (draft/missing).<br>\r\n"; // 減少雜訊
            continue;
        }

        // 列表項目渲染 (列表頁總是需要這些資料，所以不能跳過這段字串生成)
        // 注意：這裡只生成字串，不耗費太多效能
        $listItemsHtml .= $tpl->render($tpl->getSubTemplate('tmpl_blog_list_item'), array(
            'link'  => "post/" . $post['filename'],
            'time'  => $post['date'],
            'title' => $post['title']
        ));

        // 檢查單篇文章快取
        $targetPost = "post/" . $post['filename'];
        $sourcePost = "contents/post_files/" . $post['filename'];
        
        // 依賴: 原始文章 + 樣板 + 設定檔
        if ($force || !checkCache($targetPost, array($sourcePost, $templatePath, __DIR__ . '/config.php'))) {
            
            // 只有需要重產時才進行這些較重的運算
            $tagsHtml = $tpl->renderList('tmpl_post_tag_item', prepareTags($post['tags']));
            $tagsBlock = $tagsHtml ? $tpl->render($tpl->getSubTemplate('tmpl_post_tag_container'), array_merge($globalVars, array('items' => $tagsHtml))) : '';

            $catsHtml = $tpl->renderList('tmpl_post_cat_item', matchCategories($post['filename'], $categories));
            $catsBlock = $catsHtml ? $tpl->render($tpl->getSubTemplate('tmpl_post_cat_container'), array_merge($globalVars, array('items' => $catsHtml))) : '';

            $postContentHtml = $tpl->render($tpl->getSubTemplate('tmpl_post_main'), array(
                'time'           => $post['date'],
                'title'          => $post['title'],
                'link'           => $post['filename'],
                'content'        => $post['content'],
                'tags_block'     => $tagsBlock,
                'category_block' => $catsBlock
            ));

            $pageVars = array_merge($globalVars, array(
                'page_title'          => $GLOBALS['blog_title'] . "-" . $post['title'],
                'page_canonical'      => $GLOBALS['site_url'] . 'post/' . $post['filename'],
                'page_description'    => $post['description'],
                'page_og_title'       => $post['title'],
                'page_og_description' => $post['description'],
                'page_og_image'       => $post['og_image'],
                'page_og_url'         => $GLOBALS['site_url'] . 'post/' . $post['filename'],
                'page_twitter_card'   => $post['has_icon'] ? 'summary_large_image' : '',
                'body_class'          => 'is-single-page',
                'page_main_content'   => $postContentHtml
            ));

            $html = $tpl->render($tpl->getSource(), $pageVars);
            $html = pipeline($html, $indenter, true, true, true);
            write($targetPost, $html);
        } else {
            // echo "$targetPost cached.<br>\r\n"; // 減少雜訊，可視需求開啟
        }
    }


    // ==========================================
    // C. 生成文章總列表 (blog_list.html)
    // ==========================================
    $targetList = "blog_list.html";
    // 依賴: 索引檔 + 樣板 + 設定檔
    if ($force || !checkCache($targetList, array($indexFile, $templatePath, __DIR__ . '/config.php'))) {
        $listContentHtml = $tpl->render($tpl->getSubTemplate('tmpl_blog_list_container'), array(
            'items' => $listItemsHtml
        ));

        $listVars = array_merge($globalVars, array(
            'page_title'          => $GLOBALS['blog_title'] . "-文章總列表",
            'page_canonical'      => $GLOBALS['site_url'] . 'blog_list.html',
            'page_description'    => '',
            'page_og_title'       => $GLOBALS['blog_title'] . "-文章總列表",
            'page_og_description' => '',
            'page_og_image'       => '',
            'page_og_url'         => $GLOBALS['site_url'] . 'blog_list.html',
            'page_twitter_card'   => '',
            'body_class'          => '',
            'page_main_content'   => $listContentHtml
        ));

        $html = $tpl->render($tpl->getSource(), $listVars);
        $html = pipeline($html, $indenter, false, true, false); 
        write($targetList, $html);
    } else {
        echo "$targetList cached (skipped).<br>\r\n";
    }

    // ==========================================
    // D. 生成 Sitemap
    // ==========================================
    generateSitemap($force, $indexFile);
}


// --- 輔助函式區 (Helpers) ---

/**
 * 檢查快取是否有效
 * @param string $targetFile 目標檔案路徑
 * @param array $dependencies 依賴檔案路徑列表
 * @return boolean true=有效(不用重產), false=無效(需重產)
 */
function checkCache($targetFile, $dependencies) {
    if (!file_exists($targetFile)) {
        return false;
    }
    
    $targetTime = filemtime($targetFile);
    
    foreach ($dependencies as $dep) {
        if (file_exists($dep)) {
            if (filemtime($dep) > $targetTime) {
                return false; // 依賴檔比目標新 -> 失效
            }
        }
    }
    
    return true; // 所有依賴都比目標舊 -> 有效
}

function pipeline($html, $indenter, $fixPaths, $removeTemplates, $optimizeImages) {
    if ($removeTemplates) {
        $html = preg_replace('/<template\b[^>]*>.*?<\/template>\s*/is', '', $html);
    }
    if ($fixPaths) {
        $html = fix_resource_paths($html);
    }
    if ($optimizeImages) {
        $html = optimize_images($html);
    }
    // try {
    //     $html = $indenter->indent($html);
    // } catch (Exception $e) {}
    return $html;
}

function write($path, $content) {
    file_put_contents($path, $content);
    echo "$path render ok!<br>\r\n";
}

function loadPosts($indexFile) {
    $content = file_get_contents($indexFile);
    $content = str_replace("\r\n", "\n", $content);
    $lines = explode("\n", $content);
    $posts = array();

    foreach ($lines as $line) {
        if (trim($line) == "") continue;
        $parts = explode("|", $line);
        $filename = $parts[1];
        $sourcePath = "contents/post_files/" . $filename;
        
        if (!file_exists($sourcePath)) {
            $posts[] = array('isValid' => false, 'filename' => $filename);
            continue;
        }

        $iconName = "icon-" . str_replace(".html", ".jpg", $filename);
        $hasIcon = file_exists(__DIR__ . "/preview/" . $iconName);

        $posts[] = array(
            'isValid'     => true,
            'date'        => $parts[0],
            'filename'    => $filename,
            'title'       => $parts[2],
            'tags'        => explode(",", trim($parts[3])),
            'description' => isset($parts[4]) ? $parts[4] : '',
            'content'     => file_get_contents($sourcePath),
            'has_icon'    => $hasIcon,
            'og_image'    => $hasIcon ? ($GLOBALS['site_url'] . 'preview/' . $iconName) : ''
        );
    }
    return $posts;
}

function prepareTags($tagsArray) {
    $data = array();
    foreach ($tagsArray as $tag) {
        if (trim($tag) != "") $data[] = array('name' => $tag);
    }
    return $data;
}

function scanCategories($dir) {
    $cats = array();
    if (is_dir($dir)) {
        $subdirs = scandir($dir);
        foreach ($subdirs as $d) {
            if ($d == '.' || $d == '..') continue;
            if (is_dir("$dir/$d")) {
                $files = scandir("$dir/$d");
                $validFiles = array();
                foreach($files as $f) {
                    if($f != '.' && $f != '..') $validFiles[] = $f;
                }
                $cats[] = array('name' => $d, 'posts' => $validFiles);
            }
        }
    }
    return $cats;
}

function matchCategories($filename, $categories) {
    // Logic from api_filebase.php: check both full filename and filename without .html
    $nameNoExt = str_replace(".html", "", $filename);
    $matched = array();
    
    foreach ($categories as $cat) {
        // api_filebase uses: in_array($nameNoExt, $category_post_index)
        // But since we are static generating, we should check against what scanCategories returns.
        // scanCategories returns filenames present in the category directory.
        // The old logic in make_html.php used a 14-char prefix which was brittle.
        // Now we check exact match for robustness.
        
        if (in_array($filename, $cat['posts']) || in_array($nameNoExt, $cat['posts'])) {
             $matched[] = array('name' => $cat['name']);
        }
    }
    return $matched;
}

function fix_resource_paths($html) {
    $html = str_replace('href="static/', 'href="../static/', $html);
    $html = str_replace('src="static/', 'src="../static/', $html);
    $root_files = array('config.js', 'blog.css', 'blog.min.css', 'favicon.ico', 'apple-touch-icon.png', 'blog.html', 'blog_list.html');
    foreach ($root_files as $file) {
        $html = str_replace('href="' . $file, 'href="../' . $file, $html);
        $html = str_replace('src="' . $file, 'src="../' . $file, $html);
    }
    $html = str_replace('content="preview/', 'content="../preview/', $html);
    $html = str_replace("href=\"' + themeFile", "href=\"../' + themeFile", $html);
    return $html;
}

function optimize_images($html) {
    if (trim($html) === "") return "";
    $GLOBALS['g_img_counter'] = 0;
    return preg_replace_callback('/<img\s+([^>]+)>/i', function($matches) {
        $attrs = preg_replace("/\s+(loading|fetchpriority)\s*=\s*(\"[^\"]*\"|'[^']*')/i", '', $matches[1]);
        $new_attrs = ($GLOBALS['g_img_counter'] === 0) ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"';
        $GLOBALS['g_img_counter']++;
        return '<img ' . trim($attrs) . $new_attrs . '>';
    }, $html);
}

function generateSitemap($force, $indexFile) {
    $targetSitemap = "sitemap.xml";
    // Sitemap 依賴: index_post.txt
    if (!$force && checkCache($targetSitemap, array($indexFile))) {
        echo "$targetSitemap cached (skipped).<br>\r\n";
        return;
    }

    // date_default_timezone_set('Asia/Taipei'); // Removed: using global config
    $site_path = $GLOBALS['site_url'];
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    $pages = array_merge(array('blog.html', 'blog_list.html'), glob("post/*.html") ? glob("post/*.html") : array());
    foreach ($pages as $p) {
        if (file_exists($p)) {
            $xml .= "\n    <url>\n        <loc>" . $site_path . $p . "</loc>\n        <lastmod>" . date("c", filemtime($p)) . "</lastmod>\n    </url>";
        }
    }
    $xml .= "\n</urlset>";
    file_put_contents($targetSitemap, $xml);
    echo "$targetSitemap render ok!<br>\r\n";
}