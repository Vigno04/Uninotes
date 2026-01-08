<?php
require_once 'db.php';

class PersonModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    /**
     * Get person by ID
     */
    public function getPersonById(int $personId): array|false {
        $sql = "SELECT p.*, u.role FROM person p 
                LEFT JOIN user u ON p.id = u.person_id 
                WHERE p.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update profile picture
     */
    public function updateProfilePicture(int $personId, string $filename): bool {
        $sql = "UPDATE person SET profile_picture = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$filename, $personId]);
    }

    /**
     * Update person information
     */
    public function updatePerson(int $personId, string $name, string $surname, string $email): bool {
        $sql = "UPDATE person SET name = ?, surname = ?, email = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$name, $surname, $email, $personId]);
    }

    /**
     * Get upload statistics for a user
     */
    public function getUploadStats(int $personId): array {
        $sql = "SELECT COUNT(DISTINCT n.id) AS note_count,
                       COUNT(DISTINCT f.id) AS file_count
                FROM note n
                LEFT JOIN file f ON f.note_id = n.id
                WHERE n.uploaded_by = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['note_count' => 0, 'file_count' => 0];
    }

    /**
     * Get upvote statistics for a user
     */
    public function getUpvoteStats(int $personId): array {
        $sql = "SELECT COUNT(*) AS upvote_count
                FROM vote v
                JOIN note n ON v.note_id = n.id
                WHERE n.uploaded_by = ? AND v.vote = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['upvote_count' => 0];
    }

    /**
     * Get person with user data
     */
    public function getPersonWithUserData(int $personId): array|false {
        $sql = "SELECT 
                    p.name,
                    p.surname,
                    p.email,
                    p.profile_picture,
                    u.created_at,
                    u.programme,
                    u.bio,
                    u.role,
                    u.last_login
                FROM person p
                JOIN user u ON p.id = u.person_id
                WHERE p.id = ?
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
