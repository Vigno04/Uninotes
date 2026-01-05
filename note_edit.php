<?php
// note_edit.php - Controller for note editing

// If someone opens note_edit.php directly, redirect to router
if (basename($_SERVER['SCRIPT_NAME']) === 'note_edit.php') {
    header('Location: index.php?page=note_edit');
    exit;
}

require_once "db/NoteModel.php";
require_once "db/FileModel.php";
require_once "utils/MarkdownRenderer.php";

// Check if user is logged in
if (!isset($_SESSION["person_id"])) {
    header("Location: login.php");
    exit();
}

$userId = (int)$_SESSION["person_id"];
$noteModel = new NoteModel();
$fileModel = new FileModel();

// Detect if we are editing an existing note via GET parameter
$noteId   = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$mode     = $noteId ? 'edit' : 'create';

// Messages for user feedback
$successMessage = '';
$errorMessage = '';

// Placeholder values for now (no DB)
$selectedCourse        = '';
$selectedCourseOffering = '';
$selectedTopic         = '';
$titleValue            = '';
$contentValue          = '';
$existingFiles         = [];
$noteStatus            = 'draft';
$selectedTopicCourseId = null; // Per preservare il course_id del topic selezionato

$courseOfferings = [];
$userOfferings = $noteModel->getUserFollowedOfferings($userId);
foreach ($userOfferings as $offering) {
    $label = $offering['name'] . ' - Academic Year ' . $offering['year'] . '/' . ($offering['year'] + 1) . ' - ' . ($offering['semester'] == 1 ? 'First' : 'Second') . ' semester';
    $courseOfferings[$offering['id']] = $label;
}

$topics = [];
if ($selectedCourseOffering) {
    $topicsData = $noteModel->getTopicsByOfferingId($selectedCourseOffering);
    foreach ($topicsData as $topic) {
        $topics[$topic['id']] = $topic['name'];
    }
}

