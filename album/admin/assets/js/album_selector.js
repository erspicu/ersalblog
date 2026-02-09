/**
 * Album Selector for Blog Post Editor (Enhanced with Upload & Size selection)
 */
class AlbumSelector {
    constructor(options) {
        this.albumPath = options.albumPath || 'album/';
        // 確保路徑結尾有斜線
        if (!this.albumPath.endsWith('/')) {
            this.albumPath += '/';
        }
        
        // 從後台 admin/ 目錄看，相簿目錄在 ../ + albumPath
        this.baseDir = '../' + this.albumPath;
        
        this.apiUrl = options.apiUrl || this.baseDir + 'api/api_album.php';
        this.uploadUrl = options.uploadUrl || this.baseDir + 'admin/photo_actions.php';
        this.modalId = options.modalId || 'albumSelectorModal';
        this.onSelect = options.onSelect || null;

        // 語系字串
        this.lang = Object.assign({
            loading_albums: '載入相簿中...',
            loading_photos: '載入照片中...',
            no_albums: '目前沒有任何相簿。',
            no_photos: '此相簿目前沒有照片。',
            album_label: '相簿：',
            upload_btn: '直接上傳到此相簿',
            uploading_msg: '正在上傳並產生縮圖...',
            selected_msg: '選中：',
            size_original: '原圖',
            size_large: '大 (1600px)',
            size_medium: '中 (800px)',
            cancel_btn: '取消',
            close_btn: '關閉'
        }, options.lang || {});
        
        this.currentAlbumId = null;
        this.currentAlbumName = '';
        this.modal = null;
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById(this.modalId);
            if (el) {
                this.modal = new bootstrap.Modal(el);
                this.setupEventListeners();
            }
        });
    }

    setupEventListeners() {
        const modalEl = document.getElementById(this.modalId);
        modalEl.addEventListener('show.bs.modal', () => {
            this.loadAlbums();
        });

        document.getElementById('btn-back-to-albums').addEventListener('click', () => {
            this.loadAlbums();
        });
    }

    async loadAlbums() {
        this.currentAlbumId = null;
        const container = document.getElementById('album-picker-container');
        const backBtn = document.getElementById('btn-back-to-albums');
        backBtn.classList.add('d-none');
        container.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">${this.lang.loading_albums}</p></div>`;

        try {
            const resp = await fetch(`${this.apiUrl}?action=list_albums`);
            const data = await resp.json();
            const albums = data.items || [];

            if (albums.length === 0) {
                container.innerHTML = `<div class="alert alert-info">${this.lang.no_albums}</div>`;
                return;
            }

            let html = '<div class="row g-2">';
            albums.forEach(album => {
                html += `
                <div class="col-4 col-md-2">
                    <div class="card h-100 album-pick-card border-0 shadow-sm" style="cursor:pointer" onclick="window.albumPicker.loadPhotos('${album.id}', '${album.name}')">
                        <div class="ratio ratio-1x1 bg-light">
                            <img src="${this.baseDir}${album.cover}" style="object-fit:contain; padding:5px;">
                        </div>
                        <div class="p-2 text-center">
                            <div class="small fw-bold text-truncate" title="${album.name}">${album.name}</div>
                            <div class="text-muted" style="font-size:0.7rem">${album.count} 張</div>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger">載入失敗。</div>';
        }
    }

    async loadPhotos(albumId, albumName) {
        this.currentAlbumId = albumId;
        this.currentAlbumName = albumName;
        const container = document.getElementById('album-picker-container');
        const backBtn = document.getElementById('btn-back-to-albums');
        backBtn.classList.remove('d-none');
        container.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">${this.lang.loading_photos}</p></div>`;

        try {
            const resp = await fetch(`${this.apiUrl}?action=get_album&album=${encodeURIComponent(albumId)}`);
            const data = await resp.json();
            const photos = data.photos || [];

            let html = `
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h6 class="mb-0">${this.lang.album_label}<span class="text-primary">${albumName}</span></h6>
                <div class="upload-mini-form">
                    <input type="file" id="album-mini-upload" class="d-none" multiple accept="image/jpeg">
                    <button class="btn btn-sm btn-success" onclick="document.getElementById('album-mini-upload').click()">
                        <i class="bi bi-upload"></i> ${this.lang.upload_btn}
                    </button>
                </div>
            </div>
            <div class="row g-2">`;
            
            if (photos.length === 0) {
                html += `<div class="col-12 text-center py-4 text-muted">${this.lang.no_photos}</div>`;
            }

            photos.forEach(photo => {
                const thumb = photo.thumbXS || photo.thumb || photo.src;
                html += `
                <div class="col-3 col-md-2 col-lg-1-5">
                    <div class="card h-100 photo-pick-card border-0 shadow-sm" style="cursor:pointer" onclick="window.albumPicker.showSizeOptions(this, '${photo.filename}', '${photo.src}', '${photo.thumbL}', '${photo.thumb}')">
                        <div class="ratio ratio-1x1 bg-light">
                            <img src="${this.baseDir}${thumb}" style="object-fit:contain">
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
            container.innerHTML = html;

            // 監聽即時上傳
            document.getElementById('album-mini-upload').addEventListener('change', (e) => this.handleUpload(e));

        } catch (e) {
            container.innerHTML = '<div class="alert alert-danger">載入失敗。</div>';
        }
    }

    async handleUpload(event) {
        const files = event.target.files;
        if (!files.length) return;

        const formData = new FormData();
        formData.append('action', 'upload_photos');
        formData.append('album_id', this.currentAlbumId);
        // 此處需要傳入 CSRF Token，從頁面讀取
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        formData.append('csrf_token', csrfToken);

        for (let i = 0; i < files.length; i++) {
            formData.append('photos[]', files[i]);
        }

        const container = document.getElementById('album-picker-container');
        const originalContent = container.innerHTML;
        container.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-success"></div><p class="mt-2">${this.lang.uploading_msg}</p></div>`;

        try {
            const resp = await fetch(this.uploadUrl, {
                method: 'POST',
                body: formData
            });
            // 由於 photo_actions.php 是跳轉型的，這裡我們直接重新載入照片清單
            this.loadPhotos(this.currentAlbumId, this.currentAlbumName);
        } catch (e) {
            alert('Upload failed');
            container.innerHTML = originalContent;
        }
    }

    showSizeOptions(el, filename, src, thumbL, thumb) {
        // 移除其他已存在的 popover 或選取效果
        document.querySelectorAll('.photo-pick-card').forEach(c => c.classList.remove('border-primary'));
        el.classList.add('border-primary', 'border-2');

        // 簡單的尺寸選擇提示 (這裡用 prompt 或自定義 UI，這裡先用一組按鈕動態產生在卡片下方或使用 SweetAlert2 如果有的話)
        // 為了不依賴額外庫，我們在 Modal footer 顯示選擇
        const footer = document.querySelector(`#${this.modalId} .modal-footer`);
        const btnHtml = `
            <div class="me-auto small text-muted">${this.lang.selected_msg}${filename}</div>
            <div class="btn-group">
                <button class="btn btn-sm btn-primary" onclick="window.albumPicker.confirmSelect('${this.baseDir}${src}', '${filename}')">${this.lang.size_original}</button>
                <button class="btn btn-sm btn-primary" onclick="window.albumPicker.confirmSelect('${this.baseDir}${thumbL}', '${filename}')">${this.lang.size_large}</button>
                <button class="btn btn-sm btn-primary" onclick="window.albumPicker.confirmSelect('${this.baseDir}${thumb}', '${filename}')">${this.lang.size_medium}</button>
            </div>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this.lang.cancel_btn}</button>
        `;
        footer.innerHTML = btnHtml;
    }

    confirmSelect(url, filename) {
        if (this.onSelect) {
            this.onSelect(url, filename);
        }
        this.modal.hide();
        // 重設 footer
        setTimeout(() => {
            document.querySelector(`#${this.modalId} .modal-footer`).innerHTML = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this.lang.close_btn}</button>`;
        }, 500);
    }

    open() {
        this.modal.show();
    }
}