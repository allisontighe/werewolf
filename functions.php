<?php
function doesChatIdExist(Connection $connection, int $chatId): bool {
    $PDOStatement = $connection->prepare('SELECT COUNT(chat_id) FROM chats WHERE chat_id = ?');
    $PDOStatement->execute([$chatId]);
    return boolval($PDOStatement->fetchColumn());
}
function doesTelegramIdExist(Connection $connection, int $telegramId): bool {
    $PDOStatement = $connection->prepare('SELECT COUNT(telegram_id) FROM players WHERE telegram_id = ?');
    $PDOStatement->execute([$telegramId]);
    return boolval($PDOStatement->fetchColumn());
}
function deleteChatId(Connection $connection, int $chatId): void {
    $PDOStatement = $connection->prepare('DELETE FROM chats WHERE chat_id = ?');
    $PDOStatement->execute([$chatId]);
}
function addToGame(Connection $connection, int $chatId, int $telegramId, string $name): void {
    $PDOStatement = $connection->prepare('INSERT INTO players (chat_id, telegram_id, name, role) VALUES (?, ?, ?, 0)');
    $PDOStatement->execute([$chatId, $telegramId, $name]);
}
function addChat(Connection $connection, int $chatId): void {
    $PDOStatement = $connection->prepare('INSERT INTO chats (chat_id, status, message_id) VALUES (?, 0, 0)');
    $PDOStatement->execute([$chatId]);
}
function updateMessageId(Connection $connection, int $chatId, int $messageId): void {
    $PDOStatement = $connection->prepare('UPDATE chats SET status = 1, message_id = ? WHERE chat_id = ?');
    $PDOStatement->execute([$messageId, $chatId]);
}
function getMessageId(Connection $connection, int $chatId): int {
    $PDOStatement = $connection->prepare('SELECT message_id FROM chats WHERE chat_id = ?');
    $PDOStatement->execute([$chatId]);
    return (int)$PDOStatement->fetchColumn();
}
function getTelegramIdsFromChat(Connection $connection, int $chatId): array {
    $PDOStatement = $connection->prepare('SELECT telegram_id FROM players WHERE chat_id = ?');
    $PDOStatement->execute([$chatId]);
    return $PDOStatement->fetchAll(PDO::FETCH_COLUMN);
}
function setRole(Connection $connection, int $chatId, int $telegramId, int $role): void {
    $PDOStatement = $connection->prepare('UPDATE players SET role = ? WHERE chat_id = ? AND telegram_id = ?');
    $PDOStatement->execute([$role, $chatId, $telegramId]);
}
function getTelegramNamesFromChat(Connection $connection, int $chatId): array {
    $PDOStatement = $connection->prepare('SELECT name FROM players WHERE chat_id = ?');
    $PDOStatement->execute([$chatId]);
    return $PDOStatement->fetchAll(PDO::FETCH_COLUMN);
}
function getPlayerData(Connection $connection, int $chatId): array {
    $PDOStatement = $connection->prepare('SELECT name, role, took_action_on, telegram_id FROM players WHERE chat_id = ? AND dead = false');
    $PDOStatement->execute([$chatId]);
    return $PDOStatement->fetchAll(PDO::FETCH_ASSOC);
}
function getAllPlayerData(Connection $connection, int $chatId): array {
    $PDOStatement = $connection->prepare('SELECT name, role, telegram_id, dead FROM players WHERE chat_id = ?');
    $PDOStatement->execute([$chatId]);
    return $PDOStatement->fetchAll(PDO::FETCH_ASSOC);
}
function isDead(Connection $connection, int $telegramId): bool {
    $PDOStatement = $connection->prepare('SELECT dead FROM players WHERE telegram_id = ?');
    $PDOStatement->execute([$telegramId]);
    return boolval($PDOStatement->fetchColumn());
}
function takeActionOn(Connection $connection, int $telegramId, int $targetId): void {
    $PDOStatement = $connection->prepare('UPDATE players SET took_action_on = ?, last_action = CURRENT_TIMESTAMP WHERE telegram_id = ?');
    $PDOStatement->execute([$targetId, $telegramId]);
}
function clearActions(Connection $connection, int $chatId): void {
    $PDOStatement = $connection->prepare('UPDATE players SET took_action_on = 0 WHERE chat_id = ?');
    $PDOStatement->execute([$chatId]);
}
function getPlayerName(Connection $connection, int $telegramId): string {
    $PDOStatement = $connection->prepare('SELECT name FROM players WHERE telegram_id = ?');
    $PDOStatement->execute([$telegramId]);
    return $PDOStatement->fetchColumn();
}
function getChatId(Connection $connection, int $telegramId): int {
    $PDOStatement = $connection->prepare('SELECT chat_id FROM players WHERE telegram_id = ?');
    $PDOStatement->execute([$telegramId]);
    return intval($PDOStatement->fetchColumn());
}
function killPlayer(Connection $connection, int $telegramId): void {
    $PDOStatement = $connection->prepare('UPDATE players SET dead = true WHERE telegram_id = ?');
    $PDOStatement->execute([$telegramId]);
}
function getWaitInterval(Connection $connection, int $chatId): string {
    $PDOStatement = $connection->prepare('SELECT wait_interval FROM chats WHERE chat_id = ?');
    $PDOStatement->execute([$chatId]);
    return intval($PDOStatement->fetchColumn());
}
function changeWaitInterval(Connection $connection, int $chatId, int $value): void {
    $PDOStatement = $connection->prepare('UPDATE chats SET wait_interval = wait_interval + ? WHERE chat_id = ?');
    $PDOStatement->execute([$value, $chatId]);
}
function setWaitInterval(Connection $connection, int $chatId, int $value): void {
    $PDOStatement = $connection->prepare('UPDATE chats SET wait_interval = ? WHERE chat_id = ?');
    $PDOStatement->execute([$value, $chatId]);
}
function setStatus(Connection $connection, int $chatId, int $status): void {
    $PDOStatement = $connection->prepare('UPDATE chats SET status = ? WHERE chat_id = ?');
    $PDOStatement->execute([$status, $chatId]);
}
function makePlayerList(Connection $connection, int $chatId): string {
    $players = getTelegramNamesFromChat($connection, $chatId);
    $string = '*Player list (Total: '.count($players).')*'.chr(10);
    foreach($players as $player) {
        $string .= '`'.$player.'`'.chr(10);
    }
    return $string;
}
function generateKeyboard(array $player, array $allPlayers, string $command): array {//generates keyboard with other players
    $keyboard = [];
    foreach($allPlayers as $otherPlayer) {
        if ($otherPlayer['telegram_id'] !== $player['telegram_id']) {
            $keyboard[] = [['text' => $otherPlayer['name'], 'callback_data' => '/'.$command.' '.$otherPlayer['telegram_id']]];
        }
    }
    return $keyboard;
}
function divideRoles(array $roles): array {
    //divide into good or evil
    $dividedRoles = ['good' => [], 'evil' => []];
    foreach($roles as $role) {
        if ($role->getEvil()) {
            $dividedRoles['evil'][] = $role; //evil role
        }
        else {
            $dividedRoles['good'][] = $role; //good role
        }
    }
    return $dividedRoles;
}
function parseMessageText(string $messageText): array {
    $array = ['command' => '', 'parameter' => ''];
    $spaceIndex = strpos($messageText, ' ');
    if ($spaceIndex !== false) {
        $array['parameter'] = substr($messageText, $spaceIndex);
        $array['command'] = str_replace($array['parameter'], '', $messageText);
        $array['parameter'] = trim($array['parameter']);
    }
    else {
        $array['command'] = $messageText;
        $array['parameter'] = false;
    }
    return $array;
}