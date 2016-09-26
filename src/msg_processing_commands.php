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
                // Demo mode: advance directly to selfie mode
                bot_update_group_state($context, STATE_GAME_SELFIE);

                msg_processing_handle_group_state($context);
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
    else if($text === '/start ' . CODE_VICTORY) {
        Logger::debug("Prize code scanned", __FILE__, $context);

        if($context->get_group_state() === STATE_GAME_LAST_PUZ) {
            $winning_group = bot_get_winning_group($context);
            if($winning_group !== false) {
                $context->reply(TEXT_CMD_START_PRIZE_TOOLATE, array(
                    '%GROUP%' => $winning_group
                ));
            }
            else {
                bot_update_group_state($context, STATE_GAME_WON);

                msg_processing_handle_group_state($context);

                Logger::info("Group {$context->get_group_id()} has reached the prize and won", __FILE__, $context, true);

                $context->channel(TEXT_GAME_WON_CHANNEL);
            }
        }
        else {
            $context->reply(TEXT_CMD_START_PRIZE_INVALID);

            Logger::warning("Group {$context->get_group_id()} has reached the prize but is in state {$context->get_group_state()}", __FILE__, $context);
        }

        return true;
    }
    else if(starts_with($text, '/start')) {
        Logger::debug("Start command with payload");

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

                msg_processing_handle_group_state($context);

                if($context->get_group_state() === STATE_GAME_LAST_PUZ) {
                    //TODO warn others!
                }
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
