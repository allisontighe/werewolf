<?php
require_once 'Bot.class.php';
require_once 'Connection.class.php';
require_once 'functions.php';
require_once 'constants.php';
require_once 'Role.class.php';
class WerewolfBot extends Bot {
    private $connection;
    public function __construct(array $message) {
        $this->connection = new Connection;
        parent::__construct($message);
    }
    public function process() {
        //parse message text
        $parseArray = parseMessageText($this->messageText);
        $command = $parseArray['command'];
        $parameter = $parseArray['parameter'];
        
        if ($command === '/hi') {
            $this->sendEcho('Bye!');
        }
        else if ($command === '/newgame') {
            //check if a game is already running
            if (doesChatIdExist($this->connection, $this->chatId)) {
                http_response_code(200);
                exit('A game is already running!');
            }
            addChat($this->connection, $this->chatId);
            addToGame($this->connection, $this->chatId, $this->telegramId, $this->firstName);
            $this->sendEcho('A werewolf game is starting!');
            //execute game script
            $curl = curl_init('https://'.$_SERVER['HTTP_HOST'].'/Game.php?chat_id='.$this->chatId);
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 100);
            curl_exec($curl);
            curl_close($curl);
        }
        else if ($command === '/eat' && $parameter !== false) {
            if (doesTelegramIdExist($this->connection, $parameter) && !isDead($this->connection, $parameter)) {
                takeActionOn($this->connection, $this->telegramId, $parameter);
                $this->editMessage($this->chatId, $this->messageId, 'Target chosen!');
            }
            else $this->sendEcho('Invalid target!');
        }
        else if ($command === '/lynch' && $parameter !== false) {
            if (doesTelegramIdExist($this->connection, $parameter) && !isDead($this->connection, $parameter)) {
                takeActionOn($this->connection, $this->telegramId, $parameter);
                $this->editMessage($this->chatId, $this->messageId, 'Target chosen!');
                $chatId = getChatId($this->connection, $this->telegramId);
                $playerName = getPlayerName($this->connection, $this->telegramId);
                $targetName = getPlayerName($this->connection, $parameter);
                $this->sendMessageToPlayer($playerName.' has decided to lynch '.$targetName.'!', $chatId);
            }
            else $this->sendEcho('Invalid target!');
        }
        else if ($command === '/start' && $parameter !== false) {
            $chatId = $parameter;
            if (doesChatIdExist($this->connection, $chatId)) {
                //check if already joined
                if (doesTelegramIdExist($this->connection, $this->telegramId)) {
                    $this->sendEcho('You have already joined the game!');
                }
                else {
                    addToGame($this->connection, $chatId, $this->telegramId, $this->firstName);
                    $playerListMessageId = getMessageId($this->connection, $chatId);
                    if ($playerListMessageId !== 0) {
                        $this->editMessage($chatId, $playerListMessageId, makePlayerList($this->connection, $chatId));
                    }
                    $this->sendEcho('You have been added to the game!');
                }
            }
            else {
                $this->sendEcho('A game is not currently running.');
            }
        }
    }
}