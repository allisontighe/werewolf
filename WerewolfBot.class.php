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
        else if (strpos($this->messageText, '/start ') === 0) {//check if it exists in string
            //parse request
            $spaceIndex = strpos($this->messageText, ' ');
            $chatId = trim(substr($this->messageText, $spaceIndex));
            if (doesChatIdExist($this->connection, $chatId)) {
                //check if already joined
                if (doesTelegramIdExist($this->connection, $chatId, $this->telegramId)) {
                    $this->sendEcho('You have already joined the game!');
                }
                else {
                    addToGame($this->connection, $chatId, $this->telegramId, $this->firstName);
                    $playerListMessageId = getMessageId($this->connection, $chatId);
                    if ($playerListMessageId !== 0) {
                        $this->editMessage($chatId, $playerListMessageId, $this->makePlayerList($chatId));
                    }
                    $this->sendEcho('You have been added to the game!');
                }
            }
            else {
                $this->sendEcho('A game is not currently running.');
            }
        }
    }
    private function makePlayerList(int $chatId): string {
        $players = getTelegramNamesFromChat($this->connection, $chatId);
        $string = '*Player list (Total: '.count($players).')*'.chr(10);
        foreach($players as $player) {
            $string .= '`'.$player.'`'.chr(10);
        }
        return $string;
    }
    private function loadRoles() {
        $this->roles[RoleId::villager] = new Role(RoleId::villager, 'Villager', false, taskTypes::none, 'The village plower.');
        $this->roles[RoleId::werewolf] = new Role(RoleId::werewolf, 'Werewolf', true, taskTypes::night, 'The everyday baddie.');
        $this->roles[RoleId::clown] = new Role(RoleId::clown, 'Clown', false, taskTypes::none, 'You are the Village clown, you play pranks on the villagers at night and though you are good, you are sometimes mistaken for bad');
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
        $playerListMessage = json_decode($this->sendMessageToChat($this->makePlayerList($this->chatId)), true);
        $playerListMessage = intval($playerListMessage['result']['message_id']);
        updateMessageId($this->connection, $this->chatId, $playerListMessage);
        //wait for joiners
        $i = 0;
        $limit = 2;
        $keyboard = [[['text' => 'Join', 'url' => 'https://t.me/'.BotInfo::username.'?start='.$this->chatId]]];
        while ($i < $limit) {
            $timeLeft = ($limit - $i) * 30;
            $this->sendMessageToChat($timeLeft.' seconds left to join!', $keyboard);
            $i++;
            sleep(30);
        }
        //check if enough players joined
        $players = getTelegramIdsFromChat($this->connection, $this->chatId);
        if (count($players) < 5) {
            //delete chat
            deleteChatId($this->connection, $this->chatId);
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
        while($assignedBaddies > 0) {//run game till all assigned baddies die
            $this->sendMessageToChat('Night has started! Players have 60 seconds to conduct their actions!');
            $this->prepareNight();
            sleep(60);
            $this->runNight();
            if (true) {//force close loop for now
                break;
            }
        }
        return $this->endGame();
    }
    private function prepareNight() {
        $players = getPlayerData($this->connection, $this->chatId);
        foreach($players as $player) {
            if($this->roles[$player['role']]->getTaskType() === taskTypes::night) {
                if ($player['role'] === RoleId::werewolf) {
                    
                }
            }
        }
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