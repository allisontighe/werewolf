<?php
require_once 'config.php';
final class Database extends PDO {
    public function __construct() {
        try {
            parent::__construct('mysql:host=localhost;dbname='.Config::DATABASE_NAME, Config::DATABASE_USER, Config::DATABASE_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        }
        catch(PDOException $e) {
            die('Database connection failed!');
        }
    }
    public function log($log) {
        $statement = $this->prepare('INSERT INTO log (log) VALUES (:log)');
        $statement->bindParam(':log', $log, PDO::PARAM_STR);
        $statement->execute();
    }
    public function viewLog() {
        return $this->query('SELECT log FROM log LIMIT 100')->fetchAll(PDO::FETCH_COLUMN);
    }
}