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

    public function getUserFollowedOfferings(int $userId) {
        $stmt = $this->pdo->prepare("SELECT co.id, c.name, co.year, co.semester
                                    FROM course_offering_follow cof
                                    INNER JOIN course_offering co ON cof.offering_id = co.id
                                    INNER JOIN course c ON co.course_id = c.id
                                    WHERE cof.user_id = ?
                                    ORDER BY c.name, co.year DESC, co.semester");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOfferingByTopicId(int $topicId) {
        $stmt = $this->pdo->prepare("SELECT co.id, c.name, co.year, co.semester
                                    FROM topic t
                                    INNER JOIN course_offering co ON t.offering_id = co.id
                                    INNER JOIN course c ON co.course_id = c.id
                                    WHERE t.id = ?");
        $stmt->execute([$topicId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTopicsByOfferingId(int $offeringId) {
        $stmt = $this->pdo->prepare("SELECT id, name FROM topic WHERE offering_id = ? ORDER BY order_index, name");
        $stmt->execute([$offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new note.
     */
    public function create(int $ownerId, int $topicId, string $title, string $content, string $status = 'draft'): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO note (owner_id, topic_id, title, content, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$ownerId, $topicId, $title, $content, $status]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update an existing note.
     */
    public function update(int $noteId, int $topicId, string $title, string $content): bool {
        $stmt = $this->pdo->prepare("
            UPDATE note SET topic_id = ?, title = ?, content = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$topicId, $title, $content, $noteId]);
    }
 
    /**
     * Publish a note (change status from draft to published).
     */
    public function publish(int $noteId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE note SET status = 'published', published_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$noteId]);
    }

    /**
     * Unpublish a note (change status back to draft).
     */
    public function unpublish(int $noteId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE note SET status = 'draft', updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$noteId]);
    }
}
?>