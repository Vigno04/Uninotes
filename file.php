<?php
require_once("bootstrap.php");
require_once 'db/db.php';

$fileId = $_GET['id'] ?? null;

if (!$fileId || !is_numeric($fileId)) {
    http_response_code(404);
    exit("File not found");
}

$pdo = Database::getInstance();

// Fetch file details with note status and owner
$stmt = $pdo->prepare("
    SELECT f.storage_path, f.mime_type, f.filename, n.owner_id, n.status 
    FROM file f 
    JOIN note n ON f.note_id = n.id 
    WHERE f.id = ?
");
$stmt->execute([$fileId]);
$file = $stmt->fetch();

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
