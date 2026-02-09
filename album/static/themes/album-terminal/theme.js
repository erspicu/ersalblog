/**
 * Baxermux Album Theme Plugin - Terminal / ASCII Art Style
 */

(function() {
    console.log("Initializing Terminal Interface...");

    const ASCII_CHARS = "@%#*+=-:. "; // 從濃到淡

    if (document.readyState === 'complete') {
        initTerminal();
    } else {
        window.addEventListener('load', initTerminal);
    }

    function initTerminal() {
        // 1. 處理現有的圖片
        convertAllVisibleImages();

        // 2. 監聽 SPA 內容變化
        const observer = new MutationObserver((mutations) => {
            convertAllVisibleImages();
        });
        
        const container = document.getElementById('app-container');
        if (container) {
            observer.observe(container, { childList: true, subtree: true });
        }
    }

    function convertAllVisibleImages() {
        const images = document.querySelectorAll('.card-img:not(.ascii-processed)');
        images.forEach(img => {
            img.classList.add('ascii-processed');
            // 確保圖片載入完成才進行轉換
            if (img.complete && img.naturalWidth > 0) {
                convertToAscii(img);
            } else {
                img.onload = () => convertToAscii(img);
            }
        });
    }

    /**
     * 將圖片轉換為 ASCII 字元
     */
    function convertToAscii(imgEl) {
        if (!imgEl.naturalWidth || imgEl.naturalWidth === 0) return;

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // 解析度設定
        const width = 60;
        const scale = width / imgEl.naturalWidth;
        const height = Math.floor(imgEl.naturalHeight * scale * 0.5); // 0.5 修正等寬字體高度
        
        canvas.width = width;
        canvas.height = height;
        
        ctx.drawImage(imgEl, 0, 0, width, height);
        const imageData = ctx.getImageData(0, 0, width, height).data;
        
        let asciiStr = "";
        let i = 0;
        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                const r = imageData[i++];
                const g = imageData[i++];
                const b = imageData[i++];
                const a = imageData[i++];
                
                // 計算灰階亮度 (ITU-R 601-2)
                const gray = (0.299 * r + 0.587 * g + 0.114 * b);
                const charIndex = Math.floor((gray / 255) * (ASCII_CHARS.length - 1));
                asciiStr += ASCII_CHARS[charIndex];
            }
            asciiStr += "\n";
        }
        
        // 建立預覽容器
        const pre = document.createElement('pre');
        pre.className = 'ascii-art';
        pre.textContent = asciiStr;
        
        // 替換原本的包裹容器內容
        const wrap = imgEl.closest('.card-img-wrap');
        if (wrap) {
            wrap.style.opacity = '0';
            wrap.innerHTML = '';
            wrap.appendChild(pre);
            // 優雅淡入
            setTimeout(() => { wrap.style.transition = 'opacity 0.5s'; wrap.style.opacity = '1'; }, 50);
        }
    }

})();