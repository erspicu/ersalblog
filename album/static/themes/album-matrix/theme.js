/**
 * Baxermux Album Theme Plugin - Matrix Style (Canvas Digital Rain)
 * This file is automatically loaded when the theme is set to 'album-matrix'.
 */

(function() {
    console.log("Matrix System Loaded...");

    // 1. 初始化特效
    if (document.readyState === 'complete') {
        initMatrixEffect();
    } else {
        window.addEventListener('load', initMatrixEffect);
    }

    function initMatrixEffect() {
        // 建立 Canvas 元素
        const canvas = document.createElement('canvas');
        canvas.id = 'matrix-canvas';
        canvas.style.position = 'fixed';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.zIndex = '-1'; // 確保在背景
        canvas.style.pointerEvents = 'none';
        document.body.prepend(canvas);

        const ctx = canvas.getContext('2d');

        // 設定 Canvas 尺寸
        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;

        // 監聽視窗縮放
        window.addEventListener('resize', () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
            resetDrops();
        });

        // Matrix 字元集 (日文片假名 + 拉丁字母 + 數字)
        const chars = 'アァカサタナハマヤャラワガザダバパイィキシチニヒミリヰギジヂビピウゥクスツヌフムユュルグズブヅプエェケセテネヘメレヱゲゼデベペオォコソトノホモヨョロヲゴゾドボポ0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const charArray = chars.split('');

        // 設定字體大小與行數
        const fontSize = 16;
        let columns = Math.floor(width / fontSize);
        
        // 儲存每一行的 Y 座標 (初始值為隨機負數，讓雨錯開落下)
        let drops = [];

        function resetDrops() {
            columns = Math.floor(width / fontSize);
            drops = [];
            for (let i = 0; i < columns; i++) {
                drops[i] = Math.random() * -100; // 隨機起始高度
            }
        }

        resetDrops();

        // 繪圖核心
        function draw() {
            // 使用半透明黑色覆蓋上一幀，製造拖尾效果 (關鍵！)
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, width, height);

            ctx.fillStyle = '#0F0'; // 經典 Matrix 綠
            ctx.font = fontSize + 'px monospace';

            for (let i = 0; i < drops.length; i++) {
                // 隨機選字
                const text = charArray[Math.floor(Math.random() * charArray.length)];
                
                // 繪製文字
                // 加入一點隨機亮度變化，讓部分字元看起來更亮 (頭部高亮)
                if (Math.random() > 0.95) {
                    ctx.fillStyle = '#FFF'; // 亮白頭部
                } else {
                    ctx.fillStyle = '#0F0'; // 一般綠色
                }
                
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);

                // 重置邏輯：當落到螢幕底部，且隨機觸發時重置回頂部
                if (drops[i] * fontSize > height && Math.random() > 0.975) {
                    drops[i] = 0;
                }

                // 往下移動
                drops[i]++;
            }
            
            // 使用 requestAnimationFrame 效能較好，但為了控制 Matrix 的「機械感」速度，這裡可以用 setTimeout 或由 rAF 控制 fps
            requestAnimationFrame(draw);
        }

        // 啟動動畫
        draw();
    }

})();
