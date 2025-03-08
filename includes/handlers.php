<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$db = new Database();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create_folder':
        $name = $db->escape($_POST['name']);
        $parentId = isset($_POST['parent_id']) ? $db->escape($_POST['parent_id']) : null;
        
        $sql = "INSERT INTO files (name, type, parent_id, user_id) VALUES 
                ('$name', 'folder', $parentId, " . $_SESSION['user_id'] . ")";
        
        if ($db->query($sql)) {
            $fileId = $db->insert_id;
            
            // Create default permissions
            $sql = "INSERT INTO permissions (file_id, user_id, can_read, can_write, can_delete, can_rename, can_share) VALUES 
                    ($fileId, " . $_SESSION['user_id'] . ", 1, 1, 1, 1, 1)";
            
            if ($db->query($sql)) {
                echo json_encode(['success' => true]);
            } else {
                // Rollback folder creation if permissions fail
                $db->query("DELETE FROM files WHERE id = $fileId");
                echo json_encode(['success' => false, 'message' => 'Error creating permissions']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating folder: ' . $db->error]);
        }
        break;

    case 'upload_file':
        $file = $_FILES['file'];
        $name = $db->escape($file['name']);
        $parentId = isset($_POST['parent_id']) ? $db->escape($_POST['parent_id']) : null;
        
        // Check file type
        $fileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowedTypes = ALLOWED_FILE_TYPES;
        $isValidType = false;
        
        foreach ($allowedTypes as $type => $extensions) {
            if (in_array($fileType, $extensions)) {
                $isValidType = true;
                break;
            }
        }
        
        if (!$isValidType) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type']);
            exit();
        }
        
        // Generate unique filename
        $uniqueName = uniqid() . '_' . $name;
        $uploadPath = UPLOAD_DIR . '/' . $uniqueName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $size = filesize($uploadPath);
            
            $sql = "INSERT INTO files (name, path, type, size, parent_id, user_id) VALUES 
                    ('$name', '$uniqueName', 'file', $size, $parentId, " . $_SESSION['user_id'] . ")";
            
            if ($db->query($sql)) {
                $fileId = $db->insert_id;
                
                // Create default permissions
                $sql = "INSERT INTO permissions (file_id, user_id, can_read, can_write, can_delete, can_rename, can_share) VALUES 
                        ($fileId, " . $_SESSION['user_id'] . ", 1, 1, 1, 1, 1)";
                
                $db->query($sql);
                echo json_encode(['success' => true]);
            } else {
                unlink($uploadPath);
                echo json_encode(['success' => false, 'message' => 'Error saving file information']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error uploading file']);
        }
        break;

    case 'rename':
        $id = $db->escape($_POST['id']);
        $newName = $db->escape($_POST['name']);
        
        $sql = "UPDATE files SET name = '$newName' WHERE id = $id";
        
        if ($db->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error renaming']);
        }
        break;

    case 'delete':
        $id = $db->escape($_POST['id']);
        
        // Get file information
        $file = $db->query("SELECT * FROM files WHERE id = $id")->fetch_assoc();
        
        if ($file['type'] == 'file') {
            // Delete physical file
            unlink(UPLOAD_DIR . '/' . $file['path']);
        }
        
        // Delete from database
        $sql = "DELETE FROM files WHERE id = $id";
        
        if ($db->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting']);
        }
        break;

    case 'share':
        $id = $db->escape($_POST['id']);
        $username = $db->escape($_POST['username']);
        
        // Get user ID
        $user = $db->query("SELECT id FROM users WHERE username = '$username'")->fetch_assoc();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit();
        }
        
        // Insert into shared_files table
        $sql = "INSERT INTO shared_files (file_id, shared_with, shared_by) VALUES 
                ($id, " . $user['id'] . ", " . $_SESSION['user_id'] . ")";
        
        if ($db->query($sql)) {
            // Create permissions for shared user
            $sql = "INSERT INTO permissions (file_id, user_id, can_read, can_write, can_delete, can_rename, can_share) VALUES 
                    ($id, " . $user['id'] . ", 1, 0, 0, 0, 0)";
            
            $db->query($sql);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error sharing file']);
        }
        break;

    case 'create_shared_link':
        $id = $db->escape($_POST['id']);
        $expiresAt = isset($_POST['expires_at']) ? $db->escape($_POST['expires_at']) : null;
        
        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO shared_links (file_id, token, expires_at) VALUES 
                ($id, '$token', $expiresAt)";
        
        if ($db->query($sql)) {
            $link = "https://" . $_SERVER['HTTP_HOST'] . "/shared.php?token=" . $token;
            echo json_encode(['success' => true, 'link' => $link]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating shared link']);
        }
        break;

    case 'manage_permissions':
        $fileId = $db->escape($_POST['file_id']);
        $userId = $db->escape($_POST['user_id']);
        $permissions = $_POST['permissions'];
        
        $sql = "UPDATE permissions SET 
                can_read = " . ($permissions['read'] ? 1 : 0) . ",
                can_write = " . ($permissions['write'] ? 1 : 0) . ",
                can_delete = " . ($permissions['delete'] ? 1 : 0) . ",
                can_rename = " . ($permissions['rename'] ? 1 : 0) . ",
                can_share = " . ($permissions['share'] ? 1 : 0) . "
                WHERE file_id = $fileId AND user_id = $userId";
        
        if ($db->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating permissions']);
        }
        break;

    case 'search_files':
        $query = $db->escape($_POST['query']);
        
        $sql = "SELECT f.*, p.can_read, p.can_write, p.can_delete, p.can_rename, p.can_share 
                FROM files f 
                LEFT JOIN permissions p ON f.id = p.file_id AND p.user_id = " . $_SESSION['user_id'] . "
                WHERE f.name LIKE '%$query%' 
                ORDER BY f.type DESC, f.name";
        
        $result = $db->query($sql);
        $files = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'files' => $files]);
        break;

    case 'get_file_versions':
        $id = $db->escape($_POST['id']);
        
        $sql = "SELECT * FROM file_versions 
                WHERE file_id = $id 
                ORDER BY created_at DESC";
        
        $result = $db->query($sql);
        $versions = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'versions' => $versions]);
        break;

    case 'restore_version':
        $versionId = $db->escape($_POST['version_id']);
        
        $sql = "SELECT * FROM file_versions WHERE id = $versionId";
        $result = $db->query($sql);
        $version = $result->fetch_assoc();
        
        if ($version) {
            // Restore the file
            $sql = "UPDATE files SET 
                    name = '$version[name]',
                    size = $version[size],
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = $version[file_id]";
            
            if ($db->query($sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error restoring version']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Version not found']);
        }
        break;

    case 'preview_file':
        $id = $db->escape($_POST['id']);
        
        $sql = "SELECT * FROM files WHERE id = $id AND user_id = " . $_SESSION['user_id'];
        $result = $db->query($sql);
        $file = $result->fetch_assoc();
        
        if ($file) {
            $filePath = UPLOAD_DIR . '/' . $file['path'];
            
            // Get file extension
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Determine preview type
            $previewType = 'unknown';
            
            if (in_array($extension, ['txt', 'csv'])) {
                $previewType = 'text';
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $previewType = 'image';
            } elseif (in_array($extension, ['pdf'])) {
                $previewType = 'pdf';
            }
            
            echo json_encode([
                'success' => true,
                'file' => $file,
                'previewType' => $previewType
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'File not found']);
        }
        break;

    case 'tag_file':
        $id = $db->escape($_POST['id']);
        $tags = $db->escape($_POST['tags']);
        
        // Delete existing tags
        $sql = "DELETE FROM file_tags WHERE file_id = $id";
        $db->query($sql);
        
        // Insert new tags
        $tagArray = explode(',', $tags);
        foreach ($tagArray as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
                $sql = "INSERT INTO file_tags (file_id, tag) VALUES ($id, '$tag')";
                $db->query($sql);
            }
        }
        
        echo json_encode(['success' => true]);
        break;

    case 'compress_files':
        $fileIds = $_POST['file_ids'];
        $parentId = isset($_POST['parent_id']) ? $db->escape($_POST['parent_id']) : null;
        
        // Create a temporary zip file
        $zip = new ZipArchive();
        $tempZip = tempnam(sys_get_temp_dir(), 'ntsfm');
        
        if ($zip->open($tempZip, ZipArchive::CREATE) === TRUE) {
            foreach ($fileIds as $id) {
                $sql = "SELECT * FROM files WHERE id = $id AND user_id = " . $_SESSION['user_id'];
                $result = $db->query($sql);
                $file = $result->fetch_assoc();
                
                if ($file) {
                    $filePath = UPLOAD_DIR . '/' . $file['path'];
                    $zip->addFile($filePath, $file['name']);
                }
            }
            
            $zip->close();
            
            // Create a new file record for the zip
            $zipName = 'archive_' . date('Y-m-d_H-i-s') . '.zip';
            $uniqueName = uniqid() . '_' . $zipName;
            $uploadPath = UPLOAD_DIR . '/' . $uniqueName;
            
            // Move the temporary zip to the upload directory
            rename($tempZip, $uploadPath);
            
            // Get file size
            $size = filesize($uploadPath);
            
            $sql = "INSERT INTO files (name, path, type, size, parent_id, user_id) VALUES 
                    ('$zipName', '$uniqueName', 'file', $size, $parentId, " . $_SESSION['user_id'] . ")";
            
            if ($db->query($sql)) {
                $fileId = $db->insert_id;
                
                // Create default permissions
                $sql = "INSERT INTO permissions (file_id, user_id, can_read, can_write, can_delete, can_rename, can_share) VALUES 
                        ($fileId, " . $_SESSION['user_id'] . ", 1, 1, 1, 1, 1)";
                
                $db->query($sql);
                echo json_encode(['success' => true]);
            } else {
                unlink($uploadPath);
                echo json_encode(['success' => false, 'message' => 'Error saving zip file information']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating zip archive']);
        }
        break;

    case 'decompress_file':
        $id = $db->escape($_POST['id']);
        $parentId = isset($_POST['parent_id']) ? $db->escape($_POST['parent_id']) : null;
        
        $sql = "SELECT * FROM files WHERE id = $id AND user_id = " . $_SESSION['user_id'];
        $result = $db->query($sql);
        $file = $result->fetch_assoc();
        
        if ($file) {
            $filePath = UPLOAD_DIR . '/' . $file['path'];
            
            // Create a new folder for the extracted files
            $folderName = pathinfo($file['name'], PATHINFO_FILENAME);
            $uniqueName = uniqid() . '_' . $folderName;
            
            $sql = "INSERT INTO files (name, type, parent_id, user_id) VALUES 
                    ('$folderName', 'folder', $parentId, " . $_SESSION['user_id'] . ")";
            
            if ($db->query($sql)) {
                $folderId = $db->insert_id;
                
                // Create default permissions for the folder
                $sql = "INSERT INTO permissions (file_id, user_id, can_read, can_write, can_delete, can_rename, can_share) VALUES 
                        ($folderId, " . $_SESSION['user_id'] . ", 1, 1, 1, 1, 1)";
                
                $db->query($sql);
                
                // Extract the zip file
                $zip = new ZipArchive();
                if ($zip->open($filePath) === TRUE) {
                    $extractPath = UPLOAD_DIR . '/' . $uniqueName;
                    mkdir($extractPath, 0777, true);
                    
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $fileName = $zip->getNameIndex($i);
                        $filePath = $extractPath . '/' . $fileName;
                        
                        if ($zip->extractTo($extractPath, [$fileName])) {
                            // Create file record
                            $uniqueName = uniqid() . '_' . $fileName;
                            $finalPath = UPLOAD_DIR . '/' . $uniqueName;
                            rename($filePath, $finalPath);
                            
                            $size = filesize($finalPath);
                            
                            $sql = "INSERT INTO files (name, path, type, size, parent_id, user_id) VALUES 
                                    ('$fileName', '$uniqueName', 'file', $size, $folderId, " . $_SESSION['user_id'] . ")";
                            
                            if ($db->query($sql)) {
                                $fileId = $db->insert_id;
                                
                                // Create default permissions
                                $sql = "INSERT INTO permissions (file_id, user_id, can_read, can_write, can_delete, can_rename, can_share) VALUES 
                                        ($fileId, " . $_SESSION['user_id'] . ", 1, 1, 1, 1, 1)";
                                
                                $db->query($sql);
                            }
                        }
                    }
                    
                    $zip->close();
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error opening zip file']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Error creating folder']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'File not found']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$db->close();
?>
