<?php
require_once 'Connection.class.php';
if ($_GET['key'] === 'applesarefruits' && $_GET['action'] === 'createtable') {
    try {
        $conn = new Connection;
        $conn->query('TRUNCATE TABLE players');
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