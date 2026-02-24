<?php
// admin/duplicate_game.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$id = (int) $_GET['id'];
$original_game = db_fetch("SELECT * FROM games WHERE id = ?", [$id]);

if (!$original_game) {
    die("Partida original no encontrada.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_name'])) {
    $new_name = trim($_POST['new_name']);
    $description = $original_game['description'];
    $players_per_team = (int) $original_game['players_per_team'];
    $join_token = bin2hex(random_bytes(4)); // Random 8-char token

    // 1. Insert new game
    $new_game_id = db_insert("INSERT INTO games (name, description, join_token, players_per_team, status) VALUES (?, ?, ?, ?, 'draft')", [$new_name, $description, $join_token, $players_per_team]);

    if ($new_game_id) {
        // 2. Fetch original challenges
        $original_challenges = db_fetch_all("SELECT * FROM challenges WHERE game_id = ? ORDER BY order_num ASC", [$id]);

        // 3. Clone challenges
        foreach ($original_challenges as $ch) {
            db_insert("INSERT INTO challenges (game_id, title, description, order_num) VALUES (?, ?, ?, ?)", [
                $new_game_id,
                $ch['title'],
                $ch['description'],
                $ch['order_num']
            ]);
        }

        header('Location: dashboard.php?msg=duplicated');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duplicar Misión - Cacería</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <?php include 'includes/navbar.php'; ?>

        <header style="margin-bottom: 2rem;">
            <a href="dashboard.php" style="color: var(--primary); text-decoration: none; font-weight: 800;">&larr;
                CANCELAR</a>
            <h1
                style="margin-top: 1rem; font-size: 2.5rem; background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                DUPLICAR MISIÓN</h1>
            <p style="color: var(--text-muted); font-weight: 600;">Se copiarán todos los retos pero no los
                participantes.</p>
        </header>

        <div class="card">
            <h2 style="margin-bottom: 1.5rem; font-size: 1.25rem;">DETALLES DE LA COPIA</h2>
            <form method="POST">
                <div class="form-group">
                    <label>NOMBRE PARA LA NUEVA MISIÓN</label>
                    <input type="text" name="new_name" class="form-control"
                        value="<?= htmlspecialchars($original_game['name']) ?> (COPIA)" required autofocus>
                </div>
                <div
                    style="background: rgba(15, 23, 42, 0.4); padding: 1rem; border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 2rem;">
                    <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">
                        🔍 <strong>Original:</strong>
                        <?= htmlspecialchars($original_game['name']) ?><br>
                        🎯 <strong>Retos a copiar:</strong>
                        <?= db_fetch("SELECT COUNT(*) as count FROM challenges WHERE game_id = ?", [$id])['count'] ?>
                    </p>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 55px; font-size: 1rem;">
                    REALIZAR DUPLICACIÓN
                </button>
            </form>
        </div>
    </div>
</body>

</html>