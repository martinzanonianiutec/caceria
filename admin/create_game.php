<?php
// admin/create_game.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $players_per_team = (int)$_POST['players_per_team'];
    $join_token = bin2hex(random_bytes(4)); // Random 8-char token

    $game_id = db_insert("INSERT INTO games (name, description, join_token, players_per_team, status) VALUES (?, ?, ?, ?, 'draft')", [$name, $description, $join_token, $players_per_team]);

    header('Location: game_details.php?id=' . $game_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Partida - Cacería Fotográfica</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/navbar.php'; ?>

        <header style="margin-bottom: 2rem;">
            <a href="dashboard.php" style="color: var(--primary); text-decoration: none;">&larr; Volver</a>
            <h1 style="margin-top: 1rem;">Nueva Partida</h1>
        </header>

        <div class="card">
            <form method="POST">
                <div class="form-group">
                    <label>Nombre de la Partida</label>
                    <input type="text" name="name" class="form-control" placeholder="Ej: Búsqueda del Tesoro Bariloche" required>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Integrantes por Equipo</label>
                    <input type="number" name="players_per_team" class="form-control" value="4" min="1" max="50" required>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Los equipos se crearán automáticamente a medida que se unan jugadores.</p>
                </div>
                <button type="submit" class="btn btn-primary">Crear y Continuar</button>
            </form>
        </div>
    </div>
</body>
</html>
