<?php
class DatabaseHelper {
    private $db;
    public function __construct($host, $user, $password, $dbname) {
        $this->db = new mysqli($host, $user, $password, $dbname);
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->db->set_charset("utf8mb4");

        if ($this->db->connect_error) {
            die("Connessione al Database fallita: ". $this->db->connect_error);
        }
    }

    /*********************************************/
    /******************* QUERY *******************/
    /*********************************************/

    /* ############################### Query User ############################### */

    // Autentica utente
    public function checkLogin($email, $password) {
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

        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            return null;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            return null;
        }

        // password in chiaro per ora
        if ($row['password'] !== $password) {
            return null;
        }

        return $row;
    }

}

?>