<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Class wrapping a text message content from the Telegram API.
 */

require_once(dirname(__FILE__) . '/icontent.php');

class Message implements iContent {

    private $payload;

    public $message_id;
    public $chat_id;

    public $text;
    private $photo;
    private $caption;

    function __construct($payload) {
        $this->payload = $payload;

        $this->message_id = $payload['message_id'];
        $this->chat_id = $payload['chat']['id'];

        if(isset($payload['text'])){
            $this->text = $payload['text'];
        }
        if(isset($payload['photo'])){
            $this->photo = $payload['photo'];
        }
        if(isset($payload['caption'])){
            $this->caption = $payload['caption'];
        }
    }

    function is_text() {
        return isset($this->text);
    }

    /**
     * Gets a cleaned-up text response.
     */
    function get_response() {
        if($this->is_text()) {
            return extract_response($this->text);
        }
        else {
            return '';
        }
    }

    function is_photo() {
        return isset($this->photo);
    }

    function get_photo_small_id() {
        if(isset($this->photo)) {
            return $this->photo[1]['file_id'];
        }
        else {
            return null;
        }
    }

    function get_photo_max_id() {
        if(isset($this->photo)) {
            return $this->photo[sizeof($this->photo)-1]['file_id'];
        }
        else {
            return null;
        }
    }

    // Begin iContent interface

    public function get_sender() {
        return new Sender($this->payload['from']);
    }

    public function get_communicator($context) {
        return new Communicator($this->payload['chat']['id'], $context);
    }

    // End iContent interface

}
