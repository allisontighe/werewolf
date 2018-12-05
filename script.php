<?php
require_once 'Connection.class.php';
if ($_GET['key'] === 'applesarefruits' && $_GET['action'] === 'createtable') {
    try {
        $conn = new Connection;
        $conn->query('INSERT INTO players (chatId) VALUES (100)');
        $conn->query('INSERT INTO players (chatId) VALUES (1212)');
        $resultSet = $conn->query('SELECT * FROM players')->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($resultSet);
    }
    catch(PDOException $e) {
        echo $e->getMessage();
    }
}
else {
    echo 'Scripts page';
}