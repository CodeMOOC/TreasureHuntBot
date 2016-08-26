<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * State process message processing.
 */

require_once('lib.php');
require_once('model/context.php');
require_once('vendor/autoload.php');
require_once('file_downloader/get_file.php');

/**
 * Handles the group's current registration state,
 * sending out a question to the user if needed.
 *  @param Context $context - message context.
 * @return bool True if handled, false if no need.
 */
function msg_processing_handle_group_state($context) {
    if(null === $context->get_group_id()) {
        //No group
        return false;
    }
    if(null === $context->get_group_state()) {
        //No state
        return false;
    }

    switch($context->get_group_state()) {
        case STATE_NEW:
            //Needs to send the captcha question
            $context->reply(TEXT_REGISTRATION_NEW_STATE);

            telegram_send_photo(
                $context->get_chat_id(),
                'images/quiz-captcha.png',
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

        case STATE_REG_CONFIRMED:
            $context->reply(TEXT_REGISTRATION_CONFIRMED_STATE);
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
            if($context->get_track_index() === 0) {
                $context->reply(TEXT_GAME_LOCATION_STATE_FIRST);
            }
            else {
                $context->reply(TEXT_GAME_LOCATION_STATE);
            }
            return true;

        case STATE_GAME_SELFIE:
            // TODO: waiting for selfie
            return true;

        case STATE_GAME_PUZZLE:
            // TODO: send group puzzle
            return true;

        case STATE_GAME_LAST_LOC:
            // TODO:
            return true;

        case STATE_GAME_LAST_PUZ:
            // TODO:
            return true;

        case STATE_GAME_WON:
            // TODO: group has finished the treasure hunt. Nothing to do.
            return true;
    }

    return false;
}

/**
 * Handles the user's response if needed by the registration state.
 * @return bool True if handled, false otherwise.
 */
function msg_processing_handle_group_response($context) {
    if(null === $context->get_group_id()) {
        //No group
        return false;
    }
    if(null === $context->get_group_state()) {
        //No state
        return false;
    }

    switch($context->get_group_state()) {

        /* REGISTRATION */

        case STATE_NEW:
            if('c' === $context->get_response()) {
                $context->reply(TEXT_REGISTRATION_NEW_RESPONSE_CORRECT);

                bot_update_group_state($context, STATE_REG_VERIFIED);

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply(TEXT_REGISTRATION_NEW_RESPONSE_WRONG);
            }
            return true;

        case STATE_REG_VERIFIED:
            if($context->get_response()) {
                $name = ucwords($context->get_response());

                bot_update_group_name($context, $name);
                bot_update_group_state($context, STATE_REG_NAME);

                $groups_count = bot_get_registered_groups($context);

                Logger::info("Registered group '{$name}' ({$groups_count}th)", __FILE__, $context, true);

                $context->reply(TEXT_REGISTRATION_VERIFIED_RESPONSE_OK, array(
                    '%GROUP%' => $name,
                    '%COUNT%' => $groups_count
                ));

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply(TEXT_REGISTRATION_VERIFIED_RESPONSE_INVALID);
            }
            return true;

        case STATE_REG_NAME:
            //Nop
            msg_processing_handle_group_state($context);
            return true;

        /* CONFIRMATION (2nd step) */

        case STATE_REG_CONFIRMED:
            if(!is_numeric($context->get_response())) {
                $context->reply(TEXT_REGISTRATION_CONFIRMED_RESPONSE_INVALID);
                return true;
            }

            $number = intval($context->get_response());
            if($number < 2) {
                $context->reply(TEXT_REGISTRATION_CONFIRMED_RESPONSE_TOOFEW);
                return true;
            }
            else if($number > 6) {
                $context->reply(TEXT_REGISTRATION_CONFIRMED_RESPONSE_TOOMANY);
                return true;
            }

            bot_update_group_number($context, $number);
            bot_update_group_state($context, STATE_REG_NUMBER);

            Logger::info("Group '{$context->get_group_name()}' registered '{$number}' participants", __FILE__, $context);

            $context->reply(TEXT_REGISTRATION_CONFIRMED_RESPONSE_OK, array(
                '%GROUP%' => $context->get_group_name(),
                '%NUMBER%' => $number
            ));

            msg_processing_handle_group_state($context);

            return true;

        case STATE_REG_NUMBER:
            if($context->get_message()->get_photo_large_id()) {
                $file_path = getFilePath(getClient(), $context->get_message()->get_photo_large_id());
                $photo_path = getPicture(getClient(), $file_path, $context->get_message()->get_photo_large_id(), PHOTO_AVATAR);

                bot_update_group_photo($context, $photo_path);
                bot_update_group_state($context, STATE_REG_READY);

                $groups_count = bot_get_ready_groups($context);

                Logger::info("Group '{$context->get_group_name()}' is ready for the game ({$groups_count}th)", __FILE__, $context, true);

                $context->reply(TEXT_REGISTRATION_NUMBER_RESPONSE_OK, array(
                    '%COUNT%' => $groups_count
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
                $file_path = getFilePath(getClient(), $context->get_message()->get_photo_large_id());
                $photo_path = getPicture(getClient(), $file_path, $context->get_message()->get_photo_large_id(), PHOTO_SELFIE);

                $riddle_id = bot_assign_random_riddle($context);
                if($riddle_id === false || $riddle_id === null) {
                    context->reply(TEXT_FAILURE_GENERAL);
                    return true;
                }

                // Send out riddle
                $riddle_info = bot_get_riddle_info($context, $riddle_id);
                if($riddle_info[0]) {
                    telegram_send_photo($context->get_chat_id(), $riddle_info[0], $riddle_info[1]);
                }
                else {
                    telegram_send_message($context->get_chat_id(), $riddle_info[1]);
                }

                // Forward selfie to channel
                telegram_send_photo(CHAT_CHANNEL, $photo_path, hydrate(TEXT_GAME_SELFIE_FORWARD_CAPTION, array(
                    '%GROUP%' => $context->get_group_name(),
                    '%INDEX%' => $context->get_track_index() + 1
                )));
            }
            else {
                $context->reply(TEXT_GAME_SELFIE_RESPONSE_INVALID);
            }
            return true;

        case STATE_GAME_PUZZLE:
            // TODO: selfie taken, puzzle assigned
            // expecting text
            return true;

        case STATE_GAME_LAST_LOC:
            // TODO: puzzle solved, last location assigned, waiting for qr code
            // expecting deeplink
            return true;

        case STATE_GAME_LAST_PUZ:
            // TODO: qr code scanned, last puzzle assigned
            // expecting deeplink
            return true;

        case STATE_GAME_WON:
            // TODO: final qrcode scanned, victory
            // congratulate group and tell them rank & time
            return true;
    }

    return false;
}
