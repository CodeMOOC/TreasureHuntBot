<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * State process message processing.
 */

/**
 * Handles the group's current registration state,
 * sending out a question to the user if needed.
 *  @param Context $context - message context.
 * @return bool True if handled, false if no need.
 */
function msg_processing_handle_group_state($context) {
    if(null === $context->get_group_state()) {
        //No state
        return false;
    }

    switch($context->get_group_state()) {
        case STATE_NEW:
            //Needs to send the captcha question
            $context->reply(TEXT_REGISTRATION_NEW_STATE);

            telegram_send_photo(
                $context->get_telegram_chat_id(),
                '../images/quiz-captcha.png',
                TEXT_REGISTRATION_NEW_STATE_CAPTION
            );
            return true;

        case STATE_REG_VERIFIED:
            //Needs to ask for group name
            $context->reply(TEXT_REGISTRATION_VERIFIED_STATE);
            return true;

        case STATE_REG_NAME:
            $context->reply(TEXT_REGISTRATION_NAME_STATE);
            return true;

        case STATE_REG_NUMBER:
            $context->reply(TEXT_REGISTRATION_NUMBER_STATE);
            return true;

        case STATE_REG_READY:
            $context->reply(TEXT_REGISTRATION_READY_STATE);
            return true;

        /* GAME */

        case STATE_GAME_LOCATION:
            // Group has an assigned location to reach
            $context->reply(TEXT_GAME_LOCATION_STATE);
            return true;

        case STATE_GAME_SELFIE:
            $context->reply(TEXT_GAME_SELFIE_STATE);
            return true;

        case STATE_GAME_PUZZLE:
            $context->reply(TEXT_GAME_PUZZLE_STATE);
            return true;

        case STATE_GAME_LAST_LOC:
            $context->reply(TEXT_GAME_LAST_LOCATION_STATE);
            return true;

        case STATE_GAME_LAST_PUZ:
            $context->reply(TEXT_GAME_LAST_PUZZLE_STATE);
            return true;

        case STATE_GAME_WON:
            $context->reply(TEXT_GAME_WON);
            return true;
    }

    return false;
}

/**
 * Handles the user's response if needed by the registration state.
 * @return bool True if handled, false otherwise.
 */
