<?php
require_once "bootstrap.php";
require_once "db/CourseModel.php";

header('Content-Type: application/json');

if (!isset($_SESSION["person_id"])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$courseId = isset($_GET['course_id']) && is_numeric($_GET['course_id']) ? (int)$_GET['course_id'] : null;

if (!$courseId) {
    echo json_encode(['error' => 'Invalid course ID']);
    exit();
}

$courseModel = new CourseModel();
$topics = $courseModel->getTopicsByCourseId($courseId);

echo json_encode($topics);
?>
