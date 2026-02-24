<?php
// public/upload.php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['player_id'])) {
    header("Location: /caceria/");
    exit;
}

$challenge_id = isset($_GET['challenge_id']) ? (int) $_GET['challenge_id'] : 0;
$team_id = (int) ($_SESSION['team_id'] ?? 0);
$game_id = (int) ($_SESSION['game_id'] ?? 0);
$player_id = (int) ($_SESSION['player_id'] ?? 0);

if ($challenge_id <= 0) {
    die("challenge_id inválido.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];

    if ($file['error'] === UPLOAD_ERR_OK) {

        // Check for existing submission to replace
        $existing = db_fetch(
            "SELECT id, image_url FROM submissions WHERE team_id = ? AND challenge_id = ?",
            [$team_id, $challenge_id]
        );

        // Asegurar carpeta uploads en la raíz
        $uploadsDir = dirname(__DIR__) . '/uploads';
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0775, true);
        }

        // Extensión (por si viene vacía)
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'jpg';
        }

        $filename = uniqid('sub_') . '.' . $ext;
        $relativePath = 'uploads/' . $filename;      // lo que guardás en BD
        $targetPath = dirname(__DIR__) . '/' . $relativePath; // path real en disco

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {

            if ($existing) {
                // Delete old file
                if (!empty($existing['image_url'])) {
                    $oldPath = __DIR__ . '/' . ltrim($existing['image_url'], '/');
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                // Update existing record
                db_query(
                    "UPDATE submissions SET image_url = ?, player_id = ?, created_at = NOW() WHERE id = ?",
                    [$relativePath, $player_id, $existing['id']]
                );
            } else {
                // Insert new submission
                db_insert(
                    "INSERT INTO submissions (team_id, challenge_id, player_id, image_url) VALUES (?, ?, ?, ?)",
                    [$team_id, $challenge_id, $player_id, $relativePath]
                );
            }

            // Clear any rejection
            db_query(
                "DELETE FROM rejections WHERE team_id = ? AND challenge_id = ?",
                [$team_id, $challenge_id]
            );

            // Update completion status
            $total_challenges = (int) db_fetch(
                "SELECT COUNT(*) as total FROM challenges WHERE game_id = ?",
                [$game_id]
            )['total'];

            $completed_count = (int) db_fetch(
                "SELECT COUNT(*) as count FROM submissions WHERE team_id = ?",
                [$team_id]
            )['count'];

            if ($total_challenges > 0 && $completed_count >= $total_challenges) {
                db_query("UPDATE teams SET points = points + 1, finished_at = NOW() WHERE id = ?", [$team_id]);
            } else {
                db_query("UPDATE teams SET points = points + 1, finished_at = NULL WHERE id = ?", [$team_id]);
            }

            header("Location: /caceria/dashboard.php");
            exit;

        } else {
            echo "<div style='background:#f43f5e;color:#fff;padding:1rem;text-align:center;margin-bottom:1rem;'>
                    ERROR: No se pudo mover el archivo a la carpeta uploads.
                  </div>";
        }

    } else {
        // Handle upload errors
        $error_code = $file['error'];
        $error_message = "Error desconocido al subir el archivo.";

        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = "El archivo es demasiado grande (supera el límite del servidor).";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "El archivo es demasiado grande (supera el límite del formulario).";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = "El archivo se subió parcialmente.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = "No se seleccionó ningún archivo.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = "Falta la carpeta temporal.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = "No se pudo escribir el archivo en el disco.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = "Una extensión de PHP detuvo la subida.";
                break;
        }

        echo "<div style='background:#f43f5e;color:#fff;padding:1rem;text-align:center;margin-bottom:1rem;'>
                ERROR: $error_message (Código: $error_code)
              </div>";
    }
}

