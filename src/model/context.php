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
    private $event_channel_name = null;
    private $game_state = 128;
    private $game_channel_name = null;

    // Rows of cluster_id, num_locations, and description
    private $game_location_clusters = null;

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

    /**
     * Gets whether the current user is the administrator of the current game.
     */
    function is_admin() {
        return $this->is_game_admin;
    }

    /* The running game ID */
    function get_game_id() {
        return $this->game_id;
    }

    /* The running game's event ID */
    function get_event_id() {
        return $this->event_id;
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

    /**
     * Gets the current user's group name (if any).
     * Returns null if no group registered.
     */
    function get_group_name() {
        return $this->group_name;
    }

    /**
     * Gets the current user's group state (if any).
     * Returns null if no group registered.
     */
    function get_group_state() {
        return $this->group_state;
    }

    /**
     * Gets the current game's number of required locations to win.
     */
    function get_game_num_locations() {
        if($this->game_location_clusters == null) {
            return 0;
        }

        $acc = 0;
        foreach($this->game_location_clusters as $cluster) {
            $acc += intval($cluster[1]);
        }

        return $acc;
    }

    /**
     * Gets the cluster ID for the next location.
     * @param $num_reached_locations Number of reached locations.
     * @return Cluster ID of the next location to fetch or null if no location can/must be reached.
     */
    function get_next_location_cluster_id($num_reached_locations = 0) {
        if($this->game_location_clusters == null) {
            Logger::warning("Get next location cluster without having clusters", __FILE__, $this);
            return null;
        }
        if(count($this->game_location_clusters) == 0) {
            Logger::error("No clusters defined", __FILE__, $this);
            return null;
        }

        Logger::debug("Seeking next location for {$num_reached_locations} reached locations", __FILE__, $this);

        foreach($this->game_location_clusters as $cluster) {
            if($num_reached_locations < $cluster[1]) {
                Logger::debug("Picking cluster #{$cluster[0]} ({$cluster[2]}) for next location", __FILE__, $this);
                return intval($cluster[0]);
            }

            Logger::debug("Skipping cluster #{$cluster[0]} (with {$cluster[1]} locations)", __FILE__, $this);
            $num_reached_locations -= intval($cluster[1]);
        }
    }

    /**
     * Gets whether the next location starts a cluster (i.e., is the first location
     * inside a new cluster for the current user).
     * @param $num_reached_locations Number of reached locations.
     * @return True if the next location starts a cluster.
     */
    function next_location_starts_cluster($num_reached_locations = 0) {
        if($this->game_location_clusters == null) {
            Logger::warning("Get next location cluster without having clusters", __FILE__, $this);
            return false;
        }
        if(count($this->game_location_clusters) == 0) {
            Logger::error("No clusters defined", __FILE__, $this);
            return false;
        }

        foreach($this->game_location_clusters as $cluster) {
            if($num_reached_locations == 0) {
                Logger::debug("Cluster #{$cluster[0]} starts a new cluster", __FILE__, $this);
                return true;
            }

            $num_reached_locations -= intval($cluster[1]);
        }

        Logger::debug("Cluster #{$cluster[0]} does not start a new cluster", __FILE__, $this);
        return false;
    }

    /*
     * *** MESSAGE SENDING ***
     */

    /**
     * Replies to the current incoming message.
     */
    function reply($message, $additional_values = null, $additional_parameters = null) {
        return $this->send($this->get_telegram_chat_id(), $message, $additional_values, $additional_parameters);
    }

    /**
     * Replies to the current incoming message with a picture.
     */
    function picture($photo_id, $message, $additional_values = null) {
        telegram_send_photo(
            $this->get_telegram_chat_id(),
            $photo_id,
            $this->hydrate_text($message, $additional_values)
        );
    }

    /**
     * Sends out a message on the game-specific channel.
     */
    function channel($message, $additional_values = null) {
        if(!$this->game_channel_name) {
            Logger::error("Cannot send message to channel (channel not set)", __FILE__, $this);
            return;
        }

        return $this->send($this->game_channel_name, $message, $additional_values, null);
    }

    /**
     * Sends out a picture on the game-specific channel.
     */
    function channel_picture($photo_id, $message, $additional_values = null) {
        if(!$this->game_channel_name) {
            Logger::error("Cannot send picture to channel (channel not set)", __FILE__, $this);
            return;
        }

        telegram_send_photo(
            $this->game_channel_name,
            $photo_id,
            $this->hydrate_text($message, $additional_values)
        );
    }

    /**
     * Sends out a message on the event channel.
     */
    function event_channel($message, $additional_values = null) {
        if(!$this->event_channel_name) {
            Logger::error("Cannot send message to event channel (channel not set)", __FILE__, $this);
            return;
        }

        return $this->send($this->event_channel_name, $message, $additional_values, null);
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

        $default_parameters = array(
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        );
        if($receiver != $this->game_channel_name) {
            // "Hide keyboard" is added by default to all messages because
            // of a bug in Telegram that doesn't hide "one-time" keyboards after use
            $default_parameters['reply_markup'] = array(
                'hide_keyboard' => true
            );
        }

        return telegram_send_message(
            $receiver,
            $this->hydrate_text($message, $additional_values),
            unite_arrays($default_parameters, $additional_parameters)
        );
    }

    private function hydrate_text($message, $additional_values = null) {
        $hydration_values = array(
            '%FIRST_NAME%' => $this->get_message()->get_sender_first_name(),
            '%FULL_NAME%' => $this->get_message()->get_sender_full_name(),
            '%GROUP_NAME%' => $this->get_group_name()
            /*'%WEEKDAY%' => TEXT_WEEKDAYS[intval(strftime('%w'))]*/
        );

        return hydrate($message, unite_arrays($hydration_values, $additional_values));
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
        $this->internal_id = intval($this->internal_id);

        // Update last access time and names
        db_perform_action("UPDATE `identities` SET `first_name` = '" . db_escape($this->get_message()->get_sender_first_name()) . "', `full_name` = '" . db_escape($this->get_message()->get_sender_full_name()) . "', `last_access` = NOW() WHERE `id` = {$this->internal_id}");

        // Get administered games, if any
        $game = db_row_query("SELECT `game_id`, `event_id`, `state`, `telegram_channel` FROM `games` WHERE `organizer_id` = {$this->get_user_id()} AND `state` != " . GAME_STATE_DEAD . " ORDER BY `registered_on` DESC LIMIT 1");
        if($game !== null) {
            $this->is_game_admin = true;
            $this->game_id = intval($game[0]);
            $this->event_id = ($game[1] != null) ? intval($game[1]) : null;
            $this->game_state = intval($game[2]);
            $this->game_channel_name = $game[3];

            Logger::debug("User is administering game #{$this->game_id} (state {$this->game_state}) in event {$this->event_id}", __FILE__, $this);
        }
        else {
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
            }
        }

        if($this->game_id != null) {
            // Load location clusters for current game
            $this->game_location_clusters = db_table_query("SELECT `cluster_id`, `num_locations`, `description` FROM `game_location_clusters` WHERE `game_id` = {$this->game_id} ORDER BY `cluster_id` ASC");
            Logger::debug("Game #{$this->game_id} has " . count($this->game_location_clusters) . ' location clusters', __FILE__, $this);
        }
        else {
            Logger::debug("User is not administering or playing any game", __FILE__, $this);
        }
    }

    /**
     * Gets whether the user is registered for a given game.
     */
    function is_registered($game_id) {
        return ($this->game_id == $game_id && $this->group_name != null);
    }

    /**
     * Registers the user for a game.
     */
    function register($game_id) {
        $game_id = intval($game_id);

        if($this->is_registered($game_id)) {
            Logger::warning("User already registered for game #{$game_id}", __FILE__, $this);
            return 'already_registered';
        }

        if($this->group_name != null) {
            Logger::debug("User is already registered for another game (#{$this->game_id})", __FILE__, $this);
            // Ignore now
        }

        Logger::debug("Attempting to register new group for user #{$this->internal_id} for game #{$game_id}", __FILE__, $this);

        if(db_perform_action("INSERT INTO `groups` (`game_id`, `group_id`, `state`, `registered_on`, `last_state_change`) VALUES({$game_id}, {$this->internal_id}, " . STATE_NEW . ", NOW(), NOW())") === false) {
            Logger::error("Failed to register group status", __FILE__, $this);
            return false;
        }

        $this->refresh();

        Logger::info("New group registered for user #{$this->internal_id} in game #{$game_id}", __FILE__, $this);

        return true;
    }

    /*
     * *** UPDATING ***
     */

    /**
    * Updates name for the current user in the current game.
    */
    function set_group_name($new_name) {
        $updates = db_perform_action("UPDATE `groups` SET `name` = '" . db_escape($new_name) . "' WHERE `game_id` = {$this->game_id} AND `group_id` = {$this->internal_id}");

        if($updates === 1) {
            $this->group_name = $new_name;
            return true;
        }
        else {
            return false;
        }
    }

    /**
    * Updates participants count for current group.
    */
    function set_group_participants($new_number) {
        $new_number = intval($new_number);

        $updates = db_perform_action("UPDATE `groups` SET `participants_count` = {$new_number} WHERE `game_id` = {$this->game_id} AND `group_id` = {$this->internal_id}");

        return ($updates === 1);
    }

    /**
    * Updates photo path for current group.
    */
    function set_group_photo($new_photo_path) {
        $updates = db_perform_action("UPDATE `groups` SET `photo_path` = '" . db_escape($new_photo_path) . "' WHERE `game_id` = {$this->game_id} AND `group_id` = {$this->internal_id}");

        return ($updates === 1);
    }

    /**
    * Updates state for current group and refreshes context.
    */
    function set_state($new_state) {
        $updates = db_perform_action("UPDATE `groups` SET `state` = {$new_state}, `last_state_change` = NOW() WHERE `game_id` = {$this->game_id} AND `group_id` = {$this->internal_id}");

        Logger::debug("User status ==> {$new_state} (updated {$updates} rows)", __FILE__, $this);

        if($updates === 1) {
            $this->group_state = $new_state;
            return true;
        }
        else {
            return false;
        }
    }

}
