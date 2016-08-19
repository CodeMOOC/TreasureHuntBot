<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Class wrapping the bot's context in this run.
 */

require_once('lib_database.php');

require_once('incoming_message.php');

class Context {

    private $message;

    private $group_id;
    private $group_name;

    private $group_state;
    private $assigned_riddle_id;

    /**
     * Construct Context class.
     * @param §message IncomingMessage.
     */
    function __construct($message) {
        if(!($message instanceof IncomingMessage))
            die('Message variable is not an IncomingMessage instance');

        $this->message = $message;

        $last_group = db_row_query("SELECT id, name FROM `groups` WHERE `leader_telegram_id` = {$this->message->from_id} ORDER BY `registration` DESC LIMIT 1");
        if(!$last_group) {
            return;
        }
        $this->group_id = $last_group[0];
        $this->group_name = $last_group[1];

        $group_state= db_row_query("SELECT `participants_count`, `state`, `assigned_riddle_id` FROM `status` WHERE `game_id` = " . CURRENT_GAME_ID . " AND `group_id` = {$this->group_id}");
        if($group_state) {
            //Existing group state
            $this->group_state = $group_state[1];
            $this->assigned_riddle_id = $group_state[2];
        }
        else {
            //Group has no state, assign defaults
            $this->group_state = 'new';
            $this->assigned_riddle_id = null;
        }
    }

    /* True if the talking user is an admin */
    function is_admin() {
        return false;
    }

    /* The running game ID */
    function get_game_id() {
        return CURRENT_GAME_ID;
    }

    function get_user_id() {
        return $this->message->from_id;
    }

    function get_chat_id() {
        return $this->message->chat_id;
    }

    function get_message() {
        return $this->message;
    }

    function get_group_id() {
        return $this->group_id;
    }

    function get_group_name() {
        return $this->group_name;
    }

}
