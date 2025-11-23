<?php
require_once 'db.php';

class FileModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function getFileById(int $fileId) {
        $stmt = $this->pdo->prepare("
            SELECT f.storage_path, f.mime_type, f.filename, n.owner_id, n.status 
            FROM file f 
            JOIN note n ON f.note_id = n.id 
            WHERE f.id = ?
        ");
        $stmt->execute([$fileId]);
        return $stmt->fetch();
    }
}
?>