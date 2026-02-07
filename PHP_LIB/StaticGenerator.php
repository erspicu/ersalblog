<?php
namespace PHPLib;

require_once __DIR__ . '/dindent/Indenter.php';
require_once __DIR__ . '/dindent/Exception/DindentException.php';
require_once __DIR__ . '/TemplateManager.php';

use Gajus\Dindent\Indenter;
use TemplateManager;

class StaticGenerator {
    private $baseDir;
    private $langVars;
    private $config;
    private $isVerbose;

    /**
     * @param string $baseDir Project root directory
     * @param array $langVars Language variables for templates
     * @param array $config Global configuration array
     * @param bool $isVerbose Whether to output echo statements
     */
    public function __construct($baseDir, $langVars, $config, $isVerbose = true) {
        $this->baseDir = rtrim($baseDir, '/');
        $this->langVars = $langVars;
        $this->config = $config;
        $this->isVerbose = $isVerbose;

        // Ensure system helper is loaded for protect_script_tags
        if (!function_exists('protect_script_tags')) {
            $helperPath = $this->baseDir . '/admin/system_helper.php';
            if (file_exists($helperPath)) {
                require_once $helperPath;
            }
        }
    }

    private function log($msg) {
        if ($this->isVerbose) {
            echo $msg;
        }
    }

