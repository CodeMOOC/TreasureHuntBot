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

require_once(dirname(__FILE__) . '/msg_processing_admin.php');
require_once(dirname(__FILE__) . '/msg_processing_commands.php');
require_once(dirname(__FILE__) . '/msg_processing_state.php');

function process_update($context) {
    if($context->game != null && $context->game->is_admin && $context->game->game_state < GAME_STATE_ACTIVE) {
        Logger::debug("Setting up game with admin", __FILE__, $context);

        if(false) {
            // TODO: add game registration steps here (if admin for uncomplete game)
            return;
        }
    }

    if($context->game->is_admin) {
        // TODO: admin commands
    }

    if(DEACTIVATED) {
        $context->comm->reply(__('deactivated'));
        return;
    }

    // Base commands (always on)
    if($context->is_message() && msg_processing_commands($context)) {
        return;
    }

    // Registration and game process
    if(/* is playing && */ $context->is_message() && msg_processing_handle_group_response($context)) {
        return;
    }

    $context->comm->reply(__('fallback_response'));
}

$context = new Context($update);
process_update($context);
$context->close();
