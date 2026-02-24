<?php
// admin/reject_submission.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Ensure rejections table exists
db_query("CREATE TABLE IF NOT EXISTS rejections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    challenge_id INT NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_id = (int)$_POST['team_id'];
    $challenge_id = (int)$_POST['challenge_id'];
    $game_id = (int)$_POST['game_id'];
    $reason_type = $_POST['reason_type'];
    $custom_reason = trim($_POST['custom_reason']);
    
    $final_reason = ($reason_type === 'Otro') ? $custom_reason : $reason_type;
    
    if ($team_id && $challenge_id && $game_id && $final_reason) {
        // 1. Delete the submission
        db_query("DELETE FROM submissions WHERE team_id = ? AND challenge_id = ?", [$team_id, $challenge_id]);
        
        // 2. Save the rejection reason
        db_query("DELETE FROM rejections WHERE team_id = ? AND challenge_id = ?", [$team_id, $challenge_id]);
        db_insert("INSERT INTO rejections (team_id, challenge_id, reason) VALUES (?, ?, ?)", [$team_id, $challenge_id, $final_reason]);
        
        header("Location: monitor.php?id=" . $game_id);
        exit;
    }
}

die("Error en la solicitud.");
