<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cacería Fotográfica - ¡Captura la Aventura!</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        .hero {
            text-align: center;
            padding: 6rem 1rem;
            background: radial-gradient(circle at center, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
        }

        .hero h1 {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.1;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 900px;
            margin: 0 auto 2.5rem;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
        }

        .feature-card {
            text-align: center;
            padding: 2.5rem;
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            display: block;
        }
    </style>
</head>

<body>
    <div class="container">
        <header style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 0;">
            <div style="font-weight: 800; font-size: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>📸</span> CACERÍA
            </div>
            <a href="admin/login.php" class="btn btn-secondary" style="font-size: 0.75rem;">ACCESO ADMIN</a>
        </header>

        <section class="hero">
            <h1>CAPTURA CADA INSTANTE</h1>
            <p>Únete a la misiones fotográficas más emocionantes. Compite en equipo, supera desafíos y demuestra que
                tienes el mejor ojo para la imagen.</p>
            <div style="display: flex; justify-content: center; gap: 1.5rem;">
                <a href="join.php" class="btn btn-primary" style="padding: 1rem 2.5rem; font-size: 1rem;">UNIRSE A UNA
                    MISIÓN</a>
            </div>
        </section>

        <div class="features">
            <div class="card feature-card">
                <span class="feature-icon">🎯</span>
                <h3>Objetivos Claros</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 1rem;">Cumple misiones específicas
                    capturando imágenes precisas de tus objetivos.</p>
            </div>
            <div class="card feature-card">
                <span class="feature-icon">⚡</span>
                <h3>Tiempo Real</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 1rem;">Sigue el progreso de tu equipo
                    y la competencia al instante.</p>
            </div>
            <div class="card feature-card">
                <span class="feature-icon">🏆</span>
                <h3>Sé el Campeón</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 1rem;">Acumula puntos y llega a lo
                    más alto del ranking fotográfico.</p>
            </div>
        </div>

        <footer
            style="margin-top: 8rem; padding-bottom: 3rem; text-align: center; color: var(--text-muted); font-size: 0.8rem; letter-spacing: 0.1em;">
            &copy; <?= date('Y') ?> CACERÍA FOTOGRÁFICA. TODOS LOS DERECHOS RESERVADOS.
        </footer>
    </div>
</body>

</html>