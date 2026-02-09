/**
 * Baxermux Album Theme Plugin - Art Master Style
 */

(function() {
    console.log("Art Master Mode: Curating the gallery...");

    if (document.readyState === 'complete') {
        initArtMaster();
    } else {
        window.addEventListener('load', initArtMaster);
    }

    function initArtMaster() {
        // 1. 圖片逐一淡入效果
        applyFadeInEffect();

        // 2. 監聽 App 渲染事件 (針對 SPA 換頁)
        window.addEventListener('hashchange', () => {
            setTimeout(applyFadeInEffect, 500); // 延遲等待渲染完成
        });

        // 3. 加入虛擬「策展編號」
        addCuratorNotes();
    }

    function applyFadeInEffect() {
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.8s cubic-bezier(0.25, 0.8, 0.25, 1)';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100); // 交錯延遲
        });
    }

    function addCuratorNotes() {
        const titleEl = document.querySelector('.site-title');
        if (titleEl && !document.getElementById('curator-note')) {
            const note = document.createElement('div');
            note.id = 'curator-note';
            note.style.cssText = `
                font-family: 'Cinzel', serif;
                font-size: 10px;
                color: #d4af37;
                letter-spacing: 4px;
                margin-top: 10px;
                opacity: 0.6;
            `;
            const year = new Date().getFullYear();
            note.innerHTML = `EXHIBITION NO. ${Math.floor(Math.random() * 9000 + 1000)} / WINTER ${year}`;
            titleEl.parentNode.appendChild(note);
        }
    }

})();
