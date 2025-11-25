<?php
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
        
        ob_start();
        include("template/note_view.php");
        $content = ob_get_clean();
        
        $templateParams = ["title" => "Nota", "main-content" => $content];
    } else {
        $content = "<div class='container mt-5'><h1>Nota non trovata</h1></div>";
        $templateParams = ["title" => "Nota non trovata", "main-content" => $content];
    }
} else {
    $content = "<div class='container mt-5'><h1>ID non valido</h1></div>";
    $templateParams = ["title" => "ID non valido", "main-content" => $content];
}

require("template/base.php");
?>