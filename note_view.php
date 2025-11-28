<?php
// note_view.php - Controller for note viewing

// If someone opens note_view.php directly, redirect to router
if (basename($_SERVER['SCRIPT_NAME']) === 'note_view.php') {
    header('Location: index.php?page=note_view');
    exit;
}

require_once("bootstrap.php");
require_once 'db/NoteModel.php';
require_once 'utils/MarkdownRenderer.php';

$id = $_GET['id'] ?? null;

if ($id !== null && is_numeric($id)) {
    $model = new NoteModel();
    $note = $model->getById((int)$id);
    if ($note) {
        $files = $model->getFilesByNoteId($note['id']);
        
        // Build filename => file details map
        $fileMap = [];
        foreach ($files as $file) {
            $fileMap[$file['filename']] = [
                'id' => $file['id'],
                'mime' => $file['mime_type']
            ];
        }
        
        // Render Markdown to HTML using MarkdownRenderer
        $renderer = new MarkdownRenderer();
        $htmlContent = $renderer->renderWithFiles($note['content'], $fileMap);
        
        // Add file list at the end if there are files
        $htmlContent .= MarkdownRenderer::generateFileList($files);
        
        // Fetch corrections
        $corrections = [];
        $sql = "SELECT c.id, c.message, c.file_id, c.resolved, c.created_at, f.filename, p.name, p.surname
                FROM correction c
                LEFT JOIN file f ON c.file_id = f.id
                LEFT JOIN user u ON c.reported_by = u.person_id
                LEFT JOIN person p ON u.person_id = p.id
                WHERE c.note_id = ?
                ORDER BY c.created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $note['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $corrections[] = $row;
        }
        mysqli_stmt_close($stmt);
        
        // Handle POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['submit_correction']) && isset($_SESSION['person_id'])) {
                $message = trim($_POST['message']);
                $file_id = !empty($_POST['file_id']) ? (int)$_POST['file_id'] : null;
                if (!empty($message)) {
                    $stmt = mysqli_prepare($conn, "INSERT INTO correction (reported_by, note_id, file_id, message) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, "iiis", $_SESSION['person_id'], $note['id'], $file_id, $message);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            } elseif (isset($_POST['resolve_correction']) && isset($_SESSION['person_id']) && $_SESSION['person_id'] == $note['owner_id']) {
                $correction_id = (int)$_POST['correction_id'];
                $stmt = mysqli_prepare($conn, "UPDATE correction SET resolved = 1, resolved_at = NOW() WHERE id = ? AND note_id = ?");
                mysqli_stmt_bind_param($stmt, "ii", $correction_id, $note['id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
        
        $templateParams = ["title" => "Nota"];
    } else {
        $content = "<div class='container mt-5'><h1>Nota non trovata</h1></div>";
        $templateParams = ["title" => "Nota non trovata", "main-content" => $content];
    }
} else {
    $content = "<div class='container mt-5'><h1>ID non valido</h1></div>";
    $templateParams = ["title" => "ID non valido", "main-content" => $content];
}
?>