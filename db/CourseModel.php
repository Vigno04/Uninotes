<?php
require_once 'db.php';

class CourseModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    /**
     * Get all programmes
     */
    public function getAllProgrammes(): array {
        $sql = "SELECT id, name FROM programme ORDER BY name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all courses
     */
    public function getAllCourses(): array {
        $sql = "SELECT c.id, c.name, p.name AS programme_name
                FROM course c
                LEFT JOIN programme p ON c.programme_id = p.id
                ORDER BY c.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get course by ID
     */
    public function getCourseById(int $courseId): array|false {
        $sql = "SELECT c.*, p.name AS programme_name
                FROM course c
                LEFT JOIN programme p ON c.programme_id = p.id
                WHERE c.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new course
     */
    public function createCourse(string $name, ?int $programmeId = null): int|false {
        $sql = "INSERT INTO course (name, programme_id) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute([$name, $programmeId])) {
            return (int)$this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Update a course
     */
    public function updateCourse(int $courseId, string $name, ?int $programmeId = null): bool {
        $sql = "UPDATE course SET name = ?, programme_id = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$name, $programmeId, $courseId]);
    }

    /**
     * Delete a course
     */
    public function deleteCourse(int $courseId): bool {
        $sql = "DELETE FROM course WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$courseId]);
    }

    /**
     * Get all teachers
     */
    public function getAllTeachers(): array {
        $sql = "SELECT t.person_id, p.name, p.surname, p.email
                FROM teacher t
                JOIN person p ON t.person_id = p.id
                WHERE t.approved = 1
                ORDER BY p.surname, p.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get course offerings (course-teacher combinations)
     */
    public function getCourseOfferings(int $courseId): array {
        $sql = "SELECT co.id, co.year, t.person_id, p.name, p.surname
                FROM course_offering co
                JOIN teacher t ON co.teacher_id = t.person_id
                JOIN person p ON t.person_id = p.id
                WHERE co.course_id = ?
                ORDER BY co.year DESC, p.surname, p.name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a course offering
     */
    public function createCourseOffering(int $courseId, int $teacherId, string $year): int|false {
        $sql = "INSERT INTO course_offering (course_id, teacher_id, year) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute([$courseId, $teacherId, $year])) {
            return (int)$this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Get offering info by ID
     */
    public function getOfferingById(int $offeringId): array|false {
        $sql = "SELECT co.*, c.name AS course_name, 
                       p.name AS teacher_name, p.surname AS teacher_surname
                FROM course_offering co
                JOIN course c ON co.course_id = c.id
                JOIN teacher t ON co.teacher_id = t.person_id
                JOIN person p ON t.person_id = p.id
                WHERE co.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$offeringId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get followed offerings by user
     */
    public function getFollowedOfferings(int $userId): array {
        $sql = "SELECT course_offering_id FROM user_follows_course_offering WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Follow a course offering
     */
    public function followOffering(int $userId, int $offeringId): bool {
        $sql = "INSERT INTO user_follows_course_offering (user_id, course_offering_id) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE user_id = user_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$userId, $offeringId]);
    }

    /**
     * Unfollow a course offering
     */
    public function unfollowOffering(int $userId, int $offeringId): bool {
        $sql = "DELETE FROM user_follows_course_offering WHERE user_id = ? AND course_offering_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$userId, $offeringId]);
    }

    /**
     * Get follower count for an offering
     */
    public function getFollowerCount(int $offeringId): int {
        $sql = "SELECT COUNT(*) FROM user_follows_course_offering WHERE course_offering_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$offeringId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get all course offerings with details
     */
    public function getAllCourseOfferings(): array {
        $sql = "SELECT co.id, co.year, c.name AS course_name,
                       p.name AS teacher_name, p.surname AS teacher_surname,
                       COUNT(DISTINCT n.id) AS note_count
                FROM course_offering co
                JOIN course c ON co.course_id = c.id
                JOIN teacher t ON co.teacher_id = t.person_id
                JOIN person p ON t.person_id = p.id
                LEFT JOIN note n ON n.course_offering_id = co.id
                GROUP BY co.id
                ORDER BY co.year DESC, c.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get topics for a course offering
     */
    public function getTopics(int $offeringId): array {
        $sql = "SELECT id, name FROM topic WHERE course_offering_id = ? ORDER BY name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get teachers list
     */
    public function getTeachersList(): array {
        $sql = "SELECT t.person_id, p.name, p.surname
                FROM teacher t
                JOIN person p ON p.id = t.person_id
                ORDER BY p.surname, p.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create course with admin
     */
    public function createCourseWithAdmin(string $name, string $description, int $createdBy, ?int $programmeId): int|false {
        $sql = "INSERT INTO course (name, description, created_by, programme_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute([$name, $description, $createdBy, $programmeId])) {
            return (int)$this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Get course details by ID
     */
    public function getCourseDetails(int $courseId): array|false {
        $sql = "SELECT c.id, c.name, c.description, c.programme_id, c.created_at
                FROM course c
                WHERE c.id = ?
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get course offerings with teachers and followers
     */
    public function getCourseOfferingsWithDetails(int $courseId): array {
        $sql = "SELECT
                    co.id,
                    co.year,
                    co.semester,
                    GROUP_CONCAT(DISTINCT CONCAT(p_t.name, ' ', p_t.surname) ORDER BY p_t.surname SEPARATOR ', ') AS teacher_names,
                    COUNT(DISTINCT cof.user_id) AS follower_count
                FROM course_offering co
                LEFT JOIN course_offering_teacher cot ON cot.offering_id = co.id
                LEFT JOIN teacher t ON t.person_id = cot.teacher_id
                LEFT JOIN person p_t ON p_t.id = t.person_id
                LEFT JOIN course_offering_follow cof ON cof.offering_id = co.id
                WHERE co.course_id = ?
                GROUP BY co.id, co.year, co.semester
                ORDER BY co.year DESC, co.semester DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add course offering
     */
    public function addCourseOffering(int $year, string $semester, int $courseId): bool {
        $sql = "INSERT INTO course_offering (year, semester, course_id) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$year, $semester, $courseId]);
    }

    /**
     * Link teacher to offering
     */
    public function linkTeacherToOffering(int $offeringId, int $teacherId): bool {
        $sql = "INSERT IGNORE INTO course_offering_teacher (offering_id, teacher_id) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$offeringId, $teacherId]);
    }

    /**
     * Follow a course offering
     */
    public function followCourseOffering(int $offeringId, int $userId): bool {
        $sql = "INSERT IGNORE INTO course_offering_follow (offering_id, user_id) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$offeringId, $userId]);
    }

    /**
     * Unfollow a course offering
     */
    public function unfollowCourseOffering(int $offeringId, int $userId): bool {
        $sql = "DELETE FROM course_offering_follow WHERE offering_id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$offeringId, $userId]);
    }

    /**
     * Get course by ID (simple)
     */
    public function getCourse(int $courseId): array|false {
        $sql = "SELECT id, name, description FROM course WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get course offerings with notes count and teachers
     */
    public function getCourseOfferingsWithNotes(int $courseId): array {
        $sql = "SELECT 
                    co.id,
                    co.year,
                    co.semester,
                    COUNT(DISTINCT n.id) AS note_count,
                    GROUP_CONCAT(DISTINCT CONCAT(p.name, ' ', p.surname) SEPARATOR ', ') AS teachers
                FROM course_offering co
                LEFT JOIN topic t ON t.offering_id = co.id
                LEFT JOIN note n ON n.topic_id = t.id AND n.status = 'published'
                LEFT JOIN course_offering_teacher cot ON cot.offering_id = co.id
                LEFT JOIN teacher te ON te.person_id = cot.teacher_id
                LEFT JOIN person p ON p.id = te.person_id
                WHERE co.course_id = ?
                GROUP BY co.id, co.year, co.semester
                ORDER BY co.year DESC, co.semester DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get followed offering IDs for a user
     */
    public function getFollowedOfferingIds(int $userId): array {
        $sql = "SELECT offering_id FROM course_offering_follow WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get all courses with offering and note count
     */
    public function getAllCoursesWithStats(): array {
        $sql = "SELECT 
                    c.id, 
                    c.name, 
                    c.description,
                    COUNT(DISTINCT co.id) AS offering_count,
                    COUNT(DISTINCT n.id) AS note_count,
                    (SELECT co2.id FROM course_offering co2 WHERE co2.course_id = c.id ORDER BY co2.year DESC, co2.semester DESC LIMIT 1) AS single_offering_id,
                    (SELECT co2.year FROM course_offering co2 WHERE co2.course_id = c.id ORDER BY co2.year DESC, co2.semester DESC LIMIT 1) AS single_offering_year,
                    (SELECT co2.semester FROM course_offering co2 WHERE co2.course_id = c.id ORDER BY co2.year DESC, co2.semester DESC LIMIT 1) AS single_offering_semester
                FROM course c
                LEFT JOIN course_offering co ON co.course_id = c.id
                LEFT JOIN topic t ON t.offering_id = co.id
                LEFT JOIN note n ON n.topic_id = t.id AND n.status = 'published'
                GROUP BY c.id, c.name, c.description
                ORDER BY c.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get followed courses with offerings per course
     */
    public function getFollowedCoursesWithOfferings(int $userId): array {
        $sql = "SELECT 
                    c.id AS course_id,
                    co.id AS offering_id,
                    co.year,
                    co.semester
                FROM course_offering_follow cof
                JOIN course_offering co ON cof.offering_id = co.id
                JOIN course c ON co.course_id = c.id
                WHERE cof.user_id = ?
                ORDER BY c.id, co.year DESC, co.semester DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get courses for admin management (with offering and note counts)
     */
    public function getCoursesForManagement(): array {
        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.description,
                    COUNT(DISTINCT co.id) AS offering_count,
                    COUNT(DISTINCT n.id)  AS note_count
                FROM course c
                LEFT JOIN course_offering co ON co.course_id = c.id
                LEFT JOIN topic t            ON t.offering_id = co.id
                LEFT JOIN note n             ON n.topic_id = t.id
                GROUP BY c.id, c.name, c.description
                ORDER BY c.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get popular courses (for home page sidebar)
     */
    public function getPopularCourses(int $limit = 10): array {
        $sql = "SELECT c.id, c.name, COUNT(n.id) AS notes_count
            FROM course c
            JOIN course_offering co ON co.course_id = c.id
            JOIN topic t ON t.offering_id = co.id
            JOIN note n ON n.topic_id = t.id AND n.status = 'published'
            GROUP BY c.id
            ORDER BY notes_count DESC, c.name ASC
            LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user followed offerings for home filter
     */
    public function getUserFollowedOfferingsForHome(int $userId): array {
        $sql = "SELECT 
                    co.id,
                    c.name AS course_name,
                    co.year,
                    co.semester
                FROM course_offering_follow cof
                JOIN course_offering co ON cof.offering_id = co.id
                JOIN course c ON co.course_id = c.id
                WHERE cof.user_id = ?
                ORDER BY c.name, co.year DESC, co.semester DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get courses with published notes (for upload form)
     */
    public function getCoursesWithPublishedNotes(): array {
        $sql = "SELECT DISTINCT c.id, c.name
                FROM course c
                JOIN course_offering co ON co.course_id = c.id
                JOIN topic t ON t.offering_id = co.id
                JOIN note n ON n.topic_id = t.id AND n.status = 'published'
                ORDER BY c.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get followers of a course offering
     */
    public function getOfferingFollowers(int $offeringId): array {
        $sql = "SELECT p.name, p.surname, p.email
                FROM course_offering_follow cof
                JOIN user u ON u.person_id = cof.user_id
                JOIN person p ON p.id = u.person_id
                WHERE cof.offering_id = ?
                ORDER BY p.surname, p.name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all topics for a course (across all course offerings)
     */
    public function getTopicsByCourseId(int $courseId): array {
        $sql = "SELECT DISTINCT t.id, t.name
                FROM topic t
                JOIN course_offering co ON t.offering_id = co.id
                WHERE co.course_id = ?
                ORDER BY t.name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
