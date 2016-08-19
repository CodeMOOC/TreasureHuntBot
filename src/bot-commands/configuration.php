<?php
CONST TELEGRAM_MESSAGE = "message_id";
CONST TELEGRAM_CHAT = "chat";
CONST TELEGRAM_ID = "id";
CONST TELEGRAM_DATE = "date";
CONST TELEGRAM_FROM = "from";
CONST TELEGRAM_TEXT = "text";
CONST TELEGRAM_PHOTO = "photo";
CONST TELEGRAM_CAPTION = "caption";
CONST TELEGRAM_ENTITIES = "entities";
CONST TELEGRAM_LOCATION = "location";
CONST TELEGRAM_FIRSTNAME = "first_name";
CONST TELEGRAM_LASTNAME = "last_name";

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
        $this->message_id = $message[TELEGRAM_MESSAGE];
        $this->date = $message[TELEGRAM_DATE];
        $this->chat = $message[TELEGRAM_CHAT];
        $this->chat_id = $message[TELEGRAM_CHAT][TELEGRAM_ID];
        $this->from = $message[TELEGRAM_FROM];
        $this->from_id = $message[TELEGRAM_FROM][TELEGRAM_ID];

        if(isset($message[TELEGRAM_TEXT])){
            $this->text = $message[TELEGRAM_TEXT];
        }

        if(isset($message[TELEGRAM_PHOTO])){
            $this->photo = $message[TELEGRAM_PHOTO];
        }

        if(isset($message[TELEGRAM_CAPTION])){
            $this->caption = $message[TELEGRAM_CAPTION];
        }

        if(isset($message[TELEGRAM_ENTITIES])){
            $this->entities = $message[TELEGRAM_ENTITIES];
        }

        if(isset($message[TELEGRAM_LOCATION])){
            $this->entities = $message[TELEGRAM_LOCATION];
        }
    }
}