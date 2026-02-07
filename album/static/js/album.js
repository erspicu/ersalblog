/**
 * Baxermux Album System Core JS
 */

/**
 * 全螢幕相片檢視 (Modal)
 */
function openPhotoModal(src) {
    const modalImg = document.getElementById('modal-full-img');
    const photoModalEl = document.getElementById('photoModal');
    
    if (modalImg && photoModalEl) {
        modalImg.src = src;
        const myModal = new bootstrap.Modal(photoModalEl);
        myModal.show();
    }
}

/**
 * 首頁相簿列表載入 (使用樣板渲染，支援分頁)
 */
function loadAlbumList(apiUrl, page = 1) {
    const container = document.getElementById("album-list-container");
    const pagContainer = document.getElementById("pagination-container");
    const templateEl = document.getElementById("tmpl_index_album_item");
    
    if (!container || !templateEl) return;
    const template = templateEl.innerHTML;

    // 清空舊內容並顯示 Loading
    container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    if (pagContainer) pagContainer.innerHTML = "";

    fetch(`${apiUrl}?page=${page}`)
        .then(response => response.json())
        .then(data => {
            const albums = data.items;
            const pagination = data.pagination;

            if (!albums || albums.length === 0) {
                container.innerHTML = "<div class='col-12 text-center text-muted py-5'>目前沒有相簿</div>";
                return;
            }
            
            let html = "";
            albums.forEach(album => {
                let itemHtml = template;
                itemHtml = itemHtml.replace(/{{link}}/g, album.link);
                itemHtml = itemHtml.replace(/{{cover}}/g, album.cover);
                itemHtml = itemHtml.replace(/{{name}}/g, album.name);
                itemHtml = itemHtml.replace(/{{count}}/g, album.count);
                itemHtml = itemHtml.replace(/{{desc}}/g, album.desc || "&nbsp;");
                html += itemHtml;
            });
            container.innerHTML = html;

            // 渲染分頁按鈕
            if (pagContainer && pagination.totalPages > 1) {
                renderPagination(pagContainer, pagination, (newPage) => {
                    loadAlbumList(apiUrl, newPage);
                    // 同步捲動到頂部
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
        })
        .catch(err => {
            console.error("Error loading albums:", err);
            container.innerHTML = "<div class='col-12 text-danger text-center py-5'>載入相簿清單失敗</div>";
        });
}

/**
 * 渲染分頁按鈕 (Bootstrap Style)
 */
function renderPagination(container, pag, onPageClick) {
    let html = '<nav aria-label="Page navigation"><ul class="pagination pagination-sm">';
    
    // 上一頁
    html += `<li class="page-item ${pag.currentPage <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="${pag.currentPage > 1 ? `window.onPagClick(${pag.currentPage - 1})` : ''}"><i class="bi bi-chevron-left"></i></a>
             </li>`;

    // 頁碼
    for (let i = 1; i <= pag.totalPages; i++) {
        html += `<li class="page-item ${i === pag.currentPage ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="window.onPagClick(${i})">${i}</a>
                 </li>`;
    }

    // 下一頁
    html += `<li class="page-item ${pag.currentPage >= pag.totalPages ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="${pag.currentPage < pag.totalPages ? `window.onPagClick(${pag.currentPage + 1})` : ''}"><i class="bi bi-chevron-right"></i></a>
             </li>`;

    html += '</ul></nav>';
    container.innerHTML = html;

    // 全域回呼 (方便 inline onclick 調用)
    window.onPagClick = onPageClick;
}

/**
 * 開啟分享視窗並生成連結
 */
function openShareModal(filename, currentImgSrc, xlImgSrc, originalImgSrc) {
    const container = document.getElementById('share-links-container');
    if (!container) return;

    // 計算基礎路徑 (絕對網址)
    const baseHref = window.location.href.substring(0, window.location.href.lastIndexOf('/') + 1);
    const getAbs = (rel) => new URL(rel, baseHref).href;

    // 推算路徑：基於 thumbL 推算其他尺寸
    const lastUnderscore = currentImgSrc.lastIndexOf('_');
    const basePath = currentImgSrc.substring(0, lastUnderscore + 1);
    const ext = filename.substring(filename.lastIndexOf('.'));

    const sizes = [
        { label: '超大尺寸 (2048px)', url: getAbs(xlImgSrc) },
        { label: '大型尺寸 (1600px)', url: getAbs(currentImgSrc) },
        { label: '中型尺寸 (1024px)', url: getAbs(basePath + 'thumbM' + ext) },
        { label: '預覽尺寸 (800px)', url: getAbs(basePath + 'thumb' + ext) },
        { label: '原始圖檔 (高品質)', url: getAbs(originalImgSrc) }
    ];

    let html = "";
    sizes.forEach(size => {
        html += `
        <div class="share-item">
            <label class="form-label small fw-bold mb-1">${size.label}</label>
            <div class="input-group input-group-sm">
                <input type="text" class="form-control bg-light" value="${size.url}" readonly>
                <button class="btn btn-primary" onclick="copyToClipboard(this, '${size.url}')">複製</button>
            </div>
        </div>`;
    });

    container.innerHTML = html;
    const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
    shareModal.show();
}

/**
 * 複製到剪貼簿
 */
function copyToClipboard(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        const originalText = btn.innerText;
        btn.innerText = "已複製!";
        btn.classList.replace('btn-primary', 'btn-success');
        setTimeout(() => {
            btn.innerText = originalText;
            btn.classList.replace('btn-success', 'btn-primary');
        }, 2000);
    }).catch(err => {
        console.error('Copy failed:', err);
        alert('複製失敗，請手動選取複製');
    });
}