<?php
require_once 'config.php';
require_once 'WerewolfBot.class.php';
$content = file_get_contents('php://input');
$update = json_decode($content, true);
if (!$update) {
    echo "<h1>Telegram Bot</h1>";
    exit;
}
if (isset($update['message'])) {
    (new WerewolfBot($update['message']))->process();
}
