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
    $text = $context->message->text;

    if($text === '/help') {
        $context->comm->reply(__('cmd_help'));

        return true;
    }
    else if(starts_with($text, '/start')) {
        $payload = extract_command_payload($text);

        Logger::debug("Start command with payload '{$payload}'", __FILE__, $context);

        if($payload === '') {
            // Naked /start message
            if($context->game && $context->game->group_state) {
                $context->comm->reply(__('cmd_start_registered'));

                msg_processing_handle_group_state($context);
            }
            else {
                $context->comm->reply(__('cmd_start_new'));
            }
        }
        else {
            $code_info = db_row_query(sprintf(
                "SELECT `type`, `event_id`, `game_id`, `location_id`, `is_disabled` FROM `code_lookup` WHERE `code` = '%s'",
                db_escape($payload)
            ));

            if($code_info === false) {
                $context->comm->reply(__('failure_general'));
                return true;
            }
            if($code_info == null) {
                Logger::warning("Unknown /start payload received: '{$payload}'", __FILE__, $context);
                $context->comm->reply(__('cmd_start_wrong_payload'));
                return true;
            }
            else if($code_info[4] == 1) {
                // Code has been disabled
                $context->comm->reply(__('cmd_start_wrong_payload'));
                return true;
            }

            Logger::debug("Code '{$payload}' for {$code_info[0]} scanned", __FILE__, $context);

            $event_id = intval($code_info[1]);
            $game_id = intval($code_info[2]);
            $location_id = intval($code_info[3]);

            switch($code_info[0]) {
                case 'creation':
                    Logger::debug("Creation code scanned for event #{$event_id}", __FILE__, $context);

                    msg_processing_init_game_creation($context, $event_id);

                    break;

                case 'registration':
                    Logger::debug("Registration code scanned for game #{$game_id}", __FILE__, $context);

                    $result = bot_register($context, $game_id);
                    if($result === true) {
                        $context->comm->reply(__('cmd_register_confirm'));
                        msg_processing_handle_group_state($context);
                    }
                    else if($result === 'already_registered') {
                        $context->comm->reply(__('cmd_register_registered'));
                        msg_processing_handle_group_state($context);
                    }
                    else if($result === 'game_unallowed') {
                        $context->comm->reply(__('cmd_register_game_unallowed'));
                    }
                    else {
                        $context->comm->reply(__('failure_general'));
                    }
                    break;

                case 'location':
                    Logger::debug("Location code scanned for location #{$location_id}, game #{$game_id}", __FILE__, $context);

                    $result = bot_reach_location($context, $location_id, $game_id);
                    if($result === false) {
                        $context->comm->reply(__('failure_general'));
                    }
                    else if($result === 'unexpected') {
                        $context->comm->reply(__('cmd_start_location_unexpected'));
                    }
                    else if($result === 'wrong') {
                        $context->comm->reply(__('cmd_start_location_wrong'));
                    }
                    else if($result === 'game_unallowed') {
                        $context->comm->reply(__('failure_game_dead'));
                    }
                    else {
                        if($result === 'first') {
                            $context->comm->reply(__('cmd_start_location_reached_first'));
                        }
                        else if($result === 'last') {
                            $context->comm->reply(__('cmd_start_location_reached_last'));
                        }
                        else {
                            $context->comm->reply(__('cmd_start_location_reached'));
                        }

                        msg_processing_handle_group_state($context);
                    }
                    break;

                case 'victory':
                    Logger::debug("Victory code scanned for game #{$game_id}, event #{$event_id}", __FILE__, $context);

                    msg_process_victory($context, $event_id, $game_id);

                    break;

                default:
                    Logger::error("Code '{$payload}' matches unknown type {$code_info[0]}", __FILE__, $context);

                    $context->comm->reply(__('cmd_start_wrong_payload'));

                    break;
            }
        }

        return true;
    }

    return false;
}
