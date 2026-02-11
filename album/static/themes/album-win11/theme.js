/**
 * Album Service - Real Blazor WASM Theme Plugin
 * This plugin replaces the entire SPA content with the actual Blazor project.
 */
(function() {
    console.log("[Theme] Win11 (Blazor Native) Plugin Initializing...");

    // 【協議實作】通知核心停止父層預設渲染，節省資源
    if (typeof AppState !== 'undefined') {
        AppState.uiTakeover = true;
    }

    function injectBlazorApp() {
        // 1. 隱藏原有的 Header/Footer/Main
        const styles = `
            body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; }
            .header-bar, .footer, .main-content { display: none !important; }
            #blazor-theme-container { 
                position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; 
                z-index: 99999; border: none; background: #004d8c;
            }
        `;
        const styleSheet = document.createElement("style");
        styleSheet.innerText = styles;
        document.head.appendChild(styleSheet);

        // 2. 建立全屏 Iframe 載入 Blazor
        const iframe = document.createElement('iframe');
        iframe.id = 'blazor-theme-container';
        
        // 獲取目前 static 的路徑
        const staticPath = typeof APP_STATIC_PATH !== 'undefined' ? APP_STATIC_PATH : 'static/';
        iframe.src = staticPath + 'themes/album-win11/dist/wwwroot/index.html';
        
        document.body.appendChild(iframe);
    }

    // 監聽 DOM 準備好就執行
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectBlazorApp);
    } else {
        injectBlazorApp();
    }

})();
