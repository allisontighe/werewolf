<?php
require_once 'Bot.class.php';
require_once 'Database.class.php';
class WerewolfBot extends Bot {
    private $connection;
    public function __construct(array $message) {
        //$this->connection = new Database();
        parent::__construct($message);
    }
    public function process() {
        $this->sendMessage('hiii');
    }
}