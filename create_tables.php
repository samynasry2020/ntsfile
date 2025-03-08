<?php
require_once 'config.php';

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }

    // Enable SSL
    $db->ssl_set(null, null, null, null, null);

    // Read and execute the schema file
    $schema = file_get_contents('schema.sql');
    $statements = explode(';', $schema);

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->query($statement);
            if ($db->error) {
                throw new Exception("Error executing statement: " . $db->error);
            }
        }
    }

    echo "Tables created successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
