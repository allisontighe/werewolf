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
        if ($this->messageText === '/hi') {
            $this->sendMessage('Hey!');
        }
        else if ($this->messageText === '/whoisbetter') {
            $this->sendMessage('I am better!');
        }
        else if ($this->messageText === '/test') {
            $this->sendMessage('TEST TEST TEST!');
        }
        else if ($this->messageText === '/silver') {
            $this->sendMessage('Hawk!');
        }
    }
}