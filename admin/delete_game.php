<?php
// admin/delete_game.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $game_id = (int)$_GET['id'];
    
    // The database has ON DELETE CASCADE, so deleting the game 
    // will also delete its teams, players, challenges, and submissions.
    db_query("DELETE FROM games WHERE id = ?", [$game_id]);
}

header('Location: dashboard.php');
exit;
