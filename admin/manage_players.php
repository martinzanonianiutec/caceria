<?php
// admin/manage_players.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$game_id = (int) $_GET['id'];
$game = db_fetch("SELECT * FROM games WHERE id = ?", [$game_id]);
if (!$game)
    die("Partida no encontrada");

$error = '';
$success = '';

// Handle Edit Player
if (isset($_POST['edit_player'])) {
    $player_id = (int) $_POST['player_id'];
    $player_name = trim($_POST['player_name']);
    $new_team_id = (int) $_POST['team_id'];

    if (empty($player_name)) {
        $error = "El nombre del jugador no puede estar vacío.";
    } else {
        db_query("UPDATE players SET name = ?, team_id = ? WHERE id = ?", [$player_name, $new_team_id, $player_id]);
        $success = "Datos del jugador actualizados.";
    }
}

// Handle Delete Player
if (isset($_POST['delete_player'])) {
    $player_id = (int) $_POST['player_id'];
    db_query("DELETE FROM players WHERE id = ?", [$player_id]);
    $success = "Jugador eliminado.";
}

$teams = db_fetch_all("SELECT * FROM teams WHERE game_id = ? ORDER BY created_at ASC", [$game_id]);
$team_ids = array_column($teams, 'id');
$players = [];
if (!empty($team_ids)) {
    $in_clause = implode(',', $team_ids);
    $players = db_fetch_all("SELECT * FROM players WHERE team_id IN ($in_clause) ORDER BY name ASC");
}

$players_by_team = [];
foreach ($players as $p) {
    $players_by_team[$p['team_id']][] = $p;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Participantes -
        <?= htmlspecialchars($game['name']) ?>
    </title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <?php include 'includes/navbar.php'; ?>
        <header style="margin-bottom: 2.5rem;">
            <a href="game_details.php?id=<?= $game_id ?>"
                style="color: var(--primary); text-decoration: none; font-weight: 800;">&larr; VOLVER A
                CONFIGURACIÓN</a>
            <h1
                style="margin-top: 1rem; font-size: 2.5rem; background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                PARTICIPANTES</h1>
            <p style="color: var(--text-muted); font-weight: 600;">Reorganización de integrantes entre equipos</p>
        </header>

        <?php if ($error): ?>
            <div
                style="background: rgba(244, 63, 94, 0.1); border: 1px solid #f43f5e; color: #f43f5e; padding: 1rem; border-radius: var(--radius); margin-bottom: 2rem; font-weight: 600;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div
                style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 1rem; border-radius: var(--radius); margin-bottom: 2rem; font-weight: 600;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($teams as $t): ?>
                <div class="card" style="border-top: 4px solid <?= htmlspecialchars($t['color']) ?>;">
                    <h3
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; color: <?= htmlspecialchars($t['color']) ?>;">
                        <span>EQUIPO
                            <?= htmlspecialchars($t['name']) ?>
                        </span>
                        <span
                            style="font-size: 0.8rem; background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 4px; color: var(--text-muted);">
                            <?= isset($players_by_team[$t['id']]) ? count($players_by_team[$t['id']]) : 0 ?> INTEGRANTES
                        </span>
                    </h3>

                    <div style="display: grid; gap: 0.75rem; margin-bottom: 1.5rem;">
                        <?php if (isset($players_by_team[$t['id']])): ?>
                            <?php foreach ($players_by_team[$t['id']] as $p): ?>
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2); padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                                    <span style="font-weight: 600;">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </span>
                                    <div style="display: flex; gap: 0.4rem;">
                                        <button class="btn btn-secondary" style="padding: 0.4rem; font-size: 0.7rem;"
                                            onclick="editPlayer(<?= htmlspecialchars(json_encode($p)) ?>)">
                                            ✏️
                                        </button>
                                        <form method="POST" style="display:inline;"
                                            onsubmit="return confirm('¿Eliminar a este jugador?')">
                                            <input type="hidden" name="player_id" value="<?= $p['id'] ?>">
                                            <button type="submit" name="delete_player" class="btn"
                                                style="background: #f43f5e; color: white; padding: 0.4rem; font-size: 0.7rem;">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--text-muted); font-size: 0.8rem; text-align: center; font-style: italic;">Sin
                                integrantes</p>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Edit Player Modal -->
    <div id="editPlayerModal" class="modal">
        <div class="modal-content card">
            <h2 style="margin-bottom: 1.5rem;">Editar Participante</h2>
            <form method="POST">
                <input type="hidden" name="player_id" id="edit-player-id">
                <div class="form-group">
                    <label>Nombre del Jugador</label>
                    <input type="text" name="player_name" id="edit-player-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Mover al Equipo</label>
                    <select name="team_id" id="edit-player-team" class="form-control">
                        <?php foreach ($teams as $t): ?>
                            <option value="<?= $t['id'] ?>">
                                <?= htmlspecialchars($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" name="edit_player" class="btn btn-primary" style="flex: 2;">GUARDAR
                        CAMBIOS</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="toggleModal('editPlayerModal')">CANCELAR</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            width: 90%;
            max-width: 400px;
            animation: fadeIn 0.3s ease-out;
        }
    </style>

    <script>
        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
        }

        function editPlayer(p) {
            document.getElementById('edit-player-id').value = p.id;
            document.getElementById('edit-player-name').value = p.name;
            document.getElementById('edit-player-team').value = p.team_id;
            toggleModal('editPlayerModal');
        }
    </script>
</body>

</html>