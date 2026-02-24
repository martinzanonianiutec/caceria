<?php
// fix_database.php
require_once 'includes/db.php';

header('Content-Type: text/plain');

function check_and_add_column($table, $column, $definition)
{
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            echo "Adding column '$column' to '$table'...\n";
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "Success!\n";
        } else {
            echo "Column '$column' already exists in '$table'.\n";
        }
    } catch (Exception $e) {
        echo "Error checking/adding column '$column' in '$table': " . $e->getMessage() . "\n";
    }
}

echo "Starting Database Sync...\n\n";

// Ensure submissions has player_id (from previous turn)
check_and_add_column('submissions', 'player_id', 'INT NULL AFTER challenge_id');

// Ensure submissions has is_collage (from this turn)
check_and_add_column('submissions', 'is_collage', 'TINYINT(1) DEFAULT 0');

// Ensure submissions has foreign key for player_id if missing (optional but good)
try {
    // Check if FK exists
    $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'submissions' AND COLUMN_NAME = 'player_id' AND REFERENCED_TABLE_NAME = 'players'");
    if ($stmt->rowCount() == 0) {
        echo "Adding foreign key for player_id...\n";
        $pdo->exec("ALTER TABLE submissions ADD CONSTRAINT fk_submissions_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE SET NULL");
        echo "Success!\n";
    }
} catch (Exception $e) {
    echo "Note: Could not add Foreign Key (might already exist or index missing): " . $e->getMessage() . "\n";
}

echo "\nDone. Please try accessing the collage again.";
?>