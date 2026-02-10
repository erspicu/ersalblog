/**
 * Baxermux Album Theme Plugin - Y2K Style (YouTube BG Music & Construction Sticker & Java Applet Nostalgia)
 */

(function() {
    console.log("Y2K Experience Loaded! Loading Java Applet...");

    let ytPlayer = null;
    const VIDEO_ID = 'ax-efEg60yE'; 
    let mouseTrailCanvas = null;
    let mouseTrailCtx = null;
    let particles = [];
    const maxParticles = 30; // 避免過多 lag
    let mouseX = 0, mouseY = 0;

    window.addEventListener('themePluginReady', function(e) {
        // 先隱藏 body 內容，避免破梗
        document.body.style.visibility = 'hidden'; 
        initY2KEffects();
    });

    // Fallback if event didn't fire
    if (document.readyState === 'complete') {
        if (!window.Y2K_INITIALIZED) {
             document.body.style.visibility = 'hidden';
             initY2KEffects();
        }
    } else {
        window.addEventListener('load', function(){
            if (!window.Y2K_INITIALIZED) {
                document.body.style.visibility = 'hidden';
                initY2KEffects();
            }
        });
    }

    function initY2KEffects() {
        if (window.Y2K_INITIALIZED) return;
        window.Y2K_INITIALIZED = true;

        // 1. IE Error First
        showIEScriptError();
    }

    // --- Stage 1: IE Error ---
    function showIEScriptError() {
        // 恢復 body visibility 但只顯示 error (透過 z-index 蓋住)
        document.body.style.visibility = 'visible';
        
        // 建立一個全螢幕遮罩，避免點擊到後面的東西
        const overlay = document.createElement('div');
        overlay.id = 'ie-error-overlay';
        overlay.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #555; z-index: 99999; cursor: wait;
        `;
        document.body.appendChild(overlay);

        const errorDiv = document.createElement('div');
        errorDiv.id = 'ie-script-error';
        errorDiv.style.cssText = `
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 400px; background: #c0c0c0; border: 2px outset white;
            border-right-color: black; border-bottom-color: black;
            box-shadow: 10px 10px 0px rgba(0,0,0,0.5); z-index: 100000;
            font-family: 'MS Sans Serif', 'Arial', sans-serif; font-size: 12px;
            color: black; padding: 2px; user-select: none;
        `;

        // 按下任何按鈕或關閉都會觸發下一階段
        const nextStage = () => {
            if(document.getElementById('ie-script-error')) {
                document.getElementById('ie-script-error').remove();
                overlay.remove();
                startJavaAppletLoader();
            }
        };

        errorDiv.innerHTML = `
            <div style="background: navy; color: white; padding: 2px 5px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
                <span>Internet Explorer</span>
                <button id="ie-close-btn" style="background: #c0c0c0; border: 1px outset white; border-right-color: black; border-bottom-color: black; width: 16px; height: 14px; line-height: 12px; font-size: 9px; cursor: pointer; padding: 0;">X</button>
            </div>
            <div style="padding: 15px; display: flex; align-items: flex-start; gap: 15px;">
                <div style="background: yellow; color: red; border: 2px solid red; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; flex-shrink: 0;">!</div>
                <div>
                    <p style="margin: 0 0 10px 0;">An error has occurred in the script on this page.</p>
                    <table style="font-size: 11px; border-collapse: collapse;">
                        <tr><td style="padding-right: 10px;">Line:</td><td>245</td></tr>
                        <tr><td>Char:</td><td>13</td></tr>
                        <tr><td>Error:</td><td>'Object' is undefined</td></tr>
                        <tr><td>Code:</td><td>0</td></tr>
                        <tr><td>URL:</td><td style="word-break: break-all;">${window.location.href}</td></tr>
                    </table>
                </div>
            </div>
            <div style="padding: 10px; border-top: 1px solid #808080; text-align: center;">
                <p style="margin-bottom: 10px;">Do you want to continue running scripts on this page?</p>
                <div style="display: flex; justify-content: center; gap: 10px;">
                    <button id="ie-yes-btn" style="background: #c0c0c0; border: 2px outset white; border-right-color: black; border-bottom-color: black; padding: 2px 20px; cursor: pointer;">Yes</button>
                    <button id="ie-no-btn" style="background: #c0c0c0; border: 2px outset white; border-right-color: black; border-bottom-color: black; padding: 2px 20px; cursor: pointer;">No</button>
                </div>
            </div>
        `;

        document.body.appendChild(errorDiv);

        document.getElementById('ie-close-btn').onclick = nextStage;
        document.getElementById('ie-yes-btn').onclick = nextStage;
        document.getElementById('ie-no-btn').onclick = nextStage;
    }

    // --- Stage 2: Fake Java Applet Loader ---
    function startJavaAppletLoader() {
        const loaderOverlay = document.createElement('div');
        loaderOverlay.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #c0c0c0; z-index: 99999;
            display: flex; align-items: center; justify-content: center;
            flex-direction: column; font-family: 'Arial', sans-serif;
        `;

        loaderOverlay.innerHTML = `
            <div style="width: 300px; height: 150px; background: #e0e0e0; border: 2px inset white; padding: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <div style="font-weight: bold; color: #555; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                     <!-- Simple CSS Coffee Cup -->
                     <div style="width: 20px; height: 16px; border: 2px solid #555; border-radius: 0 0 5px 5px; position: relative;">
                        <div style="position: absolute; top: 2px; right: -5px; width: 4px; height: 8px; border: 2px solid #555; border-radius: 0 5px 5px 0;"></div>
                        <div style="position: absolute; top: -5px; left: 4px; width: 2px; height: 4px; background: #999;"></div>
                        <div style="position: absolute; top: -5px; left: 10px; width: 2px; height: 4px; background: #999;"></div>
                     </div>
                     Java(TM) Applet Loading...
                </div>
                
                <div style="width: 100%; background: white; border: 1px solid #999; height: 20px; position: relative;">
                    <div id="java-progress" style="width: 0%; height: 100%; background: navy;"></div>
                </div>
                <div id="java-status" style="font-size: 11px; margin-top: 5px; color: #666; align-self: flex-start;">Initializing JVM...</div>
            </div>
        `;
        document.body.appendChild(loaderOverlay);

        // Simulate Lag & Progress
        let progress = 0;
        const progressBar = document.getElementById('java-progress');
        const statusText = document.getElementById('java-status');
        
        // 卡頓模擬 function
        const freezeUI = (ms) => {
            const start = performance.now();
            while (performance.now() - start < ms) {
                // block main thread
            }
        };

        let step = 0;
        const steps = [
            { pct: 10, text: "Loading class: Lake.class", lag: 100 },
            { pct: 30, text: "Loading class: WaterRipple.class", lag: 200 },
            { pct: 45, text: "Verifying signature...", lag: 50 },
            { pct: 60, text: "Starting Java(TM) Virtual Machine...", lag: 800 }, // Big lag
            { pct: 80, text: "Allocating memory...", lag: 100 },
            { pct: 95, text: "Starting applet...", lag: 300 },
            { pct: 100, text: "Done.", lag: 50 }
        ];

        const runStep = () => {
            if (step >= steps.length) {
                // Finished
                setTimeout(() => {
                    loaderOverlay.remove();
                    startMainContent();
                }, 500);
                return;
            }

            const s = steps[step];
            progressBar.style.width = s.pct + '%';
            statusText.innerText = s.text;

            // Render update then lag
            requestAnimationFrame(() => {
                 setTimeout(() => {
                     // 這裡我們不真的 freezeUI 因為會讓 GIF 也卡住，
                     // 我們用 setTimeout 模擬 "等待" 的感覺
                     step++;
                     runStep();
                 }, s.lag);
            });
        };

        // Start loading
        setTimeout(runStep, 500);
    }

    // --- Stage 3: Main Content & Effects ---
    function startMainContent() {
        alert('Welcome to Baxermux 21 Century Album!\n\nThis site is UNDER CONSTRUCTION...');

        let userName = prompt('請輸入您的大名：', '訪客');
        if (userName) {
            updateUserGreeting(userName);
        }

        loadMidiAPI();
        createMusicPlayer();
        scatterConstructionStickers();
        
        // Start Canvas Effects
        initMouseTrailCanvas();
        initLakeApplet(); // Start the Lake Reflection Applet
        initTwinklingStars(); // Start the background twinkle stars
        initMeteors(); // Start occasional meteors
        initFireworks(); // Start occasional fireworks
    }

    function updateUserGreeting(name) {
        // Original greeting logic, adapted if title is replaced by canvas
        const headerInner = document.querySelector('.header-inner');
        if (headerInner) {
            const greeting = document.createElement('div');
            greeting.style.fontSize = '12px';
            greeting.style.color = '#00ff00';
            greeting.style.marginTop = '5px';
            greeting.style.textAlign = 'center';
            greeting.style.animation = 'tacky-blink 0.5s steps(1) infinite';
            greeting.innerHTML = 'HI! ' + name + '，你是第 01314 位訪客';
            headerInner.appendChild(greeting);
        }
    }

    // --- Meteors ---
    function initMeteors() {
        const spawnMeteor = () => {
            // 每次觸發隨機產生 1~3 顆流星
            const count = Math.floor(Math.random() * 3) + 1;
            for (let i = 0; i < count; i++) {
                setTimeout(() => {
                    const meteor = document.createElement('div');
                    meteor.className = 'meteor';
                    
                    const startX = Math.random() * window.innerWidth + 400;
                    const startY = Math.random() * (window.innerHeight / 2) - 200;
                    
                    meteor.style.left = startX + 'px';
                    meteor.style.top = startY + 'px';
                    meteor.style.animation = `meteor-anim ${Math.random() * 1 + 1}s linear forwards`;

                    document.body.appendChild(meteor);
                    setTimeout(() => { meteor.remove(); }, 2000);
                }, i * 300);
            }

            // 頻率提高：1~3 秒之間再次觸發 (平均 2 秒)
            setTimeout(spawnMeteor, Math.random() * 2000 + 1000);
        };
        
        setTimeout(spawnMeteor, 1000);
    }

    // --- Fireworks ---
    let fireworkParticles = [];
    
    function initFireworks() {
        const spawnFirework = () => {
            // 每次觸發產生 1~2 個爆炸點
            const count = Math.floor(Math.random() * 2) + 1;
            for (let i = 0; i < count; i++) {
                setTimeout(() => {
                    const x = Math.random() * (window.innerWidth * 0.8) + (window.innerWidth * 0.1);
                    const y = Math.random() * (window.innerHeight * 0.6) + (window.innerHeight * 0.1);
                    const color = Math.random() > 0.5 ? '#ff00ff' : (Math.random() > 0.5 ? '#00ffff' : '#ffff00');
                    createExplosion(x, y, color);
                }, i * 500);
            }

            // 頻率提高：1~3 秒之間再次觸發 (平均 2 秒)
            setTimeout(spawnFirework, Math.random() * 2000 + 1000);
        };
        setTimeout(spawnFirework, 2000);
    }

    function createExplosion(x, y, color) {
        const count = 60; // 煙火粒子數量加倍 (從 30 增加到 60)
        for (let i = 0; i < count; i++) {
            const angle = Math.random() * Math.PI * 2;
            const speed = Math.random() * 5 + 2; // 稍微增加速度範圍
            fireworkParticles.push({
                x: x,
                y: y,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed,
                life: 1.0,
                color: color,
                gravity: 0.15 // 重力稍微增加讓下墜更有感
            });
        }
    }

    // --- Background Twinkling Stars ---
    function initTwinklingStars() {
        const starCount = 60;
        const container = document.createElement('div');
        container.id = 'star-container';
        container.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:-1;';
        document.body.appendChild(container);

        const colors = ['#ffffff', '#ffff00', '#00ffff', '#ff00ff'];

        for (let i = 0; i < starCount; i++) {
            const star = document.createElement('div');
            star.className = 'twinkle-star';
            
            const size = Math.random() * 3 + 1;
            const duration = (Math.random() * 2 + 1) + 's';
            const delay = (Math.random() * 5) + 's';
            const color = colors[Math.floor(Math.random() * colors.length)];

            star.style.width = size + 'px';
            star.style.height = size + 'px';
            star.style.top = Math.random() * 100 + '%';
            star.style.left = Math.random() * 100 + '%';
            star.style.backgroundColor = color;
            star.style.setProperty('--duration', duration);
            star.style.animationDelay = delay;
            
            // 某些星星帶有一點發光效果
            if (Math.random() > 0.7) {
                star.style.boxShadow = `0 0 ${size * 2}px ${color}`;
            }

            container.appendChild(star);
        }
    }

    // --- Lake Reflection Applet (The "Dorian Gray" of 90s Web) ---
    function initLakeApplet() {
        const titleEl = document.querySelector('.site-title');
        if (!titleEl) return;

        const parent = titleEl.parentNode;
        const text = titleEl.innerText || "Baxermux's Album";
        
        // Create Canvas
        const canvas = document.createElement('canvas');
        // Height needs to be double for reflection
        const w = 600; 
        const h = 100; 
        canvas.width = w;
        canvas.height = h * 2; // Top + Reflection
        canvas.style.display = 'block';
        canvas.style.margin = '0 auto';
        canvas.style.cursor = 'crosshair'; // Java applet feel
        canvas.title = 'Lake Applet v1.0';

        // Replace the text title with our "Applet"
        titleEl.style.display = 'none';
        parent.insertBefore(canvas, titleEl);

        const ctx = canvas.getContext('2d');
        
        // Offscreen buffer for the source text
        const buffer = document.createElement('canvas');
        buffer.width = w;
        buffer.height = h;
        const bCtx = buffer.getContext('2d');

        // Draw text onto buffer
        bCtx.fillStyle = 'navy'; // bg of header
        bCtx.fillRect(0, 0, w, h);
        bCtx.font = 'bold 40px "Comic Sans MS", "Chalkboard SE", sans-serif';
        bCtx.textAlign = 'center';
        bCtx.textBaseline = 'middle';
        
        // Shadow
        bCtx.fillStyle = '#ff0000';
        bCtx.fillText(text, w/2 + 3, h/2 + 3);
        
        // Main Text
        bCtx.fillStyle = 'yellow';
        bCtx.fillText(text, w/2, h/2);

        // Animation Vars
        let time = 0;
        const settings = {
            speed: 0.1,
            scale: 1,
            waves: 10
        };

        function animateLake() {
            // 1. Draw Top Half (Static Source)
            ctx.drawImage(buffer, 0, 0);

            // 2. Draw Reflection (Wave Effect)
            const gap = 0;
            const startY = h + gap;

            // Simple line-by-line raster effect
            for (let y = 0; y < h; y++) {
                // Source Y (inverted from bottom of buffer)
                const sy = h - 1 - y;
                
                // Destination Y
                const dy = startY + y;
                
                // Calculate wave offset
                // Using sin(y * frequency + time)
                const xOffset = Math.sin(y * 0.05 + time) * (y * 0.2); // ripple gets stronger further down
                
                // Draw a 1px high slice
                // drawImage(img, sx, sy, sw, sh, dx, dy, dw, dh)
                ctx.drawImage(buffer, 
                    0, sy, w, 1,      // Source slice
                    xOffset, dy, w, 1 // Dest slice with offset
                );
                
                // Darken / Tint reflection (blueish)
                ctx.fillStyle = `rgba(0, 0, 100, ${y/h * 0.5})`; // fade out
                ctx.fillRect(0, dy, w, 1);
            }

            time += settings.speed;
            requestAnimationFrame(animateLake);
        }

        // Start Loop
        animateLake();
        
        // Interactive: Click to disturb water (speed up briefly)
        canvas.addEventListener('mousedown', () => {
             settings.speed = 0.5;
             setTimeout(() => settings.speed = 0.1, 500);
        });
    }

    function scatterConstructionStickers() {
        const stickerPath = 'static/themes/album-y2k/construction.gif';
        const count = 12; 
        for (let i = 0; i < count; i++) {
            const img = document.createElement('img');
            img.src = stickerPath;
            img.style.position = 'fixed';
            img.style.top = (Math.random() * 90) + '%';
            if (Math.random() > 0.5) {
                img.style.left = (Math.random() * 80) + 'px';
            } else {
                img.style.right = (Math.random() * 80) + 'px';
            }
            img.style.zIndex = '1000';
            img.style.width = '64px'; 
            img.style.pointerEvents = 'none';
            document.body.appendChild(img);
        }
    }

    // --- MIDI Player (Replacing YouTube) ---
    function loadMidiAPI() {
        if (document.getElementById('midi-js-script')) return;
        const tag = document.createElement('script');
        tag.id = 'midi-js-script';
        tag.src = "https://www.midijs.net/lib/midi.js";
        document.head.appendChild(tag);
    }

    function createMusicPlayer() {
        const playerDiv = document.createElement('div');
        playerDiv.style.position = 'fixed';
        playerDiv.style.bottom = '10px';
        playerDiv.style.right = '10px';
        playerDiv.style.zIndex = '9999';
        playerDiv.style.background = '#c0c0c0';
        playerDiv.style.border = '2px outset white';
        playerDiv.style.padding = '5px';
        playerDiv.style.textAlign = 'center';
        playerDiv.style.boxShadow = '5px 5px 0px rgba(0,0,0,0.5)';
        playerDiv.style.width = '150px';

        const midiFile = 'static/themes/album-y2k/tokyo.mid';

        playerDiv.innerHTML = `
            <div style="background:navy; color:white; font-size:10px; padding:2px; margin-bottom:5px; font-weight:bold;">
                Retro MIDI Player
            </div>
            <div id="midi-status-text" style="font-size:9px; color:black; margin-bottom:5px;">
                Ready to play MIDI
            </div>
            <div style="display:flex; gap:2px; justify-content:center;">
                <button id="midi-play-btn" class="btn" style="padding:2px 5px; font-size:10px;">▶ PLAY .MID</button>
                <button id="midi-stop-btn" class="btn" style="padding:2px 5px; font-size:10px;">■ STOP</button>
            </div>
        `;

        document.body.appendChild(playerDiv);

        document.getElementById('midi-play-btn').onclick = function() {
            if (typeof MIDIjs !== 'undefined') {
                MIDIjs.play(midiFile);
                document.getElementById('midi-status-text').innerText = '♫ Playing: tokyo.mid';
                document.getElementById('midi-status-text').style.color = 'blue';
            } else {
                alert('MIDI library still loading...');
            }
        };

        document.getElementById('midi-stop-btn').onclick = function() {
            if (typeof MIDIjs !== 'undefined') {
                MIDIjs.stop();
                document.getElementById('midi-status-text').innerText = 'Stopped';
                document.getElementById('midi-status-text').style.color = 'black';
            }
        };
    }

    // --- Canvas Mouse Trails & Effects ---
    function initMouseTrailCanvas() {
        mouseTrailCanvas = document.createElement('canvas');
        mouseTrailCanvas.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 999999;
        `;
        document.body.appendChild(mouseTrailCanvas);
        mouseTrailCtx = mouseTrailCanvas.getContext('2d');

        const resize = () => {
            mouseTrailCanvas.width = window.innerWidth;
            mouseTrailCanvas.height = window.innerHeight;
        };
        window.addEventListener('resize', resize);
        resize();

        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
            // Add new particle on move
            createParticle(mouseX, mouseY);
        });

        // Loop
        requestAnimationFrame(renderLoop);
    }

    function createParticle(x, y) {
        // Classic "Cross" or "Star" shape particles
        // Limit total
        if (particles.length > maxParticles) {
            particles.shift();
        }
        
        particles.push({
            x: x,
            y: y,
            vx: (Math.random() - 0.5) * 2,
            vy: (Math.random() - 0.5) * 2,
            life: 1.0,
            color: Math.random() > 0.5 ? '#00ffff' : '#ffff00' // Cyan or Yellow
        });
    }

    function renderLoop() {
        mouseTrailCtx.clearRect(0, 0, mouseTrailCanvas.width, mouseTrailCanvas.height);

        // --- 1. Mouse Trail Particles (Spider Web) ---
        mouseTrailCtx.lineWidth = 1;
        
        for (let i = 0; i < particles.length; i++) {
            let p = particles[i];
            
            // Update
            p.x += p.vx;
            p.y += p.vy;
            p.life -= 0.02;

            if (p.life <= 0) {
                particles.splice(i, 1);
                i--;
                continue;
            }

            // Draw Point
            mouseTrailCtx.fillStyle = p.color;
            mouseTrailCtx.globalAlpha = p.life;
            mouseTrailCtx.beginPath();
            mouseTrailCtx.arc(p.x, p.y, 2, 0, Math.PI * 2);
            mouseTrailCtx.fill();

            // Connect to nearby
            for (let j = i + 1; j < particles.length; j++) {
                let p2 = particles[j];
                let dx = p.x - p2.x;
                let dy = p.y - p2.y;
                let dist = Math.sqrt(dx*dx + dy*dy);
                
                if (dist < 100) {
                    mouseTrailCtx.strokeStyle = p.color;
                    mouseTrailCtx.globalAlpha = (1 - dist/100) * p.life;
                    mouseTrailCtx.beginPath();
                    mouseTrailCtx.moveTo(p.x, p.y);
                    mouseTrailCtx.lineTo(p2.x, p2.y);
                    mouseTrailCtx.stroke();
                }
            }
            
            // Connect to mouse cursor
            let dx = p.x - mouseX;
            let dy = p.y - mouseY;
            let dist = Math.sqrt(dx*dx + dy*dy);
            if (dist < 150) {
                 mouseTrailCtx.strokeStyle = '#ff00ff'; // Magenta connection to mouse
                 mouseTrailCtx.globalAlpha = (1 - dist/150) * p.life;
                 mouseTrailCtx.beginPath();
                 mouseTrailCtx.moveTo(p.x, p.y);
                 mouseTrailCtx.lineTo(mouseX, mouseY);
                 mouseTrailCtx.stroke();
            }
        }

        // --- 2. Firework Particles (Explosions with Gravity) ---
        for (let i = 0; i < fireworkParticles.length; i++) {
            let p = fireworkParticles[i];
            
            p.x += p.vx;
            p.y += p.vy;
            p.vy += p.gravity; // Gravity
            p.life -= 0.015;

            if (p.life <= 0) {
                fireworkParticles.splice(i, 1);
                i--;
                continue;
            }

            // Draw Rect (Retro pixel look)
            mouseTrailCtx.fillStyle = p.color;
            mouseTrailCtx.globalAlpha = p.life;
            mouseTrailCtx.fillRect(p.x, p.y, 3, 3);
        }

        requestAnimationFrame(renderLoop);
    }

})();
