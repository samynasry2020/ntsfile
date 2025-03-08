<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = new Database();

// Drop tables in reverse order of dependencies
$tables = ['shared_files', 'permissions', 'files', 'users'];

foreach ($tables as $table) {
    $sql = "DROP TABLE IF EXISTS $table";
    if ($db->query($sql)) {
        echo "Table $table dropped successfully\n";
    } else {
        echo "Error dropping table $table: " . $db->connection->error . "\n";
    }
}

echo "All tables have been dropped.\n";
