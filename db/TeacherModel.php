<?php
require_once 'db.php';

class TeacherModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    /**
     * Get all teacher requests pending approval
     */
    public function getPendingRequests(): array {
        $sql = "SELECT t.person_id, p.name, p.surname, p.email
            FROM teacher t
            JOIN person p ON t.person_id = p.id
            ORDER BY p.surname, p.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Approve a teacher request
     */
    public function approveRequest(int $personId): bool {
        return true;
    }

    /**
     * Reject/delete a teacher request
     */
    public function rejectRequest(int $personId): bool {
        $sql = "DELETE FROM teacher WHERE person_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$personId]);
    }

    /**
     * Check if a person is already a teacher
     */
    public function isTeacher(int $personId): bool {
        $sql = "SELECT COUNT(*) FROM teacher WHERE person_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if person exists by email
     */
    public function personExistsByEmail(string $email): array|false {
        $sql = "SELECT id, name, surname, email FROM person WHERE email = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new person and teacher record
     */
    public function createTeacher(string $name, string $surname, string $email): int|false {
        try {
            $this->pdo->beginTransaction();

            // Insert person
            $sql = "INSERT INTO person (name, surname, email) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$name, $surname, $email]);
            $personId = (int)$this->pdo->lastInsertId();

            // Insert teacher
            $sql = "INSERT INTO teacher (person_id) VALUES (?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$personId]);

            $this->pdo->commit();
            return $personId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Add existing person as teacher
     */
    public function addExistingAsTeacher(int $personId): bool {
        $sql = "INSERT INTO teacher (person_id) VALUES (?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$personId]);
    }

    /**
     * Request to become a teacher (by logged-in user)
     */
    public function requestTeacher(int $personId): bool {
        $sql = "SELECT COUNT(*) FROM teacher WHERE person_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personId]);
        
        if ($stmt->fetchColumn() > 0) {
            return false; // Already exists
        }

        $sql = "INSERT INTO teacher (person_id) VALUES (?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$personId]);
    }

    /**
     * Get all approved teachers
     */
    public function getAllTeachers(): array {
        $sql = "SELECT t.person_id, p.name, p.surname, p.email
            FROM teacher t
            JOIN person p ON t.person_id = p.id
            ORDER BY p.surname, p.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get teacher by person_id
     */
    public function getTeacherByPersonId(int $personId): array|false {
        $sql = "SELECT department, unibo_site, phone_number, personal_site
                FROM teacher
                WHERE person_id = ?
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if teacher exists
     */
    public function teacherExists(int $personId): bool {
        $sql = "SELECT person_id FROM teacher WHERE person_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$personId]);
        return $stmt->fetch() !== false;
    }

    /**
     * Create a teacher request (empty row for pending)
     */
    public function createTeacherRequest(int $personId): bool {
        $sql = "INSERT INTO teacher (person_id) VALUES (?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$personId]);
    }

    /**
     * Get user with teacher info
     */
    public function getUserWithTeacherInfo(int $userId): array|false {
        $sql = "SELECT 
                    p.id,
                    p.name,
                    p.surname,
                    p.email,
                    u.role,
                    t.person_id      AS teacher_person_id,
                    t.department,
                    t.unibo_site,
                    t.phone_number,
                    t.personal_site
                FROM person p
                JOIN user u ON p.id = u.person_id
                LEFT JOIN teacher t ON t.person_id = p.id
                WHERE p.id = ?
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create teacher profile
     */
    public function createTeacherProfile(int $userId, string $department, string $unibo_site, string $phone_number, string $personal_site): bool {
        $sql = "INSERT INTO teacher (person_id, department, unibo_site, phone_number, personal_site)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$userId, $department, $unibo_site, $phone_number, $personal_site]);
    }

    /**
     * Update teacher profile
     */
    public function updateTeacherProfile(int $userId, string $department, string $unibo_site, string $phone_number, string $personal_site): bool {
        $sql = "UPDATE teacher
                SET department = ?, unibo_site = ?, phone_number = ?, personal_site = ?
                WHERE person_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$department, $unibo_site, $phone_number, $personal_site, $userId]);
    }

    /**
     * Get all teachers with full name (for forms)
     */
    public function getAllTeachersWithFullName(): array {
        $sql = "SELECT DISTINCT p.id,
                       CONCAT(p.name, ' ', p.surname) AS full_name
                FROM person p
                JOIN teacher t ON t.person_id = p.id
                ORDER BY p.surname, p.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
