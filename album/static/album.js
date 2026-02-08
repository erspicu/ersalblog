/**
 * Baxermux Album System Core JS (SPA Version - Client Side Pagination Only)
 */

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
    apiType: (typeof albumConfig !== 'undefined' && albumConfig.api_type) ? albumConfig.api_type : 'json'
};

/* =========================================
   Router & Initialization
   ========================================= */
document.addEventListener("DOMContentLoaded", function() {
    initRouter();
});

window.addEventListener("hashchange", function() {
    handleRoute();
});

function initRouter() {
    handleRoute();
}

function handleRoute() {
    const hash = window.location.hash;
    const params = new URLSearchParams(hash.substring(1)); 

    const album = params.get('album');
    const photo = params.get('photo');
    const page = parseInt(params.get('page')) || 1;

    AppState.currentPage = page;

    if (photo && album) {
        loadPhotoView(album, photo);
    } else if (album) {
        loadAlbumView(album, page);
    } else {
        loadHomeView(page);
    }
}

/* =========================================
   Data Fetching
   ========================================= */
function getApiUrl(type, params = {}) {
    const isJson = AppState.apiType === 'json';
    
    if (type === 'home') {
        return isJson ? 'api/json/index.json' : `api/api_album.php?action=list_albums`;
    } else if (type === 'album') {
        const name = params.name;
        if (isJson) {
            return `api/json/${name}.json`;
        } else {
            return `api/api_album.php?action=get_album&album=${encodeURIComponent(name)}`;
        }
    }
    return '';
}

async function fetchHomeData() {
    if (AppState.homeData) return AppState.homeData;
    
    const url = getApiUrl('home');
    try {
        const resp = await fetch(url);
        if (!resp.ok) throw new Error('Network error');
        const data = await resp.json();
        AppState.homeData = data;
        return data;
    } catch (e) {
        console.error("Fetch Home Error:", e);
        return { items: [] };
    }
}

async function fetchAlbumData(albumName) {
    if (AppState.albumDataCache[albumName]) return AppState.albumDataCache[albumName];

    const url = getApiUrl('album', { name: albumName });
    try {
        const resp = await fetch(url);
        if (!resp.ok) throw new Error('Album not found');
        const data = await resp.json();
        AppState.albumDataCache[albumName] = data;
        return data;
    } catch (e) {
        console.error("Fetch Album Error:", e);
        return null;
    }
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
    if (!container) return;
    
    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '<div class="pagination">';
    const cur = pagination.currentPage;
    const total = pagination.totalPages;

    if (cur > 1) {
        html += `<span class="page-item"><a class="page-link" href="${baseUrlHash}&page=${cur - 1}"><i class="bi bi-chevron-left"></i></a></span>`;
    } else {
        html += `<span class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></span>`;
    }

    for (let i = 1; i <= total; i++) {
        const activeClass = (i === cur) ? 'active' : '';
        html += `<span class="page-item ${activeClass}"><a class="page-link" href="${baseUrlHash}&page=${i}">${i}</a></span>`;
    }

    if (cur < total) {
        html += `<span class="page-item"><a class="page-link" href="${baseUrlHash}&page=${cur + 1}"><i class="bi bi-chevron-right"></i></a></span>`;
    } else {
        html += `<span class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></span>`;
    }

    html += '</div>';
    container.innerHTML = html;
}

