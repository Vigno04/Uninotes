<?php
session_start();

// Include PDO Database singleton
require_once __DIR__ . '/db/db.php';

// Include all models
require_once __DIR__ . '/db/UserModel.php';
require_once __DIR__ . '/db/NoteModel.php';
require_once __DIR__ . '/db/FileModel.php';
require_once __DIR__ . '/db/TeacherModel.php';
require_once __DIR__ . '/db/CourseModel.php';
require_once __DIR__ . '/db/VoteModel.php';
require_once __DIR__ . '/db/CorrectionModel.php';
require_once __DIR__ . '/db/AdminModel.php';
require_once __DIR__ . '/db/PersonModel.php';

define("UPLOAD_DIR", "./uploads/");

// CSRF Protection Functions
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getCSRFField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>