<?php
define('DB_HOST', 'YOUR_AIVEN_HOST');
define('DB_USER', 'YOUR_AIVEN_USER');
define('DB_PASS', 'YOUR_AIVEN_PASSWORD');
define('DB_NAME', 'ntsfm');
define('DB_PORT', 12345);

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
