<?php
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Database configuration from Aiven
define('DB_HOST', getenv('AIVEN_PG_HOST') ?: 'ntsfm-ntsfm.c.aivencloud.com');
define('DB_USER', getenv('AIVEN_PG_USER') ?: 'avnadmin');
define('DB_PASS', getenv('AIVEN_PG_PASSWORD') ?: '');
define('DB_NAME', getenv('AIVEN_PG_DATABASE') ?: 'defaultdb');
define('DB_PORT', getenv('AIVEN_PG_PORT') ?: 17153);

// Application configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('ALLOWED_FILE_TYPES', [
    'image' => ['jpg', 'jpeg', 'png', 'gif'],
    'document' => ['pdf', 'doc', 'docx', 'txt', 'csv'],
    'archive' => ['zip', 'rar'],
    'audio' => ['mp3', 'wav'],
    'video' => ['mp4', 'avi', 'mov']
]);

define('MAX_UPLOAD_SIZE', 104857600); // 100MB

define('VERSION_HISTORY_LIMIT', 10);
define('SHARED_LINK_EXPIRY_DAYS', 7);
?>
