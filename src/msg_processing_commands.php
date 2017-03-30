<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Default command message processing.
 */

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
        else {
            $code_info = db_row_query("SELECT `type`, `event_id`, `game_id`, `location_id`, `is_disabled` FROM `code_lookup` WHERE `code` = '" . db_escape($payload) . "'");

            if($code_info === false) {
                $context->reply(TEXT_FAILURE_GENERAL);
                return true;
            }
            if($code_info == null) {
                Logger::warning("Unknown /start payload received: '{$payload}'", __FILE__, $context);
                $context->reply(TEXT_CMD_START_WRONG_PAYLOAD);
                return true;
            }
            else if($code_info[4] == 1) {
                // Code has been disabled
                $context->reply(TEXT_CMD_START_WRONG_PAYLOAD);
                return true;
            }

            Logger::debug("Code '{$payload}' for {$code_info[0]} scanned", __FILE__, $context);

            $event_id = intval($code_info[1]);
            $game_id = intval($code_info[2]);
            $location_id = intval($code_info[3]);

            switch($code_info[0]) {
                case 'creation':
                    Logger::debug("Creation code scanned for event #{$event_id}", __FILE__, $context);

                    // TODO: not implemented yet

                    break;

                case 'registration':
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
                    break;

                case 'location':
                    Logger::debug("Location code scanned for location #{$location_id}, game #{$game_id}", __FILE__, $context);

                    $result = bot_reach_location($context, $location_id, $game_id);
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
                        if($result === 'first') {
                            $context->reply(TEXT_CMD_START_LOCATION_REACHED_FIRST);
                        }
                        else if($result === 'last') {
                            $context->reply(TEXT_CMD_START_LOCATION_REACHED_LAST);
                        }
                        else {
                            $context->reply(TEXT_CMD_START_LOCATION_REACHED);
                        }

                        msg_processing_handle_group_state($context);

                        if($context->get_group_state() === STATE_GAME_LAST_PUZ) {
                            // TODO warn others!
                        }
                    }
                    break;

                case 'victory':
                    Logger::debug("Victory code scanned for event #{$event_id}", __FILE__, $context);

                    if($event_id != $context->get_event_id()) {
                        Logger::warning("Victory code does not match currently played event", __FILE__, $context);
                        $context->reply(TEXT_CMD_START_WRONG_PAYLOAD);
                        return true;
                    }

                    if($context->get_group_state() >= STATE_GAME_LAST_LOC) {
                        // Check for previous winners
                        $winning_group = bot_get_winning_group($context);
                        if($winning_group === false) {
                            $context->reply(TEXT_FAILURE_GENERAL);
                            return true;
                        }
                        else if($winning_group != null && !$context->has_timeout()) {
                            // Game has no timeout (simultaneous winners) and a winning group exists
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
                    break;

                default:
                    Logger::error("Code '{$payload}' matches unknown type {$code_info[0]}", __FILE__, $context);
                    $context->reply(TEXT_CMD_START_WRONG_PAYLOAD);
                    return true;
            }
        }

        return true;
    }

    return false;
}
