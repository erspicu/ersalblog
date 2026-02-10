/**
 * Baxermux Album Theme Plugin - Terminator Glitch Style
 * "I'll be back."
 */

(function() {
    console.log("Terminator Systems Online. Scanning targets...");

    window.addEventListener('themePluginReady', function(e) {
        initTerminatorSystems();
    });

    if (document.readyState === 'complete') {
        initTerminatorSystems();
    } else {
        window.addEventListener('load', initTerminatorSystems);
    }

    function initTerminatorSystems() {
        if (window.TERM_INITIALIZED) return;
        window.TERM_INITIALIZED = true;

        // 1. Inject data-text for CSS Glitch
        const title = document.querySelector('.site-title');
        if (title) {
            title.setAttribute('data-text', title.innerText);
            
            // Text Decoding Effect
            scrambleText(title);
        }

        // 2. Setup HUD Canvas
        createHUD();

        // 3. Random Text Glitches on Cards
        setInterval(glitchRandomCard, 2000);
    }

    // --- Text Scramble Effect ---
    function scrambleText(element) {
        const originalText = element.innerText;
        const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*";
        let iterations = 0;
        
        const interval = setInterval(() => {
            element.innerText = originalText
                .split("")
                .map((letter, index) => {
                    if(index < iterations) {
                        return originalText[index];
                    }
                    return chars[Math.floor(Math.random() * chars.length)];
                })
                .join("");
            
            if(iterations >= originalText.length) {
                clearInterval(interval);
                // Re-set data attribute for CSS glitch sync
                element.setAttribute('data-text', originalText); 
            }
            
            iterations += 1/3;
        }, 30);
    }

    // --- HUD Overlay ---
    function createHUD() {
        const canvas = document.createElement('canvas');
        canvas.id = 'term-hud';
        canvas.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999998; pointer-events: none;';
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        let width, height;

        function resize() {
            width = window.innerWidth;
            height = window.innerHeight;
            canvas.width = width;
            canvas.height = height;
        }
        window.addEventListener('resize', resize);
        resize();

        // HUD State
        let mouseX = width / 2;
        let mouseY = height / 2;
        let targetX = mouseX;
        let targetY = mouseY;

        document.addEventListener('mousemove', (e) => {
            targetX = e.clientX;
            targetY = e.clientY;
        });

        let scanLineY = 0;
        let processingText = ["ANALYZING...", "MATCHING...", "SEARCHING...", "TARGET ACQUIRED", "CPU: 6502"];
        let currentProcess = 0;
        let textTimer = 0;

        function drawCornerBrackets() {
            ctx.strokeStyle = '#ff0000';
            ctx.lineWidth = 2;
            const len = 40;
            const pad = 20;

            // Top-Left
            ctx.beginPath(); ctx.moveTo(pad, pad + len); ctx.lineTo(pad, pad); ctx.lineTo(pad + len, pad); ctx.stroke();
            // Top-Right
            ctx.beginPath(); ctx.moveTo(width - pad - len, pad); ctx.lineTo(width - pad, pad); ctx.lineTo(width - pad, pad + len); ctx.stroke();
            // Bottom-Left
            ctx.beginPath(); ctx.moveTo(pad, height - pad - len); ctx.lineTo(pad, height - pad); ctx.lineTo(pad + len, height - pad); ctx.stroke();
            // Bottom-Right
            ctx.beginPath(); ctx.moveTo(width - pad - len, height - pad); ctx.lineTo(width - pad, height - pad); ctx.lineTo(width - pad, height - pad - len); ctx.stroke();
        }

        function drawCrosshair() {
            // Smooth following (Lerp)
            mouseX += (targetX - mouseX) * 0.2;
            mouseY += (targetY - mouseY) * 0.2;

            const cx = mouseX;
            const cy = mouseY;
            const size = 30;

            ctx.strokeStyle = 'rgba(255, 0, 0, 0.8)';
            ctx.lineWidth = 1;

            // Center Cross
            ctx.beginPath();
            ctx.moveTo(cx - size, cy); ctx.lineTo(cx + size, cy);
            ctx.moveTo(cx, cy - size); ctx.lineTo(cx, cy + size);
            ctx.stroke();

            // Circle with brackets
            ctx.beginPath();
            ctx.arc(cx, cy, size * 0.8, 0, Math.PI * 2);
            ctx.stroke();

            // Target Info next to crosshair
            ctx.fillStyle = 'rgba(255, 0, 0, 0.8)';
            ctx.font = '10px "Courier New"';
            ctx.fillText(`OBJ_LOC: [${Math.floor(cx)},${Math.floor(cy)}]`, cx + size + 5, cy - 10);
            ctx.fillText(`DIST: ${Math.floor(Math.random()*100)}m`, cx + size + 5, cy);
        }

        function drawDataStream() {
            ctx.fillStyle = '#ff0000';
            ctx.font = '12px "Courier New"';
            ctx.textAlign = 'right';

            const time = new Date().toISOString().split('T')[1].split('.')[0];
            ctx.fillText("SYS.TIME: " + time, width - 30, height - 30);
            ctx.fillText("MEM: " + Math.floor(Math.random() * 1024) + "TB", width - 30, height - 45);
            ctx.fillText("THREAT: LOW", width - 30, height - 60);

            // Left side scrolling code
            ctx.textAlign = 'left';
            ctx.fillStyle = 'rgba(255, 0, 0, 0.7)';
            for(let i=0; i<5; i++) {
                ctx.fillText(Math.random().toString(16).substring(2, 10).toUpperCase(), 30, height - 30 - (i*15));
            }
        }

        function drawProcessing() {
            textTimer++;
            if (textTimer > 100) {
                currentProcess = (currentProcess + 1) % processingText.length;
                textTimer = 0;
            }

            ctx.fillStyle = '#ff0000';
            ctx.font = 'bold 16px "Courier New"';
            ctx.textAlign = 'left';
            ctx.fillText(processingText[currentProcess], 40, 60);
            
            // Blink cursor
            if (Math.floor(Date.now() / 500) % 2 === 0) {
                ctx.fillRect(40 + ctx.measureText(processingText[currentProcess]).width + 5, 48, 10, 16);
            }
        }

        function render() {
            ctx.clearRect(0, 0, width, height);

            drawCornerBrackets();
            drawCrosshair();
            drawDataStream();
            drawProcessing();

            // Moving Scan Bar (Vertical)
            scanLineY += 2;
            if (scanLineY > height) scanLineY = 0;
            
            ctx.fillStyle = 'rgba(255, 0, 0, 0.1)';
            ctx.fillRect(0, scanLineY, width, 5);

            requestAnimationFrame(render);
        }

        render();
    }

    function glitchRandomCard() {
        const cards = document.querySelectorAll('.card-title');
        if (cards.length === 0) return;

        const target = cards[Math.floor(Math.random() * cards.length)];
        scrambleText(target);
    }

})();
