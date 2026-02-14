<?php
require_once 'auth.php';
requireAlbumLogin();

$current_page = 'maintenance.php';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('system_maintenance'); ?> - Album Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="assets/js/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>

    <div class="main-content flex-grow-1 bg-light">
        <div class="mb-4">
            <h2><?php echo __('system_maintenance'); ?></h2>
            <p class="text-muted">執行全站性的相簿資料維護與重建任務。</p>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold">
                        <i class="bi bi-tools"></i> <?php echo __('rebuild_options'); ?>
                    </div>
                    <div class="card-body">
                        <form id="rebuildForm">
                            <div class="mb-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="forceJson" name="forceJson">
                                    <label class="form-check-label fw-bold" for="forceJson"><?php echo __('opt_force_json'); ?></label>
                                    <div class="form-text">強制重新掃描目錄並產生 JSON (忽略修改時間快取)。</div>
                                </div>
                                
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="forceThumb" name="forceThumb">
                                    <label class="form-check-label fw-bold" for="forceThumb"><?php echo __('opt_force_thumb'); ?></label>
                                    <div class="form-text">強制重新產生所有縮圖 (忽略現有縮圖檔)。</div>
                                </div>

                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="skipThumb" name="skipThumb">
                                    <label class="form-check-label fw-bold" for="skipThumb"><?php echo __('opt_skip_thumb'); ?></label>
                                    <div class="form-text">僅處理 JSON 資料，完全不觸動縮圖。</div>
                                </div>

                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="onlyHtml" name="onlyHtml">
                                    <label class="form-check-label fw-bold" for="onlyHtml"><?php echo __('opt_only_html'); ?></label>
                                    <div class="form-text">僅更新 `album.html` 樣板 Shell (不掃描相簿)。</div>
                                </div>
                            </div>

                            <hr>

                            <button type="button" class="btn btn-warning btn-lg w-100" id="btnExecute">
                                <i class="bi bi-play-fill"></i> <?php echo __('execute_rebuild'); ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">
                        <i class="bi bi-info-circle"></i> 注意事項
                    </div>
                    <div class="card-body small text-muted">
                        <ul>
                            <li>重建過程會根據相簿與照片數量耗費不等的時間。</li>
                            <li>「僅更新樣板」速度最快，適合在修改完 <code>config.php</code> 設定後執行。</li>
                            <li>執行期間請勿關閉視窗，以免程序中斷導致資料不完整。</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('btnExecute').addEventListener('click', function() {
    Swal.fire({
        title: '<?php echo __('rebuild_confirm_title'); ?>',
        text: '<?php echo __('rebuild_confirm_text'); ?>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<?php echo __('confirm_btn'); ?>',
        cancelButtonText: '<?php echo __('cancel_btn'); ?>'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                text: '正在執行維護任務，請稍候...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData(document.getElementById('rebuildForm'));
            formData.append('action', 'rebuild_all');
            formData.append('csrf_token', '<?php echo getCSRFToken(); ?>');

            fetch('album_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Success', data.message, 'success');
                } else {
                    Swal.fire('Error', data.message || '執行失敗', 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', '網路錯誤或伺服器異常', 'error');
            });
        }
    });
});
</script>
</body>
</html>
