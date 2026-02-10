/**
 * Baxermux Album Theme Plugin - Flickr Style
 */

(function() {
    console.log("Flickr Theme Loaded.");

    window.addEventListener('themePluginReady', function(e) {
        initFlickrLogo();
    });

    if (document.readyState === 'complete') {
        initFlickrLogo();
    } else {
        window.addEventListener('load', initFlickrLogo);
    }

    function initFlickrLogo() {
        if (window.FLICKR_INITIALIZED) return;
        window.FLICKR_INITIALIZED = true;

        const titleEl = document.querySelector('.site-title');
        if (titleEl) {
            // 致敬版 Logo：三顆重疊的半透明光暈圓點
            const logoContainer = document.createElement('span');
            logoContainer.style.cssText = 'display: inline-flex; position: relative; width: 32px; height: 16px; margin-right: 12px; order: -1; align-items: center;';
            
            // 藍、粉、白 三色光暈
            const colors = ['rgba(0, 99, 220, 0.7)', 'rgba(255, 0, 132, 0.7)', 'rgba(255, 255, 255, 0.5)'];
            
            colors.forEach((color, i) => {
                const dot = document.createElement('span');
                dot.style.cssText = `
                    width: 14px; height: 14px; 
                    background-color: ${color}; 
                    border-radius: 50%; 
                    position: absolute; 
                    left: ${i * 8}px;
                    mix-blend-mode: screen; /* 重疊處會產生光學融合感 */
                    animation: float-logo 3s ease-in-out infinite alternate;
                    animation-delay: ${i * 0.5}s;
                `;
                logoContainer.appendChild(dot);
            });

            // 加入 CSS 動畫
            const style = document.createElement('style');
            style.textContent = `
                @keyframes float-logo {
                    from { transform: translateY(0) scale(1); }
                    to { transform: translateY(-2px) scale(1.1); }
                }
            `;
            document.head.appendChild(style);

            // Insert before title text
            titleEl.insertBefore(logoContainer, titleEl.firstChild);
        }
    }
})();
