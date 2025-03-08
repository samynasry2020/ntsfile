// File size formatting function
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Create folder function
function createFolder() {
    const folderName = document.getElementById('folderName').value;
    if (!folderName) return;

    $.ajax({
        url: 'includes/handlers.php',
        type: 'POST',
        data: {
            action: 'create_folder',
            name: folderName
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('An error occurred while creating the folder');
        }
    });
}

// Upload file function
function uploadFile() {
    const fileInput = document.getElementById('file');
    const file = fileInput.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'upload_file');

    $.ajax({
        url: 'includes/handlers.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('An error occurred while uploading the file');
        }
    });
}

// Rename file/folder function
function renameFile(id) {
    const newName = prompt('Enter new name:');
    if (!newName) return;

    $.ajax({
        url: 'includes/handlers.php',
        type: 'POST',
        data: {
            action: 'rename',
            id: id,
            name: newName
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('An error occurred while renaming');
        }
    });
}

// Delete file/folder function
function deleteFile(id) {
    if (!confirm('Are you sure you want to delete this item?')) return;

    $.ajax({
        url: 'includes/handlers.php',
        type: 'POST',
        data: {
            action: 'delete',
            id: id
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('An error occurred while deleting');
        }
    });
}

// Share file function
function shareFile(id) {
    const username = prompt('Enter username to share with:');
    if (!username) return;

    $.ajax({
        url: 'includes/handlers.php',
        type: 'POST',
        data: {
            action: 'share',
            id: id,
            username: username
        },
        success: function(response) {
            if (response.success) {
                alert('File shared successfully!');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('An error occurred while sharing');
        }
    });
}

// Document ready function
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle file input change
    $('#file').on('change', function() {
        const file = this.files[0];
        if (file) {
            const fileSize = formatFileSize(file.size);
            $('#fileSize').text(fileSize);
        }
    });
});
