<?php
require_once 'Connection.class.php';
if ($_GET['action'] === 'alter' && false) {
    $connection = new Connection;
    try {
        $query = 'ALTER TABLE players ADD COLUMN telegramId BIGINT';
        $connection->exec($query);
        echo 'Executed: '.$query;
        $query = 'ALTER TABLE players ADD COLUMN name VARCHAR(50)';
        $connection->exec($query);
        echo 'Executed: '.$query;
    }
    catch(PDOException $e) {
        echo $e->getMessage();
    }
}