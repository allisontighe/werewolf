<?php
require_once 'functions.php';
require_once 'constants.php';
require_once 'Role.class.php';
require_once 'Bot.class.php';
class Game {
    private $connection;
    private $chatId;
    private $roles = [];
    private $baddies = 0;
    private $taskTime;
    public function __construct(Connection $connection, int $chatId) {
        $this->connection = $connection;
        $this->chatId = $chatId;
        $this->taskTime = taskTypes::night;
        $this->loadRoles();
        $this->process();
    }
    private function loadRoles(): void {
        $this->roles[RoleId::villager] = new Role(RoleId::villager, 'Villager', false, taskTypes::none, 'The village plower.');
        $this->roles[RoleId::werewolf] = new Role(RoleId::werewolf, 'Werewolf', true, taskTypes::night, 'Stalking your prey at night you kill and devour the bodies of the villagers one by one.');
        $this->roles[RoleId::clown] = new Role(RoleId::clown, 'Clown', false, taskTypes::none, 'You are the Village clown, you play pranks on the villagers at night and though you are good, you are sometimes mistaken for bad');
        $this->roles[RoleId::drunk] = new Role(RoleId::drunk, 'Drunk', false, taskTypes::none, 'You are the village drunk, too drunk to do anything at night');
    }
    private function process(): void {
        //update chat status
        setStatus($this->connection, $this->chatId, 1);
        //make player list
        $playerListMessage = json_decode($this->sendMessage($this->chatId, makePlayerList($this->connection, $this->chatId)), true);
        updateMessageId($this->connection, $this->chatId, intval($playerListMessage['result']['message_id']));
        //wait for joiners
        $this->waitForJoiners();
        //check if enough players joined
        $players = getTelegramIdsFromChat($this->connection, $this->chatId);
        if (count($players) < 4) {
            //delete chat
            deleteChatId($this->connection, $this->chatId);
            $this->sendMessage($this->chatId, 'Joining period ended! Not enough players present to start the game!');
            exit('Not enough players');
        }
        //start game
        $this->sendMessage($this->chatId, 'Joining period ended! Please wait while the roles are assigned!');
        //run game till all assigned baddies die
        while($this->baddies > 0) {
            $this->prepare();
            $this->run();
            $this->updateTime();
            sleep(60);
        }
        $this->endGame();
    }
    private function prepare() {
        $players = getPlayerData($this->connection, $this->chatId);
        if ($this->taskTime === taskTypes::night) {
            $this->sendMessage($this->chatId, 'Night has started! Players have 60 seconds to conduct their actions!');
        }
        else if ($this->taskTime === taskTypes::day) {
            $this->sendMessage($this->chatId, 'The day has started! Players have 60 seconds to decide who the culprit is!');
        }
        foreach($players as $player) {
            if($this->roles[$player['role']]->getTaskType() === $this->taskTime) {
                if ($player['role'] === RoleId::werewolf) {
                    $this->sendMessage($player['telegram_id'], 'Who do you want to eat tonight?', generateKeyboard($player, $players, 'eat'));
                }
                else if ($player['role'] === RoleId::clown) {
                    $this->sendMessage($player['telegram_id'], 'Who do you want to prank tonight', generateKeyboard($player, $players, 'prank'));
                }
            }
            if ($this->taskTime === taskTypes::day) {
                //lynch options
                $this->sendMessage($player['telegram_id'], 'Who do you want to lynch?', generateKeyboard($player, $players, 'lynch'));
            }
        }
    }
    private function run() {
        $players = getPlayerData($this->connection, $this->chatId);
        foreach($players as $player) {
            if($this->roles[$player['role']]->getTaskType() === $this->taskTime) {
                //do task depending on role
                if ($player['role'] === RoleId::werewolf) {
                    $targetId = $player['took_action_on'];
                    if ($targetId !== 0) {
                        //kill target
                        killPlayer($this->connection, $targetId);
                        $this->sendMessage($this->chatId, getPlayerName($this->connection, $targetId).' was eaten by the wolf!');
                        $this->sendMessage($targetId, 'NOM NOM you were eaten!');
                    }
                }
            }
            if ($this->taskTime === taskTypes::day) {
                //do lynch stuff
                $this->baddies--; //forcefully decrease now
            }
        }
    }
    private function updateTime() {
        //update task time
        if ($this->taskTime === taskTypes::night) $this->taskTime = taskTypes::day;
        else if ($this->taskTime === taskTypes::day) $this->taskTime = taskTypes::evening;
        else if ($this->taskTime === taskTypes::evening) $this->taskTime = taskTypes::night;
    }
    private function waitForJoiners() {
        $limit = getWaitInterval($this->connection, $this->chatId);
        $keyboard = [[['text' => 'Join', 'url' => 'https://t.me/'.BotInfo::username.'?start='.$this->chatId]]];
        for ($i = 0; $i < $limit; $i++) {
            $timeLeft = ($limit - $i) * 30;
            $this->sendMessage($this->chatId, $timeLeft.' seconds left to join!', $keyboard);
            sleep(30);
        }
    }
    private function assignRoles(array $players) {
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
            $this->sendMessage($player, $role->getDescription());
        }
    }
    private function endGame() {
        $this->sendMessage($this->chatId, 'The game has ended!');
        deleteChatId($this->connection, $this->chatId);
    }
    private function sendMessage(int $chatId, string $text, array $keyboard = []) {
        if (empty($keyboard)) return Bot::send('sendMessage', ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown']);
        else return Bot::send('sendMessage', ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown', 'reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
    }
}