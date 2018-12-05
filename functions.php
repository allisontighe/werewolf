<?php
function doesChatIdExist(Connection $connection, int $chatId): bool {
    $PDOStatement = $connection->prepare('SELECT COUNT(chatId) FROM players WHERE chatId = ?');
    $PDOStatement->execute([$chatId]);
    return boolval($PDOStatement->fetchColumn());
}
function doesTelegramIdExist(Connection $connection, int $chatId, int $telegramId): bool {
    $PDOStatement = $connection->prepare('SELECT COUNT(telegramId) FROM players WHERE chatId = ? AND telegramId = ?');
    $PDOStatement->execute([$chatId, $telegramId]);
    return boolval($PDOStatement->fetchColumn());
}
function deleteChatId(Connection $connection, int $chatId) {
    $PDOStatement = $connection->prepare('DELETE FROM players WHERE chatId = ?');
    $PDOStatement->execute([$chatId]);
}
function addToGame(Connection $connection, int $chatId, int $telegramId, string $name) {
    $PDOStatement = $connection->prepare('INSERT INTO players (chatId, telegramId, name) VALUES (?, ?, ?)');
    $PDOStatement->execute([$chatId, $telegramId, $name]);
}