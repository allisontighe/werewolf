<?php
require_once 'Connection.class.php';
if ($_GET['key'] === 'applesarefruits' && $_GET['action'] === 'createtable') {
    $conn = new Connection;
    $conn->exec("CREATE TABLE `players` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `chatId` BIGINT NOT NULL , `lastAction` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `role` TINYINT UNSIGNED NOT NULL DEFAULT '0' , PRIMARY KEY (`id`), INDEX (`chatId`), INDEX (`role`));");
    echo 'Script ran';
}
else {
    echo 'Scripts page';
}