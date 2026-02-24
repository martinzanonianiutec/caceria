<?php
// index.php
session_start();
require_once 'includes/db.php';

// Session Persistence Logic: Restore session if cookie exists but session is empty
if (!isset($_SESSION['player_id']) && isset($_COOKIE['caceria_puid'])) {
    $puid = (int) $_COOKIE['caceria_puid'];
    $player = db_fetch("
        SELECT p.*, t.game_id 
        FROM players p 
        JOIN teams t ON p.team_id = t.id 
        WHERE p.id = ?
    ", [$puid]);

    if ($player) {
        $_SESSION['player_id'] = $player['id'];
        $_SESSION['team_id'] = $player['team_id'];
        $_SESSION['game_id'] = $player['game_id'];
    }
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/caceria', '', $path);

if (strpos($path, '/join/') === 0) {
    require 'public/join.php';
} elseif ($path == '/dashboard.php') {
    require 'public/dashboard.php';
} elseif ($path == '/upload.php') {
    require 'public/upload.php';
} elseif ($path == '/results.php') {
    require 'public/results.php';
} elseif ($path == '/' || $path == '' || $path == '/index.php') {
    require 'public/home.php';
} else {
    http_response_code(404);
    echo "Página no encontrada: " . htmlspecialchars($path);
}
?>