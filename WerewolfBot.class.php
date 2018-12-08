<?php
require_once 'Bot.class.php';
require_once 'Connection.class.php';
require_once 'functions.php';
require_once 'constants.php';
require_once 'Role.class.php';
class WerewolfBot extends Bot {
    private $connection;
    private $roles = [];
    public function __construct(array $message) {
        $this->connection = new Connection;
        parent::__construct($message);
    }
    public function process() {
        if ($this->messageText === '/hi') {
            $this->sendMessageToChat('Bye!');
        }
        else if ($this->messageText === '/newgame') {
            //check if a game is already running
            if (doesChatIdExist($this->connection, $this->chatId)) {
                http_response_code(200);
                exit('A game is already running!');
            }
            $this->loadRoles();
            $this->beginGameSequence();
        }
        else if ($this->messageText === '/join') {
            //check if already joined
            if (doesTelegramIdExist($this->connection, $this->chatId, $this->telegramId)) {
                $this->sendEcho('You have already joined the game!');
            }
            else {
                addToGame($this->connection, $this->chatId, $this->telegramId, $this->firstName);
                $playerListMessageId = getMessageId($this->connection, $this->chatId);
                if ($playerListMessageId !== 0) {
                    $this->editMessage($playerListMessageId, $this->makePlayerList());
                }
                $this->sendEcho('Added '.$this->firstName.' to the game!');
            }
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
    private function loadRoles() {
        //always retain the numerical order!
        $this->roles[] = new Role(0, 'Villager', false, taskTypes::none, 'The village plower.');
        $this->roles[] = new Role(1, 'Werewolf', true, taskTypes::night, 'The everyday baddie.');
        $this->roles[] = new Role(2, 'Clown', false, taskTypes::none, 'You are the Village clown, you play pranks on the villagers at night and though you are good, you are sometimes mistaken for bad');
    }
    private function divideRoles(): array {
        //divide into good or evil
        $dividedRoles = ['good' => [], 'evil' => []];
        foreach($this->roles as $role) {
            if ($role->getEvil()) {
                $dividedRoles['evil'][] = $role; //evil role
            }
            else {
                $dividedRoles['good'][] = $role; //good role
            }
        }
        return $dividedRoles;
    }
    private function beginGameSequence() {
        addChat($this->connection, $this->chatId);
        addToGame($this->connection, $this->chatId, $this->telegramId, $this->firstName);
        $this->sendMessageToChat('A werewolf game is starting!');
        $playerListMessage = json_decode($this->sendMarkdownMessage($this->makePlayerList()), true);
        $playerListMessage = intval($playerListMessage['result']['message_id']);
        updateMessageId($this->connection, $this->chatId, $playerListMessage);
        //wait for joiners
        $i = 0;
        $limit = 2;
        while ($i < $limit) {
            $timeLeft = ($limit - $i) * 30;
            $this->sendMessageToChat($timeLeft.' seconds left to join!');
            $i++;
            sleep(30);
        }
        //check if enough players joined
        $players = getTelegramIdsFromChat($this->connection, $this->chatId);
        if (count($players) < 5 && false) {//disable for now
            return $this->sendMessageToChat('Joining period ended! Not enough players present to start the game!');
        }
        
        $this->sendMessageToChat('Joining period ended! Please wait while the roles are assigned!');
        //shuffle players
        shuffle($players);
        $roles = $this->divideRoles();
        $totalPlayers = count($players);
        $baddies = ceil($totalPlayers / 5);
        $assignedBaddies = 0;
        foreach($players as $player) {
            if ($assignedBaddies < $baddies) {
                //set random evil role
                $role = $roles['evil'][array_rand($roles['evil'])];
                setRole($this->connection, $this->chatId, $player, $role->getId());
                $assignedBaddies++;
            }
            else {
                //set a good role
                $role = $roles['good'][array_rand($roles['good'])];
                setRole($this->connection, $this->chatId, $player, $role->getId());
            }
            //message player
            $this->sendMessageToPlayer('You are a '.$role->getName().chr(10).$role->getDescription(), $player);
        }
        /* This isnt ready yet!!
        while($assignedBaddies > 0) {//run game till all assigned baddies die
            $this->runNight();
        }*/
        return $this->endGame();
    }
    private function runNight() {
        $players = getPlayerData($this->connection, $this->chatId);
        foreach($players as $player) {
            if($this->roles[$player['role']]->getTaskType() === taskTypes::night) {
                //do task depending on role
            }
        }
    }
    private function endGame() {
        $this->sendMessageToChat('The game has ended!');
        deleteChatId($this->connection, $this->chatId);
    }
}