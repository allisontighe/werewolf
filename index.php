<?php
require_once 'Database.class.php';
require_once 'config.php';
require_once 'WerewolfBot.class.php';
function errorHandle($no, $e, $file, $line) {
    (new Database())->log($e.$file.$line);
}
error_reporting(E_ERROR & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', 'On');
set_error_handler('errorHandle');

$content = file_get_contents('php://input');
$update = json_decode($content, true);

if (isset($update['message'])) {
    (new WerewolfBot($update['message']))->process();
}
header("Content-Type: application/json");
echo json_encode(['method' => 'sendMessage', 'chat_id' => -1001244728734, 'text' => 'Testing 1 2 1 2']);
