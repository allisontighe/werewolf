<?php
require_once 'Connection.class.php';
require_once 'Game.class.php';
ignore_user_abort(true);
set_time_limit(100);
if (!isset($_GET['chat_id'])) exit('No data!');
else {
    //start new game
    new Game(new Connection, $_GET['chat_id']);
}