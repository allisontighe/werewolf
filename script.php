<?php
require_once 'Connection.class.php';
if ($_GET['key'] === 'applesarefruits' && $_GET['action'] === 'createtable') {
    try {
        $conn = new Connection;
        $conn->exec("CREATE TABLE IF NOT EXISTS players ( id INT NOT NULL AUTO_INCREMENT , chatId BIGINT NOT NULL , lastAction TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , role TINYINT UNSIGNED NOT NULL DEFAULT '0' , PRIMARY KEY (id), INDEX (chatId), INDEX (role));");
        echo 'Script ran';
    }
    catch(PDOException $e) {
        echo $e->getMessage();
    }
}
else {
    echo 'Scripts page';
}