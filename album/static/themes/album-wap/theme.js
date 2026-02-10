/**
 * Baxermux Album Theme Plugin - WAP / Retro Mobile Style
 */

(function() {
    console.log("WAP Browser Initialized. GPRS Connected...");

    window.addEventListener('themePluginReady', function(e) {
        initWapEffects();
    });

    if (document.readyState === 'complete') {
        initWapEffects();
    } else {
        window.addEventListener('load', initWapEffects);
    }

    function initWapEffects() {
        if (window.WAP_INITIALIZED) return;
        window.WAP_INITIALIZED = true;

        updateWapClock();
        setInterval(updateWapClock, 60000);

        // 幫清單項目加上符號
        styleWapList();
        
        // 監聽 hashchange 因為 SPA 換頁後要重新處理樣式
        window.addEventListener('hashchange', () => {
            setTimeout(styleWapList, 100);
        });
    }

    function updateWapClock() {
        const header = document.querySelector('.header-bar');
        if (header) {
            const now = new Date();
            const timeStr = now.getHours().toString().padStart(2, '0') + ":" + now.getMinutes().toString().padStart(2, '0');
            // 隨機模擬訊號強弱
            const signals = ["█   ", "██  ", "███ ", "████"];
            const sig = signals[Math.floor(Math.random() * 4)];
            
            // 透過 style 定義 content 可能是個方法，但這裡我們用偽元素，所以改用 data-attribute
            // 或者簡單建立一個 div
            let statusLine = document.getElementById('wap-status-line');
            if (!statusLine) {
                statusLine = document.createElement('div');
                statusLine.id = 'wap-status-line';
                statusLine.style.cssText = 'font-size: 10px; border-bottom: 1px solid var(--wap-text); margin-bottom: 10px; text-align: left; display: flex; justify-content: space-between;';
                header.insertBefore(statusLine, header.firstChild);
            }
            statusLine.innerHTML = `<span>GPRS ${sig}</span> <span>${timeStr}</span>`;
        }
    }

    function styleWapList() {
        // 幫所有的 card-title 前面加上 [ ]
        const titles = document.querySelectorAll('.card-title');
        titles.forEach(t => {
            if (!t.innerText.startsWith('[')) {
                t.innerText = '[ ' + t.innerText + ' ]';
            }
        });

        // 幫分頁按鈕加上更 WAP 的標籤
        const pagination = document.querySelectorAll('.page-link');
        pagination.forEach(p => {
            if (p.innerText.includes('‹') || p.innerText.includes('«')) p.innerText = '<Prev';
            if (p.innerText.includes('›') || p.innerText.includes('»')) p.innerText = 'Next>';
        });
    }

})();
