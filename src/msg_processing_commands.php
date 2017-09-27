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
    else if(starts_with($text, '/start ')) {
        $payload = extract_command_payload($text);

        Logger::debug("Start command with payload '{$payload}'", __FILE__, $context);

        if($payload === '') {
            // Naked /start message
            if($context->game->group_state) {
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

                    if(!bot_creation_init($context, $event_id)) {
                        $context->comm->reply(__('failure_general'));
                    }
                    else {
                        $context->memorize_callback($context->comm->reply(
                            "Welcome to the game creation process. Do you want to proceed creating a new game for the '%EVENT_NAME%' event?",
                            null,
                            array("reply_markup" => array(
                                "inline_keyboard" => array(
                                    array(
                                        array("text" => "Yes!", "callback_data" => "confirm"),
                                        array("text" => "Cancel", "callback_data" => "cancel")
                                    )
                                )
                            ))
                        ));
                    }

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

                        if($context->game->group_state === STATE_GAME_LAST_PUZ) {
                            // TODO warn others!
                        }
                    }
                    break;

                case 'victory':
                    Logger::debug("Victory code scanned for event #{$event_id}", __FILE__, $context);

                    if($event_id != $context->game->event_id) {
                        Logger::warning("Victory code does not match currently played event", __FILE__, $context);
                        $context->comm->reply(__('cmd_start_wrong_payload'));
                        return true;
                    }

                    if($context->game->group_state === STATE_GAME_LAST_PUZ) {
                        // Check for previous winners
                        $winning_groups = bot_get_winning_groups($context);
                        if($winning_groups === false) {
                            $context->comm->reply(__('failure_general'));
                            return true;
                        }

                        bot_set_group_state($context, STATE_GAME_WON);

                        if($winning_groups == null) {
                            // Game has no winning group
                            Logger::info("Group has reached the prize first", __FILE__, $context);

                            $context->comm->reply(__('cmd_start_prize_first'));
                            $context->comm->channel(__('cmd_start_prize_channel_first'));
                        }
                        else {
                            Logger::info("Group has reached the prize (not first)", __FILE__, $context);

                            $context->comm->reply(__('cmd_start_prize_not_first'), array(
                                '%WINNING_GROUP%' => $winning_groups[0][1],
                                '%INDEX%' => count($winning_groups) + 1
                            ));
                            $context->comm->channel(__('cmd_start_prize_channel_not_first'), array(
                                '%INDEX%' => count($winning_groups) + 1
                            ));
                        }
                    }
                    else {
                        // Invalid state, cannot win game yet/again
                        $context->comm->reply(__('cmd_start_prize_invalid'));
                    }
                    break;

                default:
                    Logger::error("Code '{$payload}' matches unknown type {$code_info[0]}", __FILE__, $context);
                    $context->comm->reply(__('cmd_start_wrong_payload'));
                    return true;
            }
        }

        return true;
    }

    return false;
}
