<?php
// admin/toggle_collage.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $team_id = (int) $_POST['team_id'];
    $challenge_id = (int) $_POST['challenge_id'];
    $is_collage = (int) $_POST['is_collage'];

    db_query("
        UPDATE submissions 
        SET is_collage = ? 
        WHERE team_id = ? AND challenge_id = ?
    ", [$is_collage, $team_id, $challenge_id]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
?>