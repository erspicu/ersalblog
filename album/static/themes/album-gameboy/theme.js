/**
 * Baxermux Album Theme Plugin - GameBoy Style
 */

(function() {
    console.log("GameBoy System Initializing...");

    if (document.readyState === 'complete') {
        initGameBoy();
    } else {
        window.addEventListener('load', initGameBoy);
    }

    function initGameBoy() {
        // 1. Startup Logo Animation (Only once per session)
        if (!sessionStorage.getItem('gb_booted')) {
            showStartupAnimation();
            sessionStorage.setItem('gb_booted', 'true');
        }

        // 2. Beep sound on button hover
        addButtonBeeps();
    }

    function showStartupAnimation() {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #9bbc0f; z-index: 99999; display: flex;
            align-items: center; justify-content: center;
            font-family: 'Press Start 2P', cursive; color: #0f380f;
        `;
        
        const logo = document.createElement('div');
        logo.textContent = 'BAXERMUX';
        logo.style.fontSize = '24px';
        logo.style.transform = 'translateY(-200px)';
        logo.style.transition = 'transform 2s cubic-bezier(0.1, 0.7, 0.1, 1)';
        
        overlay.appendChild(logo);
        document.body.appendChild(overlay);

        // Sound effect (The "Ding")
        const playDing = () => {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                const ctx = new AudioContext();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                
                osc.type = 'square';
                osc.frequency.setValueAtTime(523.25, ctx.currentTime); // C5
                osc.frequency.setValueAtTime(1046.50, ctx.currentTime + 0.1); // C6
                
                gain.gain.setValueAtTime(0.1, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.5);
            } catch(e) {}
        };

        // Animate
        setTimeout(() => {
            logo.style.transform = 'translateY(0)';
            setTimeout(playDing, 1800);
            setTimeout(() => {
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.5s';
                setTimeout(() => overlay.remove(), 500);
            }, 3000);
        }, 100);
    }

    function addButtonBeeps() {
        const buttons = document.querySelectorAll('.btn, .page-link, .card');
        buttons.forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                playBeep(880, 0.05); // High beep
            });
            btn.addEventListener('click', () => {
                playBeep(440, 0.1); // Low beep
            });
        });
    }

    function playBeep(freq, duration) {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'square';
            osc.frequency.value = freq;
            gain.gain.value = 0.05;
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            setTimeout(() => osc.stop(), duration * 1000);
        } catch(e) {}
    }

})();
