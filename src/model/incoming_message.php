<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Class wrapping a message update from the Telegram API.
 */

class IncomingMessage {

    const TELEGRAM_MESSAGE = 'message_id';
    const TELEGRAM_CHAT = 'chat';
    const TELEGRAM_ID = 'id';
    const TELEGRAM_DATE = 'date';
    const TELEGRAM_FROM = 'from';
    const TELEGRAM_TEXT = 'text';
    const TELEGRAM_PHOTO = 'photo';
    const TELEGRAM_CAPTION = 'caption';
    const TELEGRAM_ENTITIES = 'entities';
    const TELEGRAM_LOCATION = 'location';
    const TELEGRAM_FIRSTNAME = 'first_name';
    const TELEGRAM_LASTNAME = 'last_name';

    public $message_id;
    public $date;
    public $chat;
    public $chat_id;
    public $from;
    public $from_id;
    public $text;
    public $photo;
    public $caption;
    public $entities;
    public $location;

    function __construct($message) {
        $this->message_id = $message[self::TELEGRAM_MESSAGE];
        $this->date = $message[self::TELEGRAM_DATE];
        $this->chat = $message[self::TELEGRAM_CHAT];
        $this->chat_id = $message[self::TELEGRAM_CHAT][self::TELEGRAM_ID];
        $this->from = $message[self::TELEGRAM_FROM];
        $this->from_id = $message[self::TELEGRAM_FROM][self::TELEGRAM_ID];

        if(isset($message[self::TELEGRAM_TEXT])){
            $this->text = $message[self::TELEGRAM_TEXT];
        }

        if(isset($message[self::TELEGRAM_PHOTO])){
            $this->photo = $message[self::TELEGRAM_PHOTO];
        }

        if(isset($message[self::TELEGRAM_CAPTION])){
            $this->caption = $message[self::TELEGRAM_CAPTION];
        }

        if(isset($message[self::TELEGRAM_ENTITIES])){
            $this->entities = $message[self::TELEGRAM_ENTITIES];
        }

        if(isset($message[self::TELEGRAM_LOCATION])){
            $this->location = $message[self::TELEGRAM_LOCATION];
        }
    }
}
