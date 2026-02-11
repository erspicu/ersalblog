/**
 * Album Service - Windows 11 Theme Plugin
 * Ported from BlazorAlbumExplorer UI Logic
 */
(function() {
    console.log("Win11 Theme Plugin Initializing...");

    // 視窗管理邏輯
    function setupDragging(element, handle) {
        var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        handle.onmousedown = dragMouseDown;

        function dragMouseDown(e) {
            e = e || window.event;
            // 如果點擊的是控制按鈕則不觸發拖拽
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
        }
    }

    function initWin11Environment() {
        // 1. 建立桌面背景
        const desktop = document.createElement('div');
        desktop.className = 'desktop';
        document.body.prepend(desktop);

        // 2. 建立「啟動 Windows」水印
        const watermark = document.createElement('div');
        watermark.className = 'activation-watermark';
        watermark.innerHTML = `
            <div class="line-1">啟動 Experimental Project</div>
            <div class="line-2">移至 [設定] 以啟動此相簿專案。</div>
            <div class="line-2" style="margin-top:5px; opacity: 0.8;">此為實驗性質專案，仍在建立中。</div>
        `;
        document.body.appendChild(watermark);

        // 3. 建立工作列
        const taskbar = document.createElement('div');
        taskbar.className = 'taskbar';
        taskbar.innerHTML = `
            <div class="start-btn" title="開始"><i class="fa-brands fa-windows"></i></div>
        `;
        document.body.appendChild(taskbar);

        // 4. 將目前的相簿內容包進視窗中
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            const win = document.createElement('div');
            win.className = 'wpf-window';
            win.id = 'album-explorer-window';
            
            win.innerHTML = `
                <div class="wpf-titlebar">
                    <div class="wpf-title">
                        <i class="fa-solid fa-folder-open ms-1 me-2" style="color: #F0C243;"></i>
                        <span id="win-title-text">Album Explorer</span>
                    </div>
                    <div class="wpf-controls">
                        <div class="wpf-control-btn"><i class="fa-solid fa-minus"></i></div>
                        <div class="wpf-control-btn"><i class="fa-regular fa-square"></i></div>
                        <div class="wpf-control-btn close" onclick="window.location.href='../index.html'"><i class="fa-solid fa-xmark"></i></div>
                    </div>
                </div>
                <div class="wpf-content-host" id="album-content-target"></div>
            `;
            
            document.body.appendChild(win);
            const target = document.getElementById('album-content-target');
            target.appendChild(mainContent);

            // 初始化拖拽
            setupDragging(win, win.querySelector('.wpf-titlebar'));
        }
    }

    // 監聽相簿應用程式載入完成
    window.addEventListener('load', function() {
        initWin11Environment();
        
        // 修正 FontAwesome 載入 (如果沒載入的話)
        if (!document.querySelector('link[href*="font-awesome"]')) {
            const fa = document.createElement('link');
            fa.rel = 'stylesheet';
            fa.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css';
            document.head.appendChild(fa);
        }
    });

    // 監聽路由變化，更新視窗標題
    window.addEventListener('hashchange', function() {
        const titleText = document.getElementById('win-title-text');
        if (titleText) {
            // 延遲一下等待 SPA 渲染標題
            setTimeout(() => {
                const pageTitle = document.title.split(' - ')[0];
                titleText.innerText = 'Album Explorer - ' + pageTitle;
            }, 100);
        }
    });

})();
