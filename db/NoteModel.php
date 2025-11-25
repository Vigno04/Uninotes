<?php
require_once 'db.php';
class NoteModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function getById(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM note WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getFilesByNoteId(int $noteId) {
        $stmt = $this->pdo->prepare("SELECT id, filename, mime_type FROM file WHERE note_id = ?");
        $stmt->execute([$noteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserCoursesList(int $userId) {
        $stmt = $this->pdo->prepare("SELECT c.name, co.year 
                                    FROM course_offering_follow cof
                                    INNER JOIN course_offering co ON cof.offering_id = co.id
                                    INNER JOIN course c ON co.course_id = c.id
                                    WHERE cof.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>