<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PHP_LIB/dindent/Indenter.php';
require_once __DIR__ . '/PHP_LIB/dindent/Exception/DindentException.php';
require_once __DIR__ . '/PHP_LIB/TemplateManager.php';

use Gajus\Dindent\Indenter;

// --- Helper for XSS Prevention ---
function escapeVars($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = escapeVars($value);
        }
        return $data;
    }
    // Don't escape content if it's meant to be HTML (like post_content), 
    // but here we are targeting metadata. 
    // For this specific build script, we will apply escaping explicitly where needed.
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}
// ---------------------------------

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

// ==========================================
// 智慧快取判斷 (Smart Cache Strategy)
// ==========================================
$cacheHashFile = __DIR__ . "/contents/build_hash.json";
$storedHashes = file_exists($cacheHashFile) ? json_decode(file_get_contents($cacheHashFile), true) : [];

// 1. 全域影響參數 (Global Impact): 變更時需重產所有頁面
$globalConfigStr = $GLOBALS['blog_title'] . 
                   $GLOBALS['blog_introduce'] . 
                   $GLOBALS['site_url'] . 
                   $GLOBALS['blog_lang'] . 
                   $GLOBALS['blog_timezone'] .
                   (file_exists($langFile) ? file_get_contents($langFile) : ''); // Also track language file changes
$currentGlobalHash = md5($globalConfigStr);

// 2. 單頁影響參數 (Index-only Impact): 變更時僅需重產 blog.html
$indexConfigStr  = $GLOBALS['blog_description'] . 
                   $GLOBALS['blog_preview'];
$currentIndexHash = md5($indexConfigStr);

// 3. 判斷變更狀態
$configChangedGlobal = ($currentGlobalHash !== ($storedHashes['global'] ?? ''));
$configChangedIndex  = ($currentIndexHash !== ($storedHashes['index'] ?? ''));

if ($configChangedGlobal) {
    echo ">> [Config Change] Global settings changed. Rebuilding ALL pages.<br>\r\n";
} elseif ($configChangedIndex) {
    echo ">> [Config Change] Index settings changed. Rebuilding blog.html.<br>\r\n";
}
// ==========================================

// 檢查是否強制重產 (Command line force overrides everything)
$isForce = in_array('-f', $argv) || in_array('--force', $argv);
$isJson = in_array('-json', $argv); 

// 執行建置 (傳入 Config 狀態)
build($isForce, $isJson, $configChangedGlobal, $configChangedIndex, $langFile);

