<?php
require_once 'Bot.class.php';
class WerewolfBot extends Bot {
    public function process() {
        if ($this->messageText === '/hi') {
            $this->sendMessage('Hiiii!');
        }
    }
}