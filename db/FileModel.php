<?php
require_once __DIR__ . '/connection.php';

class FileModel {
    private mysqli $conn;

    public function __construct() {
        $this->conn = db();
    }

    public function getFileById(int $fileId): array|null {
        $sql = "
            SELECT f.storage_path, f.mime_type, f.filename, n.owner_id, n.status 
            FROM file f 
            JOIN note n ON f.note_id = n.id 
            WHERE f.id = ?
        ";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $fileId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    /**
     * Insert a new file record into the database.
     */
    public function createFile(
        int $noteId,
        string $filename,
        string $storagePath,
        string $fileType,
        int $fileSize,
        string $mimeType,
        int $uploadedBy
    ): int {
        $sql = "
            INSERT INTO file (note_id, filename, storage_path, file_type, file_size, mime_type, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssisi", $noteId, $filename, $storagePath, $fileType, $fileSize, $mimeType, $uploadedBy);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (int)mysqli_insert_id($this->conn);
    }

    /**
     * Delete a file record by ID and return the storage path for physical deletion.
     */
    public function deleteFile(int $fileId): ?string {
        // 1) leggo il path
        $stmt = mysqli_prepare($this->conn, "SELECT storage_path FROM file WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $fileId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $file = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$file) {
            return null;
        }

        // 2) cancello il record
        $stmt = mysqli_prepare($this->conn, "DELETE FROM file WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $fileId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $file['storage_path'] ?? null;
    }

    /**
     * Get all files for a note.
     */
    public function getFilesByNoteId(int $noteId): array {
        $stmt = mysqli_prepare($this->conn, "SELECT id, filename, storage_path, file_type, file_size, mime_type FROM file WHERE note_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $noteId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
        mysqli_stmt_close($stmt);
        return $rows;
    }
}
?>
