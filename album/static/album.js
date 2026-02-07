/**
 * Baxermux Album System Core JS (No-Bootstrap Version)
 */

/* --- Modal Logic --- */
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

/**
 * 全螢幕相片檢視
 */
function openPhotoModal(src) {
    const modalImg = document.getElementById('modal-full-img');
    if (modalImg) {
        modalImg.src = src;
        openModal('photoModal');
    }
}

/**
 * Base62 編碼
 */
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

/**
 * 混淆 Slug 生成
 */
function getObfuscatedSlug(id) {
    const MOD = 2147483648n;
    const PRIME = 1580030173n;
    const MASK = 87369521n;
    let n = BigInt(id);
    n = (n * PRIME) % MOD;
    n = n ^ MASK;
    return base62Encode(n);
}

// 分享視窗暫存資料
let currentShareData = null;

/**
 * 開啟分享視窗
 */
function openShareModal(filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart) {
    // 儲存當前資料以便切換時使用
    currentShareData = { filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart };
    
    // 初始化勾選狀態 (預設為短網址)
    const toggle = document.getElementById('toggle-original-url');
    if (toggle) toggle.checked = false;

    updateShareLinks();
    openModal('shareModal');
}

/**
 * 更新分享連結列表
 */
function updateShareLinks() {
    if (!currentShareData) return;
    const container = document.getElementById('share-links-container');
    const isOriginal = document.getElementById('toggle-original-url').checked;
    
    const { filename, currentImgSrc, xlImgSrc, originalImgSrc, shortIdStart } = currentShareData;
    const baseHref = window.location.href.substring(0, window.location.href.lastIndexOf('/') + 1);
    const getAbs = (rel) => new URL(rel, baseHref).href;

    // 推算縮圖基礎路徑
    const lastUnderscore = currentImgSrc.lastIndexOf('_');
    const basePath = currentImgSrc.substring(0, lastUnderscore + 1);
    const ext = filename.substring(filename.lastIndexOf('.'));
    const sid = parseInt(shortIdStart);

    let sizes = [];
    const rootUrl = baseHref.substring(0, baseHref.indexOf('/view/'));

    if (isOriginal) {
        // 模式 A: 實體檔案絕對路徑
        sizes = [
            { label: '超大尺寸 (2048px)', url: getAbs(xlImgSrc) },
            { label: '大型尺寸 (1600px)', url: getAbs(currentImgSrc) },
            { label: '中型尺寸 (1024px)', url: getAbs(basePath + 'thumbM' + ext) },
            { label: '預覽尺寸 (800px)', url: getAbs(basePath + 'thumb' + ext) },
            { label: '原始圖檔 (高品質)', url: getAbs(originalImgSrc) }
        ];
    } else {
        // 模式 B: 混淆短網址
        const shortBase = rootUrl + '/shorturl.php?i=';
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

/**
 * 首頁載入
 */
function loadAlbumList(apiUrl, page = 1) {
    const container = document.getElementById("album-list-container");
    const pagContainer = document.getElementById("pagination-container");
    const templateEl = document.getElementById("tmpl_index_album_item");
    if (!container || !templateEl) return;

    container.innerHTML = '<div class="text-center py-5" style="grid-column: 1/-1;">載入中...</div>';
    fetch(`${apiUrl}?page=${page}`)
        .then(response => response.json())
        .then(data => {
            const albums = data.items;
            const pagination = data.pagination;
            if (!albums || albums.length === 0) {
                container.innerHTML = "<div class='text-center text-muted py-5' style='grid-column: 1/-1;'>目前沒有相簿</div>";
                return;
            }
            let html = "";
            albums.forEach(album => {
                let itemHtml = templateEl.innerHTML;
                itemHtml = itemHtml.replace(/{{link}}/g, album.link);
                itemHtml = itemHtml.replace(/{{cover}}/g, album.cover);
                itemHtml = itemHtml.replace(/{{name}}/g, album.name);
                itemHtml = itemHtml.replace(/{{count}}/g, album.count);
                itemHtml = itemHtml.replace(/{{desc}}/g, album.desc || "&nbsp;");
                html += itemHtml;
            });
            container.innerHTML = html;
            if (pagContainer && pagination.totalPages > 1) {
                renderPagination(pagContainer, pagination, (newPage) => {
                    loadAlbumList(apiUrl, newPage);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
        });
}

function renderPagination(container, pag, onPageClick) {
    let html = '<div class="pagination">';
    const addLink = (page, content, active = false, disabled = false) => {
        if (disabled) return `<span class="page-link disabled">${content}</span>`;
        if (active) return `<span class="page-link active">${content}</span>`;
        return `<a class="page-link" href="javascript:void(0)" onclick="window.onPagClick(${page})">${content}</a>`;
    };
    html += addLink(pag.currentPage - 1, '<i class="bi bi-chevron-left"></i>', false, pag.currentPage <= 1);
    for (let i = 1; i <= pag.totalPages; i++) {
        html += addLink(i, i, i === pag.currentPage);
    }
    html += addLink(pag.currentPage + 1, '<i class="bi bi-chevron-right"></i>', false, pag.currentPage >= pag.totalPages);
    html += '</div>';
    container.innerHTML = html;
    window.onPagClick = onPageClick;
}