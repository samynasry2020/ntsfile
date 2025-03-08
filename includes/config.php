<?php
define('DB_HOST', 'ntsfm-ntsfm.c.aivencloud.com');
define('DB_USER', 'avnadmin');
define('DB_PASS', '');
define('DB_NAME', 'defaultdb');
define('DB_PORT', 17153);

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
