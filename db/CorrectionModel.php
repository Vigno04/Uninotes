<?php
require_once 'db.php';

class CorrectionModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    /**
     * Create a new correction report
     */
    public function createCorrection(int $reportedBy, int $noteId, ?int $fileId, string $message): int|false {
        $sql = "INSERT INTO correction (reported_by, note_id, file_id, message) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute([$reportedBy, $noteId, $fileId, $message])) {
            return (int)$this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Mark correction as resolved
     */
    public function resolveCorrection(int $correctionId, int $noteId): bool {
        $sql = "UPDATE correction SET resolved = 1, resolved_at = NOW() WHERE id = ? AND note_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$correctionId, $noteId]);
    }

    /**
     * Get all corrections with details
     */
    public function getAllCorrections(bool $unresolvedOnly = false): array {
        $where = $unresolvedOnly ? "WHERE c.resolved = 0" : "";
        $sql = "SELECT c.id, c.message, c.file_id, c.created_at, c.resolved, c.resolved_at,
                       n.id AS note_id, n.title AS note_title,
                       f.filename,
                       p_reporter.name AS reporter_name, p_reporter.surname AS reporter_surname,
                       p.name, p.surname
                FROM correction c
                JOIN note n ON c.note_id = n.id
                LEFT JOIN file f ON c.file_id = f.id
                JOIN person p_reporter ON c.reported_by = p_reporter.id
                LEFT JOIN user u ON c.reported_by = u.person_id
                LEFT JOIN person p ON p.id = u.person_id
                {$where}
                ORDER BY c.resolved ASC, c.created_at DESC
                LIMIT 20";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get unresolved correction count
     */
    public function getUnresolvedCount(): int {
        $sql = "SELECT COUNT(*) FROM correction WHERE resolved = 0";
        $stmt = $this->pdo->query($sql);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get corrections for a note
     */
    public function getCorrectionsByNote(int $noteId): array {
        $sql = "SELECT c.id, c.message, c.file_id, c.resolved, c.created_at, 
                       f.filename, p.name, p.surname
                FROM correction c
                LEFT JOIN file f ON c.file_id = f.id
                LEFT JOIN user u ON c.reported_by = u.person_id
                LEFT JOIN person p ON u.person_id = p.id
                WHERE c.note_id = ?
                ORDER BY c.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$noteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
