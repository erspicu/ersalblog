/**
 * Baxermux Album System Core JS (SPA Version - Client Side Pagination & Dynamic Share Filtering)
 */

/* =========================================
   Download & Concurrency Manager
   ========================================= */
class DownloadManager {
    constructor(maxConcurrent = 3) {
        this.maxConcurrent = maxConcurrent;
        this.currentConcurrent = 0;
        this.queue = [];
    }

    /**
     * 接管 <img> 標籤的載入，確保有序呈現
     * @param {string} url 圖片網址
     * @param {HTMLImageElement} imgElement 圖片元素
     */
    async loadImage(url, imgElement) {
        return new Promise((resolve, reject) => {
            this.queue.push({ url, imgElement, type: 'display', resolve, reject });
            this.processQueue();
        });
    }

    /**
     * 將下載任務加入佇列 (儲存檔案)
     */
    async download(url, filename) {
        return new Promise((resolve, reject) => {
            this.queue.push({ url, filename, type: 'download', resolve, reject });
            this.processQueue();
        });
    }

    async processQueue() {
        if (this.currentConcurrent >= this.maxConcurrent || this.queue.length === 0) {
            return;
        }

        this.currentConcurrent++;
        const task = this.queue.shift();
        const { url, filename, imgElement, type, resolve, reject } = task;

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const blob = await response.blob();
            const blobUrl = window.URL.createObjectURL(blob);
            
            if (type === 'display' && imgElement) {
                imgElement.src = blobUrl;
                imgElement.classList.add('loaded');
                imgElement._blobUrl = blobUrl;
                
                // 【核心修正】將 blob 物件一併傳遞，提升 EXIF 解析穩定性
                imgElement.dispatchEvent(new CustomEvent('managed-image-loaded', { 
                    detail: { url, blobUrl, blob } 
                }));
                
                resolve(blobUrl);
            } else {
                const a = document.createElement('a');
                a.href = blobUrl;
                a.download = filename || url.split('/').pop();
                document.body.appendChild(a);
                a.click();
                setTimeout(() => {
                    window.URL.revokeObjectURL(blobUrl);
                    document.body.removeChild(a);
                }, 100);
                resolve(filename);
            }
        } catch (error) {
            console.error(`[DownloadManager] Failed: ${url}`, error);
            if (imgElement) imgElement.alt = "載入失敗";
            reject(error);
        } finally {
            this.currentConcurrent--;
            this.processQueue();
        }
    }
}

window.albumDownloadManager = new DownloadManager(3);

function managedLoadImages() {
    const images = document.querySelectorAll('img[data-managed-src]:not(.managed-init)');
    images.forEach(img => {
        img.classList.add('managed-init');
        const url = img.getAttribute('data-managed-src');
        albumDownloadManager.loadImage(url, img);
    });
}

/* =========================================
   Global State & Config
   ========================================= */
const AppState = {
    currentView: 'home',
    currentAlbum: null,
    currentPhoto: null,
    currentPage: 1,
    // 從 config.js 讀取設定，預設 24
    itemsPerPage: (typeof albumConfig !== 'undefined' && albumConfig.items_per_page) ? albumConfig.items_per_page : 24,
    albumDataCache: {}, 
    homeData: null,
    apiType: (typeof albumConfig !== 'undefined' && albumConfig.api_type) ? albumConfig.api_type : 'json',
    compressionConfig: null,
    modeToId: {}, // 存放 mode -> id 對照表
    uiTakeover: false // 【協議】是否由主題完全接管 UI 渲染
};

/**
 * 根據 mode 獲取照片對應的 URL
 */
function getImageUrlByMode(photo, mode) {
    if (!photo) return '';
    // 如果有 sizes 物件且對照表有設定
    if (photo.sizes && AppState.modeToId[mode]) {
        const id = AppState.modeToId[mode];
        if (photo.sizes[id]) return photo.sizes[id];
    }
    // 備案：如果是 PreviewIcon 且有傳統 thumb 欄位
    if (mode === 'PreviewIcon' && photo.thumb) return photo.thumb;
    if (mode === 'PictureShow' && photo.thumbL) return photo.thumbL;
    if (mode === 'ModalShow' && photo.thumbXL) return photo.thumbXL;
    
    return photo.src;
}

