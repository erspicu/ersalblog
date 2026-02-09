/**
 * Baxermux Album Theme Plugin - Netscape Navigator Style
 */

(function() {
    console.log("Netscape Navigator 4.0 Starting...");

    if (document.readyState === 'complete') {
        initNetscape();
    } else {
        window.addEventListener('load', initNetscape);
    }

    function initNetscape() {
        createBrowserChrome();
        createStatusBar();
        
        // Listen for route changes to animate the logo and update address bar
        window.addEventListener('hashchange', () => {
            simulateLoading();
            updateLocationBar();
        });

        // First load
        updateLocationBar();
    }

    function createBrowserChrome() {
        if (document.querySelector('.ns-chrome')) return;

        const chrome = document.createElement('div');
        chrome.className = 'ns-chrome';
        chrome.innerHTML = `
            <div class="ns-toolbar">
                <div class="ns-btn" onclick="window.history.back()"><span>🔙</span>Back</div>
                <div class="ns-btn" onclick="window.history.forward()"><span>Forward</span>🔜</div>
                <div class="ns-btn" onclick="location.reload()"><span>🔄</span>Reload</div>
                <div class="ns-btn" onclick="location.hash='#'"><span>🏠</span>Home</div>
                <div class="ns-btn"><span>🔍</span>Search</div>
                <div class="ns-btn"><span>📖</span>Guide</div>
                <div class="ns-btn" onclick="window.print()"><span>🖨️</span>Print</div>
                <div class="ns-btn"><span>🛡️</span>Security</div>
                
                <div class="ns-logo-container">
                    <div class="ns-logo-n" id="ns-main-logo">N</div>
                </div>
            </div>
            <div class="ns-location-bar">
                <span class="ns-label">Location:</span>
                <input type="text" class="ns-url-input" id="ns-url-input" readonly>
            </div>
        `;

        document.body.prepend(chrome);
    }

    function createStatusBar() {
        if (document.querySelector('.ns-status-bar')) return;

        const statusBar = document.createElement('div');
        statusBar.className = 'ns-status-bar';
        statusBar.innerHTML = `
            <div id="ns-status-text">Document: Done</div>
            <div class="ns-progress-area" style="margin-left: 20px;">
                <div class="ns-progress-fill" id="ns-progress-fill"></div>
            </div>
            <div class="ns-lock-icon">🔒</div>
        `;
        document.body.appendChild(statusBar);
    }

    function updateLocationBar() {
        const input = document.getElementById('ns-url-input');
        if (input) {
            input.value = window.location.href;
        }
    }

    function simulateLoading() {
        const logo = document.getElementById('ns-main-logo');
        const fill = document.getElementById('ns-progress-fill');
        const status = document.getElementById('ns-status-text');

        if (logo) logo.classList.add('loading');
        if (status) status.innerText = 'Connecting to host...';
        
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 30;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
                setTimeout(() => {
                    if (logo) logo.classList.remove('loading');
                    if (status) status.innerText = 'Document: Done';
                    if (fill) fill.style.width = '0%';
                }, 500);
            }
            if (fill) fill.style.width = progress + '%';
        }, 100);
    }

})();
