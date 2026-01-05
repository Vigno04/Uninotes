<?php
// my_drafts.php - View user's draft notes

// If someone opens my_drafts.php directly, redirect to router
if (basename($_SERVER['SCRIPT_NAME']) === 'my_drafts.php') {
    header('Location: index.php?page=my_drafts');
    exit;
}

require_once 'db/NoteModel.php';

if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$personId = (int)$_SESSION['person_id'];

$noteModel = new NoteModel();
$drafts = $noteModel->getDraftNotesByUser($personId);

$templateParams["title"] = "My Drafts - UniNotes";
$templateParams["main-content"] = "template/my_drafts.php";
require 'template/base.php';
?>
