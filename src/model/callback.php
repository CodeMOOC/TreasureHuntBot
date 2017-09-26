<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Callback data wrapper.
 */

require_once(dirname(__FILE__) . '/icontent.php');

class Callback implements iContent {

    private $payload;

    public $callback_id;
    public $message_id;
    public $data;

    function __construct($payload) {
        $this->payload = $payload;

        $this->callback_id = $payload['id'];
        $this->message_id = $payload['message']['message_id'];
        $this->data = $payload['data'];
    }

    // Begin iContent interface

    public function get_sender() {
        return new Sender($this->payload['from']);
    }

    public function get_communicator($context) {
        return new Communicator($this->payload['message']['chat']['id'], $context);
    }

    // End iContent interface

}