/* --- Home View --- */
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

    const pagination = {
        currentPage: page,
        totalPages: totalPages,
        totalItems: totalItems
    };
    
    const templateEl = document.getElementById("tmpl_index_album_item");
    if (!templateEl) return;

    if (pagedItems.length === 0 && page === 1) {
        setContent("<div class='text-center text-muted py-5'>目前沒有相簿</div>");
        return;
    }

    let listHtml = '<div class="album-grid" id="album-list-container">';
    pagedItems.forEach(album => {
        let itemHtml = templateEl.innerHTML;
        const link = `#album=${encodeURIComponent(album.id || album.name)}`;
        itemHtml = itemHtml.replace(/{{link}}/g, link);
        itemHtml = itemHtml.replace(/{{cover}}/g, album.cover);
        itemHtml = itemHtml.replace(/{{name}}/g, album.name);
        itemHtml = itemHtml.replace(/{{count}}/g, album.count);
        itemHtml = itemHtml.replace(/{{desc}}/g, album.desc || "&nbsp;");
        listHtml += itemHtml;
    });
    listHtml += '</div>';

    setContent(listHtml);
    renderPaginationUI(pagination, '#');
    document.title = "相簿首頁 - Baxermux的相簿";
    window.scrollTo(0, 0);
}

/* --- Album View --- */
async function loadAlbumView(albumName, page = 1) {
    AppState.currentView = 'album';
    setContent('<div class="text-center py-5">載入照片中...</div>');

    const data = await fetchAlbumData(albumName);
    if (!data) {
        setContent('<div class="text-center text-danger py-5">找不到相簿或載入失敗</div>');
        return;
    }

    let rawPhotos = data.photos || [];
    const totalItems = rawPhotos.length;
    const totalPages = Math.ceil(totalItems / AppState.itemsPerPage);
    const start = (page - 1) * AppState.itemsPerPage;
    const pagedPhotos = rawPhotos.slice(start, start + AppState.itemsPerPage);

    const pagination = {
        currentPage: page,
        totalPages: totalPages,
        totalItems: totalItems
    };

    const headerHtml = `
    <div class="album-header-box">
        <h2 class="fw-bold mb-2" style="font-size:1.25rem">${data.name}</h2>
        ${data.desc ? `<p class="text-muted small mb-0">${data.desc}</p>` : ''}
    </div>`;
    setHeader(headerHtml);

    const controlsHtml = `
        <div class="d-flex align-center justify-between mb-4">
            <nav class="breadcrumb">
                <div class="breadcrumb-item"><a href="#">首頁</a></div>
                <div class="breadcrumb-item active">${data.name}</div>
            </nav>
            <div class="d-flex align-center gap-2">
                <span class="badge"><i class="bi bi-image"></i> ${totalItems} 張相片</span>
                <a href="#" class="btn btn-outline"><i class="bi bi-house-door"></i> 返回首頁</a>
            </div>
        </div>
    `;

    const templateEl = document.getElementById("tmpl_album_photo_item");
    let gridHtml = '<div class="album-grid">';
    
    if (pagedPhotos.length > 0) {
        pagedPhotos.forEach(photo => {
            let itemHtml = templateEl.innerHTML;
            const photoLink = `#album=${encodeURIComponent(albumName)}&photo=${encodeURIComponent(photo.filename)}`;
            const imgSrc = photo.thumb || photo.src;
            
            itemHtml = itemHtml.replace(/{{photoPageLink}}/g, photoLink);
            itemHtml = itemHtml.replace(/{{imgSrc}}/g, imgSrc);
            itemHtml = itemHtml.replace(/{{filename}}/g, photo.filename);
            itemHtml = itemHtml.replace(/{{photoDesc}}/g, photo.title || photo.filename);
            gridHtml += itemHtml;
        });
    } else {
        gridHtml += '<div class="col-12 text-muted">此相簿沒有照片</div>';
    }
    gridHtml += '</div>';

    setContent(controlsHtml + gridHtml);
    renderPaginationUI(pagination, `#album=${encodeURIComponent(albumName)}`);
    document.title = `${data.name} - Baxermux的相簿`;
    window.scrollTo(0, 0);
}

