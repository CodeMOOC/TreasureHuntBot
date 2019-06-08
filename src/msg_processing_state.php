<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Game state process message processing.
 */

/**
 * Handles the group's current registration state,
 * sending out a question to the user if needed.
 *  @param Context $context - message context.
 * @return bool True if handled, false if no need.
 */
function msg_processing_handle_group_state($context) {
    if(!$context->game || $context->game->group_state === null) {
        //No state
        Logger::debug("Ignoring message for group state", __FILE__, $context);
        return false;
    }

    switch($context->game->group_state) {
        case STATE_NEW:
            //Needs to send the captcha question
            $context->comm->reply(__('registration_new_state'));

            $context->comm->picture(
                '../images/quiz-captcha.png',
                __('registration_new_state_caption')
            );

            return true;

        case STATE_REG_VERIFIED:
            //Needs to ask for group name
            $context->comm->reply(__('registration_verified_state'));
            return true;

        case STATE_REG_NAME:
            $context->comm->reply(__('registration_name_state'));
            return true;

        case STATE_REG_NUMBER:
            $context->comm->reply(__('registration_number_state'));
            return true;

        case STATE_REG_READY:
            if($context->game->game_channel_name) {
                $context->comm->reply(__('registration_ready_state_with_channel'));
            }
            else {
                $context->comm->reply(__('registration_ready_state_without_channel'));
            }
            return true;

        /* GAME */

        case STATE_GAME_LOCATION:
            $context->comm->reply(__('game_location_state'));
            return true;

        case STATE_GAME_SELFIE:
            $context->comm->reply(__('game_selfie_state'));
            return true;

        case STATE_GAME_PUZZLE:
            $context->comm->reply(__('game_puzzle_state'));
            return true;

        case STATE_GAME_LAST_LOC:
            $context->comm->reply(__('game_last_location_state'));
            return true;

        case STATE_GAME_LAST_SELF:
            $context->comm->reply(__('game_last_selfie_state'));
            return true;

        case STATE_GAME_LAST_PUZ:
            if($context->game->game_id == 434) {
                $context->comm->picture(GAME_MINECRAFT_URBINO_PUZZLE_1_IMAGE, __('riddle_type_final', 'riddles'));
                return true;
            }

            $context->comm->picture(GAME_LAST_PUZZLE_1_IMAGE, __('riddle_type_final', 'riddles'));
            return true;

        case STATE_GAME_LAST_PUZ + 1:
            $context->comm->picture(GAME_LAST_PUZZLE_2_IMAGE, __('riddle_type_final_repeat', 'riddles'));
            return true;

        case STATE_GAME_LAST_PUZ + 2:
            if($context->game->game_id == 434) {
                $context->comm->picture(GAME_MINECRAFT_URBINO_PUZZLE_3_IMAGE, "“Sono nato qui, figlio di un pittore come poi lo sarei diventato io. Qual era il nome di mio padre?”");
                return true;
            }

            $context->comm->picture(GAME_LAST_PUZZLE_3_IMAGE, "“Hello, mes amis. My father was a tax collector in Rouen and I helped him by developing one of the first mechanical calculators in 1642. What's my last name?”");
            return true;

        case STATE_GAME_WON:
            $context->comm->reply(__('game_won_state'));
            return true;

        case STATE_FEEDBACK:
            $context->comm->reply(__('questionnaire_q1'), null, array(
                'reply_markup' => array(
                    'keyboard' => array(
                        array(__('questionnaire_likert_1')),
                        array(__('questionnaire_likert_2')),
                        array(__('questionnaire_likert_3')),
                        array(__('questionnaire_likert_4')),
                        array(__('questionnaire_likert_5'))
                    )
                )
            ));
            return true;

        case STATE_FEEDBACK + 1:
            $context->comm->reply(__('questionnaire_q2'), null, array(
                'reply_markup' => array(
                    'keyboard' => array(
                        array(__('questionnaire_likert_1')),
                        array(__('questionnaire_likert_2')),
                        array(__('questionnaire_likert_3')),
                        array(__('questionnaire_likert_4')),
                        array(__('questionnaire_likert_5'))
                    )
                )
            ));
            return true;

        case STATE_FEEDBACK + 2:
            $context->comm->reply(__('questionnaire_q3'), null, array(
                'reply_markup' => array(
                    'keyboard' => array(
                        array(__('questionnaire_likert_1')),
                        array(__('questionnaire_likert_2')),
                        array(__('questionnaire_likert_3')),
                        array(__('questionnaire_likert_4')),
                        array(__('questionnaire_likert_5'))
                    )
                )
            ));
            return true;

        case STATE_FEEDBACK + 3:
            $context->comm->reply(__('questionnaire_q4'));
            return true;

        case STATE_CERT_SENT:
            $context->comm->reply(__('game_won_state'));
            return true;
    }

    return false;
}