function msg_processing_handle_group_response($context) {
    if(null === $context->get_group_state()) {
        //No state
        return false;
    }

    switch($context->get_group_state()) {

        /* REGISTRATION */

        case STATE_NEW:
            if('c' === $context->get_response()) {
                $context->set_state(STATE_REG_VERIFIED);
                $context->reply(TEXT_REGISTRATION_NEW_RESPONSE_CORRECT);

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply(TEXT_REGISTRATION_NEW_RESPONSE_WRONG);
            }
            return true;

        case STATE_REG_VERIFIED:
            if($context->get_response()) {
                $name = ucwords($context->get_response());

                $context->set_group_name($name);
                $context->set_state(STATE_REG_NAME);

                $groups_count = bot_get_registered_groups($context);

                Logger::info("Registered group '{$name}' ({$groups_count}th)", __FILE__, $context, true);

                $context->reply(TEXT_REGISTRATION_VERIFIED_RESPONSE_OK, array(
                    '%GROUP_COUNT%' => $groups_count
                ));

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply(TEXT_REGISTRATION_VERIFIED_RESPONSE_INVALID);
            }
            return true;

        case STATE_REG_NAME:
            if(!is_numeric($context->get_response())) {
                $context->reply(TEXT_REGISTRATION_NAME_RESPONSE_INVALID);
                return true;
            }

            $number = intval($context->get_response());
            if($number < 2) {
                $context->reply(TEXT_REGISTRATION_NAME_RESPONSE_TOOFEW);
                return true;
            }
            else if($number > 20) {
                $context->reply(TEXT_REGISTRATION_NAME_RESPONSE_TOOMANY);
                return true;
            }

            $context->set_group_participants($number);
            $context->set_state(STATE_REG_NUMBER);

            Logger::info("Group '{$context->get_group_name()}' registered '{$number}' participants", __FILE__, $context);

            $context->reply(TEXT_REGISTRATION_NAME_RESPONSE_OK, array(
                '%NUMBER%' => $number
            ));

            msg_processing_handle_group_state($context);

            return true;

        case STATE_REG_NUMBER:
            if($context->get_message()->get_photo_large_id()) {
                $file_info = telegram_get_file_info($context->get_message()->get_photo_large_id());
                $file_path = $file_info['file_path'];
                $local_path = "{$context->get_game_id()}-{$context->get_user_id()}.jpg";
                telegram_download_file($file_path, "../avatars/$local_path");

                $context->set_group_photo($local_path);
                $context->set_state(STATE_REG_READY);

                $groups_count = bot_get_ready_groups($context);

                Logger::info("Group '{$context->get_group_name()}' is ready for the game ({$groups_count}th)", __FILE__, $context);

                $context->reply(TEXT_REGISTRATION_NUMBER_RESPONSE_OK, array(
                    '%GROUP_COUNT%' => $groups_count
                ));

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply(TEXT_REGISTRATION_NUMBER_RESPONSE_INVALID, array(
                    '%GROUP%' => $context->get_group_name()
                ));
            }
            return true;

        case STATE_REG_READY:
            //Nop
            msg_processing_handle_group_state($context);
            return true;

        /* GAME */

        case STATE_GAME_LOCATION:
            // We expect a deeplink that will come through the /start command
            // Ignore everything
            msg_processing_handle_group_state($context);
            return true;

        case STATE_GAME_SELFIE:
            // Expecting photo taken at reached location
            if($context->get_message()->get_photo_large_id()) {
                $reached_locations_count = bot_get_count_of_reached_locations($context) + 1;

                $file_info = telegram_get_file_info($context->get_message()->get_photo_large_id());
                $file_path = $file_info['file_path'];
                $local_path = "{$context->get_game_id()}-{$context->get_user_id()}-{$reached_locations_count}";
                telegram_download_file($file_path, "../selfies/{$local_path}.jpg");

                // Process selfie and optional badge
                if(true) {
                    $rootdir = realpath(dirname(__FILE__) . '/..');
                    exec("convert {$rootdir}/selfies/{$local_path}.jpg -resize 1600x1600^ -gravity center -crop 1600x1600+0+0 +repage {$rootdir}/images/badge-codytrip.png -composite {$rootdir}/badges/{$local_path}.jpg");

                    $context->picture("../badges/{$local_path}.jpg", TEXT_GAME_SELFIE_RESPONSE_BADGE);
                }
                else {
                    $context->reply(TEXT_GAME_SELFIE_RESPONSE_OK);
                }

                // Post notice on channel
                $context->channel_picture($file_info['file_id'], TEXT_GAME_SELFIE_FORWARD_CAPTION, array(
                    '%INDEX%' => $reached_locations_count
                ));

                $riddle_id = bot_assign_random_riddle($context);
                if($riddle_id === false || $riddle_id === null) {
                    $context->reply(TEXT_FAILURE_GENERAL);
                    return true;
                }

                // Send out riddle
                $riddle_info = bot_get_riddle_info($context, $riddle_id);
                if($riddle_info[0]) {
                    $context->picture("../riddles/{$riddle_info[0]}", (string)$riddle_info[1]);
                }
                else {
                    $context->reply((string)$riddle_info[1]);
                }
            }
            else {
                msg_processing_handle_group_state($context);
            }
            return true;

        case STATE_GAME_PUZZLE:
            // Expecting response to puzzle
            $response = $context->get_response();
            if($response) {
                $result = bot_give_solution($context, $response);

                if($result === false) {
                    $context->reply(TEXT_FAILURE_GENERAL);
                }
                else if($result === 'wrong') {
                    $context->reply(TEXT_GAME_PUZZLE_RESPONSE_WRONG);
                }
                else if($result === true) {
                    // Give out secret hint of current track index
                    //$context->reply(CORRECT_ANSWER_PRIZE[$context->get_track_index()]);

                    $advance_result = bot_advance_track_location($context);
                    if($advance_result === false) {
                        $context->reply(TEXT_FAILURE_GENERAL);
                    }

                    // Prepare target location information
                    $target_location_id = $advance_result['location_id'];
                    $location_info = bot_get_location_info($context, $target_location_id);

                    $send_location = false;
                    if($context->next_location_starts_cluster($advance_result['reached_locations'])) {
                        // Starting a new cluster, force next location to be shown
                        $send_location = true;

                        // TODO: add other cluster information here
                    }
                    if(!$location_info[2] && !$location_info[3]) {
                        $send_location = true;
                    }

                    // Send out target location information
                    if($send_location) {
                        // Exact location
                        telegram_send_location(
                            $context->get_telegram_chat_id(),
                            $location_info[0],
                            $location_info[1]
                        );
                    }
                    if($location_info[3]) {
                        // Image with optional caption
                        $context->picture(
                            '../locations/' . $location_info[3],
                            ($location_info[2]) ? $location_info[2] : null
                        );
                    }
                    else if($location_info[2]) {
                        // Textual riddle
                        $context->reply($location_info[2]);
                    }

                    msg_processing_handle_group_state($context);
                }
                else {
                    $context->reply(TEXT_GAME_PUZZLE_RESPONSE_WAIT, array(
                        '%SECONDS%' => intval($result)
                    ));
                }
            }
            else {
                msg_processing_handle_group_state($context);
            }
            return true;

        case STATE_GAME_LAST_LOC:
            // Expecting last location QR Code
            msg_processing_handle_group_state($context);
            return true;

        case STATE_GAME_LAST_PUZ:
            // Expecting last puzzle QR Code
            msg_processing_handle_group_state($context);
            return true;

        case STATE_GAME_WON:
            // Expect nothing, game is won
            msg_processing_handle_group_state($context);
            return true;
    }

    return false;
}
