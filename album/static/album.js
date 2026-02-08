/**
 * Baxermux Album System Core JS (SPA Version - Client Side Pagination & Dynamic Share Filtering)
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

    const hasBackendExif = photo.exif && Object.keys(photo.exif).length > 0;
    const exifHtml = hasBackendExif ? formatExifHtml(photo.exif, 'PHP') : '<div class="col-12 text-muted small">正在載入技術資訊...</div>';
    viewHtml = viewHtml.replace(/{{exif_info}}/g, exifHtml);

    setContent(viewHtml);
    document.title = `${photo.filename} - ${data.name}`;
    window.scrollTo(0, 0);

    // 如果後端沒有提供 EXIF，嘗試在前端抓取
    if (!hasBackendExif) {
        tryFetchExifClientSide();
    }
}

function tryFetchExifClientSide() {
    const img = document.getElementById('photo-main-viewer');
    const container = document.getElementById('exif-info-container');
    if (!img || !container || typeof EXIF === 'undefined') return;

    const runExif = function() {
        EXIF.getData(img, function() {
            const all = EXIF.getAllTags(this);
            if (!all || Object.keys(all).length === 0) return;

            // 轉換數據格式以符合介面
            const exif = {
                make: all.Make || '',
                model: all.Model || '',
                aperture: all.FNumber ? `f/${all.FNumber.toFixed(1)}` : '未知',
                iso: all.ISOSpeedRatings || '未知',
                date: all.DateTimeOriginal || all.DateTime || '未知'
            };

            // 快門速度處理
            if (all.ExposureTime) {
                const val = parseFloat(all.ExposureTime);
                exif.shutter = (val >= 1) ? val.toFixed(1) + 's' : `1/${Math.round(1/val)}s`;
            } else {
                exif.shutter = '未知';
            }

            // 焦距處理
            if (all.FocalLength) {
                exif.focal = parseFloat(all.FocalLength).toFixed(1) + 'mm';
            } else {
                exif.focal = '未知';
            }

            // GPS 處理 (JS 版)
            if (all.GPSLatitude && all.GPSLongitude) {
                const toDecimal = (dms, ref) => {
                    const deg = dms[0].numerator / dms[0].denominator;
                    const min = dms[1].numerator / dms[1].denominator;
                    const sec = dms[2].numerator / dms[2].denominator;
                    let dec = deg + (min / 60) + (sec / 3600);
                    if (ref === 'S' || ref === 'W') dec = -dec;
                    return dec;
                };
                exif.gps = {
                    lat: toDecimal(all.GPSLatitude, all.GPSLatitudeRef),
                    lng: toDecimal(all.GPSLongitude, all.GPSLongitudeRef)
                };
            }

            container.innerHTML = formatExifHtml(exif, 'JS');
        });
    };

    // 確保圖片載入完成才執行
    if (img.complete) {
        runExif();
    } else {
        img.onload = runExif;
    }
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function formatExifHtml(exif, source) {
    if (!exif || Object.keys(exif).length === 0) return '<div class="col-12 text-muted small">無 EXIF 資訊</div>';

    const make = exif.make || '未知';
    const model = exif.model || '未知';
    const aperture = exif.aperture || '未知';
    const shutter = exif.shutter || '未知';
    const iso = exif.iso || '未知';
    const focal = exif.focal || '未知';
    const date = exif.date || '未知';

    // 1. 來源標籤
    let sourceHtml = '';
    if (source) {
        const label = source === 'PHP' ? '後端 (PHP)' : '前端 (JS)';
        const color = source === 'PHP' ? '#2e7d32' : '#ef6c00';
        const bg = source === 'PHP' ? '#e8f5e9' : '#fff3e0';
        sourceHtml = `<div class="mb-2"><span style="font-size:0.65rem; padding:2px 8px; border-radius:12px; background:${bg}; color:${color}; font-weight:bold; display:inline-block;">來源: ${label}</span></div>`;
    }

    // 2. EXIF 參數清單 (改為條列式)
    const itemsHtml = `
        <div class="exif-vertical-list">
            <div class="mb-1"><span class="text-muted">相機機型</span> <strong>${make} ${model}</strong></div>
            <div class="mb-1"><span class="text-muted">快門速度</span> <strong>${shutter}</strong></div>
            <div class="mb-1"><span class="text-muted">焦距</span> <strong>${focal}</strong></div>
            <div class="mb-1"><span class="text-muted">光圈值</span> <strong>${aperture}</strong></div>
            <div class="mb-1"><span class="text-muted">感光度</span> <strong>ISO ${iso}</strong></div>
            <div class="mb-1"><span class="text-muted">拍攝日期</span> <strong>${date}</strong></div>
        </div>
    `;

    // 3. GPS 區塊處理
    const hasGps = exif.gps && exif.gps.lat !== undefined && exif.gps.lng !== undefined && exif.gps.lat !== null && exif.gps.lng !== null;
    
    if (hasGps) {
        const lat = exif.gps.lat;
        const lng = exif.gps.lng;
        const mapLink = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
        const embedUrl = `https://maps.google.com/maps?q=${lat},${lng}&z=15&output=embed`;

        const gpsContent = `
            <div class="exif-item">
                <span class="text-muted small">GPS 座標 </span>
                <strong>緯度: ${lat.toFixed(6)}, 經度: ${lng.toFixed(6)}</strong>
                
                <div class="map-preview-box mt-2" style="position:relative; height:360px; border-radius:8px; overflow:hidden; border:1px solid #ddd;">
                    <iframe width="100%" height="100%" src="${embedUrl}" frameborder="0" style="border:0;" allowfullscreen></iframe>
                    <a href="${mapLink}" target="_blank" class="map-overlay" style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.05); z-index:10; display:flex; align-items:center; justify-content:center; text-decoration:none; opacity:0; transition:opacity 0.3s;">
                        <span class="badge bg-primary text-white shadow"><i class="bi bi-box-arrow-up-right"></i> 開啟地圖</span>
                    </a>
                </div>
            </div>
            <style>
                .map-preview-box:hover .map-overlay { opacity: 1 !important; }
                @media (max-width: 768px) {
                    .exif-split-layout { flex-direction: column !important; }
                }
            </style>
        `;

        // 返回分割佈局
        return `
            <div class="exif-split-layout" style="display: flex; gap: 30px;">
                <div style="flex: 1;">
                    ${sourceHtml}
                    ${itemsHtml}
                </div>
                <div style="flex: 1.5;">
                    ${gpsContent}
                </div>
            </div>
        `;
    }

    // 無 GPS 時返回標準佈局
    return sourceHtml + itemsHtml;
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

/**
 * 開啟分享視窗 (具備尺寸偵測與過濾功能)
 */
