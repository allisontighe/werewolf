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
            $i = 0;
            $limit = 5;
            while($i < $limit) {
                $timeLeft = ($limit - $i) * 30;
                $this->sendMessage($timeLeft.' seconds left to join!');
                $i++;
                sleep(2);
            }
            return $this->responseText = 'The game has started!';
        }
        else if ($this->messageText === '/join') {
            //check if already joined
            if (doesTelegramIdExist($this->connection, $this->chatId, $this->telegramId)) {
                return $this->responseText = 'You have already joined the game!';
            }
            addToGame($this->connection, $this->chatId, $this->telegramId, $this->firstName);
            return $this->responseText = 'Added '.$this->firstName.' to the game!';
        }
        else if ($this->messageText === '/endgame') {
            //end game
            deleteChatId($this->connection, $this->chatId);
            return $this->responseText = 'Forcefully ended the game!';
        }
    }
}