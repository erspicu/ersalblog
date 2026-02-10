/**
 * Baxermux Album Theme Plugin - IE Retro Style
 */

(function() {
    console.log("Internet Explorer 3.0 Initializing... Done.");

    window.addEventListener('themePluginReady', function(e) {
        initIERetroUI();
    });

    if (document.readyState === 'complete') {
        initIERetroUI();
    } else {
        window.addEventListener('load', initIERetroUI);
    }

    function initIERetroUI() {
        if (window.IE_RETRO_INITIALIZED) return;
        window.IE_RETRO_INITIALIZED = true;

        const header = document.querySelector('.header-bar');
        if (!header) return;

        // 1. Create Menu Bar
        const menuBar = document.createElement('div');
        menuBar.style.cssText = 'background:#c0c0c0; border-bottom:1px solid #808080; padding:2px 10px; font-family:"MS Sans Serif",Arial; font-size:11px;';
        menuBar.innerHTML = '<span style="margin-right:15px"><u>F</u>ile</span> <span style="margin-right:15px"><u>E</u>dit</span> <span style="margin-right:15px"><u>V</u>iew</span> <span style="margin-right:15px"><u>G</u>o</span> <span style="margin-right:15px"><u>F</u>avorites</span> <span><u>H</u>elp</span>';
        header.insertBefore(menuBar, header.firstChild.nextSibling);

        // 2. Create Toolbar
        const toolbar = document.createElement('div');
        toolbar.id = 'ie-toolbar';
        toolbar.innerHTML = `
            <div class="toolbar-btn"><span>⬅</span>Back</div>
            <div class="toolbar-btn"><span>➡</span>Next</div>
            <div class="toolbar-btn"><span>✖</span>Stop</div>
            <div class="toolbar-btn"><span>🔄</span>Refresh</div>
            <div class="toolbar-btn"><span>🏠</span>Home</div>
            <div style="flex-grow:1"></div>
            <div id="ie-logo" style="width:32px; height:32px; background:#000; border:2px inset #fff; display:flex; align-items:center; justify-content:center; color:#0ff; font-weight:bold; font-size:24px; font-family:serif; font-style:italic;">e</div>
        `;
        header.insertBefore(toolbar, menuBar.nextSibling);

        // 3. Create Address Bar
        const addressBar = document.createElement('div');
        addressBar.style.cssText = 'background:#c0c0c0; border-bottom:2px groove #fff; padding:5px 10px; display:flex; align-items:center; gap:10px; font-size:12px;';
        addressBar.innerHTML = `<span>Address:</span> <div style="flex-grow:1; background:#fff; border:2px inset #fff; padding:2px 5px; font-family:monospace;">${window.location.href}</div>`;
        header.appendChild(addressBar);

        // 4. IE Logo Animation
        animateIELogo();

        // 5. Create Status Bar
        createStatusBar();
    }

    function createStatusBar() {
        const statusBar = document.createElement('div');
        statusBar.id = 'ie-status-bar';
        statusBar.innerHTML = `
            <div class="status-text" id="ie-status-msg">Done</div>
            <div class="status-progress-container" id="ie-progress-container"></div>
            <div class="status-zone">Internet</div>
        `;
        document.body.appendChild(statusBar);

        // 模擬隨機讀取動作
        window.addEventListener('hashchange', () => {
            simulateProgress();
        });
        simulateProgress();
    }

    function simulateProgress() {
        const container = document.getElementById('ie-progress-container');
        const msg = document.getElementById('ie-status-msg');
        if (!container || !msg) return;

        container.innerHTML = '';
        msg.innerText = 'Opening page...';
        
        let blocks = 0;
        const maxBlocks = 15;
        const interval = setInterval(() => {
            if (blocks >= maxBlocks) {
                clearInterval(interval);
                setTimeout(() => {
                    container.innerHTML = '';
                    msg.innerText = 'Done';
                }, 500);
                return;
            }
            
            const block = document.createElement('div');
            block.className = 'progress-block';
            container.appendChild(block);
            blocks++;
        }, 100 + Math.random() * 200);
    }

    function animateIELogo() {
        const logo = document.getElementById('ie-logo');
        if (!logo) return;
        
        let angle = 0;
        setInterval(() => {
            angle = (angle + 10) % 360;
            logo.style.textShadow = `
                ${Math.cos(angle * Math.PI / 180) * 3}px ${Math.sin(angle * Math.PI / 180) * 3}px 0px rgba(255,255,255,0.5)
            `;
        }, 100);
    }

})();
