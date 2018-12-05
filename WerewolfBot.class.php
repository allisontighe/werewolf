<?php
require_once 'Bot.class.php';
require_once 'Connection.class.php';
require_once 'functions.php';
class WerewolfBot extends Bot {
    private $connection;
    private $responseText;
    public function __construct(array $message) {
        $this->connection = new Connection;
        parent::__construct($message);
    }
    public function process() {
        $this->readCommand();
        if (!empty($this->responseText)) $this->sendMessage($this->responseText);
    }
    private function readCommand() {
        if ($this->messageText === '/hi') {
            $this->responseText = 'Hey!!';
        }
        else if ($this->messageText === '/newgame') {
            //check if a game is already running
            if (doesChatIdExist($this->connection, $this->chatId)) {
                return $this->responseText = 'A game is already running!';
            }
            
        }
        else if ($this->messageText === '/endgame') {
            //end game
        }
    }
}