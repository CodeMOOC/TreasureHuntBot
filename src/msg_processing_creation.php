<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Game creation process message processing.
 */

$msg_processing_creation_handlers = array(
    GAME_STATE_NEW => function($context) {
        if($context->callback) {
            if(!$context->verify_callback()) {
                return;
            }

            if($context->callback->data === 'confirm') {
                bot_creation_confirm($context);

                $context->comm->reply("Ok! What's the name of your game?");
            }
            else {
                bot_creation_abort($context);

                $context->comm->reply("Nevermind then.");
            }
        }
        else if($context->message) {
            // TODO: handle text confirmation
        }
    }
);

/**
 * Handles the game's current registration process.
 * @param Context $context - message context.
 * @return bool True if handled, false otherwise.
 */
function msg_processing_handle_game_creation($context) {
    global $msg_processing_creation_handlers;

    if(!$context->game || !$context->game->is_admin) {
        return false;
    }

    $game_state = $context->game->game_state;
    Logger::debug("Handling action for game #{$context->game->game_id}, state " . GAME_STATE_MAP[$game_state], __FILE__, $context);

    if(isset($msg_processing_creation_handlers[$game_state])) {
        call_user_func($msg_processing_creation_handlers[$game_state], $context);
        return true;
    }
    else {
        Logger::debug("No callback to handle state " . GAME_STATE_MAP[$game_state], __FILE__, $context);
    }

    return false;
}
