<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Default command message processing.
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