function openShareModal(filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart) {
    const container = document.getElementById('share-links-container');
    container.innerHTML = '<div class="text-center py-3 text-muted">正在分析照片尺寸...</div>';
    openModal('shareModal');

    // 預先載入原圖以獲取真實尺寸
    const tempImg = new Image();
    tempImg.src = originalImgSrc;
    
    tempImg.onload = function() {
        const width = tempImg.naturalWidth;
        currentShareData = { filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart, realWidth: width };
        const toggle = document.getElementById('toggle-original-url');
        if (toggle) toggle.checked = false;
        updateShareLinks();
    };
    
    tempImg.onerror = function() {
        // 若偵測失敗則全開
        currentShareData = { filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart, realWidth: 99999 };
        const toggle = document.getElementById('toggle-original-url');
        if (toggle) toggle.checked = false;
        updateShareLinks();
    };
}

/**
 * 更新分享連結列表 (核心過濾邏輯)
 */
function updateShareLinks() {
    if (!currentShareData) return;
    const container = document.getElementById('share-links-container');
    const isOriginal = document.getElementById('toggle-original-url').checked;
    
    const { filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart, realWidth } = currentShareData;
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

    // 定義規格與過濾閾值 (minW: 原圖必須大於此寬度才會顯示該選項)
    const specs = [
        { label: '超大尺寸 (2048px)', suffix: '_thumbXL', offset: 1, minW: 1601 }, 
        { label: '大型尺寸 (1600px)', suffix: '_thumbL',  offset: 2, minW: 1025 },
        { label: '中型尺寸 (1024px)', suffix: '_thumbM',  offset: 3, minW: 801 },
        { label: '預覽尺寸 (800px)',  suffix: '_thumb',   offset: 4, minW: 321 },
        { label: '極小尺寸 (320px)',  suffix: '_thumbXS', offset: 5, minW: 0 }
    ];

    specs.forEach(spec => {
        // 如果原圖寬度夠大，或是該規格是最小的 XS，則顯示
        if (realWidth >= spec.minW) {
            const label = (realWidth < spec.minW * 1.2 && spec.minW > 0) ? `${spec.label} (接近原圖)` : spec.label;
            const url = isOriginal ? getAbs(thumbBasePath + namePart + spec.suffix + ext) : (shortBase + getObfuscatedSlug(sid + spec.offset));
            sizes.push({ label, url });
        }
    });

    // 原始圖檔永遠顯示
    const originalLabel = `原始圖檔 (${realWidth}px)`;
    const originalUrl = isOriginal ? getAbs(originalImgSrc) : (shortBase + getObfuscatedSlug(sid));
    sizes.push({ label: originalLabel, url: originalUrl });

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