// 更新 Hash 紀錄
file_put_contents($cacheHashFile, json_encode([
    'global' => $currentGlobalHash,
    'index'  => $currentIndexHash,
    'last_build' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT));


function build($force = false, $jsonMode = false, $forceGlobal = false, $forceIndex = false, $langFile = '') {
    global $langVars; 
    
    // Ensure langVars is not empty
    if (empty($langVars)) {
        echo "Warning: langVars is empty. Check language file loading.<br>\r\n";
    }

    $indenter = new Indenter();
    $tpl = new TemplateManager();
    $templatePath = "static/blog_template.html";
    $tpl->load($templatePath);

    // 準備全域變數 (Explicitly merge to ensure lang_ keys are present)
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
    // JSON Pre-generation (If flag is set)
    // ==========================================
    if ($jsonMode) {
        generateJsonApi($posts, $categories);
    }

    $commonDeps = array($templatePath);
    if ($langFile) $commonDeps[] = $langFile;

    // ==========================================
    // A. 生成首頁 (blog.html)
    // ==========================================
    $targetBlog = "blog.html";
    if ($force || $forceGlobal || $forceIndex || !checkCache($targetBlog, $commonDeps)) {
        $indexVars = array_merge($globalVars, array(
            'page_title'          => htmlspecialchars($GLOBALS['blog_title']),
            'page_canonical'      => $GLOBALS['site_url'] . 'blog.html',
            'page_description'    => htmlspecialchars($GLOBALS['blog_description']),
            'page_og_title'       => htmlspecialchars($GLOBALS['blog_title']),
            'page_og_description' => htmlspecialchars($GLOBALS['blog_description']),
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
            continue;
        }

        // 列表項目渲染
        $listItemsHtml .= $tpl->render($tpl->getSubTemplate('tmpl_blog_list_item'), array(
            'link'  => "post/" . $post['filename'],
            'time'  => $post['date'],
            'title' => htmlspecialchars($post['title'])
        ));

        // 檢查單篇文章快取
        $targetPost = "post/" . $post['filename'];
        $sourcePost = "contents/post_files/" . $post['filename'];
        
        $postDeps = array_merge($commonDeps, array($sourcePost));

        if ($force || $forceGlobal || !checkCache($targetPost, $postDeps)) {
            
            // 只有需要重產時才進行這些較重的運算
            $safeTags = array_map(function($t) { return array('name' => htmlspecialchars($t['name'])); }, prepareTags($post['tags']));
            $tagsHtml = $tpl->renderList('tmpl_post_tag_item', $safeTags);
            $tagsBlock = $tagsHtml ? $tpl->render($tpl->getSubTemplate('tmpl_post_tag_container'), array_merge($globalVars, array('items' => $tagsHtml))) : '';

            $safeCats = array_map(function($c) { return array('name' => htmlspecialchars($c['name'])); }, matchCategories($post['filename'], $categories));
            $catsHtml = $tpl->renderList('tmpl_post_cat_item', $safeCats);
            $catsBlock = $catsHtml ? $tpl->render($tpl->getSubTemplate('tmpl_post_cat_container'), array_merge($globalVars, array('items' => $catsHtml))) : '';

            $postContentHtml = $tpl->render($tpl->getSubTemplate('tmpl_post_main'), array_merge($globalVars, array(
                'time'           => $post['date'],
                'title'          => htmlspecialchars($post['title']),
                'link'           => $post['filename'],
                'content'        => $post['content'], 
                'tags_block'     => $tagsBlock,
                'category_block' => $catsBlock
            )));

            $pageVars = array_merge($globalVars, array(
                'page_title'          => htmlspecialchars($GLOBALS['blog_title'] . "-" . $post['title']),
                'page_canonical'      => $GLOBALS['site_url'] . 'post/' . $post['filename'],
                'page_description'    => htmlspecialchars($post['description']),
                'page_og_title'       => htmlspecialchars($post['title']),
                'page_og_description' => htmlspecialchars($post['description']),
                'page_og_image'       => $post['og_image'],
                'page_og_url'         => $GLOBALS['site_url'] . 'post/' . $post['filename'],
                'page_twitter_card'   => $post['has_icon'] ? 'summary_large_image' : '',
                'body_class'          => 'is-single-page',
                'page_main_content'   => $postContentHtml
            ));

            $html = $tpl->render($tpl->getSource(), $pageVars);
            $html = pipeline($html, $indenter, true, true, true);
            write($targetPost, $html);
        }
    }


    // ==========================================
    // C. 生成文章總列表 (blog_list.html)
    // ==========================================
    $targetList = "blog_list.html";
    $listDeps = array_merge($commonDeps, array($indexFile));

    if ($force || $forceGlobal || !checkCache($targetList, $listDeps)) {
        $listContentHtml = $tpl->render($tpl->getSubTemplate('tmpl_blog_list_container'), array_merge($globalVars, array(
            'items' => $listItemsHtml
        )));

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
                    if($f == '.' || $f == '..') continue;
                    // Compatibility: check for filename directly or with .html
                    if (file_exists("contents/post_files/" . $f) || file_exists("contents/post_files/" . $f . ".html")) {
                        $validFiles[] = $f;
                    }
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

// --- JSON API Generator ---

function generateJsonApi($posts, $categories) {
    $jsonDir = "api/json";
    if (!is_dir($jsonDir)) mkdir($jsonDir, 0755, true);

    echo "Generating Consolidated JSON API file...<br>\r\n";

    // 1. Calculate Sidebar/Global Stats
    $ret_tag_count = [];
    $ret_date = [];
    $ret_date_post = [];
    $cat_stats = [];

    foreach ($categories as $cat) {
        $cat_stats[] = array(
            'name' => $cat['name'],
            'count' => count($cat['posts']), 
            'posts' => $cat['posts'] 
        );
    }

    foreach ($posts as $post) {
        if (!$post['isValid']) continue;
        foreach ($post['tags'] as $t) {
            $t = trim($t);
            if ($t === '') continue;
            $ret_tag_count[$t] = ($ret_tag_count[$t] ?? 0) + 1;
        }
        $dt_parts = explode(' ', $post['date']);
        $ymd = explode('-', $dt_parts[0]);
        if (count($ymd) >= 2) {
            $year = $ymd[0]; $mon = $ymd[1]; $ymKey = $year . $mon;
            $ret_date[$year] = ($ret_date[$year] ?? 0) + 1;
            $ret_date[$ymKey] = ($ret_date[$ymKey] ?? 0) + 1;
            if (!isset($ret_date_post[$ymKey])) $ret_date_post[$ymKey] = [];
            $ret_date_post[$ymKey][] = array('title' => $post['title'], 'post_index' => $post['filename']);
        }
    }

    // Helper to format post
    $formatPost = function($p) use ($categories) {
        $content_parts = explode('<!--more-->', $p['content']);
        $summary = $content_parts[0];
        
        // Find categories for this post
        $myCats = matchCategories($p['filename'], $categories);
        $catNames = array_map(function($c){ return $c['name']; }, $myCats);

        return [
            'post_category' => $catNames,
            'post_tags'     => $p['tags'],
            'post_time'     => $p['date'],
            'post_title'    => $p['title'],
            'post_content'  => $summary,
            'post_index'    => $p['filename']
        ];
    };

    // 2. Build the big data object
    $allPosts = [];
    foreach ($posts as $p) {
        if ($p['isValid']) $allPosts[] = $formatPost($p);
    }

    $masterData = [
        'posts' => $allPosts,
        'sidebar' => [
            'category'    => $cat_stats,
            'dates_count' => $ret_date,
            'date_post'   => $ret_date_post,
            'tags'        => $ret_tag_count
        ]
    ];

    // 3. Write to single file
    file_put_contents("$jsonDir/data.json", json_encode($masterData));
    
    // Clean up old files to avoid confusion
    $oldFiles = glob("$jsonDir/*.json");
    foreach($oldFiles as $f) {
        if (basename($f) !== 'data.json') @unlink($f);
    }

    echo "  - api/json/data.json created successfully.<br>\r\n";
}