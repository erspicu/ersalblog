<?php
require_once 'auth.php';
require_once 'data_provider.php';
requireLogin();

$dataManager = new DataManager();
$id = isset($_GET['id']) ? $_GET['id'] : null;

// 檢查相簿服務是否可用
$album_enabled = false;
$actual_album_path = isset($album_path) ? $album_path : 'album/';
if (!empty($actual_album_path) && is_dir(__DIR__ . '/../' . $actual_album_path)) {
    $album_enabled = true;
}

$post = array(
    'post_title' => '',
    'post_filename' => '', 
    'post_date' => date('Y-m-d H:i:s'),
    'post_content' => '',
    'post_tags' => '',
    'post_categories' => '',
    'post_description' => ''
);

$pageTitle = __('edit_title_new');
$filename = '';

if ($id) {
    $fetched = $dataManager->getPost($id);
    if ($fetched) {
        $post = $fetched;
        $pageTitle = __('edit_title_edit');
        $filename = $post['post_filename'];
    } else {
        die(__('post_not_found'));
    }
}

// Get all categories for checkboxes
$allCatsData = $dataManager->getAllCategories(); 
$allCats = array_keys($allCatsData);

$currentCats = $post['post_categories'];
if (!is_array($currentCats)) {
    $currentCats = explode(',', isset($currentCats) ? $currentCats : '');
}
$currentCats = array_map('trim', $currentCats);
?>
<!DOCTYPE html>
<html lang="<?php echo getWebLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Blog Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; position: fixed; top: 0; left: 0; width: 250px; z-index: 1000; overflow-y: auto; }
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; }
        .main-content { margin-left: 250px; width: calc(100% - 250px); min-height: 100vh; padding: 20px; }
        .breadcrumb-item a { text-decoration: none; }
        .btn-outline-magic { border-color: #6f42c1; color: #6f42c1; transition: all 0.3s; }
        .btn-outline-magic:hover { background: linear-gradient(45deg, #6f42c1, #e83e8c); border-color: transparent; color: white; }
        #aiResultModal .diff-preview { background-color: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>

    <div class="main-content flex-grow-1 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-3">
             <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="posts.php"><?php echo __('breadcrumb_home'); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $pageTitle; ?></li>
                </ol>
            </nav>
        </div>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><?php echo $pageTitle; ?></h4>
            </div>
            <div class="card-body">
                <form action="post_save.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                    <input type="hidden" name="old_filename" value="<?php echo htmlspecialchars($filename); ?>">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars(isset($id) ? $id : ''); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_post_title'); ?></label>
                        <input type="text" name="post_title" class="form-control form-control-lg" value="<?php echo htmlspecialchars($post['post_title']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('label_filename'); ?></label>
                            <input type="text" name="post_filename" class="form-control" value="<?php echo htmlspecialchars($post['post_filename']); ?>" placeholder="<?php echo __('ph_filename'); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('label_post_date'); ?></label>
                            <input type="text" name="post_date" class="form-control" value="<?php echo htmlspecialchars($post['post_date']); ?>" placeholder="YYYY-MM-DD HH:MM:SS">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_html_content'); ?></label>
                        <div class="d-flex justify-content-between align-items-end mb-1">
                            <div class="btn-group">
                                <?php if ($album_enabled): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.albumPicker.open()">
                                    <i class="bi bi-images"></i> <?php echo __('btn_pick_from_album'); ?>
                                </button>
                                <?php endif; ?>
                                <?php if (isset($aiConfig) && $aiConfig['enabled']): ?>
                                <button type="button" class="btn btn-sm btn-outline-magic btn-ai-trigger" id="btn-ai-helper">
                                    <i class="bi bi-robot"></i> <?php echo __('btn_ai_assistant'); ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <textarea id="post_content" name="post_content" class="form-control" style="height: 400px; font-family: monospace;"><?php echo htmlspecialchars($post['post_content']); ?></textarea>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded border">
                        <label class="form-label fw-bold"><?php echo __('label_categories'); ?></label>
                        <div class="mb-2">
                            <?php foreach ($allCats as $cat): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="cats_check[]" value="<?php echo htmlspecialchars($cat); ?>" id="cat_<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo in_array($cat, $currentCats) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cat_<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="input-group input-group-sm w-50">
                            <span class="input-group-text"><?php echo __('label_add_category'); ?></span>
                            <input type="text" name="new_category" class="form-control" placeholder="<?php echo __('ph_new_category'); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_tags'); ?></label>
                        <input type="text" name="post_tags" class="form-control" value="<?php echo htmlspecialchars($post['post_tags']); ?>" placeholder="<?php echo __('ph_tags'); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_desc'); ?></label>
                        <div class="form-text mb-1"><?php echo __('hint_desc_seo'); ?></div>
                        <input type="text" name="post_description" class="form-control" value="<?php echo htmlspecialchars($post['post_description']); ?>" placeholder="<?php echo __('ph_desc_seo'); ?>">
                    </div>

                    <!-- SEO Preview Image Upload -->
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('label_preview_image'); ?></label>
                        <div class="form-text mb-1"><?php echo __('hint_preview_image'); ?> (1200x630)</div>
                        <div class="input-group">
                            <input type="file" name="preview_image" class="form-control" accept="image/*">
                            <?php 
                            $cleanFn = pathinfo($post['post_filename'], PATHINFO_FILENAME);
                            $previewPath = '../preview/icon-' . $cleanFn . '.jpg';
                            if (!empty($cleanFn) && file_exists($previewPath)): 
                            ?>
                                <span class="input-group-text bg-success text-white">
                                    <i class="bi bi-check-circle-fill"></i> <?php echo __('msg_preview_exists'); ?>
                                </span>
                                <a href="<?php echo $previewPath; ?>?t=<?php echo time(); ?>" target="_blank" class="btn btn-outline-secondary btn-sm d-flex align-items-center">
                                    <i class="bi bi-eye"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end align-items-center">
                        <div class="form-check me-3">
                            <input class="form-check-input" type="checkbox" name="auto_build" value="1" id="autoBuildCheck" checked>
                            <label class="form-check-label" for="autoBuildCheck"><?php echo __('label_auto_build'); ?></label>
                        </div>
                        <a href="posts.php" class="btn btn-outline-secondary me-md-2"><?php echo __('btn_cancel'); ?></a>
                        <button type="submit" name="is_draft" value="1" class="btn btn-warning text-dark px-4"><i class="bi bi-journal-text"></i> <?php echo __('btn_save_draft'); ?></button>
                        <button type="submit" name="is_draft" value="0" class="btn btn-success px-5"><i class="bi bi-send"></i> <?php echo __('btn_save_publish'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require 'common_js_inc.php'; ?>
<script src="assets/js/tinymce/tinymce.min.js"></script>

<!-- AI Assistant Modal -->
<div class="modal fade" id="aiHelperModal" tabindex="-1" aria-labelledby="aiHelperModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-purple text-white" style="background: linear-gradient(45deg, #6f42c1, #e83e8c);">
                <h5 class="modal-title" id="aiHelperModalLabel"><i class="bi bi-robot"></i> <?php echo __('modal_ai_title'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="ai-init-view">
                    <div id="ai-error-msg" class="alert alert-danger d-none"></div>
                    <p class="lead"><?php echo __('modal_ai_desc'); ?></p>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light fw-bold">選擇 AI 處理任務</div>
                        <div class="card-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input ai-task-checkbox" type="checkbox" value="title" id="task-title" checked>
                                <label class="form-check-label" for="task-title">自動命名標題</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ai-task-checkbox" type="checkbox" value="filename" id="task-filename" checked>
                                <label class="form-check-label" for="task-filename">建議 SEO 檔案名稱</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ai-task-checkbox" type="checkbox" value="desc" id="task-desc" checked>
                                <label class="form-check-label" for="task-desc">生成 SEO 摘要描述</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ai-task-checkbox" type="checkbox" value="tags" id="task-tags" checked>
                                <label class="form-check-label" for="task-tags">擷取文章標籤</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input ai-task-checkbox" type="checkbox" value="refine" id="task-refine">
                                <label class="form-check-label" for="task-refine">文章內文修潤 (修正語病、優化語氣)</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-2">
                        <button type="button" class="btn btn-primary btn-lg" id="btn-ai-start-analyze">
                            <i class="bi bi-stars"></i> <?php echo __('btn_ai_analyze'); ?>
                        </button>
                    </div>
                </div>

                <div id="ai-loading-view" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                    <h4><?php echo __('ai_analyzing'); ?></h4>
                    <p class="text-muted">這可能需要 10-20 秒，請稍候...</p>
                </div>

                <div id="ai-result-view" class="d-none">
                    <div class="list-group mb-3">
                        <!-- Title -->
                        <div class="list-group-item">
                            <div class="form-check mb-2">
                                <input class="form-check-input ai-apply-checkbox" type="checkbox" value="title" id="check-apply-title" checked>
                                <label class="form-check-label fw-bold" for="check-apply-title"><?php echo __('label_ai_suggest_title'); ?></label>
                            </div>
                            <input type="text" id="ai-res-title" class="form-control">
                        </div>

                        <!-- Filename -->
                        <div class="list-group-item">
                            <div class="form-check mb-2">
                                <input class="form-check-input ai-apply-checkbox" type="checkbox" value="filename" id="check-apply-filename" checked>
                                <label class="form-check-label fw-bold" for="check-apply-filename"><?php echo __('label_ai_suggest_filename'); ?></label>
                            </div>
                            <input type="text" id="ai-res-filename" class="form-control">
                        </div>

                        <!-- Meta Description -->
                        <div class="list-group-item">
                            <div class="form-check mb-2">
                                <input class="form-check-input ai-apply-checkbox" type="checkbox" value="description" id="check-apply-desc" checked>
                                <label class="form-check-label fw-bold" for="check-apply-desc"><?php echo __('label_ai_suggest_desc'); ?></label>
                            </div>
                            <textarea id="ai-res-desc" class="form-control" rows="2"></textarea>
                        </div>

                        <!-- Tags -->
                        <div class="list-group-item">
                            <div class="form-check mb-2">
                                <input class="form-check-input ai-apply-checkbox" type="checkbox" value="tags" id="check-apply-tags" checked>
                                <label class="form-check-label fw-bold" for="check-apply-tags"><?php echo __('label_ai_suggest_tags'); ?></label>
                            </div>
                            <div id="ai-res-tags-container" class="mb-2"></div>
                            <input type="text" id="ai-res-tags-raw" class="form-control form-control-sm" readonly>
                        </div>

                        <!-- Content Refinement -->
                        <div class="list-group-item">
                            <div class="form-check mb-2">
                                <input class="form-check-input ai-apply-checkbox" type="checkbox" value="content" id="check-apply-content">
                                <label class="form-check-label fw-bold" for="check-apply-content"><?php echo __('label_ai_suggest_content'); ?> (建議預覽)</label>
                            </div>
                            <div class="diff-preview border p-2 bg-light rounded" id="ai-res-content-preview" style="max-height: 200px; overflow-y: auto;"></div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success btn-lg" id="btn-ai-apply-selected">
                            <i class="bi bi-check-all"></i> <?php echo __('btn_ai_apply'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/ai_helper.js?v=<?php echo time(); ?>"></script>

<?php if ($album_enabled): ?>
<script src="assets/js/album_selector.js?v=<?php echo time(); ?>"></script>
<div class="modal fade" id="albumSelectorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><?php echo __('modal_album_picker_title'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="button" id="btn-back-to-albums" class="btn btn-sm btn-secondary d-none"><i class="bi bi-chevron-left"></i> <?php echo __('btn_back_to_albums'); ?></button>
                </div>
                <div id="album-picker-container"></div>
            </div>
            <div class="modal-footer py-2"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('btn_cancel'); ?></button></div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    <?php if ($album_enabled): ?>
    window.albumPicker = new AlbumSelector({
        albumPath: '<?php echo $actual_album_path; ?>',
        lang: {
            loading_albums: '<?php echo __('album_loading'); ?>', loading_photos: '<?php echo __('photo_loading'); ?>',
            no_albums: '<?php echo __('no_albums_found'); ?>', no_photos: '<?php echo __('no_photos_found'); ?>',
            album_label: '<?php echo __('label_album_name'); ?>', upload_btn: '<?php echo __('btn_direct_upload'); ?>',
            uploading_msg: '<?php echo __('uploading_wait'); ?>', selected_msg: '<?php echo __('msg_selected'); ?>',
            size_original: '<?php echo __('btn_size_original'); ?>', cancel_btn: '<?php echo __('btn_cancel'); ?>', close_btn: '<?php echo __('close_btn'); ?>'
        },
        onSelect: function(url, filename) {
            if (tinymce.activeEditor) {
                tinymce.activeEditor.insertContent(`<img src="${url}" alt="${filename}" style="max-width:100%; height:auto;">`);
            }
        }
    });
    <?php endif; ?>

    document.addEventListener('DOMContentLoaded', function() {
        tinymce.init({
            selector: 'textarea[name="post_content"]', height: 500, menubar: true,
            plugins: 'preview importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap quickbars emoticons',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview | image media link anchor codesample | code',
            toolbar_sticky: true, promotion: false, branding: false,
            <?php echo ($currentLang === 'zh_TW') ? "language: 'zh_TW'," : ""; ?>
            pagebreak_separator: '<!--more-->', pagebreak_split_block: true, image_advtab: true, valid_elements: '*[*]', extended_valid_elements: '*[*]', verify_html: false,
            setup: function (editor) { editor.on('change', function () { editor.save(); }); }
        });
    });
</script>
</body>
</html>
