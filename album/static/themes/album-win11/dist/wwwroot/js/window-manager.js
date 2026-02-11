/**
 * Blazor Album Explorer - Window Manager & Parent Bridge (Ultra Robust Drag)
 */

window.dragElement = (element, handle, dotNetHelper, callbackName) => {
    let startX = 0, startY = 0, initialX = 0, initialY = 0;
    
    handle.onmousedown = dragMouseDown;

    function dragMouseDown(e) {
        e = e || window.event;
        if (e.target.closest('.wpf-control-btn')) return;
        
        e.preventDefault();
        // 記錄滑鼠起點
        startX = e.clientX;
        startY = e.clientY;
        // 記錄視窗起點 (無視 Blazor 當前的 style，直接抓實體座標)
        initialX = element.offsetLeft;
        initialY = element.offsetTop;

        document.onmouseup = closeDragElement;
        document.onmousemove = elementDrag;
        
        // 通知 Blazor 開始拖動 (可選)
        element.classList.add('is-dragging');
    }

    function elementDrag(e) {
        e = e || window.event;
        e.preventDefault();
        
        // 計算總位移量
        const deltaX = e.clientX - startX;
        const deltaY = e.clientY - startY;

        // 直接設定新位置 (基於初始位置 + 位移，不依賴每幀的 offsetTop)
        element.style.left = (initialX + deltaX) + "px";
        element.style.top = (initialY + deltaY) + "px";
    }

    function closeDragElement() {
        document.onmouseup = null;
        document.onmousemove = null;
        element.classList.remove('is-dragging');

        // 拖動結束後，一次性回報最終座標給 Blazor 存檔
        if (dotNetHelper && callbackName) {
            dotNetHelper.invokeMethodAsync(callbackName, element.offsetLeft, element.offsetTop);
        }
    }
};

window.registerGlobalKeyboardHandler = (dotNetHelper) => {
    if (window._keyHandler) document.removeEventListener('keydown', window._keyHandler);
    window._keyHandler = (e) => {
        if (e.key === 'Escape') dotNetHelper.invokeMethodAsync('OnKeyPress', 'Escape');
        else if (e.key === 'ArrowLeft') dotNetHelper.invokeMethodAsync('OnKeyPress', 'ArrowLeft');
        else if (e.key === 'ArrowRight') dotNetHelper.invokeMethodAsync('OnKeyPress', 'ArrowRight');
    };
    document.addEventListener('keydown', window._keyHandler);
};

/* =========================================
   Parent Resource Manager Bridge
   ========================================= */
(function() {
    function scanAndManagedLoad() {
        const manager = window.parent.albumDownloadManager;
        if (!manager) return;
        const images = document.querySelectorAll('img[data-managed-src]:not(.managed-init)');
        images.forEach(img => {
            img.classList.add('managed-init');
            const absUrl = new URL(img.getAttribute('data-managed-src'), window.location.href).href;
            manager.loadImage(absUrl, img);
        });
    }
    const observer = new MutationObserver((mutations) => {
        mutations.forEach(m => {
            if (m.type === 'attributes' && m.attributeName === 'data-managed-src') {
                m.target.classList.remove('managed-init');
            }
        });
        scanAndManagedLoad();
    });
    window.addEventListener('load', () => {
        scanAndManagedLoad();
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-managed-src'] });
    });
})();
