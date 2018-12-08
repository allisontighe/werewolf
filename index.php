<?php
require_once 'WerewolfBot.class.php';

$content = file_get_contents('php://input');
$update = json_decode($content, true);
if(!$update){
    echo "Werewolf Bot";
    exit();
}
else (new WerewolfBot($update))->process();
