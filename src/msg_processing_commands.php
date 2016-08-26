<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Default command message processing.
 */

require_once('lib.php');
require_once('msg_processing_state.php');
require_once('model/context.php');

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
    else if(starts_with($text, '/reset')) {
        $context->reply(TEXT_CMD_RESET);

        return true;
    }
    else if($text === '/start ' . CODE_REGISTER) {
        // Registration command

        if(null === $context->get_group_state()) {
            if(!bot_register_new_group($context)) {
                $context->reply(TEXT_FAILURE_GENERAL);
            }
            else {
                $context->reply(TEXT_CMD_REGISTER_CONFIRM);

                msg_processing_handle_group_state($context);
            }
        }
        else {
            $context->reply(TEXT_CMD_REGISTER_REGISTERED);

            msg_processing_handle_group_state($context);
        }

        return true;
    }
    else if($text === '/start ' . CODE_ACTIVATE) {
        // Activation command

        $result = bot_promote_to_active($context);
        switch($result) {
            case true:
                $context->reply(TEXT_ADVANCEMENT_ACTIVATED);
                break;

            case 'not_found':
                $context->reply(TEXT_FAILURE_GROUP_NOT_FOUND);
                break;

            case 'already_active':
                $context->reply(TEXT_FAILURE_GROUP_ALREADY_ACTIVE);

                msg_processing_handle_group_state($context);
                break;

            case 'invalid_state':
                $context->reply(TEXT_FAILURE_GROUP_INVALID_STATE);

                msg_processing_handle_group_state($context);
                break;

            case false:
            default:
                $context->reply(TEXT_FAILURE_GENERAL);
                break;
        }

        return true;
    }
    else if(starts_with($text, '/start')) {
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
        // Secret location code
        else if(mb_strlen($payload) === 16) {
            Logger::debug("Treasure hunt code: '{$payload}'", __FILE__, $context);

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

 ?>
