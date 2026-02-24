<?php
// admin/monitor.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$game_id = (int) $_GET['id'];
$game = db_fetch("SELECT * FROM games WHERE id = ?", [$game_id]);
if (!$game)
    die("Partida no encontrada.");

$teams = db_fetch_all("
    SELECT t.*, 
    (SELECT COUNT(*) FROM submissions s WHERE s.team_id = t.id) as completed_count,
    (SELECT MAX(created_at) FROM submissions s WHERE s.team_id = t.id) as last_submission
    FROM teams t 
    WHERE t.game_id = ? 
    ORDER BY completed_count DESC, finished_at ASC, last_submission ASC
", [$game_id]);

$total_challenges = db_fetch("SELECT COUNT(*) as total FROM challenges WHERE game_id = ?", [$game_id])['total'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard UTEC - <?= htmlspecialchars($game['name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <!-- <meta http-equiv="refresh" content="10"> Removed in favor of JS control -->
</head>

<body style="background: #0f172a;">
    <div class="container">
        <?php include 'includes/navbar.php'; ?>
        <header style="margin-bottom: 3rem; text-align: center;">
            <h1
                style="font-size: 3rem; margin-bottom: 0.5rem; background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                TABLA DE LÍDERES</h1>
            <p style="color: var(--text-muted); font-size: 1.25rem; font-weight: 600;">
                <?= htmlspecialchars($game['name']) ?>
            </p>
            <div style="margin-top: 1rem;">
                <span class="badge badge-<?= $game['status'] ?>"
                    style="font-size: 0.875rem; border: 1px solid var(--border); background: rgba(30, 41, 59, 0.5);"><?= strtoupper($game['status']) ?></span>
            </div>
        </header>

        <div style="display: grid; gap: 1.5rem; margin-bottom: 5rem;">
            <?php foreach ($teams as $idx => $team):
                $percent = $total_challenges > 0 ? round(($team['completed_count'] / $total_challenges) * 100) : 0;
                $is_completed = ($team['completed_count'] >= $total_challenges && $total_challenges > 0);

                $members = db_fetch_all("SELECT name FROM players WHERE team_id = ? ORDER BY id ASC", [$team['id']]);
                $members_list = array_column($members, 'name');

                $rank_class = '';
                $medal = '';
                if ($idx === 0) {
                    $rank_class = 'rank-gold';
                    $medal = '🥇';
                } elseif ($idx === 1) {
                    $rank_class = 'rank-silver';
                    $medal = '🥈';
                } elseif ($idx === 2) {
                    $rank_class = 'rank-bronze';
                    $medal = '🥉';
                } else {
                    $medal = '#' . ($idx + 1);
                }

                // Styles for completed status
                $card_style = "border-left: 6px solid " . ($is_completed ? '#4ade80' : $team['color']) . ";";
                $card_style .= "background: " . ($is_completed ? 'rgba(74, 222, 128, 0.1)' : 'rgba(30, 41, 59, 0.6)') . ";";
                $card_style .= "border: 1px solid " . ($is_completed ? '#4ade80' : 'transparent') . ";";
                ?>
                <div class="card"
                    style="<?= $card_style ?> position: relative; overflow: hidden; animation: fadeIn 0.5s ease-out <?= $idx * 0.1 ?>s both;">
                    <?php if ($is_completed): ?>
                        <div
                            style="position: absolute; top: 0; right: 0; background: #4ade80; color: #0f172a; font-weight: 800; padding: 0.25rem 1rem; border-bottom-left-radius: 0.75rem;">
                            ¡COMPLETADO! VALIDAR 🔍
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 1.5rem;">
                            <div style="font-size: 2rem; width: 60px; height: 60px; background: rgba(15, 23, 42, 0.5); border-radius: 16px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border);"
                                class="<?= $rank_class ?>">
                                <?= $medal ?>
                            </div>
                            <div>
                                <div
                                    style="display: inline-block; padding: 0.4rem 1.25rem; background: <?= htmlspecialchars($team['color']) ?>15; border: 1px solid <?= htmlspecialchars($team['color']) ?>40; border-radius: 2rem; margin-bottom: 0.4rem; backdrop-filter: blur(5px);">
                                    <h2
                                        style="font-size: 1.5rem; margin: 0; color: <?= htmlspecialchars($team['color']) ?>; text-transform: uppercase; letter-spacing: 0.02em; text-shadow: 0 0 15px <?= htmlspecialchars($team['color']) ?>30;">
                                        <?= htmlspecialchars($team['name']) ?>
                                    </h2>
                                </div>
                                <p
                                    style="font-size: 0.95rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.4rem;">
                                    <span style="font-size: 1rem;">👥</span>
                                    <?= empty($members_list) ? 'Sin integrantes registrados' : htmlspecialchars(implode(', ', $members_list)) ?>
                                </p>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div
                                style="font-size: 2rem; font-weight: 800; color: <?= $is_completed ? '#4ade80' : 'var(--accent)' ?>;">
                                <?= $team['completed_count'] ?> <span style="font-size: 1rem; color: var(--text-muted);">/
                                    <?= $total_challenges ?></span>
                            </div>
                            <span style="font-weight: 800; color: var(--primary); font-size: 0.875rem;"><?= $percent ?>%
                                COMPLETADO</span>
                        </div>
                    </div>

                    <div class="progress-container" style="height: 10px; background: rgba(15, 23, 42, 0.8);">
                        <div class="progress-bar"
                            style="width: <?= $percent ?>%; background: <?= $is_completed ? '#4ade80' : 'var(--accent)' ?>; box-shadow: 0 0 10px <?= $is_completed ? 'rgba(74, 222, 128, 0.5)' : 'var(--primary-glow)' ?>;">
                        </div>
                    </div>

                    <details id="details-team-<?= $team['id'] ?>" class="team-details" data-team-id="<?= $team['id'] ?>"
                        style="margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                        <summary
                            style="cursor: pointer; font-size: 0.875rem; color: var(--text-muted); font-weight: 700; outline: none; transition: color 0.2s;">
                            VER REGISTROS CAPTURADOS (<?= $team['completed_count'] ?>)
                        </summary>
                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
                            <?php
                            $game_challenges = db_fetch_all("SELECT * FROM challenges WHERE game_id = ? ORDER BY order_num ASC", [$game_id]);
                            $team_subs = db_fetch_all("
                                SELECT s.challenge_id, s.image_url, s.is_collage, p.name as player_name 
                                FROM submissions s 
                                LEFT JOIN players p ON s.player_id = p.id 
                                WHERE s.team_id = ?
                            ", [$team['id']]);
                            $subs_map = [];
                            foreach ($team_subs as $s) {
                                $subs_map[$s['challenge_id']] = [
                                    'url' => $s['image_url'],
                                    'player' => $s['player_name'],
                                    'is_collage' => (bool) $s['is_collage']
                                ];
                            }

                            foreach ($game_challenges as $gc):
                                $sub_data = isset($subs_map[$gc['id']]) ? $subs_map[$gc['id']] : null;
                                $img = $sub_data ? $sub_data['url'] : null;
                                $player = $sub_data ? $sub_data['player'] : null;
                                ?>
                                <div
                                    style="border: 1px solid <?= $img ? ($is_completed ? '#4ade80' : 'var(--accent)') : 'var(--border)' ?>; border-radius: 0.75rem; background: rgba(15, 23, 42, 0.4); overflow: hidden; position: relative; display: flex; flex-direction: column;">
                                    <?php if ($img): ?>
                                        <a href="<?= BASE_URL . $img ?>" target="_blank">
                                            <img src="<?= BASE_URL . $img ?>"
                                                style="width: 100%; aspect-ratio: 1/1; object-fit: cover;">
                                        </a>
                                        <div id="action-<?= $team['id'] ?>-<?= $gc['id'] ?>"
                                            style="background: rgba(15, 23, 42, 0.8); color: var(--text); font-size: 0.7rem; padding: 0.5rem; text-align: center; font-weight: 600; flex-grow: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.3rem;">
                                            <span style="font-weight: 800; color: var(--accent);">👤
                                                <?= htmlspecialchars($player ?? 'Equipo') ?></span>
                                            <div style="opacity: 0.7; font-size: 0.65rem;"><?= htmlspecialchars($gc['title']) ?>
                                            </div>

                                            <div
                                                style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 5px; padding: 4px; background: rgba(0,0,0,0.3); border-radius: 4px;">
                                                <input type="checkbox" id="collage-<?= $team['id'] ?>-<?= $gc['id'] ?>"
                                                    <?= $sub_data['is_collage'] ? 'checked' : '' ?>
                                                    onchange="toggleCollage(<?= $team['id'] ?>, <?= $gc['id'] ?>, this.checked)"
                                                    style="cursor: pointer;">
                                                <label for="collage-<?= $team['id'] ?>-<?= $gc['id'] ?>"
                                                    style="font-size: 0.6rem; cursor: pointer; color: var(--accent);">COLLAGE</label>
                                            </div>

                                            <button onclick="showRejectForm(<?= $team['id'] ?>, <?= $gc['id'] ?>)"
                                                style="width: 100%; border: 1px solid #f43f5e; background: rgba(244, 63, 94, 0.1); color: #f43f5e; font-size: 0.65rem; font-weight: 800; padding: 4px; border-radius: 4px; cursor: pointer; margin-top: 5px;">
                                                RECHAZAR
                                            </button>
                                        </div>

                                        <!-- Rejection Form -->
                                        <div id="form-<?= $team['id'] ?>-<?= $gc['id'] ?>"
                                            style="display: none; background: #1e293b; padding: 0.5rem; border-radius: 0 0 0.75rem 0.75rem;">
                                            <form action="reject_submission.php" method="POST">
                                                <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                                                <input type="hidden" name="challenge_id" value="<?= $gc['id'] ?>">
                                                <input type="hidden" name="game_id" value="<?= $game_id ?>">
                                                <select name="reason_type"
                                                    onchange="toggleCustomReason(this, <?= $team['id'] ?>, <?= $gc['id'] ?>)"
                                                    style="width: 100%; padding: 4px; background: #0f172a; border: 1px solid var(--border); color: #fff; font-size: 0.65rem; margin-bottom: 0.3rem; border-radius: 4px;">
                                                    <option value="No se ve el objetivo">No se ve el objetivo</option>
                                                    <option value="Imagen borrosa">Imagen borrosa</option>
                                                    <option value="No corresponde al reto">No corresponde al reto</option>
                                                    <option value="Contenido inapropiado">Contenido inapropiado</option>
                                                    <option value="Otro">Otro...</option>
                                                </select>
                                                <input type="text" name="custom_reason"
                                                    id="custom-<?= $team['id'] ?>-<?= $gc['id'] ?>" placeholder="Escriba motivo..."
                                                    style="display: none; width: 100%; padding: 4px; background: #0f172a; border: 1px solid var(--border); color: #fff; font-size: 0.65rem; margin-bottom: 0.3rem; border-radius: 4px;">
                                                <div style="display: flex; gap: 4px;">
                                                    <button type="submit"
                                                        style="flex: 1; background: #f43f5e; color: white; border: none; padding: 4px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; cursor: pointer;">CONFIRMAR</button>
                                                    <button type="button"
                                                        onclick="cancelReject(<?= $team['id'] ?>, <?= $gc['id'] ?>)"
                                                        style="background: var(--border); color: white; border: none; padding: 4px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; cursor: pointer;">❌</button>
                                                </div>
                                            </form>
                                        </div>

                                        <div
                                            style="position: absolute; top: 0; right: 0; background: <?= $is_completed ? '#4ade80' : 'var(--accent)' ?>; color: <?= $is_completed ? '#0f172a' : 'white' ?>; font-size: 0.6rem; padding: 2px 6px; border-bottom-left-radius: 6px; font-weight: 800;">
                                            REGISTRADO
                                        </div>
                                    <?php else: ?>
                                        <div
                                            style="width: 100%; aspect-ratio: 1/1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; color: var(--text-muted); padding: 1rem; text-align: center;">
                                            <span style="font-size: 1.25rem; opacity: 0.5;">📷</span>
                                            <span
                                                style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase;"><?= htmlspecialchars($gc['title']) ?></span>
                                            <span style="font-size: 0.6rem; opacity: 0.6;">(Pendiente)</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
        summary::-webkit-details-marker {
            display: none;
        }

        summary:hover {
            color: var(--primary);
        }
    </style>

    <script>
        // Auto-refresh logic
        let refreshTimer;

        function startRefreshTimer() {
            // Refresh every 10 seconds
            refreshTimer = setTimeout(() => {
                window.location.reload();
            }, 10000);
        }

        function stopRefreshTimer() {
            if (refreshTimer) {
                clearTimeout(refreshTimer);
                refreshTimer = null;
            }
        }

        // Initialize timer
        startRefreshTimer();

        function showRejectForm(teamId, challengeId) {
            stopRefreshTimer(); // Stop refreshing when form is open
            document.getElementById(`action-${teamId}-${challengeId}`).style.display = 'none';
            document.getElementById(`form-${teamId}-${challengeId}`).style.display = 'block';
        }

        function cancelReject(teamId, challengeId) {
            document.getElementById(`action-${teamId}-${challengeId}`).style.display = 'flex';
            document.getElementById(`form-${teamId}-${challengeId}`).style.display = 'none';
            startRefreshTimer(); // Restart timer when cancelled
        }

        function toggleCustomReason(select, teamId, challengeId) {
            const customInput = document.getElementById(`custom-${teamId}-${challengeId}`);
            customInput.style.display = (select.value === 'Otro') ? 'block' : 'none';

            // Stop timer again just in case interaction restarted it (paranoid check)
            stopRefreshTimer();

            if (select.value === 'Otro') customInput.required = true;
            else customInput.required = false;
        }

        async function toggleCollage(teamId, challengeId, isCollage) {
            const formData = new FormData();
            formData.append('team_id', teamId);
            formData.append('challenge_id', challengeId);
            formData.append('is_collage', isCollage ? 1 : 0);

            try {
                const response = await fetch('toggle_collage.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (!data.success) {
                    alert('Error al guardar el estado del collage');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error al conectar con el servidor');
            }
        }

        // Add listeners to inputs to pause timer when typing
        document.addEventListener('input', function (e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') {
                stopRefreshTimer();
            }
        });

        // Persistence script
        document.addEventListener('DOMContentLoaded', () => {
            const STORAGE_KEY = 'caceria_monitor_open_teams';
            const openTeams = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            openTeams.forEach(id => {
                const el = document.getElementById(`details-team-${id}`);
                if (el) el.open = true;
            });

            // Ensure timer is stopped if any form is visible on load (unlikely but safe)
            if (document.querySelector('div[id^="form-"][style*="block"]')) {
                stopRefreshTimer();
            }

            document.querySelectorAll('.team-details').forEach(details => {
                details.addEventListener('toggle', () => {
                    let currentOpen = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
                    const teamId = details.getAttribute('data-team-id');
                    if (details.open) {
                        if (!currentOpen.includes(teamId)) currentOpen.push(teamId);
                    } else {
                        currentOpen = currentOpen.filter(id => id !== teamId);
                    }
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(currentOpen));
                });
            });
        });
    </script>
</body>

</html>