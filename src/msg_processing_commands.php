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
    else if(starts_with($text, '/start')) {
        $payload = extract_command_payload($text);
        if($payload === '') {
            if(null !== $context->get_group_state()) {
                $context->reply(TEXT_CMD_START_REGISTERED);

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply(TEXT_CMD_START_NEW);
            }
        }
        else if(mb_strlen($payload) === 16) {
            Logger::debug("Treasure hunt code: '{$payload}'", __FILE__, $context);
        }
        else {
            Logger::warning("Unsupported /start payload received: '{$payload}'", __FILE__, $context);
        }

        return true;
    }

    return false;
}

 ?>
