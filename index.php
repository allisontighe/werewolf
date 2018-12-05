<?php
ob_implicit_flush(1); //set implicit flush
require_once 'WerewolfBot.class.php';

$content = file_get_contents('php://input');
$update = json_decode($content, true);
if(!$update){
    echo "Werewolf Bot";
    exit();
}
if (isset($update['message'])) {
    (new WerewolfBot($update['message']))->process();
}
