<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();

// Get user information
$user = $db->query("SELECT * FROM users WHERE id = " . $db->escape($_SESSION['user_id']))->fetch_assoc();

// Check if user was found
if (!$user) {
    header('Location: login.php');
    exit();
}

// Get user's files and folders
$files = $db->query("SELECT f.*, p.can_read, p.can_write, p.can_delete, p.can_rename, p.can_share 
                    FROM files f 
                    LEFT JOIN permissions p ON f.id = p.file_id AND p.user_id = " . $db->escape($_SESSION['user_id']) . "
                    WHERE f.parent_id IS NULL 
                    ORDER BY f.type DESC, f.name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NTSFM - File Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.png" alt="Company Logo" height="30" class="d-inline-block align-top">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shared.php">Shared Files</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($user['username'] ?? 'Guest'); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>File Manager</h2>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newFolderModal">
                        <i class="bi bi-folder-plus"></i> New Folder
                    </button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                        <i class="bi bi-upload"></i> Upload File
                    </button>
                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#searchModal">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#compressModal">
                        <i class="bi bi-compress"></i> Compress
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <?php foreach ($files as $file): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php if ($file['type'] == 'folder'): ?>
                                    <i class="bi bi-folder"></i>
                                <?php else: ?>
                                    <i class="bi bi-file-earmark"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($file['name']); ?>
                            </h5>
                            <p class="card-text">
                                <?php if ($file['type'] == 'file'): ?>
                                    Size: <?php echo formatFileSize($file['size']); ?>
                                <?php endif; ?>
                            </p>
                            <div class="btn-group w-100">
                                <?php if ($file['can_read']): ?>
                                    <button class="btn btn-primary btn-sm" onclick="previewFile(<?php echo $file['id']; ?>)">
                                        <i class="bi bi-eye"></i> Preview
                                    </button>
                                <?php endif; ?>
                                <?php if ($file['can_write']): ?>
                                    <button class="btn btn-success btn-sm" onclick="renameFile(<?php echo $file['id']; ?>)">
                                        <i class="bi bi-pencil"></i> Rename
                                    </button>
                                    <button class="btn btn-info btn-sm" onclick="tagFile(<?php echo $file['id']; ?>)">
                                        <i class="bi bi-tag"></i> Tag
                                    </button>
                                <?php endif; ?>
                                <?php if ($file['can_share']): ?>
                                    <button class="btn btn-warning btn-sm" onclick="shareFile(<?php echo $file['id']; ?>)">
                                        <i class="bi bi-share"></i> Share
                                    </button>
                                <?php endif; ?>
                                <?php if ($file['can_delete']): ?>
                                    <button class="btn btn-danger btn-sm" onclick="deleteFile(<?php echo $file['id']; ?>)">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php if ($file['type'] == 'file' && $file['can_write']): ?>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-primary" onclick="showVersions(<?php echo $file['id']; ?>)">
                                        <i class="bi bi-clock-history"></i> Versions
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- New Folder Modal -->
    <div class="modal fade" id="newFolderModal" tabindex="-1" role="dialog" aria-labelledby="newFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newFolderModalLabel">Create New Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newFolderForm">
                        <div class="mb-3">
                            <label for="folderName" class="form-label">Folder Name</label>
                            <input type="text" class="form-control" id="folderName" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Close</button>
                    <button type="button" class="btn btn-primary" onclick="createFolder()" aria-label="Create Folder">Create</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload File Modal -->
    <div class="modal fade" id="uploadFileModal" tabindex="-1" role="dialog" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadFileModalLabel">Upload File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="file" class="form-label">Select File</label>
                            <input type="file" class="form-control" id="file" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Close</button>
                    <button type="button" class="btn btn-primary" onclick="uploadFile()" aria-label="Upload File">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Modal -->
    <div class="modal fade" id="searchModal" tabindex="-1" role="dialog" aria-labelledby="searchModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchModalLabel">Search Files</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="searchForm">
                        <div class="mb-3">
                            <label for="searchQuery" class="form-label">Search Query</label>
                            <input type="text" class="form-control" id="searchQuery" name="query" required>
                        </div>
                        <div class="mb-3">
                            <label for="searchType" class="form-label">Search Type</label>
                            <select class="form-select" id="searchType" name="type">
                                <option value="">All Types</option>
                                <option value="file">Files</option>
                                <option value="folder">Folders</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="searchTags" class="form-label">Tags</label>
                            <input type="text" class="form-control" id="searchTags" name="tags" placeholder="Comma-separated tags">
                        </div>
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Compress Modal -->
    <div class="modal fade" id="compressModal" tabindex="-1" role="dialog" aria-labelledby="compressModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="compressModalLabel">Compress Files</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="compressForm">
                        <div class="mb-3">
                            <label for="filesToCompress" class="form-label">Select Files</label>
                            <select class="form-select" id="filesToCompress" name="file_ids[]" multiple required>
                                <?php foreach ($files as $file): ?>
                                    <?php if ($file['type'] == 'file' && $file['can_read']): ?>
                                        <option value="<?php echo $file['id']; ?>">
                                            <?php echo htmlspecialchars($file['name']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="compressParent" class="form-label">Destination Folder</label>
                            <select class="form-select" id="compressParent" name="parent_id">
                                <option value="">Root</option>
                                <?php foreach ($files as $file): ?>
                                    <?php if ($file['type'] == 'folder' && $file['can_write']): ?>
                                        <option value="<?php echo $file['id']; ?>">
                                            <?php echo htmlspecialchars($file['name']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Compress</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">File Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="previewContent"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Versions Modal -->
    <div class="modal fade" id="versionsModal" tabindex="-1" role="dialog" aria-labelledby="versionsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="versionsModalLabel">File Versions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="versionsList"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tag Modal -->
    <div class="modal fade" id="tagModal" tabindex="-1" role="dialog" aria-labelledby="tagModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tagModalLabel">Tag File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="tagForm">
                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags</label>
                            <input type="text" class="form-control" id="tags" name="tags" required placeholder="Comma-separated tags">
                        </div>
                        <button type="submit" class="btn btn-primary">Save Tags</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1" role="dialog" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Share File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="shareForm">
                        <div class="mb-3">
                            <label for="shareUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="shareUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="sharePermissions" class="form-label">Permissions</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="canRead" name="permissions[read]" checked>
                                <label class="form-check-label" for="canRead">Can Read</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="canWrite" name="permissions[write]">
                                <label class="form-check-label" for="canWrite">Can Write</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="canDelete" name="permissions[delete]">
                                <label class="form-check-label" for="canDelete">Can Delete</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="canRename" name="permissions[rename]">
                                <label class="form-check-label" for="canRename">Can Rename</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="canShare" name="permissions[share]">
                                <label class="form-check-label" for="canShare">Can Share</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Share</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Shared Link Modal -->
    <div class="modal fade" id="sharedLinkModal" tabindex="-1" role="dialog" aria-labelledby="sharedLinkModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sharedLinkModalLabel">Create Shared Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="sharedLinkForm">
                        <div class="mb-3">
                            <label for="expiresAt" class="form-label">Expiration Date (Optional)</label>
                            <input type="datetime-local" class="form-control" id="expiresAt" name="expires_at">
                        </div>
                        <button type="submit" class="btn btn-primary">Create Link</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        // File preview functionality
        function previewFile(id) {
            $.post('includes/handlers.php', {
                action: 'preview_file',
                id: id
            }, function(response) {
                if (response.success) {
                    const file = response.file;
                    const previewType = response.previewType;
                    
                    let content = '';
                    
                    if (previewType === 'text') {
                        $.get('uploads/' + file.path, function(data) {
                            content = '<pre>' + data + '</pre>';
                            $('#previewContent').html(content);
                            $('#previewModal').modal('show');
                        });
                    } else if (previewType === 'image') {
                        content = '<img src="uploads/' + file.path + '" class="img-fluid" alt="' + file.name + '">';
                        $('#previewContent').html(content);
                        $('#previewModal').modal('show');
                    } else if (previewType === 'pdf') {
                        content = '<embed src="uploads/' + file.path + '" type="application/pdf" width="100%" height="600px">';
                        $('#previewContent').html(content);
                        $('#previewModal').modal('show');
                    } else {
                        alert('File type not supported for preview');
                    }
                } else {
                    alert(response.message);
                }
            });
        }

        // File versions functionality
        function showVersions(id) {
            $.post('includes/handlers.php', {
                action: 'get_file_versions',
                id: id
            }, function(response) {
                if (response.success) {
                    let versionsHtml = '<table class="table">\n' +
                                        '<thead>\n' +
                                            '<tr>\n' +
                                                '<th>Version</th>\n' +
                                                '<th>Size</th>\n' +
                                                '<th>Date</th>\n' +
                                                '<th>Actions</th>\n' +
                                            '</tr>\n' +
                                        '</thead>\n' +
                                        '<tbody>';
                    
                    response.versions.forEach(function(version, index) {
                        versionsHtml += '<tr>';
                        versionsHtml += '<td>Version ' + (index + 1) + '</td>';
                        versionsHtml += '<td>' + formatFileSize(version.size) + '</td>';
                        versionsHtml += '<td>' + new Date(version.created_at).toLocaleString() + '</td>';
                        versionsHtml += '<td><button class="btn btn-sm btn-primary" onclick="restoreVersion(' + version.id + ')">Restore</button></td>';
                        versionsHtml += '</tr>';
                    });
                    
                    versionsHtml += '</tbody></table>';
                    
                    $('#versionsList').html(versionsHtml);
                    $('#versionsModal').modal('show');
                } else {
                    alert(response.message);
                }
            });
        }

        // Restore version functionality
        function restoreVersion(versionId) {
            $.post('includes/handlers.php', {
                action: 'restore_version',
                version_id: versionId
            }, function(response) {
                if (response.success) {
                    alert('Version restored successfully');
                    location.reload();
                } else {
                    alert(response.message);
                }
            });
        }

        // Tag file functionality
        function tagFile(id) {
            $('#tagForm').on('submit', function(e) {
                e.preventDefault();
                
                $.post('includes/handlers.php', {
                    action: 'tag_file',
                    id: id,
                    tags: $('#tags').val()
                }, function(response) {
                    if (response.success) {
                        alert('Tags saved successfully');
                        $('#tagModal').modal('hide');
                    } else {
                        alert(response.message);
                    }
                });
            });
        }

        // Search functionality
        $('#searchForm').on('submit', function(e) {
            e.preventDefault();
            
            $.post('includes/handlers.php', {
                action: 'search_files',
                query: $('#searchQuery').val(),
                type: $('#searchType').val(),
                tags: $('#searchTags').val()
            }, function(response) {
                if (response.success) {
                    let filesHtml = '';
                    response.files.forEach(function(file) {
                        filesHtml += '<div class="col-md-4 mb-4">';
                        filesHtml += '<div class="card">';
                        filesHtml += '<div class="card-body">';
                        filesHtml += '<h5 class="card-title">';
                        filesHtml += file.type === 'folder' ? '<i class="bi bi-folder"></i>' : '<i class="bi bi-file-earmark"></i>';
                        filesHtml += ' ' + file.name + '</h5>';
                        if (file.type === 'file') {
                            filesHtml += '<p class="card-text">Size: ' + formatFileSize(file.size) + '</p>';
                        }
                        filesHtml += '</div></div></div>';
                    });
                    
                    $('.row').html(filesHtml);
                } else {
                    alert(response.message);
                }
            });
        });

        // Compress files functionality
        $('#compressForm').on('submit', function(e) {
            e.preventDefault();
            
            $.post('includes/handlers.php', {
                action: 'compress_files',
                file_ids: $('#filesToCompress').val(),
                parent_id: $('#compressParent').val()
            }, function(response) {
                if (response.success) {
                    alert('Files compressed successfully');
                    location.reload();
                } else {
                    alert(response.message);
                }
            });
        });

        // Share file functionality
        function shareFile(id) {
            $('#shareForm').on('submit', function(e) {
                e.preventDefault();
                
                $.post('includes/handlers.php', {
                    action: 'share',
                    id: id,
                    username: $('#shareUsername').val(),
                    permissions: {
                        read: $('#canRead').is(':checked'),
                        write: $('#canWrite').is(':checked'),
                        delete: $('#canDelete').is(':checked'),
                        rename: $('#canRename').is(':checked'),
                        share: $('#canShare').is(':checked')
                    }
                }, function(response) {
                    if (response.success) {
                        alert('File shared successfully');
                        $('#shareModal').modal('hide');
                    } else {
                        alert(response.message);
                    }
                });
            });
        }

        // Create shared link functionality
        function createSharedLink(id) {
            $('#sharedLinkForm').on('submit', function(e) {
                e.preventDefault();
                
                $.post('includes/handlers.php', {
                    action: 'create_shared_link',
                    id: id,
                    expires_at: $('#expiresAt').val()
                }, function(response) {
                    if (response.success) {
                        alert('Shared link created: ' + response.link);
                        $('#sharedLinkModal').modal('hide');
                    } else {
                        alert(response.message);
                    }
                });
            });
        }

        // Helper function to format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>
