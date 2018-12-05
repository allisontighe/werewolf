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
        if (!empty($this->responseText)) $this->sendMessageToChat($this->responseText);
    }
    private function readCommand() {
        
        if ($this->messageText === '/hi') {
            $this->responseText = 'Bye!';
        }
        else if ($this->messageText === '/newgame') {
            //check if a game is already running
            if (doesChatIdExist($this->connection, $this->chatId)) {
                http_response_code(200);
                exit('A game is already running');
            }
            addChat($this->connection, $this->chatId);
            addToGame($this->connection, $this->chatId, $this->telegramId, $this->firstName);
            return $this->beginGameSequence();
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
    private function makePlayerList(): string {
        $players = getTelegramNamesFromChat($this->connection, $this->chatId);
        $string = '*Player list (Total: '.count($players).')*'.chr(10);
        foreach($players as $player) {
            $string .= '`'.$player.'`'.chr(10);
        }
        return $string;
    }
    private function beginGameSequence() {
        $this->sendMessageToChat('A werewolf game has started!');
        $playerListMessage = json_decode($this->sendMarkdownMessage($this->makePlayerList()), true);
        $playerListMessage = intval($playerListMessage['result']['message_id']);
        //wait for joiners
        $i = 0;
        $limit = 2;
        while($i < $limit) {
            if (!doesChatIdExist($this->connection, $this->chatId)) {
                http_response_code(200);
                exit('Chat is no longer exists'); //chat id no longer exists!!
            }
            $timeLeft = ($limit - $i) * 30;
            $this->sendMessageToChat($timeLeft.' seconds left to join!');
            $this->editMessage($playerListMessage, $this->makePlayerList());
            $i++;
            sleep(30);
        }
        $this->sendMessageToChat('Joining period ended! Please wait while the roles are assigned!');
        $players = getTelegramIdsFromChat($this->connection, $this->chatId);
        //shuffle players
        shuffle($players);
        $goodRoles = getGoodRoles($this->connection);
        $evilRoles = getEvilRoles($this->connection);
        $totalPlayers = count($players);
        $baddies = ceil($totalPlayers / 5);
        $assignedBaddies = 0;
        foreach($players as $player) {
            if ($assignedBaddies < $baddies) {
                //set random evil role
                $role = $evilRoles[array_rand($evilRoles)];
                setRole($this->connection, $this->chatId, $player, $role['id']);
                $assignedBaddies++;
            }
            else {
                //set a good role
                $role = $goodRoles[array_rand($goodRoles)];
                setRole($this->connection, $this->chatId, $player, $role['id']);
            }
            //message player
            $this->sendMessageToPlayer('You are a '.$role['name'].chr(10).$role['description'], $player);
        }
        return $this->endGame();
    }
    private function endGame() {
        $this->sendMessageToChat('The game has ended!');
        deleteChatId($this->connection, $this->chatId);
    }
}