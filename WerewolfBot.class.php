<?php
require_once 'Bot.class.php';
class WerewolfBot extends Bot {
    public function process() {
        if ($this->messageText === '/hi') {
            $this->sendMessage('Hey!');
        }
        else if ($this->messageText === '/whoisbetter') {
            $this->sendMessage('I am better!');
        }
        else if ($this->messageText === '/test') {
            $this->sendMessage('TEST TEST TEST!');
        }
        else if ($this->messageText === '/silver') {
            $this->sendMessage('Hawk!');
        }
    }
}