// If editing, fetch note data
if ($mode === 'edit') {
    $note = $noteModel->getById($noteId);
    if (!$note || $note['owner_id'] != $userId) {
        // Cannot edit: note not found or not owned, fall back to create mode
        $mode = 'create';
        $noteId = null;
        $selectedCourseOffering = '';
        $selectedTopic = '';
        $titleValue = '';
        $contentValue = '';
        $topics = [];
    } else {
        $titleValue = $note['title'];
        $contentValue = $note['content'];
        $selectedTopic = $note['topic_id'];
        $noteStatus = $note['status'] ?? 'draft';
        // Get offering from topic
        $offering = $noteModel->getOfferingByTopicId($selectedTopic);
        if ($offering) {
            $selectedCourseOffering = $offering['id'];
            $selectedTopicCourseId = $offering['course_id'] ?? null; // Usa il course_id dall'offering
            // Load topics for this offering
            $topicsData = $noteModel->getTopicsByOfferingId($selectedCourseOffering);
            foreach ($topicsData as $topic) {
                $topics[$topic['id']] = $topic['name'];
            }
        }
        // Load existing files for this note
        $existingFiles = $fileModel->getFilesByNoteId($noteId);
    }
}

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file_id'])) {
    $deleteFileId = (int)$_POST['delete_file_id'];
    $deleteNoteId = isset($_POST['note_id']) && is_numeric($_POST['note_id']) ? (int)$_POST['note_id'] : null;
    
    if ($deleteNoteId && $deleteFileId) {
        // Verify the note belongs to the user
        $note = $noteModel->getById($deleteNoteId);
        if ($note && $note['owner_id'] == $userId) {
            // Get the file to verify it belongs to this note
            $fileToDelete = $fileModel->getFileById($deleteFileId);
            if ($fileToDelete && $fileToDelete['owner_id'] == $userId) {
                $storagePath = $fileModel->deleteFile($deleteFileId);
                if ($storagePath && file_exists($storagePath)) {
                    unlink($storagePath);
                }
                $successMessage = 'File deleted successfully!';
                
                // Refresh existing files list
                $existingFiles = $fileModel->getFilesByNoteId($deleteNoteId);
            } else {
                $errorMessage = 'You do not have permission to delete this file.';
            }
        } else {
            $errorMessage = 'You do not have permission to delete this file.';
        }
    }
    
    // Reload note data after file deletion
    if ($noteId) {
        $note = $noteModel->getById($noteId);
        if ($note) {
            $titleValue = $note['title'];
            $contentValue = $note['content'];
            $selectedTopic = $note['topic_id'];
            $offering = $noteModel->getOfferingByTopicId($selectedTopic);
            if ($offering) {
                $selectedCourseOffering = $offering['id'];
                $selectedTopicCourseId = $offering['course_id'] ?? null; // Usa il course_id dall'offering
                $topicsData = $noteModel->getTopicsByOfferingId($selectedCourseOffering);
                $topics = [];
                foreach ($topicsData as $topic) {
                    $topics[$topic['id']] = $topic['name'];
                }
            }
        }
        $existingFiles = $fileModel->getFilesByNoteId($noteId);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_file_id'])) {
    $postNoteId = isset($_POST['note_id']) && is_numeric($_POST['note_id']) ? (int)$_POST['note_id'] : null;
    $postTopicId = isset($_POST['topic']) && is_numeric($_POST['topic']) ? (int)$_POST['topic'] : null;
    $postTitle = trim($_POST['title'] ?? '');
    $postContent = $_POST['content'] ?? '';
    $action = $_POST['action'] ?? 'save'; // 'save' or 'publish'

    // Validate required fields
    if (!$postTopicId || empty($postTitle) || empty($postContent)) {
        $errorMessage = 'Please fill in all required fields.';
    } else {
        try {
            if ($postNoteId) {
                // Update existing note
                $existingNote = $noteModel->getById($postNoteId);
                if ($existingNote && $existingNote['owner_id'] == $userId) {
                    $noteModel->update($postNoteId, $postTopicId, $postTitle, $postContent);
                    $noteId = $postNoteId;
                    
                    // Handle publish action
                    if ($action === 'publish') {
                        $noteModel->publish($postNoteId);
                        $noteStatus = 'published';
                        $successMessage = 'Note published successfully!';
                    } else {
                        $successMessage = 'Draft saved successfully!';
                    }
                } else {
                    $errorMessage = 'You do not have permission to edit this note.';
                }
            } else {
                // Create new note (always as draft first)
                $noteId = $noteModel->create($userId, $postTopicId, $postTitle, $postContent, 'draft');
                $mode = 'edit';
                
                // Handle publish action for new note
                if ($action === 'publish') {
                    $noteModel->publish($noteId);
                    $noteStatus = 'published';
                    $successMessage = 'Note published successfully!';
                } else {
                    $successMessage = 'Draft saved successfully!';
                }
            }

            // Handle file uploads
            if ($noteId && isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $uploadDir = __DIR__ . '/uploads/user-' . $userId . '/note-' . $noteId . '/';
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $files = $_FILES['attachments'];
                $uploadedCount = 0;
                $failedUploads = [];

                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $originalName = basename($files['name'][$i]);
                        $fileType = pathinfo($originalName, PATHINFO_EXTENSION);
                        $mimeType = $files['type'][$i];
                        $fileSize = $files['size'][$i];
                        
                        // Generate safe filename (keep original name but sanitize)
                        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                        $targetPath = $uploadDir . $safeName;
                        
                        // Handle duplicate filenames
                        $counter = 1;
                        while (file_exists($targetPath)) {
                            $nameWithoutExt = pathinfo($safeName, PATHINFO_FILENAME);
                            $ext = pathinfo($safeName, PATHINFO_EXTENSION);
                            $targetPath = $uploadDir . $nameWithoutExt . '_' . $counter . '.' . $ext;
                            $safeName = $nameWithoutExt . '_' . $counter . '.' . $ext;
                            $counter++;
                        }

                        if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                            // Store relative path in database
                            $storagePath = 'uploads/user-' . $userId . '/note-' . $noteId . '/' . $safeName;
                            $fileModel->createFile($noteId, $safeName, $storagePath, $fileType, $fileSize, $mimeType, $userId);
                            $uploadedCount++;
                        } else {
                            $failedUploads[] = $originalName;
                        }
                    } elseif ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $failedUploads[] = $files['name'][$i];
                    }
                }

                if ($uploadedCount > 0) {
                    $successMessage .= " $uploadedCount file(s) uploaded.";
                }
                if (!empty($failedUploads)) {
                    $errorMessage .= ' Failed to upload: ' . implode(', ', $failedUploads);
                }
            }

            // Refresh data after save
            if ($noteId) {
                $note = $noteModel->getById($noteId);
                if ($note) {
                    $titleValue = $note['title'];
                    $contentValue = $note['content'];
                    $selectedTopic = $note['topic_id'];
                    $noteStatus = $note['status'] ?? 'draft';
                    $offering = $noteModel->getOfferingByTopicId($selectedTopic);
                    if ($offering) {
                        $selectedCourseOffering = $offering['id'];
                        $selectedTopicCourseId = $offering['course_id'] ?? null; // Usa il course_id dall'offering
                        $topicsData = $noteModel->getTopicsByOfferingId($selectedCourseOffering);
                        $topics = [];
                        foreach ($topicsData as $topic) {
                            $topics[$topic['id']] = $topic['name'];
                        }
                    }
                }
                $existingFiles = $fileModel->getFilesByNoteId($noteId);
            }

        } catch (Exception $e) {
            $errorMessage = 'An error occurred: ' . $e->getMessage();
        }
    }
}

// Generate preview HTML if we have content (after save or when editing existing note)
$previewHtml = '';
$showPreview = false;
if (!empty($contentValue)) {
    $renderer = new MarkdownRenderer();
    
    // Build file map for the renderer
    $fileMap = [];
    foreach ($existingFiles as $file) {
        $fileMap[$file['filename']] = [
            'id' => $file['id'],
            'mime' => $file['mime_type'] ?? ''
        ];
    }
    
    $previewHtml = $renderer->renderWithFiles($contentValue, $fileMap);
    $showPreview = true;
}
