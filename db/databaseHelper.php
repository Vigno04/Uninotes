<?php
require_once __DIR__ . '/connection.php';

class DatabaseHelper {
    private mysqli $db;

    public function __construct() {
        // Usa la connessione singleton (non apre nuove connessioni)
        $this->db = db();
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