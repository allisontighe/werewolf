<?php
class Bot {
    protected $chatId;
    protected $telegramId;
    protected $firstName;
    protected $messageId;
    protected $messageText;
    private const TOKEN = '682186483:AAEfr0p9f2wzYvtA6ay816aQ3HJnG7kW77E';
    public function __construct(array $message) {
        $this->chatId = (int)$message['chat']['id'] ?? 0;
        $this->telegramId = (int)$message['from']['id'] ?? 0;
        $this->messageId = (int)$message['message_id'] ?? 0;
        $this->messageText = $message['text'] ?? '';
        $this->firstName = $message['from']['first_name'] ?? '';
    }
    private function send($method, $parameters) {
        $curl = curl_init('https://api.telegram.org/bot'.self::TOKEN.'/'.$method);
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
        ob_flush();
        flush();
    }
    protected function sendMessageToChat(string $message) {
        $this->send('sendMessage', ['chat_id' => $this->chatId, 'text' => $message]);
    }
    protected function sendMarkdownMessage(string $message) {
        $this->send('sendMessage', ['chat_id' => $this->chatId, 'text' => $message, 'parse_mode' => 'Markdown']);
    }
    protected function sendMessageToPlayer(string $message, int $playerId) {
        $this->send('sendMessage', ['chat_id' => $playerId, 'text' => $message]);
    }
    protected function editMessage(int $messageId, string $message) {
        $this->send('editMessageText', ['chat_id' => $this->chatId, 'message_id' => $messageId, 'text' => $message]);
    }
}