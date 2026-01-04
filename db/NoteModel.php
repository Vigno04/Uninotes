<?php
require_once __DIR__ . '/connection.php';

class NoteModel {
    private mysqli $conn;

    public function __construct() {
        $this->conn = db();
    }

    public function getById(int $id): array|null {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM note WHERE id = ? AND deleted_at IS NULL");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function getFilesByNoteId(int $noteId): array {
        $stmt = mysqli_prepare($this->conn, "SELECT id, filename, mime_type FROM file WHERE note_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $noteId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
        mysqli_stmt_close($stmt);
        return $rows;
    }

    public function getUserFollowedOfferings(int $userId): array {
        $sql = "SELECT co.id, c.name, co.year, co.semester
                FROM course_offering_follow cof
                INNER JOIN course_offering co ON cof.offering_id = co.id
                INNER JOIN course c ON co.course_id = c.id
                WHERE cof.user_id = ?
                ORDER BY c.name, co.year DESC, co.semester";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
        mysqli_stmt_close($stmt);
        return $rows;
    }

    public function getOfferingByTopicId(int $topicId): array|null {
        $sql = "SELECT co.id, c.name, co.year, co.semester
                FROM topic t
                INNER JOIN course_offering co ON t.offering_id = co.id
                INNER JOIN course c ON co.course_id = c.id
                WHERE t.id = ?";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $topicId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function getTopicsByOfferingId(int $offeringId): array {
        $stmt = mysqli_prepare($this->conn, "SELECT id, name FROM topic WHERE offering_id = ? ORDER BY order_index, name");
        mysqli_stmt_bind_param($stmt, "i", $offeringId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
        mysqli_stmt_close($stmt);
        return $rows;
    }

    /**
     * Create a new note.
     */
    public function create(int $ownerId, int $topicId, string $title, string $content, string $status = 'draft'): int {
        $sql = "INSERT INTO note (owner_id, topic_id, title, content, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iisss", $ownerId, $topicId, $title, $content, $status);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (int)mysqli_insert_id($this->conn);
    }

    /**
     * Update an existing note.
     */
    public function update(int $noteId, int $topicId, string $title, string $content): bool {
        $sql = "UPDATE note
                SET topic_id = ?, title = ?, content = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND deleted_at IS NULL";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "issi", $topicId, $title, $content, $noteId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (bool)$ok;
    }

    /**
     * Publish a note (change status from draft to published).
     */
    public function publish(int $noteId): bool {
        $sql = "UPDATE note
                SET status = 'published', published_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND deleted_at IS NULL";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $noteId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (bool)$ok;
    }

    /**
     * Unpublish a note (change status back to draft).
     */
    public function unpublish(int $noteId): bool {
        $sql = "UPDATE note
                SET status = 'draft', updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND deleted_at IS NULL";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $noteId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (bool)$ok;
    }
}
?>