/* --- Photo View --- */
async function loadPhotoView(albumName, photoName) {
    AppState.currentView = 'photo';
    setHeader(''); 
    document.getElementById('pagination-container').innerHTML = '';

    const data = await fetchAlbumData(albumName);
    if (!data || !data.photos) {
        setContent('<div class="text-center text-danger py-5">無法載入相簿資料</div>');
        return;
    }

    const photoIndex = data.photos.findIndex(p => p.filename === photoName);
    if (photoIndex === -1) {
        setContent('<div class="text-center text-danger py-5">找不到照片</div>');
        return;
    }

    const photo = data.photos[photoIndex];
    const templateEl = document.getElementById("tmpl_photo_detail_view");
    let viewHtml = templateEl.innerHTML;

    const prevIndex = (photoIndex - 1 + data.photos.length) % data.photos.length;
    const nextIndex = (photoIndex + 1) % data.photos.length;
    const prevPhoto = data.photos[prevIndex];
    const nextPhoto = data.photos[nextIndex];

    const prevLink = `#album=${encodeURIComponent(albumName)}&photo=${encodeURIComponent(prevPhoto.filename)}`;
    const nextLink = `#album=${encodeURIComponent(albumName)}&photo=${encodeURIComponent(nextPhoto.filename)}`;
    
    const backPage = Math.floor(photoIndex / AppState.itemsPerPage) + 1;
    const albumLink = `#album=${encodeURIComponent(albumName)}&page=${backPage}`;

    const thumbL = photo.thumbL || photo.src;
    const thumbXL = photo.thumbXL || photo.src;
    const original = photo.src; 

    viewHtml = viewHtml.replace(/{{pathToHome}}album\.html/g, '#'); 
    viewHtml = viewHtml.replace(/{{albumName}}/g, data.name);
    viewHtml = viewHtml.replace(/{{filename}}/g, photo.filename);
    viewHtml = viewHtml.replace(/{{prevLink}}/g, prevLink);
    viewHtml = viewHtml.replace(/{{nextLink}}/g, nextLink);
    viewHtml = viewHtml.replace(/{{imgSrc}}/g, thumbL); 
    viewHtml = viewHtml.replace(/{{imgSrcXL}}/g, thumbXL);
    viewHtml = viewHtml.replace(/{{imgSrcOriginal}}/g, original);
    viewHtml = viewHtml.replace(/{{shortIdStart}}/g, photo.shortIdStart || '0');
    viewHtml = viewHtml.replace(/{{photoTitle}}/g, photo.title || photo.filename);
    viewHtml = viewHtml.replace(/{{photoDesc}}/g, photo.desc || '');

    viewHtml = viewHtml.replace(new RegExp(`href="../${escapeRegExp(data.name)}.html"`, 'g'), `href="${albumLink}"`);

    const exifHtml = formatExifHtml(photo.exif);
    viewHtml = viewHtml.replace(/{{exif_info}}/g, exifHtml);

    setContent(viewHtml);
    document.title = `${photo.filename} - ${data.name}`;
    window.scrollTo(0, 0);
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function formatExifHtml(exif) {
    if (!exif) return '<div class="col-12 text-muted small">無 EXIF 資訊</div>';

    const make = exif.make || '未知';
    const model = exif.model || '未知';
    const aperture = exif.aperture || '未知';
    const shutter = exif.shutter || '未知';
    const iso = exif.iso || '未知';
    const focal = exif.focal || '未知';
    const date = exif.date || '未知';

    return `
        <div class="col-6 col-md-4 exif-item"><span class="text-muted small d-block">相機機型</span><strong>${make} ${model}</strong></div>
        <div class="col-6 col-md-4 exif-item"><span class="text-muted small d-block">光圈值</span><strong>${aperture}</strong></div>
        <div class="col-6 col-md-4 exif-item"><span class="text-muted small d-block">快門速度</span><strong>${shutter}</strong></div>
        <div class="col-6 col-md-4 exif-item"><span class="text-muted small d-block">感光度</span><strong>ISO ${iso}</strong></div>
        <div class="col-6 col-md-4 exif-item"><span class="text-muted small d-block">焦距</span><strong>${focal}</strong></div>
        <div class="col-6 col-md-4 exif-item"><span class="text-muted small d-block">拍攝日期</span><strong>${date}</strong></div>
    `;
}

/* =========================================
   Modal & Utilities
   ========================================= */

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden'; 
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        document.querySelectorAll('.modal-overlay.show').forEach(modal => {
            closeModal(modal.id);
        });
    }
});