/**
 * Handles the user's response if needed by the registration state.
 * @return bool True if handled, false otherwise.
 */
function msg_processing_handle_group_response($context) {
    if(!$context->game || $context->game->group_state === null) {
        //No state
        Logger::debug("Ignoring message for group response", __FILE__, $context);
        return false;
    }

    $message_response = '';
    if($context->message) {
        $message_response = $context->message->get_response();
    }

    switch($context->game->group_state) {

        /* REGISTRATION */

        case STATE_NEW:
            if('c' === $message_response) {
                bot_set_group_state($context, STATE_REG_VERIFIED);
                $context->comm->reply(__('registration_new_response_correct'));

                msg_processing_handle_group_state($context);
            }
            else {
                $context->comm->reply(__('registration_new_response_wrong'));
            }
            return true;

        case STATE_REG_VERIFIED:
            if($message_response) {
                $name = ucwords($message_response);

                bot_set_group_name($context, $name);
                bot_set_group_state($context, STATE_REG_NAME);

                $groups_count = bot_get_registered_groups($context);

                Logger::info("Registered group '{$name}' ({$groups_count}th)", __FILE__, $context);

                $context->comm->reply(__('registration_verified_response_ok'), array(
                    '%GROUP_COUNT%' => $groups_count
                ));

                msg_processing_handle_group_state($context);
            }
            else {
                $context->comm->reply(__('registration_verified_response_invalid'));
            }
            return true;

        case STATE_REG_NAME:
            if(!is_numeric($message_response)) {
                $context->comm->reply(__('registration_name_response_invalid'));
                return true;
            }

            $number = intval($message_response);
            if($number < 2) {
                $context->comm->reply(__('registration_name_response_toofew'));
                return true;
            }
            else if($number > 20) {
                $context->comm->reply(__('registration_name_response_toomany'));
                return true;
            }

            bot_set_group_participants($context, $number);
            bot_set_group_state($context, STATE_REG_NUMBER);

            Logger::info("Group '{$context->game->group_name}' registered '{$number}' participants", __FILE__, $context);

            $context->comm->reply(__('registration_name_response_ok'), array(
                '%NUMBER%' => $number
            ));

            msg_processing_handle_group_state($context);

            return true;

        case STATE_REG_NUMBER:
            if($context->is_message() && $context->message->get_photo_max_id()) {
                $file_info = telegram_get_file_info($context->message->get_photo_max_id());
                $file_path = $file_info['file_path'];
                $local_path = "{$context->game->game_id}-{$context->get_internal_id()}.jpg";
                telegram_download_file($file_path, "../data/avatars/$local_path");

                bot_set_group_photo($context, $local_path);
                bot_set_group_state($context, STATE_REG_READY);

                $groups_count = bot_stats_ready_groups($context)[0];

                Logger::info("Group '{$context->game->group_name}' is ready for the game ({$groups_count}th)", __FILE__, $context);

                $context->comm->reply(__('registration_number_response_ok'), array(
                    '%GROUP_COUNT%' => $groups_count
                ));

                msg_processing_handle_group_state($context);
            }
            else {
                $context->comm->reply(__('registration_number_response_invalid'));
            }
            return true;

        case STATE_REG_READY:
            //Nop
            msg_processing_handle_group_state($context);
            return true;

        /* GAME */

        case STATE_GAME_LOCATION:
            // We expect a deeplink that will come through the /start command
            if($context->is_callback() && $context->callback->data === 'hint') {
                $elapsed_seconds = bot_get_time_since_location_assignment($context);
                $seconds_to_wait = 60 - $elapsed_seconds;
                Logger::debug("{$elapsed_seconds} seconds elapsed since location assignment", __FILE__, $context);

                if($elapsed_seconds >= 60) {
                    $context->comm->reply(__('game_location_hint_confirm'));

                    $location_info = bot_get_location_info($context, bot_get_expected_location_id($context));
                    telegram_send_location(
                        $context->get_telegram_chat_id(),
                        $location_info[0],
                        $location_info[1]
                    );
                    return true;
                }
                else {
                    $context->comm->reply(__('game_location_hint_wait'), array(
                        '%SECONDS%' => $seconds_to_wait
                    ), array("reply_markup" => array(
                        "inline_keyboard" => array(
                            array(
                                array("text" => __('game_location_hint_button'), "callback_data" => 'hint')
                            )
                        )
                    )));
                    return true;
                }
            }
            else if($context->is_message() && $context->is_message()->is_text()) {
                // Should signal error, provide button for hint
                return true;
            }

            msg_processing_handle_group_state($context);
            return true;

        case STATE_GAME_SELFIE:
            // Expecting photo taken at reached location
            if($context->is_message() && $context->message->get_photo_max_id()) {
                $reached_locations_count = bot_get_count_of_reached_locations($context);

                $file_info = telegram_get_file_info($context->message->get_photo_max_id());
                $file_path = $file_info['file_path'];
                $local_path = "{$context->game->game_id}-{$context->get_internal_id()}-{$reached_locations_count}";
                telegram_download_file($file_path, "../data/selfies/{$local_path}.jpg");

                // Process selfie and optional badge
                if($context->game->badge_overlay_image) {
                    $rootdir = realpath(dirname(__FILE__) . '/..');
                    exec("convert {$rootdir}/data/selfies/{$local_path}.jpg -resize 1600x1600^ -gravity center -crop 1600x1600+0+0 +repage {$rootdir}/{$context->game->badge_overlay_image} -composite {$rootdir}/data/badges/{$local_path}.jpg");

                    $context->comm->picture("../data/badges/{$local_path}.jpg", __('game_selfie_response_badge'));
                }
                else {
                    $context->comm->reply(__('game_selfie_response_ok'));
                }

                // Post notice on channel
                if($reached_locations_count > 0) {
                    $context->comm->channel_picture($file_info['file_id'], __('game_selfie_forward_caption'), array(
                        '%INDEX%' => $reached_locations_count
                    ));
                }

                $riddle_id = bot_assign_random_riddle($context);
                if($riddle_id === false || $riddle_id === null) {
                    $context->comm->reply(__('failure_general'));
                    return true;
                }

                // Get riddle information
                $riddle_info = bot_get_riddle_info($context, $riddle_id);
                
                $riddle_text = '';
                $riddle_hydration = array();

                if(!$riddle_info[0] || intval($riddle_info[0]) <= 0) {
                    // Unknown/custom riddle type, get text from parameter
                    $riddle_text = $riddle_info[1];
                }
                else {
                    // Standard riddle, get translated riddle text and hydrate with parameter
                    $riddle_text = __('riddle_type_' . $riddle_info[0], 'riddles');
                    $riddle_hydration = array(
                        '%RIDDLE_PARAM%' => $riddle_info[1]
                    );
                }

                if($riddle_info[2]) {
                    // Has picture
                    $context->comm->picture("../riddles/{$riddle_info[2]}", $riddle_text, $riddle_hydration);
                }
                else {
                    // Text-only riddle
                    $context->comm->reply($riddle_text, $riddle_hydration);
                }
            }
            else {
                msg_processing_handle_group_state($context);
            }
            return true;

        case STATE_GAME_PUZZLE:
            if($message_response || $message_response === '0') {
                $result = bot_give_solution($context, $message_response);

                if($result === false) {
                    $context->comm->reply(__('failure_general'));
                }
                else if($result === 'wrong') {
                    $context->comm->reply(__('game_puzzle_response_wrong'));
                }
                else if($result === true) {
                    $confirm_text = __('game_puzzle_response_correct');
                    $current_hint = bot_get_current_hint($context);
                    if($current_hint) {
                        $confirm_text .= ' <code>' . $current_hint . '</code>';
                    }
                    $context->comm->reply($confirm_text);

                    $advance_result = bot_advance_track_location($context);
                    if($advance_result === false) {
                        $context->comm->reply(__('failure_general'));
                    }

                    // Prepare target location information
                    $target_location_id = $advance_result['location_id'];
                    $location_info = bot_get_location_info($context, $target_location_id);

                    $send_location = false;
                    if($context->game->cluster_forces_location_on_enter($advance_result['reached_locations'])) {
                        // Starting a new cluster, force next location to be shown
                        $send_location = true;
                    }
                    if(!$location_info[2] && !$location_info[3]) {
                        // Only location is available, force send
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
                        $caption_text = ($location_info[2]) ? $location_info[2] : null;

                        $hint_keyboard = null;
                        if($context->game->location_hints_enabled && !$advance_result['end_of_track']) {
                            $hint_keyboard = array("reply_markup" => array(
                                "inline_keyboard" => array(
                                    array(
                                        array("text" => __('game_location_hint_button'), "callback_data" => 'hint')
                                    )
                                )
                            ));
                        }

                        $context->comm->picture(
                            '../data/locations/' . $location_info[3], $caption_text, null, $hint_keyboard
                        );
                    }
                    else if($location_info[2]) {
                        // Textual riddle
                        $context->comm->reply($location_info[2]);
                    }

                    msg_processing_handle_group_state($context);
                }
                else {
                    $context->comm->reply(__('game_puzzle_response_wait'), array(
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

        case STATE_GAME_LAST_SELF:
            // Expecting photo taken at last location
            if($context->is_message() && $context->message->get_photo_max_id()) {
                $file_info = telegram_get_file_info($context->message->get_photo_max_id());
                $file_path = $file_info['file_path'];
                $local_path = "{$context->game->game_id}-{$context->get_internal_id()}-final";
                telegram_download_file($file_path, "../data/selfies/{$local_path}.jpg");

                $context->comm->reply(__('game_last_selfie_response_ok'));

                $context->comm->channel_picture($file_info['file_id'], __('game_last_selfie_forward_caption'));

                $context->comm->reply(__('game_last_puzzle_instructions'));

                bot_set_group_state($context, STATE_GAME_LAST_PUZ);
            }

            msg_processing_handle_group_state($context);

            return true;

        case STATE_GAME_LAST_PUZ:
            if($context->game->game_id == 434) {
                if($message_response === GAME_MINECRAFT_URBINO_PUZZLE_1_SOLUTION) {
                    bot_set_group_state($context, STATE_GAME_LAST_PUZ + 2);

                    msg_processing_handle_group_state($context);
                }
                else {
                    $context->comm->reply(__('game_last_puzzle_wrong'));
                }
                return true;
            }

            if($message_response === GAME_LAST_PUZZLE_1_SOLUTION) {
                bot_set_group_state($context, STATE_GAME_LAST_PUZ + 1);

                msg_processing_handle_group_state($context);
            }
            else {
                $context->comm->reply(__('game_last_puzzle_wrong'));
            }
            return true;

        case STATE_GAME_LAST_PUZ + 1:
            if($message_response === GAME_LAST_PUZZLE_2_SOLUTION) {
                bot_set_group_state($context, STATE_GAME_LAST_PUZ + 2);

                msg_processing_handle_group_state($context);
            }
            else {
                $context->comm->reply(__('game_last_puzzle_wrong'));
            }
            return true;

        case STATE_GAME_LAST_PUZ + 2:
            if($context->game->game_id == 434) {
                if($message_response === GAME_MINECRAFT_URBINO_PUZZLE_3_SOLUTION) {
                    msg_process_victory($context);
                }
                else {
                    $context->comm->reply(__('game_last_puzzle_wrong'));
                }
                return true;
            }

            if($message_response === GAME_LAST_PUZZLE_3_SOLUTION) {
                msg_process_victory($context);
            }
            else {
                $context->comm->reply(__('game_last_puzzle_wrong'));
            }
            return true;

        case STATE_FEEDBACK:
            $feedback_rating = intval($message_response);
            if($feedback_rating > 0 && $feedback_rating <= 5) {
                if(db_perform_action(sprintf(
                    'REPLACE INTO `questionnaire` (`game_id`, `group_id`, `name`, `rating`) VALUES(%d, %d, \'%s\', %d)',
                    $context->game->game_id,
                    $context->get_internal_id(),
                    'game_rating',
                    $feedback_rating
                )) === false) {
                    $context->comm->reply(__('failure_general'));
                    return true;
                }

                bot_set_group_state($context, STATE_FEEDBACK + 1);
            }

            msg_processing_handle_group_state($context);
            return true;

        case STATE_FEEDBACK + 1:
            $feedback_rating = intval($message_response);
            if($feedback_rating > 0 && $feedback_rating <= 5) {
                if(db_perform_action(sprintf(
                    'REPLACE INTO `questionnaire` (`game_id`, `group_id`, `name`, `rating`) VALUES(%d, %d, \'%s\', %d)',
                    $context->game->game_id,
                    $context->get_internal_id(),
                    'telegram_rating',
                    $feedback_rating
                )) === false) {
                    $context->comm->reply(__('failure_general'));
                    return true;
                }

                bot_set_group_state($context, STATE_FEEDBACK + 2);
            }

            msg_processing_handle_group_state($context);
            return true;

        case STATE_FEEDBACK + 2:
            $feedback_rating = intval($message_response);
            if($feedback_rating > 0 && $feedback_rating <= 5) {
                if(db_perform_action(sprintf(
                    'REPLACE INTO `questionnaire` (`game_id`, `group_id`, `name`, `rating`) VALUES(%d, %d, \'%s\', %d)',
                    $context->game->game_id,
                    $context->get_internal_id(),
                    'qrcode_rating',
                    $feedback_rating
                )) === false) {
                    $context->comm->reply(__('failure_general'));
                    return true;
                }

                bot_set_group_state($context, STATE_FEEDBACK + 3);
            }

            msg_processing_handle_group_state($context);
            return true;

        case STATE_FEEDBACK + 3:
            if($message_response) {
                if(db_perform_action(sprintf(
                    'REPLACE INTO `questionnaire` (`game_id`, `group_id`, `name`, `text`) VALUES(%d, %d, \'%s\', \'%s\')',
                    $context->game->game_id,
                    $context->get_internal_id(),
                    'free_opinion',
                    db_escape($message_response)
                )) === false) {
                    $context->comm->reply(__('failure_general'));
                    return true;
                }

                // Send out confirmation because of free-form text response
                $context->memorize_callback(
                    $context->comm->reply(
                        __('questionnaire_free_confirmation'),
                        null,
                        array('reply_markup' => array(
                            'inline_keyboard' => array(
                                array(
                                    array('text' => __('questionnaire_free_confirmation_confirm_button'), 'callback_data' => 'questionnaire confirm'),
                                    array('text' => __('questionnaire_free_confirmation_deny_button'), 'callback_data' => 'questionnaire deny')
                                )
                            )
                        )
                    ))
                );

                return true;
            }
            else if($context->is_callback()) {
                if(!$context->verify_callback()) {
                    return false;
                }

                if($context->callback->data == 'questionnaire confirm') {
                    $context->comm->reply(__('questionnaire_finish_generating'));
                    telegram_send_chat_action($context->comm->get_telegram_id());

                    // Generate certificate and montages
                    $intermediate_locations_count = db_scalar_query(sprintf(
                        'SELECT `min_num_locations` FROM `events` WHERE `event_id` = %d',
                        $context->game->event_id
                    ));
                    $total_locations_count = $intermediate_locations_count + 2; // start and end

                    $rootdir = realpath(dirname(__FILE__) . '/..');
                    $identifier = "{$context->game->game_id}-{$context->get_internal_id()}";

                    Logger::debug("Generating montage with identifier {$identifier}", __FILE__, $context);

                    exec("montage {$rootdir}/data/selfies/{$identifier}-*.jpg -background \"#0000\" -auto-orient -geometry 150x150 +polaroid -tile {$total_locations_count}x1 {$rootdir}/data/certificates/{$identifier}-montage.png");

                    Logger::debug("Generating certificate", __FILE__, $context);
                    $certificate_path = "{$rootdir}/data/certificates/{$identifier}-certificate.pdf";

                    exec("php {$rootdir}/html2pdf/cert-gen.php \"{$certificate_path}\" {$context->game->group_participants} \"" . addslashes($context->game->group_name) . "\" \"completed\" \"{$context->game->game_name}\" \"{$identifier}\"");

                    Logger::info("Delivering certificate from {$certificate_path}", __FILE__, $context);
                    $context->comm->document($certificate_path, __('questionnaire_attachment_caption'));
                    $context->comm->reply(__('questionnaire_finish_thankyou'));

                    bot_set_group_state($context, STATE_CERT_SENT);
                }
                else {
                    $context->comm->reply(__('questionnaire_free_retry_prompt'));
                }

                return true;
            }

            msg_processing_handle_group_state($context);
            return true;

        case STATE_GAME_WON:
            if($context->is_callback()) {
                if(!$context->verify_callback()) {
                    return false;
                }

                if($context->callback->data == 'questionnaire') {
                    bot_set_group_state($context, STATE_FEEDBACK);

                    $context->comm->reply(__('questionnaire_init_instructions'));
                    $context->comm->reply(__('questionnaire_init_begin'));
                }
                else {
                    return false;
                }
            }

            msg_processing_handle_group_state($context);
            return true;

        case STATE_CERT_SENT:
            msg_processing_handle_group_state($context);
            return true;
    }

    return false;
}
