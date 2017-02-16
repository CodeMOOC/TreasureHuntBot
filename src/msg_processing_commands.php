<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Default command message processing.
 */

/**
 * Handle registration to a new game.
 */
function handle_registration_code($context, $code) {
    $game_id = db_scalar_query("SELECT `game_id` FROM `games` WHERE `registration_code` = '" . db_escape($code) . "' LIMIT 1");

    if($game_id != null) {
        // Registration code found
        Logger::debug("Registration code for game #{$game_id}", __FILE__, $context);

        $result = $context->register($game_id);
        if($result === true) {
            $context->reply(TEXT_CMD_REGISTER_CONFIRM);
            msg_processing_handle_group_state($context);
        }
        else if($result === 'already_registered') {
            $context->reply(TEXT_CMD_REGISTER_REGISTERED);
            msg_processing_handle_group_state($context);
        }
        else {
            $context->reply(TEXT_FAILURE_GENERAL);
        }

        return true;
    }

    return false;
}

/**
 * Handle victory codes for an event.
 */
function handle_victory_code($context, $code) {
    if($context->get_event_id() == null) {
        return false;
    }

    $victory_code = db_scalar_query("SELECT `victory_code` FROM `events` WHERE `event_id` = {$context->get_event_id()} LIMIT 1");
    if(!$victory_code) {
        Logger::error("Event #{$context->get_event_id()} has no victory code", __FILE__, $context);
        return false;
    }

    if(strcmp($victory_code, $code) == 0) {
        Logger::debug("Prize code scanned", __FILE__, $context);

        if($context->get_group_state() >= STATE_GAME_LAST_LOC) {
            // Check for previous winners
            $winning_group = bot_get_winning_group($context);
            if($winning_group !== false) {
                Logger::info("Group has reached the prize but game is already won", __FILE__, $context);

                $context->reply(TEXT_CMD_START_PRIZE_TOOLATE, array(
                    '%WINNING_GROUP%' => $winning_group[1]
                ));
            }
            else {
                Logger::info("Group has reached the prize and won", __FILE__, $context);

                $context->set_state(STATE_GAME_WON);
                $context->channel(TEXT_GAME_WON_CHANNEL);

                msg_processing_handle_group_state($context);
            }
        }
        else {
            $context->reply(TEXT_CMD_START_PRIZE_INVALID);
        }

        return true;
    }

    return false;
}

/*
 * Processes commands in text messages.
 * @param $context Context.
 * @return bool True if processed.
 */
function msg_processing_commands($context) {
    $text = $context->get_message()->text;

    if(starts_with($text, '/help')) {
        $context->reply(TEXT_CMD_HELP);

        return true;
    }
    else if(starts_with($text, '/start')) {
        Logger::debug("Start command with payload");

        $payload = extract_command_payload($text);

        // Naked /start message
        if($payload === '') {
            if(null !== $context->get_group_state()) {
                $context->reply(TEXT_CMD_START_REGISTERED);

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply(TEXT_CMD_START_NEW);
            }
        }

        // Location or special code
        else if(mb_strlen($payload) === 8) {
            Logger::debug("Treasure hunt code: '{$payload}'", __FILE__, $context);

            if(handle_registration_code($context, $payload)) {
                return true;
            }

            if(handle_victory_code($context, $payload)) {
                return true;
            }

            $result = bot_reach_location($context, $payload);
            if($result === false) {
                $context->reply(TEXT_FAILURE_GENERAL);
            }
            else if($result === 'unexpected') {
                $context->reply(TEXT_CMD_START_LOCATION_UNEXPECTED);
            }
            else if($result === 'wrong') {
                $context->reply(TEXT_CMD_START_LOCATION_WRONG);
            }
            else {
                $context->reply(TEXT_CMD_START_LOCATION_REACHED);

                msg_processing_handle_group_state($context);

                if($context->get_group_state() === STATE_GAME_LAST_PUZ) {
                    //TODO warn others!
                }
            }
        }
        // Something else (?)
        else {
            Logger::warning("Unsupported /start payload received: '{$payload}'", __FILE__, $context);

            $context->reply(TEXT_CMD_START_WRONG_PAYLOAD);
        }

        return true;
    }

    return false;
}
