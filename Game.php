<?php
require_once 'Connection.class.php';
require_once 'Game.class.php';
ignore_user_abort(true);
set_time_limit(100);

$connection = new Connection;
$PDOStatement = $connection->query('SELECT chat_id FROM chats WHERE status = 0');
if ($PDOStatement->rowCount() === 0) exit('No data!');
else {
    //start new game
    new Game($connection, $PDOStatement->fetchColumn());
}