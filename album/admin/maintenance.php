<?php
require_once 'auth.php';
requireAlbumLogin();

$current_page = 'maintenance.php';
?>
<!DOCTYPE html>
<html lang="<?php echo getWebLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('system_maintenance'); ?> - Album Admin</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="assets/js/sweetalert2.all.min.js"></script>
    <script src="<?php echo getAdminLangJs(); ?>"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .task-list { background: #f8f9fa; border-radius: 8px; padding: 15px; border: 1px solid #dee2e6; }
        .task-item { border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between; align-items: center; }
        .task-item:last-child { border-bottom: none; }
        .task-label { font-weight: bold; color: #495057; }
        .task-value { color: #0d6efd; font-family: monospace; }
        .finished-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 10px; }
        .badge-finished { background-color: #198754; color: white; font-size: 0.75rem; padding: 4px 8px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php require 'sidebar_inc.php'; ?>

    <div class="main-content flex-grow-1 bg-light">
        <div class="mb-4">
            <h2><?php echo __('system_maintenance'); ?></h2>
            <p class="text-muted"><?php echo __('maintenance_desc'); ?></p>
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
                                    <div class="form-text"><?php echo __('opt_force_json_desc'); ?></div>
                                </div>
                                
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="forceThumb" name="forceThumb">
                                    <label class="form-check-label fw-bold" for="forceThumb"><?php echo __('opt_force_thumb'); ?></label>
                                    <div class="form-text"><?php echo __('opt_force_thumb_desc'); ?></div>
                                </div>

                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="skipThumb" name="skipThumb">
                                    <label class="form-check-label fw-bold" for="skipThumb"><?php echo __('opt_skip_thumb'); ?></label>
                                    <div class="form-text"><?php echo __('opt_skip_thumb_desc'); ?></div>
                                </div>

                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="onlyHtml" name="onlyHtml">
                                    <label class="form-check-label fw-bold" for="onlyHtml"><?php echo __('opt_only_html'); ?></label>
                                    <div class="form-text"><?php echo __('opt_only_html_desc'); ?></div>
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
                        <i class="bi bi-info-circle"></i> <?php echo __('notice'); ?>
                    </div>
                    <div class="card-body small text-muted">
                        <ul>
                            <li><?php echo __('notice_1'); ?></li>
                            <li><?php echo __('notice_2'); ?></li>
                            <li><?php echo __('notice_3'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
let progressInterval = null;
let currentProgressId = null;

function startPolling() {
    progressInterval = setInterval(() => {
        const formData = new FormData();
        formData.append('action', 'get_rebuild_progress');
        formData.append('progress_id', currentProgressId);
        formData.append('csrf_token', '<?php echo getCSRFToken(); ?>');
        formData.append('t', Date.now());

        fetch('album_actions.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (!data) return;
            const albumVal = document.getElementById('panel-album-val');
            const photoVal = document.getElementById('panel-photo-val');
            const currentMsg = document.getElementById('panel-current-msg');
            const finishedContainer = document.getElementById('panel-finished-list');

            if (data.status !== 'waiting') {
                if (albumVal) albumVal.innerText = (data.album_current) ? `${data.album_current} / ${data.album_total}` : '-- / --';
                if (photoVal) photoVal.innerText = (data.photo_current) ? `${data.photo_current} / ${data.photo_total}` : '-- / --';
                if (currentMsg) currentMsg.innerText = data.message || adminLang.processing_wait;
                if (finishedContainer && data.finished_albums) {
                    finishedContainer.innerHTML = data.finished_albums.map(name => `<span class="badge-finished">${name}</span>`).join('');
                }
            }

            if (data.status === 'done') clearInterval(progressInterval);
        }).catch(err => {});
    }, 1000);
}

document.getElementById('btnExecute').addEventListener('click', function() {
    Swal.fire({
        title: adminLang.rebuild_confirm_title,
        text: adminLang.rebuild_confirm_text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: adminLang.confirm_btn,
        cancelButtonText: adminLang.cancel_btn
    }).then((result) => {
        if (result.isConfirmed) {
            currentProgressId = 'all_' + Date.now();
            Swal.fire({
                title: adminLang.rebuilding_title,
                html: `
                    <div class="task-list text-start">
                        <div class="task-item">
                            <span class="task-label">${adminLang.task_panel_album}</span>
                            <span class="task-value" id="panel-album-val">-- / --</span>
                        </div>
                        <div class="task-item">
                            <span class="task-label">${adminLang.task_panel_photo}</span>
                            <span class="task-value" id="panel-photo-val">-- / --</span>
                        </div>
                        <div class="mt-2 small">
                            <span class="task-label d-block mb-1">${adminLang.task_panel_current}</span>
                            <div id="panel-current-msg" class="text-muted border-start ps-2" style="min-height: 1.2em;">${adminLang.initializing}</div>
                        </div>
                        <div class="mt-3">
                            <span class="task-label small">${adminLang.task_panel_finished}</span>
                            <div id="panel-finished-list" class="finished-tags"></div>
                        </div>
                    </div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); startPolling(); }
            });

            const formData = new FormData(document.getElementById('rebuildForm'));
            formData.append('action', 'rebuild_all');
            formData.append('progress_id', currentProgressId);
            formData.append('csrf_token', '<?php echo getCSRFToken(); ?>');

            fetch('album_actions.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                Swal.fire(data.status === 'success' ? adminLang.success : adminLang.error, data.message, data.status);
            })
            .catch(error => {
                clearInterval(progressInterval);
                Swal.fire(adminLang.error, adminLang.error_network, 'error');
            });
        }
    });
});
</script>
</body>
</html>
