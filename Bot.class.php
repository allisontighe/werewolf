<?php
require_once 'config.php';
class Bot {
    protected $chatId;
    protected $userId;
    protected $messageId;
    protected $messageText;
    public function __construct(array $message) {
        $this->chatId = (int)$message['chat']['id'] ?? 0;
        $this->userId = (int)$message['from']['id'] ?? 0;
        $this->messageId = (int)$message['message_id'] ?? 0;
        $this->messageText = $message['text'] ?? 0;
    }
    private function send($method, $parameters) {
        $curl = curl_init('https://api.telegram.org/bot'.Config::BOT_TOKEN.'/'.$method);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    private function sendEcho($method, $parameters) {
        $parameters['method'] = $method;
        header('Content-Type: application/json');
        echo json_encode($parameters);
    }
    protected function sendMessage($message) {
        $this->sendEcho('sendMessage', ['chat_id' => $this->chatId, 'text' => $message]);
    }
}