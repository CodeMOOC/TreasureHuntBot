<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Class wrapping the bot's context in a single execution.
 */

require_once(dirname(__FILE__) . '/../lib.php');

require_once(dirname(__FILE__) . '/sender.php');
require_once(dirname(__FILE__) . '/communicator.php');
require_once(dirname(__FILE__) . '/message.php');
require_once(dirname(__FILE__) . '/callback.php');
require_once(dirname(__FILE__) . '/game.php');

class Context {

    // Raw update payload
    private $update;

    // Internal ID in table `identities`
    private $internal_id = null;
    public $language_override = null;

    // Alternative update contents
    public $message;
    public $callback;

    // Auxiliary context classes
    public $comm;
    public $sender;
    public $memory;
    public $game;

    /**
     * Construct Context instance.
     * @param $update Telegram update data structure.
     */
    function __construct($update) {
        $this->update = $update;

        $this->reload();
    }

    /**
     * Force reload context from database.
     */
    public function reload() {
        $this->close();

        if(isset($this->update['message'])) {
            $this->message = new Message($this->update['message']);

            Logger::debug("Incoming message: {$this->message->get_description()}", __FILE__, $this);
        }
        else if(isset($this->update['callback_query'])) {
            $this->callback = new Callback($this->update['callback_query']);

            Logger::debug("Incoming callback: {$this->callback->data}", __FILE__, $this);
        }
        else if(isset($this->update['channel_post'])) {
            Logger::debug("Channel post, ignoring");
            die();
        }
        else {
            Logger::fatal("Unknown kind of update", __FILE__);
        }

        $content = $this->get_content();
        $this->comm = $content->get_communicator($this);
        $this->sender = $content->get_sender();

        $this->memory = memory_load_for_user($this->get_telegram_user_id());

        $this->load_identity_and_status();
        $this->load_language();
    }

    /**
     * Performs closing duties.
     */
    function close() {
        if($this->memory && $this->comm) {
            memory_persist($this->get_telegram_user_id(), $this->memory);
        }
    }

    // *** DIRECT DATA ACCESSORS ***

    function is_message() {
        return isset($this->message);
    }

    function is_callback() {
        return isset($this->callback);
    }

    function get_content() {
        if(isset($this->message)) {
            return $this->message;
        }
        else if(isset($this->callback)) {
            return $this->callback;
        }
        else {
            return null;
        }
    }

    /**
     * Get the user's telegram user ID.
     * Currenty equal to get_telegram_chat_id().
     */
    function get_telegram_user_id() {
        return $this->get_telegram_chat_id();
    }

    /**
     * Get the current chat's telegram ID.
     */
    function get_telegram_chat_id() {
        if(!$this->comm) {
            return null;
        }

        return $this->comm->get_telegram_id();
    }

    /**
     * Get the user's internal ID.
     */
    function get_internal_id() {
        return $this->internal_id;
    }

    // *** STATUS HANDLING ***

    /**
     * Loads the user's identity and game status.
     */
    private function load_identity_and_status() {
        $identity_row = db_row_query("SELECT `id`, `active_game`, `is_admin`, `language` FROM `identities` WHERE `telegram_id` = {$this->get_telegram_user_id()}");

        // No identity registered, register now
        if(!$identity_row) {
            Logger::debug("Registering new identity", __FILE__, $this);

            $this->internal_id = db_perform_action(sprintf(
                "INSERT INTO `identities` (`id`, `telegram_id`, `first_name`, `full_name`, `first_seen_on`, `last_access`) VALUES(DEFAULT, %d, '%s', '%s', NOW(), NOW())",
                $this->get_telegram_user_id(),
                db_escape($this->sender->first_name),
                db_escape($this->sender->get_full_name())
            ));

            return;
        }

        $this->internal_id = (int)$identity_row[0];
        $this->language_override = $identity_row[3];
        Logger::debug(sprintf(
            'Known identity #%d on game #%d (admin %s), language \'%s\'',
            $this->internal_id,
            $identity_row[1],
            b2s($identity_row[2]),
            $identity_row[3]
        ), __FILE__, $this);

        // Update last access time and names
        db_perform_action(sprintf(
            "UPDATE `identities` SET `first_name` = '%s', `full_name` = '%s', `last_access` = NOW() WHERE `id` = %d",
            db_escape($this->sender->first_name),
            db_escape($this->sender->get_full_name()),
            $this->internal_id
        ));

        $this->game = new Game($identity_row[1], $identity_row[2], $this);
    }

    /**
     * Loads current language, taking into account user, game, and event settings.
     */
    private function load_language() {
        if($this->language_override) {
            // User language override always wins
            localization_set_locale($this->language_override);
        }
        else if($this->game && $this->game->game_language) {
            localization_set_locale($this->game->game_language);
        }
        else if($this->sender && $this->sender->language_code) {
            localization_set_locale($this->sender->language_code);
        }
    }

    /**
     * Update the user's active game (and his/her admin status).
     */
    public function set_active_game($game_id, $is_admin) {
        db_perform_action(sprintf(
            "UPDATE `identities` SET `active_game` = %s, `is_admin` = %d WHERE `telegram_id` = %d",
            ($game_id === null) ? 'NULL' : $game_id,
            $is_admin,
            $this->get_telegram_user_id()
        ));

        Logger::debug("Active game set to #{$game_id} as admin " . b2s($is_admin), __FILE__, $this);
    }

    /**
     * Memorizes a reference to the current message ID in order to validate future
     * callback calls.
     * @param $result Results of a communicator send call (from Telegram API).
     */
    public function memorize_callback($result) {
        if(isset($result['message_id'])) {
            $message_id = (int)$result['message_id'];

            Logger::debug("Memorizing {$message_id} as message ID for callback", __FILE__, $this);
            $this->memory['last_callback_message_id'] = $message_id;
        }
        else {
            Logger::warning("Invalid result values from Telegram, unable to memorize message ID for callback", __FILE__, $this);
        }
    }

    /**
     * Verifies that the current callback (if any) is valid (was generated by the last
     * message setting a callback keyboard).
     * @return True if callback is verified, false otherwise.
     */
    public function verify_callback() {
        if(!isset($this->callback)) {
            Logger::warning("Cannot verify callback, not processing a callback", __FILE__, $this);
            return false;
        }

        if(!isset($this->memory['last_callback_message_id'])) {
            Logger::debug("Message ID for callback not set", __FILE__, $this);
            return false;
        }

        if($this->memory['last_callback_message_id'] === $this->callback->message_id) {
            $this->memory['last_callback_message_id'] = null;
            return true;
        }
        else {
            return false;
        }
    }

}
