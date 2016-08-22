<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Registration process message processing.
 */

require_once('lib.php');
require_once('model/context.php');

/**
 * Handles the group's current registration state,
 * sending out a question to the user if needed.
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
            $context->reply(TEXT_REGISTRATION_STATE_NEW);

            telegram_send_photo(
                $context->get_chat_id(),
                'images/quiz-captcha.png',
                TEXT_REGISTRATION_STATE_NEW_CAPTION
            );
            return true;

        case STATE_REG_VERIFIED:
            //Needs to ask for group name
            $context->reply(TEXT_REGISTRATION_STATE_VERIFIED);
            return true;

        case STATE_REG_NAME:
            $context->reply(TEXT_REGISTRATION_STATE_NAME);
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
        case STATE_NEW:
            if('c' === $context->get_response()) {
                $context->reply(TEXT_REGISTRATION_RESPONSE_CORRECT);

                bot_update_group_state($context, STATE_REG_VERIFIED);

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply(TEXT_REGISTRATION_RESPONSE_WRONG);
            }
            return true;

        case STATE_REG_VERIFIED:
            if($context->get_response()) {
                $name = ucwords($context->get_response());

                bot_update_group_name($context, $name);
                bot_update_group_state($context, STATE_REG_NAME);

                $groups_count = bot_get_registered_groups($context);

                Logger::info("Registered group {$name} as {$groups_count}th group", __FILE__, $context, true);

                $context->reply(TEXT_REGISTRATION_RESPONSE_VERIFIED_OK, array(
                    '%NAME%' => $name,
                    '%COUNT%' => $groups_count
                ));

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply(TEXT_REGISTRATION_RESPONSE_VERIFIED_INVALID, array(
                    '%NAME%' => $name
                ));
            }
            return true;

        case STATE_REG_NAME:
            //Nop
            msg_processing_handle_group_state($context);

            return true;
    }

    return false;
}

?>
