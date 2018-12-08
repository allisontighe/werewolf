<?php
require_once 'constants.php';
require_once 'functions.php';
class Bot {
    protected $chatId;
    protected $telegramId;
    protected $firstName;
    protected $messageId;
    protected $messageText;
    protected $queryId;
    public function __construct(array $request) {
        $this->chatId = $request['message']['chat']['id'] ?? $request['callback_query']['message']['chat']['id'] ?? exit('Chat id not set');
        $this->telegramId = $request['message']['from']['id'] ?? $request['callback_query']['from']['id'] ?? exit('Telegram id not set');
        $this->messageId = $request['message']['message_id'] ?? $request['callback_query']['message']['message_id'] ?? exit('Message id not set');
        $this->messageText = $request['message']['text'] ?? $request['callback_query']['data'] ?? exit('Message text is not set');
        $this->firstName = $request['message']['from']['first_name'] ?? $request['callback_query']['from']['first_name'] ?? '';
        $this->queryId = $request['callback_query']['id'] ?? 0;
    }
    public static function send($method, $parameters) {
        $curl = curl_init('https://api.telegram.org/bot'.getenv('BOT_TOKEN').'/'.$method);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    protected function sendEcho(string $message) {
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode(['method' => 'sendMessage', 'chat_id' => $this->chatId, 'text' => $message]);
    }
    protected function editMessage(int $chatId, int $messageId, string $message) {
        Bot::send('editMessageText', ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => $message, 'parse_mode' => 'Markdown']);
    }
    protected function sendMessageToPlayer(string $message, int $playerId, array $keyboard = []) {
        if (empty($keyboard)) Bot::send('sendMessage', ['chat_id' => $playerId, 'text' => $message, 'parse_mode' => 'Markdown']);
        else Bot::send('sendMessage', ['chat_id' => $playerId, 'text' => $message, 'parse_mode' => 'Markdown', 'reply_markup' => json_encode(['inline_keyboard' => $keyboard])]);
    }
}