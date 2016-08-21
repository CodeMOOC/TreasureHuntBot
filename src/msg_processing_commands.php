<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Default command message processing.
 */

require_once('lib.php');
require_once('msg_processing_registration.php');
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
    else if(starts_with($text, '/start ' . CODE_REGISTER)) {
        if(null === $context->get_group_id()) {
            if(!bot_register_new_group($context)) {
                //TODO: generalize this
                $context->reply("Qualcosa è andato storto. Chi di dovere è stato avvertito.");
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
        $payload = extract_command_payload($text, '/start');
        if($payload === '') {
            if(null !== $context->get_group_id()) {
                $context->reply(TEXT_CMD_START_REGISTERED);

                msg_processing_handle_group_state($context);
            }
            else {
                $context->reply(TEXT_CMD_START_NEW);
            }
        }
        else if(strlen($payload) === 16) {
            //Special treasure-hunt code sent
            echo "Treasure hunt code: {$payload}." . PHP_EOL;
        }
        else {
            echo "Unknown payload ({$payload})." . PHP_EOL;
            error_log("Unsupported /start payload ({$payload})");
        }

        return true;
    }

    return false;
}

 ?>
