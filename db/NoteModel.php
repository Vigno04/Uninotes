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
}
?>