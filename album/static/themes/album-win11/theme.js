/**
 * Album Service - Real Blazor WASM Theme Plugin
 * This plugin replaces the entire SPA content with the actual Blazor project.
 */
(function() {
    console.log("[Theme] Win11 (Blazor Native) Plugin Initializing...");

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
        // 注意：這裡路徑指向我們剛發佈的 dist/wwwroot/index.html
        const iframe = document.createElement('iframe');
        iframe.id = 'blazor-theme-container';
        
        // 獲取目前 static 的路徑 (考慮到可能有不同的部署環境)
        const staticPath = typeof APP_STATIC_PATH !== 'undefined' ? APP_STATIC_PATH : 'static/';
        iframe.src = staticPath + 'themes/album-win11/dist/wwwroot/index.html';
        
        document.body.appendChild(iframe);
    }

    // 監聽 DOM 準備好就執行，不需要等待全部 load 完成以提升速度
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectBlazorApp);
    } else {
        injectBlazorApp();
    }

})();
