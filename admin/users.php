<?php
// admin/users.php
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
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username && $email && $password) {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                db_insert("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)", [$username, $email, $hashed_password]);
                $success_msg = "Usuario creado exitosamente.";
            } catch (Exception $e) {
                $error_msg = "Error al crear usuario: " . $e->getMessage();
            }
        } else {
            $error_msg = "Todos los campos son obligatorios.";
        }
    } elseif ($action == 'update') {
        $id = (int)$_POST['id'];
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username && $email) {
            try {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    db_query("UPDATE admins SET username = ?, email = ?, password = ? WHERE id = ?", [$username, $email, $hashed_password, $id]);
                } else {
                    db_query("UPDATE admins SET username = ?, email = ? WHERE id = ?", [$username, $email, $id]);
                }
                $success_msg = "Usuario actualizado exitosamente.";
            } catch (Exception $e) {
                $error_msg = "Error al actualizar usuario: " . $e->getMessage();
            }
        }
    } elseif ($action == 'delete') {
        $id = (int)$_POST['id'];
        try {
            db_query("DELETE FROM admins WHERE id = ?", [$id]);
            $success_msg = "Usuario eliminado exitosamente.";
        } catch (Exception $e) {
            $error_msg = "Error al eliminar usuario.";
        }
    }
}

// Fetch all admins
$admins = db_fetch_all("SELECT id, username, email, created_at FROM admins ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php include 'includes/navbar.php'; ?>

        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 2rem; background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">USUARIOS</h1>
                <p style="color: var(--text-muted); font-weight: 600;">Gestión de Administradores</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('createModal')">+ NUEVO ADMIN</button>
        </header>

        <?php if ($success_msg): ?>
            <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem;">
                <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div style="background: rgba(244, 63, 94, 0.2); border: 1px solid #f43f5e; color: #f43f5e; padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem;">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <div class="card" style="padding: 0; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: rgba(255,255,255,0.05);">
                    <tr>
                        <th style="padding: 1rem; border-bottom: 1px solid var(--border);">Usuario</th>
                        <th style="padding: 1rem; border-bottom: 1px solid var(--border);">Email</th>
                        <th style="padding: 1rem; border-bottom: 1px solid var(--border);">Creado</th>
                        <th style="padding: 1rem; border-bottom: 1px solid var(--border); text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--border); font-weight: 600;"><?= htmlspecialchars($admin['username']) ?></td>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--border); color: var(--text-muted);"><?= htmlspecialchars($admin['email']) ?></td>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.8rem;"><?= $admin['created_at'] ?></td>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--border); text-align: right;">
                                <button class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;" onclick="editUser(<?= htmlspecialchars(json_encode($admin)) ?>)">Editar</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este usuario?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                                    <button type="submit" class="btn" style="background: #f43f5e; color: white; padding: 0.4rem 0.8rem; font-size:0.75rem;">Borrar</button>
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
            <h2>Crear Administrador</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Crear</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="toggleModal('createModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content card">
            <h2>Editar Administrador</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit-id">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="username" id="edit-username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit-email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nueva Contraseña (dejar en blanco para mantener)</label>
                    <input type="password" name="password" class="form-control">
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Guardar Cambios</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="toggleModal('editModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
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

        function editUser(admin) {
            document.getElementById('edit-id').value = admin.id;
            document.getElementById('edit-username').value = admin.username;
            document.getElementById('edit-email').value = admin.email;
            toggleModal('editModal');
        }
    </script>
</body>
</html>
