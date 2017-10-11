<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Default command message processing.
 */

const MYGAMES_SWITCH_KEY = "myGamesProcess";

/*
 * Processes commands in text messages.
 * @param $context Context.
 * @return bool True if processed.
 */
function msg_processing_commands($context) {
    if($context->is_callback()) {
        if(isset($context->memory[MYGAMES_SWITCH_KEY])) {
            if(!$context->verify_callback()) {
                return false;
            }

            if(preg_match_all('/^switch (\d*) (0|1)$/', $context->callback->data, $matches, PREG_PATTERN_ORDER) >= 1) {
                $target_id = intval($matches[1][0]);
                $target_admin = (bool)$matches[2][0];

                Logger::info("Switching to game #{$target_id} as admin " . b2s($target_admin), __FILE__, $context);

                $context->set_active_game($target_id, $target_admin);

                $context->comm->reply("Done!");
            }

            $context->memory[MYGAMES_SWITCH_KEY] = null;

            return true;
        }
    }

    else if($context->is_message()) {
        $text = $context->message->text;

        // HELP
        if($text === '/help') {
            $context->comm->reply(__('cmd_help'));

            return true;
        }
        // START
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
        // STATUS
        else if($text === '/status') {
            if(!$context->game) {
                $context->comm->reply("You are new to me. Hello! 🙂");
                return true;
            }

            $status = "%FIRST_NAME%, you are " . (($context->game->is_admin) ? '<b>administering</b>' : 'playing') . " game <code>#%GAME_ID%</code> “%GAME_NAME%”, in the event <code>#%EVENT_ID%</code> “%EVENT_NAME%”.\n";

            if($context->game->is_admin) {
                $status .= 'The game is <code>' . GAME_STATE_READABLE_MAP[$context->game->game_state] . '</code>.';
            }
            else {
                $status .= 'Your team “%GROUP_NAME%” is in state: <code>' . STATE_READABLE_MAP[$context->game->group_state] . '</code>.';
            }

            $context->comm->reply($status);

            return true;
        }
        // MY GAMES
        else if($text === '/mygames') {
            $games = bot_get_associated_games($context);

            $text = '';
            $game_keyboard = array();
            $seen_admin = false;
            $seen_player = false;
            $i = 0;
            foreach($games as $game) {
                if($game[3] && !$seen_admin) {
                    $seen_admin = true;
                    $text .= "👑 <b>Administered games:</b>\n";
                }
                if(!$game[3] && !$seen_player) {
                    $seen_player = true;
                    $text .= "👤 <b>Played games:</b>\n";
                }

                if($context->game->game_id == $game[0] && $context->game->is_admin == $game[3]) {
                    $text .= "➡️ ";
                }
                $text .= "<code>#{$game[0]}</code> " . (($game[1]) ? "“{$game[1]}”" : "No name") . " <i>" . GAME_STATE_READABLE_MAP[$game[2]] . "</i>\n";

                if($i % 3 == 0) {
                    $game_keyboard[] = array();
                }
                $game_keyboard[sizeof($game_keyboard) - 1][] = array(
                    'text' => (($game[3]) ? '👑 Admin #' : '👤 Play #') . $game[0],
                    'callback_data' => "switch {$game[0]} " . (int)$game[3]
                );
                $i++;
            }

            $text .= "\nTo which game do you want to switch?";

            $context->memorize_callback($context->comm->reply($text, null, array(
                'reply_markup' => array(
                    'inline_keyboard' => $game_keyboard
                )
            )));

            $context->memory[MYGAMES_SWITCH_KEY] = true;

            return true;
        }
    }

    return false;
}
