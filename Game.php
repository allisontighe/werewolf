<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
require_once 'Connection.class.php';
require_once 'Game.class.php';
ignore_user_abort(true);
set_time_limit(1800);
if (!isset($_GET['chat_id'])) exit('No data!');
else {
    //start new game
    new Game(new Connection, $_GET['chat_id']);
}