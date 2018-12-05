<?php
final class Connection extends PDO {
    public function __construct() {
        try {
            $db = parse_url(getenv('DATABASE_URL'));
            parent::__construct('pgsql:host='.$db['host'].';port='.$db['port'].';user='.$db['user'].';password='.$db['pass'].';dbname='.ltrim($db['path'], '/'));
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e) {
            die('Database connection failed!');
        }
    }
}