    /**
     * Core build pipeline
     * @param bool $force Force rebuild all
     * @param bool $jsonMode Whether to update JSON API
     * @param bool $forceGlobal Whether to force rebuild global list pages
     * @param bool $forceIndex Unused but kept for signature compatibility if needed
     * @param string $langFile Unused but kept for signature compatibility
     * @param string|null $targetFilename Optional: only build this specific post file
     */
    public function build($force = false, $jsonMode = false, $forceGlobal = false, $forceIndex = false, $langFile = '', $targetFilename = null) {
        if (empty($this->langVars)) {
            $this->log("Warning: langVars is empty. Check language file loading.<br>\r\n");
        }

        $indenter = new Indenter();
        $tpl = new TemplateManager();
        $templatePath = $this->baseDir . "/static/blog_template.html";
        $tpl->load($templatePath);

        // Prepare Global Vars
        $globalVars = array_merge($this->langVars, array(
            'blog_title'       => $this->config['blog_title'],
            'blog_description' => $this->config['blog_description'],
            'blog_introduce'   => $this->config['blog_introduce'],
            'site_url'         => $this->config['site_url'],
        ));

        // Load Data
        $indexFile = $this->baseDir . "/contents/index_post.txt";
        $posts = $this->loadPosts($indexFile);
        $categories = $this->scanCategories($this->baseDir . "/category");

        // JSON API
        if ($jsonMode) {
            $this->generateJsonApi($posts, $categories);
        }

        $commonDeps = array($templatePath);

        // A. Generate Home (blog.html)
        $targetBlog = $this->baseDir . "/blog.html";
        // Always rebuild home if we are doing a single build to ensure latest list
        if ($force || $forceGlobal || $forceIndex || $targetFilename !== null || !$this->checkCache($targetBlog, $commonDeps)) {
            $indexVars = array_merge($globalVars, array(
                'page_title'          => htmlspecialchars($this->config['blog_title']),
                'page_canonical'      => $this->config['site_url'] . 'blog.html',
                'page_description'    => htmlspecialchars($this->config['blog_description']),
                'page_og_title'       => htmlspecialchars($this->config['blog_title']),
                'page_og_description' => htmlspecialchars($this->config['blog_description']),
                'page_og_image'       => $this->config['blog_preview'],
                'page_og_url'         => $this->config['site_url'] . 'blog.html',
                'page_twitter_card'   => 'summary_large_image',
                'body_class'          => '',
                'page_main_content'   => '', 
            ));

            $html = $tpl->render($tpl->getSource(), $indexVars);
            $html = $this->pipeline($html, $indenter, false, false, false);
            $this->write($targetBlog, $html);
        } else {
            $this->log("blog.html cached (skipped).<br>\r\n");
        }

        // B. Generate Posts (post/*.html)
        $postDir = $this->baseDir . "/post";
        if (!is_dir($postDir)) mkdir($postDir, 0755, true);
        
        $listItemsHtml = "";

        foreach ($posts as $post) {
            if (!$post['isValid']) {
                continue;
            }

            // List Item Render (Always accumulate for blog_list.html)
            $listItemsHtml .= $tpl->render($tpl->getSubTemplate('tmpl_blog_list_item'), array(
                'link'  => "post/" . $post['filename'],
                'time'  => $post['date'],
                'title' => htmlspecialchars($post['title'])
            ));

            // If targetFilename is specified, only process that one. Others use cache or skip.
            $isTarget = ($targetFilename !== null && $post['filename'] === $targetFilename);
            if ($targetFilename !== null && !$isTarget) {
                continue; 
            }

            // Check Cache
            $targetPost = $postDir . "/" . $post['filename'];
            $sourcePost = $this->baseDir . "/contents/post_files/" . $post['filename'];
            
            $postDeps = array_merge($commonDeps, array($sourcePost));

            // If it's the target, we force it.
            if ($force || $forceGlobal || $isTarget || !$this->checkCache($targetPost, $postDeps)) {
                
                $safeTags = array_map(function($t) { return array('name' => htmlspecialchars($t['name'])); }, $this->prepareTags($post['tags']));
                $tagsHtml = $tpl->renderList('tmpl_post_tag_item', $safeTags);
                $tagsBlock = $tagsHtml ? $tpl->render($tpl->getSubTemplate('tmpl_post_tag_container'), array_merge($globalVars, array('items' => $tagsHtml))) : '';

                $safeCats = array_map(function($c) { return array('name' => htmlspecialchars($c['name'])); }, $this->matchCategories($post['filename'], $categories));
                $catsHtml = $tpl->renderList('tmpl_post_cat_item', $safeCats);
                $catsBlock = $catsHtml ? $tpl->render($tpl->getSubTemplate('tmpl_post_cat_container'), array_merge($globalVars, array('items' => $catsHtml))) : '';

                $postContentHtml = $tpl->render($tpl->getSubTemplate('tmpl_post_main'), array_merge($globalVars, array(
                    'time'           => $post['date'],
                    'title'          => htmlspecialchars($post['title']),
                    'link'           => $post['filename'],
                    'content'        => protect_script_tags($post['content']), 
                    'tags_block'     => $tagsBlock,
                    'category_block' => $catsBlock
                )));

                $pageVars = array_merge($globalVars, array(
                    'page_title'          => htmlspecialchars($this->config['blog_title'] . "-" . $post['title']),
                    'page_canonical'      => $this->config['site_url'] . 'post/' . $post['filename'],
                    'page_description'    => htmlspecialchars($post['description']),
                    'page_og_title'       => htmlspecialchars($post['title']),
                    'page_og_description' => htmlspecialchars($post['description']),
                    'page_og_image'       => $post['og_image'],
                    'page_og_url'         => $this->config['site_url'] . 'post/' . $post['filename'],
                    'page_twitter_card'   => $post['has_icon'] ? 'summary_large_image' : '',
                    'body_class'          => 'is-single-page',
                    'page_main_content'   => $postContentHtml
                ));

                $html = $tpl->render($tpl->getSource(), $pageVars);
                $html = $this->pipeline($html, $indenter, true, true, true);
                $this->write($targetPost, $html);
            }
        }

        // C. Generate List (blog_list.html)
        $targetList = $this->baseDir . "/blog_list.html";
        $listDeps = array_merge($commonDeps, array($indexFile));

        if ($force || $forceGlobal || $targetFilename !== null || !$this->checkCache($targetList, $listDeps)) {
            $listContentHtml = $tpl->render($tpl->getSubTemplate('tmpl_blog_list_container'), array_merge($globalVars, array(
                'items' => $listItemsHtml
            )));

            $listVars = array_merge($globalVars, array(
                'page_title'          => $this->config['blog_title'] . "-" . $this->langVars['lang_list_page_title'],
                'page_canonical'      => $this->config['site_url'] . 'blog_list.html',
                'page_description'    => '',
                'page_og_title'       => $this->config['blog_title'] . "-" . $this->langVars['lang_list_page_title'],
                'page_og_description' => '',
                'page_og_image'       => '',
                'page_og_url'         => $this->config['site_url'] . 'blog_list.html',
                'page_twitter_card'   => '',
                'body_class'          => '',
                'page_main_content'   => $listContentHtml
            ));

            $html = $tpl->render($tpl->getSource(), $listVars);
            $html = $this->pipeline($html, $indenter, false, true, false); 
            $this->write($targetList, $html);
        } else {
            $this->log("blog_list.html cached (skipped).<br>\r\n");
        }

        // D. Generate Sitemap
        $this->generateSitemap($force || ($targetFilename !== null), $indexFile);
    }

