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
        $stmt = $this->pdo->prepare("SELECT co.id, c.id AS course_id, c.name, co.year, co.semester
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

    /**
     * Count notes by owner
     */
    public function countNotesByOwner(int $ownerId): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS c FROM note WHERE owner_id = ?");
        $stmt->execute([$ownerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    /**
     * Get notes uploaded by user (including status)
     */
    public function getNotesUploadedByUser(int $personId, ?string $statusFilter = null): array {
        $sql = "SELECT
                    n.id,
                    n.title,
                    n.status,
                    COALESCE(n.published_at, n.created_at) AS note_date,
                    c.name AS course_name,
                    n.vote_count
                FROM note n
                JOIN topic t ON n.topic_id = t.id
                JOIN course_offering co ON t.offering_id = co.id
                JOIN course c ON co.course_id = c.id
                WHERE n.owner_id = ?";
        
        $params = [$personId];
        
        if ($statusFilter !== null) {
            $sql .= " AND n.status = ?";
            $params[] = $statusFilter;
        }
        
        $sql .= " ORDER BY note_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get draft notes by user
     */
    public function getDraftNotesByUser(int $personId): array {
        return $this->getNotesUploadedByUser($personId, 'draft');
    }

    /**
     * Get published notes by user
     */
    public function getPublishedNotesByUser(int $personId): array {
        return $this->getNotesUploadedByUser($personId, 'published');
    }

    /**
     * Get filtered notes with search and filters
     */
    public function getFilteredNotes(
        ?int $offeringFilter = null,
        ?int $userId = null, 
        bool $filterMyCourses = false,
        string $searchTerm = '',
        string $sort = 'date',
        int $limit = 10
    ): array {
        $conditions = ["n.status = 'published'"];
        $params = [];
        
        if (!is_null($offeringFilter)) {
            $conditions[] = "co.id = ?";
            $params[] = $offeringFilter;
        }
        
        if ($filterMyCourses && $userId !== null) {
            $conditions[] = "EXISTS (
                SELECT 1
                FROM course_offering_follow cof
                WHERE cof.offering_id = co.id
                  AND cof.user_id = ?
            )";
            $params[] = $userId;
        }
        
        if ($searchTerm !== '') {
            $conditions[] = "(n.title LIKE ? OR n.content LIKE ?)";
            $like = '%' . $searchTerm . '%';
            $params[] = $like;
            $params[] = $like;
        }
        
        $whereClause = "WHERE " . implode(" AND ", $conditions);
        
        $orderBy = "note_date DESC";
        if ($sort === 'votes') {
            $orderBy = "n.vote_count DESC, note_date DESC";
        }
        
        // Ensure limit is an integer for safe SQL insertion
        $limitInt = (int)$limit;
        
        $sql = "SELECT
                    n.id,
                    n.title,
                    n.vote_count,
                    COALESCE(n.published_at, n.created_at) AS note_date,
                    c.name AS course_name,
                    t.name AS topic_name,
                    CONCAT(pt.name, ' ', pt.surname) AS teacher_name,
                    CONCAT(po.name, ' ', po.surname) AS author_name
                FROM note n
                JOIN topic t ON n.topic_id = t.id
                JOIN course_offering co ON t.offering_id = co.id
                JOIN course c ON co.course_id = c.id
                LEFT JOIN course_offering_teacher cot ON co.id = cot.offering_id
                LEFT JOIN teacher th ON cot.teacher_id = th.person_id
                LEFT JOIN person pt ON th.person_id = pt.id
                LEFT JOIN user uo ON n.owner_id = uo.person_id
                LEFT JOIN person po ON uo.person_id = po.id
                $whereClause
                GROUP BY n.id
                ORDER BY $orderBy
                LIMIT $limitInt";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count filtered notes
     */
    public function countFilteredNotes(
        ?int $offeringFilter = null,
        ?int $userId = null,
        bool $filterMyCourses = false,
        string $searchTerm = ''
    ): int {
        $conditions = ["n.status = 'published'"];
        $params = [];
        
        if (!is_null($offeringFilter)) {
            $conditions[] = "co.id = ?";
            $params[] = $offeringFilter;
        }
        
        if ($filterMyCourses && $userId !== null) {
            $conditions[] = "EXISTS (
                SELECT 1
                FROM course_offering_follow cof
                WHERE cof.offering_id = co.id
                  AND cof.user_id = ?
            )";
            $params[] = $userId;
        }
        
        if ($searchTerm !== '') {
            $conditions[] = "(n.title LIKE ? OR n.content LIKE ?)";
            $like = '%' . $searchTerm . '%';
            $params[] = $like;
            $params[] = $like;
        }
        
        $whereClause = "WHERE " . implode(" AND ", $conditions);
        
        $sql = "SELECT COUNT(DISTINCT n.id) AS total
                FROM note n
                JOIN topic t ON n.topic_id = t.id
                JOIN course_offering co ON t.offering_id = co.id
                JOIN course c ON co.course_id = c.id
                $whereClause";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Get offering info by offering ID
     */
    public function getOfferingInfo(int $offeringId): array|false {
        $sql = "SELECT 
                    c.name AS course_name,
                    co.year,
                    co.semester
                FROM course_offering co
                JOIN course c ON co.course_id = c.id
                WHERE co.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$offeringId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all topics
     */
    public function getAllTopics(): array {
        $sql = "SELECT DISTINCT t.id, t.name FROM topic t ORDER BY t.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>