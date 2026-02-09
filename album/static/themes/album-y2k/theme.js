/**
 * Baxermux Album Theme Plugin - Y2K Style (YouTube BG Music & Construction Sticker)
 */

(function() {
    console.log("Y2K Experience Loaded! Under Construction Mode.");

    let ytPlayer = null;
    const VIDEO_ID = 'ax-efEg60yE'; 

    window.addEventListener('themePluginReady', function(e) {
        initY2KEffects();
    });

    if (document.readyState === 'complete') {
        initY2KEffects();
    } else {
        window.addEventListener('load', initY2KEffects);
    }

    function initY2KEffects() {
        if (window.Y2K_INITIALIZED) return;
        window.Y2K_INITIALIZED = true;

        alert('Welcome to Baxermux 21 Century Album!\n\nThis site is UNDER CONSTRUCTION...');

        let userName = prompt('請輸入您的大名：', '訪客');
        if (userName) {
            updateUserGreeting(userName);
        }

        loadYouTubeAPI();
        createMusicPlayer();
        
        // 只有施工中貼圖
        scatterConstructionStickers();

        // --- 土氣特效 E: 模擬 IE 指令碼錯誤 ---
        setTimeout(showIEScriptError, 5000); // 載入 5 秒後跳出，增加驚喜感
    }

    function showIEScriptError() {
        const errorDiv = document.createElement('div');
        errorDiv.id = 'ie-script-error';
        errorDiv.style.cssText = `
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 400px; background: #c0c0c0; border: 2px outset white;
            border-right-color: black; border-bottom-color: black;
            box-shadow: 10px 10px 0px rgba(0,0,0,0.5); z-index: 10001;
            font-family: 'MS Sans Serif', 'Arial', sans-serif; font-size: 12px;
            color: black; padding: 2px;
        `;

        errorDiv.innerHTML = `
            <div style="background: navy; color: white; padding: 2px 5px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
                <span>Internet Explorer</span>
                <button onclick="this.closest('#ie-script-error').remove()" style="background: #c0c0c0; border: 1px outset white; border-right-color: black; border-bottom-color: black; width: 16px; height: 14px; line-height: 12px; font-size: 9px; cursor: pointer; padding: 0;">X</button>
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
                    <button onclick="this.closest('#ie-script-error').remove()" style="background: #c0c0c0; border: 2px outset white; border-right-color: black; border-bottom-color: black; padding: 2px 20px; cursor: pointer;">Yes</button>
                    <button onclick="this.closest('#ie-script-error').remove()" style="background: #c0c0c0; border: 2px outset white; border-right-color: black; border-bottom-color: black; padding: 2px 20px; cursor: pointer;">No</button>
                </div>
            </div>
        `;

        document.body.appendChild(errorDiv);
    }

    function updateUserGreeting(name) {
        const titleEl = document.querySelector('.site-title');
        if (titleEl) {
            const greeting = document.createElement('div');
            greeting.style.fontSize = '12px';
            greeting.style.color = '#00ff00';
            greeting.style.marginTop = '5px';
            greeting.style.animation = 'tacky-blink 0.5s steps(1) infinite';
            greeting.innerHTML = 'HI! ' + name + '，你是第 01314 位訪客';
            titleEl.parentNode.appendChild(greeting);
        }
    }

    function scatterConstructionStickers() {
        // 只使用下載好的施工中 GIF (更新為 themes 目錄結構)
        const stickerPath = 'static/themes/album-y2k/construction.gif';

        const count = 12; // 增加數量，讓它到處都是
        for (let i = 0; i < count; i++) {
            const img = document.createElement('img');
            img.src = stickerPath;
            img.style.position = 'fixed';
            img.style.top = (Math.random() * 90) + '%';
            
            // 隨機分布在左右兩側邊緣
            if (Math.random() > 0.5) {
                img.style.left = (Math.random() * 80) + 'px';
            } else {
                img.style.right = (Math.random() * 80) + 'px';
            }
            
            img.style.zIndex = '1000';
            img.style.width = '64px'; // 固定大小更有那種圖標感
            img.style.pointerEvents = 'none';
            document.body.appendChild(img);
        }
    }

    function loadYouTubeAPI() {
        if (document.getElementById('yt-api-script')) return;
        const tag = document.createElement('script');
        tag.id = 'yt-api-script';
        tag.src = "https://www.youtube.com/iframe_api";
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        window.onYouTubeIframeAPIReady = function() {
            ytPlayer = new YT.Player('yt-hidden-player', {
                height: '0', width: '0', videoId: VIDEO_ID,
                playerVars: { 'autoplay': 0, 'controls': 0 },
                events: { 'onReady': onPlayerReady }
            });
        };
    }

    function onPlayerReady(event) {
        const statusText = document.getElementById('yt-status-text');
        if (statusText) statusText.innerText = '♫ Media Ready';
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

        playerDiv.innerHTML = `
            <div style="background:navy; color:white; font-size:10px; padding:2px; margin-bottom:5px; font-weight:bold;">
                Y2K YT-Player
            </div>
            <div id="yt-status-text" style="font-size:9px; color:black; margin-bottom:5px;">
                Connecting...
            </div>
            <div style="display:flex; gap:2px; justify-content:center;">
                <button id="y2k-play-btn" class="btn" style="padding:2px 5px; font-size:10px;">▶ PLAY</button>
                <button id="y2k-stop-btn" class="btn" style="padding:2px 5px; font-size:10px;">■ STOP</button>
            </div>
            <div id="yt-hidden-player" style="display:none;"></div>
        `;

        document.body.appendChild(playerDiv);

        document.getElementById('y2k-play-btn').onclick = function() {
            if (ytPlayer) ytPlayer.playVideo();
        };

        document.getElementById('y2k-stop-btn').onclick = function() {
            if (ytPlayer) ytPlayer.stopVideo();
        };
    }

})();
