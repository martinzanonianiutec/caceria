<?php
// config/db.php
// config/db.php
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'caceria');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('BASE_URL', getenv('BASE_URL') ?: '/caceria/');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Auto-Initialization for Render/Cloud Deployment
    if (getenv('DB_AUTO_INIT') === 'true' || DB_HOST !== '127.0.0.1') {
        $check = $pdo->query("SHOW TABLES LIKE 'games'");
        if ($check->rowCount() == 0) {
            // Database is empty, let's initialize it
            $sql_files = [
                '../sql/schema.sql',
                '../sql/create_admins_table.sql',
                '../sql/create_team_colors.sql'
            ];

            // If called from a non-admin subfolder, path might change
            if (!file_exists($sql_files[0])) {
                $sql_files = array_map(fn($f) => str_replace('../', '', $f), $sql_files);
            }

            foreach ($sql_files as $file) {
                if (file_exists($file)) {
                    $sql = file_get_contents($file);
                    // Remove comments and split by semicolon
                    $sql = preg_replace('/--.*$/m', '', $sql);
                    $queries = explode(';', $sql);
                    foreach ($queries as $q) {
                        $q = trim($q);
                        if (!empty($q))
                            $pdo->exec($q);
                    }
                }
            }
        }
    }
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