    private function checkCache($targetFile, $dependencies) {
        if (!file_exists($targetFile)) {
            return false;
        }
        
        $targetTime = filemtime($targetFile);
        
        foreach ($dependencies as $dep) {
            if (file_exists($dep)) {
                if (filemtime($dep) > $targetTime) {
                    return false;
                }
            }
        }
        return true;
    }

    private function pipeline($html, $indenter, $fixPaths, $removeTemplates, $optimizeImages) {
        if ($removeTemplates) {
            $html = preg_replace('/<template\b[^>]*>.*?<\/template>\s*/is', '', $html);
        }
        if ($fixPaths) {
            $html = $this->fix_resource_paths($html);
        }
        if ($optimizeImages) {
            $html = $this->optimize_images($html);
        }
        return $html;
    }

    private function write($path, $content) {
        file_put_contents($path, $content);
        $this->log(basename($path) . " render ok!<br>\r\n");
    }

    private function loadPosts($indexFile) {
        $content = file_get_contents($indexFile);
        $content = str_replace("\r\n", "\n", $content);
        $lines = explode("\n", $content);
        $posts = array();

        foreach ($lines as $line) {
            if (trim($line) == "") continue;
            $parts = explode("|", $line);
            $filename = $parts[1];
            $sourcePath = $this->baseDir . "/contents/post_files/" . $filename;
            
            if (!file_exists($sourcePath)) {
                $posts[] = array('isValid' => false, 'filename' => $filename);
                continue;
            }

            $iconName = "icon-" . str_replace(".html", ".jpg", $filename);
            $hasIcon = file_exists($this->baseDir . "/preview/" . $iconName);

            $rawTags = explode(",", trim($parts[3]));
            $cleanTags = array();
            foreach ($rawTags as $t) {
                $t = trim($t);
                if ($t !== "") $cleanTags[] = $t;
            }

            $posts[] = array(
                'isValid'     => true,
                'date'        => $parts[0],
                'filename'    => $filename,
                'title'       => $parts[2],
                'tags'        => $cleanTags,
                'description' => isset($parts[4]) ? $parts[4] : '',
                'content'     => file_get_contents($sourcePath),
                'has_icon'    => $hasIcon,
                'og_image'    => $hasIcon ? ($this->config['site_url'] . 'preview/' . $iconName) : ''
            );
        }
        return $posts;
    }

    private function prepareTags($tagsArray) {
        $data = array();
        foreach ($tagsArray as $tag) {
            if (trim($tag) != "") $data[] = array('name' => $tag);
        }
        return $data;
    }

