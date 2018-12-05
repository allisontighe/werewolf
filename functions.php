<?php
function doesChatIdExist(Connection $connection, int $chatId): bool {
    $PDOStatement = $connection->prepare('SELECT COUNT(chat_id) FROM players WHERE chat_id = ?');
    $PDOStatement->execute([$chatId]);
    return boolval($PDOStatement->fetchColumn());
}
function doesTelegramIdExist(Connection $connection, int $chatId, int $telegramId): bool {
    $PDOStatement = $connection->prepare('SELECT COUNT(telegram_id) FROM players WHERE chat_id = ? AND telegram_id = ?');
    $PDOStatement->execute([$chatId, $telegramId]);
    return boolval($PDOStatement->fetchColumn());
}
function deleteChatId(Connection $connection, int $chatId) {
    $PDOStatement = $connection->prepare('DELETE FROM chats WHERE chat_id = ?');
    $PDOStatement->execute([$chatId]);
}
function addToGame(Connection $connection, int $chatId, int $telegramId, string $name) {
    $PDOStatement = $connection->prepare('INSERT INTO players (chat_id, telegram_id, name) VALUES (?, ?, ?)');
    $PDOStatement->execute([$chatId, $telegramId, $name]);
}
function addChat(Connection $connection, int $chatId) {
    $PDOStatement = $connection->prepare('INSERT INTO chats (chat_id, status) VALUES (?, 0)');
    $PDOStatement->execute([$chatId]);
}
function getTelegramIdsFromChat(Connection $connection, int $chatId): array {
    $PDOStatement = $connection->prepare('SELECT telegram_id FROM players WHERE chat_id = ?');
    $PDOStatement->execute([$chatId]);
    return $PDOStatement->fetchAll(PDO::FETCH_COLUMN);
}
function getGoodRoles(Connection $connection): array {
    return $connection->query('SELECT id, name, description FROM roles WHERE evil = false')->fetchAll(PDO::FETCH_ASSOC);
}
function getEvilRoles(Connection $connection): array {
    return $connection->query('SELECT id, name, description FROM roles WHERE evil = true')->fetchAll(PDO::FETCH_ASSOC);
}
function setRole(Connection $connection, int $chatId, int $telegramId, int $role) {
    $PDOStatement = $connection->prepare('UPDATE players SET role = ? WHERE chat_id = ? AND telegram_id = ?');
    $PDOStatement->execute([$role, $chatId, $telegramId]);
}