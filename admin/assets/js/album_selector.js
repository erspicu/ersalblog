/**
 * Album Selector for Blog Post Editor (Enhanced with Upload & Dynamic Size selection)
 */
class AlbumSelector {
    constructor(options) {
        this.albumPath = options.albumPath || 'album/';
        if (!this.albumPath.endsWith('/')) { this.albumPath += '/'; }
        
        // 從後台 admin/ 目錄看，相簿目錄在 ../ + albumPath
        this.baseDir = '../' + this.albumPath;
        
        this.apiUrl = options.apiUrl || this.baseDir + 'api/api_album.php';
        this.uploadUrl = options.uploadUrl || this.baseDir + 'admin/photo_actions.php';
        this.modalId = options.modalId || 'albumSelectorModal';
        this.onSelect = options.onSelect || null;

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
            cancel_btn: '取消',
            close_btn: '關閉'
        }, options.lang || {});
        
        this.currentAlbumId = null;
        this.currentAlbumName = '';
        this.thumbConfigs = []; // 存儲目前相簿支援的規格
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
        modalEl.addEventListener('show.bs.modal', () => { this.loadAlbums(); });
        document.getElementById('btn-back-to-albums').addEventListener('click', () => { this.loadAlbums(); });
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
                        <div class="ratio ratio-1x1 bg-light d-flex align-items-center justify-content-center overflow-hidden">
                            <img src="${this.baseDir}${album.cover}" style="width:100%; height:100%; object-fit:contain; padding:2px;">
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
            this.thumbConfigs = data.thumbConfigs || [];

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
                const sizes = photo.sizes || {};
                const thumb = sizes['XS'] || sizes['S'] || sizes['M'] || photo.src;

                html += `
                <div class="col-3 col-md-2 col-lg-1-5">
                    <div class="card h-100 photo-pick-card border-0 shadow-sm" style="cursor:pointer" onclick="window.albumPicker.showSizeOptions(this, ${JSON.stringify(photo).replace(/"/g, '&quot;')})">
                        <div class="ratio ratio-1x1 bg-light d-flex align-items-center justify-content-center overflow-hidden">
                            <img src="${this.baseDir}${thumb}" style="width:100%; height:100%; object-fit:contain; border-radius:4px;">
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
            container.innerHTML = html;

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
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        formData.append('csrf_token', csrfToken);
        for (let i = 0; i < files.length; i++) { formData.append('photos[]', files[i]); }
        const container = document.getElementById('album-picker-container');
        const originalContent = container.innerHTML;
        container.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-success"></div><p class="mt-2">${this.lang.uploading_msg}</p></div>`;
        try {
            await fetch(this.uploadUrl, { method: 'POST', body: formData });
            this.loadPhotos(this.currentAlbumId, this.currentAlbumName);
        } catch (e) {
            alert('Upload failed');
            container.innerHTML = originalContent;
        }
    }

    showSizeOptions(el, photo) {
        document.querySelectorAll('.photo-pick-card').forEach(c => c.classList.remove('border-primary'));
        el.classList.add('border-primary', 'border-2');
        
        const footer = document.querySelector(`#${this.modalId} .modal-footer`);
        const sizes = photo.sizes || {};
        
        // 原圖顯示解析度
        const origRes = (photo.width && photo.height) ? ` (${photo.width}x${photo.height})` : '';
        let btnsHtml = `<button class="btn btn-sm btn-primary" onclick="window.albumPicker.confirmSelect('${this.baseDir}${photo.src}', '${photo.filename}')">${this.lang.size_original}${origRes}</button>`;
        
        // 根據 thumbConfigs 動態產生按鈕並顯示寬度
        this.thumbConfigs.forEach(conf => {
            if (sizes[conf.id]) {
                const label = conf['comment-zh_TW'] || conf['comment'] || conf.id;
                const res = conf.width ? ` (${conf.width}px)` : '';
                btnsHtml += `<button class="btn btn-sm btn-outline-primary" onclick="window.albumPicker.confirmSelect('${this.baseDir}${sizes[conf.id]}', '${photo.filename}')">${label}${res}</button>`;
            }
        });

        footer.innerHTML = `
            <div class="me-auto small text-muted">${this.lang.selected_msg}${photo.filename}</div>
            <div class="btn-group">
                ${btnsHtml}
            </div>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this.lang.cancel_btn}</button>
        `;
    }

    confirmSelect(url, filename) {
        if (this.onSelect) { this.onSelect(url, filename); }
        this.modal.hide();
        setTimeout(() => {
            document.querySelector(`#${this.modalId} .modal-footer`).innerHTML = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this.lang.close_btn}</button>`;
        }, 500);
    }

    open() { this.modal.show(); }
}
