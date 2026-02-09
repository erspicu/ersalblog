/**
 * Baxermux Album Theme Plugin - Windows 3.1 Style
 */

(function() {
    console.log("Starting Windows 3.1 Environment...");

    // TADA.WAV (Shortened Base64) - Classic Win3.1 Startup Sound
    const tadaSound = "data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU"; // Placeholder for brevity, will use a real one or simulate

    // 1. 初始化
    if (document.readyState === 'complete') {
        initWin31();
    } else {
        window.addEventListener('load', initWin31);
    }

    function initWin31() {
        // 播放開機音效
        playTada();

        // 攔截 Alert
        overrideAlert();

        // 讓主要視窗可拖曳 (模擬 MDI)
        makeDraggable(document.querySelector('.modal-box'), '.modal-header');
        
        // 增加一個「關於」按鈕到右下角
        createTaskbarIcon();
    }

    function playTada() {
        // 這裡使用一個簡單的 HTML5 Audio 產生 "Ding" 聲，模擬 TADA
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'square';
            osc.frequency.setValueAtTime(440, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.1);
            
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);

            osc.connect(gain);
            gain.connect(ctx.destination);
            
            osc.start();
            osc.stop(ctx.currentTime + 0.5);
        } catch(e) {
            console.log("Audio not supported");
        }
    }

    function overrideAlert() {
        const oldAlert = window.alert;
        window.alert = function(msg) {
            // 建立一個 Win3.1 風格的 Dialog
            const dialog = document.createElement('div');
            dialog.style.cssText = `
                position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
                width: 300px; background: #C0C0C0; border: 2px outset white;
                border-right-color: black; border-bottom-color: black;
                box-shadow: 10px 10px 0px rgba(0,0,0,0.5); z-index: 10000;
                font-family: "Microsoft Sans Serif", sans-serif; font-size: 13px;
                display: flex; flex-direction: column;
            `;
            
            dialog.innerHTML = `
                <div style="background: #000080; color: white; padding: 2px 4px; font-weight: bold; text-align: center;">Error</div>
                <div style="padding: 20px; display: flex; align-items: center; gap: 15px;">
                    <div style="font-size: 32px; color: red; font-weight: bold; font-family: serif;">STOP</div>
                    <div>${msg}</div>
                </div>
                <div style="padding: 10px; text-align: center;">
                    <button id="win31-ok" style="background: #C0C0C0; border: 2px outset white; border-right-color: black; border-bottom-color: black; padding: 2px 15px; font-weight: bold; cursor: pointer;">OK</button>
                </div>
            `;
            
            document.body.appendChild(dialog);
            
            // 簡單的 Focus 鎖定
            const btn = dialog.querySelector('#win31-ok');
            btn.focus();
            btn.onclick = function() {
                dialog.remove();
            };
        };
    }

    function createTaskbarIcon() {
        const icon = document.createElement('div');
        icon.innerHTML = `
            <div style="width: 32px; height: 32px; background: white; border: 1px solid black; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 24px;">💿</span>
            </div>
            <div style="background: #000080; color: white; font-size: 11px; margin-top: 2px; padding: 0 2px;">Album Manager</div>
        `;
        icon.style.cssText = `
            position: fixed; bottom: 20px; left: 20px; text-align: center; cursor: pointer; z-index: 100;
        `;
        icon.onclick = function() {
            alert("Application is already running.");
        };
        document.body.appendChild(icon);
    }

    // 簡單的拖曳實作
    function makeDraggable(el, handleSelector) {
        if (!el) return;
        const handle = el.querySelector(handleSelector);
        if (!handle) return;

        let isDragging = false;
        let startX, startY, initialLeft, initialTop;

        handle.style.cursor = 'default';
        handle.addEventListener('mousedown', function(e) {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            const rect = el.getBoundingClientRect();
            initialLeft = rect.left;
            initialTop = rect.top;
            handle.style.background = '#000080'; // Active
        });

        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            el.style.left = (initialLeft + dx) + 'px';
            el.style.top = (initialTop + dy) + 'px';
            el.style.transform = 'none'; // Remove centering transform if any
        });

        document.addEventListener('mouseup', function() {
            isDragging = false;
        });
    }

})();
