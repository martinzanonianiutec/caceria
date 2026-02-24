<?php
// admin/dashboard.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$games = db_fetch_all("SELECT * FROM games ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Cacería</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <?php include 'includes/navbar.php'; ?>
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
            <div>
                <h1
                    style="font-size: 2rem; background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    PANEL DE CONTROL</h1>
                <p style="color: var(--text-muted); font-weight: 600;">Administración de Partidas</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <a href="create_game.php" class="btn btn-primary">+ NUEVA MISIÓN</a>
                <a href="logout.php"
                    style="color: var(--text-muted); text-decoration: none; font-size: 0.8rem; font-weight: 800;">CERRAR
                    SESIÓN</a>
            </div>
        </header>

        <div style="display: grid; gap: 1.25rem;">
            <?php foreach ($games as $game): ?>
                <div class="card" style="border-left: 4px solid var(--primary); padding: 1.5rem;">
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($game['name']) ?></h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600;">
                            CÓDIGO: <span
                                style="color: var(--accent); letter-spacing: 0.05em;"><?= $game['join_token'] ?></span> |
                            ESTADO: <span class="badge badge-<?= $game['status'] ?>"
                                style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--border); padding: 0.2rem 0.6rem;"><?= strtoupper($game['status']) ?></span>
                        </p>
                    </div>

                    <div
                        style="display: flex; flex-wrap: wrap; gap: 0.75rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                        <a href="game_details.php?id=<?= $game['id'] ?>" class="btn btn-secondary"
                            style="padding: 0.6rem 1.25rem; font-size: 0.75rem;">GESTIONAR</a>

                        <a href="collage.php?id=<?= $game['id'] ?>" class="btn"
                            style="background: #a855f7; color: white; padding: 0.6rem 1.25rem; font-size: 0.75rem; font-weight: 800;">COLLAGE</a>

                        <a href="monitor.php?id=<?= $game['id'] ?>" class="btn btn-primary"
                            style="padding: 0.6rem 1.25rem; background: #10b981; font-size: 0.75rem; box-shadow: none;">TABLA
                            EN VIVO</a>

                        <a href="/caceria/results.php?id=<?= $game['id'] ?>" class="btn"
                            style="background: var(--accent); color: white; padding: 0.6rem 1.25rem; font-size: 0.75rem; font-weight: 800;">RESULTADOS
                            ↗</a>

                        <form method="GET" action="duplicate_game.php" style="display: inline;">
                            <input type="hidden" name="id" value="<?= $game['id'] ?>">
                            <button type="submit" class="btn"
                                style="background: #f59e0b; color: #0f172a; padding: 0.6rem 1.25rem; font-size: 0.75rem; font-weight: 800;">DUPLICAR</button>
                        </form>

                        <a href="delete_game.php?id=<?= $game['id'] ?>" class="btn"
                            style="background: #f43f5e; color: white; padding: 0.6rem 1.25rem; font-size: 0.75rem;"
                            onclick="return confirm('¿Está seguro de que desea eliminar esta misión?')">ELIMINAR</a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($games)): ?>
                <div class="card"
                    style="text-align: center; padding: 4rem; color: var(--text-muted); border-style: dashed;">
                    <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;">📭</div>
                    <p style="font-weight: 600;">No hay misiones creadas. ¡Comience creando la primera!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .badge-open {
            color: #4ade80;
            border-color: #4ade80;
        }

        .badge-closed {
            color: #f87171;
            border-color: #f87171;
        }

        .badge-draft {
            color: #94a3b8;
            border-color: #94a3b8;
        }
    </style>
</body>

</html>