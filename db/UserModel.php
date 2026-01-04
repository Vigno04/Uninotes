<?php
require_once __DIR__ . '/connection.php';

class UserModel {
    private mysqli $conn;

    public function __construct() {
        $this->conn = db();
    }

    /**
     * Check if an email already exists in the database.
     */
    public function emailExists(string $email): bool {
        $stmt = mysqli_prepare($this->conn, "SELECT id FROM person WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $exists = $res && (mysqli_fetch_assoc($res) !== null);
        mysqli_stmt_close($stmt);
        return $exists;
    }

    /**
     * Create a new user: insert into person and user tables.
     */
    public function createUser(string $name, string $surname, string $email, string $hashedPassword, string $role = 'user'): int {
        mysqli_begin_transaction($this->conn);
        try {
            // Insert into person
            $stmt = mysqli_prepare($this->conn, "INSERT INTO person (name, surname, email) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $surname, $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $personId = (int)mysqli_insert_id($this->conn);

            // Insert into user
            $stmt = mysqli_prepare($this->conn, "INSERT INTO user (person_id, password, role) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iss", $personId, $hashedPassword, $role);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($this->conn);
            return $personId;
        } catch (mysqli_sql_exception $e) {
            mysqli_rollback($this->conn);
            throw $e;
        }
    }

    /**
     * Authenticate a user by email and password.
     * Returns user data array on success, false on failure.
     * Handles legacy plaintext password migration.
     */
    public function authenticate(string $email, string $password): array|false {
        $sql = "
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
        ";

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$user) {
            return false;
        }

        // Check password
        if (password_verify($password, $user['password'])) {
            return $user;
        }

        // Legacy plaintext match: re-hash and update
        if ($user['password'] === $password) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $this->updatePassword((int)$user['person_id'], $newHash);
            $user['password'] = $newHash;
            return $user;
        }

        return false;
    }

    /**
     * Update the password for a user.
     */
    public function updatePassword(int $personId, string $hashedPassword): bool {
        $stmt = mysqli_prepare($this->conn, "UPDATE user SET password = ? WHERE person_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $personId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (bool)$ok;
    }

    /**
     * Update the last_login timestamp for a user.
     */
    public function updateLastLogin(int $personId): bool {
        $stmt = mysqli_prepare($this->conn, "UPDATE user SET last_login = NOW() WHERE person_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $personId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (bool)$ok;
    }
}
?>