    private function scanCategories($dir) {
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
                        if (file_exists($this->baseDir . "/contents/post_files/" . $f) || file_exists($this->baseDir . "/contents/post_files/" . $f . ".html")) {
                            $validFiles[] = $f;
                        }
                    }
                    $cats[] = array('name' => $d, 'posts' => $validFiles);
                }
            }
        }
        return $cats;
    }

    private function matchCategories($filename, $categories) {
        $nameNoExt = str_replace(".html", "", $filename);
        $matched = array();
        foreach ($categories as $cat) {
            if (in_array($filename, $cat['posts']) || in_array($nameNoExt, $cat['posts'])) {
                 $matched[] = array('name' => $cat['name']);
            }
        }
        return $matched;
    }

    private function fix_resource_paths($html) {
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

    private function optimize_images($html) {
        if (trim($html) === "") return "";
        $GLOBALS['g_img_counter'] = 0; // Use simple global counter or property
        return preg_replace_callback('/<img\s+([^>]+)>/i', function($matches) {
            $attrs = preg_replace("/\s+(loading|fetchpriority)\s*=\s*(\"[^\"]*\"|'[^']*')/i", '', $matches[1]);
            $new_attrs = ($GLOBALS['g_img_counter'] === 0) ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"';
            $GLOBALS['g_img_counter']++;
            return '<img ' . trim($attrs) . $new_attrs . '>';
        }, $html);
    }

    private function generateSitemap($force, $indexFile) {
        $targetSitemap = $this->baseDir . "/sitemap.xml";
        if (!$force && $this->checkCache($targetSitemap, array($indexFile))) {
            $this->log("sitemap.xml cached (skipped).<br>\r\n");
            return;
        }

        $site_path = $this->config['site_url'];
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $pages = array_merge(array('blog.html', 'blog_list.html'), (glob($this->baseDir . "/post/*.html") ? glob($this->baseDir . "/post/*.html") : array()));
        foreach ($pages as $p) {
            if (file_exists($p)) {
                $relPath = substr($p, strlen($this->baseDir) + 1);
                $relPath = str_replace('\\', '/', $relPath);
                $xml .= "\n    <url>\n        <loc>" . $site_path . $relPath . "</loc>\n        <lastmod>" . date("c", filemtime($p)) . "</lastmod>\n    </url>";
            }
        }
        $xml .= "\n</urlset>";
        file_put_contents($targetSitemap, $xml);
        $this->log("sitemap.xml render ok!<br>\r\n");
    }

    private function generateJsonApi($posts, $categories) {
        $jsonDir = $this->baseDir . "/api/json";
        if (!is_dir($jsonDir)) mkdir($jsonDir, 0755, true);

        $this->log("Generating Consolidated JSON API file...<br>\r\n");

        $ret_tag_count = array();
        $ret_date = array();
        $ret_date_post = array();
        $cat_stats = array();

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
                $ret_tag_count[$t] = (isset($ret_tag_count[$t]) ? $ret_tag_count[$t] : 0) + 1;
            }
            $dt_parts = explode(' ', $post['date']);
            $ymd = explode('-', $dt_parts[0]);
            if (count($ymd) >= 2) {
                $year = $ymd[0]; $mon = $ymd[1]; $ymKey = $year . $mon;
                $ret_date[$year] = (isset($ret_date[$year]) ? $ret_date[$year] : 0) + 1;
                $ret_date[$ymKey] = (isset($ret_date[$ymKey]) ? $ret_date[$ymKey] : 0) + 1;
                if (!isset($ret_date_post[$ymKey])) $ret_date_post[$ymKey] = array();
                $ret_date_post[$ymKey][] = array('title' => $post['title'], 'post_index' => $post['filename']);
            }
        }

        // Helper (Avoid using $this in closure for PHP 5.3 compatibility)
        $self = $this;
        $formatPost = function($p) use ($categories, $self) {
            $content_parts = explode('<!--more-->', $p['content']);
            $summary = protect_script_tags($content_parts[0]);
            
            $filename = $p['filename'];
            $nameNoExt = str_replace(".html", "", $filename);
            $matched = array();
            foreach ($categories as $cat) {
                if (in_array($filename, $cat['posts']) || in_array($nameNoExt, $cat['posts'])) {
                     $matched[] = $cat['name'];
                }
            }
            $catNames = $matched;

            $finalTags = array();
            foreach ($p['tags'] as $t) {
                $t = trim($t);
                if ($t !== "") $finalTags[] = $t;
            }

            return array(
                'post_category' => $catNames,
                'post_tags'     => $finalTags,
                'post_time'     => $p['date'],
                'post_title'    => $p['title'],
                'post_content'  => $summary,
                'post_index'    => $p['filename']
            );
        };

        $allPosts = array();
        foreach ($posts as $p) {
            if ($p['isValid']) {
                if (file_exists($this->baseDir . "/post/" . $p['filename'])) {
                    $allPosts[] = $formatPost($p);
                }
            }
        }

        $masterData = array(
            'posts' => $allPosts,
            'sidebar' => array(
                'category'    => $cat_stats,
                'dates_count' => $ret_date,
                'date_post'   => $ret_date_post,
                'tags'        => $ret_tag_count
            )
        );

        file_put_contents("$jsonDir/data.json", json_encode($masterData));
        
        $oldFiles = glob("$jsonDir/*.json");
        foreach($oldFiles as $f) {
            if (basename($f) !== 'data.json') @unlink($f);
        }

        $this->log("  - api/json/data.json created successfully.<br>\r\n");
    }
}
?>