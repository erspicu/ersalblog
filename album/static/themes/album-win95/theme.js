/**
 * Baxermux Album Theme Plugin - Windows 95 Style
 */

(function() {
    console.log("Starting Windows 95...");

    if (document.readyState === 'complete') {
        initWin95();
    } else {
        window.addEventListener('load', initWin95);
    }

    function initWin95() {
        playStartupSound();
        createTaskbar();
        injectMenuBar();
        
        // 監聽路由變化，因為 SPA 渲染需要時間
        const observer = new MutationObserver(() => {
            if (AppState.currentView === 'photo') {
                injectPropertiesButton();
            }
        });
        observer.observe(document.getElementById('app-container'), { childList: true });

        // 藍白當機彩蛋 (連點標題 5 次)
        let clickCount = 0;
        document.querySelector('.site-title').addEventListener('click', () => {
            clickCount++;
            if (clickCount >= 5) triggerBSOD();
        });
    }

    function injectPropertiesButton() {
        if (document.getElementById('win95-props-btn')) return;
        
        const container = document.querySelector('.photo-viewer-container');
        if (!container) return;

        const btn = document.createElement('button');
        btn.id = 'win95-props-btn';
        btn.className = 'btn properties-btn';
        btn.style.cssText = 'position: absolute; bottom: 10px; right: 10px; z-index: 100;';
        btn.innerText = 'Properties';
        btn.onclick = showPropertiesDialog;
        container.appendChild(btn);
    }

    function showPropertiesDialog() {
        // 抓取原本隱藏面板中的內容
        const infoContent = document.querySelector('.info-card')?.innerHTML || 'No info available.';
        const exifContent = document.querySelector('.exif-card')?.innerHTML || 'No EXIF data.';

        const dialog = document.createElement('div');
        dialog.id = 'props-dialog';
        dialog.style.cssText = `
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 800px; background: #C0C0C0; border: 2px outset white;
            border-right-color: black; border-bottom-color: black;
            box-shadow: 5px 5px 15px rgba(0,0,0,0.5); z-index: 10001;
            font-family: sans-serif; font-size: 12px; padding: 2px;
        `;

        dialog.innerHTML = `
            <div style="background: #000080; color: white; padding: 2px 5px; font-weight: bold; display: flex; justify-content: space-between;">
                <span>Properties</span>
                <button onclick="this.closest('#props-dialog').remove()" style="background: #C0C0C0; border: 1px outset white; border-right-color: black; border-bottom-color: black; width: 16px; height: 14px; line-height: 12px; font-size: 9px; cursor: pointer; padding: 0;">X</button>
            </div>
            <div style="padding: 10px;">
                <div class="win95-tabs">
                    <div class="win95-tab active" data-target="tab-general">General</div>
                    <div class="win95-tab" data-target="tab-details">Details</div>
                </div>
                <div id="tab-general" class="win95-tab-content">
                    ${infoContent}
                </div>
                <div id="tab-details" class="win95-tab-content" style="display:none;">
                    ${exifContent}
                </div>
                <div style="text-align: right; margin-top: 10px;">
                    <button class="btn" style="width: 80px;" onclick="this.closest('#props-dialog').remove()">OK</button>
                </div>
            </div>
        `;

        document.body.appendChild(dialog);

        // Tab 切換邏輯
        const tabs = dialog.querySelectorAll('.win95-tab');
        tabs.forEach(tab => {
            tab.onclick = () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                dialog.querySelectorAll('.win95-tab-content').forEach(c => c.style.display = 'none');
                document.getElementById(tab.dataset.target).style.display = 'block';
            };
        });
    }

    function playStartupSound() {
        if (sessionStorage.getItem('win95_booted')) return;
        sessionStorage.setItem('win95_booted', 'true');

        // Simple synth sound resembling the startup chime
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            const ctx = new AudioContext();
            
            const playNote = (freq, time, dur) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.frequency.value = freq;
                osc.type = 'sine';
                gain.gain.setValueAtTime(0.1, time);
                gain.gain.exponentialRampToValueAtTime(0.01, time + dur);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(time);
                osc.stop(time + dur);
            };

            const now = ctx.currentTime;
            playNote(392.00, now, 2);       // G4
            playNote(523.25, now + 0.2, 2); // C5
            playNote(659.25, now + 0.4, 2); // E5
            playNote(783.99, now + 0.6, 3); // G5
            
        } catch(e) {}
    }

    function createTaskbar() {
        const taskbar = document.createElement('div');
        taskbar.className = 'win95-taskbar';
        taskbar.innerHTML = `
            <div class="start-btn" onclick="alert('Clicking Start does nothing yet, just like real life.')">
                <img src="static/themes/album-win95/start_icon.png" alt="logo">
                Start
            </div>
            <div style="border: 2px inset white; padding: 2px 5px; background: #dfdfdf; flex-grow: 0; margin-right: auto; width: 150px; display: flex; align-items: center; font-weight: bold;">
                <img src="static/themes/album-win95/start_icon.png" style="width:16px; height:16px; margin-right:5px;">
                Album Explorer
            </div>
            <div class="taskbar-tray" id="win95-clock">12:00 PM</div>
        `;
        document.body.appendChild(taskbar);

        // Clock
        setInterval(() => {
            const now = new Date();
            let hours = now.getHours();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; 
            const minutes = now.getMinutes().toString().padStart(2, '0');
            document.getElementById('win95-clock').innerText = `${hours}:${minutes} ${ampm}`;
        }, 1000);
    }

    function injectMenuBar() {
        const header = document.getElementById('album-header-section');
        if (!header) return;
        
        const menu = document.createElement('div');
        menu.className = 'win95-menubar';
        menu.innerHTML = `
            <span><u>F</u>ile</span>
            <span><u>E</u>dit</span>
            <span><u>V</u>iew</span>
            <span id="win95-help-menu"><u>H</u>elp</span>
        `;
        header.insertBefore(menu, header.firstChild);

        // Help Menu Click Logic
        document.getElementById('win95-help-menu').onclick = showAboutDialog;
    }

    function showAboutDialog() {
        const dialog = document.createElement('div');
        dialog.id = 'about-dialog';
        dialog.style.cssText = `
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 350px; background: #C0C0C0; border: 2px outset white;
            border-right-color: black; border-bottom-color: black;
            box-shadow: 5px 5px 15px rgba(0,0,0,0.5); z-index: 10002;
            font-family: sans-serif; font-size: 12px; padding: 2px;
        `;

        dialog.innerHTML = `
            <div style="background: #000080; color: white; padding: 2px 5px; font-weight: bold; display: flex; justify-content: space-between;">
                <span>About Baxermux Album</span>
                <button onclick="this.closest('#about-dialog').remove()" style="background: #C0C0C0; border: 1px outset white; border-right-color: black; border-bottom-color: black; width: 16px; height: 14px; line-height: 12px; font-size: 9px; cursor: pointer; padding: 0;">X</button>
            </div>
            <div style="padding: 20px; display: flex; align-items: flex-start; gap: 15px;">
                <img src="static/themes/album-win95/start_icon.png" style="width: 32px; height: 32px;">
                <div>
                    <p style="font-weight: bold; margin-bottom: 5px;">Baxermux Photography Blog</p>
                    <p style="color: #444; margin-bottom: 10px;">Version 1.0 (Build 950)</p>
                    <p style="border-top: 1px solid #808080; padding-top: 10px;">
                        放一些Blog用到的素材照片。本產品授權予所有攝影愛好者。
                    </p>
                    <p style="margin-top: 20px;">Copyright &copy; 1995-2026 BaxerMux.</p>
                </div>
            </div>
            <div style="text-align: right; padding: 10px;">
                <button class="btn" style="width: 80px;" onclick="this.closest('#about-dialog').remove()">OK</button>
            </div>
        `;

        document.body.appendChild(dialog);
    }

    function triggerBSOD() {
        const bsod = document.createElement('div');
        bsod.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #0000AA; color: #FFFFFF; font-family: 'Courier New', monospace;
            padding: 50px; z-index: 999999; font-size: 18px; line-height: 1.5;
            cursor: none;
        `;
        bsod.innerHTML = `
            <div style="background:#AAAAAA; color:#0000AA; display:inline-block; padding: 0 10px; margin-bottom: 20px;">Windows</div>
            <p>A fatal exception 0E has occurred at 0028:C0011E36 in VXD VMM(01) + 00010E36. The current application will be terminated.</p>
            <p>* Press any key to terminate the current application.</p>
            <p>* Press CTRL+ALT+DEL again to restart your computer. You will lose any unsaved information in all applications.</p>
            <br>
            <p style="text-align: center;">Press any key to continue _</p>
        `;
        document.body.appendChild(bsod);
        
        const removeBsod = () => {
            bsod.remove();
            document.removeEventListener('keydown', removeBsod);
            document.removeEventListener('click', removeBsod);
        };
        
        setTimeout(() => {
            document.addEventListener('keydown', removeBsod);
            document.addEventListener('click', removeBsod);
        }, 1000);
    }

})();
