<?php
class DatabaseHelper {
    private $db;

    public function __construct() {
        // Connessione al database UniNotes
        $servername = "localhost";
        $username = "root";
        $password = "";        // la tua password MySQL
        $dbname = "uninotes";  // nome del database dove hai importato lo schema

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->db = new mysqli($servername, $username, $password, $dbname);
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
    public function authUser() {}

/**
     * Registra un nuovo utente nel database.
     * @param string $first_name Nome
     * @param string $last_name Cognome
     * @param string $email Email
     * @param string $password Password
     * @param string $phone Numero di telefono
     * @param int $role Ruolo (1 = venditore, 2 = cliente)
     * @param string $address Indirizzo
     * @return array ['success' => true|false, 'message' => '...'].
     */
    public function registerUser($first_name, $last_name, $email, $password, $phone, $role, $address)
    {
        $this->db->begin_transaction();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO `User`(`first_name`,`last_name`,`email`,`passwordHash`,`address`,`phone_number`,`role`) VALUES (?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sssssis", $first_name, $last_name, $email, $passwordHash, $address, $phone, $role);

            if ($stmt->execute() === false) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Errore nella registrazione dell\'utente.'];
            }
            $this->db->commit();
            return ['success' => true, 'message' => 'Registrazione avvenuta con successo.'];
        } catch (mysqli_sql_exception $e) {
            $this->db->rollback();
            // Puoi verificare l'errore specifico (es. duplicate entry) e personalizzare il messaggio
            if ($e->getCode() === 1062) { // Duplicate entry
                return ['success' => false, 'message' => 'L\'email è già in uso.'];
            }
            return ['success' => false, 'message' => 'Errore nella registrazione: ' . $e->getMessage()];
        }
    }

    public function updatePassword() {}

    public function getUserInfo() {}


}

?>