<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Bot-user communicator.
 */

class Communicator {

    private $chat_id;
    private $owning_context;

    function __construct($chat_id, $owning_context) {
        $this->chat_id = $chat_id;
        $this->owning_context = $owning_context;
    }

    function get_telegram_id() {
        return $this->chat_id;
    }

    /**
     * Replies on the current chat.
     */
     function reply($message, $additional_values = null, $additional_parameters = null) {
        return $this->send($this->chat_id, $message, $additional_values, $additional_parameters);
    }

    /**
     * Replies to the current incoming message with a picture.
     */
    function picture($photo_id, $message, $additional_values = null, $additional_parameters = null) {
        $default_parameters = array(
            'parse_mode' => 'HTML'
        );
        $final_parameters = unite_arrays($default_parameters, $additional_parameters);

        return telegram_send_photo(
            $this->chat_id,
            $photo_id,
            $this->hydrate_text($message, $additional_values),
            $final_parameters
        );
    }

    /**
     * Replies to the current incoming message with a document.
     */
    function document($document_path, $caption, $additional_values = null) {
        return telegram_send_document(
            $this->chat_id,
            $document_path,
            $this->hydrate_text($caption, $additional_values)
        );
    }

    /**
     * Sends out a message on the game-specific channel.
     */
    function channel($message, $additional_values = null) {
        if(!$this->owning_context->game->game_channel_name) {
            Logger::info("Cannot send message to channel (channel not set)", __FILE__, $this->owning_context);
            return;
        }

        return $this->send($this->owning_context->game->game_channel_name, $message, $additional_values, null);
    }

    /**
     * Sends out a picture on the game-specific channel.
     */
    function channel_picture($photo_id, $message, $additional_values = null) {
        if(!$this->owning_context->game->game_channel_name) {
            Logger::info("Cannot send picture to channel (channel not set)", __FILE__, $this->owning_context);

            return;
        }

        if($this->owning_context->game->game_channel_censor) {
            Logger::debug('Photo not sent to channel because of censorship settings', __FILE__, $this->owning_context);

            return $this->channel($message, $additional_values);
        }
        else {
            return telegram_send_photo(
                $this->owning_context->game->game_channel_name,
                $photo_id,
                $this->hydrate_text($message, $additional_values)
            );
        }
    }

    /**
     * Sends out a message on the event channel.
     */
    function event_channel($message, $additional_values = null) {
        if(!$this->owning_context->game->event_channel_name) {
            Logger::info("Cannot send message to event channel (channel not set)", __FILE__, $this->owning_context);
            return;
        }

        return $this->send(
            $this->owning_context->game->event_channel_name,
            $message,
            $additional_values,
            null
        );
    }

    function send($receiver, $message, $additional_values = null, $additional_parameters = null) {
        if(!$receiver) {
            Logger::error("Receiver not set", __FILE__, $this->owning_context);
            return false;
        }
        if($message === null) {
            Logger::info("Message is null", __FILE__, $this->owning_context);
            return false;
        }

        if(is_array($message)) {
            $message = $message[array_rand($message)];
        }

        $default_parameters = array(
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        );
        if($this->owning_context->game && $receiver != $this->owning_context->game->game_channel_name) {
            // "Hide keyboard" is added by default to all messages because
            // of a bug in Telegram that doesn't hide "one-time" keyboards after use
            $default_parameters['reply_markup'] = array(
                'hide_keyboard' => true
            );
        }

        $final_parameters = unite_arrays($default_parameters, $additional_parameters);
        $result = telegram_send_message(
            $receiver,
            $this->hydrate_text($message, $additional_values),
            $final_parameters
        );

        if(isset($final_parameters['reply_markup']['inline_keyboard'])) {
            // New inline keyboard set with message, memorize for callback verification
            $this->owning_context->memorize_callback($result);
        }

        return $result;
    }

    private function hydrate_text($message, $additional_values = null) {
        $hydration_values = array(
            '%FIRST_NAME%' => $this->owning_context->sender->first_name,
            '%FULL_NAME%' => $this->owning_context->sender->get_full_name()
            /*'%WEEKDAY%' => TEXT_WEEKDAYS[intval(strftime('%w'))]*/
        );
        if($this->owning_context->game) {
            $hydration_values['%GROUP_NAME%'] = $this->owning_context->game->group_name;
            $hydration_values['%GAME_ID%'] = $this->owning_context->game->game_id;
            $hydration_values['%GAME_NAME%'] = $this->owning_context->game->game_name;
            $hydration_values['%EVENT_ID%'] = $this->owning_context->game->event_id;
            $hydration_values['%EVENT_NAME%'] = $this->owning_context->game->event_name;

            if($this->owning_context->game->game_channel_name) {
                $hydration_values['%GAME_CHANNEL%'] = $this->owning_context->game->game_channel_name;
            }
        }

        return hydrate($message, unite_arrays($hydration_values, $additional_values));
    }

}
