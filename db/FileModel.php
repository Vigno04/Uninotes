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

    /**
     * Insert a new file record into the database.
     */
    public function createFile(int $noteId, string $filename, string $storagePath, string $fileType, int $fileSize, string $mimeType, int $uploadedBy): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO file (note_id, filename, storage_path, file_type, file_size, mime_type, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$noteId, $filename, $storagePath, $fileType, $fileSize, $mimeType, $uploadedBy]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Delete a file record by ID and return the storage path for physical deletion.
     */
    public function deleteFile(int $fileId): ?string {
        $stmt = $this->pdo->prepare("SELECT storage_path FROM file WHERE id = ?");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();
        
        if ($file) {
            $stmt = $this->pdo->prepare("DELETE FROM file WHERE id = ?");
            $stmt->execute([$fileId]);
            return $file['storage_path'];
        }
        return null;
    }

    /**
     * Get all files for a note.
     */
    public function getFilesByNoteId(int $noteId): array {
        $stmt = $this->pdo->prepare("SELECT id, filename, storage_path, file_type, file_size, mime_type FROM file WHERE note_id = ?");
        $stmt->execute([$noteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>