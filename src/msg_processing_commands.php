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
    $matches = array();
    if(preg_match('/^reg-([0-9]*)-(.{8})$/', $code, $matches) !== 1) {
        Logger::warning("Code {$code} does not match a registration code", __FILE__, $context);
        return false;
    }

    Logger::debug("Code matches a registration code for game {$matches[1]}", __FILE__, $context);

    $registration_code = db_scalar_query("SELECT `registration_code` FROM `games` WHERE `game_id` = {$matches[1]} LIMIT 1");
    if($matches[2] != $registration_code) {
        Logger::warning("Registration code secret does not match", __FILE__, $context);
        return false;
    }

    $game_id = intval($matches[1]);
    Logger::debug("Registration code scanned for game #{$game_id}", __FILE__, $context);

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

/**
 * Handle victory codes for an event.
 */
function handle_victory_code($context, $code) {
    $matches = array();
    if(preg_match('/^win-([0-9]*)-(.{8})$/', $code, $matches) !== 1) {
        Logger::warning("Code {$code} does not match a victory code", __FILE__, $context);
        return false;
    }

    Logger::debug("Code matches a victory code for event {$matches[1]}", __FILE__, $context);

    if($matches[1] != $context->get_event_id()) {
        Logger::warning("Victory code does not match currently played event", __FILE__, $context);
        return false;
    }
    $victory_code = db_scalar_query("SELECT `victory_code` FROM `events` WHERE `event_id` = {$matches[1]} LIMIT 1");
    if($matches[2] != $victory_code) {
        Logger::warning("Victory code secret does not match", __FILE__, $context);
        return false;
    }

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
        // Invalid state, cannot win game yet
        $context->reply(TEXT_CMD_START_PRIZE_INVALID);
    }

    return true;
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
        $payload = extract_command_payload($text);

        Logger::debug("Start command with payload '{$payload}'");

        if($payload === '') {
            // Naked /start message
            if(null !== $context->get_group_state()) {
                $context->reply(TEXT_CMD_START_REGISTERED);

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply(TEXT_CMD_START_NEW);
            }
        }
        else if(starts_with($payload, 'win')) {
            // Victory code
            if(handle_victory_code($context, $payload)) {
                return true;
            }
            else {
                $context->reply(TEXT_CMD_START_WRONG_PAYLOAD);
            }
        }
        else if(starts_with($payload, 'reg')) {
            // Registration code
            if(handle_registration_code($context, $payload)) {
                return true;
            }
            else {
                $context->reply(TEXT_CMD_START_WRONG_PAYLOAD);
            }
        }
        else if(starts_with($payload, 'loc')) {
            // Location code
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
        else {
            // Something else (?)
            Logger::warning("Unsupported /start payload received: '{$payload}'", __FILE__, $context);

            $context->reply(TEXT_CMD_START_WRONG_PAYLOAD);
        }

        return true;
    }

    return false;
}
