/**
 * Baxermux Album Theme Plugin - Synthwave / Vaporwave Style
 */

(function() {
    console.log("Welcome to the Retro-Future...");

    let ytPlayer = null;
    const VIDEO_ID = '4xDzrJKXOOY'; // Lofi / Synthwave Mix

    if (document.readyState === 'complete') {
        initVapor();
    } else {
        window.addEventListener('load', initVapor);
    }

    function initVapor() {
        loadYouTubeAPI();
        createMusicPlayer();
        applyGlitchEffect();
    }

    function loadYouTubeAPI() {
        if (document.getElementById('yt-api-script')) return;
        const tag = document.createElement('script');
        tag.id = 'yt-api-script';
        tag.src = "https://www.youtube.com/iframe_api";
        document.head.appendChild(tag);

        window.onYouTubeIframeAPIReady = function() {
            ytPlayer = new YT.Player('yt-hidden-player', {
                height: '0', width: '0', videoId: VIDEO_ID,
                playerVars: { 'autoplay': 0, 'controls': 0, 'loop': 1, 'playlist': VIDEO_ID }
            });
        };
    }

    function createMusicPlayer() {
        const playerDiv = document.createElement('div');
        playerDiv.style.cssText = `
            position: fixed; bottom: 40px; right: 20px; z-index: 9999;
            background: rgba(36, 11, 54, 0.8); border: 2px solid #00ffff;
            padding: 10px; text-align: center; color: #00ffff;
            box-shadow: 5px 5px 0px #ff00ff; font-family: 'Orbitron', sans-serif;
        `;

        playerDiv.innerHTML = `
            <div style="font-size: 10px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 2px;">Synth-Stream</div>
            <div style="display: flex; gap: 5px; justify-content: center;">
                <button id="vapor-play" style="background:transparent; border:1px solid #ff00ff; color:#ff00ff; cursor:pointer; font-size:10px; padding:2px 10px;">PLAY</button>
                <button id="vapor-pause" style="background:transparent; border:1px solid #00ffff; color:#00ffff; cursor:pointer; font-size:10px; padding:2px 10px;">STOP</button>
            </div>
            <div id="yt-hidden-player" style="display:none;"></div>
        `;

        document.body.appendChild(playerDiv);

        document.getElementById('vapor-play').onclick = () => ytPlayer?.playVideo();
        document.getElementById('vapor-pause').onclick = () => ytPlayer?.pauseVideo();
    }

    function applyGlitchEffect() {
        const title = document.querySelector('.site-title');
        if (!title) return;

        setInterval(() => {
            if (Math.random() > 0.95) {
                title.style.transform = `skew(${Math.random() * 20 - 10}deg) translate(${Math.random() * 10 - 5}px)`;
                title.style.filter = `hue-rotate(${Math.random() * 360}deg)`;
                setTimeout(() => {
                    title.style.transform = 'skew(-10deg)';
                    title.style.filter = 'none';
                }, 100);
            }
        }, 200);
    }

})();
