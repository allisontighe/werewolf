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
        elseif ($this-> messageText === '/arc') {
            $this-> sendMessage ('Arc is Awesome');
        }
        elseif ($this-> messageText === '/silversnow') {
            $this-> sendMessage ('Silver loves Snowy');
        }
        elseif ($this-> messageText === '/snowhawk') {
            $this-> sendMessage ('Snowy loves silver');
        }
        elseif ($this-> messageText === '/iloveyou') {
            $this-> sendMessage === "I love you more";
        }
        elseif ($this-> messageText ==='/morning') {
            $this-> sendMessage ==- 'good morning sunshine';
        }elseif ($this-> messageText ==='/night'){
            $this-> sendMessage === 'goodnight moonlight';
        }
        }
    }