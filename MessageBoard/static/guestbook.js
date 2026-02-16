/**
 * MessageBoard Iframe Loader (Bootstrapper)
 * This script runs on the host blog page.
 */
(function() {
    const container = document.getElementById('esmessageboard-root');
    if (!container) return;

    // --- 1. Detect Context & Config ---
    let scriptPath = '';
    const scripts = document.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; i++) {
        if (scripts[i].src && scripts[i].src.indexOf('guestbook.js') !== -1) {
            scriptPath = scripts[i].src;
            break;
        }
    }
    const basePath = scriptPath.substring(0, scriptPath.lastIndexOf('/') + 1);

    function getMeta(p) {
        const el = document.querySelector(`meta[property="${p}"], meta[name="${p}"]`);
        return el ? el.getAttribute('content') : null;
    }

    // Detect theme from AppConfig or current stylesheet hints
    let currentTheme = 'default';
    if (typeof AppConfig !== 'undefined' && AppConfig.theme_file) {
        if (AppConfig.theme_file.indexOf('dark') !== -1) currentTheme = 'dark';
        if (AppConfig.theme_file.indexOf('pink') !== -1) currentTheme = 'pink';
        if (AppConfig.theme_file.indexOf('matrix') !== -1) currentTheme = 'matrix';
    }

    const context = {
        site_id: getMeta('og:site_name') || window.location.hostname.replace(/\./g, '_'),
        page_id: (new URLSearchParams(window.location.search)).get('page') || window.location.pathname.split('/').pop().replace('.html', '') || 'index',
        page_title: getMeta('og:title') || document.title,
        lang: (typeof AppConfig !== 'undefined' && AppConfig.lang) ? AppConfig.lang : 'zh_TW',
        theme: currentTheme
    };

    // --- 2. Build Iframe URL ---
    const iframeUrl = new URL('messageboard.html', basePath);
    Object.keys(context).forEach(key => iframeUrl.searchParams.set(key, context[key]));
    iframeUrl.searchParams.set('v', new Date().getTime());

    // --- 3. Create Iframe ---
    const iframe = document.createElement('iframe');
    iframe.id = 'mb-iframe';
    iframe.src = iframeUrl.href;
    iframe.style.width = '100%';
    iframe.style.height = '300px'; // Initial height
    iframe.style.border = 'none';
    iframe.style.overflow = 'hidden';
    iframe.style.transition = 'height 0.2s ease-out';
    iframe.setAttribute('scrolling', 'no');
    
    container.innerHTML = '';
    container.appendChild(iframe);

    // --- 4. Listen for Resize Events from Iframe ---
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'mb-resize' && event.data.height) {
            iframe.style.height = (event.data.height + 20) + 'px';
        }
    });

})();
