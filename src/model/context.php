<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Class wrapping the bot's context in this run.
 */

require_once('lib.php');

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
        $this->refresh();
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

    /**
     * Gets a cleaned-up response from the user, if any.
     */
    function get_response() {
        $text = $this->message->text;
        if($text)
            return extract_response($text);
        else
            return '';
    }

    function get_group_id() {
        return $this->group_id;
    }

    function get_group_name() {
        return $this->group_name;
    }

    function get_group_state() {
        return $this->group_state;
    }

    /**
     * Replies to the current incoming message.
     * Enables markdown parsing and disables web previews by default.
     */
    function reply($message, $additional_values = null) {
        $hydration_values = array(
            '%FULL_NAME%' => $this->get_message()->get_full_sender_name(),
            '%GROUP_NAME%' => $this->get_group_name()
        );

        $hydrated = hydrate($message, unite_arrays($hydration_values, $additional_values));

        return telegram_send_message(
            $this->get_chat_id(),
            $hydrated,
            array(
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true
            )
        );
    }

    /**
     * Refreshes information about the context from the DB.
     */
    function refresh() {
        $group_id = db_scalar_query("SELECT `id` FROM `identities` WHERE `telegram_id` = {$this->get_user_id()}");
        if($group_id === null || $group_id === false) {
            //No identity registered
            return;
        }

        $state = db_row_query("SELECT `group_id`, `name`, `participants_count`, `state`, `assigned_riddle_id` FROM `status` WHERE `game_id` = " . CURRENT_GAME_ID . " AND `group_id` = {$group_id}");
        if($state === null) {
            //No registration
            return;
        }

        $this->group_id = intval($state[0]);
        if($state[1]) {
            $this->group_name = $state[1];
        }
        else {
            $this->group_name = TEXT_UNNAMED_GROUP;
        }
        $this->group_state = $state[3];
        $this->assigned_riddle_id = $state[4];
    }

}
