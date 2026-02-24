<?php
// public/join.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

$uri = $_SERVER['REQUEST_URI'];
$parts = explode('/join/', $uri);
$token = end($parts);
$token = explode('?', $token)[0];

$game = db_fetch("SELECT * FROM games WHERE join_token = ?", [$token]);
if (!$game)
    die("El código de acceso no es válido.");

if ($game['status'] == 'closed')
    die("Esta partida ha finalizado.");

// PERSISTENCE: If the player is already logged into THIS game, skip the join form
if (isset($_SESSION['player_id']) && isset($_SESSION['game_id']) && $_SESSION['game_id'] == $game['id']) {
    header("Location: /caceria/dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['player_name'])) {
    $player_name = trim($_POST['player_name']);
    $game_id = $game['id'];

    // Validate name uniqueness within the game
    $existing = db_fetch("
        SELECT COUNT(*) as count 
        FROM players p 
        JOIN teams t ON p.team_id = t.id 
        WHERE t.game_id = ? AND p.name = ?
    ", [$game_id, $player_name]);

    if ($existing['count'] > 0) {
        $error = "Lo sentimos, ese nombre ya está en uso en esta partida. Por favor, elige otro.";
    } else {
        $players_per_team = (int) $game['players_per_team'];

        $available_team = db_fetch("
            SELECT t.id, COUNT(p.id) as player_count 
            FROM teams t 
            LEFT JOIN players p ON t.id = p.team_id 
            WHERE t.game_id = ? 
            GROUP BY t.id 
            HAVING player_count < ? 
            ORDER BY t.created_at ASC 
            LIMIT 1
        ", [$game_id, $players_per_team]);

        $team_id = null;
        if ($available_team) {
            $team_id = $available_team['id'];
        } else {
            $current_teams_count = db_fetch("SELECT COUNT(*) as count FROM teams WHERE game_id = ?", [$game_id])['count'];
            
            // NEW: Fetch colors from the database
            $available_colors = db_fetch_all("SELECT name, hex FROM team_colors ORDER BY id ASC");
            
            if (!empty($available_colors)) {
                // Use colors defined in the admin
                $color_data = $available_colors[$current_teams_count % count($available_colors)];
                $team_name = $color_data['name'];
                $color = $color_data['hex'];
            } else {
                // Last resort fallback if NO colors are defined in the admin
                $team_name = "Equipo " . ($current_teams_count + 1);
                $fallback_colors = ['#6366f1', '#ec4899', '#06b6d4', '#f59e0b', '#8b5cf6', '#10b981', '#f43f5e', '#a855f7'];
                $color = $fallback_colors[$current_teams_count % count($fallback_colors)];
            }

            $team_id = db_insert("INSERT INTO teams (game_id, name, color) VALUES (?, ?, ?)", [$game_id, $team_name, $color]);
        }

        if ($team_id) {
            $player_id = db_insert("INSERT INTO players (team_id, name) VALUES (?, ?)", [$team_id, $player_name]);
            $_SESSION['player_id'] = $player_id;
            $_SESSION['team_id'] = $team_id;
            $_SESSION['game_id'] = $game_id;
            setcookie('caceria_puid', $player_id, time() + (30 * 24 * 60 * 60), '/');
            header("Location: /caceria/dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unirse a la Misión - Cacería</title>
    <link rel="stylesheet" href="/caceria/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
</head>

<body style="display: flex; align-items: center; justify-content: center;">
    <div class="container" style="max-width: 400px; padding: 0 1.5rem;">
        <div class="card"
            style="text-align: center; border-color: var(--primary); box-shadow: 0 0 30px var(--primary-glow); animation: glow 4s infinite alternate;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🎮</div>
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem; color: #fff;">¿LISTO PARA COMENZAR?</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem; font-weight: 600;">Te estás uniendo a: <span
                    style="color: var(--accent);"><?= htmlspecialchars($game['name']) ?></span></p>

            <?php if ($error): ?>
                <div
                    style="background: rgba(244, 63, 94, 0.1); border: 1px solid #f43f5e; color: #f43f5e; padding: 0.75rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-size: 0.875rem; font-weight: 600;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group" style="text-align: left;">
                    <label>NOMBRE O NOMBRE DE USUARIO</label>
                    <input type="text" name="player_name" class="form-control" placeholder="Escribe tu nombre..."
                        required autofocus style="background: rgba(15, 23, 42, 0.8);">
                </div>
                <button type="submit" class="btn btn-primary"
                    style="width: 100%; height: 55px; font-size: 1rem; margin-top: 1rem;">
                    INICIAR MISIÓN
                </button>
            </form>
        </div>
    </div>
</body>

</html>