async function initCompressionConfig() {
    try {
        const resp = await fetch('config/compression.json');
        const data = await resp.json();
        AppState.compressionConfig = data;
        // 建立對照表
        data.forEach(item => {
            if (item.mode) AppState.modeToId[item.mode] = item.id;
        });
    } catch (e) {
        console.warn("Load compression.json failed, using defaults.");
    }
}

/* =========================================
   Router & Initialization
   ========================================= */
function renderTemplate(templateId, vars = {}) {
    const templateEl = document.getElementById(templateId);
    if (!templateEl) return `<!-- Missing Template: ${templateId} -->`;
    let html = templateEl.innerHTML;
    for (const key in vars) {
        const regex = new RegExp(`{{${key}}}`, 'g');
        html = html.replace(regex, vars[key]);
    }
    return html;
}

document.addEventListener("DOMContentLoaded", async () => {
    await initCompressionConfig();
    initRouter();
});

window.addEventListener("hashchange", () => handleRoute());
function initRouter() { handleRoute(); }

function handleRoute() {
    // 【協議檢查】如果主題已接管，則停止父層的預設渲染
    if (AppState.uiTakeover) {
        console.log("[Core] UI Takeover active, skipping default rendering.");
        return;
    }

    const hash = window.location.hash;
    const params = new URLSearchParams(hash.substring(1)); 
    const album = params.get('album');
    const photo = params.get('photo');
    const page = parseInt(params.get('page')) || 1;
    AppState.currentPage = page;

    if (photo && album) loadPhotoView(album, photo);
    else if (album) loadAlbumView(album, page);
    else loadHomeView(page);
}

/* =========================================
   Data Fetching
   ========================================= */
function getApiUrl(type, params = {}) {
    const isJson = AppState.apiType === 'json';
    if (type === 'home') return isJson ? 'api/json/index.json' : `api/api_album.php?action=list_albums`;
    if (type === 'album') {
        const name = params.name;
        return isJson ? `api/json/${name}.json` : `api/api_album.php?action=get_album&album=${encodeURIComponent(name)}`;
    }
    return '';
}

async function fetchHomeData() {
    if (AppState.homeData) return AppState.homeData;
    const url = getApiUrl('home');
    try {
        const resp = await fetch(url);
        const data = await resp.json();
        AppState.homeData = data;
        return data;
    } catch (e) { return { items: [] }; }
}

async function fetchAlbumData(albumName) {
    if (AppState.albumDataCache[albumName]) return AppState.albumDataCache[albumName];
    const url = getApiUrl('album', { name: albumName });
    try {
        const resp = await fetch(url);
        const data = await resp.json();
        AppState.albumDataCache[albumName] = data;
        return data;
    } catch (e) { return null; }
}

/* =========================================
   View Rendering
   ========================================= */
function setContent(html) {
    const container = document.getElementById('app-container');
    if (container) container.innerHTML = html;
}

function setHeader(html) {
    const container = document.getElementById('album-header-section');
    if (container) container.innerHTML = html;
}

function renderPaginationUI(pagination, baseUrlHash) {
    const container = document.getElementById('pagination-container');
    if (!container || !pagination || pagination.totalPages <= 1) {
        if(container) container.innerHTML = '';
        return;
    }
    let itemsHtml = '';
    const cur = pagination.currentPage;
    const total = pagination.totalPages;

    itemsHtml += renderTemplate('tmpl_pagination_item', {
        link: (cur > 1) ? `${baseUrlHash}&page=${cur - 1}` : '#',
        text: '<i class="bi bi-chevron-left"></i>',
        activeClass: '',
        disabledClass: (cur > 1) ? '' : 'disabled'
    });

    for (let i = 1; i <= total; i++) {
        itemsHtml += renderTemplate('tmpl_pagination_item', {
            link: `${baseUrlHash}&page=${i}`,
            text: i,
            activeClass: (i === cur) ? 'active' : '',
            disabledClass: ''
        });
    }

    itemsHtml += renderTemplate('tmpl_pagination_item', {
        link: (cur < total) ? `${baseUrlHash}&page=${cur + 1}` : '#',
        text: '<i class="bi bi-chevron-right"></i>',
        activeClass: '',
        disabledClass: (cur < total) ? '' : 'disabled'
    });
    container.innerHTML = renderTemplate('tmpl_pagination', { items: itemsHtml });
}

