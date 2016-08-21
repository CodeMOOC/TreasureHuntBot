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
    const TELEGRAM_FILEID = 'file_id';

    private $payload;

    public $message_id;
    public $date;
    public $chat_id;
    public $from_id;

    public $text;

    private $photo;
    private $caption;

    function __construct($message) {
        $this->payload = $message;

        $this->message_id = $message[self::TELEGRAM_MESSAGE];
        $this->date = new DateTime('@' . $message[self::TELEGRAM_DATE]);
        $this->chat_id = $message[self::TELEGRAM_CHAT][self::TELEGRAM_ID];
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
    }

    function is_text() {
        return isset($this->text);
    }

    function is_photo() {
        return isset($this->payload[self::TELEGRAM_PHOTO]);
    }

    function get_photo_small_id() {
        $photo = $this->payload[self::TELEGRAM_PHOTO];
        if(!isset($photo)) {
            return 0;
        }

        return $photo[1][self::TELEGRAM_FILEID];
    }

    function get_photo_large_id() {
        $photo = $this->payload[self::TELEGRAM_PHOTO];
        if(!isset($photo)) {
            return 0;
        }

        return $photo[sizeof($photo)-1][self::TELEGRAM_FILEID];
    }

    function get_full_sender_name() {
        $parts = array(
            $this->payload['from']['first_name'],
            $this->payload['from']['last_name']
        );

        return implode(' ', array_filter($parts));
    }

}