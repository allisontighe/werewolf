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