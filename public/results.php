<?php
// public/results.php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: /caceria/");
    exit;
}

$game_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// If no game_id passed, pick the latest game as default
if (!$game_id) {
    $latest = db_fetch("SELECT id FROM games ORDER BY created_at DESC LIMIT 1");
    if ($latest)
        $game_id = $latest['id'];
}

$game = db_fetch("SELECT * FROM games WHERE id = ?", [$game_id]);

if (!$game) {
    die("Partida no encontrada.");
}

$teams = db_fetch_all("
    SELECT t.*, 
    (SELECT COUNT(*) FROM submissions s WHERE s.team_id = t.id) as completed_count,
    (SELECT MAX(created_at) FROM submissions s WHERE s.team_id = t.id) as last_submission
    FROM teams t 
    WHERE t.game_id = ? 
    ORDER BY completed_count DESC, finished_at ASC, last_submission ASC
", [$game_id]);

$total_challenges = db_fetch("SELECT COUNT(*) as total FROM challenges WHERE game_id = ?", [$game_id])['total'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados en Vivo -
        <?= htmlspecialchars($game['name']) ?>
    </title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <meta http-equiv="refresh" content="30">
</head>

<body style="background: #0f172a;">
    <div class="container">
        <?php include '../admin/includes/navbar.php'; ?>
        <header style="margin-bottom: 2.5rem; text-align: center;">
            <div style="margin-bottom: 1.5rem;">
                <img src="assets/img/Isologotipo.png" style="max-width: 120px; opacity: 0.9;">
            </div>
            <h1
                style="font-size: 2.5rem; margin-bottom: 0.5rem; background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                TABLA DE POSICIONES</h1>
            <p style="color: var(--text-muted); font-size: 1.1rem; font-weight: 600;">
                <?= htmlspecialchars($game['name']) ?>
            </p>
        </header>

        <div style="display: grid; gap: 1rem; margin-bottom: 4rem;">
            <?php foreach ($teams as $idx => $t):
                $percent = $total_challenges > 0 ? round(($t['completed_count'] / $total_challenges) * 100) : 0;
                $is_me = (isset($_SESSION['team_id']) && $_SESSION['team_id'] == $t['id']);

                $ranks = ['🥇', '🥈', '🥉'];
                $medal = $idx < 3 ? $ranks[$idx] : '#' . ($idx + 1);
                ?>
                <div class="card" style="border-left: 4px solid <?= htmlspecialchars($t['color']) ?>; padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 1.25rem;">
                            <div
                                style="width: 45px; height: 45px; background: rgba(15, 23, 42, 0.6); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; border: 1px solid var(--border);">
                                <?= $medal ?>
                            </div>
                            <div>
                                <h3
                                    style="margin: 0; font-size: 1.25rem; color: <?= htmlspecialchars($t['color']) ?>; text-transform: uppercase; letter-spacing: 0.05em;">
                                    <?= htmlspecialchars($t['name']) ?>
                                </h3>
                                <div
                                    style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-top: 2px;">
                                    <?= $t['completed_count'] ?> /
                                    <?= $total_challenges ?> RETOS
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 800; font-size: 1.1rem; color: var(--text);">
                                <?= $percent ?>%
                            </div>
                        </div>
                    </div>

                    <div class="progress-container"
                        style="height: 8px; margin-top: 1rem; background: rgba(15, 23, 42, 0.4);">
                        <div class="progress-bar"
                            style="width: <?= $percent ?>%; background: <?= htmlspecialchars($t['color']) ?>; box-shadow: 0 0 10px <?= htmlspecialchars($t['color']) ?>40;">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>