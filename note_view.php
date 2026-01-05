<?php
// note_view.php - Controller for note viewing

// If someone opens note_view.php directly, redirect to router
if (basename($_SERVER['SCRIPT_NAME']) === 'note_view.php') {
    header('Location: index.php?page=note_view');
    exit;
}

require_once("bootstrap.php");
require_once 'utils/MarkdownRenderer.php';

$id = $_GET['id'] ?? null;

if ($id !== null && is_numeric($id)) {
    $noteModel = new NoteModel();
    $correctionModel = new CorrectionModel();
    
    $note = $noteModel->getById((int)$id);
    if ($note) {
        // Check if the note is a draft and the user is not the owner
        $isOwner = isset($_SESSION['person_id']) && $_SESSION['person_id'] == $note['owner_id'];
        
        if ($note['status'] === 'draft' && !$isOwner) {
            $content = "<div class='container mt-5'><div class='alert alert-warning'><h4>Draft Note</h4><p>This note is still a draft and is not publicly available.</p></div></div>";
            $templateParams = ["title" => "Draft Note", "main-content" => $content];
        } else {
            $files = $noteModel->getFilesByNoteId($note['id']);
        
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
        $corrections = $correctionModel->getCorrectionsByNote($note['id']);
        
        // Handle POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['submit_correction']) && isset($_SESSION['person_id'])) {
                $message = trim($_POST['message']);
                $file_id = !empty($_POST['file_id']) ? (int)$_POST['file_id'] : null;
                if (!empty($message)) {
                    $correctionModel->createCorrection($_SESSION['person_id'], $note['id'], $file_id, $message);
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            } elseif (isset($_POST['resolve_correction']) && isset($_SESSION['person_id']) && $_SESSION['person_id'] == $note['owner_id']) {
                $correction_id = (int)$_POST['correction_id'];
                $correctionModel->resolveCorrection($correction_id, $note['id']);
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
        
        $templateParams = ["title" => "Nota"];
        }
    } else {
        $content = "<div class='container mt-5'><h1>Nota non trovata</h1></div>";
        $templateParams = ["title" => "Nota non trovata", "main-content" => $content];
    }
} else {
    $content = "<div class='container mt-5'><h1>ID non valido</h1></div>";
    $templateParams = ["title" => "ID non valido", "main-content" => $content];
}
?>