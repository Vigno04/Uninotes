<?php
// db/db.php
// Compatibilità: manteniamo la classe Database ma senza creare un'altra connessione.

declare(strict_types=1);

require_once __DIR__ . '/connection.php';

final class Database {
    private function __construct() {}

    /**
     * Restituisce la connessione mysqli singleton.
     */
    public static function getInstance(): mysqli {
        return db();
    }
}
?>