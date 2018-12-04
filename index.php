<?php
require_once 'config.php';
$content = file_get_contents('php://input');
$update = json_decode($content, true);
if (!$update) {
    echo "<h1>Telegram Bot</h1>";
    exit;
}
if (isset($update['message'])); //do something
