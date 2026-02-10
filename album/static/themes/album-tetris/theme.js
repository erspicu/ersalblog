/**
 * Baxermux Album Theme Plugin - Tetris Arcade
 * Implements a background autonomous Tetris simulation loop.
 */

(function() {
    console.log("Tetris Arcade Loaded. INSERT COIN.");

    window.addEventListener('themePluginReady', function(e) {
        initTetrisBackground();
    });

    if (document.readyState === 'complete') {
        initTetrisBackground();
    } else {
        window.addEventListener('load', initTetrisBackground);
    }

    function initTetrisBackground() {
        if (window.TETRIS_INITIALIZED) return;
        window.TETRIS_INITIALIZED = true;

        const canvas = document.createElement('canvas');
        canvas.id = 'tetris-bg';
        canvas.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; background: #050505; opacity: 0.6;';
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        let animationFrameId;

        // --- Game Config ---
        const BLOCK_SIZE = 30; // Increased size
        let COLS, ROWS;
        let grid = [];
        let offsetX = 0; // Horizontal offset to center the board

        // Tetromino Definitions
        const SHAPES = [
            [[1, 1, 1, 1]], // I
            [[1, 1], [1, 1]], // O
            [[0, 1, 0], [1, 1, 1]], // T
            [[1, 0, 0], [1, 1, 1]], // L
            [[0, 0, 1], [1, 1, 1]], // J
            [[0, 1, 1], [1, 1, 0]], // S
            [[1, 1, 0], [0, 1, 1]]  // Z
        ];
        const COLORS = [
            null,
            '#00ffff', // I - Cyan
            '#ffff00', // O - Yellow
            '#ff00ff', // T - Magenta
            '#ff7f00', // L - Orange
            '#0000ff', // J - Blue
            '#00ff00', // S - Green
            '#ff0000'  // Z - Red
        ];

        let piece = null;
        let score = 0;
        let dropCounter = 0;
        let dropInterval = 60; // Faster
        let lastTime = 0;
        let gameOverState = false;
        let gameOverTimer = 0;

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            
            // Limit board width to 1/3 of screen
            const targetWidth = canvas.width / 3;
            COLS = Math.floor(targetWidth / BLOCK_SIZE);
            if (COLS < 10) COLS = 10; // Ensure at least 10 columns for gameplay look
            
            ROWS = Math.floor(canvas.height / BLOCK_SIZE);
            
            // Calculate X offset to center
            offsetX = Math.floor((canvas.width - (COLS * BLOCK_SIZE)) / 2);
            
            resetGame();
        }

        function createGrid() {
            return Array.from({ length: ROWS }, () => Array(COLS).fill(0));
        }

        function resetGame() {
            grid = createGrid();
            score = 0;
            gameOverState = false;
            spawnPiece();
        }

        function spawnPiece() {
            const typeId = Math.floor(Math.random() * SHAPES.length);
            const shape = SHAPES[typeId];
            const colorId = typeId + 1;
            
            piece = {
                shape: shape,
                color: colorId,
                x: Math.floor(Math.random() * (COLS - shape[0].length)),
                y: 0
            };

            if (collide(grid, piece)) {
                gameOverState = true;
                gameOverTimer = 0;
            }
        }

        function collide(arena, player) {
            const [m, o] = [player.shape, player];
            for (let y = 0; y < m.length; ++y) {
                for (let x = 0; x < m[y].length; ++x) {
                    if (m[y][x] !== 0 &&
                        (arena[y + o.y] && arena[y + o.y][x + o.x]) !== 0) {
                        return true;
                    }
                }
            }
            return false;
        }

        function merge(arena, player) {
            player.shape.forEach((row, y) => {
                row.forEach((value, x) => {
                    if (value !== 0) {
                        if(arena[y + player.y] && arena[y + player.y][x + player.x] !== undefined) {
                            arena[y + player.y][x + player.x] = player.color;
                            score += 10; // Increase score when merging
                        }
                    }
                });
            });
        }

        function drawMatrix(matrix, offset, context, isGlobal = false) {
            matrix.forEach((row, y) => {
                row.forEach((value, x) => {
                    if (value !== 0) {
                        context.fillStyle = COLORS[value];
                        context.shadowBlur = 8;
                        context.shadowColor = COLORS[value];
                        
                        // Apply offsetX to translate to center
                        const dx = (x + offset.x) * BLOCK_SIZE + (isGlobal ? 0 : offsetX);
                        const dy = (y + offset.y) * BLOCK_SIZE;

                        context.fillRect(dx, dy, BLOCK_SIZE - 2, BLOCK_SIZE - 2);

                        context.fillStyle = 'rgba(255,255,255,0.4)';
                        context.fillRect(dx + 2, dy + 2, BLOCK_SIZE - 6, BLOCK_SIZE - 6);
                        context.shadowBlur = 0;
                    }
                });
            });
        }

        function drawUI() {
            // --- 1. Fill sides with random blocks ---
            const sideBlockSize = BLOCK_SIZE;
            const rows = Math.ceil(canvas.height / sideBlockSize);
            const leftCols = Math.floor(offsetX / sideBlockSize);
            const rightStart = offsetX + (COLS * BLOCK_SIZE);
            const rightCols = Math.ceil((canvas.width - rightStart) / sideBlockSize);

            ctx.globalAlpha = 0.3; // Make background blocks subtle
            
            // Left Side
            for (let y = 0; y < rows; y++) {
                for (let x = 0; x < leftCols; x++) {
                    // Semi-random pattern based on coordinates
                    const seed = (x * 7 + y * 13) % 8;
                    if (seed > 0 && seed < 7) {
                        ctx.fillStyle = COLORS[seed];
                        ctx.fillRect(x * sideBlockSize, y * sideBlockSize, sideBlockSize - 2, sideBlockSize - 2);
                    }
                }
            }

            // Right Side
            for (let y = 0; y < rows; y++) {
                for (let x = 0; x < rightCols; x++) {
                    const seed = ((x + 20) * 11 + y * 17) % 8;
                    if (seed > 0 && seed < 7) {
                        ctx.fillStyle = COLORS[seed];
                        ctx.fillRect(rightStart + (x * sideBlockSize), y * sideBlockSize, sideBlockSize - 2, sideBlockSize - 2);
                    }
                }
            }
            ctx.globalAlpha = 1.0;

            // --- 2. Score Info Box ---
            // Positioned at the right side, vertically centered
            const uiX = canvas.width - 120;
            const uiY = canvas.height / 2;
            ctx.textAlign = 'center';
            
            // UI Background Vertical Box
            ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
            ctx.fillRect(uiX - 80, uiY - 90, 160, 180);
            ctx.strokeStyle = varColor('primary');
            ctx.lineWidth = 2;
            ctx.strokeRect(uiX - 80, uiY - 90, 160, 180);

            // SCORE
            ctx.font = 'bold 14px "Courier New"';
            ctx.fillStyle = '#fff';
            ctx.fillText("SCORE", uiX, uiY - 50);
            
            ctx.font = 'bold 24px "Courier New"';
            ctx.fillStyle = '#0f0';
            ctx.fillText(score.toString().padStart(6, '0'), uiX, uiY - 15);

            // Divider
            ctx.strokeStyle = '#444';
            ctx.beginPath();
            ctx.moveTo(uiX - 60, uiY + 5);
            ctx.lineTo(uiX + 60, uiY + 5);
            ctx.stroke();

            // HIGH SCORE
            ctx.font = 'bold 14px "Courier New"';
            ctx.fillStyle = '#fff';
            ctx.fillText("HI-SCORE", uiX, uiY + 35);
            
            ctx.font = 'bold 24px "Courier New"';
            ctx.fillStyle = '#f00'; 
            ctx.fillText("050000", uiX, uiY + 70);

            // Draw Board Border
            ctx.strokeStyle = '#333';
            ctx.lineWidth = 1;
            ctx.strokeRect(offsetX - 2, 0, (COLS * BLOCK_SIZE) + 4, canvas.height);
        }

        function varColor(name) {
            return getComputedStyle(document.documentElement).getPropertyValue('--' + name + '-color').trim();
        }

        function drawGameOver(time) {
             ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
             ctx.fillRect(offsetX, 0, COLS * BLOCK_SIZE, canvas.height);

             ctx.font = 'bold 40px "Courier New"';
             ctx.fillStyle = (Math.floor(time / 400) % 2 === 0) ? '#ff0000' : '#ffff00';
             ctx.textAlign = 'center';
             ctx.fillText("GAME OVER", canvas.width / 2, canvas.height / 2);
        }

        function update(time = 0) {
            const deltaTime = time - lastTime;
            lastTime = time;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // 1. Draw UI (Score)
            drawUI();

            // 2. Draw Grid
            drawMatrix(grid, {x: 0, y: 0}, ctx);

            if (gameOverState) {
                gameOverTimer += deltaTime;
                drawGameOver(time);
                if (gameOverTimer > 3000) resetGame();
            } else {
                if(piece) drawMatrix(piece.shape, piece, ctx);

                dropCounter += deltaTime;
                if (dropCounter > dropInterval) {
                    piece.y++;
                    if (collide(grid, piece)) {
                        piece.y--;
                        merge(grid, piece);
                        spawnPiece();
                    }
                    dropCounter = 0;
                }
            }

            animationFrameId = requestAnimationFrame(update);
        }

        window.addEventListener('resize', resize);
        resize(); // Init
        update();
    }
})();