$challenge = db_fetch("SELECT * FROM challenges WHERE id = ?", [$challenge_id]);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capturar - Cacería UTEC</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container" style="max-width: 500px;">
        <header style="margin-bottom: 2.5rem;">
            <a href="/caceria/dashboard.php" class="btn btn-secondary"
                style="padding: 0.5rem 1rem; font-size: 0.75rem;">&larr; VOLVER AL REGISTRO</a>
            <div style="margin-top: 2rem;">
                <div style="margin-bottom: 1rem;">
                    <img src="assets/img/Isologotipo.png" style="max-width: 80px; opacity: 0.8;">
                </div>
                <span
                    style="color: var(--accent); font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em;">OBJETIVO
                    SELECCIONADO</span>
                <h1 style="font-size: 2rem; margin-top: 0.25rem; color: #fff;">
                    <?= htmlspecialchars($challenge['title'] ?? '') ?></h1>
                <p style="color: var(--text-muted); font-size: 1rem; font-weight: 600;">
                    <?= htmlspecialchars($challenge['description'] ?? '') ?></p>
            </div>
        </header>

        <div class="card" style="text-align: center; border-style: dashed; background: rgba(30, 41, 59, 0.4);">
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div id="previewContainer" style="margin-bottom: 2rem; display: none; animation: fadeIn 0.4s ease-out;">
                    <p
                        style="color: var(--accent); font-weight: 800; font-size: 0.75rem; margin-bottom: 1rem; text-transform: uppercase;">
                        IMAGEN CAPTURADA</p>
                    <img id="imagePreview"
                        style="max-width: 100%; border-radius: var(--radius); border: 2px solid var(--accent); box-shadow: 0 0 20px rgba(6, 182, 212, 0.3);">
                </div>

                <div class="form-group">
                    <label for="photo" class="btn btn-primary"
                        style="width: 100%; height: 160px; flex-direction: column; gap: 0.75rem; background: rgba(99, 102, 241, 0.05); border: 2px dashed var(--primary); box-shadow: none;">
                        <span style="font-size: 3rem;">📸</span>
                        <span id="labelTodo" style="font-weight: 800; font-size: 1rem;">ACTIVAR DISPOSITIVO</span>
                        <input type="file" name="photo" id="photo" style="display: none;" accept="image/*"
                            capture="environment" required>
                    </label>
                </div>

                <button type="submit" id="submitBtn" class="btn btn-primary"
                    style="width: 100%; display: none; margin-top: 1.5rem; background: var(--gradient); font-size: 1rem; padding: 1.25rem;">
                    SUBIR REGISTRO
                </button>
            </form>
        </div>
    </div>

    <script>
        const photoInput = document.getElementById('photo');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const labelTodo = document.getElementById('labelTodo');
        const submitBtn = document.getElementById('submitBtn');

        photoInput.addEventListener('change', function (e) {
            const file = this.files[0];
            if (file) {
                // Show loading state
                labelTodo.textContent = "COMPRIMIENDO...";
                submitBtn.style.display = 'none';

                const reader = new FileReader();
                reader.onload = function (event) {
                    const img = new Image();
                    img.onload = function () {
                        // Canvas for compression
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
                        const MAX_SIZE = 1280; // Max dimension

                        // Resize logic
                        if (width > height) {
                            if (width > MAX_SIZE) {
                                height *= MAX_SIZE / width;
                                width = MAX_SIZE;
                            }
                        } else {
                            if (height > MAX_SIZE) {
                                width *= MAX_SIZE / height;
                                height = MAX_SIZE;
                            }
                        }

                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        // Compress to JPEG 0.7 quality
                        canvas.toBlob(function (blob) {
                            if (blob) {
                                // Create new file from blob
                                const compressedFile = new File([blob], file.name, {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                });

                                // Replace input file with compressed one
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(compressedFile);
                                photoInput.files = dataTransfer.files;

                                // Update preview
                                imagePreview.src = URL.createObjectURL(compressedFile);
                                previewContainer.style.display = 'block';
                                labelTodo.textContent = "CAMBIAR CAPTURA (OPTIMIZADA)";
                                submitBtn.style.display = 'inline-block';
                                submitBtn.textContent = `SUBIR REGISTRO (~${(blob.size / 1024).toFixed(0)}KB)`;

                                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
                            } else {
                                alert("Error al comprimir la imagen.");
                                labelTodo.textContent = "INTENTAR DE NUEVO";
                            }
                        }, 'image/jpeg', 0.7);
                    }
                    img.onerror = function () {
                        alert("La imagen seleccionada parece estar dañada.");
                        labelTodo.textContent = "INTENTAR DE NUEVO";
                    };
                    img.src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>