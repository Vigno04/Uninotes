<?php
require_once 'db.php';

class VoteModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    /**
     * Get user's vote for a note
     */
    public function getUserVote(int $noteId, int $userId): int|null {
        $sql = "SELECT vote FROM vote WHERE note_id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$noteId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['vote'] : null;
    }

    /**
     * Add or update a vote
     */
    public function vote(int $noteId, int $userId, int $voteValue): bool {
        $sql = "INSERT INTO vote (note_id, user_id, vote) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE vote = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$noteId, $userId, $voteValue, $voteValue]);
    }

    /**
     * Remove a vote
     */
    public function removeVote(int $noteId, int $userId): bool {
        $sql = "DELETE FROM vote WHERE note_id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$noteId, $userId]);
    }

    /**
     * Get total upvotes for a note
     */
    public function getUpvoteCount(int $noteId): int {
        $sql = "SELECT COUNT(*) FROM vote WHERE note_id = ? AND vote = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$noteId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get total downvotes for a note
     */
    public function getDownvoteCount(int $noteId): int {
        $sql = "SELECT COUNT(*) FROM vote WHERE note_id = ? AND vote = -1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$noteId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get net score for a note
     */
    public function getScore(int $noteId): int {
        $sql = "SELECT COALESCE(SUM(vote), 0) FROM vote WHERE note_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$noteId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Count upvoted notes by user
     */
    public function countUpvotedNotesByUser(int $userId): int {
        $sql = "SELECT COUNT(DISTINCT note_id) AS c FROM vote WHERE user_id = ? AND vote = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    /**
     * Get notes upvoted by user
     */
    public function getNotesUpvotedByUser(int $personId): array {
        $sql = "SELECT
                    n.id,
                    n.title,
                    COALESCE(n.published_at, n.created_at) AS note_date,
                    c.name AS course_name,
                    n.vote_count
                FROM vote v
                JOIN note n ON v.note_id = n.id
                JOIN topic t ON n.topic_id = t.id
                JOIN course_offering co ON t.offering_id = co.id
                JOIN course c ON co.course_id = c.id
                WHERE v.user_id = ?
                  AND v.vote = 1
                GROUP BY n.id
                ORDER BY note_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
