<?php
require_once 'Connection.class.php';
if ($_GET['key'] === 'applesarefruits' && $_GET['action'] === 'createtable') {
    try {
        $conn = new Connection;
        $conn->exec("CREATE TABLE IF NOT EXISTS players ( id SERIAL PRIMARY KEY, chatId BIGINT, lastAction TIMESTAMP DEFAULT CURRENT_TIMESTAMP , role SMALLINT DEFAULT '0');");
        echo 'Script ran';
    }
    catch(PDOException $e) {
        echo $e->getMessage();
    }
}
else {
    echo 'Scripts page';
}