/**
 * Blazor Album Explorer - Window Manager & Parent Bridge
 */

window.dragElement = (element, handle, dotNetHelper, callbackName) => {
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    handle.onmousedown = dragMouseDown;

    function dragMouseDown(e) {
        e = e || window.event;
        if (e.target.closest('.wpf-control-btn')) return;
        e.preventDefault();
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.onmouseup = closeDragElement;
        document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
        e = e || window.event;
        e.preventDefault();
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        element.style.top = (element.offsetTop - pos2) + "px";
        element.style.left = (element.offsetLeft - pos1) + "px";
    }

    function closeDragElement() {
        document.onmouseup = null;
        document.onmousemove = null;
        if (dotNetHelper && callbackName) {
            dotNetHelper.invokeMethodAsync(callbackName, element.offsetLeft, element.offsetTop);
        }
    }
};

window.registerGlobalKeyboardHandler = (dotNetHelper) => {
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') dotNetHelper.invokeMethodAsync('OnKeyPress', 'Escape');
        else if (e.key === 'ArrowLeft') dotNetHelper.invokeMethodAsync('OnKeyPress', 'ArrowLeft');
        else if (e.key === 'ArrowRight') dotNetHelper.invokeMethodAsync('OnKeyPress', 'ArrowRight');
    });
};

/* =========================================
   Parent Resource Manager Bridge
   ========================================= */
(function() {
    function scanAndManagedLoad() {
        // 尋找父視窗的管理員
        const manager = window.parent.albumDownloadManager;
        if (!manager) return;

        const images = document.querySelectorAll('img[data-managed-src]:not(.managed-init)');
        images.forEach(img => {
            img.classList.add('managed-init');
            const relUrl = img.getAttribute('data-managed-src');
            // 【關鍵修正】將相對路徑轉換為絕對 URL，避免父子視窗路徑深度不一致的問題
            const absUrl = new URL(relUrl, window.location.href).href;
            manager.loadImage(absUrl, img);
        });
    }

    // 監聽 DOM 變化 (適配 Blazor 的動態渲染)
    const observer = new MutationObserver(() => scanAndManagedLoad());
    
    window.addEventListener('load', () => {
        scanAndManagedLoad();
        observer.observe(document.body, { 
            childList: true, 
            subtree: true, 
            attributes: true, 
            attributeFilter: ['data-managed-src'] 
        });
    });
})();
