<?php
require_once("bootstrap.php");
require_once 'db/FileModel.php';

$fileId = $_GET['id'] ?? null;

if (!$fileId || !is_numeric($fileId)) {
    http_response_code(404);
    exit("File not found");
}

$fileModel = new FileModel();
$file = $fileModel->getFileById($fileId);

if (!$file) {
    http_response_code(404);
    exit("File not found");
}

// Permission check: Allow if note is published OR user is the owner
$userId = $_SESSION['user_id'] ?? null;
if ($file['status'] !== 'published' && $file['owner_id'] != $userId) {
    http_response_code(403);
    exit("Access denied");
}

// Serve the file
$filePath = $file['storage_path'];
if (!file_exists($filePath)) {
    http_response_code(404);
    exit("File not found on server");
}

// Set appropriate headers
$mimeType = $file['mime_type'] ?? mime_content_type($filePath);
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . basename($file['filename']) . '"');
header('Content-Length: ' . filesize($filePath));

// Output file
readfile($filePath);
exit();
?>
