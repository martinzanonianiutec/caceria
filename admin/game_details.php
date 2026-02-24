<?php
// admin/game_details.php
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

// Handle Add Challenge
if (isset($_POST['add_challenge'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $order_num = (int) $_POST['order_num'];
    db_query("INSERT INTO challenges (game_id, title, description, order_num) VALUES (?, ?, ?, ?)", [$game_id, $title, $description, $order_num]);
    header("Location: game_details.php?id=$game_id");
    exit;
}

// Handle Edit Challenge
if (isset($_POST['edit_challenge'])) {
    $challenge_id = (int) $_POST['challenge_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $order_num = (int) $_POST['order_num'];
    db_query("UPDATE challenges SET title = ?, description = ?, order_num = ? WHERE id = ?", [$title, $description, $order_num, $challenge_id]);
    header("Location: game_details.php?id=$game_id");
    exit;
}

// Handle Delete Challenge
if (isset($_POST['delete_challenge'])) {
    $challenge_id = (int) $_POST['challenge_id'];
    db_query("DELETE FROM challenges WHERE id = ?", [$challenge_id]);
    header("Location: game_details.php?id=$game_id");
    exit;
}

// Handle Update Status
if (isset($_POST['update_status'])) {
    $status = $_POST['status'];
    db_query("UPDATE games SET status = ? WHERE id = ?", [$status, $game_id]);
    header("Location: game_details.php?id=$game_id");
    exit;
}

function rebalance_game_teams($game_id, $new_limit)
{
    global $pdo;

    // 1. Update game limit
    db_query("UPDATE games SET players_per_team = ? WHERE id = ?", [$new_limit, $game_id]);

    // 2. Fetch all players in this game in join order
    $players = db_fetch_all("
        SELECT p.id 
        FROM players p 
        JOIN teams t ON p.team_id = t.id 
        WHERE t.game_id = ? 
        ORDER BY p.id ASC
    ", [$game_id]);

    if (empty($players))
        return; // Nothing to rebalance

    // 3. Setup teams
    $teams_needed = ceil(count($players) / $new_limit);
    $existing_teams = db_fetch_all("SELECT id FROM teams WHERE game_id = ? ORDER BY created_at ASC", [$game_id]);

    // Create more teams if needed
    while (count($existing_teams) < $teams_needed) {
        $count = count($existing_teams);
        $available_colors = db_fetch_all("SELECT name, hex FROM team_colors ORDER BY id ASC");

        if (!empty($available_colors)) {
            $color_data = $available_colors[$count % count($available_colors)];
            $team_name = $color_data['name'];
            $color = $color_data['hex'];
        } else {
            $team_name = "Equipo " . ($count + 1);
            $fallback_colors = ['#6366f1', '#ec4899', '#06b6d4', '#f59e0b', '#8b5cf6', '#10b981', '#f43f5e', '#a855f7'];
            $color = $fallback_colors[$count % count($fallback_colors)];
        }

        $new_team_id = db_insert("INSERT INTO teams (game_id, name, color) VALUES (?, ?, ?)", [$game_id, $team_name, $color]);
        $existing_teams[] = ['id' => $new_team_id];
    }

    $final_teams = array_slice($existing_teams, 0, $teams_needed);

    // 4. Redistribute players
    foreach ($players as $idx => $p) {
        $team_idx = floor($idx / $new_limit);
        $target_team_id = $final_teams[$team_idx]['id'];
        db_query("UPDATE players SET team_id = ? WHERE id = ?", [$target_team_id, $p['id']]);
    }

    // 5. Cleanup: Delete empty teams with no submissions
    db_query("
        DELETE FROM teams 
        WHERE game_id = ? 
        AND id NOT IN (SELECT DISTINCT team_id FROM players)
        AND id NOT IN (SELECT DISTINCT team_id FROM submissions)
    ", [$game_id]);
}

// Handle Update Teams Limit and Rebalance
if (isset($_POST['update_teams_limit'])) {
    $new_limit = (int) $_POST['players_per_team'];
    if ($new_limit > 0) {
        rebalance_game_teams($game_id, $new_limit);
        header("Location: game_details.php?id=$game_id&msg=rebalanced");
        exit;
    }
}

$challenges = db_fetch_all("SELECT * FROM challenges WHERE game_id = ? ORDER BY order_num ASC", [$game_id]);
$teams = db_fetch_all("SELECT * FROM teams WHERE game_id = ?", [$game_id]);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Partida - Cacería Fotográfica</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <?php include 'includes/navbar.php'; ?>
        <header style="margin-bottom: 2rem;">
            <a href="dashboard.php" style="color: var(--primary); text-decoration: none; font-weight: 800;">&larr;
                VOLVER AL PANEL</a>
            <h1
                style="margin-top: 1rem; font-size: 2.5rem; background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                <?= htmlspecialchars($game['name']) ?>
            </h1>
            <p style="color: var(--text-muted); font-weight: 600;"><?= htmlspecialchars($game['description']) ?></p>
        </header>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'rebalanced'): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 1rem; border-radius: var(--radius); margin-bottom: 2rem; font-weight: 600; text-align: center;">
                    ✨ ¡EQUIPOS REEQUILIBRADOS CON ÉXITO! Todos los jugadores han sido redistribuidos.
                </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <section>
                <div class="card">
                    <h2 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🎯</span> RETOS DE LA MISIÓN
                    </h2>

                    <div style="display: grid; gap: 1rem; margin-bottom: 2.5rem;">
                        <?php foreach ($challenges as $ch): ?>
                            <div
                                style="padding: 1rem; border: 1px solid var(--border); border-radius: var(--radius); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                                <div>
                                    <span
                                        style="color: var(--primary); font-weight: 800; margin-right: 0.5rem;">#<?= $ch['order_num'] ?></span>
                                    <strong style="font-size: 1.1rem;"><?= htmlspecialchars($ch['title']) ?></strong>
                                    <p style="margin: 0.25rem 0 0 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
                                        <?= htmlspecialchars($ch['description']) ?>
                                    </p>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button class="btn btn-secondary" style="padding: 0.5rem 0.75rem; font-size: 0.75rem;"
                                        onclick="editChallenge(<?= htmlspecialchars(json_encode($ch)) ?>)">
                                        EDITAR
                                    </button>
                                    <form method="POST" style="display: inline;"
                                        onsubmit="return confirm('¿Eliminar este reto?')">
                                        <input type="hidden" name="challenge_id" value="<?= $ch['id'] ?>">
                                        <button type="submit" name="delete_challenge" class="btn"
                                            style="background: #f43f5e; color: white; padding: 0.5rem 0.75rem; font-size: 0.75rem;">
                                            BORRAR
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($challenges)): ?>
                            <p
                                style="text-align: center; color: var(--text-muted); padding: 2rem; border: 1px dashed var(--border); border-radius: var(--radius);">
                                No hay retos configurados aún.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div style="padding-top: 1.5rem; border-top: 1px solid var(--border);">
                        <h3 style="margin-bottom: 1rem; font-size: 1.25rem;">NUEVO RETO</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label>TÍTULO</label>
                                <input type="text" name="title" class="form-control"
                                    placeholder="Ej: Foto con el logo de la empresa" required>
                            </div>
                            <div class="form-group" style="display: flex; gap: 1rem;">
                                <div style="flex: 1;">
                                    <label>ORDEN</label>
                                    <input type="number" name="order_num" class="form-control"
                                        value="<?= count($challenges) + 1 ?>">
                                </div>
                                <div style="flex: 3;">
                                    <label>DESCRIPCIÓN</label>
                                    <input type="text" name="description" class="form-control"
                                        placeholder="Detalles sobre lo que deben capturar">
                                </div>
                            </div>
                            <button type="submit" name="add_challenge" class="btn btn-primary"
                                style="width: 100%;">AGREGAR RETO A LA MISIÓN</button>
                        </form>
                    </div>
                </div>
            </section>

            <aside>
                <div class="card" style="margin-bottom: 1.5rem;">
                    <h2 style="margin-bottom: 1.5rem; font-size: 1.25rem;">REGLAS DE EQUIPO</h2>
                    <form method="POST"
                        onsubmit="return confirm('¿Confirmar reequilibrio? Esto redistribuirá a los jugadores actuales en grupos de <?= $game['players_per_team'] ?>. Si la partida está en curso, los jugadores podrían cambiar de equipo y perder su progreso grupal.')">
                        <div class="form-group">
                            <label>PARTICIPANTES POR EQUIPO</label>
                            <input type="number" name="players_per_team" class="form-control"
                                value="<?= $game['players_per_team'] ?>" min="1" required>
                        </div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.3;">
                            💡 Cambiar esto redistribuirá automáticamente a todos los participantes actuales para llenar
                            los nuevos cupos.
                        </p>
                        <button type="submit" name="update_teams_limit" class="btn btn-secondary"
                            style="width: 100%; border: 1px solid var(--primary); background: rgba(99, 102, 241, 0.1);">
                            ACTUALIZAR Y REEQUILIBRAR
                        </button>
                    </form>
                </div>

                <div class="card" style="margin-bottom: 1.5rem;">
                    <h2 style="margin-bottom: 1rem; font-size: 1.25rem;">ESTADO</h2>
                    <form method="POST">
                        <select name="status" class="form-control" style="margin-bottom: 1rem; font-weight: 600;">
                            <option value="draft" <?= $game['status'] == 'draft' ? 'selected' : '' ?>>📁 BORRADOR</option>
                            <option value="open" <?= $game['status'] == 'open' ? 'selected' : '' ?>>🔓 ABIERTA</option>
                            <option value="closed" <?= $game['status'] == 'closed' ? 'selected' : '' ?>>🔒 CERRADA</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-secondary"
                            style="width: 100%;">ACTUALIZAR ESTADO</button>
                    </form>
                </div>

                <div class="card">
                    <h2 style="margin-bottom: 1rem; font-size: 1.25rem;">ACCESO</h2>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem; font-weight: 800;">
                        LINK DE INVITACIÓN:</p>
                    <?php $join_url = "http://" . $_SERVER['HTTP_HOST'] . "/caceria/join/" . $game['join_token']; ?>
                    <p
                        style="font-size: 0.75rem; word-break: break-all; color: var(--accent); background: rgba(0,0,0,0.2); padding: 0.75rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                        <?= $join_url ?>
                    </p>

                    <div
                        style="margin-top: 1.5rem; text-align: center; background: white; padding: 1rem; border-radius: 1rem;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($join_url) ?>"
                            alt="QR" style="display: block; margin: 0 auto;">
                    </div>
                </div>

                <div class="card" style="margin-top: 1.5rem;">
                    <h2 style="margin-bottom: 1rem; font-size: 1.25rem;">EQUIPOS</h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">Actualmente hay
                        <strong><?= count($teams) ?></strong> equipos registrados.
                    </p>
                    <a href="manage_players.php?id=<?= $game_id ?>" class="btn btn-primary"
                        style="width: 100%; height: auto; padding: 1rem; flex-direction: column; gap: 0.25rem;">
                        <span style="font-size: 1.25rem;">👥</span>
                        <span>GESTIONAR PARTICIPANTES</span>
                    </a>
                </div>
            </aside>
        </div>
    </div>

    <!-- Edit Challenge Modal -->
    <div id="editChallengeModal" class="modal">
        <div class="modal-content card">
            <h2 style="margin-bottom: 1.5rem;">Editar Reto</h2>
            <form method="POST">
                <input type="hidden" name="challenge_id" id="edit-challenge-id">
                <div class="form-group">
                    <label>Título</label>
                    <input type="text" name="title" id="edit-challenge-title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" id="edit-challenge-description" class="form-control"
                        rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Orden</label>
                    <input type="number" name="order_num" id="edit-challenge-order" class="form-control" required>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" name="edit_challenge" class="btn btn-primary" style="flex: 2;">GUARDAR
                        CAMBIOS</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="toggleModal('editChallengeModal')">CANCELAR</button>
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
            max-width: 500px;
            animation: fadeIn 0.3s ease-out;
        }
    </style>

    <script>
        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
        }

        function editChallenge(ch) {
            document.getElementById('edit-challenge-id').value = ch.id;
            document.getElementById('edit-challenge-title').value = ch.title;
            document.getElementById('edit-challenge-description').value = ch.description;
            document.getElementById('edit-challenge-order').value = ch.order_num;
            toggleModal('editChallengeModal');
        }
    </script>
</body>

</html>