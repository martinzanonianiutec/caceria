<?php
// config/db.php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'caceria');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/caceria/');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function db_query($query, $params = [])
{
    global $pdo;
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

function db_fetch($query, $params = [])
{
    return db_query($query, $params)->fetch();
}

function db_fetch_all($query, $params = [])
{
    return db_query($query, $params)->fetchAll();
}

function db_insert($query, $params = [])
{
    global $pdo;
    db_query($query, $params);
    return $pdo->lastInsertId();
}
?>