/**
 * Baxermux Album Theme Plugin - Yayoi Kusama Style
 * "Our earth is only one polka dot among a million stars in the cosmos."
 */

(function() {
    console.log("Kusama Theme Loaded. Infinity Polka Dots.");

    window.addEventListener('themePluginReady', function(e) {
        initPolkaDotCursor();
    });

    if (document.readyState === 'complete') {
        initPolkaDotCursor();
    } else {
        window.addEventListener('load', initPolkaDotCursor);
    }

    function initPolkaDotCursor() {
        if (window.KUSAMA_INITIALIZED) return;
        window.KUSAMA_INITIALIZED = true;

        const canvas = document.createElement('canvas');
        canvas.id = 'kusama-cursor';
        canvas.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999999; pointer-events: none;';
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        let width, height;
        let particles = [];

        function resize() {
            width = window.innerWidth;
            height = window.innerHeight;
            canvas.width = width;
            canvas.height = height;
        }
        window.addEventListener('resize', resize);
        resize();

        // 追蹤滑鼠位置
        let mouseX = -100, mouseY = -100;
        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
            
            // 產生更多豐富色彩
            const colors = ['#000000', '#E60012', '#FFFFFF', '#FFFF00', '#FF00FF', '#00FFFF', '#00FF00'];
            const color = colors[Math.floor(Math.random() * colors.length)];
            
            // 每次移動產生 2-3 個點
            for(let k=0; k<2; k++) {
                particles.push({
                    x: mouseX + (Math.random()-0.5)*20,
                    y: mouseY + (Math.random()-0.5)*20,
                    size: Math.random() * 5 + 5, 
                    maxSize: Math.random() * 40 + 20, 
                    growthRate: Math.random() * 2 + 1,
                    life: 1.0,
                    color: color,
                    vx: (Math.random() - 0.5) * 4,
                    vy: (Math.random() - 0.5) * 4
                });
            }
        });

        // 加入背景漂浮的大圓點 (細胞)
        let floatingDots = [];
        for(let i=0; i<15; i++) {
            floatingDots.push({
                x: Math.random() * width,
                y: Math.random() * height,
                r: Math.random() * 100 + 50,
                color: ['#FF00FF', '#00FFFF', '#FFFF00', '#E60012'][Math.floor(Math.random()*4)],
                vx: (Math.random()-0.5)*0.5,
                vy: (Math.random()-0.5)*0.5,
                alpha: 0.2
            });
        }

        function render() {
            ctx.clearRect(0, 0, width, height);

            // 繪製背景漂浮物
            floatingDots.forEach(d => {
                d.x += d.vx;
                d.y += d.vy;
                if(d.x < -d.r) d.x = width + d.r;
                if(d.x > width + d.r) d.x = -d.r;
                if(d.y < -d.r) d.y = height + d.r;
                if(d.y > height + d.r) d.y = -d.r;

                ctx.fillStyle = d.color;
                ctx.globalAlpha = d.alpha;
                ctx.beginPath();
                ctx.arc(d.x, d.y, d.r, 0, Math.PI * 2);
                ctx.fill();
            });

            // 繪製粒子
            for (let i = 0; i < particles.length; i++) {
                let p = particles[i];

                // 生長與移動
                if (p.size < p.maxSize) {
                    p.size += p.growthRate;
                } else {
                    p.life -= 0.02; // 開始消逝
                }
                
                p.x += p.vx;
                p.y += p.vy;

                if (p.life <= 0) {
                    particles.splice(i, 1);
                    i--;
                    continue;
                }

                ctx.fillStyle = p.color;
                ctx.globalAlpha = p.life;
                ctx.beginPath();
                // 畫不規則的圓 (模擬有機體) - 這裡簡單用圓，但透過重疊產生效果
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                ctx.fill();
                
                // 畫邊框讓它更像普普藝術
                ctx.strokeStyle = '#000'; // 統一黑邊
                ctx.lineWidth = 1;
                ctx.stroke();
            }

            requestAnimationFrame(render);
        }

        render();
    }
})();
