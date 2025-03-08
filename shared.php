<?php
session_start();
require_once 'includes/db.php';

// Check for token
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: index.php');
    exit();
}

$db = new Database();

// Get file information
$sql = "SELECT f.*, sl.expires_at 
        FROM files f 
        JOIN shared_links sl ON f.id = sl.file_id 
        WHERE sl.token = '$token'";

$result = $db->query($sql);
$file = $result->fetch_assoc();

// Check if file exists and link hasn't expired
if (!$file || ($file['expires_at'] && strtotime($file['expires_at']) < time())) {
    echo "Invalid or expired link";
    exit();
}

// Check if file is a folder
$isFolder = $file['type'] === 'folder';

// Get files in folder if it's a folder
$files = [];
if ($isFolder) {
    $sql = "SELECT f.*, p.can_read, p.can_write, p.can_delete, p.can_rename, p.can_share 
            FROM files f 
            LEFT JOIN permissions p ON f.id = p.file_id AND p.user_id = " . $_SESSION['user_id'] . "
            WHERE f.parent_id = " . $db->escape($file['id']) . "
            ORDER BY f.type DESC, f.name";
    
    $result = $db->query($sql);
    $files = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared File - NTSFM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><?php echo htmlspecialchars($file['name']); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if ($isFolder): ?>
                            <div class="row">
                                <?php foreach ($files as $childFile): ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <?php if ($childFile['type'] == 'folder'): ?>
                                                        <i class="bi bi-folder"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-file-earmark"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($childFile['name']); ?>
                                                </h5>
                                                <p class="card-text">
                                                    <?php if ($childFile['type'] == 'file'): ?>
                                                        Size: <?php echo formatFileSize($childFile['size']); ?>
                                                    <?php endif; ?>
                                                </p>
                                                <div class="btn-group w-100">
                                                    <a href="download.php?id=<?php echo $childFile['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-download"></i> Download
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <a href="download.php?id=<?php echo $file['id']; ?>" class="btn btn-primary btn-lg">
                                    <i class="bi bi-download"></i> Download File
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
