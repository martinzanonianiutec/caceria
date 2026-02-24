<?php
// public/dashboard.php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['player_id'])) { 
    header("Location: /caceria/"); 
    exit; 
}

$team_id = $_SESSION['team_id'];
$game_id = $_SESSION['game_id'];

$game = db_fetch("SELECT * FROM games WHERE id = ?", [$game_id]);
$team = db_fetch("SELECT * FROM teams WHERE id = ?", [$team_id]);

$challenges = db_fetch_all("SELECT * FROM challenges WHERE game_id = ? ORDER BY id ASC", [$game_id]);
$submissions = db_fetch_all("
    SELECT s.challenge_id, s.image_url, s.created_at, p.name as player_name 
    FROM submissions s 
    LEFT JOIN players p ON s.player_id = p.id 
    WHERE s.team_id = ?
", [$team_id]);

$rejections = db_fetch_all("SELECT challenge_id, reason FROM rejections WHERE team_id = ?", [$team_id]);
$rejection_map = [];
foreach ($rejections as $r) {
    $rejection_map[$r['challenge_id']] = $r['reason'];
}

$completed_map = [];
foreach ($submissions as $s) {
    $completed_map[$s['challenge_id']] = $s;
}

// Deterministic shuffling based on team_id
srand($team_id);
shuffle($challenges);
srand(); // Reset seed to default

// Identify the active window (max 2 visible incomplete challenges)
$active_challenges = [];
$visible_count = 0;
foreach ($challenges as $index => &$ch) {
    $is_done = isset($completed_map[$ch['id']]);
    
    if (!$is_done && $visible_count < 2) {
        $ch['is_blocked'] = false;
        $visible_count++;
    } elseif ($is_done) {
        $ch['is_blocked'] = false;
    } else {
        $ch['is_blocked'] = true;
    }
}

$total_challenges = count($challenges);
$completed_count = count($submissions);
$percent = $total_challenges > 0 ? round(($completed_count / $total_challenges) * 100) : 0;

// Fetch team members
$members = db_fetch_all("SELECT id, name FROM players WHERE team_id = ? ORDER BY id ASC", [$team_id]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Misión Cacería UTEC - <?= htmlspecialchars($team['name']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header style="margin-bottom: 2.5rem; text-align: center;">
            <div style="margin-bottom: 1.5rem;">
                <img src="assets/img/Isologotipo.png" style="max-width: 120px; opacity: 0.8;">
            </div>
            
            <div style="text-align: center; margin-bottom: 0.5rem;">
                <span style="color: <?= htmlspecialchars($team['color']) ?>; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.2em; opacity: 0.8;">
                    — EQUIPO ACTIVO —
                </span>
            </div>
            
            <div style="display: inline-block; padding: 0.75rem 2rem; background: <?= htmlspecialchars($team['color']) ?>15; border: 2px solid <?= htmlspecialchars($team['color']) ?>40; border-radius: 2rem; margin-bottom: 0.5rem; backdrop-filter: blur(5px); box-shadow: 0 10px 25px -5px <?= htmlspecialchars($team['color']) ?>30;">
                <h1 style="font-size: 2.5rem; margin: 0; color: <?= htmlspecialchars($team['color']) ?>; text-transform: uppercase; letter-spacing: 0.05em; text-shadow: 0 0 20px <?= htmlspecialchars($team['color']) ?>40;">
                    <?= htmlspecialchars($team['name']) ?>
                </h1>
            </div>
            
            <p style="color: var(--text-muted); font-weight: 600; margin-bottom: 1.5rem;"><?= htmlspecialchars($game['name']) ?></p>

            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 0.5rem; margin-bottom: 2rem;">
                <?php foreach ($members as $mem): ?>
                    <?php $is_me = ($mem['id'] == $_SESSION['player_id']); ?>
                    <span style="padding: 0.4rem 0.8rem; border-radius: 0.75rem; font-size: 0.8rem; font-weight: 700; border: 1px solid <?= $is_me ? 'var(--primary)' : 'var(--border)' ?>; background: <?= $is_me ? 'rgba(99, 102, 241, 0.2)' : 'rgba(255,255,255,0.05)' ?>; color: <?= $is_me ? 'var(--primary)' : 'var(--text-muted)' ?>;">
                        <?= $is_me ? '👤 ' : '' ?><?= htmlspecialchars($mem['name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
            
            <div class="card" style="margin-top: 1rem; background: rgba(30, 41, 59, 0.7); border-color: var(--border); position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--primary);"></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-weight: 800; font-size: 1.1rem;">
                    <span>PROGRESO DE LA MISIÓN</span>
                    <span style="color: var(--accent);"><?= $completed_count ?> / <?= $total_challenges ?></span>
                </div>
                <div class="progress-container" style="height: 16px; background: rgba(15, 23, 42, 0.6);">
                    <div class="progress-bar" style="width: <?= $percent ?>%; box-shadow: 0 0 15px var(--primary-glow);"></div>
                </div>
                <p style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-muted); font-weight: 600;">
                    <?= $percent == 100 ? "¡MISIÓN COMPLETADA EXITOSAMENTE! 🏆" : "Complete todos los objetivos para asegurar la victoria." ?>
                </p>
            </div>
        </header>

        <h2 style="font-size: 1.25rem; margin-bottom: 4.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <span style="display: block; width: 30px; height: 2px; background: var(--primary);"></span>
            OBJETIVOS DEL RETO
        </h2>
        
        <div class="grid-cards" style="margin-bottom: 6rem;">
            <?php foreach ($challenges as $idx => $ch): 
                $sub = isset($completed_map[$ch['id']]) ? $completed_map[$ch['id']] : null;
                $is_done = ($sub !== null);
                $rejection_reason = isset($rejection_map[$ch['id']]) ? $rejection_map[$ch['id']] : null;
            ?>
                <div class="card quest-card" style="border-color: <?= $ch['is_blocked'] ? 'var(--border)' : ($is_done ? 'var(--accent)' : ($rejection_reason ? '#f43f5e' : 'var(--border)')) ?>; opacity: <?= $ch['is_blocked'] ? '0.5' : ($is_done ? '0.8' : '1') ?>; position: relative;">
                    <?php if ($ch['is_blocked']): ?>
                        <div style="position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 10; display: flex; align-items: center; justify-content: center; border-radius: var(--radius);">
                            <div style="text-align: center;">
                                <span style="font-size: 2rem; display: block; margin-bottom: 0.5rem; opacity: 0.5;">🔒</span>
                                <span style="font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.8rem;">Reto bloqueado</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 1.25rem; filter: <?= $ch['is_blocked'] ? 'blur(2px)' : 'none' ?>;">
                        <div style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 12px; background: <?= $is_done ? 'rgba(6, 182, 212, 0.1)' : ($rejection_reason ? 'rgba(244, 63, 94, 0.1)' : 'rgba(99, 102, 241, 0.1)') ?>; border: 1px solid <?= $is_done ? 'var(--accent)' : ($rejection_reason ? '#f43f5e' : 'var(--primary)') ?>; display: flex; align-items: center; justify-content: center; font-weight: 800; color: <?= $is_done ? 'var(--accent)' : ($rejection_reason ? '#f43f5e' : 'var(--primary)') ?>;">
                            <?= $is_done ? '✓' : ($idx + 1) ?>
                        </div>
                        <div style="flex-grow: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
                                <h3 style="margin: 0; font-size: 1.15rem; color: <?= $is_done ? 'var(--accent)' : 'var(--text)' ?>; text-decoration: <?= $is_done ? 'line-through' : 'none' ?>;">
                                    <?= $ch['is_blocked'] ? 'RETO OCULTO' : htmlspecialchars($ch['title']) ?>
                                </h3>
                                <?php if ($is_done && $game['status'] == 'open'): ?>
                                    <a href="upload.php?challenge_id=<?= $ch['id'] ?>" style="flex-shrink: 0; font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-decoration: none; border: 1px solid var(--border); padding: 2px 8px; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--primary)'; this.style.color='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'; this.style.color='var(--text-muted)'">
                                        REEMPLAZAR
                                    </a>
                                <?php endif; ?>
                            </div>
                            <p style="margin: 0.5rem 0 1.25rem 0; font-size: 0.95rem; color: var(--text-muted);">
                                <?= $ch['is_blocked'] ? 'Completa los retos anteriores para desbloquear este objetivo.' : htmlspecialchars($ch['description']) ?>
                            </p>
                            
                            <?php if ($is_done): ?>
                                <div style="display: flex; align-items: center; gap: 1rem; background: rgba(15, 23, 42, 0.4); padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--border);">
                                    <img src="<?= BASE_URL . $sub['image_url'] ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
                                    <div>
                                        <span style="display: block; font-size: 0.875rem; color: var(--accent); font-weight: 800;">COMPLETADO</span>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);">Por: <strong><?= htmlspecialchars($sub['player_name'] ?? 'Equipo') ?></strong></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php if ($rejection_reason): ?>
                                    <div style="margin-bottom: 1.25rem; background: rgba(244, 63, 94, 0.1); border: 1px solid #f43f5e; color: #f43f5e; padding: 0.75rem; border-radius: 0.75rem; font-size: 0.8rem; font-weight: 600;">
                                        <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.25rem;">
                                            <span style="font-size: 1.1rem;">⚠️</span>
                                            <span style="text-transform: uppercase; letter-spacing: 0.05em;">FOTO RECHAZADA</span>
                                        </div>
                                        <div style="color: #fff; font-weight: 400; opacity: 0.9;">Motivo: <?= htmlspecialchars($rejection_reason) ?></div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($game['status'] == 'open' && !$ch['is_blocked']): ?>
                                    <a href="upload.php?challenge_id=<?= $ch['id'] ?>" class="btn btn-primary" style="width: 100%;">
                                        <?= $rejection_reason ? 'INTENTAR DE NUEVO' : 'INICIAR CAPTURA' ?>
                                    </a>
                                <?php elseif ($game['status'] != 'open'): ?>
                                    <div style="text-align: center; color: var(--text-muted); font-size: 0.875rem; font-weight: bold; border: 1px dashed var(--border); padding: 0.75rem; border-radius: var(--radius);">
                                        MISIÓN BLOQUEADA
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
