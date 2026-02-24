<?php
// admin/colors.php
session_start();
require_once '../includes/db.php';

// Authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$success_msg = '';
$error_msg = '';

// Handle CRUD Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'create') {
        $name = trim($_POST['name'] ?? '');
        $hex = trim($_POST['hex'] ?? '');

        if ($name && $hex) {
            try {
                db_insert("INSERT INTO team_colors (name, hex) VALUES (?, ?)", [$name, $hex]);
                $success_msg = "Color creado exitosamente.";
            } catch (Exception $e) {
                $error_msg = "Error al crear color: " . $e->getMessage();
            }
        } else {
            $error_msg = "Todos los campos son obligatorios.";
        }
    } elseif ($action == 'delete') {
        $id = (int) $_POST['id'];
        try {
            db_query("DELETE FROM team_colors WHERE id = ?", [$id]);
            $success_msg = "Color eliminado exitosamente.";
        } catch (Exception $e) {
            $error_msg = "Error al eliminar color.";
        }
    }
}

// Fetch all colors
$colors = db_fetch_all("SELECT * FROM team_colors ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Colores - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <?php include 'includes/navbar.php'; ?>

        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1
                    style="font-size: 2rem; background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    COLORES DE EQUIPO</h1>
                <p style="color: var(--text-muted); font-weight: 600;">Gestión de Nombres y Colores para Equipos</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('createModal')">+ NUEVO COLOR</button>
        </header>

        <?php if ($success_msg): ?>
            <div
                style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem;">
                <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div
                style="background: rgba(244, 63, 94, 0.2); border: 1px solid #f43f5e; color: #f43f5e; padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem;">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <div class="card" style="padding: 0; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: rgba(255,255,255,0.05);">
                    <tr>
                        <th style="padding: 1rem; border-bottom: 1px solid var(--border);">Nombre (Nombre del Equipo)
                        </th>
                        <th style="padding: 1rem; border-bottom: 1px solid var(--border);">Color</th>
                        <th style="padding: 1rem; border-bottom: 1px solid var(--border); text-align: right;">Acciones
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($colors as $color): ?>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--border); font-weight: 600;">
                                <?= htmlspecialchars($color['name']) ?></td>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--border);">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div
                                        style="width: 20px; height: 20px; border-radius: 4px; background-color: <?= htmlspecialchars($color['hex']) ?>; border: 1px solid var(--border);">
                                    </div>
                                    <span><?= htmlspecialchars($color['hex']) ?></span>
                                </div>
                            </td>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--border); text-align: right;">
                                <form method="POST" style="display: inline;"
                                    onsubmit="return confirm('¿Eliminar este color?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $color['id'] ?>">
                                    <button type="submit" class="btn"
                                        style="background: #f43f5e; color: white; padding: 0.4rem 0.8rem; font-size:0.75rem;">Borrar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content card">
            <h2>Nuevo Color de Equipo</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>Nombre del Equipo (Eje: Rojo, Cobalto, etc.)</label>
                    <input type="text" name="name" class="form-control" required
                        placeholder="Nombre que verá el equipo">
                </div>
                <div class="form-group">
                    <label>Color (Hexadecimal)</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="color" id="colorPicker"
                            style="height: 45px; width: 60px; border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer;"
                            value="#3b82f6" oninput="document.getElementById('hexInput').value = this.value">
                        <input type="text" name="hex" id="hexInput" class="form-control" value="#3b82f6" required
                            maxlength="7" oninput="document.getElementById('colorPicker').value = this.value">
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Crear</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="toggleModal('createModal')">Cancelar</button>
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
    </script>
</body>

</html>