async function loadHomeView(page = 1) {
    AppState.currentView = 'home';
    setHeader(''); 
    setContent('<div class="text-center py-5">載入相簿列表中...</div>');
    const data = await fetchHomeData();
    let rawItems = data.items || [];
    const totalItems = rawItems.length;
    const totalPages = Math.ceil(totalItems / AppState.itemsPerPage);
    const start = (page - 1) * AppState.itemsPerPage;
    const pagedItems = rawItems.slice(start, start + AppState.itemsPerPage);

    let listHtml = '<div class="album-grid" id="album-list-container">';
    pagedItems.forEach(album => {
        listHtml += renderTemplate('tmpl_index_album_item', {
            link: `#album=${encodeURIComponent(album.id || album.name)}`,
            cover: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
            name: album.name,
            count: album.count,
            desc: album.desc || "&nbsp;"
        });
    });
    listHtml += '</div>';
    setContent(listHtml);
    const cards = document.querySelectorAll('#album-list-container .card-img');
    pagedItems.forEach((album, idx) => { if(cards[idx]) cards[idx].setAttribute('data-managed-src', album.cover); });
    managedLoadImages();
    renderPaginationUI({currentPage: page, totalPages: totalPages}, '#');
    document.title = "相簿首頁 - Baxermux的相簿";
}

async function loadAlbumView(albumName, page = 1) {
    AppState.currentView = 'album';
    setContent('<div class="text-center py-5">載入照片中...</div>');
    const data = await fetchAlbumData(albumName);
    if (!data) { setContent('<div class="text-center text-danger py-5">找不到相簿</div>'); return; }
    let rawPhotos = data.photos || [];
    const totalItems = rawPhotos.length;
    const totalPages = Math.ceil(totalItems / AppState.itemsPerPage);
    const start = (page - 1) * AppState.itemsPerPage;
    const pagedPhotos = rawPhotos.slice(start, start + AppState.itemsPerPage);

    setHeader(renderTemplate('tmpl_album_header', { name: data.name, desc_html: data.desc_html || '' }));
    let gridHtml = renderTemplate('tmpl_album_controls', { name: data.name, total: totalItems }) + '<div class="album-grid">';
    pagedPhotos.forEach(photo => {
        gridHtml += renderTemplate('tmpl_album_photo_item', {
            photoPageLink: `#album=${encodeURIComponent(albumName)}&photo=${encodeURIComponent(photo.filename)}`,
            imgSrc: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
            filename: photo.filename,
            photoDesc: photo.title || photo.filename
        });
    });
    gridHtml += '</div>';
    setContent(gridHtml);
    const imgs = document.querySelectorAll('.album-grid .card-img');
    pagedPhotos.forEach((photo, idx) => { 
        if(imgs[idx]) {
            // 【核心修正】根據模式獲取縮圖
            const url = getImageUrlByMode(photo, 'PreviewIcon');
            imgs[idx].setAttribute('data-managed-src', url); 
        }
    });
    managedLoadImages();
    renderPaginationUI({currentPage: page, totalPages: totalPages}, `#album=${encodeURIComponent(albumName)}`);
    document.title = `${data.name} - Baxermux的相簿`;
}

