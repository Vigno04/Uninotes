<?php
require_once("bootstrap.php");
require_once 'db/NoteModel.php';
require_once 'vendor/parsedown/Parsedown.php';

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
        
        // Render Markdown to HTML
        $Parsedown = new Parsedown();
        $htmlContent = $Parsedown->text($note['content']);
        
        // Replace filenames with secure URLs using DOM manipulation
        if (!empty($fileMap)) {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true); // Suppress warnings
            $dom->loadHTML('<?xml encoding="UTF-8">' . $htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
            
            // Handle images
            $images = $dom->getElementsByTagName('img');
            foreach ($images as $img) {
                $src = $img->getAttribute('src');
                if (isset($fileMap[$src])) {
                    $img->setAttribute('src', 'file.php?id=' . $fileMap[$src]['id']);
                }
            }
            
            // Handle links (for downloadable files)
            $links = $dom->getElementsByTagName('a');
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                if (isset($fileMap[$href])) {
                    $link->setAttribute('href', 'file.php?id=' . $fileMap[$href]['id']);
                    // Add download attribute for non-images
                    if (!str_starts_with($fileMap[$href]['mime'] ?? '', 'image/')) {
                        $link->setAttribute('download', '');
                    }
                }
            }
            
            $htmlContent = $dom->saveHTML();
        }
        
        // Add file list at the end if there are files
        if (!empty($files)) {
            $fileList = '<div class="attached-files mt-4"><h3>Attached Files</h3><ul>';
            foreach ($files as $file) {
                $url = 'file.php?id=' . $file['id'];
                $fileList .= '<li><a href="' . htmlspecialchars($url) . '" download>' . htmlspecialchars($file['filename']) . '</a></li>';
            }
            $fileList .= '</ul></div>';
            $htmlContent .= $fileList;
        }
        
        ob_start();
        include("template/note.php");
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