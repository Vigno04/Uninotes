<?php
require_once "bootstrap.php";
require_once "db/NoteModel.php";

header('Content-Type: application/json');

if (!isset($_SESSION["person_id"])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$offeringId = isset($_GET['offering_id']) && is_numeric($_GET['offering_id']) ? (int)$_GET['offering_id'] : null;

if (!$offeringId) {
    echo json_encode(['error' => 'Invalid offering ID']);
    exit();
}

$userId = (int)$_SESSION["person_id"];
$noteModel = new NoteModel();

// Check if user follows this offering
$userOfferings = $noteModel->getUserFollowedOfferings($userId);
$followedIds = array_column($userOfferings, 'id');

if (!in_array($offeringId, $followedIds)) {
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

$topics = $noteModel->getTopicsByOfferingId($offeringId);

echo json_encode($topics);
?>