async function loadPhotoView(albumName, photoName) {
    AppState.currentView = 'photo';
    setHeader(''); 
    const data = await fetchAlbumData(albumName);
    const photoIndex = data?.photos?.findIndex(p => p.filename === photoName) ?? -1;
    if (photoIndex === -1) { setContent('<div class="text-center text-danger py-5">找不到照片</div>'); return; }
    const photo = data.photos[photoIndex];
    const prevPhoto = data.photos[(photoIndex - 1 + data.photos.length) % data.photos.length];
    const nextPhoto = data.photos[(photoIndex + 1) % data.photos.length];

    // 獲取不同模式的 URL
    const urlPicture = getImageUrlByMode(photo, 'PictureShow');
    const urlModal = getImageUrlByMode(photo, 'ModalShow');

    setContent(renderTemplate('tmpl_photo_detail_view', {
        pathToHome: '#',
        albumName: data.name,
        albumLink: `#album=${encodeURIComponent(albumName)}&page=${Math.floor(photoIndex / AppState.itemsPerPage) + 1}`,
        filename: photo.filename,
        prevLink: `#album=${encodeURIComponent(albumName)}&photo=${encodeURIComponent(prevPhoto.filename)}`,
        nextLink: `#album=${encodeURIComponent(albumName)}&photo=${encodeURIComponent(nextPhoto.filename)}`,
        imgSrc: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
        imgSrcXL: urlModal,
        imgSrcOriginal: photo.src,
        shortIdStart: photo.shortIdStart || '0',
        photoTitle: photo.title || photo.filename,
        photoDesc: photo.desc || '',
        exif_info: (photo.exif && Object.keys(photo.exif).length > 0) ? formatExifHtml(photo.exif, 'PHP') : '<div class="col-12 text-muted small" id="exif-loading-text">正在載入技術資訊...</div>'
    }));

    // 先設置 EXIF 偵測監聽器
    if (!photo.exif || Object.keys(photo.exif).length === 0) {
        tryFetchExifClientSide();
    }

    // 啟動圖片下載
    const mainImg = document.getElementById('photo-main-viewer');
    if(mainImg) {
        mainImg.setAttribute('data-managed-src', urlPicture);
        managedLoadImages();
    }
    document.title = `${photo.filename} - ${data.name}`;
}

/**
 * 所有主題通用的 EXIF 偵測，監聽 DownloadManager 的事件並優先使用 Blob
 */
function tryFetchExifClientSide() {
    const img = document.getElementById('photo-main-viewer');
    const container = document.getElementById('exif-info-container');
    if (!img || !container || typeof EXIF === 'undefined') return;

    const runExif = function(e) {
        const sourceData = (e && e.detail && e.detail.blob) ? e.detail.blob : img;
        if (img.src.startsWith('data:image/gif;base64') && !e) return;

        EXIF.getData(sourceData, function() {
            const all = EXIF.getAllTags(this);
            if (!all || Object.keys(all).length === 0) return;
            const exif = {
                make: all.Make || '', model: all.Model || '',
                aperture: all.FNumber ? `f/${all.FNumber.toFixed(1)}` : '未知',
                iso: all.ISOSpeedRatings || '未知',
                date: all.DateTimeOriginal || all.DateTime || '未知'
            };
            if (all.ExposureTime) {
                const val = parseFloat(all.ExposureTime);
                exif.shutter = (val >= 1) ? val.toFixed(1) + 's' : `1/${Math.round(1/val)}s`;
            } else exif.shutter = '未知';
            if (all.FocalLength) exif.focal = parseFloat(all.FocalLength).toFixed(1) + 'mm'; else exif.focal = '未知';
            if (all.GPSLatitude && all.GPSLongitude) {
                const toDecimal = (dms, ref) => {
                    let dec = (dms[0].numerator/dms[0].denominator) + (dms[1].numerator/dms[1].denominator/60) + (dms[2].numerator/dms[2].denominator/3600);
                    return (ref === 'S' || ref === 'W') ? -dec : dec;
                };
                exif.gps = { lat: toDecimal(all.GPSLatitude, all.GPSLatitudeRef), lng: toDecimal(all.GPSLongitude, all.GPSLongitudeRef) };
            }
            container.innerHTML = formatExifHtml(exif, 'JS');
        });
    };

    img.addEventListener('managed-image-loaded', runExif, { once: true });
    if (img.classList.contains('loaded') && !img.src.startsWith('data:image/gif;base64')) {
        runExif();
    }
}

