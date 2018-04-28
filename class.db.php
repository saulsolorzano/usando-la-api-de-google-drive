<?php
include 'db.php';

class DBHandler {
    private $db;
    function __construct() {
        $this->connect_database();
    }
    // Esta función es la que usaremos para interactuar con nuestra base de datos
    public function getInstance() {
        return $this->db;
    }

    private function connect_database() {
        try {
            $connection_string = 'mysql:host='.DB_HOST.';charset=utf8;dbname='.DB_NAME;
            $connection_array = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            );

            $this->db = new PDO($connection_string, DB_USER, DB_PASSWORD, $connection_array);
        }
        catch(PDOException $e) {
            echo 'ERROR: ' . $e->getMessage();
        }
    }
}