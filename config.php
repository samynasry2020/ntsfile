<?php
define('DB_HOST', 'ntsfm-ntsfm.c.aivencloud.com');
define('DB_USER', 'avnadmin');
define('DB_PASS', 'AVNS_tZ_mA6t9dITpkAznJPL');
define('DB_NAME', 'defaultdb');
define('DB_PORT', '17153');

define('BASE_URL', 'http://ntsfm.test');
define('UPLOAD_DIR', __DIR__ . '/uploads');

define('DEFAULT_PERMISSIONS', [
    'read' => true,
    'write' => false,
    'delete' => false,
    'rename' => false,
    'share' => false
]);

// File types allowed for upload
define('ALLOWED_FILE_TYPES', [
    'image' => ['jpg', 'jpeg', 'png', 'gif'],
    'document' => ['pdf', 'doc', 'docx', 'txt'],
    'archive' => ['zip', 'rar'],
    'spreadsheet' => ['xls', 'xlsx'],
    'presentation' => ['ppt', 'pptx']
]);
?>