function formatExifHtml(exif, source) {
    if (!exif) return '<div class="col-12 text-muted small">無 EXIF 資訊</div>';
    let sourceHtml = source ? renderTemplate('tmpl_exif_source_label', {
        label: source === 'PHP' ? '後端 (PHP)' : '前端 (JS)',
        color: source === 'PHP' ? '#2e7d32' : '#ef6c00',
        bg: source === 'PHP' ? '#e8f5e9' : '#fff3e0'
    }) : '';
    const fields = [
        { label: '相機機型', value: `${exif.make || ''} ${exif.model || ''}`.trim() || '未知' },
        { label: '快門速度', value: exif.shutter || '未知' },
        { label: '焦距', value: exif.focal || '未知' },
        { label: '光圈值', value: exif.aperture || '未知' },
        { label: '感光度', value: `ISO ${exif.iso || '未知'}` },
        { label: '拍攝日期', value: exif.date || '未知' }
    ];
    let itemsHtml = '';
    fields.forEach(f => { itemsHtml += renderTemplate('tmpl_exif_item', f); });
    const listHtml = renderTemplate('tmpl_exif_vertical_list', { items: itemsHtml });
    if (exif.gps && exif.gps.lat) {
        const gpsHtml = renderTemplate('tmpl_gps_block', {
            lat: exif.gps.lat.toFixed(6), lng: exif.gps.lng.toFixed(6),
            mapLink: `https://www.google.com/maps/search/?api=1&query=${exif.gps.lat},${exif.gps.lng}`,
            embedUrl: `https://maps.google.com/maps?q=${exif.gps.lat},${exif.gps.lng}&z=15&output=embed`
        });
        return renderTemplate('tmpl_exif_split_layout', { left_content: sourceHtml + listHtml, right_content: gpsHtml });
    }
    return sourceHtml + listHtml;
}

/* =========================================
   Modal & Utilities
   ========================================= */
function openModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
}
function closeModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
}
document.addEventListener('keydown', (e) => { if (e.key === "Escape") document.querySelectorAll('.modal-overlay.show').forEach(m => closeModal(m.id)); });
function openPhotoModal(src) { 
    const img = document.getElementById('modal-full-img'); 
    if (img) { 
        img.src = src; 
        openModal('photoModal'); 
    } 
}

function base62Encode(num) {
    const charset = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    let n = BigInt(num); if (n === 0n) return charset[0];
    let res = ''; while (n > 0n) { res = charset[Number(n % 62n)] + res; n = n / 62n; }
    return res;
}
function getObfuscatedSlug(id) {
    let n = (BigInt(id) * 1580030173n) % 2147483648n;
    return base62Encode(n ^ 87369521n);
}

let currentShareData = null;
function openShareModal(filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart) {
    const container = document.getElementById('share-links-container');
    container.innerHTML = '<div class="text-center py-3 text-muted">分析中...</div>';
    openModal('shareModal');
    const temp = new Image(); temp.src = originalImgSrc;
    temp.onload = () => { currentShareData = { filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart, realWidth: temp.naturalWidth }; updateShareLinks(); };
    temp.onerror = () => { currentShareData = { filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart, realWidth: 9999 }; updateShareLinks(); };
}

function updateShareLinks() {
    if (!currentShareData) return;
    const isOriginal = document.getElementById('toggle-original-url').checked;
    const { filename, originalImgSrc, shortIdStart, realWidth } = currentShareData;
    const baseHref = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
    const getAbs = (p) => new URL(p.startsWith('./') ? p.substring(2) : p, baseHref).href;
    const basePath = originalImgSrc.substring(0, originalImgSrc.lastIndexOf('/') + 1); 
    const thumbPath = basePath + 'Thumbnail/';
    const name = filename.substring(0, filename.lastIndexOf('.'));
    const ext = filename.substring(filename.lastIndexOf('.'));
    const sid = parseInt(shortIdStart);

    let html = "";
    [{l:'超大 (2048px)',s:'_3XL',o:1,w:1601},{l:'大型 (1600px)',s:'_2XL',o:2,w:1025},{l:'中型 (1024px)',s:'_XL',o:3,w:801},{l:'預覽 (800px)',s:'_L',o:4,w:641},{l:'小型 (640px)',s:'_M',o:5,w:321},{l:'極小 (320px)',s:'_S',o:6,w:0}]
    .forEach(s => {
        if (realWidth >= s.w) {
            const url = isOriginal ? getAbs(thumbPath + name + s.s + ext) : (baseHref + 'shorturl.php?i=' + getObfuscatedSlug(sid + s.o));
            html += renderTemplate('tmpl_share_item', { label: s.l, url: url });
        }
    });
    html += renderTemplate('tmpl_share_item', { label: `原始 (${realWidth}px)`, url: isOriginal ? getAbs(originalImgSrc) : (baseHref + 'shorturl.php?i=' + getObfuscatedSlug(sid)) });
    document.getElementById('share-links-container').innerHTML = html;
}

function copyToClipboard(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        const old = btn.innerText; btn.innerText = "已複製!";
        setTimeout(() => btn.innerText = old, 2000);
    });
}
