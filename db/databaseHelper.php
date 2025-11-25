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

    public function registerUser() {}

    public function updatePassword() {}

    public function getUserInfo() {}


}

?>