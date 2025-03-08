<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();

// Get file ID
$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: index.php');
    exit();
}

// Get file information
$sql = "SELECT f.*, p.can_read 
        FROM files f 
        LEFT JOIN permissions p ON f.id = p.file_id AND p.user_id = " . $_SESSION['user_id'] . "
        WHERE f.id = " . $db->escape($id);

$result = $db->query($sql);
$file = $result->fetch_assoc();

// Check if file exists and user has read permission
if (!$file || !$file['can_read']) {
    header('Location: index.php');
    exit();
}

// Set appropriate headers for file download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $file['size']);

// Read and output the file
readfile(UPLOAD_DIR . '/' . $file['path']);
exit();
?>
