/**
 * Baxermux Album Theme Plugin - MS-DOS Style
 * Features: BIOS POST Simulation, Keyboard Typing Effect
 */

(function() {
    console.log("Booting MS-DOS...");

    if (document.readyState === 'complete') {
        initDosEffects();
    } else {
        window.addEventListener('load', initDosEffects);
    }

    function initDosEffects() {
        // 1. BIOS POST Screen (Only show once per session ideally, but for now every reload)
        if (!sessionStorage.getItem('dos_booted')) {
            showBiosPost();
            sessionStorage.setItem('dos_booted', 'true');
        }

        // 2. Keyboard Navigation Hint
        console.log("Tips: Use Tab and Enter to navigate like a pro.");
    }

    function showBiosPost() {
        const postScreen = document.createElement('div');
        postScreen.id = 'bios-post';
        postScreen.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #000; color: #BBB; font-family: 'Courier New', monospace;
            font-size: 16px; padding: 20px; z-index: 99999;
            white-space: pre; overflow: hidden;
        `;
        document.body.appendChild(postScreen);

        const lines = [
            "PhoenixBIOS 4.0 Release 6.0",
            "Copyright 1985-1994 Phoenix Technologies Ltd.",
            "All Rights Reserved",
            "",
            "CPU = Pentium II 133 MHz",
            "640K System RAM Passed",
            "15360K Extended RAM Passed",
            "System BIOS shadowed",
            "Video BIOS shadowed",
            "",
            "Fixed Disk 0: BAXERMUX_ALBUM_DRIVE_C",
            "ATAPI CD-ROM: SONY CD-ROM CDU311",
            "Mouse initialized",
            "",
            "Press <DEL> to enter SETUP",
            "Booting from C:...",
            "",
            "Starting MS-DOS..."
        ];

        let lineIndex = 0;
        const typeLine = () => {
            if (lineIndex < lines.length) {
                const line = document.createElement('div');
                line.textContent = lines[lineIndex];
                postScreen.appendChild(line);
                
                // Random delay for realism
                let delay = 100 + Math.random() * 300;
                if (lineIndex === 5 || lineIndex === 6) delay = 600; // Memory test slower
                
                lineIndex++;
                setTimeout(typeLine, delay);
            } else {
                // Finish booting
                setTimeout(() => {
                    postScreen.innerHTML += '<div style="color:white; margin-top:20px;">C:\> ALBUM.EXE</div>';
                    setTimeout(() => {
                        postScreen.style.display = 'none';
                    }, 1000);
                }, 800);
            }
        };

        typeLine();
    }

})();
