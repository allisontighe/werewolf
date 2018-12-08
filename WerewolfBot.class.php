<?php
require_once 'Bot.class.php';
require_once 'Connection.class.php';
require_once 'functions.php';
require_once 'constants.php';
require_once 'Role.class.php';
class WerewolfBot extends Bot {
    private $connection;
    private $roles = [];
    private $baddies = 0;
    private function loadRoles() {
        $this->roles[RoleId::villager] = new Role(RoleId::villager, 'Villager', false, taskTypes::none, 'The village plower.');
        $this->roles[RoleId::werewolf] = new Role(RoleId::werewolf, 'Werewolf', true, taskTypes::night, 'Stalking your prey at night you kill and devour the bodies of the villagers one by one.');
        $this->roles[RoleId::clown] = new Role(RoleId::clown, 'Clown', false, taskTypes::none, 'You are the Village clown, you play pranks on the villagers at night and though you are good, you are sometimes mistaken for bad');
        $this->roles[RoleId::Drunk] = new Role(RoleId::Drunk, 'Drunk', false, taskTypes::none, 'You are the village drunk, too drunk to do anything at night');
    }
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
            $this->sendMessageToChat('Bye!');
        }
        else if ($command === '/newgame') {
            //check if a game is already running
            if (doesChatIdExist($this->connection, $this->chatId)) {
                http_response_code(200);
                exit('A game is already running!');
            }
            $this->loadRoles();
            $this->beginGameSequence();
        }
        else if ($command === '/eat' && $parameter !== false) {
            if (doesTelegramIdExist($this->connection, $parameter) && !isDead($this->connection, $parameter)) {
                takeActionOn($this->connection, $this->telegramId, $parameter);
                $this->editMessage($this->chatId, $this->messageId, 'Target chosen!');
            }
            else $this->sendMessageToChat('Invalid target!');
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
            else $this->sendMessageToChat('Invalid target!');
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
    private function beginGameSequence() {
        addChat($this->connection, $this->chatId);
        addToGame($this->connection, $this->chatId, $this->telegramId, $this->firstName);
        $this->sendMessageToChat('A werewolf game is starting!');
        $playerListMessage = json_decode($this->sendMessageToChat(makePlayerList($this->connection, $this->chatId)), true);
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
        if (count($players) < 2) {
            //delete chat
            deleteChatId($this->connection, $this->chatId);
            return $this->sendMessageToChat('Joining period ended! Not enough players present to start the game!');
        }
        
        $this->sendMessageToChat('Joining period ended! Please wait while the roles are assigned!');
        //shuffle players
        shuffle($players);
        $roles = divideRoles($this->roles);
        $totalPlayers = count($players);
        $baddies = ceil($totalPlayers / 5);
        foreach($players as $player) {
            if ($this->baddies < $baddies) {
                //set random evil role
                $role = $roles['evil'][array_rand($roles['evil'])];
                setRole($this->connection, $this->chatId, $player, $role->getId());
                $this->baddies++;
            }
            else {
                //set a good role
                $role = $roles['good'][array_rand($roles['good'])];
                setRole($this->connection, $this->chatId, $player, $role->getId());
            }
            //message player
            $this->sendMessageToPlayer('You are a '.$role->getName().chr(10).$role->getDescription(), $player);
        }
        while($this->baddies > 0) {//run game till all assigned baddies die
            $this->prepareNight();
            sleep(60);
            $this->runNight();
            $this->prepareDay();
            sleep(60);
            $this->runDay();
        }
        return $this->endGame();
    }
    private function prepareDay() {
        $this->sendMessageToChat('The day has started! Players have 60 seconds to decide who the culprit is!');
        $players = getPlayerData($this->connection, $this->chatId);
        foreach($players as $player) {
            $this->sendMessageToPlayer('Who do you want to lynch?', $player['telegram_id'], generateKeyboard($player, $players, 'lynch'));
        }
    }
    private function runDay() {
        $players = getPlayerData($this->connection, $this->chatId);
        $this->baddies--; //forcefully decrease now
        foreach($players as $player) {
            //lynch
        }
    }
    private function prepareNight() {
        $this->sendMessageToChat('Night has started! Players have 60 seconds to conduct their actions!');
        $players = getPlayerData($this->connection, $this->chatId);
        foreach($players as $player) {
            if($this->roles[$player['role']]->getTaskType() === taskTypes::night) {
                if ($player['role'] === RoleId::werewolf) {
                    $this->sendMessageToPlayer('Who do you want to eat tonight?', $player['telegram_id'], generateKeyboard($player, $players, 'eat'));
                    if ($player['role'] === RoleId::Clown) {
                        $this->sendMessageToPlayer('Who do you want to prank tonight', $player['telegram_id'], generateKeyboard($player, $players, 'prank'));
                    }
                }
            }
        }
    }
    private function runNight() {
        $players = getPlayerData($this->connection, $this->chatId);
        $someoneDied = false;
        foreach($players as $player) {
            if($this->roles[$player['role']]->getTaskType() === taskTypes::night) {
                //do task depending on role
                if ($player['role'] === RoleId::werewolf) {
                    $targetId = $player['took_action_on'];
                    if ($targetId !== 0) {
                        //kill target
                        $someoneDied = true;
                        killPlayer($this->connection, $targetId);
                        $this->sendMessageToChat(getPlayerName($this->connection, $targetId).' was eaten by the wolf!');
                        $this->sendMessageToPlayer('NOM NOM you were eaten!', $targetId);
                    }
                }
            }
        }
        if (!$someoneDied) {
            $this->sendMessageToChat('The night ended without anyone taking any action');
        }
    }
    private function endGame() {
        $this->sendMessageToChat('The game has ended!');
        deleteChatId($this->connection, $this->chatId);
    }
}