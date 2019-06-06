<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing functionality,
 * used by both pull and push scripts.
 * Expected an update data structure as $update.
 */

require_once(dirname(__FILE__) . '/game.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/model/context.php');

require_once(dirname(__FILE__) . '/msg_helpers.php');
require_once(dirname(__FILE__) . '/msg_processing_admin.php');
require_once(dirname(__FILE__) . '/msg_processing_commands.php');
require_once(dirname(__FILE__) . '/msg_processing_creation.php');
require_once(dirname(__FILE__) . '/msg_processing_localization.php');
require_once(dirname(__FILE__) . '/msg_processing_state.php');

function process_update($context) {
    if(msg_processing_admin($context)) {
        return;
    }

    if(DEACTIVATED) {
        $context->comm->reply(__('deactivated'));
        return;
    }

    // Localization process
    if(msg_processing_localization($context)) {
        return;
    }

    // Base game commands
    if(msg_processing_commands($context)) {
        return;
    }

    // Game creation process
    if($context->game && $context->game->is_admin && $context->game->game_state <= GAME_STATE_ACTIVE) {
        Logger::debug("Game setup process still running", __FILE__, $context);

        if(msg_processing_handle_game_creation($context)) {
            return;
        }
    }

    // Registration and play process
    if($context->game && $context->game->game_id !== null && !$context->game->is_admin) {
        if(msg_processing_handle_group_response($context)) {
            return;
        }
    }

    if($context->is_message()) {
        // Default responses only to messages
        $context->comm->reply(__('fallback_response'));
    }
}

$context = new Context($update);

process_update($context);
if($context->is_callback()) {
    // If we are handling a callback, set the callback as processed now
    $callback_id = $context->callback->callback_id;
    telegram_answer_callback_query($callback_id, TRUE);
}

$context->close();
