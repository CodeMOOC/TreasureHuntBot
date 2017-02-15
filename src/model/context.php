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

    private $internal_id = null;

    private $is_game_admin = false;
    private $game_id = 1;
    private $event_id = null;
    private $game_state = 128;
    private $game_channel_name = null;

    private $group_name = null;
    private $group_state = null;

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

    /*
     * *** GENERIC ACCESSORS ***
     */

    /* The running game ID */
    function get_game_id() {
        return $this->game_id;
    }

    /* Get the user's internal ID */
    function get_user_id() {
        return $this->internal_id;
    }

    /* Get the incoming message's sender ID */
    function get_telegram_user_id() {
        return $this->message->from_id;
    }

    /* Get the incoming message's chat ID */
    function get_telegram_chat_id() {
        return $this->message->chat_id;
    }

    /* Get the full incoming message */
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

    function get_group_name() {
        return $this->group_name;
    }

    function get_group_state() {
        return $this->group_state;
    }

    /*
     * *** MESSAGE SENDING ***
     */

    /**
     * Replies to the current incoming message.
     * Enables markdown parsing and disables web previews by default.
     */
    function reply($message, $additional_values = null, $additional_parameters = null) {
        return $this->send($this->get_telegram_chat_id(), $message, $additional_values, $additional_parameters);
    }

    /**
     * Sends out a message on the channel.
     */
    function channel($message, $additional_values = null) {
        if(!$this->game_channel_name) {
            Logger::error("Cannot send message to channel (channel not set)", __FILE__, $this);
            return;
        }

        return $this->send($this->game_channel_name, $message, $additional_values, null);
    }

    function send($receiver, $message, $additional_values = null, $additional_parameters = null) {
        if(!$receiver) {
            Logger::error("Receiver not set", __FILE__, $this);
            return false;
        }
        if($message === null) {
            Logger::info("Message is null", __FILE__, $this);
            return false;
        }

        $hydration_values = array(
            '%FIRST_NAME%' => $this->get_message()->get_sender_first_name(),
            '%FULL_NAME%' => $this->get_message()->get_sender_full_name(),
            '%GROUP_NAME%' => $this->get_group_name()
            /*'%WEEKDAY%' => TEXT_WEEKDAYS[intval(strftime('%w'))]*/
        );

        $hydrated = hydrate($message, unite_arrays($hydration_values, $additional_values));
        $default_parameters = array(
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        );
        if($receiver != CHAT_CHANNEL) {
            // "Hide keyboard" is added by default to all messages because
            // of a bug in Telegram that doesn't hide "one-time" keyboards after use
            $default_parameters['reply_markup'] = array(
                'hide_keyboard' => true
            );
        }

        return telegram_send_message(
            $receiver,
            $hydrated,
            unite_arrays($default_parameters, $additional_parameters)
        );
    }

    /*
     * *** STATUS HANDLING ***
     */

    /**
     * Refreshes information about the context from the DB.
     * Optionally registers the user.
     */
    function refresh() {
        $this->internal_id = db_scalar_query("SELECT `id` FROM `identities` WHERE `telegram_id` = {$this->get_telegram_user_id()}");
        if(!$this->internal_id) {
            Logger::debug('Registering new identity', __FILE__, $this);

            // No identity registered, register now
            $this->internal_id = db_perform_action("INSERT INTO `identities` (`id`, `telegram_id`, `first_name`, `full_name`, `first_seen_on`, `last_access`) VALUES(DEFAULT, {$this->get_telegram_user_id()}, '" . db_escape($this->get_message()->get_sender_first_name()) . "', '" . db_escape($this->get_message()->get_sender_full_name()) . "', NOW(), NOW())");

            return;
        }

        // Update last access time
        db_perform_action("UPDATE `identities` SET `last_access` = NOW() WHERE `id` = {$this->get_user_id()}");

        // Get administered games, if any
        $game = db_row_query("SELECT `game_id`, `event_id`, `state`, `telegram_channel` FROM `games` WHERE `organizer_id` = {$this->get_user_id()} AND `state` != " . GAME_STATE_DEAD . " ORDER BY `registered_on` DESC LIMIT 1");
        if($game !== null) {
            $this->is_game_admin = true;
            $this->game_id = intval($game[0]);
            $this->event_id = ($game[1] != null) ? intval($game[1]) : null;
            $this->game_state = intval($game[2]);
            $this->game_channel_name = $game[3];

            Logger::debug("User is administering game #{$this->game_id} (state {$this->game_state}) in event {$this->event_id}", __FILE__, $this);

            return;
        }

        // Get played games, if any
        $group = db_row_query("SELECT `groups`.`game_id`, `groups`.`name`, `groups`.`state`, `games`.`event_id`, `games`.`state`, `games`.`telegram_channel` FROM `groups` LEFT OUTER JOIN `games` ON `groups`.`game_id` = `games`.`game_id` WHERE `group_id` = {$this->get_user_id()} ORDER BY `groups`.`registered_on` DESC LIMIT 1");
        if($group !== null) {
            $this->game_id = intval($group[0]);
            $this->event_id = ($group[3] != null) ? intval($group[3]) : null;
            $this->game_state = intval($group[4]);
            $this->group_name = ($group[1] != null) ? $group[1] : TEXT_UNNAMED_GROUP;
            $this->group_state = intval($group[2]);
            $this->game_channel_name = $group[5];

            Logger::debug("User is playing game #{$this->game_id} (state {$this->game_state}) in event {$this->event_id}, with group {$this->group_name} (state {$this->group_state})", __FILE__, $this);

            return;
        }

        Logger::debug("User is not administering or playing any game", __FILE__, $this);
    }

}
