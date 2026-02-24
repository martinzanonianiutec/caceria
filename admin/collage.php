<?php
// admin/collage.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$game_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$game_id) {
    $latest = db_fetch("SELECT id FROM games ORDER BY created_at DESC LIMIT 1");
    if ($latest)
        $game_id = $latest['id'];
}

$game = db_fetch("SELECT * FROM games WHERE id = ?", [$game_id]);
if (!$game)
    die("Partida no encontrada.");

$photos = db_fetch_all("
    SELECT s.*, t.name as team_name, t.color as team_color
    FROM submissions s 
    JOIN teams t ON s.team_id = t.id 
    WHERE t.game_id = ? AND s.is_collage = 1 
    ORDER BY s.created_at ASC
", [$game_id]);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collage SCRAPBOOK - <?= htmlspecialchars($game['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #030712;
            --text: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg);
            background-image:
                radial-gradient(circle at 20% 30%, #1e1b4b 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, #1e293b 0%, transparent 40%);
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
            width: 100vw;
            height: 100vh;
        }

        #collage-canvas {
            position: relative;
            width: 100%;
            height: 100%;
            perspective: 1000px;
        }

        .photo-recorte {
            position: absolute;
            width: 250px;
            /* Reduced from 380px */
            padding: 6px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(0, 0, 0, 0.1);
            cursor: move;
            transition: transform 1s cubic-bezier(0.4, 0, 0.2, 1),
                top 1s cubic-bezier(0.4, 0, 0.2, 1),
                left 1s cubic-bezier(0.4, 0, 0.2, 1),
                opacity 0.8s ease-in-out;
            user-select: none;
            opacity: 0;
        }

        .photo-recorte.visible {
            opacity: 1;
        }

        .photo-recorte:hover {
            z-index: 999 !important;
            transform: scale(1.05) rotate(0deg) !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7);
        }

        .photo-recorte img {
            width: 100%;
            height: auto;
            display: block;
            pointer-events: none;
        }

        #empty-msg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            opacity: 0.5;
        }

        .controls {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 2000;
            display: flex;
            gap: 10px;
        }

        .btn-mini {
            padding: 8px 15px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 800;
            border-radius: 5px;
            backdrop-filter: blur(5px);
            transition: all 0.2s;
        }

        .btn-mini:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: #fff;
        }
    </style>
</head>

<body>
    <div class="controls">
        <button class="btn-mini" onclick="initReorganize()">🔄 REORGANIZAR AHORA</button>
    </div>

    <div id="collage-canvas">
        <?php if (empty($photos)): ?>
            <div id="empty-msg">
                <h1 style="font-size: 4rem;">🖼️</h1>
                <p>No hay fotos seleccionadas para el collage.</p>
            </div>
        <?php else: ?>
            <?php foreach ($photos as $idx => $p): ?>
                <div class="photo-recorte" id="photo-<?= $idx ?>">
                    <img src="<?= BASE_URL . $p['image_url'] ?>" alt="Foto">
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        const canvas = document.getElementById('collage-canvas');
        const photos = document.querySelectorAll('.photo-recorte');
        let timeLeft = 30;
        let timerInterval;

        function getSmartPositions() {
            const winW = window.innerWidth;
            const winH = window.innerHeight;
            const photoW = 250;
            const photoH = 250; // Estimate max

            // Divide screen into a grid to minimize overlap
            const cols = Math.floor(winW / (photoW * 0.8));
            const rows = Math.floor(winH / (photoH * 0.8));
            const spots = [];

            for (let r = 0; r < rows; r++) {
                for (let c = 0; c < cols; c++) {
                    spots.push({
                        x: (c * (winW / cols)) + (winW / cols / 2) - (photoW / 2),
                        y: (r * (winH / rows)) + (winH / rows / 2) - (photoH / 2)
                    });
                }
            }

            // Shuffle spots
            for (let i = spots.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [spots[i], spots[j]] = [spots[j], spots[i]];
            }

            return spots;
        }

        async function initReorganize() {
            // Reset timer
            timeLeft = 30;

            // Fade out
            photos.forEach(p => p.classList.remove('visible'));

            // Wait for fade out
            await new Promise(r => setTimeout(r, 800));

            const spots = getSmartPositions();

            photos.forEach((photo, i) => {
                const spot = spots[i % spots.length];

                // Add jitter within spot
                const jitterX = (Math.random() * 60) - 30;
                const jitterY = (Math.random() * 60) - 30;

                const x = spot.x + jitterX;
                const y = spot.y + jitterY;
                const rot = (Math.random() * 24) - 12;

                // Stict boundaries: photoW is 250, plus padding/shadows (~280 total)
                const margin = 20;
                const maxX = window.innerWidth - 280;
                const maxY = window.innerHeight - 280;

                photo.style.left = `${Math.max(margin, Math.min(x, maxX))}px`;
                photo.style.top = `${Math.max(margin, Math.min(y, maxY))}px`;
                photo.style.transform = `rotate(${rot}deg)`;
                photo.style.zIndex = i + 1;
            });

            // Fade in
            photos.forEach(p => p.classList.add('visible'));
        }

        function startGlobalTimer() {
            timerInterval = setInterval(() => {
                timeLeft--;
                if (timeLeft <= 0) {
                    initReorganize();
                }
            }, 1000);
        }

        window.onload = () => {
            initReorganize();
            startGlobalTimer();
        };

        // Re-randomize on resize but don't reset timer
        let resizeTimer;
        window.onresize = () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(initReorganize, 500);
        };

        // Maintain Draggable logic
        photos.forEach(photo => {
            let isDragging = false;
            let startX, startY, startLeft, startTop;

            photo.addEventListener('mousedown', (e) => {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                startLeft = parseInt(photo.style.left);
                startTop = parseInt(photo.style.top);
                photo.style.zIndex = 1000; // Bring to front while dragging
                photo.style.cursor = 'grabbing';
            });

            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                photo.style.left = `${startLeft + dx}px`;
                photo.style.top = `${startTop + dy}px`;
            });

            document.addEventListener('mouseup', () => {
                isDragging = false;
                if (photo) photo.style.cursor = 'move';
            });
        });
    </script>
</body>

</html>