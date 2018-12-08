<?php
require_once 'Connection.class.php';
require_once 'Game.class.php';

$connection = new Connection;
$PDOStatement = $connection->prepare('SELECT chat_id FROM chats WHERE status = 1');
if ($PDOStatement->rowCount() === 0) exit('No data!');
else {
    //start new game
    new Game($connection, $PDOStatement->fetchColumn());
}