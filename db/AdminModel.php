<?php
require_once 'db.php';

class AdminModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    /**
     * Get all users with details and filters
     */
    public function getAllUsers(?string $search = null, ?string $roleFilter = null, bool $showDeleted = false): array {
        $sql = "SELECT u.person_id, u.person_id AS id, p.name, p.surname, p.email, u.role, 
                       u.created_at, u.created_at AS user_created, u.last_login, u.deleted_at,
                       t.person_id AS teacher_person_id,
                       t.department AS teacher_department,
                       t.unibo_site AS teacher_unibo_site,
                       t.phone_number AS teacher_phone_number,
                       t.personal_site AS teacher_personal_site,
                       (SELECT COUNT(*) FROM note n WHERE n.owner_id = u.person_id AND n.deleted_at IS NULL) AS note_count
                FROM user u
                JOIN person p ON u.person_id = p.id
                LEFT JOIN teacher t ON t.person_id = u.person_id";
        
        $conditions = [];
        $params = [];
        
        if (!$showDeleted) {
            $conditions[] = "u.deleted_at IS NULL";
        }
        
        if ($search) {
            $conditions[] = "(p.name LIKE ? OR p.surname LIKE ? OR p.email LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($roleFilter && $roleFilter !== 'all') {
            $conditions[] = "u.role = ?";
            $params[] = $roleFilter;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY u.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Promote user to admin
     */
    public function promoteToAdmin(int $personId): bool {
        $sql = "UPDATE user SET role = 'admin' WHERE person_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$personId]);
    }

    /**
     * Demote admin to user
     */
    public function demoteToUser(int $personId): bool {
        $sql = "UPDATE user SET role = 'user' WHERE person_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$personId]);
    }

    /**
     * Soft delete a user
     */
    public function deleteUser(int $personId): bool {
        $sql = "UPDATE user SET deleted_at = NOW() WHERE person_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$personId]);
    }

    /**
     * Restore a deleted user
     */
    public function restoreUser(int $personId): bool {
        $sql = "UPDATE user SET deleted_at = NULL WHERE person_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$personId]);
    }

    /**
     * Check if user is a teacher
     */
    public function isTeacher(int $personId): bool {
        $sql = "SELECT COUNT(*) FROM teacher WHERE person_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get dashboard statistics
     */
    public function getStats(): array {
        $stats = [];
        
        $sql = "SELECT COUNT(*) FROM user";
        $stats['users'] = (int)$this->pdo->query($sql)->fetchColumn();
        
        $sql = "SELECT COUNT(*) FROM course";
        $stats['courses'] = (int)$this->pdo->query($sql)->fetchColumn();
        
        $sql = "SELECT COUNT(*) FROM note";
        $stats['notes'] = (int)$this->pdo->query($sql)->fetchColumn();
        
        $sql = "SELECT COUNT(*) FROM correction WHERE resolved = 0";
        $stats['corrections'] = (int)$this->pdo->query($sql)->fetchColumn();
        
        return $stats;
    }

    /**
     * Get recent notes
     */
    public function getRecentNotes(int $limit = 10): array {
        $sql = "SELECT n.id, n.title, n.status, n.created_at,
                   p.name, p.surname,
                   c.name AS course_name
            FROM note n
            LEFT JOIN person p ON p.id = n.owner_id
            JOIN topic t ON t.id = n.topic_id
            JOIN course_offering co ON co.id = t.offering_id
                JOIN course c ON c.id = co.course_id
            ORDER BY n.created_at DESC
            LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent corrections
     */
    public function getRecentCorrections(int $limit = 10): array {
        $sql = "SELECT c.id, c.message, c.created_at, c.resolved,
                   n.title AS note_title,
                   p.name AS reporter_name, p.surname AS reporter_surname
            FROM correction c
            JOIN note n ON c.note_id = n.id
            JOIN person p ON c.reported_by = p.id
            ORDER BY c.created_at DESC
            LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get notes by course statistics
     */
    public function getNotesByCourse(): array {
        $sql = "SELECT c.name AS course_name, COUNT(n.id) AS note_count
            FROM course c
            LEFT JOIN course_offering co ON co.course_id = c.id
            LEFT JOIN topic t ON t.offering_id = co.id
            LEFT JOIN note n ON n.topic_id = t.id AND n.status = 'published'
            GROUP BY c.id
            ORDER BY note_count DESC
            LIMIT 10";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count all users
     */
    public function countUsers(): int {
        $sql = "SELECT COUNT(*) AS c FROM user";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    /**
     * Count all courses
     */
    public function countCourses(): int {
        $sql = "SELECT COUNT(*) AS c FROM course";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    /**
     * Count all notes
     */
    public function countNotes(): int {
        $sql = "SELECT COUNT(*) AS c FROM note";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    /**
     * Count unresolved corrections
     */
    public function countUnresolvedCorrections(): int {
        $sql = "SELECT COUNT(*) AS c FROM correction WHERE resolved = 0";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }
}