function openPhotoModal(src) {
    const modalImg = document.getElementById('modal-full-img');
    if (modalImg) {
        modalImg.src = src;
        openModal('photoModal');
    }
}

function base62Encode(num) {
    const charset = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    let n = BigInt(num);
    if (n === 0n) return charset[0];
    let result = '';
    const base = 62n;
    while (n > 0n) {
        let rem = n % base;
        result = charset[Number(rem)] + result;
        n = n / base;
    }
    return result;
}

function getObfuscatedSlug(id) {
    const MOD = 2147483648n;
    const PRIME = 1580030173n;
    const MASK = 87369521n;
    let n = BigInt(id);
    n = (n * PRIME) % MOD;
    n = n ^ MASK;
    return base62Encode(n);
}

let currentShareData = null;

function openShareModal(filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart) {
    currentShareData = { filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart };
    const toggle = document.getElementById('toggle-original-url');
    if (toggle) toggle.checked = false;
    updateShareLinks();
    openModal('shareModal');
}

function updateShareLinks() {
    if (!currentShareData) return;
    const container = document.getElementById('share-links-container');
    const isOriginal = document.getElementById('toggle-original-url').checked;
    
    const { filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart } = currentShareData;
    const baseHref = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
    
    const getAbs = (relPath) => {
        if(relPath.startsWith('./')) relPath = relPath.substring(2);
        return new URL(relPath, baseHref).href;
    }

    const basePath = originalImgSrc.substring(0, originalImgSrc.lastIndexOf('/') + 1); 
    const thumbBasePath = basePath + 'Thumbnail/';
    const namePart = filename.substring(0, filename.lastIndexOf('.'));
    const ext = filename.substring(filename.lastIndexOf('.'));
    const sid = parseInt(shortIdStart);

    let sizes = [];
    const shortBase = baseHref + 'shorturl.php?i=';

    if (isOriginal) {
        sizes = [
            { label: '超大尺寸 (2048px)', url: getAbs(thumbBasePath + namePart + '_thumbXL' + ext) },
            { label: '大型尺寸 (1600px)', url: getAbs(thumbBasePath + namePart + '_thumbL' + ext) },
            { label: '中型尺寸 (1024px)', url: getAbs(thumbBasePath + namePart + '_thumbM' + ext) },
            { label: '預覽尺寸 (800px)', url: getAbs(thumbBasePath + namePart + '_thumb' + ext) },
            { label: '原始圖檔 (高品質)', url: getAbs(originalImgSrc) }
        ];
    } else {
        sizes = [
            { label: '超大尺寸 (2048px)', url: shortBase + getObfuscatedSlug(sid + 1) },
            { label: '大型尺寸 (1600px)', url: shortBase + getObfuscatedSlug(sid + 2) },
            { label: '中型尺寸 (1024px)', url: shortBase + getObfuscatedSlug(sid + 3) },
            { label: '預覽尺寸 (800px)', url: shortBase + getObfuscatedSlug(sid + 4) },
            { label: '原始圖檔 (高品質)', url: shortBase + getObfuscatedSlug(sid) }
        ];
    }

    let html = "";
    sizes.forEach(size => {
        html += `
        <div class="share-item">
            <label class="small fw-bold" style="display:block; margin-bottom:4px;">${size.label}</label>
            <div class="input-group">
                <input type="text" value="${size.url}" readonly>
                <button class="btn btn-primary" onclick="copyToClipboard(this, '${size.url}')">複製</button>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

function copyToClipboard(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        const originalText = btn.innerText;
        btn.innerText = "已複製!";
        setTimeout(() => {
            btn.innerText = originalText;
        }, 2000);
    });
}
