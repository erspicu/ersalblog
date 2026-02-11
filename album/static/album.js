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
     * 將下載任務加入佇列
     * @param {string} url 圖片網址
     * @param {string} filename 儲存檔名
     * @returns {Promise}
     */
    async download(url, filename) {
        return new Promise((resolve, reject) => {
            this.queue.push({ url, filename, resolve, reject });
            this.processQueue();
        });
    }

    async processQueue() {
        if (this.currentConcurrent >= this.maxConcurrent || this.queue.length === 0) {
            return;
        }

        this.currentConcurrent++;
        const { url, filename, resolve, reject } = this.queue.shift();

        console.log(`[DownloadManager] Starting: ${filename} (Active: ${this.currentConcurrent})`);

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const blob = await response.blob();
            
            // 觸發瀏覽器下載
            const blobUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(blobUrl);
            document.body.removeChild(a);

            resolve(filename);
        } catch (error) {
            console.error(`[DownloadManager] Failed: ${filename}`, error);
            reject(error);
        } finally {
            this.currentConcurrent--;
            console.log(`[DownloadManager] Finished: ${filename} (Active: ${this.currentConcurrent})`);
            this.processQueue();
        }
    }
}

// 全域共享實例
window.albumDownloadManager = new DownloadManager(3);

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
function renderTemplate(templateId, vars = {}) {
    const templateEl = document.getElementById(templateId);
    if (!templateEl) {
        console.error(`Template not found: ${templateId}`);
        return `<!-- Missing Template: ${templateId} -->`;
    }
    let html = templateEl.innerHTML;
    for (const key in vars) {
        const regex = new RegExp(`{{${key}}}`, 'g');
        html = html.replace(regex, vars[key]);
    }
    return html;
}

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

    let itemsHtml = '';
    const cur = pagination.currentPage;
    const total = pagination.totalPages;

    // Previous
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

    // Next
    itemsHtml += renderTemplate('tmpl_pagination_item', {
        link: (cur < total) ? `${baseUrlHash}&page=${cur + 1}` : '#',
        text: '<i class="bi bi-chevron-right"></i>',
        activeClass: '',
        disabledClass: (cur < total) ? '' : 'disabled'
    });

    container.innerHTML = renderTemplate('tmpl_pagination', { items: itemsHtml });
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
        listHtml += renderTemplate('tmpl_index_album_item', {
            link: `#album=${encodeURIComponent(album.id || album.name)}`,
            cover: album.cover,
            name: album.name,
            count: album.count,
            desc: album.desc || "&nbsp;"
        });
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

    const headerHtml = renderTemplate('tmpl_album_header', {
        name: data.name,
        desc_html: data.desc_html || ''
    });
    setHeader(headerHtml);

    const controlsHtml = renderTemplate('tmpl_album_controls', {
        name: data.name,
        total: totalItems
    });

    let gridHtml = '<div class="album-grid">';
    
    if (pagedPhotos.length > 0) {
        pagedPhotos.forEach(photo => {
            const photoLink = `#album=${encodeURIComponent(albumName)}&photo=${encodeURIComponent(photo.filename)}`;
            const imgSrc = photo.thumb || photo.src;
            
            gridHtml += renderTemplate('tmpl_album_photo_item', {
                photoPageLink: photoLink,
                imgSrc: imgSrc,
                filename: photo.filename,
                photoDesc: photo.title || photo.filename
            });
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

    const hasBackendExif = photo.exif && Object.keys(photo.exif).length > 0;
    const exifHtml = hasBackendExif ? formatExifHtml(photo.exif, 'PHP') : '<div class="col-12 text-muted small">正在載入技術資訊...</div>';

    viewHtml = renderTemplate('tmpl_photo_detail_view', {
        pathToHome: '#',
        albumName: data.name,
        albumLink: albumLink,
        filename: photo.filename,
        prevLink: prevLink,
        nextLink: nextLink,
        imgSrc: thumbL,
        imgSrcXL: thumbXL,
        imgSrcOriginal: original,
        shortIdStart: photo.shortIdStart || '0',
        photoTitle: photo.title || photo.filename,
        photoDesc: photo.desc || '',
        exif_info: exifHtml
    });

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

    // 1. 來源標籤
    let sourceHtml = '';
    if (source) {
        sourceHtml = renderTemplate('tmpl_exif_source_label', {
            label: source === 'PHP' ? '後端 (PHP)' : '前端 (JS)',
            color: source === 'PHP' ? '#2e7d32' : '#ef6c00',
            bg: source === 'PHP' ? '#e8f5e9' : '#fff3e0'
        });
    }

    // 2. EXIF 參數清單
    const fields = [
        { label: '相機機型', value: `${exif.make || '未知'} ${exif.model || '未知'}` },
        { label: '快門速度', value: exif.shutter || '未知' },
        { label: '焦距', value: exif.focal || '未知' },
        { label: '光圈值', value: exif.aperture || '未知' },
        { label: '感光度', value: `ISO ${exif.iso || '未知'}` },
        { label: '拍攝日期', value: exif.date || '未知' }
    ];

    let itemsHtml = '';
    fields.forEach(f => {
        itemsHtml += renderTemplate('tmpl_exif_item', f);
    });
    const listHtml = renderTemplate('tmpl_exif_vertical_list', { items: itemsHtml });

    // 3. GPS 區塊處理
    const hasGps = exif.gps && exif.gps.lat !== undefined && exif.gps.lng !== undefined && exif.gps.lat !== null && exif.gps.lng !== null;
    
    if (hasGps) {
        const gpsHtml = renderTemplate('tmpl_gps_block', {
            lat: exif.gps.lat.toFixed(6),
            lng: exif.gps.lng.toFixed(6),
            mapLink: `https://www.google.com/maps/search/?api=1&query=${exif.gps.lat},${exif.gps.lng}`,
            embedUrl: `https://maps.google.com/maps?q=${exif.gps.lat},${exif.gps.lng}&z=15&output=embed`
        });

        // 返回分割佈局
        return renderTemplate('tmpl_exif_split_layout', {
            left_content: sourceHtml + listHtml,
            right_content: gpsHtml
        });
    }

    // 無 GPS 時返回標準佈局
    return sourceHtml + listHtml;
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
        html += renderTemplate('tmpl_share_item', {
            label: size.label,
            url: size.url
        });
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
