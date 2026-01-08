<?php
require_once 'db.php';

class UserModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    /**
     * Check if an email already exists in the database.
     */
    public function emailExists(string $email): bool {
        $stmt = $this->pdo->prepare("SELECT id FROM person WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }

    /**
     * Create a new user: insert into person and user tables.
     */
    public function createUser(string $name, string $surname, string $email, string $hashedPassword, string $role = 'user'): int {
        // Insert into person
        $stmt = $this->pdo->prepare("INSERT INTO person (name, surname, email) VALUES (?, ?, ?)");
        $stmt->execute([$name, $surname, $email]);
        $personId = (int)$this->pdo->lastInsertId();

        // Insert into user
        $stmt = $this->pdo->prepare("INSERT INTO user (person_id, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$personId, $hashedPassword, $role]);

        return $personId;
    }

    /**
     * Authenticate a user by email and password.
     * Returns user data array on success, false on failure.
     * Handles legacy plaintext password migration.
     */
    public function authenticate(string $email, string $password): array|false {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.id AS person_id,
                p.name,
                p.surname,
                p.email,
                u.password,
                u.role
            FROM person p
            JOIN user u ON p.id = u.person_id
            WHERE p.email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        // Check password
        if (password_verify($password, $user['password'])) {
            // Hashed password matches
            return $user;
        } elseif ($user['password'] === $password) {
            // Legacy plaintext match: re-hash and update
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $this->updatePassword($user['person_id'], $newHash);
            $user['password'] = $newHash; // Update for return
            return $user;
        }

        return false;
    }

    /**
     * Update the password for a user.
     */
    public function updatePassword(int $personId, string $hashedPassword): bool {
        $stmt = $this->pdo->prepare("UPDATE user SET password = ? WHERE person_id = ?");
        return $stmt->execute([$hashedPassword, $personId]);
    }

    /**
     * Update the last_login timestamp for a user.
     */
    public function updateLastLogin(int $personId): bool {
        $stmt = $this->pdo->prepare("UPDATE user SET last_login = NOW() WHERE person_id = ?");
        return $stmt->execute([$personId]);
    }

    /**
     * Update user profile (programme and bio)
     */
    public function updateUserProfile(int $personId, string $programme, string $bio): bool {
        $stmt = $this->pdo->prepare("UPDATE user SET programme = ?, bio = ? WHERE person_id = ?");
        return $stmt->execute([$programme, $bio, $personId]);
    }
}
?>