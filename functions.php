<?php
function doesChatIdExist(Connection $connection, int $chatId): bool {
    $PDOStatement = $connection->prepare('SELECT COUNT(chatId) FROM players WHERE chatId = ?');
    $PDOStatement->execute([$chatId]);
    return boolval($PDOStatement->fetchColumn());
}