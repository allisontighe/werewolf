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
    private $players = 0;
    private $taskTime;
    private $day = 0;
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
        $this->roles[RoleId::clown] = new Role(RoleId::clown, 'Clown', false, taskTypes::night, 'You are the Village clown, you play pranks on the villagers at night and though you are good, you are sometimes mistaken for bad');
        $this->roles[RoleId::drunk] = new Role(RoleId::drunk, 'Drunk', false, taskTypes::none, 'You are the village drunk, too drunk to do anything at night');
        $this->roles[RoleId::slacker] = new Role(RoleId::slacker, 'Slacker', false, taskTypes::none, 'You are a slacker! The slacker joins the game a day later than other players');
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
        $this->players = count($players);
        if ($this->players < 4) {
            //delete chat
            deleteChatId($this->connection, $this->chatId);
            $this->sendMessage($this->chatId, 'Joining period ended! Not enough players present to start the game!');
        }
        else {
            //start game
            $this->sendMessage($this->chatId, 'Joining period ended! Please wait while the roles are assigned!');
            //assign roles
            $this->assignRoles($players);
            //run game till all assigned baddies die
            while($this->baddies > 0 && $this->players > 2) {
                $this->prepare();
                sleep(60);
                $this->run();
                $this->updateTime();
            }
            $this->endGame();
        }
    }
    private function prepare() {
        $players = getPlayerData($this->connection, $this->chatId);
        if ($this->taskTime === taskTypes::night) {
            $this->sendMessage($this->chatId, 'Night has started! Players have 60 seconds to conduct their actions!');
        }
        else if ($this->taskTime === taskTypes::day) {
            $this->sendMessage($this->chatId, 'The day has started! Players have 60 seconds to decide who the culprit is!');
        }
        else if ($this->taskTime === taskTypes::evening) {
            $this->sendMessage($this->chatId, 'Its evening time! Players have 60 seconds to conduct their actions!');
        }
        //remove offline players
        foreach($players as $key => $player) {
            if ($player['status'] === Status::offline) unset($players[$key]);
        }
        foreach($players as $player) {
            if($this->roles[$player['role']]->getTaskType() === $this->taskTime) {
                if ($player['role'] === RoleId::werewolf) {
                    $this->sendMessage($player['telegram_id'], 'Who do you want to eat tonight?', generateKeyboard($player, $players));
                }
                else if ($player['role'] === RoleId::clown) {
                    $this->sendMessage($player['telegram_id'], 'Who do you want to prank tonight?', generateKeyboard($player, $players));
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
        $lynchArray = [];
        foreach($players as $player) {
            if($this->roles[$player['role']]->getTaskType() === $this->taskTime) {
                //do task depending on role
                if ($player['role'] === RoleId::werewolf) {
                    $targetId = $player['took_action_on'];
                    if ($targetId !== 0) {
                        //kill target
                        killPlayer($this->connection, $targetId);
                        $this->players--;
                        //check if baddie, if yes decrease baddie count
                        $index = array_search($targetId, array_column($players, 'telegram_id'));
                        if ($this->roles[$players[$index]['role']]->getEvil()) {
                            $this->baddies--;
                        }
                        $this->sendMessage($this->chatId, $players[$index]['name'].' was eaten by the wolf! '.$players[$index]['name'].' was a '.$this->roles[$players[$index]['role']]->getName());
                        $this->sendMessage($targetId, 'NOM NOM you were eaten!');
                    }
                }
                else if ($player['role'] === RoleId::clown) {
                    $targetId = $player['took_action_on'];
                    if($targetId !== 0) {
                        //prank target
                        $this->sendMessage($targetId, 'You wake up to the sound of a door slamming, as you turn on the light you see your house is covered in honey, the clown has pranked you!');
                    }
                }
            }
            if ($player['role'] === RoleId::slacker && $player['status'] === Status::offline && $this->day > 0) {
                setPlayerStatus($this->connection, $player['telegram_id'], Status::none);
                $this->sendMessage($this->chatId, '['.$player['name'].'](tg://user?id='.$player['telegram_id'].') joins the game!');
            }
            if ($this->taskTime === taskTypes::day) {
                //do lynch stuff
                if (array_key_exists($player['took_action_on'], $lynchArray)) {
                    $lynchArray[$player['took_action_on']]++;
                }
                else $lynchArray[$player['took_action_on']] = 1;
            }
        }
        if ($this->taskTime === taskTypes::day) {
            //get max voted for player key
            $lynchIds = array_keys($lynchArray, max($lynchArray));
            //see if more than one was max
            if (count($lynchIds) > 1) {
                //if so, dont lynch
                $this->sendMessage($this->chatId, 'The villagers were unable to come up with a decision!');
            }
            else {
                //lynch!
                killPlayer($this->connection, $lynchIds[0]);
                $this->players--;
                //check if player was baddie
                $playerIndex = array_search($lynchIds[0], array_column($players, 'telegram_id'));
                if ($this->roles[$players[$playerIndex]['role']]->getEvil()) {
                    //decrease baddies
                    $this->baddies--;
                }
                //announce
                $this->sendMessage($lynchIds[0], 'You were lynched!');
                $this->sendMessage($this->chatId, $players[$playerIndex]['name'].' was lynched! '.$players[$playerIndex]['name'].' was a '.$this->roles[$players[$playerIndex]['role']]->getName().'!');
            }
        }
        //clear actions
        clearActions($this->connection, $this->chatId);
    }
    private function updateTime() {
        //update task time
        if ($this->taskTime === taskTypes::night) $this->taskTime = taskTypes::day;
        else if ($this->taskTime === taskTypes::day) $this->taskTime = taskTypes::evening;
        else if ($this->taskTime === taskTypes::evening) $this->taskTime = taskTypes::night;
        //increase day
        $this->day++;
    }
    private function waitForJoiners() {
        $keyboard = [[['text' => 'Join', 'url' => 'https://t.me/'.BotInfo::username.'?start='.$this->chatId]]];
        $limit = getWaitInterval($this->connection, $this->chatId);
        $messages = [];
        while ($limit > 0) {
            $messages[] = $this->sendMessage($this->chatId, ($limit * Interval::join).' seconds left to join!', $keyboard);
            sleep(Interval::join);
            //decrease interval
            changeWaitInterval($this->connection, $this->chatId, -1);
            //get new interval
            $limit = getWaitInterval($this->connection, $this->chatId);
        }
        foreach ($messages as $message) {
            //decode
            $message = json_decode($message, true);
            //delete join message
            $this->deleteMessage($message['result']['message_id'], $this->chatId);
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
                if ($role->getId() === RoleId::slacker) {
                    setPlayerStatus($this->connection, $player, Status::offline); //set offline status for slacker
                }
            }
            //message player
            $this->sendMessage($player, $role->getDescription());
        }
    }
    private function endGame() {
        $players = getAllPlayerData($this->connection, $this->chatId);
        $text = '';
        foreach($players as $player) {
            $text .= '['.$player['name'].'](tg://user?id='.$player['telegram_id'].') - '.$this->roles[$player['role']]->getName();
            if ($player['dead']) $text .= ' (Dead)';
            else $text .= ' (Alive)';
            if ($this->roles[$player['role']]->getEvil() && $this->baddies > 0 || !$this->roles[$player['role']]->getEvil()) $text .= ' *Won*';
            else $text .= ' *Lost*';
            $text .= chr(10);
        }
        $this->sendMessage($this->chatId, $text);
        $this->sendMessage($this->chatId, 'The game has ended!');
        deleteChatId($this->connection, $this->chatId);
    }
    private function sendMessage(int $chatId, string $text, array $keyboard = []) {
        if (empty($keyboard)) return Bot::send('sendMessage', ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown']);
        else return Bot::send('sendMessage', ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown', 'reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
    }
    private function deleteMessage(int $messageId, int $chatId){
        Bot::send('deleteMessage', ['chat_id' => $chatId, 'message_id' => $messageId]); 
    }
}