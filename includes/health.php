<?php
header('Content-Type: application/json');

try {
    $db = new PDO(
        "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $status = [
        'status' => 'healthy',
        'database' => 'connected',
        'timestamp' => date('Y-m-d H:i:s')
    ];
} catch (Exception $e) {
    $status = [
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

echo json_encode($status);
