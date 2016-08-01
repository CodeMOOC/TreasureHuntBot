<?php

class Configuration {

    var $message_id;
    var $date;
    var $chat;
    var $chat_id;
    var $from;
    var $text;
    var $photo;
    var $caption;
    var $entities;
    var $location;

    function __construct($message) {
        $this->messageId = $message['chat']['id'];
        $this->date = $message['date'];
        $this->chat = $message['chat'];
        $this->chat_id = $message['chat']['id'];
        $this->from = $message['from'];
        $this->from_id = $message['from']['id'];

        if(isset($message['text'])){
            $this->text = $message['text'];
        }

        if(isset($message['photo'])){
            $this->photo = $message['photo'];
        }

        if(isset($message['caption'])){
            $this->caption = $message['caption'];
        }

        if(isset($message['entities'])){
            $this->entities = $message['entities'];
        }

        if(isset($message['location'])){
            $this->entities = $message['location'];
        }
    }
}